<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained()->onDelete('cascade');
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->enum('role', ['user', 'assistant']);
            $table->longText('content');
            $table->enum('type', ['question', 'summary', 'progress_note', 'analysis']);
            
            $table->timestamps();
            $table->index(['resident_id', 'facility_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_messages');
    }
};