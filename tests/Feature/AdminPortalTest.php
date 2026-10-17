<?php

namespace Tests\Feature;

use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    public function test_admin_can_manage_the_demo_service_details_and_view_all_requests(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $service = ServiceType::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Portal overview')
            ->assertSee('NPR 500.00')
            ->assertSee('Edit service details');

        $this->actingAs($admin)
            ->get(route('admin.service.edit'))
            ->assertOk()
            ->assertSee('Example fee in NPR');

        $this->actingAs($admin)
            ->put(route('admin.service.update', $service), [
                'name' => 'Residential Recommendation Letter',
                'fee' => '750.00',
                'processing_days' => 7,
            ])
            ->assertRedirect(route('admin.service.edit'))
            ->assertSessionHas('status', 'Service details saved.');

        $this->assertDatabaseHas('service_types', [
            'id' => $service->id,
            'fee' => '750.00',
            'processing_days' => 7,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee('All citizen requests');
    }

    public function test_non_admins_cannot_manage_service_details_or_open_admin_pages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $citizen = User::factory()->create();
        $service = ServiceType::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($citizen)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($citizen)
            ->put(route('admin.service.update', $service), [
                'name' => 'Unauthorized service update',
                'fee' => 1,
                'processing_days' => 1,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('service_types', [
            'id' => $service->id,
            'name' => 'Unauthorized service update',
        ]);
    }

    public function test_admin_catalog_does_not_offer_a_citizen_application_action(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('services.index'))
            ->assertOk()
            ->assertSee('Go to your dashboard')
            ->assertDontSee('Start application');
    }
}
