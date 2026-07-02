<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ChartAssistantConversation;
use App\Models\Facility;
use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SetupFacility;

class ChartAssistantAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use SetupFacility;

    private Facility $otherFacility;

    private Branch $otherBranch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createFacilityAndBranch();

        $this->otherFacility = Facility::factory()->create(['name' => 'Foreign Facility']);
        $this->otherBranch = Branch::factory()->create([
            'facility_id' => $this->otherFacility->id,
            'name' => 'Foreign Branch',
        ]);
    }

    public function test_cannot_access_chart_assistant_for_resident_in_other_facility(): void
    {
        $this->createAndActAs('administrator');

        $foreignResident = Resident::withoutGlobalScopes()->create([
            'name' => 'Foreign Resident',
            'first_name' => 'Foreign',
            'last_name' => 'Resident',
            'branch_id' => $this->otherBranch->id,
            'date_of_birth' => '1960-05-20',
            'gender' => 'female',
            'admission_date' => '2024-06-01',
            'is_active' => true,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/charts/assistant/'.$foreignResident->id);

        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_cannot_access_or_post_to_conversation_from_other_facility(): void
    {
        $this->createAndActAs('administrator');

        $foreignResident = Resident::withoutGlobalScopes()->create([
            'name' => 'Foreign Resident',
            'first_name' => 'Foreign',
            'last_name' => 'Resident',
            'branch_id' => $this->otherBranch->id,
            'date_of_birth' => '1960-05-20',
            'gender' => 'female',
            'admission_date' => '2024-06-01',
            'is_active' => true,
            'status' => 'active',
        ]);

        $conversation = ChartAssistantConversation::create([
            'resident_id' => $foreignResident->id,
            'title' => 'Foreign chart review',
            'status' => 'active',
            'context' => ['window' => 'last 14 days'],
            'messages' => [['role' => 'system', 'content' => 'start']],
        ]);

        $show = $this->getJson('/api/v1/charts/assistant/conversations/'.$conversation->id);
        $this->assertContains($show->status(), [403, 404]);

        $send = $this->postJson('/api/v1/charts/assistant/conversations/'.$conversation->id.'/messages', [
            'message' => 'Any risks?',
        ]);
        $this->assertContains($send->status(), [403, 404]);
    }

    public function test_can_access_conversation_for_own_facility_resident(): void
    {
        $this->createAndActAs('administrator');

        $resident = $this->createResident($this->branch);

        $conversation = ChartAssistantConversation::create([
            'resident_id' => $resident->id,
            'title' => 'Own chart review',
            'status' => 'active',
            'context' => ['window' => 'last 14 days'],
            'messages' => [['role' => 'system', 'content' => 'start']],
        ]);

        $this->getJson('/api/v1/charts/assistant/conversations/'.$conversation->id)
            ->assertOk()
            ->assertJsonPath('conversation.id', $conversation->id);
    }
}
