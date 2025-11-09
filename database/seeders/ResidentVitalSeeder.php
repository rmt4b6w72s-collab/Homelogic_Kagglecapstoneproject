<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Resident;
use App\Models\User;
use App\Models\VitalSign;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ResidentVitalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $output = $this->command ?? null;

        $residents = Resident::with('branch')->get();
        if ($residents->isEmpty()) {
            $output?->warn('No residents found — skipping resident vital seeding.');
            return;
        }

        $takenByUserId = User::query()->value('id');
        if (!$takenByUserId) {
            $output?->warn('No users found — skipping resident vital seeding.');
            return;
        }

        $daysToGenerate = 7;
        $now = Carbon::now()->startOfDay();
        $createdCount = 0;

        DB::transaction(function () use ($residents, $takenByUserId, $daysToGenerate, $now, &$createdCount) {
            foreach ($residents as $resident) {
                $branchId = $resident->branch_id;

                // Establish baseline vitals per resident for more realistic data
                $baseSystolic = rand(110, 125);
                $baseDiastolic = rand(70, 80);
                $basePulse = rand(65, 85);
                $baseTemperature = rand(970, 990) / 10; // 97.0 - 99.0
                $baseOxygen = rand(95, 99);

                for ($offset = 0; $offset < $daysToGenerate; $offset++) {
                    $measurementDate = $now->copy()->subDays($offset);
                    $existing = VitalSign::where('resident_id', $resident->id)
                        ->whereDate('measurement_date', $measurementDate->toDateString())
                        ->exists();

                    if ($existing) {
                        continue;
                    }

                    $systolic = (int) round($baseSystolic * (1 + rand(-5, 5) / 100));
                    $diastolic = (int) round($baseDiastolic * (1 + rand(-5, 5) / 100));
                    $pulse = (int) round($basePulse * (1 + rand(-8, 8) / 100));
                    $temperature = round($baseTemperature + rand(-15, 20) / 10, 1);
                    $oxygen = max(90, min(100, (int) round($baseOxygen + rand(-3, 2))));
                    $painLevel = rand(0, 10) > 7 ? rand(0, 4) : null;

                    $status = 'approved';
                    $notes = 'Routine caregiver vital check.';

                    if ($systolic >= 140 || $diastolic >= 90 || $temperature >= 100.4 || $oxygen <= 92) {
                        $status = 'critical';
                        $notes = 'Reading outside normal range — escalate to nurse.';
                    } elseif ($systolic >= 130 || $diastolic >= 85 || $temperature >= 99.5 || $oxygen <= 94) {
                        $status = 'pending_review';
                        $notes = 'Slightly elevated reading — monitor closely.';
                    }

                    VitalSign::create([
                        'resident_id' => $resident->id,
                        'branch_id' => $branchId,
                        'measurement_date' => $measurementDate,
                        'systolic' => $systolic,
                        'diastolic' => $diastolic,
                        'temperature' => $temperature,
                        'pulse' => $pulse,
                        'oxygen_saturation' => $oxygen,
                        'pain_level' => $painLevel,
                        'status' => $status,
                        'notes' => $notes,
                        'taken_by' => $takenByUserId,
                    ]);

                    $createdCount++;
                }
            }
        });

        $output?->info("Resident vital seeder generated {$createdCount} new vital records across {$residents->count()} residents.");
    }
}
