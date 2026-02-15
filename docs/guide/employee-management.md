# Employee Management

Employee management system with rank-based organization, company/department associations, and training tracking.

## Overview

Features:
- Employee creation and editing via EmployeeForm component
- Rank-based employee grouping
- Inline rank name editing
- Drag-and-drop rank sorting
- Department filtering
- Show/hide terminated employees toggle
- Search by name or service number
- Employee cards with detailed information
- Training tracking integration
- Company, department, license associations
- Authority-specific data isolation

**Required Permissions:**
- Viewing requires route access or explicit permission
- `WRITE_EMPLOYEE` (create/edit employees, edit ranks)
- `DELETE_EMPLOYEE` (delete ranks)

## Accessing Employee Management

**Desktop:** Double-click **Employees** icon
**Menu:** Start Menu → HR → Employees
**Route:** `/employee`

## Interface Layout

### Header Section

**Title:** Employee management with account-group icon

**Action Buttons** (if canEdit permission):
- **Add Employee** (mdi-account-plus-outline) - Primary elevated button
- **Add Rank** (mdi-shield-plus-outline) - Primary tonal button
- **Sort Ranks** (mdi-sort) - Primary tonal button

**Show Terminated Toggle:**
- Switch to show/hide terminated employees
- Warning color (orange)
- Off by default (shows only active employees)

### Filter Section

**Search Field:**
- Magnify icon
- Searches by employee name (case-insensitive)
- Searches by service number
- Real-time filtering
- Clearable

**Department Filter:**
- Dropdown select
- "Alle Departments" option (shows all)
- Filter by specific job type/department
- Clearable

### Employee Display

**Rank Sections:**
- Employees grouped by rank
- Sorted by rank `sort_order` field
- Each rank section shows:
  - Rank header with background styling
  - Employee cards in grid layout (flex wrap)
  - Divider between sections

**Rank Header:**
- Rank name (double-click to edit if canEdit)
- Edit tooltip with pencil icon
- Delete rank button (if canDelete)
  - Disabled if rank has employees
  - Tooltip: "Rang löschen (nur wenn leer)"

**Employee Cards:**
- Grid layout with responsive columns
- Hover effect (translateY animation)
- Fade-in animation on load
- MemberCard component displays:
  - Employee details
  - Company associations
  - Department associations
  - Training status
  - Notes (editable inline)

**Empty State:**
- Shows when no employees found
- Icon and message
- Card format

**Loading State:**
- Centered progress spinner
- Text: "Lade Mitarbeiterdaten..."

## Managing Employees

### Add Employee

1. Click **Add Employee** button
2. **EmployeeForm** component opens in dialog
3. Fill employee details:
   - Personal information
   - Rank assignment
   - Company associations (multi-select)
   - Department associations (multi-select)
   - License assignments (multi-select)
   - Additional fields as configured
4. Click **Save**
5. Employee added to system
6. Appears in appropriate rank section

**Form Fields:**
- Receives: companies, departments, ranks, licenses, jobTypes
- Opens in "add" mode with `isNew: true`
- Validation rules apply
- Emits `@updateEmployeeList` on save

### Edit Employee

**From Employee Card:**
1. Click edit button on MemberCard
2. EmployeeForm opens with employee data
3. Modify any fields
4. Click **Save** to update

**From Deep Link:**
- URL query: `?id=123` or props: `id`, `meta.id`, `meta.employeeId`
- Automatically opens edit dialog on mount (if canEdit)

**Data Transformation for Edit:**
- `company`: array of company IDs
- `department`: array of department IDs
- `license`: array of license IDs
- `rankId`: number (from rank_id)
- Full employee object passed to form

### Update Employee Notes

**Inline Editing:**
- Notes field in MemberCard component
- Edit inline directly on card
- Emits `@updateNotes` event
- Auto-saves via API

## Managing Ranks

### Add Rank

1. Click **Add Rank** button
2. **Add Rank Dialog** opens
3. Fill form:
   - **Rank Name*** (required)
     - Text field, outlined
     - Validation: must not be empty
   - **Associated Department*** (required)
     - Select from job types/departments
     - Default: currently selected department or 1
4. Click **Add**
5. Rank created and appears in list

**API Endpoint:**
```
POST /employee?action=addRank
Payload: {
  name: string,
  department: number
}
```

### Edit Rank Name

**Inline Editing:**
1. Double-click rank name in rank header
2. Text field appears inline
3. Edit name
4. Press Enter or click outside to save
5. Press Escape to cancel

**Visual Feedback:**
- Pencil icon tooltip on hover (if canEdit)
- Editing state tracks active edits
- Auto-saves on blur

**API Endpoint:**
```
POST /employee?action=updateRankName
Payload: {
  id: number,
  name: string
}
```

### Sort Ranks

1. Click **Sort Ranks** button
2. **Sort Ranks Dialog** opens
3. Drag-and-drop list appears:
   - Each rank shows name and department
   - Drag handle icon (mdi-drag-horizontal-variant)
   - Ghost item styling during drag
4. Drag ranks to reorder
5. Click **Save** to persist order
6. Or **Cancel** to discard

