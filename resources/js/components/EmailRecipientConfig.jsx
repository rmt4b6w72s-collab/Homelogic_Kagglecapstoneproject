import React, { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Users, X } from 'lucide-react';
import api from '../services/api';

export default function EmailRecipientConfig({ facilityId, config, onChange }) {
  const [selectedRoles, setSelectedRoles] = useState(config?.recipient_roles || []);
  const [selectedUserIds, setSelectedUserIds] = useState(config?.recipient_user_ids || []);
  const [userSearch, setUserSearch] = useState('');

  // Fetch roles
  const { data: rolesData } = useQuery({
    queryKey: ['roles'],
    queryFn: async () => {
      const response = await api.get('/roles', { params: { per_page: 100 } });
      return response.data?.data || [];
    },
  });

  // Fetch users for the facility
  const { data: usersData } = useQuery({
    enabled: !!facilityId,
    queryKey: ['facility-users', facilityId, userSearch],
    queryFn: async () => {
      const params = { facility_id: facilityId, per_page: 100, active_only: 'true' };
      if (userSearch) params.search = userSearch;
      const response = await api.get('/users', { params });
      return response.data?.data || [];
    },
  });

  // Common roles that might be used
  const commonRoles = ['administrator', 'admin', 'manager', 'nurse', 'caregiver', 'super_admin'];

  useEffect(() => {
    if (onChange) {
      onChange({
        enabled: config?.enabled ?? true,
        recipient_roles: selectedRoles,
        recipient_user_ids: selectedUserIds,
      });
    }
  }, [selectedRoles, selectedUserIds, config?.enabled]);

  const toggleRole = (role) => {
    setSelectedRoles((prev) =>
      prev.includes(role) ? prev.filter((r) => r !== role) : [...prev, role]
    );
  };

  const toggleUser = (userId) => {
    setSelectedUserIds((prev) =>
      prev.includes(userId) ? prev.filter((id) => id !== userId) : [...prev, userId]
    );
  };

  const usersList = usersData?.data || usersData || [];
  const selectedUsers = usersList.filter((u) => selectedUserIds.includes(u.id));

  return (
    <div className="space-y-6">
      {/* Roles Selection */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Recipient Roles
        </label>
        <p className="text-xs text-gray-500 mb-3">
          Select roles that should receive this email notification
        </p>
        <div className="flex flex-wrap gap-2">
          {commonRoles.map((role) => (
            <button
              key={role}
              type="button"
              onClick={() => toggleRole(role)}
              className={`px-3 py-1.5 text-sm rounded-lg border transition-colors ${
                selectedRoles.includes(role)
                  ? 'bg-[var(--theme-primary)] text-white border-[var(--theme-primary)]'
                  : 'bg-white text-gray-700 border-gray-300 hover:border-[var(--theme-primary)]'
              }`}
            >
              {role.charAt(0).toUpperCase() + role.slice(1).replace('_', ' ')}
            </button>
          ))}
        </div>
        {selectedRoles.length > 0 && (
          <div className="mt-3 flex flex-wrap gap-2">
            {selectedRoles.map((role) => (
              <span
                key={role}
                className="inline-flex items-center gap-1 px-2 py-1 bg-[var(--theme-primary)]/10 text-[var(--theme-primary)] rounded text-sm"
              >
                {role}
                <button
                  type="button"
                  onClick={() => toggleRole(role)}
                  className="hover:text-[var(--theme-primary-hover)]"
                >
                  <X className="w-3 h-3" />
                </button>
              </span>
            ))}
          </div>
        )}
      </div>

      {/* Specific Users Selection */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Specific Users
        </label>
        <p className="text-xs text-gray-500 mb-3">
          Select specific users who should receive this email (in addition to roles)
        </p>
        
        {/* User Search */}
        <div className="relative mb-3">
          <Users className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Search users..."
            value={userSearch}
            onChange={(e) => setUserSearch(e.target.value)}
            className="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[var(--theme-primary)]"
          />
        </div>

        {/* User List */}
        {userSearch && (
          <div className="border border-gray-200 rounded-lg max-h-48 overflow-y-auto">
            {usersList
              .filter((u) => !selectedUserIds.includes(u.id))
              .map((user) => (
                <button
                  key={user.id}
                  type="button"
                  onClick={() => toggleUser(user.id)}
                  className="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 border-b border-gray-100 last:border-0"
                >
                  {user.name || `${user.first_name} ${user.last_name}`} ({user.email})
                </button>
              ))}
          </div>
        )}

        {/* Selected Users */}
        {selectedUsers.length > 0 && (
          <div className="mt-3 space-y-2">
            <p className="text-xs font-medium text-gray-700">Selected Users:</p>
            {selectedUsers.map((user) => (
              <div
                key={user.id}
                className="flex items-center justify-between px-3 py-2 bg-gray-50 rounded-lg"
              >
                <span className="text-sm text-gray-700">
                  {user.name || `${user.first_name} ${user.last_name}`} ({user.email})
                </span>
                <button
                  type="button"
                  onClick={() => toggleUser(user.id)}
                  className="text-gray-400 hover:text-red-600"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

