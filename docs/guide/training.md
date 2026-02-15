# Training Management

Training assignment and tracking system with matrix view showing employee training completion status across categories.

## Overview

Features:
- Matrix view of trainings by category
- Employee training assignment
- Training completion tracking with dates and instructors
- Category and rank filtering
- Company-based filtering
- Training plan HTML generation
- Authority-specific data isolation
- Admin interface for training/category management

**Required Permissions:**
- `READ_TRAINING` (view assignments)
- `WRITE_TRAINING` (assign trainings)
- `ADMIN_READ_TRAINING` (admin configuration)

## Accessing Training

**User View - Training Assignment:**
**Desktop:** Double-click **Training** icon
**Menu:** Start Menu → Employee Management → Training
**Route:** `/trainingassign`

**Admin View - Training Configuration:**
**Desktop:** Start → Administration → Training → Settings
**Route:** `/admin/trainings`

## Training Assignment View

### Interface Layout

**Top Toolbar:**
- **New Assignment** button (if WRITE permission)
- **Training Plan** button (creates HTML table)
- **Expand All** / **Collapse All** category buttons
- Search field (filters employees by name/service number)
- Rank filter (multi-select dropdown)
- Company filter (multi-select dropdown)

**Category Sections:**
- Collapsible panels for each training category
- Each category shows:
  - Category name header
  - Data table with trainings as columns
  - Employees as rows
  - Checkmarks/dates for completed trainings

### Matrix View Structure

**Table Layout:**
```
| Employee Name | Training 1 | Training 2 | Training 3 | ... |
|---------------|-----------|-----------|-----------|-----|
| FGS001 Name   | ✓ Date    |     -     | ✓ Date    | ... |
| FGS002 Name   | ✓ Date    | ✓ Date    |     -     | ... |
```

**Employee Column:**
- Format: `FGS{servicenumber} {name}`
- Example: "FGS042 John Doe"
- Sortable

**Training Columns:**
- One column per training in category
- Width: 150px each
- Center-aligned
- Shows completion status

**Completion Status:**
- **✓ (checkmark)** - Training completed
- **Date** - DD.MM.YYYY format of completion
- **Instructor** - Name shown on hover or in tooltip
- **-** (dash) - Training not assigned/completed

### Filtering

**Search:**
- Filters employees by name or service number
- Real-time filtering
- Case-insensitive
- Clearable

**Rank Filter:**
- Multi-select dropdown
- Shows all ranks from database
- Filters employees by selected ranks
- Clear all to show all ranks

**Company Filter:**
- Multi-select dropdown
- Shows all companies with employee assignments
- Filters employees by company membership
- Uses `employee_ids` array from company records
- Clear all to show all companies

**Combined Filtering:**
- All filters work together (AND logic)
- Empty category sections hide when no matching employees

## Assigning Training

### New Assignment Dialog

1. Click **New Assignment** button
2. Dialog opens with form

**Form Fields:**

**Training Selection*** (required)
- Multi-select autocomplete
- Shows all available trainings
- Grouped by category (if applicable)
- Can assign multiple trainings at once

**Employee Selection*** (required)
- Multi-select autocomplete
- Format: `FGS{servicenumber} {name}`
- Can assign to multiple employees at once
- Filtered by authority

**Date** (optional)
- Date picker
- Completion date
- Format: YYYY-MM-DD (input), DD.MM.YYYY (display)

**Instructor/Notes** (optional)
- Text field
- Name of instructor or additional notes

3. Click **Save**
4. Training assignments created for all selected combinations
5. Matrix refreshes automatically
6. Success toast notification

**Assignment Logic:**
- Creates training_assign record for each training x employee combination
- If training already exists for employee, date/instructor updates (if provided)
- Multiple trainings can be assigned to multiple employees in one action

## Training Plan Generation

### Create Training Plan HTML

1. Click **Training Plan** button
2. HTML table generates automatically
3. HTML copied to clipboard
4. Success notification shows

**Generated Content:**

**Hardcoded Categories** (in specific order):
1. Ground Training
2. Advanced Training
3. Engine Training
4. Ladder Training
5. Rescue Training

**Table Structure:**
```
| Category Name | Datum/Uhrzeit | Dozent | Module benötigt | Teilnahme | Mind. Teilnehmer | Bemerkungen | Ort |
```

**"Module benötigt" Column:**
- Lists employees WITHOUT this training
- Format: FGS001, FGS002, FGS003
- Company filtering applied for specific categories:
  - Engine Training: Filters by "Engine Company" members
  - Ladder Training: Filters by "Ladder Company" members
  - Rescue Training: Filters by "Rescue Company" members

**Static Fields:**
- Bemerkungen: "Unterlagen durchlesen & verinnerlichen"
- Other columns: Empty for manual entry

**HTML Format:**
- Styled HTML table with borders
- Grey headers (rgb(201,201,201))
- Bold text in headers
- Fixed column widths
- Ready for email or document insertion

**Usage:**
- Paste into TiptapEditor (Messages, Documents)
- Email to department heads
- Print for meeting materials

## Admin Configuration

