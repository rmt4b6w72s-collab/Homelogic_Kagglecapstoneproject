<?php

namespace App\Http\Controllers;

use App\Models\ChartMessage;
use App\Models\Resident;
use App\Services\ChartAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChartAssistantController extends Controller
{
    public function __construct(private ChartAssistantService $assistantService)
    {
    }

    public function summarize(Request $request, Resident $resident): JsonResponse
    {
        $result = $this->assistantService->analyze($this->payloadFromRequest($request, $resident), (string) $request->input('prompt', 'Summarize chart trends and next actions.'));
        $this->storeAssistantMessage($request, $resident, $result['summary'] ?? 'Summary generated.', 'summary');

        return response()->json([
            'resident_id' => $resident->id,
            'summary' => $result['summary'] ?? null,
            'insights' => $result['insights'] ?? [],
            'recommendations' => $result['recommendations'] ?? [],
            'mode' => $result['mode'] ?? 'heuristic',
        ]);
    }

    public function extractCarePlan(Request $request, Resident $resident): JsonResponse
    {
        $result = $this->assistantService->analyze($this->payloadFromRequest($request, $resident), (string) $request->input('prompt', 'Extract concise care-plan priorities.'));
        $carePlan = collect($result['recommendations'] ?? [])->map(fn ($line) => ['item' => $line, 'status' => 'recommended'])->values();

        return response()->json([
            'resident_id' => $resident->id,
            'care_plan' => $carePlan,
            'source_summary' => $result['summary'] ?? null,
        ]);
    }

    public function generateProgressNote(Request $request, Resident $resident): JsonResponse
    {
        $result = $this->assistantService->analyze($this->payloadFromRequest($request, $resident), (string) $request->input('prompt', 'Generate a concise progress note.'));
        $insightLines = collect($result['insights'] ?? [])->take(3)->map(fn ($line) => '- '.$line)->implode("\n");
        $note = trim((string) ($result['summary'] ?? ''));
        if ($insightLines !== '') {
            $note .= "\n\nKey observations:\n".$insightLines;
        }

        $this->storeAssistantMessage($request, $resident, $note, 'progress_note');

        return response()->json([
            'resident_id' => $resident->id,
            'progress_note' => $note,
            'assistant' => [
                'insights' => $result['insights'] ?? [],
                'recommendations' => $result['recommendations'] ?? [],
            ],
        ]);
    }

    public function chat(Request $request, Resident $resident): JsonResponse
    {
        $message = trim((string) $request->input('message', ''));
        if ($message === '') {
            return response()->json(['message' => 'message is required'], 422);
        }

        $this->storeUserMessage($request, $resident, $message, 'question');

        $result = $this->assistantService->analyze($this->payloadFromRequest($request, $resident), $message);
        $reply = (string) ($result['summary'] ?? 'No response available.');

        $this->storeAssistantMessage($request, $resident, $reply, 'analysis');

        return response()->json([
            'resident_id' => $resident->id,
            'reply' => $reply,
            'assistant' => $result,
        ]);
    }

    public function chatHistory(Request $request, Resident $resident): JsonResponse
    {
        $messages = ChartMessage::query()
            ->where('resident_id', $resident->id)
            ->latest('id')
            ->limit((int) $request->input('limit', 50))
            ->get()
            ->reverse()
            ->values()
            ->map(fn (ChartMessage $message) => [
                'id' => $message->id,
                'role' => $message->role,
                'type' => $message->type,
                'content' => $message->content,
                'created_at' => $message->created_at?->toDateTimeString(),
            ]);

        return response()->json([
            'resident_id' => $resident->id,
            'messages' => $messages,
        ]);
    }

    public function analyzeRisks(Request $request, Resident $resident): JsonResponse
    {
        $result = $this->assistantService->analyze($this->payloadFromRequest($request, $resident), (string) $request->input('prompt', 'Identify key risks and escalation triggers.'));
        $text = strtolower(implode(' ', array_merge($result['insights'] ?? [], $result['recommendations'] ?? [])));
        $riskFlags = [
            'critical_vitals' => str_contains($text, 'critical'),
            'medication_adherence' => str_contains($text, 'medication') || str_contains($text, 'missed'),
            'sleep_concern' => str_contains($text, 'sleep'),
            'behavioral_concern' => str_contains($text, 'behavior'),
        ];

        return response()->json([
            'resident_id' => $resident->id,
            'risks' => $riskFlags,
            'assistant' => $result,
        ]);
    }

    private function payloadFromRequest(Request $request, Resident $resident): array
    {
        return [
            'resident' => [
                'id' => $resident->id,
                'name' => trim(($resident->first_name ?? '').' '.($resident->last_name ?? '')) ?: 'Resident',
            ],
            'window' => $request->input('window', 'last 14 days'),
            'vitals' => $request->input('vitals', []),
            'sleep' => $request->input('sleep', []),
            'behavior_charts' => $request->input('behavior_charts', []),
            'appointments' => $request->input('appointments', []),
            'medications' => $request->input('medications', []),
            'faxes' => $request->input('faxes', []),
        ];
    }

    private function storeUserMessage(Request $request, Resident $resident, string $content, string $type): void
    {
        $this->storeMessage($request, $resident, 'user', $content, $type);
    }

    private function storeAssistantMessage(Request $request, Resident $resident, string $content, string $type): void
    {
        $this->storeMessage($request, $resident, 'assistant', $content, $type);
    }

    private function storeMessage(Request $request, Resident $resident, string $role, string $content, string $type): void
    {
        $facilityId = $request->user()?->facility_id ?? $resident->facility_id;
        if (empty($facilityId)) {
            return;
        }

        ChartMessage::query()->create([
            'resident_id' => $resident->id,
            'facility_id' => $facilityId,
            'user_id' => $request->user()?->id,
            'role' => $role,
            'content' => $content,
            'type' => $type,
        ]);
    }
}