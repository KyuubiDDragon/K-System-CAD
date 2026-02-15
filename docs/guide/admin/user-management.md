# User Management

Complete guide for administrators to manage user accounts in K-Systems.

---

## Overview

User management involves:
- Creating and deleting user accounts
- Assigning roles and permissions
- Enabling features per user
- Managing passwords and access
- Monitoring user activity

**Required Permission:** `ADMIN_READ_USERS`

---

## Creating Users

### Step 1: Access User Management

1. Navigate to **Admin > Users** or **Admin > User Management**
2. View list of existing users

### Step 2: Create New User

![Create User](../../public/images/admin/admin-user-create.png)

1. Click **"+ New User"** or **"Create User"**

2. **Enter basic information:**
   - **Username** - Unique identifier (e.g., john.doe)
   - **Email** - User's email address
   - **Password** - Temporary password (user should change on first login)
   - **Confirm Password**

3. **Select Authority/Organization** - Which organization user belongs to

4. **Assign Initial Role** - Select from available roles:
   - FULL_ADMIN - Full access
   - System Administrator - System-wide admin
   - MEMBER - Regular user
   - Custom roles

5. **Click "Create"** or **"Save"**

### Username Best Practices

**Format Options:**
- `firstname.lastname` (recommended)
- `firstinitiallastname` (jdoe)
- `employeenumber`

**Rules:**
- Must be unique across the system
- No spaces
- Use lowercase
- Alphanumeric and periods/underscores only

---

## Managing User Permissions

### Understanding Permission Levels

K-Systems uses **role-based access control (RBAC)**:

1. **Permissions** - Specific rights (e.g., READ_EMPLOYEE, WRITE_REPORT)
2. **Roles** - Collections of permissions (e.g., Admin, Manager, Member)
3. **Features** - Module access flags (e.g., employee, reports, calendar)

**User Access = Roles + Permissions + Features**

### Assigning Roles

![Assign Roles](../../public/images/admin/admin-user-permissions.png)

1. **Edit user** account
2. Go to **"Roles"** tab or section
3. **Select roles** to assign:
   - Users can have multiple roles
   - Permissions are cumulative
4. **Save changes**

### Adding Individual Permissions

For specific permissions not covered by roles:

1. **Edit user** account
2. Go to **"Permissions"** tab
3. **Check permissions** to grant:
   - READ_* (view access)
   - WRITE_* (create/edit access)
   - DELETE_* (delete access)
   - ADMIN_* (administrative access)
4. **Save**

### Enabling Features

Features control which modules a user can access:

1. **Edit user** account
2. Go to **"Features"** tab
3. **Enable features:**
   - employee
   - document
   - reports
   - calendar
   - dispatch
   - training
   - mail
   - map
   - whiteboard
   - todo
   - (and more)
4. **Save**

---

## Editing Users

### Updating User Information

1. **Find user** in user list
2. **Click "Edit"** or user name
3. **Update fields:**
   - Email address
   - Name
   - Role assignments
   - Permissions
   - Features
4. **Save changes**

### Resetting Passwords

**When user forgets password:**

1. **Edit user** account
2. **Click "Reset Password"** or navigate to password section
3. **Enter new temporary password**
4. **Save**
5. **Inform user** of new password
6. **User must change** password on next login

**Password Requirements:**
- Minimum 8 characters (recommended)
- Include uppercase, lowercase, numbers (recommended)
- Avoid common words

### Activating/Deactivating Users

**To temporarily disable access without deleting:**

1. **Edit user** account
2. **Set status** to:
   - **Active** - Can log in
   - **Inactive** - Cannot log in
   - **Banned** - Permanently blocked
3. **Save**

**Use cases:**
- Employee on leave (Inactive)
- Terminated employee (Banned or delete)
- Temporary suspension (Inactive)

---

## Deleting Users

### When to Delete vs Deactivate

**Deactivate (preferred):**
- Employee on leave
- Temporarily suspended
- May return in future
- Need to preserve historical data

**Delete:**
- Terminated permanently
- Duplicate accounts
- Test accounts

### Deleting a User

1. **Find user** in list
2. **Click "Delete"** or trash icon
3. **Confirm deletion**
4. **User account removed**

::: warning Data Preservation
Consider the impact on historical data. Reports, documents, and messages created by the user may reference the deleted account. Consider deactivation instead.
:::

---

## Common User Management Tasks

