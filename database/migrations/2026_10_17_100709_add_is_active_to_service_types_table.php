<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
        });

        DB::table('service_types')->update(['is_active' => false]);

        DB::table('departments')
            ->where('code', 'KMC')
            ->update([
                'code' => 'KMC-WARD-01',
                'name' => 'Kathmandu Metropolitan City — Selected Ward Office (demo)',
                'description' => 'Demonstration ward office for a residential recommendation service. Confirm official requirements, fees, and processing times with the ward office before relying on this information.',
            ]);

        DB::table('service_types')
            ->where('name', 'Residential Recommendation Letter')
            ->whereIn('department_id', function ($query): void {
                $query->select('id')
                    ->from('departments')
                    ->where('code', 'KMC-WARD-01');
            })
            ->update(['is_active' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('service_types')->update(['is_active' => true]);

        DB::table('departments')
            ->where('code', 'KMC-WARD-01')
            ->update([
                'code' => 'KMC',
                'name' => 'Kathmandu Municipality',
                'description' => 'Municipal registration and recommendation services.',
            ]);

        Schema::table('service_types', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