**Sorting:**
- Uses vuedraggable component
- Updates `sort_order` field for all ranks
- Persistent dialog (max-width: 600px)

**API Endpoint:**
```
POST /employee?action=saveCategorySorting
Payload: {
  ranks: [
    { id: number, sort_order: number }
  ]
}
```

### Delete Rank

1. Click **Delete** button on rank header (if canDelete)
2. **Delete Confirmation Dialog** appears
3. Shows rank name and warning
4. Click **Delete** to confirm
5. Or **Cancel** to abort

**Protection:**
- Cannot delete rank with employees
- Delete button disabled if rank has members
- Tooltip: "Rang löschen (nur wenn leer)"

**API Endpoint:**
```
POST /employee?action=deleteRank
Payload: { id: number }
```

## Filtering Employees

### Department Filter

**How It Works:**
- Filter ranks by `jobrole_id`
- "Alle Departments" (value: 0) shows all
- Specific department ID filters to that department only

**Effect:**
- Filters visible ranks
- Only ranks matching selected department shown
- Employees within those ranks displayed

### Termination Status Filter

**Show Terminated Toggle:**
- Off (default): Shows only active employees
- On: Shows terminated employees

**Logic:**
- Checks `is_terminated` flag
- Compares `leavedate` with current date
- Filters employees within each rank

### Text Search

**Searches:**
- Employee name (case-insensitive contains)
- Service number (toString comparison)

**Behavior:**
- Applied after department and termination filters
- Real-time filtering as you type
- Clearable input

### Combined Filtering

**Filter Order:**
1. Department filter (filters ranks)
2. Termination status (filters employees in ranks)
3. Text search (filters employees in ranks)

**Result:**
- All filters work together (AND logic)
- Empty ranks still displayed (can be hidden via code option)

## Employee Card (MemberCard Component)

**Displays:**
- Employee photo/avatar
- Name and service number
- Rank badge
- Company associations
- Department associations
- Training status (with integration to training system)
- License information
- Notes field (editable inline)

**Actions:**
- Edit employee (opens EmployeeForm)
- Update notes inline
- View details

**Props Received:**
- member (employee data)
- logoURL
- companies array
- departments array
- trainings array
- trainingassigns array
- ranks array
- licenses array
- showPreview boolean
- showIsTerminated boolean

**Events Emitted:**
- `@edit` - opens edit dialog
- `@updateNotes` - saves note changes

## Data Model

**Employee:**
```typescript
interface Employee {
  id: number;
  name: string;
  servicenumber: number;
  rank_id: number;
  is_terminated: boolean;
  leavedate?: string; // YYYY-MM-DD
  company?: number[]; // Array of company IDs
  department?: number[]; // Array of department IDs
  license?: number[]; // Array of license IDs
  notes?: string;
  // Additional fields...
  authority_id: number;
}
```

**Rank:**
```typescript
interface Rank {
  id: number;
  name: string;
  department: number; // Job type ID
  jobrole_id: number; // Same as department
  sort_order: number;
  authority_id: number;
  members: Employee[]; // Employees in this rank
}
```

**Company:**
```typescript
interface Company {
  id: number;
  name: string;
  authority_id: number;
}
```

**Department:**
```typescript
interface Department {
  id: number;
  name: string;
  authority_id: number;
}
```

**License:**
```typescript
interface License {
  id: number;
  name: string;
  authority_id: number;
}
```

**JobType:**
```typescript
interface JobType {
  id: number;
  name: string;
  authority_id: number;
}
```

## API Integration

**Base Path:** `/employee/`

**Get Employees by Rank:**
```
GET /employee?action=getEmployee
Response: Rank[] (with nested members)
```

**Get Companies:**
```
GET /employee?action=getCompanies
Response: Company[]
```

**Get Departments:**
```
GET /employee?action=getDepartments
Response: Department[]
```

**Get Ranks (Flat List):**
```
GET /employee?action=getRanks
Response: Rank[]
```

**Get Licenses:**
```
GET /employee?action=getLicenses
Response: License[]
```

**Get Job Types:**
```
GET /employee?action=getJobTypes
Response: JobType[]
```

**Training Integration:**
```
GET /training?action=getTrainings
Response: Training[]

GET /training?action=getTrainingAssigns
Response: TrainingAssign[]
```

**Add Rank:**
```
POST /employee?action=addRank
Payload: {
  name: string,
  department: number
}
```

**Update Rank Name:**
```
POST /employee?action=updateRankName
Payload: {
  id: number,
  name: string
}
```

**Delete Rank:**
```
POST /employee?action=deleteRank
Payload: { id: number }
```

**Save Rank Sort Order:**
```
POST /employee?action=saveCategorySorting
Payload: {
  ranks: [
    { id: number, sort_order: number }
  ]
}
```

**Employee CRUD:**
- Handled by EmployeeForm component
- Add/Edit operations emit `@updateEmployeeList`
- Refreshes employee data after changes

## Multi-Tenant Security

