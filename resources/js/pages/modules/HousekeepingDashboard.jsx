import React from 'react';
import { Building2, CheckCircle, Clock } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function HousekeepingDashboard() {
  const statsConfig = [
    { key: 'completed_today', label: 'Completed Today', icon: CheckCircle, color: 'from-green-500 to-emerald-500' },
    { key: 'pending', label: 'Pending Tasks', icon: Clock, color: 'from-orange-500 to-red-500' },
  ];

  const sidebarItems = [
    { path: '/housekeeping', label: 'Checklist', icon: Building2 },
    { path: '/housekeeping/dashboard', label: 'Dashboard', icon: Building2 },
    { path: '/housekeeping/schedule', label: 'Schedule', icon: Building2 },
  ];

  return (
    <ModuleDashboard
      moduleId="housekeeping"
      moduleName="Housekeeping"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

