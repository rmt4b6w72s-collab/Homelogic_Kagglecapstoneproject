<?php

namespace App\Services\Fax\Providers;

use App\Services\Fax\Contracts\FaxProvider;
use App\Services\Fax\Support\CredentialField;
use App\Services\Fax\Support\InboundFax;
use App\Services\Fax\Support\NumberInfo;
use App\Services\Fax\Support\SendFaxRequest;
use App\Services\Fax\Support\SendResult;
use App\Services\Fax\Support\TestResult;
use App\Services\Fax\Support\WebhookResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * iFax programmable fax driver.
 *
 * Docs: https://www.ifaxapp.com/docs/api/v1
 * HIPAA: BAA available on Pro+ plans.
 *
 * One API key can be shared across all Evergreen facilities. Each facility
 * still owns its own fax number(s); outbound sends use callerId, and inbound
 * webhooks are routed centrally by the destination toNumber.
 *
 * iFax uses account-wide webhook URLs (not per number). Configure the shared
 * URL from Fax Settings in any facility — it is the same for all facilities.
 */
class IFaxFaxProvider implements FaxProvider
{
    private const BASE_URL = 'https://api.ifaxapp.com/v1';

    public function __construct(private array $credentials) {}

    public static function key(): string
    {
        return 'ifax';
    }

    public static function displayName(): string
    {
        return 'iFax';
    }

    public static function description(): ?string
    {
        return 'HIPAA-compliant cloud fax with REST API. One API key can serve all facilities.';
    }

    public static function credentialSchema(): array
    {
        return [
            new CredentialField(
                name: 'api_key',
                label: 'API Key',
                type: CredentialField::TYPE_SECRET,
                help: 'Found in iFax Dashboard → Settings → Developer API. The same key can be used for every facility.',
                placeholder: 'Live or sandbox key',
            ),
        ];
    }

    public static function sharedWebhookUrl(): ?string
    {
        $secret = (string) config('fax.ifax.webhook_secret', '');

        return $secret !== ''
            ? url('/api/webhooks/fax/'.self::key().'/'.$secret)
            : null;
    }

    public static function usesSharedWebhook(): bool
    {
        return true;
    }

