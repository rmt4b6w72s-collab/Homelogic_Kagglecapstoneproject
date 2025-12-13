import React, { lazy, Suspense } from 'react';
import { useParams, Routes, Route } from 'react-router-dom';

// Lazy load all module dashboards
const ResidentsDashboard = lazy(() => import('../../pages/modules/ResidentsDashboard'));
const MedicationsDashboard = lazy(() => import('../../pages/modules/MedicationsDashboard'));
const VitalsDashboard = lazy(() => import('../../pages/modules/VitalsDashboard'));
const AppointmentsDashboard = lazy(() => import('../../pages/modules/AppointmentsDashboard'));
const AssessmentsDashboard = lazy(() => import('../../pages/modules/AssessmentsDashboard'));
const SleepDashboard = lazy(() => import('../../pages/modules/SleepDashboard'));
const HousekeepingDashboard = lazy(() => import('../../pages/modules/HousekeepingDashboard'));
const IncidentsDashboard = lazy(() => import('../../pages/modules/IncidentsDashboard'));
const FireDrillsDashboard = lazy(() => import('../../pages/modules/FireDrillsDashboard'));
const GroceryStatusDashboard = lazy(() => import('../../pages/modules/GroceryStatusDashboard'));
const LeaveRequestsDashboard = lazy(() => import('../../pages/modules/LeaveRequestsDashboard'));
const EmployeeDocumentsDashboard = lazy(() => import('../../pages/modules/EmployeeDocumentsDashboard'));
const PharmacyDashboard = lazy(() => import('../../pages/modules/PharmacyDashboard'));
const BillingExpensesDashboard = lazy(() => import('../../pages/modules/BillingExpensesDashboard'));
const BehaviorsDashboard = lazy(() => import('../../pages/modules/BehaviorsDashboard'));

const moduleDashboardMap = {
  residents: ResidentsDashboard,
  medications: MedicationsDashboard,
  vitals: VitalsDashboard,
  appointments: AppointmentsDashboard,
  assessments: AssessmentsDashboard,
  sleep: SleepDashboard,
  housekeeping: HousekeepingDashboard,
  incidents: IncidentsDashboard,
  fire_drills: FireDrillsDashboard,
  grocery_status: GroceryStatusDashboard,
  leave_requests: LeaveRequestsDashboard,
  employee_documents: EmployeeDocumentsDashboard,
  pharmacy: PharmacyDashboard,
  billing_expenses: BillingExpensesDashboard,
  behaviors: BehaviorsDashboard,
};

export default function ModuleRouter({ type = 'dashboard' }) {
  const { module } = useParams();
  const DashboardComponent = moduleDashboardMap[module];
  const ModuleAnalytics = lazy(() => import('../../pages/modules/ModuleAnalytics'));

  if (!DashboardComponent) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-2">Module Not Found</h1>
          <p className="text-gray-600">The module "{module}" does not exist.</p>
        </div>
      </div>
    );
  }

  return (
    <Routes>
      <Route 
        path="dashboard" 
        element={
          <Suspense fallback={
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
              <div className="text-center">
                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p className="mt-4 text-gray-600">Loading module...</p>
              </div>
            </div>
          }>
            <DashboardComponent />
          </Suspense>
        } 
      />
      <Route 
        path="analytics" 
        element={
          <Suspense fallback={
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
              <div className="text-center">
                <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p className="mt-4 text-gray-600">Loading analytics...</p>
              </div>
            </div>
          }>
            <ModuleAnalytics />
          </Suspense>
        } 
      />
    </Routes>
  );
}

