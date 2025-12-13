<?php

namespace App\Services;

use App\Constants\Modules;
use App\Models\User;
use App\Models\Resident;
use App\Models\Medication;
use App\Models\MedicationAdministration;
use App\Models\VitalSign;
use App\Models\Appointment;
use App\Models\Assessment;
use App\Models\SleepRecord;
use App\Models\CleaningTaskAssignment;
use App\Models\Incident;
use App\Models\FireDrill;
use App\Models\GroceryStatusUpdate;
use App\Models\LeaveRequest;
use App\Models\EmployeeDocument;
use App\Models\PharmacyOrder;
use App\Models\PharmacyInventory;
use App\Models\Expense;
use App\Models\Behavior;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ModuleDashboardService
{
    /**
     * Get stats for all modules (quick overview)
     */
    public function getAllModuleStats(User $user): array
    {
        $stats = [];
        $enabledModules = $user->enabled_modules ?? [];

        // If super admin, show all modules
        if ($user->role === 'super_admin') {
            $enabledModules = array_keys(Modules::all());
        }

        foreach ($enabledModules as $module) {
            try {
                $stats[$module] = [
                    'quick_stat' => $this->getQuickStat($module, $user),
                ];
            } catch (\Exception $e) {
                Log::warning("Failed to get stats for module: {$module}", [
                    'error' => $e->getMessage(),
                ]);
                $stats[$module] = ['quick_stat' => null];
            }
        }

        return $stats;
    }

    /**
     * Get quick stat for a module
     */
    protected function getQuickStat(string $module, User $user): ?int
    {
        if (!Schema::hasTable($this->getTableForModule($module))) {
            return null;
        }

        switch ($module) {
            case Modules::RESIDENTS:
                return $this->getResidentsQuickStat($user);
            case Modules::MEDICATIONS:
                return $this->getMedicationsQuickStat($user);
            case Modules::VITALS:
                return $this->getVitalsQuickStat($user);
            case Modules::APPOINTMENTS:
                return $this->getAppointmentsQuickStat($user);
            case Modules::ASSESSMENTS:
                return $this->getAssessmentsQuickStat($user);
            case Modules::SLEEP:
                return $this->getSleepQuickStat($user);
            case Modules::HOUSEKEEPING:
                return $this->getHousekeepingQuickStat($user);
            case Modules::INCIDENTS:
                return $this->getIncidentsQuickStat($user);
            case Modules::FIRE_DRILLS:
                return $this->getFireDrillsQuickStat($user);
            case Modules::GROCERY_STATUS:
                return $this->getGroceryStatusQuickStat($user);
            case Modules::LEAVE_REQUESTS:
                return $this->getLeaveRequestsQuickStat($user);
            case Modules::EMPLOYEE_DOCUMENTS:
                return $this->getEmployeeDocumentsQuickStat($user);
            case Modules::PHARMACY:
                return $this->getPharmacyQuickStat($user);
            case Modules::BILLING_EXPENSES:
                return $this->getBillingExpensesQuickStat($user);
            case Modules::BEHAVIORS:
                return $this->getBehaviorsQuickStat($user);
            default:
                return null;
        }
    }

    /**
     * Get module dashboard stats
     */
    public function getModuleStats(string $module, User $user): array
    {
        if (!Modules::isValid($module)) {
            return [];
        }

        switch ($module) {
            case Modules::RESIDENTS:
                return $this->getResidentsStats($user);
            case Modules::MEDICATIONS:
                return $this->getMedicationsStats($user);
            case Modules::VITALS:
                return $this->getVitalsStats($user);
            case Modules::APPOINTMENTS:
                return $this->getAppointmentsStats($user);
            case Modules::ASSESSMENTS:
                return $this->getAssessmentsStats($user);
            case Modules::SLEEP:
                return $this->getSleepStats($user);
            case Modules::HOUSEKEEPING:
                return $this->getHousekeepingStats($user);
            case Modules::INCIDENTS:
                return $this->getIncidentsStats($user);
            case Modules::FIRE_DRILLS:
                return $this->getFireDrillsStats($user);
            case Modules::GROCERY_STATUS:
                return $this->getGroceryStatusStats($user);
            case Modules::LEAVE_REQUESTS:
                return $this->getLeaveRequestsStats($user);
            case Modules::EMPLOYEE_DOCUMENTS:
                return $this->getEmployeeDocumentsStats($user);
            case Modules::PHARMACY:
                return $this->getPharmacyStats($user);
            case Modules::BILLING_EXPENSES:
                return $this->getBillingExpensesStats($user);
            case Modules::BEHAVIORS:
                return $this->getBehaviorsStats($user);
            default:
                return [];
        }
    }

    /**
     * Get recent activity for a module
     */
    public function getRecentActivity(string $module, User $user, int $limit = 10): array
    {
        if (!Modules::isValid($module)) {
            return [];
        }

        switch ($module) {
            case Modules::RESIDENTS:
                return $this->getResidentsRecentActivity($user, $limit);
            case Modules::MEDICATIONS:
                return $this->getMedicationsRecentActivity($user, $limit);
            case Modules::VITALS:
                return $this->getVitalsRecentActivity($user, $limit);
            case Modules::APPOINTMENTS:
                return $this->getAppointmentsRecentActivity($user, $limit);
            case Modules::ASSESSMENTS:
                return $this->getAssessmentsRecentActivity($user, $limit);
            case Modules::SLEEP:
                return $this->getSleepRecentActivity($user, $limit);
            case Modules::HOUSEKEEPING:
                return $this->getHousekeepingRecentActivity($user, $limit);
            case Modules::INCIDENTS:
                return $this->getIncidentsRecentActivity($user, $limit);
            case Modules::FIRE_DRILLS:
                return $this->getFireDrillsRecentActivity($user, $limit);
            case Modules::GROCERY_STATUS:
                return $this->getGroceryStatusRecentActivity($user, $limit);
            case Modules::LEAVE_REQUESTS:
                return $this->getLeaveRequestsRecentActivity($user, $limit);
            case Modules::EMPLOYEE_DOCUMENTS:
                return $this->getEmployeeDocumentsRecentActivity($user, $limit);
            case Modules::PHARMACY:
                return $this->getPharmacyRecentActivity($user, $limit);
            case Modules::BILLING_EXPENSES:
                return $this->getBillingExpensesRecentActivity($user, $limit);
            case Modules::BEHAVIORS:
                return $this->getBehaviorsRecentActivity($user, $limit);
            default:
                return [];
        }
    }

    // Quick stat methods
    protected function getResidentsQuickStat(User $user): int
    {
        $query = Resident::query();
        $this->applyFacilityFilter($query, $user);
        return $query->where('is_active', true)->count();
    }

    protected function getMedicationsQuickStat(User $user): int
    {
        if (!Schema::hasTable('medications')) return 0;
        $query = Medication::query();
        $this->applyFacilityFilter($query, $user);
        return $query->where('status', 'active')
            ->whereHas('administrations', function($q) {
                $q->whereNull('administered_at');
            })
            ->count();
    }

    protected function getVitalsQuickStat(User $user): int
    {
        if (!Schema::hasTable('vital_signs')) return 0;
        $query = VitalSign::query();
        $this->applyFacilityFilter($query, $user);
        return $query->whereDate('measurement_date', today())
            ->where('status', 'pending_review')
            ->count();
    }

    protected function getAppointmentsQuickStat(User $user): int
    {
        if (!Schema::hasTable('appointments')) return 0;
        $query = Appointment::query();
        $this->applyFacilityFilter($query, $user);
        return $query->where('status', 'scheduled')
            ->where('appointment_date', '>=', today())
            ->count();
    }

    protected function getAssessmentsQuickStat(User $user): int
    {
        if (!Schema::hasTable('assessments')) return 0;
        $query = Assessment::query();
        $this->applyFacilityFilter($query, $user);
        return $query->where('status', 'pending')->count();
    }

    protected function getSleepQuickStat(User $user): int
    {
        if (!Schema::hasTable('sleep_records')) return 0;
        $query = SleepRecord::query();
        $this->applyFacilityFilter($query, $user);
        return $query->whereDate('sleep_date', today())->count();
    }

    protected function getHousekeepingQuickStat(User $user): int
    {
        if (!Schema::hasTable('cleaning_task_assignments')) return 0;
        $query = CleaningTaskAssignment::query();
        $this->applyFacilityFilter($query, $user);
        return $query->where('status', 'pending')->count();
    }

    protected function getIncidentsQuickStat(User $user): int
    {
        if (!Schema::hasTable('incidents')) return 0;
        $query = Incident::query();
        $this->applyFacilityFilter($query, $user);
        return $query->where('status', 'open')->count();
    }

    protected function getFireDrillsQuickStat(User $user): int
    {
        if (!Schema::hasTable('fire_drills')) return 0;
        $query = FireDrill::query();
        $this->applyFacilityFilter($query, $user);
        return $query->where('status', 'scheduled')
            ->where('scheduled_date', '>=', today())
            ->count();
    }

    protected function getGroceryStatusQuickStat(User $user): int
    {
        if (!Schema::hasTable('grocery_status_updates')) return 0;
        $query = GroceryStatusUpdate::query();
        $this->applyFacilityFilter($query, $user);
        $weekStart = Carbon::now()->startOfWeek();
        return $query->where('week_start_date', $weekStart)
            ->whereNull('status')
            ->count();
    }

    protected function getLeaveRequestsQuickStat(User $user): int
    {
        if (!Schema::hasTable('leave_requests')) return 0;
        $query = LeaveRequest::query();
        $this->applyFacilityFilter($query, $user);
        return $query->where('status', 'pending')->count();
    }

    protected function getEmployeeDocumentsQuickStat(User $user): int
    {
        if (!Schema::hasTable('employee_documents')) return 0;
        $query = EmployeeDocument::query();
        $this->applyFacilityFilter($query, $user);
        $expiringSoon = Carbon::now()->addDays(30);
        return $query->where('expiration_date', '<=', $expiringSoon)
            ->where('expiration_date', '>=', today())
            ->count();
    }

    protected function getPharmacyQuickStat(User $user): int
    {
        if (!Schema::hasTable('pharmacy_inventory')) return 0;
        $query = PharmacyInventory::query();
        $this->applyFacilityFilter($query, $user);
        return $query->whereColumn('quantity', '<=', 'reorder_level')->count();
    }

    protected function getBillingExpensesQuickStat(User $user): int
    {
        if (!Schema::hasTable('expenses')) return 0;
        $query = Expense::query();
        $this->applyFacilityFilter($query, $user);
        return $query->where('status', 'pending')->count();
    }

    protected function getBehaviorsQuickStat(User $user): int
    {
        if (!Schema::hasTable('behaviors')) return 0;
        $query = Behavior::query();
        $this->applyFacilityFilter($query, $user);
        return $query->whereDate('occurred_at', today())->count();
    }

    // Full stats methods (simplified - will be expanded per module)
    protected function getResidentsStats(User $user): array
    {
        $query = Resident::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'total_residents' => $query->count(),
            'active_residents' => $query->where('is_active', true)->count(),
            'new_this_month' => $query->whereMonth('created_at', now()->month)->count(),
        ];
    }

    protected function getMedicationsStats(User $user): array
    {
        if (!Schema::hasTable('medications')) return [];
        $query = Medication::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'pending_administrations' => $query->where('status', 'active')
                ->whereHas('administrations', function($q) {
                    $q->whereNull('administered_at');
                })->count(),
            'active_medications' => $query->where('status', 'active')->count(),
            'overdue_medications' => $query->where('status', 'active')
                ->whereHas('administrations', function($q) {
                    $q->whereNull('administered_at')
                      ->where('scheduled_time', '<', now());
                })->count(),
        ];
    }

    protected function getVitalsStats(User $user): array
    {
        if (!Schema::hasTable('vital_signs')) return [];
        $query = VitalSign::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'vitals_today' => $query->whereDate('measurement_date', today())->count(),
            'critical_alerts' => $query->where('status', 'critical')->count(),
            'pending_review' => $query->where('status', 'pending_review')->count(),
        ];
    }

    protected function getAppointmentsStats(User $user): array
    {
        if (!Schema::hasTable('appointments')) return [];
        $query = Appointment::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'upcoming' => $query->where('status', 'scheduled')
                ->where('appointment_date', '>=', today())->count(),
            'completed_today' => $query->where('status', 'completed')
                ->whereDate('appointment_date', today())->count(),
            'pending' => $query->where('status', 'pending')->count(),
        ];
    }

    protected function getAssessmentsStats(User $user): array
    {
        if (!Schema::hasTable('assessments')) return [];
        $query = Assessment::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'pending' => $query->where('status', 'pending')->count(),
            'completed_this_month' => $query->where('status', 'completed')
                ->whereMonth('completed_at', now()->month)->count(),
            'overdue' => $query->where('status', 'pending')
                ->where('due_date', '<', today())->count(),
        ];
    }

    protected function getSleepStats(User $user): array
    {
        if (!Schema::hasTable('sleep_records')) return [];
        $query = SleepRecord::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'records_today' => $query->whereDate('sleep_date', today())->count(),
            'total_records' => $query->count(),
        ];
    }

    protected function getHousekeepingStats(User $user): array
    {
        if (!Schema::hasTable('cleaning_task_assignments')) return [];
        $query = CleaningTaskAssignment::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'completed_today' => $query->where('status', 'completed')
                ->whereDate('completed_at', today())->count(),
            'pending' => $query->where('status', 'pending')->count(),
        ];
    }

    protected function getIncidentsStats(User $user): array
    {
        if (!Schema::hasTable('incidents')) return [];
        $query = Incident::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'this_month' => $query->whereMonth('incident_date', now()->month)->count(),
            'open' => $query->where('status', 'open')->count(),
            'resolved_today' => $query->where('status', 'resolved')
                ->whereDate('resolved_at', today())->count(),
        ];
    }

    protected function getFireDrillsStats(User $user): array
    {
        if (!Schema::hasTable('fire_drills')) return [];
        $query = FireDrill::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'upcoming' => $query->where('status', 'scheduled')
                ->where('scheduled_date', '>=', today())->count(),
            'completed_this_month' => $query->where('status', 'completed')
                ->whereMonth('completed_at', now()->month)->count(),
        ];
    }

    protected function getGroceryStatusStats(User $user): array
    {
        if (!Schema::hasTable('grocery_status_updates')) return [];
        $query = GroceryStatusUpdate::query();
        $this->applyFacilityFilter($query, $user);
        $weekStart = Carbon::now()->startOfWeek();
        
        return [
            'updates_today' => $query->whereDate('updated_at', today())->count(),
            'pending_this_week' => $query->where('week_start_date', $weekStart)
                ->whereNull('status')->count(),
        ];
    }

    protected function getLeaveRequestsStats(User $user): array
    {
        if (!Schema::hasTable('leave_requests')) return [];
        $query = LeaveRequest::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'pending' => $query->where('status', 'pending')->count(),
            'approved_this_month' => $query->where('status', 'approved')
                ->whereMonth('approved_at', now()->month)->count(),
        ];
    }

    protected function getEmployeeDocumentsStats(User $user): array
    {
        if (!Schema::hasTable('employee_documents')) return [];
        $query = EmployeeDocument::query();
        $this->applyFacilityFilter($query, $user);
        $expiringSoon = Carbon::now()->addDays(30);
        
        return [
            'total_documents' => $query->count(),
            'expiring_soon' => $query->where('expiration_date', '<=', $expiringSoon)
                ->where('expiration_date', '>=', today())->count(),
        ];
    }

    protected function getPharmacyStats(User $user): array
    {
        if (!Schema::hasTable('pharmacy_inventory')) return [];
        $query = PharmacyInventory::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'low_stock' => $query->whereColumn('quantity', '<=', 'reorder_level')->count(),
            'total_items' => $query->count(),
        ];
    }

    protected function getBillingExpensesStats(User $user): array
    {
        if (!Schema::hasTable('expenses')) return [];
        $query = Expense::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'expenses_this_month' => $query->whereMonth('expense_date', now()->month)->count(),
            'pending' => $query->where('status', 'pending')->count(),
        ];
    }

    protected function getBehaviorsStats(User $user): array
    {
        if (!Schema::hasTable('behaviors')) return [];
        $query = Behavior::query();
        $this->applyFacilityFilter($query, $user);
        
        return [
            'today' => $query->whereDate('occurred_at', today())->count(),
            'this_month' => $query->whereMonth('occurred_at', now()->month)->count(),
        ];
    }

    // Recent activity methods (simplified - return basic structure)
    protected function getResidentsRecentActivity(User $user, int $limit): array
    {
        $query = Resident::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($resident) {
            return [
                'id' => $resident->id,
                'title' => $resident->name ?? 'Resident #' . $resident->id,
                'description' => 'Resident profile',
                'date' => $resident->updated_at,
            ];
        })->toArray();
    }

    protected function getMedicationsRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('medication_administrations')) return [];
        $query = MedicationAdministration::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($admin) {
            return [
                'id' => $admin->id,
                'title' => 'Medication Administration',
                'description' => $admin->medication->drug->name ?? 'Medication',
                'date' => $admin->created_at,
            ];
        })->toArray();
    }

    protected function getVitalsRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('vital_signs')) return [];
        $query = VitalSign::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($vital) {
            return [
                'id' => $vital->id,
                'title' => 'Vital Sign Recorded',
                'description' => $vital->resident->name ?? 'Resident',
                'date' => $vital->measurement_date,
            ];
        })->toArray();
    }

    protected function getAppointmentsRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('appointments')) return [];
        $query = Appointment::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($appt) {
            return [
                'id' => $appt->id,
                'title' => $appt->title ?? 'Appointment',
                'description' => $appt->resident->name ?? 'Resident',
                'date' => $appt->appointment_date,
            ];
        })->toArray();
    }

    protected function getAssessmentsRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('assessments')) return [];
        $query = Assessment::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($assessment) {
            return [
                'id' => $assessment->id,
                'title' => $assessment->type ?? 'Assessment',
                'description' => $assessment->resident->name ?? 'Resident',
                'date' => $assessment->completed_at ?? $assessment->created_at,
            ];
        })->toArray();
    }

    protected function getSleepRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('sleep_records')) return [];
        $query = SleepRecord::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($record) {
            return [
                'id' => $record->id,
                'title' => 'Sleep Record',
                'description' => $record->resident->name ?? 'Resident',
                'date' => $record->sleep_date,
            ];
        })->toArray();
    }

    protected function getHousekeepingRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('cleaning_task_assignments')) return [];
        $query = CleaningTaskAssignment::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($task) {
            return [
                'id' => $task->id,
                'title' => $task->task->name ?? 'Cleaning Task',
                'description' => 'Task assignment',
                'date' => $task->completed_at ?? $task->created_at,
            ];
        })->toArray();
    }

    protected function getIncidentsRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('incidents')) return [];
        $query = Incident::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($incident) {
            return [
                'id' => $incident->id,
                'title' => $incident->type ?? 'Incident',
                'description' => $incident->description ?? '',
                'date' => $incident->incident_date,
            ];
        })->toArray();
    }

    protected function getFireDrillsRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('fire_drills')) return [];
        $query = FireDrill::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($drill) {
            return [
                'id' => $drill->id,
                'title' => 'Fire Drill',
                'description' => $drill->scheduled_date ? $drill->scheduled_date->format('M d, Y') : '',
                'date' => $drill->completed_at ?? $drill->scheduled_date,
            ];
        })->toArray();
    }

    protected function getGroceryStatusRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('grocery_status_updates')) return [];
        $query = GroceryStatusUpdate::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($update) {
            return [
                'id' => $update->id,
                'title' => 'Grocery Status Update',
                'description' => 'Status update',
                'date' => $update->updated_at,
            ];
        })->toArray();
    }

    protected function getLeaveRequestsRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('leave_requests')) return [];
        $query = LeaveRequest::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($request) {
            return [
                'id' => $request->id,
                'title' => 'Leave Request',
                'description' => $request->staff->name ?? 'Staff',
                'date' => $request->created_at,
            ];
        })->toArray();
    }

    protected function getEmployeeDocumentsRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('employee_documents')) return [];
        $query = EmployeeDocument::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($doc) {
            return [
                'id' => $doc->id,
                'title' => $doc->document_type ?? 'Document',
                'description' => $doc->employee->name ?? 'Employee',
                'date' => $doc->uploaded_at ?? $doc->created_at,
            ];
        })->toArray();
    }

    protected function getPharmacyRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('pharmacy_orders')) return [];
        $query = PharmacyOrder::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($order) {
            return [
                'id' => $order->id,
                'title' => 'Pharmacy Order',
                'description' => 'Order #' . $order->id,
                'date' => $order->created_at,
            ];
        })->toArray();
    }

    protected function getBillingExpensesRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('expenses')) return [];
        $query = Expense::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($expense) {
            return [
                'id' => $expense->id,
                'title' => $expense->category ?? 'Expense',
                'description' => '$' . number_format($expense->amount ?? 0, 2),
                'date' => $expense->expense_date,
            ];
        })->toArray();
    }

    protected function getBehaviorsRecentActivity(User $user, int $limit): array
    {
        if (!Schema::hasTable('behaviors')) return [];
        $query = Behavior::query();
        $this->applyFacilityFilter($query, $user);
        return $query->latest()->limit($limit)->get()->map(function($behavior) {
            return [
                'id' => $behavior->id,
                'title' => $behavior->type ?? 'Behavior',
                'description' => $behavior->resident->name ?? 'Resident',
                'date' => $behavior->occurred_at,
            ];
        })->toArray();
    }

    /**
     * Apply facility filtering to query
     */
    protected function applyFacilityFilter($query, User $user): void
    {
        if ($user->role === 'super_admin') {
            return; // Super admins see all
        }

        // Get facility from user or branch
        $facilityId = $user->facility_id;
        if (!$facilityId && $user->assigned_branch_id) {
            $branch = \App\Models\Branch::find($user->assigned_branch_id);
            $facilityId = $branch?->facility_id;
        }

        if ($facilityId) {
            // Filter through branch relationship if model has branch_id
            if (method_exists($query->getModel(), 'branch')) {
                $query->whereHas('branch', function($q) use ($facilityId) {
                    $q->where('facility_id', $facilityId);
                });
            } elseif (method_exists($query->getModel(), 'resident')) {
                // For models that relate through resident
                $query->whereHas('resident.branch', function($q) use ($facilityId) {
                    $q->where('facility_id', $facilityId);
                });
            }
        }

        // Apply branch filter for caregivers
        if (in_array($user->role, ['caregiver', 'nurse']) && $user->assigned_branch_id) {
            if (method_exists($query->getModel(), 'branch')) {
                $query->where('branch_id', $user->assigned_branch_id);
            } elseif (method_exists($query->getModel(), 'resident')) {
                $query->whereHas('resident', function($q) use ($user) {
                    $q->where('branch_id', $user->assigned_branch_id);
                });
            }
        }
    }

    /**
     * Get table name for module
     */
    protected function getTableForModule(string $module): string
    {
        $tableMap = [
            Modules::RESIDENTS => 'residents',
            Modules::MEDICATIONS => 'medications',
            Modules::VITALS => 'vital_signs',
            Modules::APPOINTMENTS => 'appointments',
            Modules::ASSESSMENTS => 'assessments',
            Modules::SLEEP => 'sleep_records',
            Modules::HOUSEKEEPING => 'cleaning_task_assignments',
            Modules::INCIDENTS => 'incidents',
            Modules::FIRE_DRILLS => 'fire_drills',
            Modules::GROCERY_STATUS => 'grocery_status_updates',
            Modules::LEAVE_REQUESTS => 'leave_requests',
            Modules::EMPLOYEE_DOCUMENTS => 'employee_documents',
            Modules::PHARMACY => 'pharmacy_inventory',
            Modules::BILLING_EXPENSES => 'expenses',
            Modules::BEHAVIORS => 'behaviors',
        ];

        return $tableMap[$module] ?? '';
    }
}

