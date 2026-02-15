# Permissions Guide

Understanding and managing the K-Systems permission system.

---

## Permission System Overview

K-Systems uses a comprehensive **Role-Based Access Control (RBAC)** system with three layers:

1. **Permissions** - Specific rights to perform actions
2. **Roles** - Collections of permissions
3. **Features** - Module-level access flags

**Access Control Formula:**
```
User Access = Assigned Roles + Individual Permissions + Enabled Features
```

---

## Permission Structure

### Permission Naming Convention

Permissions follow a consistent pattern:

**Format:** `ACTION_MODULE` or `ACTION_MODULE_SPECIFIC`

**Examples:**
- `READ_EMPLOYEE` - View employee records
- `WRITE_REPORT` - Create/edit reports
- `DELETE_DOCUMENT` - Delete documents
- `ADMIN_READ_USERS` - Manage user accounts

### Permission Categories

**General Permissions:**
- `ALL_PERMISSIONS` - Grants access to everything (use carefully!)
- `CAN_LOGIN` - Basic login ability
- `READ_ACCOUNT` - View own profile
- `WRITE_ACCOUNT` - Modify own profile

**Module Permissions:** (Pattern: ACTION_MODULE)
- READ_* - View/access module
- WRITE_* - Create and edit
- DELETE_* - Remove records

**Admin Permissions:** (Pattern: ADMIN_*)
- `ADMIN_READ_USERS` - User management
- `ADMIN_READ_ROLES` - Role management
- `ADMIN_READ_SETTINGS` - System settings
- `ADMIN_AUTHORITY_SETTINGS` - Organization branding
- `SYSTEM_ADMIN` - Full system administration

---

## Complete Permission List

### Core Modules

**Employee Management:**
- `READ_EMPLOYEE` - View employees
- `WRITE_EMPLOYEE` - Create/edit employees
- `DELETE_EMPLOYEE` - Delete employees
- `ADMIN_READ_EMPLOYEE` - Configure employee settings

**Documents:**
- `READ_DOCUMENT` - Access documents
- `WRITE_DOCUMENT` - Upload/edit documents
- `DELETE_DOCUMENT` - Delete documents
- `READ_DOCUMENT_GLOBAL` - Access Authority Exchange (global documents)
- `READ_DOCUMENT_TRAINING` - Training documents
- `READ_DOCUMENT_DEPARTMENT` - Department documents
- `READ_DOCUMENT_ADMINISTRATION` - Admin documents
- (Plus custom area permissions)

**Reports:**
- `READ_REPORT` - View reports
- `WRITE_REPORT` - Create/edit reports
- `DELETE_REPORT` - Delete reports
- `READ_REPORTTEMPLATE` - Access templates
- `READ_REPORTDEPARTMENT` - Report categories
- `READ_REPORT_STATUS` - Report statuses
- `READ_REPORTCODE` - Report codes
- `ADMIN_REPORTS` - Configure report fields

**Calendar:**
- `READ_CALENDAR` - View calendar
- `WRITE_CALENDAR` - Create/edit events
- `DELETE_CALENDAR` - Delete events

**Messages/Mail:**
- `READ_MAIL` - Access mail system
- `WRITE_MAIL` - Send messages
- `DELETE_MAIL` - Delete messages
- `ADMIN_MAIL` - Mail system configuration

**Training:**
- `READ_TRAINING` - View training
- `WRITE_TRAINING` - Assign/manage training
- `DELETE_TRAINING` - Remove training assignments
- `READ_TEST` - Training tests
- `ADMIN_READ_TRAINING` - Training configuration

**Dispatch & Operations:**
- `READ_DISPATCH` - View dispatch
- `WRITE_DISPATCH` - Create/manage dispatch calls
- `DELETE_DISPATCH` - Remove dispatch records
- `READ_CREW` - Crew management
- `READ_VEHICLE` - Vehicle management

**Company Management:**
- `READ_COMPANY` - View companies/departments
- `WRITE_COMPANY` - Create/edit companies
- `DELETE_COMPANY` - Remove companies
- `READ_COMPANYTYPE` - Company types
- `READ_COMPANY_WEBSITES` - Website management
- `WRITE_COMPANY_WEBSITES` - Edit websites
- `DELETE_COMPANY_WEBSITES` - Delete websites

