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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // appointment_reminder, vital_due, assessment_due, medication_due, leave_request, etc.
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable(); // icon name
            $table->string('icon_color')->nullable(); // color for the icon
            $table->boolean('is_read')->default(false);
            $table->string('action_url')->nullable(); // URL to navigate to when clicked
            $table->json('metadata')->nullable(); // additional data as JSON
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
