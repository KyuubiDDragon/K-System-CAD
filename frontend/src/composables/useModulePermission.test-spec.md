# useModulePermission Test Specification

## Overview
This document outlines test cases for the `useModulePermission` composable to ensure it correctly handles both bitmask and legacy permission formats.

## Action Bitmask Values

```
READ   = 1  = 0b000001
WRITE  = 2  = 0b000010
DELETE = 4  = 0b000100
ADMIN  = 8  = 0b001000
VIEW   = 16 = 0b010000
CREATE = 32 = 0b100000
```

## Permission Hierarchy

- **ADMIN** (8) implies: DELETE, WRITE, READ, VIEW, CREATE
- **DELETE** (4) implies: WRITE, READ
- **WRITE** (2) implies: READ

## Test Cases

### Test Suite 1: Bitmask Format Detection

#### Test 1.1: Detect Bitmask Format
**Input:** `{"employee": 7, "calendar": 23}`
**Expected:** `isBitmask()` returns `true`

#### Test 1.2: Detect Legacy Format
**Input:** `["READ_EMPLOYEE", "WRITE_EMPLOYEE"]`
**Expected:** `isBitmask()` returns `false`

#### Test 1.3: Detect Invalid Format
**Input:** `null`, `undefined`, `""`, `123`
**Expected:** `isBitmask()` returns `false`

---

### Test Suite 2: Bitmask Permission Checks

#### Test 2.1: Single Permission (READ)
**Permissions:** `{"employee": 1}`  (0b000001 = READ only)
**Checks:**
- `hasModulePermission('employee', 'read')` → `true`
- `hasModulePermission('employee', 'write')` → `false`
- `hasModulePermission('employee', 'delete')` → `false`

#### Test 2.2: Multiple Permissions (READ + WRITE)
**Permissions:** `{"employee": 3}`  (0b000011 = READ + WRITE)
**Checks:**
- `hasModulePermission('employee', 'read')` → `true`
- `hasModulePermission('employee', 'write')` → `true`
- `hasModulePermission('employee', 'delete')` → `false`

#### Test 2.3: DELETE Permission (includes WRITE + READ)
**Permissions:** `{"employee": 4}`  (0b000100 = DELETE only)
**Checks:**
- `hasModulePermission('employee', 'read')` → `true` (implied by DELETE)
- `hasModulePermission('employee', 'write')` → `true` (implied by DELETE)
- `hasModulePermission('employee', 'delete')` → `true`
- `hasModulePermission('employee', 'admin')` → `false`

#### Test 2.4: Full Permissions (READ + WRITE + DELETE)
**Permissions:** `{"employee": 7}`  (0b000111 = READ + WRITE + DELETE)
**Checks:**
- `hasModulePermission('employee', 'read')` → `true`
- `hasModulePermission('employee', 'write')` → `true`
- `hasModulePermission('employee', 'delete')` → `true`
- `hasModulePermission('employee', 'admin')` → `false`

#### Test 2.5: ADMIN Permission (implies all others)
**Permissions:** `{"employee": 8}`  (0b001000 = ADMIN only)
**Checks:**
- `hasModulePermission('employee', 'read')` → `true` (implied)
- `hasModulePermission('employee', 'write')` → `true` (implied)
- `hasModulePermission('employee', 'delete')` → `true` (implied)
- `hasModulePermission('employee', 'admin')` → `true`
- `hasModulePermission('employee', 'view')` → `true` (implied)
- `hasModulePermission('employee', 'create')` → `true` (implied)

#### Test 2.6: VIEW and CREATE Permissions
**Permissions:** `{"report": 48}`  (0b110000 = VIEW + CREATE)
**Checks:**
- `hasModulePermission('report', 'view')` → `true`
- `hasModulePermission('report', 'create')` → `true`
- `hasModulePermission('report', 'read')` → `false`
- `hasModulePermission('report', 'write')` → `false`

