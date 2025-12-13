import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { BarChart3, ArrowLeft } from 'lucide-react';
import ModuleLayout from '../../components/modules/ModuleLayout';
import ModuleSidebar from '../../components/modules/ModuleSidebar';
import { getModuleCard } from '../../config/moduleCards';

export default function ModuleAnalytics() {
  const { module } = useParams();
  const navigate = useNavigate();
  const moduleCard = getModuleCard(module);

  const sidebarItems = [
    {
      path: `/modules/${module}/dashboard`,
      label: 'Dashboard',
      icon: BarChart3,
    },
    {
      path: `/modules/${module}/analytics`,
      label: 'Analytics',
      icon: BarChart3,
    },
  ];

  const sidebar = (
    <ModuleSidebar 
      moduleId={module}
      items={sidebarItems}
    />
  );

  const headerActions = (
    <button
      onClick={() => navigate(`/modules/${module}/dashboard`)}
      className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center space-x-2"
    >
      <ArrowLeft className="w-4 h-4" />
      <span>Back to Dashboard</span>
    </button>
  );

  return (
    <ModuleLayout
      moduleName={`${moduleCard?.name || 'Module'} Analytics`}
      moduleId={module}
      sidebar={sidebar}
      headerActions={headerActions}
    >
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">Analytics & Charts</h2>
        <p className="text-gray-600">
          Analytics and charts for {moduleCard?.name || module} will be displayed here.
        </p>
        <p className="text-sm text-gray-500 mt-4">
          This page will contain module-specific analytics, trends, and visualizations.
        </p>
      </div>
    </ModuleLayout>
  );
}

