# User Management

Manage user accounts, assign roles, and configure user settings in K-Systems.

## Overview

User Management allows administrators to:
- Create and edit user accounts
- Assign roles (groups) to users
- Link users to employee records
- Ban/unban user accounts
- Reset user passwords
- Configure user email template settings

**Required Permission:** `ADMIN_USER`

## Access

**Desktop:** Start Menu → Administration → User Management
**Menu:** Administration → Users
**Route:** `/admin/users`

## User List

### Interface

**User Table Columns:**
- **Username**: Login name
- **Email**: Email address
- **Linked Employee**: Employee name with service number
- **Groups**: Assigned roles (displayed as chips)
- **Status**: Active/Banned status
- **Mail Header**: Email header image link
- **Mail Footer**: Email footer image link
- **Mail Header Neutral**: Neutral email header link
- **Mail Footer Neutral**: Neutral email footer link
- **Signature**: Email signature image link
- **Last Login**: Date and time of last login
- **Actions**: Edit, Ban/Unban, Reset Password

### Filters

**Status Filter:**
- All Users
- Active Users
- Banned Users

### Search

**Search Users by:**
- Username
- Email address
- Employee name (if linked)

**How to Search:**
1. Enter search term in search box
2. Results filter automatically
3. Clear search to show all users

## Creating Users

### Add New User

**Create User:**
1. Click **+ Add User** button
2. Fill required fields:

**Required Fields:**
- **Username**: Unique login name
  - Must be unique
  - Letters, numbers, underscore allowed
- **Email**: Valid email address
  - Must be unique
  - Used for notifications
- **Groups**: Select at least one role
  - Multi-select dropdown
  - At least one role required
  - Determines user permissions
- **Password**: Initial password
  - Minimum 8 characters
  - User can change after login

3. Click **Create User**
4. User account created
5. User can now login

**Password Requirements:**
- Minimum 8 characters
- Must be set during creation
- User should change on first login

## Editing Users

### Edit User Account

**Edit User:**
1. Click **Edit** button on user row
2. Modify fields:

**Editable Fields:**
- **Username**: Login name
- **Email**: Email address
- **Linked Employee**: Link to employee record
  - Autocomplete search
  - Optional
  - Links user account to employee
- **Groups**: Role assignments
  - Multi-select
  - At least one required

**Email Template Settings:**
- **Mail Header**: URL to email header image
- **Mail Footer**: URL to email footer image
- **Mail Header Neutral**: URL to neutral header
- **Mail Footer Neutral**: URL to neutral footer
- **Signature**: URL to signature image

3. Click **Save**
4. Changes applied immediately

**Link to Employee:**
1. Click **Linked Employee** field
2. Start typing employee name
3. Select from autocomplete results
4. Employee linked to user account
5. Shares profile information

**Copy Image Links:**
- Click copy icon next to image URL
- URL copied to clipboard
- Paste into email template or other location

## User Status Management

### Ban User

**Ban User Account:**
1. Click **Ban** button on user row
2. Confirm ban action
3. User account banned
4. User cannot login
5. All sessions terminated

**Effects of Banning:**
- User logged out immediately
- Cannot login again
- Account preserved
- Can be unbanned later

### Unban User

**Restore User Access:**
1. Click **Unban** button on banned user
2. Confirm unban action
3. User account reactivated
4. User can login again

### Active Status

Users are marked as **Active** by default when created. Only banned users show **Banned** status.

## Password Management

### Reset User Password

**Reset Password:**
1. Click **Reset Password** button
2. Enter new password:
   - Minimum 8 characters
   - Enter in both fields
   - Must match
3. Click **Reset Password**
4. Password changed immediately
5. User can login with new password

**Password Validation:**
- Both fields must match
- Minimum 8 characters required
- Form validates before submission

**Best Practice:** User should change password on first login after reset.

## Role Assignment

### Assign Roles

**Add Roles to User:**
1. Edit user
2. Click **Groups** field
3. Select one or more roles
4. At least one role required
5. Save changes

**Available Roles:**
- Configured in Role Management
- Based on authority permissions
- Different roles have different permissions
- Users can have multiple roles

**Multiple Roles:**
- User permissions combine from all roles
- Higher power level takes precedence
- Provides flexible access control

See [Role Management](/guide/admin/roles) for role configuration.

## API Reference

### User Endpoints

```http
GET /admin/user?action=getUserOverview
Returns: List of all users with details

GET /admin/user?action=getGroups
Returns: List of all available roles/groups

POST /admin/user?action=addNewUser
Body: { username, email, groups[], password }
Returns: Success message and user ID

POST /admin/user?action=updateUser
Body: { id, username, email, linked_employee?, groups[], mail_header?, mail_footer?, mail_header_neutral?, mail_footer_neutral?, signature? }
Returns: Success message

POST /admin/user?action=bannUser
Body: { id }
Returns: Success message

POST /admin/user?action=unbannUser
Body: { id }
Returns: Success message

POST /admin/user?action=resetPassword
Body: { id, password }
Returns: Success message
```

### Employee Endpoint

```http
GET /admin/employee/index.php?action=getEmployees
Returns: List of employees for linking
```

## TypeScript Interfaces

```typescript
interface User {
  id: number;
  username: string;
  email: string;
  groups: Group[];
  name: string; // Format: [servicenumber] name
  banned: 0 | 1;
  mail_header: string;
  mail_footer: string;
  mail_header_neutral: string;
  mail_footer_neutral: string;
  signature: string;
  last_login: string;
  linked_employee: number | null;
}

interface Group {
  id: number;
  name: string;
}
```

## Best Practices

**Do:**
✅ Assign appropriate roles based on job function
✅ Link users to employee records when applicable
✅ Use strong passwords (more than minimum)
✅ Review user list regularly
✅ Remove or ban unused accounts
✅ Set up email templates for professional communications

**Don't:**
❌ Share user accounts
❌ Use weak passwords
❌ Give unnecessary role permissions
❌ Forget to ban terminated employee accounts
❌ Leave test accounts active

## Troubleshooting

### User Can't Login

**Check:**
1. Verify user status is Active (not Banned)
2. Confirm username and password correct
3. Check if user has at least one role assigned
4. Verify account exists in user list

### Changes Not Saving

**Solutions:**
1. Check all required fields filled
2. Verify at least one group selected
3. Check email format is valid
4. Ensure username is unique
5. Check browser console for errors

### Employee Link Not Working

**Solutions:**
1. Verify employee exists in employee list
2. Use autocomplete to search
3. Check permissions to access employees
4. Refresh employee list if needed

## Limitations

The following features mentioned in some documentation **do not exist** in the current codebase:

❌ **Not Available:**
- Bulk user operations (select multiple users)
- User import from CSV/Excel
- User export functionality
- User activity logs view within User Management
- Advanced filtering beyond status (by role, department, date)
- User profile pictures upload
- Custom user fields
- User templates
- Password policy configuration in UI
- Session management (view active sessions)
- Two-factor authentication setup
- Permission preview before assigning roles
- Notification preferences per user
- User onboarding wizard

---

## Next Steps

- [Role Management](/guide/admin/roles) - Configure roles and permissions
- [Authority Management](/guide/admin/authorities) - Manage authorities
- [Employee Management](/guide/employee-management) - Link employees to users
