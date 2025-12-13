import React from 'react';
import { Flame, Calendar, CheckCircle } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function FireDrillsDashboard() {
  const statsConfig = [
    { key: 'upcoming', label: 'Upcoming', icon: Calendar, color: 'from-red-600 to-orange-500' },
    { key: 'completed_this_month', label: 'Completed This Month', icon: CheckCircle, color: 'from-green-500 to-emerald-500' },
  ];

  const sidebarItems = [
    { path: '/fire-drills', label: 'Fire Drills', icon: Flame },
  ];

  return (
    <ModuleDashboard
      moduleId="fire_drills"
      moduleName="Fire Drills"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

