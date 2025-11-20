# Facility Owner Login Guide

## How Facility Owners Access the System

### Step 1: Account Creation by Super Admin

When a Super Admin creates a facility, they can optionally create the facility owner account at the same time:

1. Go to `/app/super-admin/facilities`
2. Click "+ Add Facility"
3. Fill in:
   - **Basic Information**: Facility name, address, phone, email
   - **Branding & Customization**: Logo, colors, subdomain
   - **Facility Owner Account** (Optional but recommended):
     - Owner Name
     - Owner Email (this will be the login email)
     - Owner Role (Administrator/Manager/Clinical Supervisor)
     - Password (minimum 8 characters)
   - **Initial Branch Setup**: Branch name and address
4. Click "Create"

The system will automatically:
- Create the facility
- Create the owner account
- Create the initial branch
- Link everything together
- Set the facility as "approved"

### Step 2: Facility Owner Login

Once the account is created, the facility owner can log in:

#### Login URL:
```
http://yourdomain.com/app/login
```
or for local development:
```
http://127.0.0.1:8000/app/login
```

#### Login Credentials:
- **Email**: The email address entered during facility creation (owner_email)
- **Password**: The password set during facility creation

#### Login Process:
1. Navigate to the login page
2. Enter the owner email address
3. Enter the password
4. Click "Sign In"
5. The system will:
   - Authenticate the user
   - Verify the account is active
   - Load facility context
   - Redirect to the dashboard

### Step 3: Dashboard Access

After successful login, facility owners are redirected to:

- **Main Dashboard**: `/app/dashboard`
- **Facility-Specific View**: All data shown is filtered to their facility only
- **Role-Based Navigation**: Based on their role (Administrator, Manager, Clinical Supervisor)

### What Facility Owners Can Access

Facility owners with **Administrator** role have full access to:

- **Dashboard**: Overview of residents, appointments, vitals, medications
- **Administration**:
  - Residents management
  - Facilities (view their own facility)
  - Branches (manage their branches)
  - Users (manage their facility users)
  - Roles & Permissions
  - Drugs
  - Vital Ranges
  - Employee Documents
  - Activity Logs
  - Deactivated Records
- **Resident Management**:
  - Create and manage residents
  - View resident details
  - Manage assessments
  - Schedule appointments
- **Operations**:
  - Medications management
  - Vitals tracking
  - Sleep records
  - Housekeeping
  - Fire drills
  - Grocery status
  - Pharmacy management
- **Reports**: All reporting features for their facility

### Data Isolation

- Facility owners can **only see data from their own facility**
- Each facility's data is completely isolated from other facilities
- The system automatically filters all queries based on the user's `facility_id`

### If Owner Account Wasn't Created During Facility Setup

If the Super Admin created the facility without creating the owner account:

1. The Super Admin can:
   - Go to `/app/administration/users`
   - Click "Add User"
   - Select the facility from the dropdown
   - Fill in owner details
   - Set role to "Administrator" or appropriate role
   - Create the account

2. Or the Super Admin can edit the facility and add the owner later through the Users management section.

### Password Reset

If a facility owner forgets their password:

1. Contact the Super Admin
2. Super Admin can reset the password through:
   - `/app/administration/users`
   - Edit the user
   - Change the password

### Subdomain Access (Future Feature)

When subdomains are configured, facility owners can access via:
```
http://facility-subdomain.yourdomain.com/app/login
```

This will automatically set their facility context based on the subdomain.

## Security Notes

- All logins are logged in the Activity Logs
- Accounts can be deactivated by Super Admin
- Passwords must be at least 8 characters
- Session timeout: 30 minutes of inactivity
- All API requests require authentication token

## Support

If facility owners have issues logging in:
1. Verify the account is active
2. Check email and password are correct
3. Contact Super Admin for password reset
4. Check Activity Logs for login attempts

