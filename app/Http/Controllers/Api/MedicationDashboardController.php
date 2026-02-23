<?php

namespace App\Http\Controllers\Api;

use App\Models\Medication;
use App\Models\MedicationAdministration;
use App\Models\MedicationDelivery;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicationDashboardController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = Carbon::now(config('app.timezone'));
        $today = $now->toDateString();

        $isCaregiver = $this->isCaregiver($user);
        $branchIds = $this->resolveBranchIds($user, $isCaregiver);

        $todayStats = $this->getTodayStats($branchIds, $today);
        $upcoming = $this->getUpcomingMedications($branchIds, $now);
        $missedToday = $this->getMissedToday($branchIds, $today);
        $adherenceTrend = $this->getAdherenceTrend($branchIds, $now);
        $recentActivity = $this->getRecentActivity($branchIds);
        $residentSummary = $this->getResidentSummary($branchIds, $today);
        $deliveryStatus = $this->getDeliveryStatus($branchIds, $today);

        return response()->json([
            'today' => $todayStats,
            'upcoming' => $upcoming,
            'missed_today' => $missedToday,
            'adherence_trend' => $adherenceTrend,
            'recent_activity' => $recentActivity,
            'resident_summary' => $residentSummary,
            'delivery_status' => $deliveryStatus,
        ]);
    }

    private function resolveBranchIds($user, bool $isCaregiver): array
    {
        if ($isCaregiver && $user->assigned_branch_id) {
            return [$user->assigned_branch_id];
        }

        if ($user->facility_id) {
            return $this->getFacilityBranchIds($user->facility_id);
        }

        return [];
    }

    private function getTodayStats(array $branchIds, string $today): array
    {
        $activeMeds = Medication::where('is_active', true)
            ->whereIn('branch_id', $branchIds)
            ->where('start_date', '<=', $today)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->get();

        $scheduled = 0;
        foreach ($activeMeds as $med) {
            for ($i = 1; $i <= 4; $i++) {
                if ($med->{"time_{$i}"}) {
                    $scheduled++;
                }
            }
        }

        $baseQuery = MedicationAdministration::whereIn('branch_id', $branchIds)
            ->whereDate('administered_at', $today);

        $administered = (clone $baseQuery)->where('status', 'completed')->count();
        $missed = (clone $baseQuery)->where('status', 'missed')->count();
        $refused = (clone $baseQuery)->where('status', 'refused')->count();
        $adherence = $scheduled > 0 ? round(($administered / $scheduled) * 100) : 0;

        return [
            'scheduled' => $scheduled,
            'administered' => $administered,
            'missed' => $missed,
            'refused' => $refused,
            'adherence' => min($adherence, 100),
            'active_medications' => $activeMeds->count(),
        ];
    }

    private function getUpcomingMedications(array $branchIds, Carbon $now): array
    {
        $today = $now->toDateString();
        $windowHours = 4;

        $activeMeds = Medication::with(['resident:id,first_name,last_name,profile_image_url', 'drug:id,name'])
            ->where('is_active', true)
            ->whereIn('branch_id', $branchIds)
            ->where('start_date', '<=', $today)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
            ->get();

        $upcoming = [];
        $cutoff = $now->copy()->addHours($windowHours);

        foreach ($activeMeds as $med) {
            for ($i = 1; $i <= 4; $i++) {
                $timeStr = $med->{"time_{$i}"};
                if (!$timeStr) continue;

                $parts = explode(':', $timeStr);
                if (count($parts) < 2) continue;

                $scheduledTime = $now->copy()->setTime((int)$parts[0], (int)$parts[1], 0);

                if ($scheduledTime->lt($now) || $scheduledTime->gt($cutoff)) continue;

                $hasAdmin = MedicationAdministration::where('medication_id', $med->id)
                    ->whereBetween('administered_at', [
                        $scheduledTime->copy()->subMinutes(60),
                        $scheduledTime->copy()->addMinutes(60),
                    ])
                    ->whereIn('status', ['completed', 'refused', 'hospital_admission', 'pharmacy_administration_confirm'])
                    ->exists();

                if ($hasAdmin) continue;

                $upcoming[] = [
                    'medication_id' => $med->id,
                    'medication_name' => $med->name ?: $med->drug?->name ?? 'Unknown',
                    'resident_id' => $med->resident_id,
                    'resident_name' => $med->resident
                        ? trim($med->resident->first_name . ' ' . $med->resident->last_name)
                        : 'Unknown',
                    'resident_image' => $med->resident?->profile_image_url,
                    'scheduled_time' => $scheduledTime->format('g:i A'),
                    'scheduled_at' => $scheduledTime->toIso8601String(),
                    'instructions' => $med->instructions,
                    'minutes_until' => (int) $now->diffInMinutes($scheduledTime, false),
                ];
            }
        }

        usort($upcoming, fn ($a, $b) => $a['minutes_until'] - $b['minutes_until']);

        return array_slice($upcoming, 0, 15);
    }

    private function getMissedToday(array $branchIds, string $today): array
    {
        return MedicationAdministration::with([
            'medication:id,name,drug_id,instructions',
            'medication.drug:id,name',
            'resident:id,first_name,last_name,profile_image_url',
        ])
            ->whereIn('branch_id', $branchIds)
            ->whereDate('administered_at', $today)
            ->where('status', 'missed')
            ->orderBy('administered_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'medication_name' => $a->medication?->name ?: $a->medication?->drug?->name ?? 'Unknown',
                'resident_id' => $a->resident_id,
                'resident_name' => $a->resident
                    ? trim($a->resident->first_name . ' ' . $a->resident->last_name)
                    : 'Unknown',
                'resident_image' => $a->resident?->profile_image_url,
                'scheduled_time' => $a->administered_at
                    ? Carbon::parse($a->administered_at)->setTimezone(config('app.timezone'))->format('g:i A')
                    : null,
                'instructions' => $a->medication?->instructions,
            ])
            ->toArray();
    }

    private function getAdherenceTrend(array $branchIds, Carbon $now): array
    {
        $trend = [];

        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = $now->copy()->subDays($daysAgo);
            $dateStr = $date->toDateString();

            $activeMeds = Medication::where('is_active', true)
                ->whereIn('branch_id', $branchIds)
                ->where('start_date', '<=', $dateStr)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $dateStr))
                ->get();

            $scheduled = 0;
            foreach ($activeMeds as $med) {
                for ($i = 1; $i <= 4; $i++) {
                    if ($med->{"time_{$i}"}) $scheduled++;
                }
            }

            $base = MedicationAdministration::whereIn('branch_id', $branchIds)
                ->whereDate('administered_at', $dateStr);

            $administered = (clone $base)->where('status', 'completed')->count();
            $missed = (clone $base)->where('status', 'missed')->count();
            $refused = (clone $base)->where('status', 'refused')->count();

            $adherence = $scheduled > 0 ? round(($administered / $scheduled) * 100) : 0;

            $trend[] = [
                'date' => $dateStr,
                'day' => $date->format('D'),
                'scheduled' => $scheduled,
                'administered' => $administered,
                'missed' => $missed,
                'refused' => $refused,
                'adherence' => min($adherence, 100),
            ];
        }

        return $trend;
    }

    private function getRecentActivity(array $branchIds): array
    {
        return MedicationAdministration::with([
            'medication:id,name,drug_id',
            'medication.drug:id,name',
            'resident:id,first_name,last_name,profile_image_url',
            'administeredBy:id,first_name,last_name,name',
        ])
            ->whereIn('branch_id', $branchIds)
            ->orderBy('administered_at', 'desc')
            ->limit(15)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'medication_name' => $a->medication?->name ?: $a->medication?->drug?->name ?? 'Unknown',
                'resident_name' => $a->resident
                    ? trim($a->resident->first_name . ' ' . $a->resident->last_name)
                    : 'Unknown',
                'resident_image' => $a->resident?->profile_image_url,
                'administered_by' => $a->administeredBy?->name
                    ?: trim(($a->administeredBy?->first_name ?? '') . ' ' . ($a->administeredBy?->last_name ?? ''))
                    ?: 'System',
                'status' => $a->status,
                'administered_at' => $a->administered_at,
                'dosage_given' => $a->dosage_given,
                'notes' => $a->notes,
            ])
            ->toArray();
    }

    private function getResidentSummary(array $branchIds, string $today): array
    {
        $residents = Resident::whereIn('branch_id', $branchIds)
            ->where('status', 'active')
            ->select('id', 'first_name', 'last_name', 'profile_image_url', 'branch_id')
            ->get();

        $summary = [];

        foreach ($residents as $resident) {
            $activeMedCount = Medication::where('resident_id', $resident->id)
                ->where('is_active', true)
                ->where('start_date', '<=', $today)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
                ->count();

            if ($activeMedCount === 0) continue;

            $activeMeds = Medication::where('resident_id', $resident->id)
                ->where('is_active', true)
                ->where('start_date', '<=', $today)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
                ->get();

            $scheduled = 0;
            foreach ($activeMeds as $med) {
                for ($i = 1; $i <= 4; $i++) {
                    if ($med->{"time_{$i}"}) $scheduled++;
                }
            }

            $base = MedicationAdministration::where('resident_id', $resident->id)
                ->whereDate('administered_at', $today);

            $administered = (clone $base)->where('status', 'completed')->count();
            $missedToday = (clone $base)->where('status', 'missed')->count();

            $adherence = $scheduled > 0 ? round(($administered / $scheduled) * 100) : 0;

            $summary[] = [
                'resident_id' => $resident->id,
                'resident_name' => trim($resident->first_name . ' ' . $resident->last_name),
                'resident_image' => $resident->profile_image_url,
                'active_medications' => $activeMedCount,
                'scheduled_today' => $scheduled,
                'administered_today' => $administered,
                'missed_today' => $missedToday,
                'adherence' => min($adherence, 100),
            ];
        }

        usort($summary, function ($a, $b) {
            if ($b['missed_today'] !== $a['missed_today']) {
                return $b['missed_today'] - $a['missed_today'];
            }
            return $a['adherence'] - $b['adherence'];
        });

        return $summary;
    }

    private function getDeliveryStatus(array $branchIds, string $today): array
    {
        $todayCount = MedicationDelivery::whereIn('branch_id', $branchIds)
            ->where('received_date', $today)
            ->count();

        $pendingVerification = MedicationDelivery::whereIn('branch_id', $branchIds)
            ->where('status', 'received')
            ->count();

        $recentDeliveries = MedicationDelivery::with([
            'resident:id,first_name,last_name',
            'receivedBy:id,first_name,last_name,name',
        ])
            ->whereIn('branch_id', $branchIds)
            ->orderBy('received_date', 'desc')
            ->orderBy('received_time', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'pharmacy_name' => $d->pharmacy_name,
                'resident_name' => $d->resident
                    ? trim($d->resident->first_name . ' ' . $d->resident->last_name)
                    : 'Batch Delivery',
                'quantity' => $d->quantity_received,
                'status' => $d->status,
                'delivery_type' => $d->delivery_type,
                'received_date' => $d->received_date,
                'received_by' => $d->receivedBy?->name
                    ?: trim(($d->receivedBy?->first_name ?? '') . ' ' . ($d->receivedBy?->last_name ?? ''))
                    ?: 'Unknown',
            ])
            ->toArray();

        return [
            'today_count' => $todayCount,
            'pending_verification' => $pendingVerification,
            'recent' => $recentDeliveries,
        ];
    }
}
