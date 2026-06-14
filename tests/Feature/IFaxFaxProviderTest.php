<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Fax;
use App\Models\FaxNumber;
use App\Models\FaxSetting;
use App\Services\Fax\Providers\IFaxFaxProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IFaxFaxProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'fax.ifax.webhook_secret' => 'test-ifax-webhook-secret',
            'fax.disk' => 'local',
        ]);
    }

    public function test_test_connection_succeeds_when_ifax_api_returns_numbers(): void
    {
        Http::fake([
            'https://api.ifaxapp.com/v1/customer/inbound/number-list' => Http::response([
                'status' => 1,
                'message' => 'Numbers list retrieved successfully',
                'data' => [],
            ]),
        ]);

        $provider = new IFaxFaxProvider(['api_key' => 'live-key']);
        $result = $provider->testConnection();

        $this->assertTrue($result->ok);
        $this->assertSame('iFax API reachable.', $result->message);
    }

    public function test_send_fax_returns_job_id_from_ifax_response(): void
    {
        Storage::fake('local');
        $path = storage_path('app/faxes/test.pdf');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, '%PDF-1.4');

        Http::fake([
            'https://api.ifaxapp.com/v1/customer/fax-send' => Http::response([
                'status' => 1,
                'message' => 'Fax processed for sending',
                'data' => ['jobId' => 98765],
            ]),
        ]);

        $provider = new IFaxFaxProvider(['api_key' => 'live-key']);
        $result = $provider->send(new \App\Services\Fax\Support\SendFaxRequest(
            fromNumber: '+12065550100',
            toNumber: '+12065550999',
            filePath: $path,
            subject: 'Refill',
        ));

        $this->assertTrue($result->ok);
        $this->assertSame('98765', $result->providerFaxId);
        $this->assertSame('queued', $result->status);
    }

    public function test_status_webhook_updates_matching_outbound_fax(): void
    {
        Event::fake();

        $facility = Facility::factory()->create();
        $settings = new FaxSetting([
            'facility_id' => $facility->id,
            'provider' => 'ifax',
            'credentials' => ['api_key' => 'live-key'],
            'is_active' => true,
        ]);
        $settings->facility_id = $facility->id;
        $settings->webhook_secret = FaxSetting::generateWebhookSecret();
        $settings->save();

        $fax = Fax::create([
            'facility_id' => $facility->id,
            'direction' => Fax::DIRECTION_OUTBOUND,
            'provider' => 'ifax',
            'provider_fax_id' => '12345',
            'from_number' => '+12065550100',
            'to_number' => '+12065550999',
            'status' => Fax::STATUS_QUEUED,
        ]);

        $response = $this->postJson('/api/webhooks/fax/ifax/test-ifax-webhook-secret', [
            'jobId' => 12345,
            'faxStatus' => 'delivered',
            'direction' => 'sent',
            'message' => 'NORMAL_CLEARING',
        ]);

        $response->assertOk();
        $this->assertSame(Fax::STATUS_DELIVERED, $fax->refresh()->status);
    }

    public function test_inbound_webhook_routes_to_facility_by_destination_number(): void
    {
        Event::fake();
        Storage::fake('local');

        $facility = Facility::factory()->create();
        $settings = new FaxSetting([
            'facility_id' => $facility->id,
            'provider' => 'ifax',
            'credentials' => ['api_key' => 'live-key'],
            'is_active' => true,
        ]);
        $settings->facility_id = $facility->id;
        $settings->webhook_secret = FaxSetting::generateWebhookSecret();
        $settings->save();
        FaxNumber::create([
            'facility_id' => $facility->id,
            'provider' => 'ifax',
            'provider_number_id' => '200989|1595589515890656281',
            'e164_number' => '+12065550100',
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->post('/api/webhooks/fax/ifax/test-ifax-webhook-secret', [
            'direction' => 'inbound',
            'jobId' => 7067845,
            'transactionId' => 8660564,
            'fromNumber' => '+12065550999',
            'toNumber' => '+12065550100',
            'faxStatus' => 'received',
            'faxTotalPages' => 2,
            'filename' => UploadedFile::fake()->createWithContent('fax.pdf', '%PDF-1.4 inbound'),
        ]);

        $response->assertOk();

        $fax = Fax::withoutGlobalScopes()
            ->where('provider', 'ifax')
            ->where('provider_fax_id', '7067845')
            ->first();

        $this->assertNotNull($fax);
        $this->assertSame($facility->id, $fax->facility_id);
        $this->assertSame(Fax::DIRECTION_INBOUND, $fax->direction);
        $this->assertSame(2, $fax->page_count);
    }
}
