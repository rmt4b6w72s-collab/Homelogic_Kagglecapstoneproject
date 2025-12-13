import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { getModuleCardsForUser } from '../config/moduleCards';
import api from '../services/api';
import { useTheme } from '../contexts/ThemeContext';

export default function Modules() {
  const navigate = useNavigate();
  const { primary } = useTheme();

  // Fetch current user to get enabled modules
  const { data: currentUser, isLoading: userLoading } = useQuery({
    queryKey: ['current-user'],
    queryFn: async () => {
      try {
        const response = await api.get('/user');
        return response.data;
      } catch (err) {
        console.error('Failed to fetch current user:', err);
        return null;
      }
    },
  });

  // Fetch module stats for quick preview
  const { data: moduleStats } = useQuery({
    queryKey: ['module-stats'],
    queryFn: async () => {
      try {
        const response = await api.get('/modules/stats');
        return response.data || {};
      } catch (err) {
        console.error('Failed to fetch module stats:', err);
        return {};
      }
    },
    enabled: !!currentUser,
  });

  const enabledModules = currentUser?.enabled_modules || [];
  const isSuperAdmin = currentUser?.role === 'super_admin';
  const moduleCards = isSuperAdmin 
    ? getModuleCardsForUser() 
    : getModuleCardsForUser(enabledModules);

  const handleModuleClick = (module) => {
    navigate(module.route);
  };

  if (userLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p className="mt-4 text-gray-600">Loading modules...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">
            Welcome back, {currentUser?.first_name || currentUser?.name || 'User'} 👋
          </h1>
          <p className="text-gray-600">
            Select a module to get started
          </p>
        </div>

        {/* Module Cards Grid */}
        {moduleCards.length === 0 ? (
          <div className="bg-white rounded-lg shadow-sm p-12 text-center">
            <p className="text-gray-500 text-lg">
              No modules available. Please contact your administrator.
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {moduleCards.map((module) => {
              const Icon = module.icon;
              const stats = moduleStats?.[module.id] || {};
              const quickStat = stats.quick_stat || null;

              return (
                <div
                  key={module.id}
                  onClick={() => handleModuleClick(module)}
                  className="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer group border border-gray-200 hover:border-gray-300"
                >
                  <div className="p-6">
                    {/* Icon and Quick Stat */}
                    <div className="flex items-start justify-between mb-4">
                      <div className={`p-3 rounded-lg bg-gradient-to-br ${module.color} text-white`}>
                        <Icon className="w-6 h-6" />
                      </div>
                      {quickStat !== null && (
                        <div className="text-right">
                          <div className="text-2xl font-bold text-gray-900">{quickStat}</div>
                          <div className="text-xs text-gray-500">pending</div>
                        </div>
                      )}
                    </div>

                    {/* Module Name */}
                    <h3 className="text-lg font-semibold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                      {module.name}
                    </h3>

                    {/* Description */}
                    <p className="text-sm text-gray-600 line-clamp-2 mb-4">
                      {module.description}
                    </p>

                    {/* View Details Link */}
                    <div className="flex items-center text-sm font-medium text-blue-600 group-hover:text-blue-700">
                      View Dashboard
                      <svg className="ml-2 w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                      </svg>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}

