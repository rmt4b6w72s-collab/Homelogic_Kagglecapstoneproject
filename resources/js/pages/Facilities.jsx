import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../services/api';
import { Building2, Plus, Search, Edit, Trash2, MapPin, Phone, Mail, Image as ImageIcon, Palette, Users } from 'lucide-react';
import { useToastContext } from '../contexts/ToastContext';

export default function Facilities() {
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const [search, setSearch] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState(null);
  
  // Check if user is super admin - MUST be called before any conditional returns
  const { data: currentUser, isLoading: userLoading } = useQuery({
    queryKey: ['current-user'],
    queryFn: async () => {
      try {
        const response = await api.get('/user');
        return response.data;
      } catch {
        return null;
      }
    },
  });
  
  // Facilities query - MUST be called before any conditional returns (hooks rule)
  const { data, isLoading } = useQuery({
    queryKey: ['facilities', search],
    queryFn: async () => {
      const res = await api.get('/facilities', { params: { search, per_page: 20 } });
      return res.data;
    },
    enabled: !userLoading, // Only fetch when user data is loaded
  });

  // Delete mutation - MUST be called before any conditional returns (hooks rule)
  const deleteMutation = useMutation({
    mutationFn: async (id) => api.delete(`/facilities/${id}`),
    onSuccess: () => queryClient.invalidateQueries(['facilities']),
  });
  
  const isSuperAdmin = currentUser?.role === 'super_admin';
  
  // Redirect non-super admins to dashboard
  React.useEffect(() => {
    if (!userLoading && currentUser && !isSuperAdmin) {
      navigate('/dashboard', { replace: true });
    }
  }, [currentUser, isSuperAdmin, userLoading, navigate]);
  
  // Don't render anything if not super admin (AFTER all hooks are called)
  if (userLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--theme-primary)]"></div>
          <p className="mt-4 text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }
  
  if (!isSuperAdmin) {
    return null; // Will redirect via useEffect
  }

  return (
    <div>
      <div className="bg-white rounded-lg shadow p-6 mb-6">
        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
          <div>
            <h2 className="text-xl font-semibold text-gray-900 mb-2">Facilities Management</h2>
            <p className="text-gray-600">Search and manage facilities.</p>
          </div>
          <button
            onClick={() => { setEditing(null); setShowForm(true); }}
            className="w-full sm:w-auto px-4 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg hover:bg-[var(--theme-primary-hover)] transition-colors flex items-center justify-center space-x-2 text-sm md:text-base"
          >
            <Plus className="w-4 h-4" />
            <span>Add Facility</span>
          </button>
        </div>
        
        <div className="relative max-w-md">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search facilities..."
            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
          />
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-12">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--theme-primary)]"></div>
          <p className="mt-4 text-gray-600">Loading facilities...</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {data?.data?.length ? (
            data.data.map((f) => (
              <div key={f.id} className="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                <div className="flex flex-col h-full">
                  {/* Header */}
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex-1">
                      <div className="flex items-center space-x-2 mb-2">
                        {f.logo_url ? (
                          <img src={f.logo_url} alt={f.name} className="w-10 h-10 object-contain rounded" />
                        ) : (
                          <Building2 className="w-5 h-5 text-[var(--theme-primary)]" />
                        )}
                        <h3 className="text-lg font-bold text-gray-900">{f.name}</h3>
                      </div>
                    </div>
                    {/* Actions */}
                    <div className="flex space-x-1">
                    <button
                      onClick={() => { setEditing(f); setShowForm(true); }}
                        className="p-2 text-[var(--theme-primary)] hover:bg-[var(--theme-primary-bg-light)] rounded-lg transition-colors"
                      title="Edit"
                    >
                      <Edit className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => window.confirm('Delete facility?') && deleteMutation.mutate(f.id)}
                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                      title="Delete"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                    </div>
                  </div>
                  
                  {/* Details */}
                  <div className="space-y-2 flex-1">
                    {f.address && (
                      <div className="flex items-start space-x-2 text-sm text-gray-600">
                        <MapPin className="w-4 h-4 mt-0.5 flex-shrink-0" />
                        <span className="line-clamp-2">{f.address}</span>
                      </div>
                    )}
                    {f.phone && (
                      <div className="flex items-center space-x-2 text-sm text-gray-600">
                        <Phone className="w-4 h-4 flex-shrink-0" />
                        <span>{f.phone}</span>
                      </div>
                    )}
                    {f.email && (
                      <div className="flex items-center space-x-2 text-sm text-gray-600">
                        <Mail className="w-4 h-4 flex-shrink-0" />
                        <span className="truncate">{f.email}</span>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))
          ) : (
            <div className="col-span-2 bg-white rounded-lg shadow p-12 text-center">
              <Building2 className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600 text-lg font-medium">No facilities found</p>
            </div>
          )}
        </div>
      )}

      {showForm && (
        <FacilityForm
          record={editing}
          isSuperAdmin={isSuperAdmin}
          onClose={() => { setShowForm(false); setEditing(null); }}
          onSuccess={() => { setShowForm(false); setEditing(null); queryClient.invalidateQueries(['facilities']); }}
        />
      )}
    </div>
  );
}

