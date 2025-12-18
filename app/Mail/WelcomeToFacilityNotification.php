<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Facility;
use App\Models\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeToFacilityNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?Facility $facility = null,
        public ?Branch $branch = null,
        public ?string $temporaryPassword = null
    ) {
        // Load facility and branch if not provided
        if (!$this->facility && $user->facility_id) {
            $this->facility = $user->facility;
        }
        if (!$this->branch && $user->assigned_branch_id) {
            $this->branch = $user->assignedBranch;
        }
    }

    public function envelope(): Envelope
    {
        $facilityName = $this->facility?->name ?? 'the facility';
        
        return new Envelope(
            subject: "Welcome to {$facilityName} - Your Account Information",
        );
    }

    public function content(): Content
    {
        $userName = trim(($this->user->first_name ?? '') . ' ' . ($this->user->last_name ?? '')) ?: $this->user->name;
        $facilityName = $this->facility?->name ?? 'the facility';
        $facilityAddress = $this->facility?->address;
        $facilityPhone = $this->facility?->phone;
        $facilityEmail = $this->facility?->email;
        $branchName = $this->branch?->name;
        $branchAddress = $this->branch?->address;
        $userRole = ucfirst($this->user->role ?? 'Staff');
        $userEmail = $this->user->email;
        
        return new Content(
            text: 'mail.welcome-to-facility',
            with: [
                'userName' => $userName,
                'facilityName' => $facilityName,
                'facilityAddress' => $facilityAddress,
                'facilityPhone' => $facilityPhone,
                'facilityEmail' => $facilityEmail,
                'branchName' => $branchName,
                'branchAddress' => $branchAddress,
                'userRole' => $userRole,
                'userEmail' => $userEmail,
                'hasTemporaryPassword' => !empty($this->temporaryPassword),
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => config('app.url') . '/login',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
