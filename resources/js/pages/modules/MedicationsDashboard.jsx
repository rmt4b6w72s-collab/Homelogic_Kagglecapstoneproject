import React from 'react';
import { Pill, Clock, AlertCircle } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function MedicationsDashboard() {
  const statsConfig = [
    { key: 'pending_administrations', label: 'Pending Administrations', icon: Clock, color: 'from-orange-500 to-red-500' },
    { key: 'active_medications', label: 'Active Medications', icon: Pill, color: 'from-purple-500 to-pink-500' },
    { key: 'overdue_medications', label: 'Overdue Medications', icon: AlertCircle, color: 'from-red-500 to-rose-500' },
  ];

  const sidebarItems = [
    { path: '/medications', label: 'Medications', icon: Pill },
    { path: '/medication-deliveries', label: 'Deliveries', icon: Pill },
    { path: '/medication-history', label: 'History', icon: Pill },
  ];

  return (
    <ModuleDashboard
      moduleId="medications"
      moduleName="Medications"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