**Additional Modules:**
- `READ_INVOICE` - Invoices
- `READ_TODO` - Todo lists
- `READ_MAP` - Map access
- `WRITE_MAP` - Add map markers
- `DELETE_MAP` - Delete markers
- `READ_MAP_GLOBAL` - Cross-organization maps
- `READ_WHITEBOARD` - Collaborative whiteboard
- `READ_BLACKBOARD_EMPLOYEE` - Employee bulletin board
- `READ_BLACKBOARD_ADMIN` - Admin announcements
- `READ_BLACKBOARD_GLOBAL` - Global bulletin board
- `READ_APPLICATION` - Job applications
- `WRITE_APPLICATION` - Manage applications
- `READ_FIREPROTECTION` - Fire protection records
- `READ_TEMPLATE` - Document templates
- `READ_CHEATSHEET` - Help/reference materials
- `ADMIN_CHEATSHEET` - Edit help content
- `READ_FILEMANAGER` - File manager access
- `READ_WEATHER` - Weather widget
- `ADMIN_READ_WEATHER` - Weather configuration
- `READ_PERSON_FILE` - Person files
- `READ_VEHICLE_FILE` - Vehicle files
- `READ_APARTMENT_FILE` - Apartment files

---

## Role Management

### Default Roles

**FULL_ADMIN (Power: 999):**
- Has `ALL_PERMISSIONS`
- Complete system access
- Can manage everything

**System Administrator (Power: 9999):**
- Has `SYSTEM_ADMIN`
- Cross-organization access
- Technical administration

**MEMBER (Power: 1):**
- Basic user role
- Assigned specific permissions as needed

**BANNED (Power: 0):**
- No permissions
- Suspended/inactive accounts

### Creating Custom Roles

1. **Navigate to Admin > Roles**
2. **Click "+ New Role"**
3. **Enter role details:**
   - **Name** - Descriptive name (e.g., "Report Manager", "Training Coordinator")
   - **Description** - What this role does
   - **Power Level** - Numerical hierarchy (1-999)
4. **Select permissions** to include
5. **Save**

### Assigning Permissions to Roles

1. **Edit role**
2. **Permission selection interface:**
   - Check boxes for each permission
   - Or use "Select All" for category
3. **Common role templates:**

**Department Manager:**
- READ_EMPLOYEE, WRITE_EMPLOYEE
- READ_CALENDAR, WRITE_CALENDAR
- READ_DOCUMENT, WRITE_DOCUMENT
- READ_REPORT, WRITE_REPORT
- READ_TRAINING, WRITE_TRAINING

**Report Writer:**
- READ_REPORT, WRITE_REPORT
- READ_EMPLOYEE (to assign people)
- READ_DOCUMENT (to attach files)
- READ_CALENDAR (for dates)

**Read-Only User:**
- READ_EMPLOYEE
- READ_DOCUMENT
- READ_REPORT
- READ_CALENDAR
- (No WRITE or DELETE permissions)

---

## Feature Flags

### Understanding Features

**Features** are module-level on/off switches per user. Even with permissions, user needs feature enabled to access module.

**Example:**
- User has `READ_TRAINING` permission
- But `training` feature is disabled
- Result: User cannot access Training module

### Available Features

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
- blackboard
- invoice
- application
- company
- template
- filemanager
- weather
- person_file
- vehicle_file
- apartment_file
- company_websites
- authorities (cross-organization features)
- system_admin

### Enabling Features

**Per User:**
1. Admin > Users > Edit User
2. Features tab
3. Check features to enable
4. Save

**Per Role:** (if supported)
1. Admin > Roles > Edit Role
2. Features section
3. Select default features for role
4. Save

---

## Permission Best Practices

### Principle of Least Privilege

**Grant minimum necessary access:**
- Start with READ permissions
- Add WRITE only if user needs to create/edit
- DELETE only for administrators
- ADMIN permissions very selectively

### Role-Based Assignment

**Use roles instead of individual permissions:**
- Create roles for common job functions
- Assign users to roles
- Easier to manage and audit
- Consistent across similar users

**Example Roles:**
- Fire Chief - FULL_ADMIN
- Company Officer - Department Manager role
- Firefighter - Basic Member role
- Training Officer - Training Coordinator role
- Report Admin - Report Manager role

### Regular Audits

**Quarterly review:**
- Who has what access?
- Is access still appropriate?
- Remove permissions no longer needed
- Check for unused accounts