#### Test 2.7: Multiple Modules
**Permissions:** `{"employee": 7, "calendar": 3, "report": 1}`
**Checks:**
- `hasModulePermission('employee', 'delete')` → `true`
- `hasModulePermission('calendar', 'write')` → `true`
- `hasModulePermission('calendar', 'delete')` → `false`
- `hasModulePermission('report', 'read')` → `true`
- `hasModulePermission('report', 'write')` → `false`

#### Test 2.8: Module Not in Permissions
**Permissions:** `{"employee": 7}`
**Checks:**
- `hasModulePermission('calendar', 'read')` → `false`
- `hasModulePermission('nonexistent', 'read')` → `false`

---

### Test Suite 3: Legacy Format Permission Checks

#### Test 3.1: Single Permission
**Permissions:** `["READ_EMPLOYEE"]`
**Checks:**
- `hasModulePermission('employee', 'read')` → `true`
- `hasModulePermission('employee', 'write')` → `false`

#### Test 3.2: Multiple Permissions
**Permissions:** `["READ_EMPLOYEE", "WRITE_EMPLOYEE", "READ_CALENDAR"]`
**Checks:**
- `hasModulePermission('employee', 'read')` → `true`
- `hasModulePermission('employee', 'write')` → `true`
- `hasModulePermission('employee', 'delete')` → `false`
- `hasModulePermission('calendar', 'read')` → `true`
- `hasModulePermission('calendar', 'write')` → `false`

#### Test 3.3: Permission Hierarchy (WRITE implies READ)
**Permissions:** `["WRITE_EMPLOYEE"]`
**Checks:**
- `hasModulePermission('employee', 'read')` → `true` (implied by WRITE)
- `hasModulePermission('employee', 'write')` → `true`

#### Test 3.4: Permission Hierarchy (DELETE implies WRITE and READ)
**Permissions:** `["DELETE_EMPLOYEE"]`
**Checks:**
- `hasModulePermission('employee', 'read')` → `true` (implied)
- `hasModulePermission('employee', 'write')` → `true` (implied)
- `hasModulePermission('employee', 'delete')` → `true`

#### Test 3.5: Case Insensitivity
**Permissions:** `["READ_EMPLOYEE"]`
**Checks:**
- `hasModulePermission('Employee', 'Read')` → `true`
- `hasModulePermission('EMPLOYEE', 'READ')` → `true`
- `hasModulePermission('employee', 'read')` → `true`

---

### Test Suite 4: ALL_PERMISSIONS Super Admin

#### Test 4.1: ALL_PERMISSIONS in Legacy Format
**Permissions:** `["ALL_PERMISSIONS"]`
**Checks:**
- `hasModulePermission('employee', 'read')` → `true`
- `hasModulePermission('employee', 'admin')` → `true`
- `hasModulePermission('any_module', 'any_action')` → `true`

#### Test 4.2: ALL_PERMISSIONS in Bitmask Format
**Permissions:** `{"ALL_PERMISSIONS": 1}`
**Checks:**
- `hasModulePermission('employee', 'read')` → `true`
- `hasModulePermission('employee', 'admin')` → `true`
- `hasModulePermission('any_module', 'any_action')` → `true`

#### Test 4.3: ALL_PERMISSIONS with Other Permissions
**Permissions:** `["ALL_PERMISSIONS", "READ_EMPLOYEE"]`
**Expected:** ALL_PERMISSIONS grants everything, other permissions are redundant
**Checks:**
- `hasModulePermission('employee', 'read')` → `true`
- `hasModulePermission('calendar', 'delete')` → `true`

---

### Test Suite 5: hasAnyModulePermission

#### Test 5.1: User Has One of Multiple Actions (Bitmask)
**Permissions:** `{"employee": 3}`  (READ + WRITE)
**Checks:**
- `hasAnyModulePermission('employee', ['read', 'write'])` → `true`
- `hasAnyModulePermission('employee', ['read', 'delete'])` → `true` (has read)
- `hasAnyModulePermission('employee', ['delete', 'admin'])` → `false` (has neither)

