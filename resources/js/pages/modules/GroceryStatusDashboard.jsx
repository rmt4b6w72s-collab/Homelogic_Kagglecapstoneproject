import React from 'react';
import { ShoppingCart, Calendar, Clock } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function GroceryStatusDashboard() {
  const statsConfig = [
    { key: 'updates_today', label: 'Updates Today', icon: Calendar, color: 'from-amber-500 to-yellow-500' },
    { key: 'pending_this_week', label: 'Pending This Week', icon: Clock, color: 'from-orange-500 to-red-500' },
  ];

  const sidebarItems = [
    { path: '/grocery-status', label: 'Grocery Status', icon: ShoppingCart },
  ];

  return (
    <ModuleDashboard
      moduleId="grocery_status"
      moduleName="Grocery Status"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

