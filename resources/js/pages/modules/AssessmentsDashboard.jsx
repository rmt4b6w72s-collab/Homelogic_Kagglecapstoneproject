import React from 'react';
import { ClipboardList, CheckCircle, AlertCircle } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function AssessmentsDashboard() {
  const statsConfig = [
    { key: 'pending', label: 'Pending', icon: ClipboardList, color: 'from-orange-500 to-red-500' },
    { key: 'completed_this_month', label: 'Completed This Month', icon: CheckCircle, color: 'from-green-500 to-emerald-500' },
    { key: 'overdue', label: 'Overdue', icon: AlertCircle, color: 'from-red-500 to-rose-500' },
  ];

  const sidebarItems = [
    { path: '/assessments', label: 'Assessments', icon: ClipboardList },
  ];

  return (
    <ModuleDashboard
      moduleId="assessments"
      moduleName="Assessments"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

