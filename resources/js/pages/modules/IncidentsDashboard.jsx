import React from 'react';
import { AlertCircle, CheckCircle, Calendar } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function IncidentsDashboard() {
  const statsConfig = [
    { key: 'this_month', label: 'This Month', icon: Calendar, color: 'from-blue-500 to-cyan-500' },
    { key: 'open', label: 'Open Incidents', icon: AlertCircle, color: 'from-red-500 to-rose-500' },
    { key: 'resolved_today', label: 'Resolved Today', icon: CheckCircle, color: 'from-green-500 to-emerald-500' },
  ];

  const sidebarItems = [
    { path: '/incidents', label: 'Incidents', icon: AlertCircle },
  ];

  return (
    <ModuleDashboard
      moduleId="incidents"
      moduleName="Incidents"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