### Accessing Admin View

**Route:** `/admin/trainings`

**Two Sections:**
1. Training Management
2. Category Management

### Training Management

**Training Table:**

**Columns:**
- ID
- Name
- Category (short name)
- Sort Order
- Actions (Edit, Delete)

**New Training:**
1. Click **New Training** button
2. Fill form:
   - **Name*** (required) - Training name
   - **Category*** (required) - Select from categories
   - **Sort Order*** (required) - Number for ordering
3. Click **Save**
4. Training appears in list

**Edit Training:**
1. Click Edit icon
2. Modify fields
3. Save
4. Matrix view updates

**Delete Training:**
1. Click Delete icon
2. Confirm deletion
3. Training removed
4. All training assignments for this training remain in database (orphaned)

### Category Management

**Category Table:**

**Columns:**
- ID
- Name
- Abbreviation (Short)
- Sort Order
- Actions (Edit, Delete)

**New Category:**
1. Click **New Category** button
2. Fill form:
   - **Name*** (required) - Category full name
   - **Abbreviation*** (required) - Short name (e.g., "GT" for Ground Training)
   - **Sort Order*** (required) - Display order
3. Click **Save**
4. Category appears in list

**Edit Category:**
1. Click Edit icon
2. Modify fields
3. Save
4. All trainings in category updated

**Delete Category:**
1. Click Delete icon
2. Confirm deletion
3. Category removed
4. Trainings in this category lose category assignment (catId becomes invalid)
5. Both category and training lists refresh

**Sort Order:**
- Categories sort by `sort_order` ascending
- Trainings within category also sort by `sort_order`
- Lower numbers appear first

## Data Model

**Training:**
```typescript
interface Training {
  id: number;
  name: string;
  catId: number;
  cat_name: string; // Category name (joined)
  cat_short: string; // Category abbreviation (joined)
  sort_order: number;
  authority_id: number;
}
```

**TrainingCategory:**
```typescript
interface TrainingCategory {
  id: number;
  name: string;
  short: string; // Abbreviation
  sort_order: number;
  authority_id: number;
}
```

**TrainingAssign:**
```typescript
interface TrainingAssign {
  id: number;
  training_id: number;
  employeeid: number;
  date: string; // YYYY-MM-DD HH:MM:SS or YYYY-MM-DD
  instructor: string;
  authority_id: number;
}
```

**Employee (for matrix):**
```typescript
{
  id: number;
  name: string; // Formatted as "FGS{servicenumber} {name}"
  servicenumber: number;
  rank_id: number;
  rank_name: string;
  trainingDetails: {
    [trainingId: string]: {
      date: string;
      instructor: string;
    }
  };
}
```

## API Integration

### User View API

**Base Path:** `/training/`

**Get Trainings:**
```
GET /training/?action=getTrainings
Response: Training[]
```

**Get Training Assigns:**
```
GET /training/?action=getTrainingAssigns
Response: TrainingAssign[]
```

**Get Employees:**
```
GET /training/?action=getEmployees
Response: Employee[] (with rank info)
```

**Get Ranks:**
```
GET /training/?action=getRanks
Response: Rank[]
```

**Get Companies:**
```
GET /training/?action=getCompanies
Response: Company[] (with employee_ids)
```

**Save Training Assignment:**
```
POST /training/?action=saveTraining
Payload: {
  trainingIds: number[];
  employeeIds: number[];
  date: string;
  instructor: string;
}
```

### Admin View API

**Base Path:** `/admin/training/`

**Get Trainings:**
```
GET /admin/training?action=getTrainings
Response: Training[]
```

**Get Categories:**
```
GET /admin/training?action=getCategories
Response: TrainingCategory[]
```

**Save Training:**
```
POST /admin/training?action=saveTrainings
Payload: {
  id?: number;
  name: string;
  catId: number;
  sort_order: number;
}
```

**Save Category:**
```
POST /admin/training?action=saveCategories
Payload: {
  id?: number;
  name: string;
  short: string;
  sort_order: number;
}
```

**Delete Training:**
```
POST /admin/training?action=deleteTrainings
Payload: { id: number }
```

**Delete Category:**
```
POST /admin/training?action=deleteCategories
Payload: { id: number }
```

## Multi-Tenant Security

**Authority Isolation:**
- All trainings, categories, and assignments scoped to `authority_id`
- Users only see data from their organization
- Backend filters by authority automatically
- Cross-organization access prevented

## Desktop Window Mode

**Props Support:**
```typescript
interface Props {
  meta?: Record<string, any>;
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
  id?: number | string; // Training ID (for future highlight feature)
}
```

**Permission Resolution:**
- Checks props, route.meta, and ALL_PERMISSIONS
- Admin view defaults to true for canEdit/canDelete

## Limitations

**What's NOT Available:**

### Training Features
- ❌ Test/quiz system
- ❌ Training materials (documents, videos, presentations)
- ❌ Prerequisites
- ❌ Training duration tracking
- ❌ Training location
- ❌ Online/in-person designation
- ❌ Instructor assignment (only notes field)
- ❌ Training priority levels
- ❌ Training status workflow (only completed/not completed)

