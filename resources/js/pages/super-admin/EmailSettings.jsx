import React, { useMemo, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Mail, Send, Info, Users, FileText } from 'lucide-react';
import api from '../../services/api';
import { useToastContext } from '../../contexts/ToastContext';
import Tabs, { TabsList, TabsTrigger, TabsContent } from '../../components/ui/radix/Tabs';
import EmailRecipientConfig from '../../components/EmailRecipientConfig';
import EmailTemplateEditor from '../../components/EmailTemplateEditor';
import NotificationTypeSelector from '../../components/NotificationTypeSelector';

export default function EmailSettings() {
  const toast = useToastContext();
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState('smtp');
  const [selectedNotificationType, setSelectedNotificationType] = useState('');

  // TODO: Replace with selected facility selector for super admins
  const { data: currentUser } = useQuery({
    queryKey: ['me'],
    queryFn: async () => {
      const response = await api.get('/me');
      return response.data?.data || response.data;
    },
  });

  const facilityId = useMemo(() => {
    if (typeof window !== 'undefined') {
      const stored = window.localStorage.getItem('super_admin_selected_facility_id');
      if (stored) return stored;
    }
    return currentUser?.facility_id;
  }, [currentUser]);

  const { data: settings, isLoading } = useQuery({
    enabled: !!facilityId,
    queryKey: ['facility-settings', facilityId, 'email'],
    queryFn: async () => {
      const response = await api.get(`/facilities/${facilityId}/settings/email`);
      return response.data?.data || {};
    },
  });

  const [mailDriver, setMailDriver] = useState(settings?.mail_driver?.value || 'smtp');

  const defaultValues = useMemo(
    () => ({
      mail_driver: settings?.mail_driver?.value || 'smtp',
      mail_host: settings?.mail_host?.value || '',
      mail_port: settings?.mail_port?.value || 587,
      mail_username: settings?.mail_username?.value || '',
      mail_password: '',
      mail_encryption: settings?.mail_encryption?.value || 'tls',
      mail_from_address: settings?.mail_from_address?.value || '',
      mail_from_name: settings?.mail_from_name?.value || '',
      ses_region: settings?.ses_region?.value || '',
      ses_configuration_set: settings?.ses_configuration_set?.value || '',
      test_recipient: settings?.test_recipient?.value || '',
    }),
    [settings]
  );

  // Update mailDriver state when settings load
  React.useEffect(() => {
    if (settings?.mail_driver?.value) {
      setMailDriver(settings.mail_driver.value);
    }
  }, [settings]);

  const isSESDriver = mailDriver === 'ses' || mailDriver === 'ses-v2';

  const saveMutation = useMutation({
    mutationFn: async (values) => {
      const isSES = values.mail_driver === 'ses' || values.mail_driver === 'ses-v2';
      const payload = {
        settings: {
          mail_driver: { value: values.mail_driver, type: 'string' },
          mail_from_address: { value: values.mail_from_address, type: 'string' },
          mail_from_name: { value: values.mail_from_name, type: 'string' },
          test_recipient: { value: values.test_recipient, type: 'string' },
          // SMTP-specific fields
          ...(!isSES && {
            mail_host: { value: values.mail_host, type: 'string' },
            mail_port: { value: values.mail_port, type: 'integer' },
            mail_username: { value: values.mail_username, type: 'string' },
            mail_encryption: { value: values.mail_encryption, type: 'string' },
            ...(values.mail_password
              ? { mail_password: { value: values.mail_password, type: 'string' } }
              : {}),
          }),
          // SES-specific fields
          ...(isSES && {
            ses_region: { value: values.ses_region || null, type: 'string' },
            ses_configuration_set: { value: values.ses_configuration_set || null, type: 'string' },
          }),
        },
      };

      const response = await api.put(`/facilities/${facilityId}/settings/email`, payload);
      return response.data;
    },
    onSuccess: () => {
      toast.showToast('Email settings updated successfully.', 'success');
      queryClient.invalidateQueries(['facility-settings', facilityId, 'email']);
    },
    onError: (error) => {
      toast.showToast(
        error.response?.data?.message || 'Failed to update email settings',
        'error'
      );
    },
  });

  const testEmailMutation = useMutation({
    mutationFn: async (recipient) => {
      const response = await api.post(`/facilities/${facilityId}/settings/email/test`, {
        recipient,
      });
      return response.data;
    },
    onSuccess: (data) => {
      toast.showToast(`Test email sent successfully to ${data.recipient}`, 'success');
    },
    onError: (error) => {
      const errorMessage = error.response?.data?.message || 'Failed to send test email';
      toast.showToast(errorMessage, 'error');
    },
  });

  const handleSubmit = (event) => {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const values = Object.fromEntries(formData.entries());
    values.mail_port = values.mail_port ? parseInt(values.mail_port, 10) : null;
    saveMutation.mutate(values);
  };

  const handleTestEmail = (event) => {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    const recipient = formData.get('test_recipient');
    if (!recipient) {
      toast.showToast('Please enter a test recipient email address', 'error');
      return;
    }
    testEmailMutation.mutate(recipient);
  };

  // Fetch notification configs
  const { data: notificationConfigs } = useQuery({
    enabled: !!facilityId && activeTab === 'recipients',
    queryKey: ['email-notification-configs', facilityId],
    queryFn: async () => {
      const response = await api.get(`/facilities/${facilityId}/email-notification-configs`);
      return response.data?.data || [];
    },
  });

  // Fetch templates
  const { data: templates } = useQuery({
    enabled: !!facilityId && activeTab === 'templates',
    queryKey: ['email-templates', facilityId],
    queryFn: async () => {
      const response = await api.get(`/facilities/${facilityId}/email-templates`);
      return response.data?.data || [];
    },
  });

  // Fetch specific template
  const { data: currentTemplate } = useQuery({
    enabled: !!facilityId && !!selectedNotificationType && activeTab === 'templates',
    queryKey: ['email-template', facilityId, selectedNotificationType],
    queryFn: async () => {
      try {
        const response = await api.get(
          `/facilities/${facilityId}/email-templates/${selectedNotificationType}`
        );
        return response.data?.data;
      } catch (error) {
        if (error.response?.status === 404) {
          return null;
        }
        throw error;
      }
    },
  });

  // Fetch specific config
  const { data: currentConfig } = useQuery({
    enabled: !!facilityId && !!selectedNotificationType && activeTab === 'recipients',
    queryKey: ['email-notification-config', facilityId, selectedNotificationType],
    queryFn: async () => {
      try {
        const response = await api.get(
          `/facilities/${facilityId}/email-notification-configs/${selectedNotificationType}`
        );
        return response.data?.data;
      } catch (error) {
        if (error.response?.status === 404) {
          return null;
        }
        throw error;
      }
    },
  });

  // Save notification config
  const saveConfigMutation = useMutation({
    mutationFn: async (data) => {
      const response = await api.put(
        `/facilities/${facilityId}/email-notification-configs/${selectedNotificationType}`,
        data
      );
      return response.data;
    },
    onSuccess: () => {
      toast.showToast('Recipient configuration saved successfully', 'success');
      queryClient.invalidateQueries(['email-notification-configs', facilityId]);
      queryClient.invalidateQueries(['email-notification-config', facilityId, selectedNotificationType]);
    },
    onError: (error) => {
      toast.showToast(
        error.response?.data?.message || 'Failed to save recipient configuration',
        'error'
      );
    },
  });

  // Save template
  const saveTemplateMutation = useMutation({
    mutationFn: async (data) => {
      const response = await api.put(
        `/facilities/${facilityId}/email-templates/${selectedNotificationType}`,
        data
      );
      return response.data;
    },
    onSuccess: () => {
      toast.showToast('Email template saved successfully', 'success');
      queryClient.invalidateQueries(['email-templates', facilityId]);
      queryClient.invalidateQueries(['email-template', facilityId, selectedNotificationType]);
    },
    onError: (error) => {
      toast.showToast(
        error.response?.data?.message || 'Failed to save email template',
        'error'
      );
    },
  });

  const handleConfigChange = (configData) => {
    if (selectedNotificationType) {
      saveConfigMutation.mutate(configData);
    }
  };

  const handleTemplateSave = (templateData) => {
    if (selectedNotificationType) {
      saveTemplateMutation.mutate(templateData);
    }
  };

  if (!facilityId) {
    return (
      <div className="p-6 bg-white rounded-xl shadow-sm">
        <p className="text-sm text-gray-600">
          Email settings are available once a facility is associated with your account.
        </p>
      </div>
    );
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[200px]">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--theme-primary)]" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-xl shadow-sm p-6 flex items-center space-x-3">
        <div className="h-10 w-10 flex items-center justify-center rounded-lg bg-[var(--theme-primary)]/10 text-[var(--theme-primary)]">
          <Mail className="w-5 h-5" />
        </div>
        <div>
          <h1 className="text-xl font-semibold text-gray-900">Email Settings</h1>
          <p className="text-sm text-gray-500">
            Configure email delivery, recipients, and templates for this facility.
          </p>
        </div>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
        <TabsList className="w-full justify-start">
          <TabsTrigger value="smtp">
            <Mail className="w-4 h-4 mr-2" />
            SMTP/SES Configuration
          </TabsTrigger>
          <TabsTrigger value="recipients">
            <Users className="w-4 h-4 mr-2" />
            Notification Recipients
          </TabsTrigger>
          <TabsTrigger value="templates">
            <FileText className="w-4 h-4 mr-2" />
            Email Templates
          </TabsTrigger>
        </TabsList>

        <TabsContent value="smtp">
          <form onSubmit={handleSubmit} className="bg-white rounded-xl shadow-sm p-6 space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Driver</label>
            <select
              name="mail_driver"
              value={mailDriver}
              onChange={(e) => setMailDriver(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
            >
              <option value="smtp">SMTP</option>
              <option value="ses">Amazon SES</option>
              <option value="ses-v2">Amazon SES v2</option>
              <option value="sendmail">Sendmail</option>
              <option value="log">Log (development)</option>
            </select>
          </div>

          {isSESDriver && (
            <div className="md:col-span-2">
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-start space-x-2">
                <Info className="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
                <div className="text-sm text-blue-800">
                  <p className="font-medium mb-1">AWS Credentials</p>
                  <p>
                    AWS credentials are configured globally in your .env file (AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION).
                    You can optionally override the region below.
                  </p>
                </div>
              </div>
            </div>
          )}

          {!isSESDriver && (
            <>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Host</label>
                <input
                  name="mail_host"
                  defaultValue={defaultValues.mail_host}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
                  placeholder="smtp.example.com"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Port</label>
                <input
                  name="mail_port"
                  type="number"
                  defaultValue={defaultValues.mail_port}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                <select
                  name="mail_encryption"
                  defaultValue={defaultValues.mail_encryption}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
                >
                  <option value="tls">TLS</option>
                  <option value="ssl">SSL</option>
                  <option value="null">None</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input
                  name="mail_username"
                  defaultValue={defaultValues.mail_username}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input
                  name="mail_password"
                  type="password"
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
                  placeholder="••••••••"
                />
                <p className="mt-1 text-xs text-gray-400">
                  Leave blank to keep the existing password.
                </p>
              </div>
            </>
          )}

          {isSESDriver && (
            <>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  AWS Region (Optional)
                </label>
                <input
                  name="ses_region"
                  defaultValue={defaultValues.ses_region}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
                  placeholder="us-east-1"
                />
                <p className="mt-1 text-xs text-gray-500">
                  Leave blank to use the global AWS_DEFAULT_REGION from .env
                </p>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Configuration Set (Optional)
                </label>
                <input
                  name="ses_configuration_set"
                  defaultValue={defaultValues.ses_configuration_set}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
                  placeholder="my-config-set"
                />
                <p className="mt-1 text-xs text-gray-500">
                  Optional SES configuration set for tracking and analytics
                </p>
              </div>
            </>
          )}

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              From Email Address
            </label>
            <input
              name="mail_from_address"
              defaultValue={defaultValues.mail_from_address}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
              placeholder="noreply@example.com"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              From Name
            </label>
            <input
              name="mail_from_name"
              defaultValue={defaultValues.mail_from_name}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
              placeholder="Facility Name"
            />
          </div>
        </div>

        <div className="border-t border-gray-200 pt-4 mt-4 space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="text-sm font-semibold text-gray-900">Test Email</h2>
            <button
              type="button"
              onClick={handleSubmit}
              disabled={saveMutation.isPending}
              className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {saveMutation.isPending ? 'Saving...' : 'Save Settings'}
            </button>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-[2fr_1fr] gap-4 items-end">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Test Recipient Email
              </label>
              <input
                name="test_recipient"
                defaultValue={defaultValues.test_recipient}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
                placeholder="you@example.com"
              />
            </div>
            <button
              type="button"
              onClick={handleTestEmail}
              disabled={testEmailMutation.isPending}
              className="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold rounded-lg bg-[var(--theme-primary)] text-white hover:bg-[var(--theme-primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {testEmailMutation.isPending ? (
                <>
                  <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2" />
                  Sending...
                </>
              ) : (
                <>
                  <Send className="w-4 h-4 mr-2" />
                  Send Test Email
                </>
              )}
            </button>
          </div>
        </div>
      </form>
        </TabsContent>

        <TabsContent value="recipients">
          <div className="bg-white rounded-xl shadow-sm p-6 space-y-6">
            <div>
              <h2 className="text-lg font-semibold text-gray-900 mb-2">
                Configure Email Recipients
              </h2>
              <p className="text-sm text-gray-500 mb-4">
                Select who should receive emails for each notification type. You can configure by role and/or specific users.
              </p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Notification Type
              </label>
              <NotificationTypeSelector
                value={selectedNotificationType}
                onChange={setSelectedNotificationType}
              />
            </div>

            {selectedNotificationType && (
              <div className="border-t border-gray-200 pt-6">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-md font-medium text-gray-900">
                    Recipient Configuration for {selectedNotificationType.replace(/_/g, ' ')}
                  </h3>
                  <label className="flex items-center gap-2">
                    <input
                      type="checkbox"
                      checked={currentConfig?.enabled ?? true}
                      onChange={(e) => {
                        handleConfigChange({
                          enabled: e.target.checked,
                          recipient_roles: currentConfig?.recipient_roles || [],
                          recipient_user_ids: currentConfig?.recipient_user_ids || [],
                        });
                      }}
                      className="w-4 h-4 text-[var(--theme-primary)] border-gray-300 rounded focus:ring-[var(--theme-primary)]"
                    />
                    <span className="text-sm text-gray-700">Enable this notification</span>
                  </label>
                </div>
                <EmailRecipientConfig
                  facilityId={facilityId}
                  config={currentConfig}
                  onChange={handleConfigChange}
                />
              </div>
            )}
          </div>
        </TabsContent>

        <TabsContent value="templates">
          <div className="bg-white rounded-xl shadow-sm p-6 space-y-6">
            <div>
              <h2 className="text-lg font-semibold text-gray-900 mb-2">
                Email Template Management
              </h2>
              <p className="text-sm text-gray-500 mb-4">
                Create and customize HTML email templates for each notification type. Use variables like {'{{variableName}}'} to insert dynamic content.
              </p>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Notification Type
              </label>
              <NotificationTypeSelector
                value={selectedNotificationType}
                onChange={setSelectedNotificationType}
              />
            </div>

            {selectedNotificationType && (
              <div className="border-t border-gray-200 pt-6">
                <EmailTemplateEditor
                  facilityId={facilityId}
                  notificationType={selectedNotificationType}
                  template={currentTemplate}
                  onSave={handleTemplateSave}
                />
              </div>
            )}
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}