    private function http(): PendingRequest
    {
        $apiKey = $this->credentials['api_key'] ?? '';
        if (! $apiKey) {
            throw new RuntimeException('iFax API key missing.');
        }

        return Http::withHeaders([
            'accessToken' => $apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout(30)
            ->baseUrl(self::BASE_URL);
    }

    public function testConnection(): TestResult
    {
        try {
            $response = $this->http()->post('/customer/inbound/number-list', []);

            $body = $response->json() ?? [];
            if ($response->successful() && $this->isSuccessful($body)) {
                return TestResult::ok('iFax API reachable.', [
                    'numbers' => count($body['data'] ?? []),
                ]);
            }

            return TestResult::fail(
                'HTTP '.$response->status().' from iFax: '.($body['message'] ?? $response->body()),
            );
        } catch (\Throwable $e) {
            return TestResult::fail('Unable to reach iFax: '.$e->getMessage());
        }
    }

    public function send(SendFaxRequest $request): SendResult
    {
        if (! is_file($request->filePath)) {
            return SendResult::fail("Source file not found: {$request->filePath}");
        }

        $payload = array_filter([
            'faxNumber' => $this->normalizeE164($request->toNumber),
            'callerId' => $this->normalizeE164($request->fromNumber),
            'subject' => $request->subject,
            'from_name' => $request->options['from_name'] ?? null,
            'to_name' => $request->options['to_name'] ?? null,
            'message' => $request->options['message'] ?? null,
            'faxQuality' => $request->options['quality'] ?? 'Standard',
            'faxData' => [[
                'fileName' => basename($request->filePath),
                'fileData' => base64_encode((string) file_get_contents($request->filePath)),
            ]],
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = $this->http()->post('/customer/fax-send', $payload);
        } catch (\Throwable $e) {
            return SendResult::fail('iFax send failed: '.$e->getMessage());
        }

        $body = $response->json() ?? [];
        if ($response->failed() || ! $this->isSuccessful($body)) {
            return SendResult::fail(
                'iFax rejected fax: '.($body['message'] ?? $response->body()),
                $body,
            );
        }

        $jobId = (string) ($body['data']['jobId'] ?? '');

        return SendResult::ok(
            providerFaxId: $jobId,
            status: 'queued',
            raw: $body,
        );
    }

    public function searchAvailableNumbers(?string $areaCode = null, string $country = 'US', int $limit = 10): array
    {
        if (! $areaCode) {
            return [];
        }

        try {
            $areasResponse = $this->http()->post('/customer/inbound/area-list', [
                'abbreviation' => $country === 'US' ? 'US' : $country,
            ]);
        } catch (\Throwable $e) {
            return [];
        }

        $areasBody = $areasResponse->json() ?? [];
        if ($areasResponse->failed() || ! $this->isSuccessful($areasBody)) {
            return [];
        }

        $areaId = collect($areasBody['data'] ?? [])
            ->first(fn ($area) => (string) ($area['prefix'] ?? '') === (string) $areaCode)['id'] ?? null;

        if (! $areaId) {
            return [];
        }

        try {
            $numbersResponse = $this->http()->post('/customer/inbound/numbers', [
                'id' => (string) $areaId,
            ]);
        } catch (\Throwable $e) {
            return [];
        }

        $numbersBody = $numbersResponse->json() ?? [];
        if ($numbersResponse->failed() || ! $this->isSuccessful($numbersBody)) {
            return [];
        }

        return collect($numbersBody['data'] ?? [])
            ->take($limit)
            ->map(fn ($number) => new NumberInfo(
                e164Number: $this->normalizeE164(is_string($number) ? $number : ($number['faxNumber'] ?? '')),
                providerNumberId: null,
                country: $country,
                region: $areaCode,
                raw: is_array($number) ? $number : ['faxNumber' => $number],
            ))
            ->filter(fn (NumberInfo $info) => $info->e164Number !== '')
            ->values()
            ->all();
    }

    public function purchaseNumber(string $e164Number): NumberInfo
    {
        $normalized = $this->normalizeE164($e164Number);

        $search = $this->http()->post('/customer/inbound/search', [
            'faxNumber' => $normalized,
        ]);
        $searchBody = $search->json() ?? [];
        if ($search->failed() || ! $this->isSuccessful($searchBody)) {
            throw new RuntimeException('iFax number search failed: '.($searchBody['message'] ?? $search->body()));
        }

        $response = $this->http()->post('/customer/inbound/buy', [
            'faxNumber' => $normalized,
            'addLicense' => false,
        ]);

        $body = $response->json() ?? [];
        if ($response->failed() || ! $this->isSuccessful($body)) {
            throw new RuntimeException('iFax purchase failed: '.($body['message'] ?? $response->body()));
        }

        $numberId = (string) ($body['data']['numberId'] ?? '');
        $orderId = (string) ($body['data']['orderId'] ?? '');

        return new NumberInfo(
            e164Number: $normalized,
            providerNumberId: $numberId !== '' && $orderId !== '' ? "{$numberId}|{$orderId}" : $numberId,
            country: 'US',
            raw: $body['data'] ?? [],
        );
    }

    public function releaseNumber(string $providerNumberId): bool
    {
        [$numberId, $orderId] = $this->parseProviderNumberId($providerNumberId);
        if (! $numberId || ! $orderId) {
            return false;
        }

        try {
            $response = $this->http()->post('/customer/inbound/number-delete', [
                'numberId' => $numberId,
                'orderId' => $orderId,
            ]);

            $body = $response->json() ?? [];

            return $response->successful() && $this->isSuccessful($body);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function verifyWebhookSignature(Request $request): void
    {
        // iFax does not sign webhooks. The shared secret is enforced in the URL
        // path by IFaxFaxWebhookController before this provider is invoked.
    }

    public function parseWebhook(Request $request): WebhookResult
    {
        if ($this->isInboundWebhook($request)) {
            return $this->parseInboundWebhook($request);
        }

        $payload = $request->json()->all();
        if ($payload === []) {
            $payload = $request->all();
        }

        if ($payload === []) {
            return WebhookResult::ignore();
        }

        return $this->parseStatusWebhook($payload);
    }

    public function downloadInboundMedia(InboundFax $inbound): string
    {
        if ($inbound->mediaBytes !== null && $inbound->mediaBytes !== '') {
            return $inbound->mediaBytes;
        }

        if (! $inbound->mediaUrl) {
            return '';
        }

        $response = $this->http()->get($inbound->mediaUrl);

        if ($response->failed()) {
            throw new RuntimeException('Failed to download iFax media: HTTP '.$response->status());
        }

        return $response->body();
    }

    private function isInboundWebhook(Request $request): bool
    {
        if ($request->hasFile('filename')) {
            return true;
        }

        $direction = strtolower((string) $request->input('direction', ''));

        return in_array($direction, ['inbound', 'ib'], true)
            || strtolower((string) $request->input('faxStatus', '')) === 'received';
    }

    private function parseInboundWebhook(Request $request): WebhookResult
    {
        $jobId = (string) ($request->input('jobId') ?? $request->input('transactionId') ?? '');
        $fromNumber = $this->normalizeE164($request->input('fromNumber') ?? $request->input('sender') ?? '');
        $toNumber = $this->normalizeE164($request->input('toNumber') ?? $request->input('receiver') ?? '');

        if ($jobId === '' || $toNumber === '') {
            return WebhookResult::ignore($request->all());
        }

        $mediaBytes = null;
        if ($request->hasFile('filename')) {
            $mediaBytes = (string) file_get_contents($request->file('filename')->getRealPath());
        }

        $pageCount = $request->input('faxTotalPages')
            ?? $request->input('faxReceivedPages')
            ?? $request->input('pages');

        $receivedAt = null;
        $receivedTimestamp = $request->input('faxCallEnd') ?? $request->input('receivedTime');
        if ($receivedTimestamp) {
            $receivedAt = is_numeric($receivedTimestamp)
                ? (new \DateTimeImmutable)->setTimestamp((int) $receivedTimestamp)
                : new \DateTimeImmutable((string) $receivedTimestamp);
        }

        return WebhookResult::inbound(
            new InboundFax(
                providerFaxId: $jobId,
                fromNumber: $fromNumber,
                toNumber: $toNumber,
                mediaBytes: $mediaBytes,
                pageCount: $pageCount !== null ? (int) $pageCount : null,
                receivedAt: $receivedAt,
                raw: $this->sanitizeWebhookPayload($request->except(['filename'])),
            ),
            eventId: $request->input('transactionId') ? (string) $request->input('transactionId') : null,
            raw: $this->sanitizeWebhookPayload($request->except(['filename'])),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizeWebhookPayload(array $payload): array
    {
        return collect($payload)
            ->map(function (mixed $value): mixed {
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    return [
                        'original_name' => $value->getClientOriginalName(),
                        'size' => $value->getSize(),
                    ];
                }

                return $value;
            })
            ->all();
    }

    private function parseStatusWebhook(array $payload): WebhookResult
    {
        $data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;
        $jobId = (string) ($data['jobId'] ?? $payload['jobId'] ?? '');
        $faxStatus = strtolower((string) ($data['faxStatus'] ?? $payload['faxStatus'] ?? ''));
        $direction = strtolower((string) ($data['direction'] ?? $payload['direction'] ?? ''));

        if ($jobId === '') {
            return WebhookResult::ignore($payload);
        }

        if (in_array($direction, ['inbound', 'ib'], true)) {
            return WebhookResult::ignore($payload);
        }

        $statusMap = [
            'sending' => 'sending',
            'delivered' => 'delivered',
            'failed' => 'failed',
            'canceled' => 'failed',
            'cancelled' => 'failed',
        ];

        if (! isset($statusMap[$faxStatus])) {
            return WebhookResult::ignore($payload);
        }

        return WebhookResult::status(
            providerFaxId: $jobId,
            newStatus: $statusMap[$faxStatus],
            reason: $data['message'] ?? $payload['message'] ?? null,
            eventId: isset($data['transactionId']) ? (string) $data['transactionId'] : null,
            raw: $payload,
        );
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function parseProviderNumberId(string $providerNumberId): array
    {
        if (str_contains($providerNumberId, '|')) {
            [$numberId, $orderId] = explode('|', $providerNumberId, 2);

            return [$numberId ?: null, $orderId ?: null];
        }

        return [$providerNumberId ?: null, null];
    }

    private function isSuccessful(array $body): bool
    {
        return (int) ($body['status'] ?? 0) === 1;
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
