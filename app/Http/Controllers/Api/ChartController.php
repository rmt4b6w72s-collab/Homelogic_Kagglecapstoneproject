<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use App\Models\VitalSign;
use App\Models\Assessment;
use App\Models\Appointment;
use App\Models\SleepRecord;
use App\Models\User;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ChartController extends Controller
{
    // Resident Charts
    public function residentStats(): JsonResponse
    {
        $stats = [
            'total_residents' => Resident::count(),
            'active_residents' => Resident::where('is_active', true)->count(),
            'by_branch' => Resident::selectRaw('branches.name as branch_name, COUNT(*) as count')
                ->join('branches', 'residents.branch_id', '=', 'branches.id')
                ->groupBy('branches.id', 'branches.name')
                ->get(),
            'by_status' => Resident::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
        ];

        return response()->json($stats);
    }

    // Vitals Charts
    public function vitalsStats(): JsonResponse
    {
        $stats = [
            'total_vitals' => VitalSign::count(),
            'today_vitals' => VitalSign::whereDate('measurement_date', today())->count(),
            'week_vitals' => VitalSign::whereBetween('measurement_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count(),
            'month_vitals' => VitalSign::whereMonth('measurement_date', Carbon::now()->month)->count(),
            'trends' => $this->getVitalsTrends(),
            'blood_pressure' => $this->getBloodPressureData(),
            'temperature' => $this->getTemperatureData(),
        ];

        return response()->json($stats);
    }

    private function getVitalsTrends(): array
    {
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = VitalSign::whereDate('measurement_date', $date)->count();
            $last7Days[] = [
                'date' => $date->format('M j'),
                'count' => $count
            ];
        }
        return $last7Days;
    }

    private function getBloodPressureData(): array
    {
        $vitals = VitalSign::whereNotNull('systolic')
            ->whereNotNull('diastolic')
            ->latest('measurement_date')
            ->limit(50)
            ->get();
        
        return [
            'labels' => $vitals->map(fn($v) => $v->measurement_date->format('M j'))->toArray(),
            'systolic' => $vitals->pluck('systolic')->toArray(),
            'diastolic' => $vitals->pluck('diastolic')->toArray(),
        ];
    }

    private function getTemperatureData(): array
    {
        $vitals = VitalSign::whereNotNull('temperature')
            ->latest('measurement_date')
            ->limit(50)
            ->get();
        
        return [
            'labels' => $vitals->map(fn($v) => $v->measurement_date->format('M j'))->toArray(),
            'temperature' => $vitals->pluck('temperature')->toArray(),
        ];
    }

    // Assessment Charts
    public function assessmentStats(): JsonResponse
    {
        $stats = [
            'total_assessments' => Assessment::count(),
            'completed_assessments' => Assessment::where('status', 'approved')->count(),
            'pending_assessments' => Assessment::whereNotIn('status', ['approved', 'archived'])->count(),
            'this_month' => Assessment::whereMonth('created_at', Carbon::now()->month)->count(),
            'by_type' => Assessment::selectRaw('assessment_type, COUNT(*) as count')
                ->groupBy('assessment_type')
                ->get(),
            'completion_trends' => $this->getAssessmentTrends(),
        ];

        return response()->json($stats);
    }

    private function getAssessmentTrends(): array
    {
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = Assessment::whereDate('assessment_date', $date)->count();
            $last7Days[] = [
                'date' => $date->format('M j'),
                'count' => $count
            ];
        }
        return $last7Days;
    }

    // Appointments Charts
    public function appointmentStats(): JsonResponse
    {
        $stats = [
            'total_appointments' => Appointment::count(),
            'upcoming' => Appointment::where('appointment_date', '>=', Carbon::today())->count(),
            'completed' => Appointment::where('status', 'completed')->count(),
            'pending' => Appointment::where('status', 'scheduled')->count(),
            'by_status' => Appointment::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'trends' => $this->getAppointmentTrends(),
        ];

        return response()->json($stats);
    }

    private function getAppointmentTrends(): array
    {
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = Appointment::whereDate('appointment_date', $date)->count();
            $last7Days[] = [
                'date' => $date->format('M j'),
                'count' => $count
            ];
        }
        return $last7Days;
    }

    // Sleep Charts
    public function sleepStats(): JsonResponse
    {
        $stats = [
            'total_records' => SleepRecord::count(),
            'avg_sleep_hours' => SleepRecord::avg('total_sleep_hours'),
            'avg_quality' => SleepRecord::whereNotNull('sleep_quality')->avg('sleep_quality'),
            'sleep_duration_trends' => $this->getSleepDurationTrends(),
            'quality_distribution' => $this->getSleepQualityDistribution(),
        ];

        return response()->json($stats);
    }

    private function getSleepDurationTrends(): array
    {
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $avg = SleepRecord::whereDate('sleep_date', $date)->avg('total_sleep_hours');
            $last7Days[] = [
                'date' => $date->format('M j'),
                'avg_hours' => round($avg ?? 0, 2)
            ];
        }
        return $last7Days;
    }

    private function getSleepQualityDistribution(): array
    {
        return SleepRecord::selectRaw('sleep_quality, COUNT(*) as count')
            ->whereNotNull('sleep_quality')
            ->groupBy('sleep_quality')
            ->orderBy('sleep_quality')
            ->get()
            ->map(fn($r) => ['quality' => $r->sleep_quality, 'count' => $r->count])
            ->toArray();
    }

    // Staff Charts
    public function staffStats(): JsonResponse
    {
        $stats = [
            'total_staff' => User::where('is_active', true)->count(),
            'total_caregivers' => User::whereHas('roles', function($q) {
                $q->where('name', 'caregiver');
            })->where('is_active', true)->count(),
            'active_assignments' => \App\Models\Assignment::where('is_active', true)->count(),
            'pending_leave' => LeaveRequest::where('status', 'pending')->count(),
            'leave_by_status' => LeaveRequest::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
        ];

        return response()->json($stats);
    }
}

