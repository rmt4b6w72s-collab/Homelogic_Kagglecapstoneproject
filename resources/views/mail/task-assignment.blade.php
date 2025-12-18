Hello,

You have been assigned a new task:

Task: {{ $taskTitle }}
Area: {{ $areaName }}
Scheduled Date: {{ $scheduledDate }}
Status: {{ $status }}
@if($estimatedMinutes)
Estimated Time: {{ $estimatedMinutes }} minutes
@endif

Instructions:
{{ $taskInstructions }}

Assigned by: {{ $assignedByName }}

Please log in to your account to view full details and update the task status.

Thank you,
{{ config('mail.from.name') }}