#### Test 5.2: User Has One of Multiple Actions (Legacy)
**Permissions:** `["READ_EMPLOYEE", "WRITE_EMPLOYEE"]`
**Checks:**
- `hasAnyModulePermission('employee', ['read', 'write'])` → `true`
- `hasAnyModulePermission('employee', ['delete', 'admin'])` → `false`

#### Test 5.3: Empty Actions Array
**Permissions:** `{"employee": 7}`
**Checks:**
- `hasAnyModulePermission('employee', [])` → `false`

---

### Test Suite 6: hasAllModulePermissions

#### Test 6.1: User Has All Required Actions (Bitmask)
**Permissions:** `{"employee": 7}`  (READ + WRITE + DELETE)
**Checks:**
- `hasAllModulePermissions('employee', ['read', 'write'])` → `true`
- `hasAllModulePermissions('employee', ['read', 'write', 'delete'])` → `true`
- `hasAllModulePermissions('employee', ['read', 'write', 'delete', 'admin'])` → `false`

#### Test 6.2: User Has All Required Actions (Legacy)
**Permissions:** `["READ_EMPLOYEE", "WRITE_EMPLOYEE", "DELETE_EMPLOYEE"]`
**Checks:**
- `hasAllModulePermissions('employee', ['read', 'write'])` → `true`
- `hasAllModulePermissions('employee', ['read', 'write', 'delete'])` → `true`
- `hasAllModulePermissions('employee', ['read', 'admin'])` → `false`

#### Test 6.3: Empty Actions Array
**Permissions:** `{"employee": 7}`
**Checks:**
- `hasAllModulePermissions('employee', [])` → `true` (no permissions required)

---

### Test Suite 7: Edge Cases

#### Test 7.1: Null/Undefined Permissions
**Permissions:** `null` or `undefined`
**Checks:**
- `hasModulePermission('employee', 'read')` → `false`
- `hasAnyModulePermission('employee', ['read'])` → `false`
- `hasAllModulePermissions('employee', ['read'])` → `false`

#### Test 7.2: Empty Permissions Object
**Permissions:** `{}`
**Checks:**
- `hasModulePermission('employee', 'read')` → `false`

#### Test 7.3: Empty Permissions Array
**Permissions:** `[]`
**Checks:**
- `hasModulePermission('employee', 'read')` → `false`

#### Test 7.4: Invalid Module Name
**Permissions:** `{"employee": 7}`
**Checks:**
- `hasModulePermission('', 'read')` → `false` (logs warning)
- `hasModulePermission(null, 'read')` → `false` (logs warning)

#### Test 7.5: Invalid Action Name
**Permissions:** `{"employee": 7}`
**Checks:**
- `hasModulePermission('employee', '')` → `false` (logs warning)
- `hasModulePermission('employee', null)` → `false` (logs warning)
- `hasModulePermission('employee', 'invalid_action')` → `false` (logs warning)

#### Test 7.6: Non-String Actions in Array
**Permissions:** `{"employee": 7}`
**Checks:**
- `hasAnyModulePermission('employee', ['read', null, 'write'])` → `true` (skips null)
- `hasAllModulePermissions('employee', ['read', null])` → `false` (invalid entry)

---

### Test Suite 8: getModulePermissionValue

#### Test 8.1: Get Bitmask Value
**Permissions:** `{"employee": 7, "calendar": 23}`
**Checks:**
- `getModulePermissionValue('employee')` → `7`
- `getModulePermissionValue('calendar')` → `23`
- `getModulePermissionValue('nonexistent')` → `null`

#### Test 8.2: Not Bitmask Format
**Permissions:** `["READ_EMPLOYEE"]`
**Checks:**
- `getModulePermissionValue('employee')` → `null`

---

### Test Suite 9: isUsingBitmaskFormat

