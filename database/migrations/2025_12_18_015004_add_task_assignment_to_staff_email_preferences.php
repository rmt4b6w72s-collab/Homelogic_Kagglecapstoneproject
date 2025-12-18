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
        Schema::table('staff_email_preferences', function (Blueprint $table) {
            $table->boolean('task_assignment_enabled')->default(true)->after('daily_summary_enabled')->comment('Receive emails for task assignments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_email_preferences', function (Blueprint $table) {
            $table->dropColumn('task_assignment_enabled');
        });
    }
};
