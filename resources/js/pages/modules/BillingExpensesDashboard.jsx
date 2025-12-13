import React from 'react';
import { DollarSign, Calendar, Clock } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function BillingExpensesDashboard() {
  const statsConfig = [
    { key: 'expenses_this_month', label: 'Expenses This Month', icon: Calendar, color: 'from-green-600 to-emerald-500' },
    { key: 'pending', label: 'Pending', icon: Clock, color: 'from-yellow-500 to-amber-500' },
  ];

  const sidebarItems = [
    { path: '/billing/expenses', label: 'Expenses', icon: DollarSign },
    { path: '/billing/expense-categories', label: 'Categories', icon: DollarSign },
    { path: '/billing/invoices', label: 'Invoices', icon: DollarSign },
    { path: '/billing/reports', label: 'Reports', icon: DollarSign },
  ];

  return (
    <ModuleDashboard
      moduleId="billing_expenses"
      moduleName="Billing & Expenses"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

