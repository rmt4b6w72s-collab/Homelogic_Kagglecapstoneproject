import React from 'react';
import { FileText, AlertTriangle, FileCheck } from 'lucide-react';
import ModuleDashboard from '../../components/modules/ModuleDashboard';

export default function EmployeeDocumentsDashboard() {
  const statsConfig = [
    { key: 'total_documents', label: 'Total Documents', icon: FileText, color: 'from-gray-500 to-slate-500' },
    { key: 'expiring_soon', label: 'Expiring Soon', icon: AlertTriangle, color: 'from-yellow-500 to-amber-500' },
  ];

  const sidebarItems = [
    { path: '/administration/employee-documents', label: 'Employee Documents', icon: FileText },
  ];

  return (
    <ModuleDashboard
      moduleId="employee_documents"
      moduleName="Employee Documents"
      statsConfig={statsConfig}
      sidebarItems={sidebarItems}
    />
  );
}

