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
        Schema::table('appointments', function (Blueprint $table) {
            // Add appointment_time column if it doesn't exist
            if (!Schema::hasColumn('appointments', 'appointment_time')) {
                $table->time('appointment_time')->nullable()->after('appointment_date');
            }
            
            // Add appointment_type_id if it doesn't exist
            if (!Schema::hasColumn('appointments', 'appointment_type_id')) {
                $table->foreignId('appointment_type_id')->nullable()->after('branch_id')->constrained()->onDelete('set null');
            }
            
            // Add healthcare_provider_id if it doesn't exist
            if (!Schema::hasColumn('appointments', 'healthcare_provider_id')) {
                $table->foreignId('healthcare_provider_id')->nullable()->after('appointment_type_id')->constrained()->onDelete('set null');
            }
            
            // Add next_appointment_date if it doesn't exist
            if (!Schema::hasColumn('appointments', 'next_appointment_date')) {
                $table->date('next_appointment_date')->nullable()->after('status');
            }
            
            // Add recurrence_pattern if it doesn't exist
            if (!Schema::hasColumn('appointments', 'recurrence_pattern')) {
                $table->string('recurrence_pattern')->nullable()->after('next_appointment_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Remove columns in reverse order
            if (Schema::hasColumn('appointments', 'recurrence_pattern')) {
                $table->dropColumn('recurrence_pattern');
            }
            if (Schema::hasColumn('appointments', 'next_appointment_date')) {
                $table->dropColumn('next_appointment_date');
            }
            if (Schema::hasColumn('appointments', 'healthcare_provider_id')) {
                $table->dropForeign(['healthcare_provider_id']);
                $table->dropColumn('healthcare_provider_id');
            }
            if (Schema::hasColumn('appointments', 'appointment_type_id')) {
                $table->dropForeign(['appointment_type_id']);
                $table->dropColumn('appointment_type_id');
            }
            if (Schema::hasColumn('appointments', 'appointment_time')) {
                $table->dropColumn('appointment_time');
            }
        });
    }
};
