import React from 'react';
import { Users, UserPlus, Calendar } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function ResidentsDashboard() {
  const statsConfig = [
    { key: 'total_residents', label: 'Total Residents', icon: Users, color: 'from-blue-500 to-cyan-500' },
    { key: 'active_residents', label: 'Active Residents', icon: Users, color: 'from-green-500 to-emerald-500' },
    { key: 'new_this_month', label: 'New This Month', icon: UserPlus, color: 'from-purple-500 to-pink-500' },
  ];

  const sidebarItems = [
    { path: '/administration/residents', label: 'Manage Residents', icon: Users },
    { path: '/my-residents', label: 'My Residents', icon: Users },
  ];

  return (
    <ModuleDashboard
      moduleId="residents"
      moduleName="Residents"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

