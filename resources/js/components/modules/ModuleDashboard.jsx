import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { useParams, useNavigate } from 'react-router-dom';
import { BarChart3, Clock, Activity } from 'lucide-react';
import api from '../../services/api';
import ModuleLayout from './ModuleLayout';
import ModuleSidebar from './ModuleSidebar';
import { getModuleCard } from '../../config/moduleCards';

export default function ModuleDashboard({ 
  moduleId, 
  moduleName, 
  sidebarItems = [],
  statsConfig = [],
  recentActivityConfig = {}
}) {
  const { module } = useParams();
  const navigate = useNavigate();
  const actualModuleId = module || moduleId;
  const moduleCard = getModuleCard(actualModuleId);

  // Fetch module stats
  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['module-stats', actualModuleId],
    queryFn: async () => {
      const response = await api.get(`/modules/${actualModuleId}/stats`);
      return response.data;
    },
  });

  // Fetch recent activity
  const { data: recentActivity, isLoading: activityLoading } = useQuery({
    queryKey: ['module-activity', actualModuleId],
    queryFn: async () => {
      const response = await api.get(`/modules/${actualModuleId}/recent-activity?limit=10`);
      return response.data;
    },
  });

  // Build sidebar items
  const defaultSidebarItems = [
    {
      path: `/modules/${actualModuleId}/dashboard`,
      label: 'Dashboard',
      icon: Activity,
    },
    {
      path: `/modules/${actualModuleId}/analytics`,
      label: 'Analytics',
      icon: BarChart3,
    },
    ...sidebarItems,
  ];

  const sidebar = (
    <ModuleSidebar 
      moduleId={actualModuleId}
      items={defaultSidebarItems}
    />
  );

  const headerActions = (
    <button
      onClick={() => navigate(`/modules/${actualModuleId}/analytics`)}
      className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
    >
      <BarChart3 className="w-4 h-4" />
      <span>View Analytics</span>
    </button>
  );

  return (
    <ModuleLayout
      moduleName={moduleName || moduleCard?.name || 'Module'}
      moduleId={actualModuleId}
      sidebar={sidebar}
      headerActions={headerActions}
    >
      <div className="space-y-6">
        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {statsLoading ? (
            Array.from({ length: 3 }).map((_, i) => (
              <div key={i} className="bg-white rounded-lg shadow-sm p-6 animate-pulse">
                <div className="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
                <div className="h-8 bg-gray-200 rounded w-1/3"></div>
              </div>
            ))
          ) : (
            statsConfig.map((stat, index) => {
              const value = stats?.[stat.key] ?? 0;
              return (
                <div key={index} className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm text-gray-600 mb-1">{stat.label}</p>
                      <p className="text-2xl font-bold text-gray-900">{value}</p>
                    </div>
                    {stat.icon && (
                      <div className={`p-3 rounded-lg bg-gradient-to-br ${stat.color || 'from-blue-500 to-cyan-500'} text-white`}>
                        <stat.icon className="w-6 h-6" />
                      </div>
                    )}
                  </div>
                </div>
              );
            })
          )}
        </div>

        {/* Recent Activity */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200">
          <div className="p-6 border-b border-gray-200">
            <h2 className="text-lg font-semibold text-gray-900 flex items-center space-x-2">
              <Clock className="w-5 h-5" />
              <span>Recent Activity</span>
            </h2>
          </div>
          <div className="p-6">
            {activityLoading ? (
              <div className="space-y-4">
                {Array.from({ length: 5 }).map((_, i) => (
                  <div key={i} className="animate-pulse">
                    <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                    <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                  </div>
                ))}
              </div>
            ) : recentActivity && recentActivity.length > 0 ? (
              <div className="space-y-4">
                {recentActivity.map((item, index) => (
                  <div key={index} className="flex items-start space-x-4 pb-4 border-b border-gray-100 last:border-0">
                    <div className="flex-shrink-0">
                      <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <Activity className="w-5 h-5 text-blue-600" />
                      </div>
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium text-gray-900">{item.title}</p>
                      <p className="text-sm text-gray-500 mt-1">{item.description}</p>
                      <p className="text-xs text-gray-400 mt-1">
                        {item.date ? new Date(item.date).toLocaleDateString() : ''}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500">
                <p>No recent activity</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </ModuleLayout>
  );
}

