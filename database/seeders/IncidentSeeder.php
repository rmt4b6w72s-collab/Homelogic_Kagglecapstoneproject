<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\User;
use Carbon\Carbon;

class IncidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $residents = Resident::all();
        $users = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['administrator', 'super_admin', 'caregiver']);
        })->get();

        if ($residents->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No residents or users found. Please run ResidentSeeder and UserSeeder first.');
            return;
        }

        $incidentTypes = [
            'Fall',
            'Medication Error',
            'Behavioral Incident',
            'Medical Emergency',
            'Equipment Malfunction',
            'Security Breach',
            'Fire/Safety',
            'Food Safety',
            'Infection Control',
            'Transportation',
            'Communication Error',
            'Environmental Hazard',
            'Staff Injury',
            'Resident Injury',
            'Property Damage'
        ];

        $severityLevels = [
            Incident::SEVERITY_LOW,
            Incident::SEVERITY_MEDIUM,
            Incident::SEVERITY_HIGH,
            Incident::SEVERITY_CRITICAL,
        ];
        
        $statuses = [
            Incident::STATUS_OPEN,
            Incident::STATUS_IN_PROGRESS,
            Incident::STATUS_RESOLVED,
            Incident::STATUS_CLOSED,
            Incident::STATUS_ON_HOLD,
        ];
        
        $priorities = [
            Incident::PRIORITY_LOW,
            Incident::PRIORITY_MEDIUM,
            Incident::PRIORITY_HIGH,
            Incident::PRIORITY_CRITICAL,
        ];

        $locations = [
            'Room ' . rand(100, 999),
            'Dining Area',
            'Main Hallway',
            'Activity Room',
            'Bathroom',
            'Outdoor Patio',
            'Nurses Station',
            'Elevator',
            'Stairs',
            null, // Some incidents may not have location
        ];

        foreach ($residents as $resident) {
            // Create 1-4 incidents per resident
            $incidentCount = rand(1, 4);
            
            for ($i = 0; $i < $incidentCount; $i++) {
                $occurredAt = Carbon::now()->subDays(rand(1, 180))->setTime(rand(6, 21), rand(0, 59));

                $type = $incidentTypes[array_rand($incidentTypes)];
                $severity = $severityLevels[array_rand($severityLevels)];
                $priority = $priorities[array_rand($priorities)];
                $status = $statuses[array_rand($statuses)];
                $reportedBy = $users->random();
                $location = $locations[array_rand($locations)];
                
                // For resolved/closed incidents, set resolved_by and resolved_at
                $resolvedBy = null;
                $resolvedAt = null;
                if (in_array($status, [Incident::STATUS_RESOLVED, Incident::STATUS_CLOSED])) {
                    $resolvedBy = $users->random();
                    $resolvedAt = $occurredAt->copy()->addHours(rand(1, 72));
                }
                
                // Some incidents may be assigned
                $assignedTo = rand(0, 1) === 1 ? $users->random() : null;

                Incident::create([
                    'resident_id' => $resident->id,
                    'branch_id' => $resident->branch_id,
                    'incident_type' => $type,
                    'description' => ucfirst($type) . ' incident involving resident. Immediate action taken and documented. ' .
                        ($severity === Incident::SEVERITY_CRITICAL ? 'This was a critical incident requiring immediate attention.' : ''),
                    'incident_date' => $occurredAt,
                    'location' => $location,
                    'severity' => $severity,
                    'priority' => $priority,
                    'status' => $status,
                    'action_taken' => 'Staff responded immediately and documented the incident. Appropriate measures were taken to ensure resident safety.',
                    'witnesses' => rand(0, 1) === 1 ? 'Witnessed by staff member ' . $users->random()->name : null,
                    'follow_up' => in_array($status, [Incident::STATUS_RESOLVED, Incident::STATUS_CLOSED])
                        ? 'Incident resolved. Resident monitored and follow-up care provided. Safety protocols reviewed.'
                        : 'Monitor resident and review safety protocols. Follow-up assessment scheduled.',
                    'reported_by' => $reportedBy->id,
                    'assigned_to' => $assignedTo?->id,
                    'resolved_by' => $resolvedBy?->id,
                    'resolved_at' => $resolvedAt,
                    'created_at' => $occurredAt,
                    'updated_at' => $resolvedAt ?? $occurredAt,
                ]);
            }
        }

        $this->command->info('IncidentSeeder completed successfully!');
    }

}