### Certification Features
- ❌ Certification tracking separate from training
- ❌ Certification expiration/renewal
- ❌ Certificate upload
- ❌ Certificate generation
- ❌ Recertification reminders
- ❌ Compliance tracking
- ❌ Certification types

### Assignment Features
- ❌ Bulk assignment by department
- ❌ Due dates for training
- ❌ Overdue tracking
- ❌ Automatic reminders/notifications
- ❌ Recurring training (annual/bi-annual)
- ❌ Email notifications
- ❌ Calendar integration
- ❌ Scheduled classes/sessions

### Attendance Features
- ❌ Class sessions
- ❌ Attendance tracking
- ❌ Sign-in sheets
- ❌ Attendance reports
- ❌ No-show tracking
- ❌ Makeup sessions

### Instructor Features
- ❌ Instructor management
- ❌ Instructor qualifications
- ❌ Instructor scheduling
- ❌ Instructor assignments

### Training Records
- ❌ Training transcript/history per employee
- ❌ Total training hours calculation
- ❌ Test scores
- ❌ Pass/fail tracking
- ❌ Training notes/comments
- ❌ Export to PDF

### Reporting & Analytics
- ❌ Completion rates
- ❌ Overdue reports
- ❌ Training hours reports
- ❌ Compliance reports
- ❌ Export functionality
- ❌ Training statistics
- ❌ Trend analysis

### Advanced Features
- ❌ Training calendar view
- ❌ Training materials library
- ❌ Learning management system (LMS)
- ❌ Course catalog
- ❌ External training tracking
- ❌ Training budget tracking
- ❌ Vendor management
- ❌ Training evaluation/feedback
- ❌ Skills matrix
- ❌ Competency tracking

### UI Limitations
- ❌ Only matrix view (no list view)
- ❌ No employee detail drill-down
- ❌ No training detail view
- ❌ Limited sorting (only by employee name)
- ❌ No export from matrix
- ❌ No print-optimized view
- ❌ Training plan hardcoded to 5 categories

**Current Reality:**
This is a **training assignment tracker** with a matrix view showing who has completed which trainings. It is not a comprehensive Learning Management System (LMS), certification tracker, or training administration platform.

## Use Cases

**Training Tracking:**
- Track which employees completed which trainings
- Visual matrix of training completion status
- Quick identification of training gaps

**Training Assignment:**
- Assign multiple trainings to multiple employees
- Record completion dates and instructors
- Bulk assignment workflows

**Planning:**
- Generate training plan HTML for missing trainings
- Company-specific filtering for specialized trainings
- Identify employees needing specific trainings

**Organization:**
- Categorize trainings by type
- Order trainings and categories logically
- Filter by rank and company

## Best Practices

**Setting Up:**
1. Create categories first in admin view
2. Add trainings to categories with clear names
3. Use sort_order for logical grouping (10, 20, 30, etc.)
4. Keep category abbreviations short (2-3 characters)

**Assigning Trainings:**
1. Use bulk assignment for efficiency
2. Record dates when available
3. Include instructor name in notes field
4. Assign related trainings together

**Using Matrix View:**
1. Filter by company for specialized trainings
2. Use search to find specific employees
3. Collapse unused categories
4. Generate training plans regularly

**Training Plans:**
1. Review generated plan before distributing
2. Update company memberships for accurate filtering
3. Use for planning sessions/courses
4. Share with department heads

## Troubleshooting

### Can't Assign Training

**Problem:** New Assignment button missing or doesn't work

**Solutions:**
1. Check `WRITE_TRAINING` permission
2. Verify feature enabled for authority
3. Refresh page
4. Check browser console for errors

### Training Not Saving

**Problem:** Assignment doesn't persist

**Solutions:**
1. Check all required fields filled (trainings, employees)
2. Verify internet connection
3. Check browser console for API errors
4. Ensure trainings/employees exist
5. Verify authority_id matches

### Matrix Not Loading

**Problem:** Empty matrix or loading spinner

**Solutions:**
1. Check `READ_TRAINING` permission
2. Verify backend is running
3. Check browser console for API errors
4. Ensure trainings and categories exist
5. Check employees exist in system

### Training Plan Copy Fails

**Problem:** Clipboard copy doesn't work

**Solutions:**
1. Check browser clipboard permissions
2. Try again
3. Check browser console for errors
4. Verify categories/trainings exist

### Category/Training Not Deleting

**Problem:** Delete fails in admin view

**Solutions:**
1. Check permissions
2. Deleting category may affect trainings (catId becomes invalid)
3. Consider editing instead
4. Check for error messages

### Filters Not Working

**Problem:** Rank/company filters don't filter

**Solutions:**
1. Verify rank_id on employees
2. Check company employee_ids array populated
3. Clear filters and try again
4. Refresh page

## Related Documentation

- [👥 Employee Management](/guide/employee-management) - Managing employees
- [🏢 Companies](/guide/companies) - Company management
- [⚙️ Admin](/guide/admin/trainings) - Training configuration

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
