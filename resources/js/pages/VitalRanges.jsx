import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../services/api';
import { Activity, Plus, Edit, Trash2 } from 'lucide-react';

export default function VitalRanges() {
  const queryClient = useQueryClient();
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState(null);

  // Get current user to check permissions
  const { data: currentUser } = useQuery({
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

  const isSuperAdmin = currentUser?.role === 'super_admin';
  const permissions = Array.isArray(currentUser?.permissions) ? currentUser.permissions : [];
  const canCreate = isSuperAdmin || permissions.includes('create_vital_ranges');
  const canEdit = isSuperAdmin || permissions.includes('edit_vital_ranges');
  const canDelete = isSuperAdmin || permissions.includes('delete_vital_ranges');

  const { data, isLoading } = useQuery({
    queryKey: ['vital-ranges'],
    queryFn: async () => (await api.get('/vital-ranges', { params: { per_page: 50 } })).data,
  });

  const deleteMutation = useMutation({
    mutationFn: async (id) => api.delete(`/vital-ranges/${id}`),
    onSuccess: () => queryClient.invalidateQueries(['vital-ranges']),
  });

  return (
    <div>
      <div className="bg-white rounded-lg shadow p-6 mb-6">
        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
          <div>
            <h2 className="text-xl font-semibold text-gray-900 mb-2">Vital Ranges Management</h2>
            <p className="text-gray-600">View and manage vital sign reference ranges.</p>
          </div>
          {canCreate && (
            <button onClick={() => { setEditing(null); setShowForm(true); }} className="w-full sm:w-auto px-4 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg hover:bg-[var(--theme-primary-hover)] transition-colors flex items-center justify-center space-x-2 text-sm md:text-base">
              <Plus className="w-4 h-4" />
              <span>Add Range</span>
            </button>
          )}
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-12">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[var(--theme-primary)]"></div>
          <p className="mt-4 text-gray-600">Loading ranges...</p>
        </div>
      ) : (
        <div className="bg-white rounded-lg shadow overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vital</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                <th className="px-6 py-3"></th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {data?.data?.map((r) => (
                <tr key={r.id}>
                  <td className="px-6 py-4 whitespace-nowrap font-medium text-gray-900 capitalize">{r.parameter?.replace('_', ' ')}</td>
                  <td className="px-6 py-4 whitespace-nowrap">{r.min_normal ?? '-'}</td>
                  <td className="px-6 py-4 whitespace-nowrap">{r.max_normal ?? '-'}</td>
                  <td className="px-6 py-4 whitespace-nowrap">{r.unit ?? '-'}</td>
                  <td className="px-6 py-4 whitespace-nowrap text-right text-sm">
                    {canEdit && (
                      <button onClick={() => { setEditing(r); setShowForm(true); }} className="p-2 text-[var(--theme-primary)] hover:bg-green-50 rounded-lg mr-2"><Edit className="w-4 h-4" /></button>
                    )}
                    {canDelete && (
                      <button onClick={() => window.confirm('Delete range?') && deleteMutation.mutate(r.id)} className="p-2 text-[var(--theme-secondary)] hover:bg-amber-50 rounded-lg"><Trash2 className="w-4 h-4" /></button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {!data?.data?.length && (
            <div className="p-12 text-center">
              <Activity className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600 text-lg font-medium">No ranges defined</p>
            </div>
          )}
        </div>
      )}

      {showForm && (
        <RangeForm
          record={editing}
          onClose={() => { setShowForm(false); setEditing(null); }}
          onSuccess={() => { setShowForm(false); setEditing(null); queryClient.invalidateQueries(['vital-ranges']); }}
        />
      )}
    </div>
  );
}

function RangeForm({ record, onClose, onSuccess }) {
  const [form, setForm] = useState({
    parameter: record?.parameter || '',
    min_normal: record?.min_normal ?? '',
    max_normal: record?.max_normal ?? '',
    unit: record?.unit || '',
    description: record?.description || '',
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});
    try {
      if (record) {
        await api.put(`/vital-ranges/${record.id}`, form);
      } else {
        await api.post('/vital-ranges', form);
      }
      onSuccess();
    } catch (e) {
      setErrors(e.response?.data?.errors || { general: e.response?.data?.message || 'Failed to save range' });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        {/* Header - Fixed */}
        <div className="flex-shrink-0 p-6 border-b border-gray-200">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-xl font-semibold text-gray-900">
              {record ? 'Edit Range' : 'Add Range'}
            </h2>
            <button
              onClick={onClose}
              className="text-gray-500 hover:text-gray-700"
            >
              ✕
            </button>
          </div>
        </div>
        
        {/* Scrollable Content */}
        <div className="flex-1 overflow-y-auto p-6">
          {errors.general && (
            <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg"><p className="text-sm text-red-800">{errors.general}</p></div>
          )}
          <form id="vital-range-form" onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Parameter *
              </label>
              <select
                value={form.parameter}
                onChange={(e) => setForm({ ...form, parameter: e.target.value })}
                required
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
              >
                <option value="">Select parameter</option>
                <option value="systolic">Systolic</option>
                <option value="diastolic">Diastolic</option>
                <option value="temperature">Temperature</option>
                <option value="pulse">Pulse</option>
                <option value="oxygen_saturation">Oxygen Saturation</option>
              </select>
              {errors.parameter && <p className="text-xs text-red-600 mt-1">{errors.parameter[0]}</p>}
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Min Normal
                </label>
                <input
                  type="number"
                  step="0.01"
                  value={form.min_normal}
                  onChange={(e) => setForm({ ...form, min_normal: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Max Normal
                </label>
                <input
                  type="number"
                  step="0.01"
                  value={form.max_normal}
                  onChange={(e) => setForm({ ...form, max_normal: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
                />
              </div>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Unit
              </label>
              <input
                value={form.unit}
                onChange={(e) => setForm({ ...form, unit: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Description
              </label>
              <textarea
                value={form.description}
                onChange={(e) => setForm({ ...form, description: e.target.value })}
                rows={2}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--theme-primary)] focus:border-transparent"
              />
            </div>
          </form>
        </div>
        
        {/* Footer - Fixed */}
        <div className="flex-shrink-0 p-6 border-t border-gray-200">
          <div className="flex justify-end space-x-3">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              form="vital-range-form"
              disabled={submitting}
              className="px-4 py-2 bg-[var(--theme-primary)] text-[var(--theme-text-on-primary)] rounded-lg hover:bg-[var(--theme-primary-hover)] transition-colors disabled:opacity-50"
            >
              {submitting ? 'Saving...' : (record ? 'Update' : 'Create')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

