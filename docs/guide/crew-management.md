# Crew Management

Simple crew list management for organizing operational teams with basic CRUD operations.

## Overview

Features:
- Create/edit/delete crews
- Crew name and status tracking
- Sort order configuration
- Active/inactive status toggle
- Authority-specific data isolation
- Simple table view

**Required Permission:** `READ_CREW` (view), `WRITE_CREW` (create/edit), `DELETE_CREW` (delete)

## Accessing Crew Management

**Desktop:** Double-click **Crew** icon
**Menu:** Start Menu → Operations → Crew Management
**Route:** `/crew`

## Interface Layout

### Main View

**Header:**
- Title with crew icon
- **New Crew** button (if you have WRITE permission)

**Crew Table:**

**Columns:**
- **Name** - Crew name with icon
- **Status** - Optional status text
- **Sortierung** - Sort order (number)
- **Aktiv** - Active/inactive status chip
- **Actions** - Edit, Delete, Activate/Deactivate buttons

**Features:**
- Sortable columns
- Hover effects on rows
- Loading indicator while fetching
- Empty state with add crew button

**Sorting:**
- Primary sort: `sort_order` (ascending)
- Secondary sort: `name` (alphabetical)

## Creating Crews

### New Crew

1. Click **New Crew** button (requires `WRITE_CREW` permission)
2. Dialog opens with form

**Form Fields:**

**Name*** (required)
- Text field
- Crew name or designation
- Icon: account-group
- Example: "Engine 1 A-Shift", "Ladder 2 B-Shift", "Ambulance 3"

**Status** (optional)
- Text field
- Optional status description
- Icon: information-outline
- Example: "On Duty", "Training", "Available"

**Sortierung*** (required)
- Number field
- Sort order for table display
- Lower numbers appear first
- Default: 0
- Icon: sort

3. Click **Save**
4. Crew appears in table sorted by sort_order

**Validation:**
- Name is required
- Sort order is required (number)
- Save button disabled until valid

## Editing Crews

### Edit Crew

1. Click **Edit** icon (pencil) on crew row
2. Dialog opens with current data
3. Modify any fields:
   - Name
   - Status
   - Sort order
4. Click **Save** to update
5. Changes applied immediately

**Edit Permissions:**
- Requires `WRITE_CREW` permission
- Edit button only shows if you have permission

**Edit Behavior:**
- Form pre-filled with crew data
- All fields editable
- Validation same as new crew
- Table refreshes after save

## Deleting Crews

### Delete Crew

1. Click **Delete** icon (trash) on crew row
2. Confirmation dialog appears:
   - Warning icon
   - "Are you sure you want to delete this crew?"
   - Crew name displayed
   - Warning about permanent deletion
3. Click **Delete** to confirm
4. Or **Cancel** to abort
5. Crew removed from database

**Delete Permissions:**
- Requires `DELETE_CREW` permission
- Delete button only shows if you have permission

**Warning:** Deletion is permanent and cannot be undone.

## Active/Inactive Status

### Toggle Activation

**Activate/Deactivate:**
1. Click **Power** icon on crew row
   - Green power-plug icon = Active
   - Grey power-plug-off icon = Inactive
2. Status toggles immediately
3. Loading indicator during API call
4. Success toast notification

**Status Indicator:**
- **Active (1)**: Green chip with check-circle icon
- **Inactive (0)**: Red chip with close-circle icon

**Status Effects:**
- Active status: `active = 1` in database
- Inactive status: `active = 0` in database
- Status toggled via `toggleCrewActivation` API action
- Optimistic UI update (changes immediately, no refresh)

**Tooltip:**
- Shows "Activate" or "Deactivate" on hover
- Translated based on current status

## Data Model

**Crew Interface:**
```typescript
interface Crew {
  id: number;
  name: string;
  status: string | null;
  sort_order: number;
  active: 0 | 1; // 0 = inactive, 1 = active
  authority_id: number;
  employees?: Employee[]; // Related data (not shown in UI)
  vehicles?: Vehicle[]; // Related data (not shown in UI)
}
```

