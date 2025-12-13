import React from 'react';
import { Calendar, CheckCircle, Clock } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function AppointmentsDashboard() {
  const statsConfig = [
    { key: 'upcoming', label: 'Upcoming', icon: Calendar, color: 'from-blue-500 to-cyan-500' },
    { key: 'completed_today', label: 'Completed Today', icon: CheckCircle, color: 'from-green-500 to-emerald-500' },
    { key: 'pending', label: 'Pending', icon: Clock, color: 'from-yellow-500 to-amber-500' },
  ];

  const sidebarItems = [
    { path: '/appointments', label: 'Appointments', icon: Calendar },
  ];

  return (
    <ModuleDashboard
      moduleId="appointments"
      moduleName="Appointments"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

