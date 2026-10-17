<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    public function test_seed_data_is_idempotent_and_applications_generate_tracking_ids(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('departments', 1);
        $this->assertDatabaseCount('service_types', 1);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('applications', 0);

        $citizen = User::factory()->create();
        $officer = User::query()->where('role', 'officer')->firstOrFail();
        $serviceType = ServiceType::query()->where('is_active', true)->firstOrFail();

        $application = Application::query()->create([
            'user_id' => $citizen->id,
            'service_type_id' => $serviceType->id,
            'current_officer_id' => $officer->id,
        ]);

        $this->assertMatchesRegularExpression('/^NSP-[A-Z0-9]{12}$/', $application->tracking_id);
        $this->assertTrue($application->user->is($citizen));
        $this->assertTrue($application->currentOfficer->is($officer));
        $this->assertTrue($application->serviceType->is($serviceType));

        $application->documents()->create([
            'file_path' => 'applications/example/passport.pdf',
            'file_type' => 'application/pdf',
        ]);

        $application->statusHistories()->create([
            'old_status' => null,
            'new_status' => 'submitted',
            'changed_by' => $officer->id,
        ]);

        $this->assertDatabaseCount('application_documents', 1);
        $this->assertDatabaseCount('status_histories', 1);
        $this->assertSame(1, Department::query()->count());
    }
}
