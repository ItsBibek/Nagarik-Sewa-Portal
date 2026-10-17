<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationAuthorizationTest extends TestCase
{
    public function test_registration_creates_a_citizen_even_when_privileged_fields_are_submitted(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('register.store'), [
            'name' => 'New Citizen',
            'email' => 'new-citizen@example.test',
            'phone' => '9800000000',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'role' => 'admin',
            'department_id' => Department::query()->firstOrFail()->id,
        ])->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'new-citizen@example.test')->firstOrFail();

        $this->assertSame('citizen', $user->role);
        $this->assertNull($user->department_id);
        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_seeded_admin_can_log_in_and_log_out_using_web_authentication(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@nagarik-sewa.test')->firstOrFail();

        $this->get(route('login'))->assertOk();

        $this->post(route('login.store'), [
            'email' => 'admin@nagarik-sewa.test',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_sanctum_token_authenticates_api_requests_and_can_be_revoked(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->getJson('/api/v1/user')->assertUnauthorized();

        $tokenResponse = $this->postJson('/api/v1/tokens', [
            'email' => 'admin@nagarik-sewa.test',
            'password' => 'password',
            'device_name' => 'feature-test',
        ])->assertOk()
            ->assertJsonPath('message', 'API token created.')
            ->assertJsonPath('data.token_type', 'Bearer');

        $token = $tokenResponse->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('message', 'Authenticated user retrieved.')
            ->assertJsonPath('data.email', 'admin@nagarik-sewa.test');

        $this->withToken($token)
            ->deleteJson('/api/v1/tokens/current')
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'API token revoked.');

        Auth::forgetGuards();

        $this->withToken($token)->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_application_policy_prevents_cross_citizen_and_cross_department_access(): void
    {
        $this->seed(DatabaseSeeder::class);

        $department = Department::query()->where('code', 'KMC-WARD-01')->firstOrFail();
        $otherDepartment = Department::query()->create([
            'name' => 'Another test department',
            'code' => 'OTHER',
        ]);
        $serviceType = ServiceType::query()->where('department_id', $department->id)->firstOrFail();
        $citizen = User::factory()->create();
        $otherCitizen = User::factory()->create();
        $officer = User::query()->where('department_id', $department->id)->firstOrFail();
        $otherOfficer = User::factory()->create([
            'role' => 'officer',
            'department_id' => $otherDepartment->id,
        ]);
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $application = Application::query()->create([
            'user_id' => $citizen->id,
            'service_type_id' => $serviceType->id,
            'current_officer_id' => $officer->id,
        ]);

        $this->assertTrue($citizen->can('view', $application));
        $this->assertFalse($otherCitizen->can('view', $application));
        $this->assertTrue($officer->can('view', $application));
        $this->assertFalse($otherOfficer->can('view', $application));
        $this->assertTrue($citizen->can('create', Application::class));
        $this->assertFalse($officer->can('create', Application::class));
        $this->assertTrue($officer->can('update', $application));
        $this->assertFalse($otherOfficer->can('update', $application));
        $this->assertFalse($citizen->can('delete', $application));
        $this->assertTrue($admin->can('view', $application));
        $this->assertTrue($admin->can('update', $application));
        $this->assertTrue($admin->can('delete', $application));
    }

    public function test_application_policy_is_enforced_by_http_actions(): void
    {
        $this->seed(DatabaseSeeder::class);

        Route::get('/_test/policy-applications/{application}', function (string $application) {
            Gate::authorize('view', Application::query()->findOrFail($application));

            return response()->noContent();
        })->middleware('auth');

        $department = Department::query()->where('code', 'KMC-WARD-01')->firstOrFail();
        $serviceType = ServiceType::query()->where('department_id', $department->id)->firstOrFail();
        $owner = User::factory()->create();
        $otherCitizen = User::factory()->create();
        $application = Application::query()->create([
            'user_id' => $owner->id,
            'service_type_id' => $serviceType->id,
        ]);

        $this->actingAs($owner)
            ->get('/_test/policy-applications/'.$application->id)
            ->assertNoContent();

        $this->actingAs($otherCitizen)
            ->get('/_test/policy-applications/'.$application->id)
            ->assertForbidden();
    }

    public function test_department_middleware_rejects_officers_outside_the_resource_department(): void
    {
        $this->seed(DatabaseSeeder::class);

        Route::get('/_test/department-applications/{application}', function (Application $application) {
            return response()->noContent();
        })->middleware(['auth', 'department:application']);

        $department = Department::query()->where('code', 'KMC-WARD-01')->firstOrFail();
        $otherDepartment = Department::query()->create([
            'name' => 'Another test department',
            'code' => 'OTHER',
        ]);
        $serviceType = ServiceType::query()->where('department_id', $department->id)->firstOrFail();
        $application = Application::query()->create([
            'user_id' => User::factory()->create()->id,
            'service_type_id' => $serviceType->id,
        ]);
        $officer = User::query()->where('department_id', $department->id)->firstOrFail();
        $otherOfficer = User::factory()->create([
            'role' => 'officer',
            'department_id' => $otherDepartment->id,
        ]);
        $citizen = User::factory()->create();

        $this->actingAs($officer)
            ->get('/_test/department-applications/'.$application->id)
            ->assertNoContent();

        $this->actingAs($otherOfficer)
            ->get('/_test/department-applications/'.$application->id)
            ->assertForbidden();

        $this->actingAs($citizen)
            ->get('/_test/department-applications/'.$application->id)
            ->assertForbidden();
    }

    public function test_role_middleware_allows_only_the_specified_roles(): void
    {
        $this->seed(DatabaseSeeder::class);

        Route::get('/_test/admin', function () {
            return response()->noContent();
        })->middleware(['auth', 'role:admin']);

        $citizen = User::factory()->create();
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($citizen)->get('/_test/admin')->assertForbidden();
        $this->actingAs($admin)->get('/_test/admin')->assertNoContent();
    }
}
