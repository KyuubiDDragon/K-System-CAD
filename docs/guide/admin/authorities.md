# Authority Management

Manage authorities (organizations) in the K-Systems multi-tenant environment.

## Overview

Authority Management allows Super Administrators to manage multiple organizations within K-Systems. Each authority represents a separate organization with isolated data, users, and settings.

**Key Features:**
- Create new authorities with admin accounts
- Edit authority information
- Manage feature permissions per authority
- Delete authorities
- Control which system features each authority can access

**Required Permission:** `SUPER_ADMIN` or `ADMIN_AUTHORITY`

## Access

**Desktop:** Start Menu → Administration → Authorities
**Menu:** Administration → Authorities
**Route:** `/admin/authorities`

## Understanding Authorities

### What is an Authority?

**Authority (Organization) represents:**
- A separate organization/tenant
- Complete data isolation from other authorities
- Independent user accounts
- Separate configuration and settings
- Custom feature access

**Examples:**
- Fire Department District 1
- Police Department City A
- EMS Service Region B
- Corporate Office

### Multi-Tenant System

**K-Systems is multi-tenant:**
- Multiple organizations in one installation
- Complete data separation
- Shared codebase
- Independent authorities
- Authority-specific permissions

**Data Isolation:**
- Every database record has `authority_id`
- Users belong to one authority
- Cannot see other authority's data
- Super Admin can manage all authorities

## Authority List

### Interface

**Authority Table Columns:**
- **ID**: Authority identifier (chip display)
- **Name**: Internal identifier
- **Display Name**: Public-facing name
- **Description**: Authority description
- **Status**: Active/Inactive (chip display)
- **Actions**: Edit, Manage Features, Delete

**Search:**
- Search by name, display name, or description
- Results filter automatically

**Sorting:**
- Click column headers to sort
- Sort by ID, name, display name, or status

## Creating Authorities

### Create New Authority

**Add Authority:**
1. Click **+ Create Authority** button
2. Fill authority information:

**Required Fields:**
- **Name** (required)
  - Internal identifier
  - Used in system backend
  - Example: "fire_dept_1", "lspd"
  - Lowercase, underscores recommended
- **Display Name** (required)
  - Public-facing name
  - Shows in interface
  - Example: "Los Santos Fire Department"
- **Description** (optional)
  - Describe the organization
  - Purpose and notes
  - Textarea field

**Status:**
- **Active** switch
  - Enable to allow authority access
  - Disable to suspend authority

### Admin Account Creation

**Create Admin Account (Optional):**
1. Enable **Create Admin Account** switch
2. Admin account fields appear:

**Admin Account Fields:**
- **Admin Username** (required if enabled)
  - Username for authority administrator
  - Minimum 3 characters
  - Alphanumeric + underscore only
  - Example: "admin", "firechiefadmin"
- **Admin Email** (required if enabled)
  - Email for admin account
  - Must be valid email format
  - Used for notifications
- **Admin Password** (required if enabled)
  - Initial admin password
  - Minimum 8 characters
  - Show/hide toggle available
  - Admin should change on first login

**What Happens:**
- Admin account created automatically
- Assigned admin role for this authority
- Can login immediately after authority creation
- Has full authority administration permissions

3. Click **Create Authority**
4. Authority created
5. Admin account created (if enabled)

## Editing Authorities

### Edit Authority Information

**Update Authority:**
1. Click **Edit** button on authority row
2. Edit authority dialog opens

**Editable Fields:**
- **Name**: Internal identifier
- **Display Name**: Public name
- **Description**: Authority description
- **Active**: Enable/disable status

3. Click **Save**
4. Changes applied immediately

**Note:** Admin account creation only available when creating new authority, not when editing.

## Managing Features

### Configure Feature Access

**Manage Features:**
1. Click **Manage Features** button on authority row
2. Manage Features dialog opens

**Features List:**
- Shows all available system features
- Checkbox for each feature
- Feature name, description, and code displayed
- Search features (filters list)

**Enable/Disable Features:**
1. Check features to enable
2. Uncheck features to disable
3. Search to find specific features quickly
4. Click **Save**
5. Feature permissions updated

**How Feature Access Works:**
- Enabled features available to authority users
- Disabled features hidden from authority
- Controls menu items, routes, and functionality
- Combined with user role permissions

**Common Features:**
- Desktop Mode
- Employee Management
- Reports
- Documents
- Calendar
- Messages
- Dispatch
- Training
- Fire Protection
- Map Integration
- And more...

### Feature Permissions

**Feature + Role System:**
- Authority must have feature enabled (authority level)
- User must have permission (role level)
- Both required for access
- Provides two-level access control

