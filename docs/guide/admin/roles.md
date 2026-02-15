# Role Management

Configure roles (groups) and assign permissions in K-Systems.

## Overview

Role Management allows you to:
- Create custom roles (groups)
- Assign permissions to roles
- Configure report category access
- Manage role hierarchy with power levels
- Delete unused roles

**Required Permission:** `ADMIN_ROLE`

## Access

**Desktop:** Start Menu → Administration → Role Management
**Menu:** Administration → Roles
**Route:** `/admin/roles`

## Understanding Roles

### What are Roles?

**Roles (Groups) are:**
- Named collections of permissions
- Assigned to users
- Reusable across multiple users
- Authority-specific (each authority has own roles)

**Example Roles:**
- Administrator
- Manager
- Employee
- Dispatcher
- Viewer

### Role vs Permission

**Role (Group):**
- Container for permissions
- User-friendly name
- Business logic grouping
- Example: "HR Manager", "Dispatcher"

**Permission:**
- Specific system capability
- Technical identifier
- Fine-grained access
- Example: `READ_EMPLOYEE`, `WRITE_REPORT`, `ADMIN_USER`

### Power Level

**Power Level determines:**
- Role hierarchy
- Which roles can be assigned by users with this role
- Higher power = more authority
- Example: Administrator (100) > Manager (50) > Employee (10)

## Role List

### Interface

**Role Table Columns:**
- **Role Name**: Display name
- **Description**: Role purpose
- **Power Level**: Hierarchy level (displayed as chip)
- **Sort Order**: Display order in lists
- **Actions**: Edit, Delete

**Sorting:**
- Click column headers to sort
- Sort by name, power level, or sort order

## Creating Roles

### Create New Role

**Add Role:**
1. Click **+ Add Role** button
2. Configure role in 4 tabs:

### Tab 1: Information

**Basic Information:**
- **Role Name** (required)
  - Clear, descriptive name
  - Example: "HR Manager", "Dispatcher"
- **Description** (optional)
  - Explain role purpose
  - Who should have this role
- **Power Level** (required)
  - Number representing hierarchy
  - Higher = more authority
  - Example: Admin = 100, Manager = 50, User = 10
- **Sort Order** (required)
  - Display order in lists
  - Lower numbers appear first
  - Example: 1, 2, 3, etc.

### Tab 2: Admin Rights

**Administrative Permissions:**
- Shows permissions starting with "ADMIN"
- Grouped by module/site
- Checkbox selection per permission

**Common Admin Permissions:**
- `ADMIN_USER` - User management
- `ADMIN_ROLE` - Role management
- `ADMIN_AUTHORITY` - Authority settings
- `ADMIN_EMPLOYEE` - Employee administration
- `ADMIN_REPORT` - Report configuration
- `ADMIN_DOCUMENT` - Document area management
- `ADMIN_SYSTEM` - System settings

**Permission Display:**
- Name/Alias: Permission identifier
- Description: What permission allows
- Checkbox: Assign to role

### Tab 3: General Rights

**General Permissions:**
- Shows non-admin permissions
- Grouped by module/site
- Checkbox selection per permission

**Common Permission Patterns:**
- **READ_**: View/read access
  - Example: `READ_EMPLOYEE`, `READ_REPORT`
- **WRITE_**: Create/edit access
  - Example: `WRITE_EMPLOYEE`, `WRITE_REPORT`
- **DELETE_**: Delete access
  - Example: `DELETE_EMPLOYEE`, `DELETE_REPORT`

**Module Permissions:**
- Employee Management
- Reports
- Documents
- Calendar
- Messages
- Todos
- Map
- Dispatch
- Training
- Fire Protection

### Tab 4: Report Categories

**Report Category Access:**
- Control which report categories role can access
- Checkbox for each category
- Shows category name and title
- Independent from general report permissions

**How it Works:**
- User needs `READ_REPORT` permission AND category access
- Both are required to view reports in category
- Allows fine-grained report access control

3. Click **Save Role**
4. Role created and available for user assignment

## Editing Roles

### Modify Existing Role

**Edit Role:**
1. Click **Edit** button on role row
2. Modify any tab:
   - Information
   - Admin Rights
   - General Rights
   - Report Categories
3. Click **Save**
4. Changes apply immediately to all users with this role

**Change Detection:**
- Save button disabled if no changes made
- Prevents accidental saves
- Form tracks all modifications

## Deleting Roles

### Remove Role

**Delete Role:**
1. Click **Delete** button on role row
2. Confirmation dialog appears:
   - Shows role name
   - Warning about permanent deletion
3. Click **Delete** to confirm
4. Role deleted

**Important:**
- Deletion is permanent
- Users with this role will lose associated permissions
- Check user assignments before deleting
- Cannot delete if users still assigned (recommended practice)

## Permission Management

### Permission Categories

