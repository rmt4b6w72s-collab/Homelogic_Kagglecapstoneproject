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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('middle_names')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_names');
            $table->renameColumn('phone', 'phone_number');
            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->string('marital_status')->nullable()->after('date_of_birth');
            $table->string('sex')->nullable()->after('marital_status');
            $table->string('position')->nullable()->after('sex');
            $table->string('credentials')->nullable()->after('position');
            $table->text('credential_details')->nullable()->after('credentials');
            $table->date('date_employed')->nullable()->after('credential_details');
            $table->string('supervisor_name')->nullable()->after('date_employed');
            $table->string('provider_name')->nullable()->after('supervisor_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('phone_number', 'phone');
            $table->dropColumn([
                'first_name',
                'middle_names',
                'last_name',
                'date_of_birth',
                'marital_status',
                'sex',
                'position',
                'credentials',
                'credential_details',
                'date_employed',
                'supervisor_name',
                'provider_name',
            ]);
        });
    }
};
