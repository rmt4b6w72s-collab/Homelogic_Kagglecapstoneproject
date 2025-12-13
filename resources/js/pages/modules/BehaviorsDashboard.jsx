import React from 'react';
import { Activity, Calendar, TrendingUp } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function BehaviorsDashboard() {
  const statsConfig = [
    { key: 'today', label: 'Today', icon: Activity, color: 'from-pink-500 to-rose-500' },
    { key: 'this_month', label: 'This Month', icon: Calendar, color: 'from-purple-500 to-pink-500' },
  ];

  const sidebarItems = [
    // Add behavior routes when available
  ];

  return (
    <ModuleDashboard
      moduleId="behaviors"
      moduleName="Behaviors"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

