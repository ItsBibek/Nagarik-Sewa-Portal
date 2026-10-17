<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationApiTest extends TestCase
{
    public function test_application_list_requires_a_token_and_returns_only_the_citizens_applications(): void
    {
        $this->seed(DatabaseSeeder::class);
        $citizen = User::factory()->create();
        $otherCitizen = User::factory()->create();
        $service = ServiceType::query()->where('is_active', true)->firstOrFail();
        $owned = $this->makeApplication($citizen, $service);
        $notOwned = $this->makeApplication($otherCitizen, $service);

        $this->getJson('/api/v1/applications')
            ->assertUnauthorized()
            ->assertExactJson([
                'data' => null,
                'message' => 'Unauthenticated.',
            ]);

        $this->withToken($citizen->createToken('api-test')->plainTextToken)
            ->getJson('/api/v1/applications')
            ->assertOk()
            ->assertJsonPath('message', 'Applications retrieved.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tracking_id', $owned->tracking_id)
            ->assertJsonMissing(['tracking_id' => $notOwned->tracking_id])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'tracking_id',
                        'status',
                        'submitted_at',
                        'service' => ['id', 'name', 'department', 'fee_npr'],
                        'documents',
                        'status_history',
                    ],
                ],
                'links',
                'meta',
                'message',
            ]);
    }

    public function test_citizen_can_submit_documents_and_receives_a_safe_created_resource(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $citizen = User::factory()->create();
        $service = ServiceType::query()->where('is_active', true)->firstOrFail();

        $response = $this->withToken($citizen->createToken('mobile')->plainTextToken)
            ->postJson('/api/v1/applications', [
                'service_type_id' => $service->id,
                'documents' => [
                    UploadedFile::fake()->create('citizenship.pdf', 100, 'application/pdf'),
                    UploadedFile::fake()->create('residence.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Application submitted.')
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.service.fee_npr', 500)
            ->assertJsonCount(2, 'data.documents')
            ->assertJsonCount(1, 'data.status_history');

        $application = Application::query()
            ->where('tracking_id', $response->json('data.tracking_id'))
            ->firstOrFail();

        $this->assertSame($citizen->id, $application->user_id);
        $this->assertDatabaseCount('application_documents', 2);
        $this->assertDatabaseCount('status_histories', 1);

        foreach ($application->documents as $document) {
            Storage::disk('local')->assertExists($document->file_path);
        }

        $this->assertStringNotContainsString('file_path', $response->getContent());
        $this->assertStringNotContainsString('current_officer_id', $response->getContent());
    }

    public function test_submission_validation_returns_consistent_422_errors_and_creates_nothing(): void
    {
        $this->seed(DatabaseSeeder::class);
        $citizen = User::factory()->create();
        $service = ServiceType::query()->where('is_active', true)->firstOrFail();

        $this->withToken($citizen->createToken('mobile')->plainTextToken)
            ->postJson('/api/v1/applications', [
                'service_type_id' => $service->id,
                'documents' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonStructure(['data' => ['errors' => ['documents.0']]]);

        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('application_documents', 0);
    }

    public function test_officer_can_advance_a_status_but_cannot_read_another_department_application(): void
    {
        $this->seed(DatabaseSeeder::class);
        $officer = User::query()->where('role', 'officer')->firstOrFail();
        $service = ServiceType::query()->where('is_active', true)->firstOrFail();
        $application = $this->makeApplication(User::factory()->create(), $service);

        $this->withToken($officer->createToken('ward-staff')->plainTextToken)
            ->patchJson('/api/v1/applications/'.$application->id.'/status', [
                'status' => 'under_review',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'under_review')
            ->assertJsonPath('message', 'Application status updated.');

        $this->assertDatabaseHas('status_histories', [
            'application_id' => $application->id,
            'old_status' => 'submitted',
            'new_status' => 'under_review',
            'changed_by' => $officer->id,
        ]);

        $otherDepartment = Department::query()->create([
            'name' => 'Different department',
            'code' => 'OTHER',
        ]);
        $otherService = $otherDepartment->serviceTypes()->create([
            'name' => 'Different service',
            'required_documents' => ['identity_document'],
            'fee' => 0,
            'processing_days' => 2,
            'is_active' => true,
        ]);
        $otherApplication = $this->makeApplication(User::factory()->create(), $otherService);

        $this->withToken($officer->createToken('ward-staff')->plainTextToken)
            ->getJson('/api/v1/applications/'.$otherApplication->id)
            ->assertForbidden()
            ->assertExactJson([
                'data' => null,
                'message' => 'You are not authorized to perform this action.',
            ]);
    }

    public function test_status_validation_and_forbidden_roles_return_standard_json_errors(): void
    {
        $this->seed(DatabaseSeeder::class);
        $citizen = User::factory()->create();
        $service = ServiceType::query()->where('is_active', true)->firstOrFail();
        $application = $this->makeApplication(User::factory()->create(), $service);

        $this->withToken($citizen->createToken('mobile')->plainTextToken)
            ->patchJson('/api/v1/applications/'.$application->id.'/status', [
                'status' => 'under_review',
            ])
            ->assertForbidden()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'You are not authorized to perform this action.');

        $officer = User::query()->where('role', 'officer')->firstOrFail();
        $this->assertTrue($officer->can('update', $application));

        Auth::forgetGuards();

        $response = $this->withToken($officer->createToken('ward-staff')->plainTextToken)
            ->patchJson('/api/v1/applications/'.$application->id.'/status', [
                'status' => 'correction_required',
            ]);
        $response->assertUnprocessable()
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonStructure(['data' => ['errors' => ['remark']]]);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'submitted',
        ]);
    }

    public function test_api_application_detail_returns_404_for_a_missing_application(): void
    {
        $this->seed(DatabaseSeeder::class);
        $citizen = User::factory()->create();

        $this->withToken($citizen->createToken('mobile')->plainTextToken)
            ->getJson('/api/v1/applications/999999')
            ->assertNotFound()
            ->assertExactJson([
                'data' => null,
                'message' => 'The requested resource was not found.',
            ]);
    }

    private function makeApplication(
        User $citizen,
        ServiceType $service,
        string $status = 'submitted',
    ): Application {
        $application = Application::query()->create([
            'user_id' => $citizen->id,
            'service_type_id' => $service->id,
            'status' => $status,
        ]);
        $application->statusHistories()->create([
            'old_status' => null,
            'new_status' => $status,
            'changed_by' => $citizen->id,
        ]);

        return $application;
    }
}