**Example:**
- Authority: Training Module enabled
- User: Has `READ_TRAINING` permission
- Result: User can access training module

If either is missing, user cannot access feature.

## Deleting Authorities

### Remove Authority

**Delete Authority:**
1. Click **Delete** button on authority row
2. Confirmation dialog appears:
   - Shows authority name
   - Warning about permanent deletion
3. Click **Delete** to confirm
4. Authority deleted permanently

**Warning:**
- Deletion is permanent and cannot be undone
- All authority data will be deleted:
  - Users
  - Employees
  - Reports
  - Documents
  - All records with this authority_id
- Backup before deleting if data needs to be preserved

**Recommendation:** Deactivate instead of deleting to preserve data.

## Permission Refresh

### Refresh User Permissions

**After managing features:**
- User permissions may need refresh
- System may prompt to refresh permissions
- Ensures users immediately see feature changes

**Manual Refresh:**
- Users can logout and login again
- Or wait for session refresh
- Permission cache updates

## API Reference

### Authority Endpoints

```http
GET /admin/authority/index.php?action=getAuthorities
Returns: List of all authorities

GET /admin/authority/index.php?action=getFeatures
Returns: List of all system features

GET /admin/authority/index.php?action=getAuthorityFeatures&id={id}
Returns: Features enabled for specific authority

POST /admin/authority/index.php?action=createAuthority
Body: {
  name,
  display_name,
  description,
  active,
  create_admin_account?,
  admin_username?,
  admin_email?,
  admin_password?
}
Returns: Success message and authority ID

POST /admin/authority/index.php?action=updateAuthority
Body: { id, name, display_name, description, active }
Returns: Success message

POST /admin/authority/index.php?action=deleteAuthority
Body: { id }
Returns: Success message

POST /admin/authority/index.php?action=updateAuthorityFeatures
Body: { authority_id, feature_ids[] }
Returns: Success message
```

## TypeScript Interfaces

```typescript
interface Authority {
  id: number | null;
  name: string;
  display_name: string;
  description: string;
  active: boolean;
  created_at?: string;
  updated_at?: string;
  // For creation only:
  create_admin_account?: boolean;
  admin_username?: string;
  admin_email?: string;
  admin_password?: string;
}

interface Feature {
  id: number;
  name: string;
  code: string;
  description: string;
  created_at?: string;
  updated_at?: string;
}
```

## Best Practices

**Do:**
✅ Use descriptive display names
✅ Create admin account with authority
✅ Document authority purpose in description
✅ Configure features based on needs
✅ Deactivate instead of delete when possible
✅ Regular review of feature access
✅ Backup before deleting authorities
✅ Use strong admin passwords

**Don't:**
❌ Delete authorities without backup
❌ Use confusing internal names
❌ Enable unnecessary features
❌ Share admin account credentials
❌ Forget to configure features after creation
❌ Use weak admin passwords
❌ Leave inactive authorities active

## Troubleshooting

### Authority Not Accessible

**Check:**
1. Verify authority status is Active
2. Check admin account created
3. Verify admin credentials
4. Check feature permissions configured
5. Review browser console for errors

### Features Not Available

**Solutions:**
1. Open Manage Features dialog
2. Verify feature is checked/enabled
3. Save feature permissions
4. User logout and login again
5. Check user role permissions also

### Admin Account Not Working

**Check:**
1. Verify admin account was created (check during authority creation)
2. Check username and password correct
3. Verify authority is active
4. Check admin has appropriate role assigned
5. Try password reset if needed

### Changes Not Saving

**Solutions:**
1. Check all required fields filled
2. Verify form validation passed
3. Check browser console for errors
4. Check network connection
5. Retry operation

## Limitations

The following features mentioned in some documentation **do not exist** in the current codebase:

❌ **Not Available:**
- Authority cloning/duplication
- Bulk feature assignment
- Authority templates
- Feature usage analytics
- Authority migration tools
- License management per authority
- Billing/subscription management
- Storage quota management
- User limit enforcement
- Authority statistics dashboard
- Cross-authority operations
- Authority merge functionality
- Bulk authority operations
- Authority import/export
- Authority audit logs
- Custom domain configuration in UI
- Subdomain management
- Database isolation controls
- Super admin detailed system monitoring

---

## Next Steps

- [User Management](/guide/admin/users) - Create users for authority
- [Role Management](/guide/admin/roles) - Configure roles
- [Authority Branding](/guide/admin/authority-branding) - Customize authority appearance
- [System Settings](/guide/admin/system-settings) - Configure system settings
