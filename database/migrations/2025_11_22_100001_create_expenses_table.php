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
        if (Schema::hasTable('expenses')) {
            return;
        }
        
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('expense_category_id')->constrained()->onDelete('restrict');
            $table->foreignId('resident_id')->nullable()->constrained()->onDelete('set null');
            $table->string('vendor_name')->nullable();
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('expense_date');
            $table->date('payment_date')->nullable();
            $table->enum('payment_method', ['cash', 'check', 'card', 'transfer', 'other'])->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->string('invoice_number')->nullable();
            $table->string('receipt_url')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['facility_id', 'expense_date']);
            $table->index(['expense_category_id']);
            $table->index(['resident_id']);
            $table->index(['payment_status']);
            $table->index(['branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

