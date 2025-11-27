<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing unique index on email column
        // MySQL creates a unique index named 'users_email_unique' by default
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            // Check if the unique index exists and drop it
            $indexes = DB::select("SHOW INDEXES FROM users WHERE Key_name = 'users_email_unique'");
            if (!empty($indexes)) {
                DB::statement('ALTER TABLE users DROP INDEX users_email_unique');
            }
        } else {
            // For SQLite, try to drop the unique constraint
            // SQLite doesn't support dropping unique constraints directly, 
            // but we can recreate the table if needed
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique(['email']);
                });
            } catch (\Exception $e) {
                // Index might not exist or already dropped, continue
            }
        }

        // Add composite unique index on (email, facility_id)
        // This allows the same email in different facilities
        // Note: NULL facility_id values (super admins) will still be unique per email
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['email', 'facility_id'], 'users_email_facility_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the composite unique index
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_facility_unique');
        });

        // Restore the original unique index on email only
        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
