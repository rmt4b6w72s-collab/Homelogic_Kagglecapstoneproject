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
        $facilities = \App\Models\Facility::all();
        $allModules = array_keys(\App\Constants\Modules::all());

        foreach ($facilities as $facility) {
            foreach ($allModules as $module) {
                $facility->modules()->updateOrCreate(
                    ['module' => $module],
                    ['is_enabled' => true]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally remove all module records, or leave them for rollback safety
        // \App\Models\FacilityModule::truncate();
    }
};
