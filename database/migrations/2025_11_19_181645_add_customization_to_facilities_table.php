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
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('brochure_color');
            $table->string('primary_color')->nullable()->after('logo');
            $table->string('secondary_color')->nullable()->after('primary_color');
            $table->string('accent_color')->nullable()->after('secondary_color');
            $table->string('subdomain')->nullable()->unique()->after('accent_color');
            $table->enum('registration_status', ['pending', 'approved', 'rejected'])->default('approved')->after('subdomain');
            $table->foreignId('registered_by_user_id')->nullable()->after('registration_status')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropForeign(['registered_by_user_id']);
            $table->dropColumn([
                'logo',
                'primary_color',
                'secondary_color',
                'accent_color',
                'subdomain',
                'registration_status',
                'registered_by_user_id',
            ]);
        });
    }
};
