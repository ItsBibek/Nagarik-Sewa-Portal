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

class ApplicationWorkflowTest extends TestCase
{
    public function test_officer_queue_only_lists_applications_for_their_department(): void
    {
        $this->seed(DatabaseSeeder::class);
        $officer = User::query()->where('role', 'officer')->firstOrFail();
        $ownApplication = $this->makeTestApplication();
        $inactiveService = $officer->department->serviceTypes()->create([
            'name' => 'Inactive ward service',
            'required_documents' => ['identity_document'],
            'fee' => 0,
            'processing_days' => 2,
            'is_active' => false,
        ]);
        $inactiveApplication = Application::query()->create([
            'user_id' => User::factory()->create()->id,
            'service_type_id' => $inactiveService->id,
            'status' => 'submitted',
        ]);
        $otherDepartment = Department::query()->create([
            'name' => 'Different department',
            'code' => 'OTHER',
        ]);
        $otherService = $otherDepartment->serviceTypes()->create([
            'name' => 'Private test service',
            'required_documents' => ['identity_document'],
            'fee' => 0,
            'processing_days' => 2,
            'is_active' => true,
        ]);
        $otherApplication = Application::query()->create([
            'user_id' => User::factory()->create()->id,
            'service_type_id' => $otherService->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($officer)
            ->get(route('officer.applications.index'))
            ->assertOk()
            ->assertSee($ownApplication->tracking_id)
            ->assertDontSee($inactiveApplication->tracking_id)
            ->assertDontSee($otherApplication->tracking_id);
    }

    public function test_officer_can_progress_an_application_and_terminal_statuses_cannot_change(): void
    {
        $this->seed(DatabaseSeeder::class);
        $officer = User::query()->where('role', 'officer')->firstOrFail();
        $application = $this->makeTestApplication();

        $this->actingAs($officer)
            ->patch(route('officer.applications.status.update', $application), [
                'status' => 'under_review',
            ])
            ->assertRedirect(route('officer.applications.show', $application));

        $application->refresh();
        $this->assertSame('under_review', $application->status);
        $this->assertSame($officer->id, $application->current_officer_id);
        $this->assertDatabaseHas('status_histories', [
            'application_id' => $application->id,
            'old_status' => 'submitted',
            'new_status' => 'under_review',
            'changed_by' => $officer->id,
        ]);

        $this->actingAs($officer)
            ->patch(route('officer.applications.status.update', $application), [
                'status' => 'approved',
            ])
            ->assertRedirect(route('officer.applications.show', $application));

        $this->actingAs($officer)
            ->from(route('officer.applications.show', $application))
            ->patch(route('officer.applications.status.update', $application), [
                'status' => 'rejected',
                'remark' => 'Late decision attempt',
            ])
            ->assertRedirect(route('officer.applications.show', $application))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('status_histories', [
            'application_id' => $application->id,
            'old_status' => 'under_review',
            'new_status' => 'approved',
            'changed_by' => $officer->id,
        ]);
        $this->assertDatabaseCount('status_histories', 3);
    }

    public function test_citizen_cannot_approve_their_own_application(): void
    {
        $this->seed(DatabaseSeeder::class);
        $citizen = User::factory()->create();
        $application = $this->makeTestApplication('submitted', $citizen);

        $this->actingAs($citizen)
            ->patch(route('officer.applications.status.update', $application), [
                'status' => 'approved',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseCount('status_histories', 1);
    }

    public function test_correction_and_rejection_require_a_remark(): void
    {
        $this->seed(DatabaseSeeder::class);
        $officer = User::query()->where('role', 'officer')->firstOrFail();
        $application = $this->makeTestApplication('under_review');

        $this->actingAs($officer)
            ->from(route('officer.applications.show', $application))
            ->patch(route('officer.applications.status.update', $application), [
                'status' => 'correction_required',
            ])
            ->assertRedirect(route('officer.applications.show', $application))
            ->assertSessionHasErrors('remark');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'under_review',
        ]);
        $this->assertDatabaseCount('status_histories', 1);
    }

    public function test_officer_cannot_return_correction_required_application_without_citizen_resubmission(): void
    {
        $this->seed(DatabaseSeeder::class);
        $officer = User::query()->where('role', 'officer')->firstOrFail();
        $application = $this->makeTestApplication('under_review');

        $this->actingAs($officer)
            ->patch(route('officer.applications.status.update', $application), [
                'status' => 'correction_required',
                'remark' => 'Please replace the documents.',
            ])
            ->assertRedirect(route('officer.applications.show', $application));

        $this->actingAs($officer)
            ->from(route('officer.applications.show', $application))
            ->patch(route('officer.applications.status.update', $application), [
                'status' => 'under_review',
            ])
            ->assertRedirect(route('officer.applications.show', $application))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'correction_required',
        ]);
    }

