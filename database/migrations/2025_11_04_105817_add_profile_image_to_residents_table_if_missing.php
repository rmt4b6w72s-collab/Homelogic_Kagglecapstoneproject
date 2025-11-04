<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add profile_image column to residents table if it doesn't exist
        if (Schema::hasTable('residents') && !Schema::hasColumn('residents', 'profile_image')) {
            Schema::table('residents', function (Blueprint $table) {
                // Try to add after 'status' if it exists, otherwise add at the end
                if (Schema::hasColumn('residents', 'status')) {
                    $table->string('profile_image')->nullable()->after('status');
                } else {
                    $table->string('profile_image')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove profile_image column if it exists
        if (Schema::hasTable('residents') && Schema::hasColumn('residents', 'profile_image')) {
            Schema::table('residents', function (Blueprint $table) {
                $table->dropColumn('profile_image');
            });
        }
    }
};
