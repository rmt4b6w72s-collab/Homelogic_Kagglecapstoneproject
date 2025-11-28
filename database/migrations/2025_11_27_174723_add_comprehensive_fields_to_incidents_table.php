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
        Schema::table('incidents', function (Blueprint $table) {
            // Add status field
            if (!Schema::hasColumn('incidents', 'status')) {
                $table->enum('status', ['open', 'in_progress', 'resolved', 'closed', 'on_hold'])
                    ->default('open')
                    ->after('severity');
            }

            // Add priority field
            if (!Schema::hasColumn('incidents', 'priority')) {
                $table->enum('priority', ['critical', 'high', 'medium', 'low'])
                    ->default('medium')
                    ->after('status');
            }

            // Add location field
            if (!Schema::hasColumn('incidents', 'location')) {
                $table->string('location')->nullable()->after('incident_date');
            }

            // Add incident_number field
            if (!Schema::hasColumn('incidents', 'incident_number')) {
                $table->string('incident_number')->unique()->nullable()->after('id');
            }

            // Add assigned_to field
            if (!Schema::hasColumn('incidents', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->after('reported_by');
            }

            // Add resolved_by field
            if (!Schema::hasColumn('incidents', 'resolved_by')) {
                $table->foreignId('resolved_by')->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->after('assigned_to');
            }

            // Add resolved_at field
            if (!Schema::hasColumn('incidents', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('resolved_by');
            }

            // Add witnesses field
            if (!Schema::hasColumn('incidents', 'witnesses')) {
                $table->text('witnesses')->nullable()->after('action_taken');
            }

            // Add soft deletes
            if (!Schema::hasColumn('incidents', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }

            // Add indexes for performance
            $table->index('status');
            $table->index('priority');
            $table->index('severity');
            $table->index('incident_date');
            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'incident_date']);
            $table->index(['resident_id', 'incident_date']);
        });

        // Update severity to enum if it's currently a string
        // Note: This requires careful handling if there's existing data
        if (Schema::hasColumn('incidents', 'severity')) {
            // Check current column type by querying the information schema
            Schema::table('incidents', function (Blueprint $table) {
                // We'll need to handle this carefully - for now we'll keep it as string
                // but add validation in the model
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['severity']);
            $table->dropIndex(['incident_date']);
            $table->dropIndex(['branch_id', 'status']);
            $table->dropIndex(['branch_id', 'incident_date']);
            $table->dropIndex(['resident_id', 'incident_date']);

            // Drop columns
            if (Schema::hasColumn('incidents', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('incidents', 'witnesses')) {
                $table->dropColumn('witnesses');
            }
            if (Schema::hasColumn('incidents', 'resolved_at')) {
                $table->dropColumn('resolved_at');
            }
            if (Schema::hasColumn('incidents', 'resolved_by')) {
                $table->dropForeign(['resolved_by']);
                $table->dropColumn('resolved_by');
            }
            if (Schema::hasColumn('incidents', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            }
            if (Schema::hasColumn('incidents', 'incident_number')) {
                $table->dropUnique(['incident_number']);
                $table->dropColumn('incident_number');
            }
            if (Schema::hasColumn('incidents', 'location')) {
                $table->dropColumn('location');
            }
            if (Schema::hasColumn('incidents', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('incidents', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
