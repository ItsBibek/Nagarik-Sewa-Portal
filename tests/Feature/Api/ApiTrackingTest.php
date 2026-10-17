<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class ApiTrackingTest extends TestCase
{
    public function test_public_api_returns_status_and_safe_history_for_a_valid_tracking_id(): void
    {
        $this->seed(DatabaseSeeder::class);

        $citizen = User::factory()->create();
        $officer = User::query()->where('role', 'officer')->firstOrFail();
        $serviceType = ServiceType::query()->where('is_active', true)->firstOrFail();
        $application = Application::query()->create([
            'user_id' => $citizen->id,
            'service_type_id' => $serviceType->id,
            'status' => 'under_review',
        ]);
        $application->statusHistories()->createMany([
            [
                'old_status' => null,
                'new_status' => 'submitted',
                'changed_by' => $citizen->id,
            ],
            [
                'old_status' => 'submitted',
                'new_status' => 'under_review',
                'remark' => 'Internal officer note.',
                'changed_by' => $officer->id,
            ],
        ]);
        $application->documents()->create([
            'document_type' => 'citizenship_certificate',
            'file_path' => 'applications/private/citizenship.pdf',
            'file_type' => 'application/pdf',
        ]);

        $response = $this->getJson('/api/v1/track/'.$application->tracking_id)
            ->assertOk()
            ->assertJsonPath('message', 'Tracking information retrieved.')
            ->assertJsonPath('data.tracking_id', $application->tracking_id)
            ->assertJsonPath('data.status', 'under_review')
            ->assertJsonPath('data.service', $serviceType->name)
            ->assertJsonPath('data.ward_office', $serviceType->department->name)
            ->assertJsonPath('data.status_history.0.status', 'submitted')
            ->assertJsonPath('data.status_history.1.status', 'under_review');

        $this->assertStringNotContainsString('Internal officer note', $response->getContent());
        $this->assertStringNotContainsString('applications/private/citizenship.pdf', $response->getContent());
        $this->assertStringNotContainsString('"documents"', $response->getContent());
    }

    public function test_public_api_returns_404_for_a_fake_tracking_id(): void
    {
        $this->getJson('/api/v1/track/NSP-AAAAAAAAAAAA')
            ->assertNotFound()
            ->assertExactJson([
                'data' => null,
                'message' => 'The requested resource was not found.',
            ]);
    }
}
