<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Resident;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class CustomAppointments extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'My Appointments';
    protected static ?string $title = 'My Appointments';
    protected static ?int $navigationSort = 50;
    protected static string $view = 'filament.pages.custom-appointments';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('caregiver');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hidden - caregivers will use main appointments page
    }

    public Collection $appointments;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->appointments = Appointment::query()
            ->with(['resident', 'branch', 'appointmentType', 'healthcareProvider', 'createdBy'])
            ->where('appointment_date', '>=', now()->subDays(7)->startOfDay()) // Get appointments from last 7 days
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();
    }

    public function getResidents(): Collection
    {
        return Resident::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function getAppointmentsByDate(): array
    {
        $grouped = $this->appointments->groupBy(function ($appointment) {
            return $appointment->appointment_date->format('Y-m-d');
        });

        return $grouped->toArray();
    }

    public function getUpcomingAppointments(): Collection
    {
        return $this->appointments->take(12); // Show up to 12 appointments for 4 rows of 3 cards
    }

    public function getResidentAppointments($residentId): Collection
    {
        return $this->appointments->where('resident_id', $residentId);
    }
}