function FacilityForm({ record, isSuperAdmin, onClose, onSuccess }) {
  const { showToast } = useToastContext();
  const scrollableRef = useRef(null);
  
  // Scroll to top when modal opens or record changes
  useEffect(() => {
    // Use setTimeout to ensure DOM is fully rendered
    const timer = setTimeout(() => {
      if (scrollableRef.current) {
        scrollableRef.current.scrollTop = 0;
      }
    }, 0);
    return () => clearTimeout(timer);
  }, [record]);
  
  const [form, setForm] = useState({
    name: record?.name || '',
    address: record?.address || '',
    phone: record?.phone || '',
    email: record?.email || '',
    subdomain: record?.subdomain || '',
    is_active: record?.is_active ?? true,
    primary_color: record?.primary_color || '#1E3A5F', // HomeLogic360 dark blue
    secondary_color: record?.secondary_color || '#86EFAC', // HomeLogic360 light green
    accent_color: record?.accent_color || '#FFFFFF', // HomeLogic360 white
    logo: null,
    // Owner account fields (only when creating new facility)
    owner_name: '',
    owner_email: '',
    owner_role: 'administrator',
    owner_password: '',
    // Initial branch fields (only when creating new facility)
    branch_name: 'Main Branch',
    branch_address: '',
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [logoPreview, setLogoPreview] = useState(record?.logo_url || null);

  const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setForm({ ...form, logo: file });
      const reader = new FileReader();
      reader.onloadend = () => setLogoPreview(reader.result);
      reader.readAsDataURL(file);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});
    
    // Validate that if owner email is provided, all owner fields are required
    if (!record && isSuperAdmin && form.owner_email && (!form.owner_name || !form.owner_password)) {
      setErrors({ general: 'If creating an owner account, all owner fields are required (name, email, password)' });
      setSubmitting(false);
      return;
    }
    
    try {
      const formData = new FormData();
      Object.keys(form).forEach((key) => {
        if (key === 'logo' && form.logo instanceof File) {
          formData.append('logo', form.logo);
        } else if (key === 'is_active') {
          // Convert boolean to string '1' or '0' for FormData
          formData.append(key, form[key] ? '1' : '0');
        } else if (key === 'subdomain' && form.subdomain && form.subdomain.trim() !== '') {
          // Only include subdomain if it's not empty
          formData.append(key, form.subdomain);
        } else if (key.startsWith('owner_') || key.startsWith('branch_')) {
          // Include owner and branch fields only when creating (not editing)
          if (!record && form[key] !== null && form[key] !== '') {
            formData.append(key, form[key]);
          }
        } else if (key !== 'logo' && key !== 'subdomain' && !key.startsWith('owner_') && !key.startsWith('branch_') && form[key] !== null && form[key] !== '') {
          formData.append(key, form[key]);
        }
      });

      let response;
      if (record) {
        // Use POST with FormData for file uploads (Laravel handles this)
        response = await api.post(`/facilities/${record.id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      } else {
        response = await api.post('/facilities', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
      
      // Show success message
      if (response.data && response.data.owner) {
        showToast(
          `Facility created! Owner account created: ${response.data.owner.email}`,
          'success'
        );
      } else {
        showToast('Facility created successfully!', 'success');
      }
      
      onSuccess();
    } catch (e) {
      console.error('Error saving facility:', e);
      console.error('Error response:', e.response);
      console.error('Error data:', e.response?.data);
      console.error('Error status:', e.response?.status);
      
      const errorData = e.response?.data;
      if (errorData?.errors) {
        setErrors(errorData.errors);
      } else if (errorData?.message) {
        setErrors({ general: errorData.message });
      } else if (e.response?.status === 422) {
        setErrors({ general: 'Validation failed. Please check all fields and try again.' });
      } else if (e.response?.status === 500) {
        setErrors({ general: 'Server error. Please try again or contact support.' });
      } else if (e.message) {
        setErrors({ general: e.message });
      } else {
        setErrors({ general: 'Failed to save facility. Please check all fields and try again.' });
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 text-sm md:text-base">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        {/* Header - Fixed */}
        <div className="flex-shrink-0 p-6 border-b">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 className="text-2xl font-bold text-gray-900">{record ? 'Edit Facility' : 'Add Facility'}</h2>
            <button onClick={onClose} className="text-gray-400 hover:text-gray-600 text-2xl w-8 h-8 flex items-center justify-center">×</button>
          </div>
        </div>
        
        {/* Scrollable Content */}
        <div ref={scrollableRef} className="flex-1 overflow-y-auto p-6">
          {(errors.general || Object.keys(errors).length > 0) && (
            <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
              {errors.general ? (
                <p className="text-sm text-red-800">{errors.general}</p>
              ) : (
                <div>
                  <p className="text-sm font-semibold text-red-800 mb-2">Please fix the following errors:</p>
                  <ul className="list-disc list-inside space-y-1">
                    {Object.entries(errors).map(([field, messages]) => (
                      <li key={field} className="text-sm text-red-700">
                        <strong>{field}:</strong> {Array.isArray(messages) ? messages.join(', ') : messages}
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          )}
          <form onSubmit={handleSubmit} id="facility-form" className="space-y-6">
            {/* Basic Information */}
            <div className="border-b pb-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                  <input
                    value={form.name}
                    onChange={(e) => setForm({ ...form, name: e.target.value })}
                    required
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                  />
                  {errors.name && <p className="text-xs text-red-600 mt-1">{errors.name[0]}</p>}
                </div>
                {isSuperAdmin && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Subdomain</label>
                    <input
                      value={form.subdomain}
                      onChange={(e) => setForm({ ...form, subdomain: e.target.value.replace(/[^a-z0-9-]/g, '').toLowerCase() })}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent font-mono"
                      placeholder="facility-name"
                    />
                    <p className="text-xs text-gray-500 mt-1">e.g., {form.subdomain || 'facility-name'}.yourapp.com</p>
                  </div>
                )}
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                  <textarea
                    value={form.address}
                    onChange={(e) => setForm({ ...form, address: e.target.value })}
                    rows={3}
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

            {/* Branding & Customization (Super Admin Only) */}
            {isSuperAdmin && (
              <div className="border-b pb-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <Palette className="w-5 h-5 text-[var(--theme-primary)]" />
                  Branding & Customization
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-2">
                      <ImageIcon className="w-4 h-4" />
                      Logo
                    </label>
                    <input
                      type="file"
                      accept="image/*"
                      onChange={handleLogoChange}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    />
                    {logoPreview && (
                      <div className="mt-3">
                        <p className="text-sm text-gray-600 mb-2">Preview:</p>
                        <img src={logoPreview} alt="Logo preview" className="w-32 h-32 object-contain border rounded-lg bg-gray-50 p-2" />
                      </div>
                    )}
                    {errors.logo && <p className="text-xs text-red-600 mt-1">{errors.logo[0]}</p>}
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
            )}

            {/* Owner Account Setup (only for new facilities) */}
            {!record && isSuperAdmin && (
              <div className="border-b pb-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <Users className="w-5 h-5 text-[var(--theme-primary)]" />
                  Facility Owner Account (Optional)
                </h3>
                <p className="text-sm text-gray-600 mb-4">Create the facility owner/admin account now, or add it later. If creating now, all fields below are required.</p>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Owner Name *</label>
                    <input
                      value={form.owner_name}
                      onChange={(e) => setForm({ ...form, owner_name: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                      placeholder="Owner full name"
                    />
                    {errors.owner_name && <p className="text-xs text-red-600 mt-1">{errors.owner_name[0]}</p>}
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Owner Email</label>
                    <input
                      type="email"
                      value={form.owner_email}
                      onChange={(e) => setForm({ ...form, owner_email: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                      placeholder="owner@facility.com"
                    />
                    {errors.owner_email && <p className="text-xs text-red-600 mt-1">{errors.owner_email[0]}</p>}
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Owner Role</label>
                    <select
                      value={form.owner_role}
                      onChange={(e) => setForm({ ...form, owner_role: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    >
                      <option value="administrator">Administrator</option>
                      <option value="manager">Manager</option>
                      <option value="clinical_supervisor">Clinical Supervisor</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input
                      type="password"
                      value={form.owner_password}
                      onChange={(e) => setForm({ ...form, owner_password: e.target.value })}
                      minLength={8}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                      placeholder="Minimum 8 characters"
                    />
                    <p className="text-xs text-gray-500 mt-1">Leave empty if creating account later</p>
                    {errors.owner_password && <p className="text-xs text-red-600 mt-1">{errors.owner_password[0]}</p>}
                  </div>
                </div>
              </div>
            )}

            {/* Initial Branch Setup (only for new facilities) */}
            {!record && isSuperAdmin && (
              <div className="border-b pb-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                  <Building2 className="w-5 h-5 text-[var(--theme-primary)]" />
                  Initial Branch Setup
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Branch Name</label>
                    <input
                      value={form.branch_name}
                      onChange={(e) => setForm({ ...form, branch_name: e.target.value })}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                    />
                    {errors.branch_name && <p className="text-xs text-red-600 mt-1">{errors.branch_name[0]}</p>}
                  </div>
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Branch Address</label>
                    <textarea
                      value={form.branch_address}
                      onChange={(e) => setForm({ ...form, branch_address: e.target.value })}
                      rows={2}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-[var(--theme-primary)]"
                      placeholder="Leave empty to use facility address"
                    />
                  </div>
                </div>
              </div>
            )}

            <div className="flex items-center">
              <input
                type="checkbox"
                id="is_active"
                checked={form.is_active}
                onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
                className="w-4 h-4 text-[var(--theme-primary)] border-gray-300 rounded focus:ring-[var(--theme-primary)]"
              />
              <label htmlFor="is_active" className="ml-2 text-sm font-medium text-gray-700">
                Active Facility
              </label>
            </div>
          </form>
        </div>
        
        {/* Footer - Fixed */}
        <div className="flex-shrink-0 p-6 border-t bg-gray-50">
          <div className="flex items-center justify-end gap-3">
            <button type="button" onClick={onClose} className="px-6 py-2 border border-gray-300 rounded-lg hover:bg-white transition-colors">Cancel</button>
            <button type="submit" form="facility-form" disabled={submitting} className="px-6 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg hover:bg-[var(--theme-primary-hover)] disabled:opacity-50 transition-colors">
              {submitting ? 'Saving...' : (record ? 'Update' : 'Create')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

