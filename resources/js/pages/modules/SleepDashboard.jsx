import React from 'react';
import { Moon, Calendar } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function SleepDashboard() {
  const statsConfig = [
    { key: 'records_today', label: 'Records Today', icon: Moon, color: 'from-slate-500 to-gray-500' },
    { key: 'total_records', label: 'Total Records', icon: Calendar, color: 'from-indigo-500 to-blue-500' },
  ];

  const sidebarItems = [
    { path: '/sleep', label: 'Sleep Records', icon: Moon },
    { path: '/sleep-patterns', label: 'Sleep Patterns', icon: Moon },
  ];

  return (
    <ModuleDashboard
      moduleId="sleep"
      moduleName="Sleep Records"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