### Bulk User Creation

For creating multiple users (e.g., new hire class):

**If supported:**
1. Go to **User Management**
2. **Click "Import"** or **"Bulk Create"**
3. **Upload CSV** with user data
4. **Map fields** (username, email, role, etc.)
5. **Review** and confirm
6. **Create** all users

**CSV Format Example:**
```csv
username,email,role,password
john.doe,john@example.com,MEMBER,TempPass123
jane.smith,jane@example.com,MEMBER,TempPass456
```

### Changing User Roles

**Promotions/Role Changes:**

1. **Edit user**
2. **Remove old role** (if exclusive)
3. **Add new role**
4. **Adjust permissions** if needed
5. **Save**

**User will need to log out and back in** for changes to take full effect.

### Transferring User Ownership

When user leaves and another takes over responsibilities:

1. **Create new user** account for replacement
2. **Assign same roles/permissions**
3. **Transfer data** (if needed):
   - Reassign reports
   - Reassign documents
   - Update employee record link
4. **Deactivate old user**

---

## User Monitoring

### Viewing User Activity

**Check user login history:**
1. Go to **Admin > Logs** or **User Activity**
2. **Filter by user**
3. **Review:**
   - Last login
   - Login attempts
   - Failed logins
   - Active sessions

**Monitor usage:**
- Which modules user accesses
- Frequency of use
- Data created/modified

### Managing Active Sessions

**If user is stuck or needs to be logged out:**

1. Go to **Admin > Active Sessions**
2. **Find user** session
3. **Click "Terminate"** or **"Force Logout"**
4. User will be logged out immediately

---

## Security Best Practices

### Password Policies

**Enforce strong passwords:**
- Minimum length: 8-12 characters
- Require complexity (upper, lower, number, special)
- Expire passwords regularly (90 days)
- Prevent password reuse (last 5)

**Configure in:** Admin > Settings > Security

### Access Review

**Regularly review:**
- User access levels (quarterly)
- Inactive accounts (monthly)
- Admin accounts (verify necessity)
- Permission assignments (audit annually)

**Remove access promptly:**
- When employee leaves
- When role changes
- When no longer needed

### Multi-Factor Authentication (MFA)

If supported:
1. **Enable MFA** for admin accounts
2. **Encourage** for all users
3. **Configure** in Admin > Security Settings

---

## Troubleshooting

### User Can't Log In

**Check:**
- Account is Active (not Inactive/Banned)
- Username is correct
- Password is correct (offer to reset)
- Authority selection is correct
- No browser/cookie issues

### User Missing Permissions

**Check:**
- Role assignments are correct
- Features are enabled
- Specific permissions granted if needed
- User has logged out and back in (to refresh)

### User Sees "Unauthorized" Errors

**Causes:**
- Permission removed recently
- Feature disabled
- Role changed
- Session expired

**Solutions:**
- Verify current permissions
- Re-add permissions if needed
- Ask user to log out and back in

---

## User Management Checklist

**New User Setup:**
- [ ] Create user account
- [ ] Assign appropriate role
- [ ] Enable required features
- [ ] Set temporary password
- [ ] Send welcome email with credentials
- [ ] Verify user can log in
- [ ] Check user has correct access

**User Modification:**
- [ ] Update user information
- [ ] Adjust roles as needed
- [ ] Modify permissions
- [ ] Enable/disable features
- [ ] Save changes
- [ ] Notify user of changes

**User Offboarding:**
- [ ] Deactivate or delete account
- [ ] Transfer ownership of data (if needed)
- [ ] Revoke all access
- [ ] Document in HR records
- [ ] Remove from distribution lists

---

## Related Guides

- **[Roles Management](/guide/admin/roles)** - Managing roles and role permissions
- **[Permissions Guide](/guide/admin/permissions)** - Understanding permissions
- **[Employee Onboarding Workflow](/guide/workflows/employee-onboarding)** - Complete onboarding process

---

## Quick Reference

**Create User:**
1. Admin > Users > + New User
2. Enter username, email, password
3. Assign role
4. Enable features
5. Save

**Edit User:**
1. Admin > Users > Find user
2. Click Edit
3. Update information
4. Save

**Reset Password:**
1. Edit user
2. Reset Password section
3. Enter new password
4. Save and inform user

**Required Permission:**
- ADMIN_READ_USERS

---

**Last Updated:** 2025-10-27
