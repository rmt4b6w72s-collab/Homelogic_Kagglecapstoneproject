<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Facility;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find or create "Evergreen Oasis Care Home" facility
        $facility = Facility::firstOrCreate(
            ['name' => 'Evergreen Oasis Care Home'],
            [
                'location' => 'Edmonds, WA',
                'description' => 'Our flagship facility providing compassionate care in a warm, home-like environment.',
                'address' => '123 Main Street, Edmonds, WA 98020',
                'phone' => '(206) 555-0123',
                'email' => 'info@evergreenoasis.com',
                'is_active' => true,
                'registration_status' => 'approved',
            ]
        );

        // Update all existing branches to belong to this facility
        DB::table('branches')
            ->whereNull('facility_id')
            ->update(['facility_id' => $facility->id]);

        // Update all existing users to belong to this facility (except super admins)
        DB::table('users')
            ->whereNull('facility_id')
            ->where('role', '!=', 'super_admin')
            ->update(['facility_id' => $facility->id]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove facility_id from users
        DB::table('users')
            ->where('facility_id', function ($query) {
                $query->select('id')
                    ->from('facilities')
                    ->where('name', 'Evergreen Oasis Care Home')
                    ->limit(1);
            })
            ->update(['facility_id' => null]);

        // Remove facility_id from branches
        DB::table('branches')
            ->where('facility_id', function ($query) {
                $query->select('id')
                    ->from('facilities')
                    ->where('name', 'Evergreen Oasis Care Home')
                    ->limit(1);
            })
            ->update(['facility_id' => null]);
    }
};
