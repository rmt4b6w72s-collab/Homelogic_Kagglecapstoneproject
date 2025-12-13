import React from 'react';
import { Heart, AlertTriangle, Clock } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function VitalsDashboard() {
  const statsConfig = [
    { key: 'vitals_today', label: 'Vitals Today', icon: Heart, color: 'from-red-500 to-rose-500' },
    { key: 'critical_alerts', label: 'Critical Alerts', icon: AlertTriangle, color: 'from-red-600 to-orange-500' },
    { key: 'pending_review', label: 'Pending Review', icon: Clock, color: 'from-yellow-500 to-amber-500' },
  ];

  const sidebarItems = [
    { path: '/vitals', label: 'Record Vitals', icon: Heart },
    { path: '/view-vitals', label: 'View Vitals', icon: Heart },
  ];

  return (
    <ModuleDashboard
      moduleId="vitals"
      moduleName="Vitals"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

