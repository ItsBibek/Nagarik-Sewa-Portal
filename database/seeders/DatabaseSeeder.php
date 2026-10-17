<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        ServiceType::query()->update(['is_active' => false]);

        $department = Department::updateOrCreate(
            ['code' => 'KMC-WARD-01'],
            [
                'name' => 'Kathmandu Metropolitan City — Selected Ward Office (demo)',
                'description' => 'Demonstration ward office for a residential recommendation service. Confirm official requirements, fees, and processing times with the ward office before relying on this information.',
            ],
        );

        $department->serviceTypes()->updateOrCreate(
            ['name' => 'Residential Recommendation Letter'],
            [
                'required_documents' => ['citizenship_certificate', 'proof_of_residence'],
                'fee' => 500,
                'processing_days' => 5,
                'is_active' => true,
            ],
        );

        $officer = User::query()->firstOrNew([
            'role' => 'officer',
            'department_id' => $department->id,
        ]);
        $officer->fill([
            'email' => 'officer-kmc-ward-01@nagarik-sewa.test',
            'name' => 'Selected Ward Office Demo Officer',
            'password' => Hash::make('password'),
        ])->save();

        User::updateOrCreate(
            ['email' => 'admin@nagarik-sewa.test'],
            [
                'name' => 'Portal Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department_id' => null,
            ],
        );
    }
}
