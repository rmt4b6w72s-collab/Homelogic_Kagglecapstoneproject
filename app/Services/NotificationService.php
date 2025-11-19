<?php

namespace App\Services;

use App\Mail\LateMedicationNotification;
use App\Mail\LateVitalSignNotification;
use App\Models\Medication;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send email notification for late medication
     */
    public function sendLateMedicationEmail(Medication $medication, Resident $resident, $caregivers): void
    {
        $medicationName = $medication->drug?->name ?? $medication->name;
        $residentName = trim(($resident->first_name ?? '') . ' ' . ($resident->last_name ?? ''));
        
        foreach ($caregivers as $caregiver) {
            if ($caregiver->email) {
                try {
                    Mail::to($caregiver->email)->send(
                        new LateMedicationNotification($medication, $resident, 'Scheduled Time')
                    );
                    
                    // Log email sent (since using log driver, this will appear in logs)
                    Log::info('Late medication email sent', [
                        'to' => $caregiver->email,
                        'medication' => $medicationName,
                        'resident' => $residentName,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send late medication email', [
                        'to' => $caregiver->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Send email notification for late vital signs
     */
    public function sendLateVitalSignEmail(Resident $resident, $caregivers, int $hoursOverdue): void
    {
        $residentName = trim(($resident->first_name ?? '') . ' ' . ($resident->last_name ?? ''));
        
        foreach ($caregivers as $caregiver) {
            if ($caregiver->email) {
                try {
                    Mail::to($caregiver->email)->send(
                        new LateVitalSignNotification($resident, $hoursOverdue)
                    );
                    
                    // Log email sent (since using log driver, this will appear in logs)
                    Log::info('Late vital sign email sent', [
                        'to' => $caregiver->email,
                        'resident' => $residentName,
                        'hours_overdue' => $hoursOverdue,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send late vital sign email', [
                        'to' => $caregiver->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Get recipient emails from users
     */
    public function getRecipientEmails($users): array
    {
        return $users->pluck('email')->filter()->toArray();
    }
}