**By Module:**
- Employee
- Reports
- Documents
- Calendar
- Messages
- Invoices
- Todos
- Map
- Whiteboard
- Dispatch
- Training
- Fire Protection
- Administration

**By Action Type:**
- **READ**: View/read access
- **WRITE**: Create/edit access
- **DELETE**: Remove/delete access
- **ADMIN**: Module administration
- **SHARE**: External sharing capabilities

### Permission Naming Convention

**Format:** `ACTION_MODULE`

**Examples:**
- `READ_EMPLOYEE`
- `WRITE_REPORT`
- `DELETE_DOCUMENT`
- `ADMIN_USER`
- `SHARE_REPORT`

### Assigning Permissions

**Add Permissions:**
1. Edit role
2. Go to Admin Rights or General Rights tab
3. Check permissions to include
4. Permissions grouped by module
5. Click **Save**

**Remove Permissions:**
1. Edit role
2. Uncheck permissions to remove
3. Click **Save**

**Bulk Selection:**
- Check multiple permissions at once
- Expand/collapse permission groups
- Search within permission list (if many permissions)

## Report Category Access

### Configure Category Access

**Manage Report Categories:**
1. Edit role
2. Go to **Report Categories** tab
3. Check categories this role can access
4. Click **Save**

**Access Control:**
- Users need both:
  1. `READ_REPORT` permission (from General Rights)
  2. Category access (from Report Categories tab)
- Missing either = no access to reports in that category

**Use Cases:**
- Limit sensitive report access
- Department-specific reports
- Role-based report visibility
- Compliance requirements

## API Reference

### Role Endpoints

```http
GET /admin/roles?action=getRoles
Returns: List of all roles

GET /admin/roles?action=getPermissions
Returns: List of all available permissions

GET /admin/roles?action=getRolePermissions
Returns: Role-permission assignments

GET /admin/roles?action=getCategoryRoles&roleId={id}
Returns: Report category access for role

POST /admin/roles?action=createRole
Body: { name, description, sort_order, power, permissionIds[] }
Returns: Success message and role ID

POST /admin/roles?action=updateRole
Body: { id, name, description, sort_order, power, permissionIds[] }
Returns: Success message

POST /admin/roles?action=deleteRole
Body: { id }
Returns: Success message
```

### Report Category Endpoint

```http
GET /report?action=getCategories
Returns: List of report categories for category access tab
```

## TypeScript Interfaces

```typescript
interface Role {
  id: number;
  name: string;
  description: string | null;
  sort_order: number;
  power: number;
}

interface Permission {
  id: number;
  name: string;
  name_alias: string;
  description: string;
  site: string; // Used for grouping
}

interface RolePermission {
  role_id: number;
  permission_id: number;
}
```

## Best Practices

**Do:**
✅ Create roles by job function
✅ Use descriptive role names
✅ Document role purpose in description
✅ Set appropriate power levels
✅ Review permissions regularly
✅ Use least privilege principle
✅ Test permissions after changes
✅ Configure report category access appropriately

**Don't:**
❌ Create too many similar roles
❌ Give excessive permissions
❌ Use confusing role names
❌ Forget to set power levels
❌ Skip role descriptions
❌ Delete roles without checking usage
❌ Grant admin permissions unnecessarily

## Troubleshooting

### User Missing Permission

**Check:**
1. Verify user has role assigned (User Management)
2. Check role has required permission (Role Management)
3. Verify permission is in correct tab (Admin vs General Rights)
4. For reports: Check report category access
5. User may need to re-login for permission changes

### Permission Changes Not Working

**Solutions:**
1. Verify changes were saved
2. Check change detection (Save button enabled?)
3. User may need to logout and login again
4. Clear browser cache if needed
5. Check backend permissions in database

### Can't Delete Role

**Common Causes:**
- Users currently assigned to role
- System validation preventing deletion

**Solution:**
1. Check which users have this role
2. Assign users to different role first
3. Then retry deletion

### Report Category Access Not Working

**Check:**
1. Role has `READ_REPORT` permission (General Rights tab)
2. Role has category access (Report Categories tab)
3. Both are required
4. User has correct role assigned

## Limitations

The following features mentioned in some documentation **do not exist** in the current codebase:

❌ **Not Available:**
- Role hierarchy with parent-child relationships
- Role inheritance (permissions from parent roles)
- Role templates/presets
- Role cloning/duplication
- Role usage statistics (how many users)
- Permission preview before saving
- Bulk permission assignment tools
- Role comparison side-by-side
- Role audit trail/history
- Permission search within tabs
- Custom permission creation in UI
- Role import/export
- Time-based role assignments
- IP-based role restrictions
- Role auto-assignment rules

---

## Next Steps

- [User Management](/guide/admin/users) - Assign roles to users
- [Authority Management](/guide/admin/authorities) - Manage authorities
- [Reports](/guide/reports) - Understand report category access
