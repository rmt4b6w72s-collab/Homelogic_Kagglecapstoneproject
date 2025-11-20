import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';
import { Clock, CheckCircle, XCircle, Search, Eye, Check, X, Building2, Mail, Phone, MapPin } from 'lucide-react';
import { DashboardSkeleton } from '../components/ui/SkeletonLoader';
import { useToastContext } from '../contexts/ToastContext';

export default function FacilityRegistrations() {
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const { showToast } = useToastContext();
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('pending');
  const [selectedRegistration, setSelectedRegistration] = useState(null);
  const [showApproveModal, setShowApproveModal] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ['facility-registrations', statusFilter, search],
    queryFn: async () => {
      const params = { status: statusFilter };
      if (search) params.search = search;
      const res = await api.get('/facility-registrations', { params });
      return res.data;
    },
  });

  const approveMutation = useMutation({
    mutationFn: async (data) => {
      const response = await api.post(`/facilities/approve-registration/${selectedRegistration.id}`, data, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['facility-registrations']);
      queryClient.invalidateQueries(['super-admin-stats']);
      setShowApproveModal(false);
      setSelectedRegistration(null);
      showToast('Facility approved and created successfully!', 'success');
    },
    onError: (error) => {
      showToast(error.response?.data?.message || 'Failed to approve registration', 'error');
    },
  });

  const rejectMutation = useMutation({
    mutationFn: async (id) => {
      await api.put(`/facility-registrations/${id}`, { status: 'rejected' });
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['facility-registrations']);
      queryClient.invalidateQueries(['super-admin-stats']);
      showToast('Registration rejected', 'success');
    },
  });

  const getStatusBadge = (status) => {
    const badges = {
      pending: { icon: Clock, color: 'bg-amber-100 text-amber-800', label: 'Pending' },
      approved: { icon: CheckCircle, color: 'bg-green-100 text-green-800', label: 'Approved' },
      rejected: { icon: XCircle, color: 'bg-red-100 text-red-800', label: 'Rejected' },
    };
    const badge = badges[status] || badges.pending;
    const Icon = badge.icon;
    return (
      <span className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium ${badge.color}`}>
        <Icon className="w-4 h-4" />
        {badge.label}
      </span>
    );
  };

  if (isLoading) {
    return <DashboardSkeleton />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
          <div>
            <h2 className="text-2xl font-bold text-gray-900 mb-2">Facility Registrations</h2>
            <p className="text-gray-600">Review and approve facility registration requests</p>
          </div>
        </div>

        {/* Filters */}
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="relative flex-1 max-w-md">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search by facility name, contact, or email..."
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
            />
          </div>
          <div className="flex gap-2">
            {['pending', 'approved', 'rejected'].map((status) => (
              <button
                key={status}
                onClick={() => setStatusFilter(status)}
                className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                  statusFilter === status
                    ? 'bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)]'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                {status.charAt(0).toUpperCase() + status.slice(1)}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Registrations List */}
      {data?.data?.length ? (
        <div className="grid grid-cols-1 gap-6">
          {data.data.map((registration) => (
            <div key={registration.id} className="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
              <div className="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div className="flex-1">
                  <div className="flex items-start justify-between mb-4">
                    <div>
                      <div className="flex items-center gap-3 mb-2">
                        <Building2 className="w-6 h-6 text-[var(--theme-primary)]" />
                        <h3 className="text-xl font-bold text-gray-900">{registration.facility_name}</h3>
                        {getStatusBadge(registration.status)}
                      </div>
                      <p className="text-sm text-gray-600 mb-3">Requested on {new Date(registration.created_at).toLocaleDateString()}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div className="flex items-center gap-2 text-gray-700">
                      <Mail className="w-4 h-4 text-gray-400" />
                      <span className="text-sm">{registration.email}</span>
                    </div>
                    {registration.phone && (
                      <div className="flex items-center gap-2 text-gray-700">
                        <Phone className="w-4 h-4 text-gray-400" />
                        <span className="text-sm">{registration.phone}</span>
                      </div>
                    )}
                    <div className="flex items-start gap-2 text-gray-700 md:col-span-2">
                      <MapPin className="w-4 h-4 text-gray-400 mt-0.5" />
                      <span className="text-sm">{registration.address || 'No address provided'}</span>
                    </div>
                    {registration.requested_subdomain && (
                      <div className="flex items-center gap-2 text-gray-700">
                        <span className="text-sm font-medium">Subdomain:</span>
                        <span className="text-sm text-[var(--theme-primary)] font-mono">{registration.requested_subdomain}</span>
                      </div>
                    )}
                  </div>

                  <div className="flex items-center gap-2 text-sm text-gray-600">
                    <span>Contact:</span>
                    <span className="font-medium">{registration.contact_name}</span>
                  </div>
                </div>

                {registration.status === 'pending' && (
                  <div className="flex gap-2 lg:flex-col">
                    <button
                      onClick={() => {
                        setSelectedRegistration(registration);
                        setShowApproveModal(true);
                      }}
                      className="px-4 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg hover:bg-[var(--theme-primary-hover)] transition-colors flex items-center gap-2 whitespace-nowrap"
                    >
                      <Check className="w-4 h-4" />
                      Approve & Setup
                    </button>
                    <button
                      onClick={() => {
                        if (window.confirm('Are you sure you want to reject this registration?')) {
                          rejectMutation.mutate(registration.id);
                        }
                      }}
                      className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2 whitespace-nowrap"
                    >
                      <X className="w-4 h-4" />
                      Reject
                    </button>
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="bg-white rounded-lg shadow p-12 text-center">
          <Clock className="w-16 h-16 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-600 text-lg font-medium">No {statusFilter} registrations found</p>
        </div>
      )}

      {/* Approve Modal */}
      {showApproveModal && selectedRegistration && (
        <ApproveRegistrationModal
          registration={selectedRegistration}
          onClose={() => {
            setShowApproveModal(false);
            setSelectedRegistration(null);
          }}
          onSubmit={(data) => approveMutation.mutate(data)}
          isLoading={approveMutation.isPending}
        />
      )}
    </div>
  );
}

function ApproveRegistrationModal({ registration, onClose, onSubmit, isLoading }) {
  const [form, setForm] = useState({
    facility_name: registration.facility_name,
    subdomain: registration.requested_subdomain || registration.facility_name.toLowerCase().replace(/\s+/g, '-'),
    address: registration.address || '',
    phone: registration.phone || '',
    email: registration.email || '',
    branch_name: 'Main Branch',
    branch_address: registration.address || '',
    owner_name: registration.contact_name,
    owner_email: registration.email,
    owner_role: 'administrator',
    owner_password: '',
    logo: null,
    primary_color: '#25603E',
    secondary_color: '#8B4513',
    accent_color: '#F5F5DC',
  });

  const [errors, setErrors] = useState({});
  const [logoPreview, setLogoPreview] = useState(null);

  const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setForm({ ...form, logo: file });
      const reader = new FileReader();
      reader.onloadend = () => setLogoPreview(reader.result);
      reader.readAsDataURL(file);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});

    const formData = new FormData();
    Object.keys(form).forEach((key) => {
      if (form[key] !== null && form[key] !== '') {
        if (key === 'logo' && form.logo instanceof File) {
          formData.append('logo', form.logo);
        } else if (key !== 'logo') {
          formData.append(key, form[key]);
        }
      }
    });

    onSubmit(formData);
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        {/* Header - Fixed */}
        <div className="flex-shrink-0 p-6 border-b">
          <div className="flex items-center justify-between">
            <h2 className="text-2xl font-bold text-gray-900">Approve & Create Facility</h2>
            <button onClick={onClose} className="text-gray-400 hover:text-gray-600 text-2xl w-8 h-8 flex items-center justify-center">×</button>
          </div>
        </div>

        {/* Scrollable Content */}
        <div className="flex-1 overflow-y-auto p-6">
          <form onSubmit={handleSubmit} id="approve-facility-form" className="space-y-6">
            {/* Facility Information */}
            <div className="border-b pb-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Facility Information</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Facility Name *</label>
                  <input
                    value={form.facility_name}
                    onChange={(e) => setForm({ ...form, facility_name: e.target.value })}
                    required
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Subdomain *</label>
                  <input
                    value={form.subdomain}
                    onChange={(e) => setForm({ ...form, subdomain: e.target.value.replace(/[^a-z0-9-]/g, '').toLowerCase() })}
                    required
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent font-mono"
                  />
                  <p className="text-xs text-gray-500 mt-1">e.g., {form.subdomain}.yourapp.com</p>
                </div>
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                  <textarea
                    value={form.address}
                    onChange={(e) => setForm({ ...form, address: e.target.value })}
                    rows={2}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                  <input
                    value={form.phone}
                    onChange={(e) => setForm({ ...form, phone: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                  <input
                    type="email"
                    value={form.email}
                    onChange={(e) => setForm({ ...form, email: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                  />
                </div>
              </div>
            </div>

            {/* Branding */}
            <div className="border-b pb-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Branding & Customization</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                  <input
                    type="file"
                    accept="image/*"
                    onChange={handleLogoChange}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                  />
                  {logoPreview && (
                    <div className="mt-2">
                      <img src={logoPreview} alt="Logo preview" className="w-32 h-32 object-contain border rounded-lg" />
                    </div>
                  )}
                </div>
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
                    <div className="flex gap-2">
                      <input
                        type="color"
                        value={form.primary_color}
                        onChange={(e) => setForm({ ...form, primary_color: e.target.value })}
                        className="w-16 h-10 border border-gray-300 rounded cursor-pointer"
                      />
                      <input
                        type="text"
                        value={form.primary_color}
                        onChange={(e) => setForm({ ...form, primary_color: e.target.value })}
                        className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent font-mono text-sm"
                        placeholder="#25603E"
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Secondary Color</label>
                    <div className="flex gap-2">
                      <input
                        type="color"
                        value={form.secondary_color}
                        onChange={(e) => setForm({ ...form, secondary_color: e.target.value })}
                        className="w-16 h-10 border border-gray-300 rounded cursor-pointer"
                      />
                      <input
                        type="text"
                        value={form.secondary_color}
                        onChange={(e) => setForm({ ...form, secondary_color: e.target.value })}
                        className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent font-mono text-sm"
                        placeholder="#8B4513"
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Accent Color</label>
                    <div className="flex gap-2">
                      <input
                        type="color"
                        value={form.accent_color}
                        onChange={(e) => setForm({ ...form, accent_color: e.target.value })}
                        className="w-16 h-10 border border-gray-300 rounded cursor-pointer"
                      />
                      <input
                        type="text"
                        value={form.accent_color}
                        onChange={(e) => setForm({ ...form, accent_color: e.target.value })}
                        className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent font-mono text-sm"
                        placeholder="#F5F5DC"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Branch Setup */}
            <div className="border-b pb-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Initial Branch Setup</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Branch Name *</label>
                  <input
                    value={form.branch_name}
                    onChange={(e) => setForm({ ...form, branch_name: e.target.value })}
                    required
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                  />
                </div>
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Branch Address</label>
                  <textarea
                    value={form.branch_address}
                    onChange={(e) => setForm({ ...form, branch_address: e.target.value })}
                    rows={2}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                  />
                </div>
              </div>
            </div>

            {/* Owner Account */}
            <div className="pb-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Facility Owner Account</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Owner Name *</label>
                  <input
                    value={form.owner_name}
                    onChange={(e) => setForm({ ...form, owner_name: e.target.value })}
                    required
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Owner Email *</label>
                  <input
                    type="email"
                    value={form.owner_email}
                    onChange={(e) => setForm({ ...form, owner_email: e.target.value })}
                    required
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Owner Role *</label>
                  <select
                    value={form.owner_role}
                    onChange={(e) => setForm({ ...form, owner_role: e.target.value })}
                    required
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                  >
                    <option value="administrator">Administrator</option>
                    <option value="manager">Manager</option>
                    <option value="clinical_supervisor">Clinical Supervisor</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                  <input
                    type="password"
                    value={form.owner_password}
                    onChange={(e) => setForm({ ...form, owner_password: e.target.value })}
                    required
                    minLength={8}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                  />
                  <p className="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                </div>
              </div>
            </div>

          </form>
        </div>
        
        {/* Footer - Fixed */}
        <div className="flex-shrink-0 p-6 border-t bg-gray-50">
          <div className="flex items-center justify-end gap-3">
            <button
              type="button"
              onClick={onClose}
              className="px-6 py-2 border border-gray-300 rounded-lg hover:bg-white transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              form="approve-facility-form"
              disabled={isLoading}
              className="px-6 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg hover:bg-[var(--theme-primary-hover)] disabled:opacity-50 transition-colors"
            >
              {isLoading ? 'Creating...' : 'Approve & Create Facility'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

