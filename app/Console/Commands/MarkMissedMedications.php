<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Medication;
use App\Models\MedicationAdministration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class MarkMissedMedications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'medications:mark-missed {--date= : Date to check (Y-m-d format, defaults to today for real-time or yesterday for end-of-day)} {--end-of-day : Run in end-of-day mode (checks yesterday)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark medications as missed when administration windows close (runs periodically) or at end of day';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now(config('app.timezone'));
        $windowMinutes = 60; // 60 minutes before and after scheduled time
        
        // Determine which date(s) to check
        if ($this->option('end-of-day')) {
            // End-of-day mode: check yesterday
            $targetDate = Carbon::yesterday(config('app.timezone'));
            $this->info("End-of-day mode: Checking missed medications for date: {$targetDate->format('Y-m-d')}");
        } elseif ($this->option('date')) {
            // Specific date provided
            try {
                $targetDate = Carbon::createFromFormat('Y-m-d', $this->option('date'), config('app.timezone'));
                $this->info("Checking missed medications for date: {$targetDate->format('Y-m-d')}");
            } catch (\Exception $e) {
                $this->error("Invalid date format. Use Y-m-d format (e.g., 2025-12-25)");
                return 1;
            }
        } else {
            // Real-time mode: check today for past windows, and also check yesterday
            // to catch any missed medications that weren't caught by the end-of-day run
            $targetDate = $now->copy();
            $this->info("Real-time mode: Checking missed medications for today's past windows and yesterday");
        }
        
        // Get system user (first admin user, or create a system user)
        $systemUser = User::whereIn('role', ['super_admin', 'administrator', 'admin'])->first();
        if (!$systemUser) {
            // Fallback to user ID 1 if no admin exists
            $systemUserId = 1;
        } else {
            $systemUserId = $systemUser->id;
        }

        $count = 0;
        
        // In real-time mode, check both today and yesterday
        // In other modes, check only the target date
        $datesToCheck = [];
        if (!$this->option('end-of-day') && !$this->option('date')) {
            // Real-time mode: check today and yesterday
            $datesToCheck = [
                $targetDate->copy(), // Today
                $targetDate->copy()->subDay(), // Yesterday
            ];
        } else {
            // End-of-day or specific date: check only the target date
            $datesToCheck = [$targetDate];
        }

        foreach ($datesToCheck as $checkDate) {
            $dateStr = $checkDate->format('Y-m-d');
            $this->info("Checking date: {$dateStr}");

            // Get all active medications that were active on this date
            $medications = Medication::where('is_active', true)
                ->where(function ($q) use ($dateStr) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', $dateStr);
                })
                ->where(function ($q) use ($dateStr) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $dateStr);
                })
                ->get();

            foreach ($medications as $medication) {
                // Check each of the 4 possible time slots
                for ($i = 1; $i <= 4; $i++) {
                    $timeField = "time_{$i}";
                    $scheduledTimeStr = $medication->$timeField;

                    if (!$scheduledTimeStr) {
                        continue;
                    }

                    // Parse scheduled time for the check date
                    try {
                        // Parse time in H:i format
                        $timeParts = explode(':', $scheduledTimeStr);
                        if (count($timeParts) !== 2) {
                            Log::error("Invalid time format for medication {$medication->id}: {$scheduledTimeStr}");
                            continue;
                        }

                        $scheduledTime = $checkDate->copy();
                        $scheduledTime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
                    } catch (\Exception $e) {
                        Log::error("Error parsing time for medication {$medication->id}: {$scheduledTimeStr} - " . $e->getMessage());
                        continue;
                    }

                    // Calculate administration window (60 minutes before and after scheduled time)
                    $windowStart = $scheduledTime->copy()->subMinutes($windowMinutes);
                    $windowEnd = $scheduledTime->copy()->addMinutes($windowMinutes);

                    // In real-time mode, check windows that have closed (window end has passed)
                    // In end-of-day mode, check all windows for the day
                    // For yesterday (when checking in real-time mode), always check all windows
                    $isYesterday = $checkDate->format('Y-m-d') === $now->copy()->subDay()->format('Y-m-d');
                    if (!$this->option('end-of-day') && !$this->option('date') && !$isYesterday) {
                        // Real-time mode for today: only mark if window has closed (60 minutes after scheduled time)
                        // This ensures we give the full window for administration before marking as missed
                        if ($windowEnd->isFuture()) {
                            continue; // Window hasn't closed yet, skip
                        }
                    }

                // Check if there's already an administration record for this medication
                // within the administration window (60 minutes before and after scheduled time)
                // We only count non-missed statuses (completed, refused, hospital_admission, etc.)
                $hasAdministration = MedicationAdministration::where('medication_id', $medication->id)
                    ->whereBetween('administered_at', [$windowStart, $windowEnd])
                    ->whereIn('status', ['completed', 'refused', 'hospital_admission', 'pharmacy_administration_confirm'])
                    ->exists();

                if (!$hasAdministration) {
                    // Check if a missed record already exists for this time slot
                    $hasMissedRecord = MedicationAdministration::where('medication_id', $medication->id)
                        ->whereBetween('administered_at', [
                            $scheduledTime->copy()->subMinutes(5),
                            $scheduledTime->copy()->addMinutes(5)
                        ])
                        ->where('status', 'missed')
                        ->exists();

                    if (!$hasMissedRecord) {
                        // Create missed record at the scheduled time
                        try {
                            $notes = $this->option('end-of-day') 
                                ? 'Automatically marked as missed at end of day'
                                : 'Automatically marked as missed when administration window closed';
                            
                            MedicationAdministration::create([
                                'medication_id' => $medication->id,
                                'resident_id' => $medication->resident_id,
                                'branch_id' => $medication->branch_id,
                                'administered_by' => $systemUserId,
                                'status' => 'missed',
                                'administered_at' => $scheduledTime,
                                'notes' => $notes,
                            ]);

                            $this->info("Marked medication ID {$medication->id} ({$medication->name}) as missed for {$scheduledTime->format('Y-m-d H:i')}");
                            $count++;
                        } catch (\Exception $e) {
                            Log::error("Error creating missed medication record for medication {$medication->id}: " . $e->getMessage());
                            $this->warn("Failed to mark medication ID {$medication->id} as missed: " . $e->getMessage());
                        }
                    }
                }
                }
            }
        }

        $this->info("Completed. Marked {$count} medication doses as missed.");
        Log::info("MarkMissedMedications command completed. Marked {$count} medication doses as missed.");

        return 0;
    }
}
