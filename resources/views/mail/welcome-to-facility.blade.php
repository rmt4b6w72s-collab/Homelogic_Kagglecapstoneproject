Hello {{ $userName }},

Welcome to {{ $facilityName }}! Your account has been created and you are now part of our team.

ACCOUNT INFORMATION
-------------------
Email: {{ $userEmail }}
Role: {{ $userRole }}
@if($hasTemporaryPassword)
Temporary Password: {{ $temporaryPassword }}

Please change your password after your first login for security.
@endif

FACILITY INFORMATION
--------------------
Facility: {{ $facilityName }}
@if($facilityAddress)
Address: {{ $facilityAddress }}
@endif
@if($facilityPhone)
Phone: {{ $facilityPhone }}
@endif
@if($facilityEmail)
Email: {{ $facilityEmail }}
@endif

@if($branchName)
BRANCH ASSIGNMENT
-----------------
Branch: {{ $branchName }}
@if($branchAddress)
Address: {{ $branchAddress }}
@endif
@endif

NEXT STEPS
----------
1. Log in to your account at: {{ $loginUrl }}
@if($hasTemporaryPassword)
2. Change your temporary password
@endif
3. Review your assigned tasks and responsibilities
4. Familiarize yourself with the facility and branch information

If you have any questions or need assistance, please contact your supervisor or facility administrator.

We're excited to have you on board!

Best regards,
{{ config('mail.from.name') }}

