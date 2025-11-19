<?php

namespace App\Mail;

use App\Models\Resident;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LateVitalSignNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Resident $resident,
        public int $hoursOverdue
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Late Vital Sign Alert - ' . trim(($this->resident->first_name ?? '') . ' ' . ($this->resident->last_name ?? '')),
        );
    }

    public function content(): Content
    {
        $residentName = trim(($this->resident->first_name ?? '') . ' ' . ($this->resident->last_name ?? ''));
        
        return new Content(
            text: 'mail.late-vital-sign',
            with: [
                'residentName' => $residentName,
                'hoursOverdue' => $this->hoursOverdue,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
