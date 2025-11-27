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
        if (Schema::hasTable('payment_notification_preferences')) {
            return;
        }
        
        Schema::create('payment_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade')->comment('If null, applies to all facility admins');
            $table->integer('days_before_due')->default(7)->comment('Days before payment due date to send notification');
            $table->boolean('notify_on_due_date')->default(true);
            $table->boolean('notify_on_overdue')->default(true);
            $table->integer('overdue_reminder_interval_days')->default(7)->comment('Days between overdue reminders');
            $table->boolean('email_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);
            $table->json('notification_channels')->nullable()->comment('Additional channels: sms, push, etc.');
            $table->timestamps();

            $table->unique(['facility_id', 'user_id']);
            $table->index(['facility_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_notification_preferences');
    }
};