### Separation of Duties

**Distribute administrative tasks:**
- User Admin - manages user accounts
- Training Admin - manages training only
- Document Admin - manages documents
- Avoid single person with all admin rights (except system admin)

---

## Permission Scenarios

### Scenario 1: New Employee (General Staff)

**Needs:**
- View employees, calendar, documents
- Create reports for their activities
- Access training materials

**Setup:**
- **Role:** MEMBER
- **Permissions:**
  - READ_EMPLOYEE
  - READ_CALENDAR
  - READ_DOCUMENT
  - WRITE_REPORT
  - READ_TRAINING
- **Features:** employee, calendar, document, reports, training

### Scenario 2: Department Manager

**Needs:**
- Manage department employees
- Create/edit reports and documents
- Schedule training
- Full calendar access

**Setup:**
- **Role:** Department Manager (custom)
- **Permissions:**
  - READ_EMPLOYEE, WRITE_EMPLOYEE
  - READ_CALENDAR, WRITE_CALENDAR
  - READ_DOCUMENT, WRITE_DOCUMENT
  - READ_REPORT, WRITE_REPORT
  - READ_TRAINING, WRITE_TRAINING
- **Features:** All department-relevant features

### Scenario 3: Read-Only Auditor

**Needs:**
- View all records for auditing
- Cannot modify anything

**Setup:**
- **Role:** Auditor (custom)
- **Permissions:** All READ_* permissions, no WRITE or DELETE
- **Features:** All modules for viewing

### Scenario 4: Training Coordinator

**Needs:**
- Manage training assignments
- Access training documents
- View employee records
- Limited other access

**Setup:**
- **Role:** Training Coordinator (custom)
- **Permissions:**
  - READ_EMPLOYEE (to see who to train)
  - READ_TRAINING, WRITE_TRAINING, DELETE_TRAINING
  - READ_DOCUMENT, WRITE_DOCUMENT (training documents)
  - ADMIN_READ_TRAINING (configure training)
- **Features:** employee, training, document

---

## Troubleshooting Permissions

### User Can't Access Module

**Check in order:**
1. **Feature enabled?** (Admin > Users > Features tab)
2. **Has READ permission?** (Admin > Users > Permissions tab)
3. **Role includes permission?** (Admin > Roles > Check role permissions)
4. **Logged out and back in?** (Permissions refresh on login)

### User Can View But Not Edit

**Solution:**
- Add WRITE_* permission for that module
- Or add to role that includes write access

### Permission Changes Not Taking Effect

**Cause:** Permissions cached in session

**Solution:**
- User must log out and log back in
- Or admin can force logout (Admin > Active Sessions)

### User Has Too Much Access

**Solution:**
- Review assigned roles
- Remove unnecessary roles
- Remove individual permissions
- Disable unnecessary features

---

## Security Considerations

### Sensitive Permissions

**Use extreme caution with:**
- `ALL_PERMISSIONS` - Grants everything
- `SYSTEM_ADMIN` - System-wide access
- `ADMIN_READ_USERS` - Can create admin accounts
- `DELETE_*` permissions - Can remove data
- `ADMIN_*` permissions - Configuration access

### Access Control Lists (ACLs)

**Additional layer:**
- Documents have per-document permissions
- Reports can be restricted
- Calendar events can be private

**Both** system permissions AND ACLs must allow access.

### Audit Logging

**Track permission changes:**
- Who granted permissions
- When permissions changed
- What was changed
- Why (include notes)

**Review logs regularly** for unauthorized changes.

---

## Related Guides

- **[User Management](/guide/admin/user-management)** - Managing users
- **[Role Management](/guide/admin/roles)** - Creating and managing roles
- **[Employee Onboarding](/guide/workflows/employee-onboarding)** - Setting up new users

---

## Quick Reference

**Permission Format:**
- `ACTION_MODULE` (e.g., READ_EMPLOYEE)
- READ = View
- WRITE = Create/Edit
- DELETE = Remove
- ADMIN = Configure

**Access Layers:**
1. Role (collection of permissions)
2. Individual permissions
3. Feature flags (module on/off)

**Best Practice:**
- Use roles for common job functions
- Grant minimum necessary access
- Review permissions quarterly
- Audit admin accounts regularly

**Required Permission:**
- ADMIN_READ_ROLES (to manage permissions)

---

**Last Updated:** 2025-10-27
