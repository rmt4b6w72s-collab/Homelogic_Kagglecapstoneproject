<?php

namespace App\Mail;

use App\Models\Medication;
use App\Models\Resident;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LateMedicationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Medication $medication,
        public Resident $resident,
        public string $scheduledTime
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Late Medication Alert - ' . ($this->medication->drug?->name ?? $this->medication->name),
        );
    }

    public function content(): Content
    {
        $residentName = trim(($this->resident->first_name ?? '') . ' ' . ($this->resident->last_name ?? ''));
        $medicationName = $this->medication->drug?->name ?? $this->medication->name;
        
        return new Content(
            text: 'mail.late-medication',
            with: [
                'residentName' => $residentName,
                'medicationName' => $medicationName,
                'scheduledTime' => $this->scheduledTime,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
