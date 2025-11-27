<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Facility;
use App\Services\LocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GeocodeBranchesAndFacilities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geocode:locations 
                            {--force : Force geocoding even if coordinates already exist}
                            {--branch-id= : Geocode a specific branch by ID}
                            {--facility-id= : Geocode a specific facility by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Geocode addresses for branches and facilities to populate latitude/longitude coordinates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $locationService = app(LocationService::class);
        $force = $this->option('force');
        $branchId = $this->option('branch-id');
        $facilityId = $this->option('facility-id');

        $this->info('Starting geocoding process...');
        $this->newLine();

        $totalProcessed = 0;
        $totalSuccess = 0;
        $totalFailed = 0;

        // Process branches
        if ($branchId) {
            $branches = Branch::where('id', $branchId)->get();
        } else {
            $branches = Branch::where(function ($query) use ($force) {
                if (!$force) {
                    $query->whereNull('latitude')
                          ->orWhereNull('longitude');
                }
            })->whereNotNull('address')
              ->where('address', '!=', '')
              ->get();
        }

        if ($branches->count() > 0) {
            $this->info("Processing {$branches->count()} branch(es)...");
            
            foreach ($branches as $branch) {
                $totalProcessed++;
                $this->line("Geocoding branch: {$branch->name} ({$branch->address})");
                
                if (!$force && $branch->hasCoordinates()) {
                    $this->warn("  → Skipped: Already has coordinates");
                    continue;
                }

                $coordinates = $locationService->geocodeAddress($branch->address);
                
                if ($coordinates) {
                    $branch->latitude = $coordinates['latitude'];
                    $branch->longitude = $coordinates['longitude'];
                    $branch->save();
                    $totalSuccess++;
                    $this->info("  ✓ Success: {$coordinates['latitude']}, {$coordinates['longitude']}");
                } else {
                    $totalFailed++;
                    $this->error("  ✗ Failed: Unable to geocode address");
                }

                // Rate limiting: 1 request per second for Nominatim
                if ($totalProcessed < ($branches->count() + ($facilityId ? 1 : 0))) {
                    sleep(1);
                }
            }
        }

        // Process facilities
        if ($facilityId) {
            $facilities = Facility::where('id', $facilityId)->get();
        } else {
            $facilities = Facility::where(function ($query) use ($force) {
                if (!$force) {
                    $query->whereNull('latitude')
                          ->orWhereNull('longitude');
                }
            })->whereNotNull('address')
              ->where('address', '!=', '')
              ->get();
        }

        if ($facilities->count() > 0) {
            $this->newLine();
            $this->info("Processing {$facilities->count()} facility(ies)...");
            
            foreach ($facilities as $facility) {
                $totalProcessed++;
                $this->line("Geocoding facility: {$facility->name} ({$facility->address})");
                
                if (!$force && $facility->hasCoordinates()) {
                    $this->warn("  → Skipped: Already has coordinates");
                    continue;
                }

                $coordinates = $locationService->geocodeAddress($facility->address);
                
                if ($coordinates) {
                    $facility->latitude = $coordinates['latitude'];
                    $facility->longitude = $coordinates['longitude'];
                    $facility->save();
                    $totalSuccess++;
                    $this->info("  ✓ Success: {$coordinates['latitude']}, {$coordinates['longitude']}");
                } else {
                    $totalFailed++;
                    $this->error("  ✗ Failed: Unable to geocode address");
                }

                // Rate limiting: 1 request per second for Nominatim
                if ($totalProcessed < ($branches->count() + $facilities->count())) {
                    sleep(1);
                }
            }
        }

        // Summary
        $this->newLine();
        $this->info('Geocoding complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $totalProcessed],
                ['Successful', $totalSuccess],
                ['Failed', $totalFailed],
            ]
        );

        if ($totalFailed > 0) {
            $this->warn("Some addresses could not be geocoded. Please check the addresses and try again, or enter coordinates manually.");
        }

        return Command::SUCCESS;
    }
}
