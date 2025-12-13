<?php

use App\Models\User;
use App\Models\Incident;
use App\Models\Resident;
use Illuminate\Support\Facades\Auth;

// Load Laravel application
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // 1. Login as Musa (User ID 1)
    $user = User::find(1);
    if (!$user) {
        die("User 1 not found.\n");
    }
    Auth::login($user);
    echo "Logged in as: " . $user->name . " (Role: " . $user->role . ")\n";

    // Bind facility to app container (simulate middleware)
    $facility = \App\Models\Facility::find($user->facility_id);
    if ($facility) {
        app()->instance('facility', $facility);
        echo "Bound facility: " . $facility->name . "\n";
    }

    // 2. Find a resident to attach incident to
    $resident = Resident::first();
    if (!$resident) {
        die("No residents found.\n");
    }
    echo "Using resident: " . $resident->first_name . " " . $resident->last_name . "\n";

    // 3. Attempt to create incident
    // Create a dummy file for upload simulation
    $dummyFilePath = __DIR__ . '/dummy_attachment.txt';
    file_put_contents($dummyFilePath, 'This is a test attachment.');
    $uploadedFile = new \Illuminate\Http\UploadedFile(
        $dummyFilePath,
        'dummy_attachment.txt',
        'text/plain',
        null,
        true
    );

    // Mock request file handling (since we can't easily mock Request::allFiles() in this script without more setup)
    // Instead, we will manually create the attachment after incident creation to test the model logic
    // But wait, the controller does this. We should try to use the controller if possible, or replicate its logic exactly.
    
    // Let's replicate the controller logic for attachment creation
    echo "Attempting to create incident with attachment logic...\n";
    
    $incidentData = [
        'resident_id' => $resident->id,
        'branch_id' => $resident->branch_id,
        'incident_type' => 'Fall',
        'description' => 'Test incident description with attachment',
        'incident_date' => now(),
        'severity' => 'low',
        'priority' => 'low',
        'status' => 'open',
        'reported_by' => $user->id,
        'incident_number' => 'TEST-ATTACH-' . time(),
    ];

    $incident = Incident::create($incidentData);
    echo "Incident created: " . $incident->id . "\n";

    // Simulate attachment creation
    echo "Simulating attachment creation...\n";
    $storedPath = 'incident-attachments/dummy_' . time() . '.txt';
    // We won't actually store the file to avoid clutter, just test the DB insertion
    
    \App\Models\IncidentAttachment::create([
        'incident_id' => $incident->id,
        'file_path' => $storedPath,
        'file_name' => 'dummy_attachment.txt',
        'file_type' => 'document',
        'file_size' => 1024,
        'mime_type' => 'text/plain',
        'uploaded_by' => $user->id,
        'description' => 'Test attachment description',
    ]);
    echo "Attachment created successfully.\n";


    // 4. Try to load relationships (like controller does)
    echo "Loading relationships...\n";
    $incident->load(['resident', 'branch', 'reportedBy', 'assignedTo', 'attachments']);
    echo "Relationships loaded.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
