<?php

namespace App\Http\Controllers\Webhook;

use App\Events\FaxReceived;
use App\Events\FaxStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Fax;
use App\Models\FaxEvent;
use App\Models\FaxNumber;
use App\Models\FaxSetting;
use App\Services\Fax\FaxManager;
use App\Services\Fax\ProviderRegistry;
use App\Services\Fax\Providers\IFaxFaxProvider;
use App\Services\Fax\Support\WebhookResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Account-wide iFax webhook receiver.
 *
 * iFax only allows one callback URL per account, so this endpoint is shared
 * across all facilities. Inbound events are routed by the destination
 * toNumber; outbound status events are routed by provider_fax_id (jobId).
 */
class IFaxFaxWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret): Response
    {
        $expectedSecret = (string) config('fax.ifax.webhook_secret', '');
        if ($expectedSecret === '' || ! hash_equals($expectedSecret, $secret)) {
            return response()->json(['error' => 'Not found.'], 404);
        }

        $registry = app(ProviderRegistry::class);
        if (! $registry->has(IFaxFaxProvider::key())) {
            return response()->json(['error' => 'Provider not registered.'], 410);
        }

        $provider = $registry->make(IFaxFaxProvider::key(), []);

        try {
            $provider->verifyWebhookSignature($request);
        } catch (Throwable $e) {
            Log::warning('iFax webhook signature rejected', ['reason' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        try {
            $result = $provider->parseWebhook($request);
        } catch (Throwable $e) {
            Log::error('iFax webhook parse failed', ['reason' => $e->getMessage()]);

            return response()->json(['error' => 'Could not parse webhook payload.'], 422);
        }

        if ($result->kind === WebhookResult::KIND_IGNORE) {
            return response()->noContent();
        }

        if ($result->providerEventId) {
            $seen = FaxEvent::withoutGlobalScopes()
                ->where('provider_event_id', $result->providerEventId)
                ->exists();
            if ($seen) {
                return response()->noContent();
            }
        }

        $manager = app(FaxManager::class);

        try {
            if ($result->kind === WebhookResult::KIND_STATUS) {
                return $this->handleStatusWebhook($manager, $result);
            }

            if ($result->kind === WebhookResult::KIND_INBOUND && $result->inbound) {
                return $this->handleInboundWebhook($manager, $result);
            }
        } catch (Throwable $e) {
            Log::error('iFax webhook handling failed', ['reason' => $e->getMessage()]);

            return response()->json(['error' => 'Webhook processing failed.'], 500);
        }

        return response()->noContent();
    }

    private function handleStatusWebhook(FaxManager $manager, WebhookResult $result): Response
    {
        $fax = Fax::withoutGlobalScopes()
            ->where('provider', IFaxFaxProvider::key())
            ->where('provider_fax_id', $result->providerFaxId)
            ->first();

        if (! $fax) {
            Log::info('iFax status webhook for unknown provider_fax_id', [
                'provider_fax_id' => $result->providerFaxId,
            ]);

            return response()->noContent();
        }

        $manager->applyStatus(
            $fax,
            $result->newStatus ?: $fax->status,
            $result->statusReason,
            $result->providerEventId,
            $result->raw,
        );

        broadcast(new FaxStatusUpdated($fax->refresh()));

        return response()->json(['ok' => true]);
    }

    private function handleInboundWebhook(FaxManager $manager, WebhookResult $result): Response
    {
        $inbound = $result->inbound;
        $faxNumber = $this->findFaxNumberByE164($inbound->toNumber);

        if (! $faxNumber) {
            Log::warning('iFax inbound webhook for unknown toNumber', [
                'to_number' => $inbound->toNumber,
            ]);

            return response()->json(['error' => 'Unknown destination number.'], 422);
        }

        $facility = Facility::find($faxNumber->facility_id);
        if (! $facility) {
            return response()->json(['error' => 'Facility missing.'], 404);
        }

        $settings = FaxSetting::withoutGlobalScopes()
            ->where('facility_id', $facility->id)
            ->where('provider', IFaxFaxProvider::key())
            ->first();

        if (! $settings || ! $settings->isConfigured()) {
            return response()->json(['error' => 'Facility fax settings missing.'], 422);
        }

        app()->instance('facility', $facility);

        $fax = $manager->recordInbound($facility, $settings, $inbound);
        broadcast(new FaxReceived($fax));

        return response()->json(['ok' => true]);
    }

    private function findFaxNumberByE164(string $e164Number): ?FaxNumber
    {
        $normalized = $this->normalizeE164($e164Number);
        if ($normalized === '') {
            return null;
        }

        $candidates = FaxNumber::withoutGlobalScopes()
            ->where('provider', IFaxFaxProvider::key())
            ->where('is_active', true)
            ->get();

        foreach ($candidates as $number) {
            if ($this->normalizeE164($number->e164_number) === $normalized) {
                return $number;
            }
        }

        return null;
    }

    private function normalizeE164(?string $number): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+'.$digits;
        }

        return str_starts_with((string) $number, '+') ? (string) $number : '+'.$digits;
    }
}
