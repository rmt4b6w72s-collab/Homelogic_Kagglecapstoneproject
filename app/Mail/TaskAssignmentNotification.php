<?php

namespace App\Mail;

use App\Models\CleaningTaskAssignment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class TaskAssignmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CleaningTaskAssignment $assignment,
        public ?User $assignedBy = null
    ) {}

    public function envelope(): Envelope
    {
        $taskTitle = $this->assignment->task?->title ?? 'New Task';
        $scheduledDate = Carbon::parse($this->assignment->scheduled_date)->format('M d, Y');
        
        return new Envelope(
            subject: "New Task Assigned: {$taskTitle} - {$scheduledDate}",
        );
    }

    public function content(): Content
    {
        $task = $this->assignment->task;
        $area = $task?->area;
        $scheduledDate = Carbon::parse($this->assignment->scheduled_date)->format('l, F j, Y');
        $assignedByName = $this->assignedBy?->name ?? 'Administrator';
        
        return new Content(
            text: 'mail.task-assignment',
            with: [
                'taskTitle' => $task?->title ?? 'Task',
                'taskInstructions' => $task?->instructions ?? 'No specific instructions provided.',
                'areaName' => $area?->name ?? 'General',
                'scheduledDate' => $scheduledDate,
                'assignedByName' => $assignedByName,
                'estimatedMinutes' => $task?->estimated_minutes,
                'status' => ucfirst($this->assignment->status ?? 'assigned'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
