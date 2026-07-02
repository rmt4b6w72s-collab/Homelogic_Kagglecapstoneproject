<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\BehaviorChart;
use App\Models\ChartAssistantConversation;
use App\Models\Fax;
use App\Models\Medication;
use App\Models\MedicationAdministration;
use App\Models\Resident;
use App\Models\SleepRecord;
use App\Models\VitalSign;
use App\Services\ChartAssistantService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChartAssistantController extends Controller
{
    public function __construct(private ChartAssistantService $assistantService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'Chart assistant access is not permitted.');
        }

        $query = ChartAssistantConversation::query();

        if ($user->role !== 'super_admin' && $user->facility_id) {
            $query->whereHas('resident.branch', function ($q) use ($user) {
                $q->where('facility_id', $user->facility_id);
            });
        }

        if ($request->filled('resident_id')) {
            $query->where('resident_id', $request->integer('resident_id'));
        }

        $conversations = $query->latest()->get()->map(function (ChartAssistantConversation $conversation) {
            return [
                'id' => $conversation->id,
                'resident_id' => $conversation->resident_id,
                'title' => $conversation->title ?? 'Chart review',
                'status' => $conversation->status,
                'context' => $conversation->context ?? [],
                'messages' => $conversation->messages ?? [],
                'created_at' => $conversation->created_at?->toDateTimeString(),
                'updated_at' => $conversation->updated_at?->toDateTimeString(),
            ];
        });

        return response()->json(['conversations' => $conversations]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'Chart assistant access is not permitted.');
        }

        $residentId = $request->input('resident_id');
        if (empty($residentId)) {
            return response()->json(['message' => 'resident_id is required'], 422);
        }

        $resident = Resident::query()->findOrFail($residentId);
        $this->authorizeResidentAccess($resident);

        $context = $this->buildPayload($resident, $request);
        $conversation = ChartAssistantConversation::create([
            'resident_id' => $resident->id,
            'title' => $request->input('title') ?: 'Chart review',
            'status' => 'active',
            'context' => $context,
            'messages' => [[
                'role' => 'system',
                'content' => 'You are a resident care chart assistant. Be concise and safety-aware.',
            ]],
        ]);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'resident_id' => $conversation->resident_id,
                'title' => $conversation->title,
                'status' => $conversation->status,
                'context' => $conversation->context,
                'messages' => $conversation->messages,
            ],
        ]);
    }

    public function showConversation(ChartAssistantConversation $conversation): JsonResponse
    {
        $this->authorizeConversationAccess($conversation);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'resident_id' => $conversation->resident_id,
                'title' => $conversation->title,
                'status' => $conversation->status,
                'context' => $conversation->context ?? [],
                'messages' => $conversation->messages ?? [],
                'created_at' => $conversation->created_at?->toDateTimeString(),
                'updated_at' => $conversation->updated_at?->toDateTimeString(),
            ],
        ]);
    }

    public function sendMessage(Request $request, ChartAssistantConversation $conversation): JsonResponse
    {
        $this->authorizeConversationAccess($conversation);

        $message = trim((string) $request->input('message', ''));
        if ($message === '') {
            return response()->json(['message' => 'message is required'], 422);
        }

        $messages = is_array($conversation->messages) ? $conversation->messages : [];
        $messages[] = ['role' => 'user', 'content' => $message];

        $assistantResult = $this->assistantService->analyze($conversation->context ?? [], $message);
        $messages[] = ['role' => 'assistant', 'content' => $assistantResult['summary'] ?? 'No response available.'];

        $conversation->update([
            'messages' => $messages,
            'context' => array_merge($conversation->context ?? [], ['last_prompt' => $message]),
        ]);

        return response()->json([
            'assistant' => $assistantResult,
            'conversation' => [
                'id' => $conversation->id,
                'messages' => $messages,
            ],
        ]);
    }

    public function analyze(Request $request, Resident $resident): JsonResponse
    {
        $this->authorizeResidentAccess($resident);

        $payload = $this->buildPayload($resident, $request);
        $result = $this->assistantService->analyze($payload, $request->get('prompt'));

        return response()->json([
            'resident_id' => $resident->id,
            'window' => $request->get('window', 'last 14 days'),
            'payload' => $payload,
            'assistant' => $result,
        ]);
    }

    public function show(Request $request, Resident $resident): JsonResponse
    {
        return $this->analyze($request, $resident);
    }

    private function authorizeResidentAccess(Resident $resident): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(403, 'Chart assistant access is not permitted.');
        }

        if ($user->role === 'super_admin') {
            return;
        }

        $facilityId = $resident->branch?->facility_id;
        if (! $facilityId || $facilityId !== $user->facility_id) {
            abort(404, 'Resident not found.');
        }
    }

    private function authorizeConversationAccess(ChartAssistantConversation $conversation): void
    {
        $conversation->loadMissing('resident.branch');
        if (! $conversation->resident) {
            abort(404, 'Conversation not found.');
        }

        $this->authorizeResidentAccess($conversation->resident);
    }

    private function isComparisonPrompt(string $prompt): bool
    {
        $normalized = strtolower(trim($prompt));

        return $normalized !== '' && (
            str_contains($normalized, 'compare')
            || str_contains($normalized, 'prior week')
            || str_contains($normalized, 'previous week')
            || str_contains($normalized, 'what changed')
            || str_contains($normalized, 'week to week')
            || str_contains($normalized, 'versus')
            || str_contains($normalized, 'vs')
        );
    }

    private function buildPeriodMetrics(Resident $resident, string $startDate, string $endDate): array
    {
        $vitalsQuery = VitalSign::query()
            ->where('resident_id', $resident->id)
            ->whereBetween('measurement_date', [$startDate, $endDate])
            ->orderBy('measurement_date', 'asc');

        $vitals = $vitalsQuery->get();
        $criticalCount = $vitals->filter(fn ($item) => $item->status === 'critical')->count();
        $warningCount = $vitals->filter(fn ($item) => $item->status === 'pending_review')->count();
        $latestVital = $vitals->sortByDesc('measurement_date')->first();
        $recentVitals = $vitals->take(6)->map(function ($item) {
            return [
                'date' => $item->measurement_date?->toDateString(),
                'temperature' => $item->temperature,
                'pulse' => $item->pulse,
                'oxygen_saturation' => $item->oxygen_saturation,
                'status' => $item->status,
            ];
        })->values();

        $sleepRecords = SleepRecord::query()
            ->where('resident_id', $resident->id)
            ->whereBetween('sleep_date', [$startDate, $endDate])
            ->orderBy('sleep_date', 'asc')
            ->get();

        $behaviorCharts = BehaviorChart::query()
            ->where('resident_id', $resident->id)
            ->whereBetween('chart_date', [$startDate, $endDate])
            ->orderBy('chart_date', 'asc')
            ->get();

        $providerNotifiedCount = $behaviorCharts->filter(function ($chart) {
            return $chart->logs->contains(fn ($log) => (bool) $log->reported_to_provider);
        })->count();

        $upcomingAppointments = Appointment::query()
            ->where('resident_id', $resident->id)
            ->where('appointment_date', '>=', $startDate)
            ->where('appointment_date', '<=', $endDate)
            ->count();

        $recentFaxes = Fax::query()
            ->where('resident_id', $resident->id)
            ->whereBetween('received_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->orderByDesc('received_at')
            ->take(5)
            ->get()
            ->map(function ($fax) {
                return [
                    'id' => $fax->id,
                    'subject' => $fax->subject,
                    'status' => $fax->status,
                    'direction' => $fax->direction,
                    'received_at' => $fax->received_at?->toDateTimeString(),
                ];
            })
            ->values();

        $activeMedications = Medication::query()
            ->where('resident_id', $resident->id)
            ->where('is_active', true)
            ->count();

        $medicationAdministrations = MedicationAdministration::query()
            ->where('resident_id', $resident->id)
            ->whereBetween('administered_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->get();

        $missedMedicationCount = $medicationAdministrations->filter(fn ($item) => in_array($item->status, ['missed', 'refused', 'hospital_admission'], true))->count();

        return [
            'vitals' => [
                'count' => $vitals->count(),
                'critical_count' => $criticalCount,
                'warning_count' => $warningCount,
                'history' => $recentVitals,
                'latest' => $latestVital ? [
                    'temperature' => $latestVital->temperature,
                    'pulse' => $latestVital->pulse,
                    'oxygen_saturation' => $latestVital->oxygen_saturation,
                    'status' => $latestVital->status,
                ] : null,
            ],
            'sleep' => [
                'count' => $sleepRecords->count(),
                'average_hours' => $sleepRecords->avg('total_sleep_hours') ?: 0,
                'average_quality' => $sleepRecords->avg('sleep_quality') ?: 0,
            ],
            'behavior_charts' => [
                'count' => $behaviorCharts->count(),
                'provider_notified_count' => $providerNotifiedCount,
                'log_count' => $behaviorCharts->sum(fn ($chart) => $chart->logs->count()),
            ],
            'appointments' => [
                'upcoming_count' => $upcomingAppointments,
            ],
            'faxes' => [
                'count' => $recentFaxes->count(),
                'messages' => $recentFaxes,
            ],
            'medications' => [
                'active_count' => $activeMedications,
                'missed_count' => $missedMedicationCount,
                'administration_count' => $medicationAdministrations->count(),
            ],
        ];
    }

    private function buildFollowUpReasonsFromMetrics(array $metrics): array
    {
        $reasons = [];

        $critical = (int) ($metrics['vitals']['critical_count'] ?? 0);
        $warnings = (int) ($metrics['vitals']['warning_count'] ?? 0);
        $missedMeds = (int) ($metrics['medications']['missed_count'] ?? 0);
        $providerAlerts = (int) ($metrics['behavior_charts']['provider_notified_count'] ?? 0);
        $appointments = (int) ($metrics['appointments']['upcoming_count'] ?? 0);

        if ($critical > 0) {
            $reasons[] = "{$critical} critical vital reading(s)";
        }
        if ($warnings > 0) {
            $reasons[] = "{$warnings} warning vital reading(s)";
        }
        if ($missedMeds > 0) {
            $reasons[] = "{$missedMeds} missed/refused medication administration(s)";
        }
        if ($providerAlerts > 0) {
            $reasons[] = "{$providerAlerts} provider-notified behavior event(s)";
        }
        if ($appointments > 0) {
            $reasons[] = "{$appointments} upcoming appointment(s) requiring prep";
        }

        return $reasons;
    }

    private function buildFacilityFollowUpCandidates(Request $request, Resident $selectedResident, int $days): array
    {
        $user = $request->user();
        if (! $user) {
            return [];
        }

        $facilityId = $selectedResident->branch?->facility_id;
        if (! $facilityId) {
            return [];
        }

        $startDate = Carbon::now()->subDays(max(1, $days))->toDateString();
        $endDate = Carbon::now()->toDateString();

        $residentQuery = Resident::query()
            ->whereHas('branch', function ($q) use ($facilityId) {
                $q->where('facility_id', $facilityId);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(25);

        $residents = $residentQuery->get();

        $candidates = $residents->map(function (Resident $resident) use ($startDate, $endDate) {
            $metrics = $this->buildPeriodMetrics($resident, $startDate, $endDate);
            $reasons = $this->buildFollowUpReasonsFromMetrics($metrics);

            $critical = (int) ($metrics['vitals']['critical_count'] ?? 0);
            $warnings = (int) ($metrics['vitals']['warning_count'] ?? 0);
            $missed = (int) ($metrics['medications']['missed_count'] ?? 0);
            $providerAlerts = (int) ($metrics['behavior_charts']['provider_notified_count'] ?? 0);
            $appointments = (int) ($metrics['appointments']['upcoming_count'] ?? 0);

            $riskScore = ($critical * 8) + ($warnings * 3) + ($missed * 4) + ($providerAlerts * 3) + $appointments;

            if (empty($reasons)) {
                $reasons[] = 'no urgent issues flagged; continue routine monitoring';
            }

            return [
                'id' => $resident->id,
                'name' => trim(($resident->first_name ?? '').' '.($resident->last_name ?? '')) ?: 'Resident',
                'risk_score' => $riskScore,
                'reasons' => $reasons,
            ];
        })
            ->sortByDesc('risk_score')
            ->take(10)
            ->values()
            ->all();

        return $candidates;
    }

    private function buildPayload(Resident $resident, Request $request): array
    {
        $window = $request->get('window', 'last 14 days');
        $days = max(1, (int) $request->get('days', 14));
        $comparisonRequested = $this->isComparisonPrompt((string) $request->get('prompt', ''));
        $currentDays = $comparisonRequested ? min($days, 7) : $days;
        $currentStartDate = Carbon::now()->subDays($currentDays)->toDateString();
        $currentEndDate = Carbon::now()->toDateString();
        $comparisonStartDate = Carbon::now()->subDays($currentDays * 2)->toDateString();
        $comparisonEndDate = Carbon::now()->subDays($currentDays + 1)->toDateString();

        $currentPeriod = $this->buildPeriodMetrics($resident, $currentStartDate, $currentEndDate);
        $comparisonPeriod = $this->buildPeriodMetrics($resident, $comparisonStartDate, $comparisonEndDate);
        $followUpCandidates = $this->buildFacilityFollowUpCandidates($request, $resident, $currentDays);

        return [
            'resident' => [
                'id' => $resident->id,
                'name' => trim(($resident->first_name ?? '').' '.($resident->last_name ?? '')) ?: 'Resident',
            ],
            'window' => $comparisonRequested ? 'comparison requested' : $window,
            ...$currentPeriod,
            'comparison' => $comparisonPeriod,
            'facility_follow_up_candidates' => $followUpCandidates,
        ];
    }
}