    public function test_citizen_can_resubmit_documents_after_a_correction_request(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $officer = User::query()->where('role', 'officer')->firstOrFail();
        $citizen = User::factory()->create();
        $application = $this->makeTestApplication('under_review', $citizen);

        $this->actingAs($officer)
            ->patch(route('officer.applications.status.update', $application), [
                'status' => 'correction_required',
                'remark' => 'Please provide clearer copies of both documents.',
            ])
            ->assertRedirect(route('officer.applications.show', $application));

        $this->actingAs($citizen)
            ->get(route('applications.corrections.edit', $application))
            ->assertOk()
            ->assertSee('Please provide clearer copies of both documents.');

        $this->actingAs($citizen)
            ->post(route('applications.corrections.store', $application), [
                'documents' => [
                    UploadedFile::fake()->create('citizenship.pdf', 100, 'application/pdf'),
                    UploadedFile::fake()->create('residence.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('applications.index'));

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => 'under_review',
        ]);
        $this->assertDatabaseCount('application_documents', 2);
        $this->assertDatabaseHas('status_histories', [
            'application_id' => $application->id,
            'old_status' => 'correction_required',
            'new_status' => 'under_review',
            'changed_by' => $citizen->id,
        ]);

        foreach ($application->fresh()->documents as $document) {
            Storage::disk('local')->assertExists($document->file_path);
        }
    }

    public function test_officer_cannot_read_or_change_applications_from_another_department(): void
    {
        $this->seed(DatabaseSeeder::class);
        $officer = User::query()->where('role', 'officer')->firstOrFail();
        $otherDepartment = Department::query()->create([
            'name' => 'Different department',
            'code' => 'OTHER',
        ]);
        $otherService = $otherDepartment->serviceTypes()->create([
            'name' => 'Private test service',
            'required_documents' => ['identity_document'],
            'fee' => 0,
            'processing_days' => 2,
            'is_active' => true,
        ]);
        $application = Application::query()->create([
            'user_id' => User::factory()->create()->id,
            'service_type_id' => $otherService->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($officer)
            ->get(route('officer.applications.show', $application))
            ->assertForbidden();

        $this->actingAs($officer)
            ->patch(route('officer.applications.status.update', $application), [
                'status' => 'under_review',
            ])
            ->assertForbidden();
    }

    public function test_citizens_cannot_access_officer_workflow_routes(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs(User::factory()->create())
            ->get(route('officer.applications.index'))
            ->assertForbidden();
    }

    public function test_private_application_documents_are_downloadable_only_by_authorized_users(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $citizen = User::factory()->create();
        $otherCitizen = User::factory()->create();
        $application = $this->makeTestApplication('submitted', $citizen);
        $filePath = 'applications/'.$application->tracking_id.'/identity.pdf';
        Storage::disk('local')->put($filePath, 'private document contents');
        $document = $application->documents()->create([
            'document_type' => 'citizenship_certificate',
            'file_path' => $filePath,
            'file_type' => 'application/pdf',
        ]);

        $this->actingAs($otherCitizen)
            ->get(route('applications.documents.download', [$application, $document]))
            ->assertForbidden();

        $this->actingAs($citizen)
            ->get(route('applications.documents.download', [$application, $document]))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    private function makeTestApplication(
        string $status = 'submitted',
        ?User $citizen = null,
    ): Application {
        $citizen ??= User::factory()->create();
        $serviceType = ServiceType::query()
            ->where('name', 'Residential Recommendation Letter')
            ->firstOrFail();

        $application = Application::query()->create([
            'user_id' => $citizen->id,
            'service_type_id' => $serviceType->id,
            'status' => $status,
        ]);

        $application->statusHistories()->create([
            'old_status' => $status === 'submitted' ? null : 'submitted',
            'new_status' => $status,
            'changed_by' => $citizen->id,
        ]);

        return $application;
    }
}
