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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, json, boolean, integer
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default super admin theme settings
        DB::table('system_settings')->insert([
            [
                'key' => 'super_admin_primary_color',
                'value' => '#1E3A5F',
                'type' => 'string',
                'description' => 'Primary color for super admin interface',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'super_admin_secondary_color',
                'value' => '#86EFAC',
                'type' => 'string',
                'description' => 'Secondary color for super admin interface',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'super_admin_accent_color',
                'value' => '#FFFFFF',
                'type' => 'string',
                'description' => 'Accent color for super admin interface',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