**Authority Isolation:**
- All employees scoped to `authority_id`
- All ranks scoped to `authority_id`
- All companies, departments, licenses scoped to `authority_id`
- Users only see data from their organization
- Backend filters by authority automatically

## Desktop Window Mode

**Props Support:**
```typescript
interface Props {
  meta?: Record<string, any>;
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
  id?: number | string; // Employee ID to auto-open
}
```

**Deep Linking:**
- Query parameter: `?id=123`
- Props: `id`, `meta.id`, `meta.employeeId`
- Automatically opens edit dialog for specified employee
- Useful for notifications and direct links

## Styling & Theme

**Dark Theme:**
- Gradient backgrounds
- Glassmorphism effects (backdrop-filter blur)
- Hover animations (translateY on cards)
- Fade-in animations for rank sections

**Responsive Layout:**
- Flex wrap grid for employee cards
- Responsive column layout
- Mobile-friendly dialogs

**Custom Styling:**
- Rank headers with background styling
- Dividers between rank sections
- Gradient dialog headers
- Custom drag-and-drop ghost styling

## Limitations

**What's NOT Available:**

### Employee Features
- ❌ No employee photo upload (avatar placeholder only)
- ❌ No employee import/export
- ❌ No bulk operations
- ❌ No employee versioning/history
- ❌ No employee status workflow (only terminated flag)
- ❌ No emergency contact information
- ❌ No employee documents/attachments
- ❌ No employee certifications (beyond licenses)

### Organizational Features
- ❌ No org chart visualization
- ❌ No hierarchy display (manager/subordinate)
- ❌ No team management
- ❌ No shift scheduling
- ❌ No attendance tracking
- ❌ No leave management (separate vacation module)

### Rank Features
- ❌ No rank permissions configuration
- ❌ No rank hierarchy levels
- ❌ No rank descriptions
- ❌ No rank badges/icons customization
- ❌ No rank-based access control (permissions separate)

### Filtering & Search
- ❌ No advanced search
- ❌ No saved filters
- ❌ No filter by company
- ❌ No filter by license
- ❌ No filter by training status
- ❌ No date filters (hire date, leave date)

### Reporting
- ❌ No employee statistics
- ❌ No headcount reports
- ❌ No export functionality
- ❌ No employee directory print view
- ❌ No analytics dashboard

### UI Limitations
- ❌ No table view (only card grid)
- ❌ No list view
- ❌ No employee detail page (edit dialog only)
- ❌ No column customization
- ❌ No bulk edit
- ❌ No employee comparison

**Current Reality:**
This is a **simple employee management system** with rank-based organization and basic filtering. It is not a comprehensive HRIS, workforce management system, or organizational chart tool.

## Use Cases

**Employee Organization:**
- Organize employees by rank/position
- Track company and department associations
- Manage license assignments
- View training completion status

**Rank Management:**
- Create hierarchical rank structure
- Associate ranks with departments
- Reorder ranks for display priority
- Track employees per rank

**Quick Access:**
- Search employees by name or service number
- Filter by department
- Toggle between active and terminated employees
- Deep link to specific employees

## Best Practices

**Rank Setup:**
1. Create ranks before adding employees
2. Associate ranks with appropriate departments
3. Use clear, consistent rank naming
4. Order ranks by hierarchy (drag-and-drop)

**Employee Data:**
1. Assign correct rank to each employee
2. Associate with relevant companies
3. Link to appropriate departments
4. Track license assignments
5. Use notes field for important information

**Filtering:**
1. Use department filter to focus on specific areas
2. Toggle terminated employees when reviewing past staff
3. Search for quick employee lookup
4. Combine filters for detailed views

**Maintenance:**
1. Update ranks as organization changes
2. Mark employees as terminated rather than deleting
3. Keep notes current
4. Regular review of rank associations

## Troubleshooting

### Can't Add Employee

**Problem:** Add Employee button missing or doesn't work

**Solutions:**
1. Check WRITE_EMPLOYEE permission
2. Verify canEdit permission
3. Ensure ranks exist in system
4. Refresh page

### Employee Form Not Saving

**Problem:** Save doesn't persist in EmployeeForm

**Solutions:**
1. Check all required fields filled
2. Verify internet connection
3. Check browser console for API errors
4. Ensure rank assignment valid

### Can't Delete Rank

**Problem:** Delete button disabled or doesn't work

**Solutions:**
1. Verify rank has no employees (must be empty)
2. Check DELETE_EMPLOYEE permission
3. Move employees to different rank first
4. Refresh page

### Filters Not Working

**Problem:** Filters don't show expected employees

**Solutions:**
1. Clear all filters and try again
2. Check department filter selection
3. Verify search spelling
4. Check terminated employee toggle state
5. Refresh page

### Rank Sorting Not Saving

**Problem:** Rank order reverts after refresh

**Solutions:**
1. Verify canEdit permission
2. Check browser console for API errors
3. Ensure internet connection during save
4. Try reordering again

## Related Documentation

- [📋 Training Management](/guide/training) - Training tracking
- [🏢 Companies](/guide/companies) - Company management
- [📅 Vacation](/guide/vacation) - Leave management

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
