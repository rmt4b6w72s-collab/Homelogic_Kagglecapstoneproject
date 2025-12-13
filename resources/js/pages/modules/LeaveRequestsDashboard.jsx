import React from 'react';
import { Briefcase, Clock, CheckCircle } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function LeaveRequestsDashboard() {
  const statsConfig = [
    { key: 'pending', label: 'Pending Requests', icon: Clock, color: 'from-orange-500 to-red-500' },
    { key: 'approved_this_month', label: 'Approved This Month', icon: CheckCircle, color: 'from-green-500 to-emerald-500' },
  ];

  const sidebarItems = [
    { path: '/leave-requests', label: 'Leave Requests', icon: Briefcase },
    { path: '/administration/leave-requests', label: 'Manage Requests', icon: Briefcase },
  ];

  return (
    <ModuleDashboard
      moduleId="leave_requests"
      moduleName="Leave Requests"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