#### Test 9.1: Bitmask Format Detection
**Permissions:** `{"employee": 7}`
**Checks:**
- `isUsingBitmaskFormat.value` → `true`

#### Test 9.2: Legacy Format Detection
**Permissions:** `["READ_EMPLOYEE"]`
**Checks:**
- `isUsingBitmaskFormat.value` → `false`

#### Test 9.3: No Permissions
**Permissions:** `null`
**Checks:**
- `isUsingBitmaskFormat.value` → `false`

---

## Integration Test Scenarios

### Scenario 1: Backend Migration from Legacy to Bitmask

**Initial State:** Backend returns legacy format
```json
{
  "permissions": ["READ_EMPLOYEE", "WRITE_EMPLOYEE"]
}
```

**Component Code:**
```typescript
const { hasModulePermission } = useModulePermission();
const canEdit = hasModulePermission('employee', 'write');
```
**Result:** `canEdit = true`

**After Migration:** Backend returns bitmask format
```json
{
  "permissions": {"employee": 3}
}
```

**Component Code:** (unchanged)
```typescript
const { hasModulePermission } = useModulePermission();
const canEdit = hasModulePermission('employee', 'write');
```
**Result:** `canEdit = true`

**Conclusion:** No frontend code changes needed during backend migration!

---

### Scenario 2: Gradual Permission Reduction

**User starts with:** `{"employee": 7}` (READ + WRITE + DELETE)
- Can view employee list: ✅
- Can edit employees: ✅
- Can delete employees: ✅

**Permission reduced to:** `{"employee": 3}` (READ + WRITE)
- Can view employee list: ✅
- Can edit employees: ✅
- Can delete employees: ❌

**Permission reduced to:** `{"employee": 1}` (READ only)
- Can view employee list: ✅
- Can edit employees: ❌
- Can delete employees: ❌

---

## Manual Testing Checklist

### Setup
1. ✅ Create a test user in the database
2. ✅ Assign various permission combinations
3. ✅ Log in as test user
4. ✅ Open browser console

### Test Steps

1. **Test Bitmask Format:**
   - Set user permissions to `{"employee": 7, "calendar": 3}`
   - In component, check `hasModulePermission('employee', 'read')`
   - Expected: Console shows no warnings, returns true

2. **Test Legacy Format:**
   - Set user permissions to `["READ_EMPLOYEE", "WRITE_EMPLOYEE"]`
   - In component, check `hasModulePermission('employee', 'read')`
   - Expected: Console shows no warnings, returns true

3. **Test Permission Hierarchy:**
   - Set user permissions to `{"employee": 4}` (DELETE only)
   - Check READ permission: should return true (implied)
   - Check WRITE permission: should return true (implied)
   - Check DELETE permission: should return true

4. **Test UI Rendering:**
   - View a page with permission-gated UI elements
   - Change user permissions in database
   - Refresh page
   - Verify UI elements show/hide correctly

5. **Test ALL_PERMISSIONS:**
   - Set user permissions to `["ALL_PERMISSIONS"]`
   - Try to access any module/action
   - Expected: All checks return true

---

## Performance Considerations

- **Bitmask Format:** O(1) lookup + bitwise operation (very fast)
- **Legacy Format:** O(n) array search where n = number of permissions
- **Format Detection:** O(1) - checks first value type only
- **Computed Permissions:** Reactive, only recalculates when auth store changes

---

## Known Limitations

1. **Custom Actions:** Only predefined actions (read, write, delete, admin, view, create) are supported
2. **Module Names:** Must match exactly between frontend and backend (case-insensitive in composable)
3. **Permission Hierarchy:** Hardcoded in composable, changes require code update

---

## Future Enhancements

1. **Dynamic Action Map:** Load action definitions from backend
2. **Permission Explanation:** Add function to explain why a check passed/failed
3. **Batch Permission Checks:** Optimize checking many permissions at once
4. **Cache Results:** Cache permission checks for even better performance