**Form Data:**
```typescript
{
  id?: number | null; // Null for new, ID for edit
  name: string;
  status: string;
  sort_order: number;
}
```

## API Integration

**Base Path:** `/crew/`

**Actions:**

**Get Crews:**
```
GET /crew/?action=getCrews
Response: { data: Crew[] }
```

**Add Crew:**
```
POST /crew/?action=addCrew
Payload: { name: string, status: string, sort_order: number }
```

**Edit Crew:**
```
POST /crew/?action=editCrew
Payload: { id: number, name: string, status: string, sort_order: number }
```

**Delete Crew:**
```
POST /crew/?action=deleteCrew
Payload: { id: number }
```

**Toggle Activation:**
```
POST /crew/?action=toggleCrewActivation
Payload: { id: number }
```

**Error Handling:**
- API errors shown via toast notifications
- Console logging for debugging
- Loading states during operations
- Table refreshes after successful operations

## Multi-Tenant Security

**Authority Isolation:**
- All crews scoped to `authority_id`
- Users only see crews from their organization
- Backend filters by authority automatically
- Cross-organization access prevented

**Permission Checks:**
- `READ_CREW` - View crew list
- `WRITE_CREW` - Create and edit crews
- `DELETE_CREW` - Delete crews
- Desktop window mode supports permission props

## Desktop Window Mode

**Props Support:**
```typescript
interface Props {
  meta?: Record<string, any>;
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
}
```

**Permission Resolution:**
1. Check `props.allPermissions` (grants all)
2. Check individual prop permissions (canEdit, canDelete, canCreate)
3. Fall back to `route.meta` permissions
4. Buttons show/hide based on permissions

## Styling & Theme

**Dark Theme:**
- Background: #111723 with gradient overlays
- Card background: rgba(15, 23, 42, 0.6) with blur
- Border: rgba(255, 255, 255, 0.08)
- Dialog background: #0f172a

**Animations:**
- Table rows fade in on load
- Action buttons lift on hover (translateY(-2px))
- Loading spinners on async operations
- Smooth transitions throughout

**Responsive:**
- Fluid container with padding
- Table adapts to screen size
- Dialogs max-width 600px (form) and 500px (delete)

## Limitations

**What's NOT Available:**

### Shift Management
- ❌ Shift scheduling
- ❌ Shift patterns (24/48, 12-hour, etc.)
- ❌ Shift calendar
- ❌ Shift roster creation
- ❌ Automatic scheduling
- ❌ Shift templates
- ❌ Shift trades and swaps
- ❌ Rotation management
- ❌ Kelly days

### Personnel Assignment
- ❌ Assign employees to crews
- ❌ Position assignments (officer, engineer, etc.)
- ❌ Multiple assignments
- ❌ Assignment history
- ❌ Crew member list display
- ❌ Drag-drop assignments
- ❌ Personnel availability tracking

### Qualification Tracking
- ❌ Certification management
- ❌ Qualification requirements
- ❌ Expiration tracking
- ❌ Training records
- ❌ License tracking
- ❌ Skill tracking
- ❌ Qualification matrix

### Availability Management
- ❌ Mark available/unavailable
- ❌ Time-off requests
- ❌ Vacation tracking
- ❌ Sick leave tracking
- ❌ Overtime tracking
- ❌ Availability calendar
- ❌ Shift coverage tracking

### Staffing Features
- ❌ Minimum staffing requirements
- ❌ Staffing alerts
- ❌ Coverage analysis
- ❌ Staffing matrix
- ❌ Position requirements
- ❌ Float personnel
- ❌ Acting assignments
- ❌ Holdover tracking

### Overtime Management
- ❌ Overtime calculation
- ❌ Overtime distribution
- ❌ Overtime list/rotation
- ❌ Forced overtime
- ❌ Callback tracking
- ❌ Overtime reports
- ❌ Cost analysis

### Dispatch Integration
- ❌ Real-time crew status
- ❌ Unit staffing display
- ❌ Qualification verification
- ❌ Crew availability in dispatch
- ❌ Automatic updates
- ❌ Incident assignments

