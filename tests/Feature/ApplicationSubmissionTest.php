<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationSubmissionTest extends TestCase
{
    public function test_service_catalog_groups_available_services_by_department(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('Kathmandu Metropolitan City')
            ->assertSee('Residential recommendation letter')
            ->assertDontSee('Citizenship Recommendation')
            ->assertDontSee('Visa Extension')
            ->assertSee('does not assert official', false);
    }

    public function test_registration_redirects_citizens_to_their_dashboard(): void
    {
        $this->post(route('register.store'), [
            'name' => 'New Citizen',
            'email' => 'new-citizen@example.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_registration_returns_a_citizen_to_their_dashboard_even_after_a_service_redirect(): void
    {
        $this->seed(DatabaseSeeder::class);
        $serviceType = ServiceType::query()->where('name', 'Residential Recommendation Letter')->firstOrFail();

        $this->get(route('applications.create', ['service_type_id' => $serviceType->id]))
            ->assertRedirect(route('login'));

        $this->post(route('register.store'), [
            'name' => 'Applicant Citizen',
            'email' => 'applicant-citizen@example.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Residential recommendation letter');
    }

    public function test_inactive_legacy_services_cannot_be_submitted(): void
    {
        $this->seed(DatabaseSeeder::class);
        $citizen = User::factory()->create();
        $department = Department::query()->firstOrFail();
        $legacyService = $department->serviceTypes()->create([
            'name' => 'Inactive legacy service',
            'required_documents' => ['identity_document'],
            'fee' => 0,
            'processing_days' => 2,
            'is_active' => false,
        ]);

        $this->actingAs($citizen)
            ->from(route('applications.create'))
            ->post(route('applications.store'), [
                'service_type_id' => $legacyService->id,
                'documents' => [UploadedFile::fake()->create('identity.pdf', 100, 'application/pdf')],
            ])
            ->assertRedirect(route('applications.create'))
            ->assertSessionHasErrors('service_type_id');

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_citizen_can_submit_required_documents_and_receive_a_tracking_id(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);

        $citizen = User::factory()->create();
        $serviceType = ServiceType::query()->where('name', 'Residential Recommendation Letter')->firstOrFail();

        $this->actingAs($citizen)
            ->get(route('applications.create', ['service_type_id' => $serviceType->id]))
            ->assertOk()
            ->assertSee('citizenship_certificate')
            ->assertSee('proof_of_residence')
            ->assertSee('documents[${index}]', false);

        $response = $this->actingAs($citizen)->post(route('applications.store'), [
            'service_type_id' => $serviceType->id,
            'user_id' => 9999,
            'current_officer_id' => 9999,
            'status' => 'approved',
            'documents' => [
                UploadedFile::fake()->create('citizenship-certificate.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('residence-proof.pdf', 100, 'application/pdf'),
            ],
        ]);

        $application = Application::query()->with(['documents', 'statusHistories'])->firstOrFail();

        $response->assertRedirect(route('applications.track', $application->tracking_id));
        $this->assertMatchesRegularExpression('/^NSP-[A-Z0-9]{12}$/', $application->tracking_id);
        $this->assertSame('submitted', $application->status);
        $this->assertSame($citizen->id, $application->user_id);
        $this->assertNull($application->current_officer_id);
        $this->assertCount(2, $application->documents);
        $this->assertCount(1, $application->statusHistories);
        $this->assertSame('submitted', $application->statusHistories->first()->new_status);
        $this->assertSame('citizenship_certificate', $application->documents->first()->document_type);

        foreach ($application->documents as $document) {
            Storage::disk('local')->assertExists($document->file_path);
        }
    }

    public function test_submission_requires_every_service_document_and_stores_nothing_on_validation_failure(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);

        $citizen = User::factory()->create();
        $serviceType = ServiceType::query()->where('name', 'Residential Recommendation Letter')->firstOrFail();

        $this->actingAs($citizen)->from(route('applications.create'))->post(route('applications.store'), [
            'service_type_id' => $serviceType->id,
            'documents' => [
                UploadedFile::fake()->create('citizenship-photo.pdf', 100, 'application/pdf'),
            ],
        ])->assertRedirect(route('applications.create'))
            ->assertSessionHasErrors('documents.1');

        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('application_documents', 0);
        $this->assertDatabaseCount('status_histories', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_submission_rejects_invalid_file_extensions_and_files_over_five_megabytes(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);

        $citizen = User::factory()->create();
        $serviceType = ServiceType::query()->where('name', 'Residential Recommendation Letter')->firstOrFail();

        $this->actingAs($citizen)->from(route('applications.create'))->post(route('applications.store'), [
            'service_type_id' => $serviceType->id,
            'documents' => [
                UploadedFile::fake()->create('malicious.php', 100, 'text/plain'),
                UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ],
        ])->assertRedirect(route('applications.create'))
            ->assertSessionHasErrors('documents.0');

        $this->actingAs($citizen)->from(route('applications.create'))->post(route('applications.store'), [
            'service_type_id' => $serviceType->id,
            'documents' => [
                UploadedFile::fake()->create('citizenship.pdf', 5121, 'application/pdf'),
                UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ],
        ])->assertRedirect(route('applications.create'))
            ->assertSessionHasErrors('documents.0');

        $this->assertDatabaseCount('applications', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_submission_returns_422_for_missing_required_fields_and_invalid_file_types(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);

        $citizen = User::factory()->create();
        $serviceType = ServiceType::query()->where('name', 'Residential Recommendation Letter')->firstOrFail();

        $this->actingAs($citizen)
            ->postJson(route('applications.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_type_id', 'documents']);

        $this->actingAs($citizen)
            ->postJson(route('applications.store'), [
                'service_type_id' => $serviceType->id,
                'documents' => [
                    UploadedFile::fake()->create('malicious.php', 100, 'text/plain'),
                    UploadedFile::fake()->create('residence.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('documents.0');

        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('application_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_public_tracking_shows_statuses_but_hides_remarks_and_document_paths(): void
    {
        $this->seed(DatabaseSeeder::class);

        $citizen = User::factory()->create();
        $officer = User::query()->where('role', 'officer')->firstOrFail();
        $serviceType = ServiceType::query()->where('name', 'Residential Recommendation Letter')->firstOrFail();
        $application = Application::query()->create([
            'user_id' => $citizen->id,
            'service_type_id' => $serviceType->id,
            'status' => 'under_review',
        ]);

        $application->documents()->create([
            'document_type' => 'citizenship_photo',
            'file_path' => 'applications/private-citizen-document.pdf',
            'file_type' => 'application/pdf',
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
                'remark' => 'Confidential staff note; do not expose publicly.',
                'changed_by' => $officer->id,
            ],
        ]);

        $this->get(route('applications.track', $application->tracking_id))
            ->assertOk()
            ->assertSee($application->tracking_id)
            ->assertSee('Under Review')
            ->assertSee('Submitted')
            ->assertDontSee('Confidential staff note')
            ->assertDontSee('private-citizen-document.pdf');

        $this->get('/track/NSP-DOES-NOT-EXIST')
            ->assertNotFound();
    }

    public function test_only_citizens_can_open_application_submission_pages(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->get(route('applications.create'))
            ->assertRedirect(route('login'));

        $this->actingAs($admin)
            ->get(route('applications.create'))
            ->assertForbidden();
    }
}
