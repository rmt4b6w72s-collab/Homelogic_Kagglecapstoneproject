import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../services/api';
import {
  ArrowLeft, Building2, Palette, Users,
  Eye, EyeOff, CheckCircle
} from 'lucide-react';
import { useToastContext } from '../contexts/ToastContext';

export default function ApproveFacilityRegistration() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { showToast } = useToastContext();
  const queryClient = useQueryClient();
  const [showPassword, setShowPassword] = useState(false);
  const [activeTab, setActiveTab] = useState('facility');

  // Fetch registration details
  const { data: registration, isLoading } = useQuery({
    queryKey: ['facility-registration', id],
    queryFn: async () => {
      const res = await api.get(`/facility-registrations/${id}`);
      return res.data;
    },
    enabled: !!id,
  });

  const [form, setForm] = useState({
    facility_name: '',
    subdomain: '',
    address: '',
    phone: '',
    email: '',
    branch_name: 'Main Branch',
    branch_address: '',
    owner_name: '',
    owner_email: '',
    owner_role: 'administrator',
    owner_password: '',
    logo: null,
    primary_color: '#25603E',
    secondary_color: '#8B4513',
    accent_color: '#F5F5DC',
  });

  const [logoPreview, setLogoPreview] = useState(null);
  const [errors, setErrors] = useState({});

  // Initialize form with registration data
  useEffect(() => {
    if (registration) {
      setForm({
        facility_name: registration.facility_name || '',
        subdomain: registration.requested_subdomain || registration.facility_name?.toLowerCase().replace(/\s+/g, '-') || '',
        address: registration.address || '',
        phone: registration.phone || '',
        email: registration.email || '',
        branch_name: 'Main Branch',
        branch_address: registration.address || '',
        owner_name: registration.contact_name || '',
        owner_email: registration.email || '',
        owner_role: 'administrator',
        owner_password: '',
        logo: null,
        primary_color: '#25603E',
        secondary_color: '#8B4513',
        accent_color: '#F5F5DC',
      });
    }
  }, [registration]);

  const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setForm({ ...form, logo: file });
      const reader = new FileReader();
      reader.onloadend = () => setLogoPreview(reader.result);
      reader.readAsDataURL(file);
    }
  };

  const approveMutation = useMutation({
    mutationFn: async (formData) => {
      const response = await api.post(`/facilities/approve-registration/${id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['facility-registrations']);
      queryClient.invalidateQueries(['super-admin-stats']);
      showToast('Facility approved and created successfully!', 'success');
      navigate('/super-admin/facility-registrations');
    },
    onError: (error) => {
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      }
      showToast(error.response?.data?.message || 'Failed to approve registration', 'error');
    },
  });

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

    approveMutation.mutate(formData);
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p className="mt-4 text-gray-600">Loading registration details...</p>
        </div>
      </div>
    );
  }

  if (!registration) {
    return (
      <div className="space-y-6">
        <div className="bg-white rounded-lg shadow p-6">
          <p className="text-gray-600">Registration not found</p>
          <button
            onClick={() => navigate('/super-admin/facility-registrations')}
            className="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            Back to Registrations
          </button>
        </div>
      </div>
    );
  }

  const tabs = [
    { id: 'facility', label: 'Facility Information', icon: Building2 },
    { id: 'branding', label: 'Branding', icon: Palette },
    { id: 'branch', label: 'Branch Setup', icon: Building2 },
    { id: 'owner', label: 'Owner Account', icon: Users },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-4">
            <button
              onClick={() => navigate('/super-admin/facility-registrations')}
              className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <ArrowLeft className="w-5 h-5 text-gray-600" />
            </button>
            <div>
              <h2 className="text-2xl font-bold text-gray-900">Approve & Create Facility</h2>
              <p className="text-gray-600">Review and approve facility registration: {registration.facility_name}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Form with Tabs */}
      <form onSubmit={handleSubmit}>
        <div className="bg-white rounded-lg shadow">
          {/* Tabs */}
          <div className="p-6 pb-0">
            <nav className="flex flex-wrap gap-2 rounded-2xl bg-white p-2 shadow-sm ring-1 ring-gray-100">
              {tabs.map((tab) => {
                const Icon = tab.icon;
                return (
                  <button
                    key={tab.id}
                    type="button"
                    onClick={() => setActiveTab(tab.id)}
                    className={`flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition whitespace-nowrap ${
                      activeTab === tab.id
                        ? 'bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] shadow-sm'
                        : 'text-gray-600 hover:bg-gray-50'
                    }`}
                  >
                    <Icon className="w-4 h-4" />
                    <span>{tab.label}</span>
                  </button>
                );
              })}
            </nav>
          </div>

          {/* Tab Content */}
          <div className="p-6">
            {/* Facility Information Tab */}
            {activeTab === 'facility' && (
              <div className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-semibold text-gray-900 mb-2">
                      Facility Name <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      value={form.facility_name}
                      onChange={(e) => setForm({ ...form, facility_name: e.target.value })}
                      required
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    />
                    {errors.facility_name && (
                      <p className="mt-1 text-sm text-red-600">{errors.facility_name[0]}</p>
                    )}
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-gray-900 mb-2">
                      Subdomain <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      value={form.subdomain}
                      onChange={(e) => setForm({ ...form, subdomain: e.target.value.replace(/[^a-z0-9-]/g, '').toLowerCase() })}
                      required
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)] font-mono"
                    />
                    <p className="text-xs text-gray-500 mt-1">e.g., {form.subdomain}.yourapp.com</p>
                    {errors.subdomain && (
                      <p className="mt-1 text-sm text-red-600">{errors.subdomain[0]}</p>
                    )}
                  </div>

                  <div className="md:col-span-2">
                    <label className="block text-sm font-semibold text-gray-900 mb-2">Address</label>
                    <textarea
                      value={form.address}
                      onChange={(e) => setForm({ ...form, address: e.target.value })}
                      rows={2}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    />
                    {errors.address && (
                      <p className="mt-1 text-sm text-red-600">{errors.address[0]}</p>
                    )}
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-gray-900 mb-2">Phone</label>
                    <input
                      type="tel"
                      value={form.phone}
                      onChange={(e) => setForm({ ...form, phone: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    />
                    {errors.phone && (
                      <p className="mt-1 text-sm text-red-600">{errors.phone[0]}</p>
                    )}
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-gray-900 mb-2">Email</label>
                    <input
                      type="email"
                      value={form.email}
                      onChange={(e) => setForm({ ...form, email: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    />
                    {errors.email && (
                      <p className="mt-1 text-sm text-red-600">{errors.email[0]}</p>
                    )}
                  </div>
                </div>
              </div>
            )}

            {/* Branding Tab */}
            {activeTab === 'branding' && (
              <div className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-semibold text-gray-900 mb-2">Logo</label>
                    <input
                      type="file"
                      accept="image/*"
                      onChange={handleLogoChange}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    />
                    {logoPreview && (
                      <div className="mt-2">
                        <img src={logoPreview} alt="Logo preview" className="w-32 h-32 object-contain border rounded-lg" />
                      </div>
                    )}
                    {errors.logo && (
                      <p className="mt-1 text-sm text-red-600">{errors.logo[0]}</p>
                    )}
                  </div>

                  <div className="space-y-4">
                    <div>
                      <label className="block text-sm font-semibold text-gray-900 mb-2">Primary Color</label>
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
                          className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)] font-mono text-sm"
                          placeholder="#25603E"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block text-sm font-semibold text-gray-900 mb-2">Secondary Color</label>
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
                          className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)] font-mono text-sm"
                          placeholder="#8B4513"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block text-sm font-semibold text-gray-900 mb-2">Accent Color</label>
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
                          className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)] font-mono text-sm"
                          placeholder="#F5F5DC"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Branch Setup Tab */}
            {activeTab === 'branch' && (
              <div className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-semibold text-gray-900 mb-2">
                      Branch Name <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      value={form.branch_name}
                      onChange={(e) => setForm({ ...form, branch_name: e.target.value })}
                      required
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    />
                    {errors.branch_name && (
                      <p className="mt-1 text-sm text-red-600">{errors.branch_name[0]}</p>
                    )}
                  </div>

                  <div className="md:col-span-2">
                    <label className="block text-sm font-semibold text-gray-900 mb-2">Branch Address</label>
                    <textarea
                      value={form.branch_address}
                      onChange={(e) => setForm({ ...form, branch_address: e.target.value })}
                      rows={2}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    />
                    {errors.branch_address && (
                      <p className="mt-1 text-sm text-red-600">{errors.branch_address[0]}</p>
                    )}
                  </div>
                </div>
              </div>
            )}

            {/* Owner Account Tab */}
            {activeTab === 'owner' && (
              <div className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-semibold text-gray-900 mb-2">
                      Owner Name <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      value={form.owner_name}
                      onChange={(e) => setForm({ ...form, owner_name: e.target.value })}
                      required
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    />
                    {errors.owner_name && (
                      <p className="mt-1 text-sm text-red-600">{errors.owner_name[0]}</p>
                    )}
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-gray-900 mb-2">
                      Owner Email <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="email"
                      value={form.owner_email}
                      onChange={(e) => setForm({ ...form, owner_email: e.target.value })}
                      required
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    />
                    {errors.owner_email && (
                      <p className="mt-1 text-sm text-red-600">{errors.owner_email[0]}</p>
                    )}
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-gray-900 mb-2">
                      Owner Role <span className="text-red-500">*</span>
                    </label>
                    <select
                      value={form.owner_role}
                      onChange={(e) => setForm({ ...form, owner_role: e.target.value })}
                      required
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    >
                      <option value="administrator">Administrator</option>
                      <option value="manager">Manager</option>
                      <option value="clinical_supervisor">Clinical Supervisor</option>
                    </select>
                    {errors.owner_role && (
                      <p className="mt-1 text-sm text-red-600">{errors.owner_role[0]}</p>
                    )}
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-gray-900 mb-2">
                      Password <span className="text-red-500">*</span>
                    </label>
                    <div className="relative">
                      <input
                        type={showPassword ? 'text' : 'password'}
                        value={form.owner_password}
                        onChange={(e) => setForm({ ...form, owner_password: e.target.value })}
                        required
                        minLength={8}
                        className="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg text-gray-900 bg-white focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                      />
                      <button
                        type="button"
                        onClick={() => setShowPassword(!showPassword)}
                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                      >
                        {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                      </button>
                    </div>
                    <p className="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                    {errors.owner_password && (
                      <p className="mt-1 text-sm text-red-600">{errors.owner_password[0]}</p>
                    )}
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Form Actions */}
        <div className="bg-white rounded-lg shadow p-6 mt-6">
          <div className="flex items-center justify-end gap-4">
            <button
              type="button"
              onClick={() => navigate('/super-admin/facility-registrations')}
              className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-semibold"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={approveMutation.isPending}
              className="px-6 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg hover:bg-[var(--theme-primary-hover)] disabled:opacity-50 transition-colors font-semibold flex items-center gap-2"
            >
              {approveMutation.isPending ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                  Creating...
                </>
              ) : (
                <>
                  <CheckCircle className="w-4 h-4" />
                  Approve & Create Facility
                </>
              )}
            </button>
          </div>
        </div>
      </form>
    </div>
  );
}