### Reporting & Analytics
- ❌ Roster reports
- ❌ Staffing summary
- ❌ Qualification reports
- ❌ Availability reports
- ❌ Overtime reports
- ❌ Shift trade reports
- ❌ Export functionality

### Advanced Features
- ❌ Calendar view
- ❌ Timeline view
- ❌ Gantt chart view
- ❌ Filters (by station, shift, position)
- ❌ Search functionality
- ❌ Bulk operations
- ❌ Copy/clone crews
- ❌ Templates
- ❌ Notes or comments
- ❌ Attachments
- ❌ History/audit trail

### UI Limitations
- ❌ No employee/vehicle relationships shown in table
- ❌ No detail view with tabs
- ❌ No embedded sub-tables
- ❌ No pagination (all crews load at once)
- ❌ No advanced filtering
- ❌ No export/print
- ❌ No keyboard shortcuts

**Current Reality:**
This is a **simple crew directory** for naming and organizing teams. It is not a comprehensive crew scheduling, staffing, or shift management system.

## Use Cases

**Basic Organization:**
- Create list of crews/teams
- Name shifts or units
- Track active/inactive status
- Organize with sort order

**Simple Directory:**
- Maintain crew names
- Optional status field for notes
- Quick activate/deactivate
- Basic CRUD operations

**Foundation for Future:**
- Backend has `employees` and `vehicles` relationships
- Could be extended to assign personnel
- Currently only basic metadata tracked

## Best Practices

**Creating Crews:**
1. Use clear, descriptive names
2. Include shift designation in name if applicable
3. Use consistent naming convention
4. Set logical sort order (by station, shift, priority)

**Status Field:**
1. Use for current state (e.g., "On Duty", "Training")
2. Keep brief and clear
3. Update as needed
4. Optional - leave blank if not needed

**Sort Order:**
1. Plan numbering scheme (e.g., 10, 20, 30 for easy insertion)
2. Lower numbers appear first
3. Use same increment for easy reordering
4. Update when adding new crews

**Active/Inactive:**
1. Deactivate crews no longer in use
2. Keep historical crews as inactive rather than delete
3. Use for seasonal or temporary crews
4. Filter active crews for operational use (if filtering added)

## Troubleshooting

### Can't Create Crew

**Problem:** New Crew button doesn't work or is missing

**Solutions:**
1. Check you have `WRITE_CREW` permission
2. Verify feature enabled for your authority
3. Refresh page
4. Check browser console for errors

### Crew Not Saving

**Problem:** Save button disabled or save fails

**Solutions:**
1. Verify name is filled (required)
2. Verify sort_order is filled (required)
3. Check validation messages
4. Check browser console for API errors
5. Verify internet connection

### Can't Edit Crew

**Problem:** Edit button doesn't work

**Solutions:**
1. Check you have `WRITE_CREW` permission
2. Verify crew belongs to your authority
3. Refresh page and try again
4. Check browser console for errors

### Can't Delete Crew

**Problem:** Delete fails or button missing

**Solutions:**
1. Check you have `DELETE_CREW` permission
2. Crew may have related records (employees, vehicles)
3. Verify crew belongs to your authority
4. Check error message in toast
5. Consider deactivating instead of deleting

### Toggle Activation Fails

**Problem:** Active/inactive toggle doesn't work

**Solutions:**
1. Verify internet connection
2. Check browser console for API errors
3. Refresh page to see correct state
4. Try again

### Crews Not Loading

**Problem:** Empty table or loading spinner doesn't stop

**Solutions:**
1. Check internet connection
2. Verify backend is running
3. Check browser console for API errors
4. Check network tab for failed requests
5. Verify authority_id is correct

## Related Documentation

- [👥 Employee Management](/guide/employee-management) - Managing employees
- [🚗 Vehicle Files](/guide/vehicle-files) - Vehicle tracking
- [🚒 Dispatch Operations](/guide/dispatch) - Dispatch system

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
