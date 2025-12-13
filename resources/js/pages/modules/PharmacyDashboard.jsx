import React from 'react';
import { Package, AlertTriangle, Box } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function PharmacyDashboard() {
  const statsConfig = [
    { key: 'low_stock', label: 'Low Stock Items', icon: AlertTriangle, color: 'from-red-500 to-rose-500' },
    { key: 'total_items', label: 'Total Items', icon: Box, color: 'from-blue-600 to-indigo-500' },
  ];

  const sidebarItems = [
    { path: '/pharmacy/inventory', label: 'Inventory', icon: Package },
    { path: '/pharmacy/orders', label: 'Orders', icon: Package },
    { path: '/pharmacy/suppliers', label: 'Suppliers', icon: Package },
  ];

  return (
    <ModuleDashboard
      moduleId="pharmacy"
      moduleName="Pharmacy"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

