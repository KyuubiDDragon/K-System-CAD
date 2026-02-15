# Report Management

Dynamic report system with custom fields, categories, status workflow, sharing capabilities, and filtering for structured documentation and tracking.

## Overview

Features:
- Dynamic report creation with custom fields
- Report categories for organization
- Report codes for classification
- Status workflow management
- Person and company associations
- Custom field definitions per authority
- Report sharing with access levels
- Advanced filtering (6 filter types + custom fields)
- Tab-based views (all, shared with me, shared by me)
- Pin/unpin important reports
- PDF download functionality
- Real-time search across multiple fields
- Authority-based data isolation

**Required Permissions:**
- `READ_REPORT` (view reports)
- `WRITE_REPORT` (create/edit reports)
- `DELETE_REPORT` (delete reports)
- `SHARE_REPORT` (share reports with others)

## Accessing Reports

**Desktop:** Double-click **Reports** icon
**Menu:** Start Menu → Reports & Documents → Reports
**Route:** `/report`

## Interface Layout

### Header Section

**Title:** "Berichte" with file-document icon

**Action Button:**
- **New Report** button (if WRITE permission)
- Opens category selection dialog

### Filter Card

**Filter Type Buttons** (6 types with counts):
- **All** - All reports
- **Own** - Reports created by you
- **Missing** - Reports needing your input
- **Incomplete** - Reports with missing employee data
- **Open** - Unapproved reports
- **Pending** - Complete but unapproved reports

**Search Field:**
- Searches title, location, and report code
- Real-time filtering
- Clearable

**Dropdown Filters:**
- **Category** - Filter by report category
- **Creator** - Filter by employee who created report

**Custom Fields Filter** (expansion panel):
- Dynamic filters based on authority's custom fields
- Shows count of available custom fields
- Supports text, number, date, boolean, select, multiselect
- Each field type has appropriate input widget

### Tab Navigation

**Four Tabs:**
1. **All Reports** - Your organization's reports
2. **Shared With Me** - Reports other authorities shared with you
3. **Shared By Me** - Reports you shared with other authorities
4. **All Shared** - Combined view of all sharing activity

### Report Table

**Columns:**
- **Status Icon** - Approved (green check) / Open (orange alert)
- **Report Info** - Title with code, optional pin icon
- **Category** - Report category name
- **Status** - Workflow status name
- **Creator** - Employee who created report
- **Created** - Creation date (DD.MM.YYYY)
- **Updated** - Last modified date (DD.MM.YYYY)
- **Actions** - Edit, Share, Delete, Download, Pin buttons

**Row Features:**
- Hover effect
- Pinned reports show blue pin icon
- Missing employees show orange alert icon with tooltip
- Sharing indicators:
  - Blue arrow-down-left: Shared with you
  - Green arrow-up-right: Shared by you

**Pagination:**
- 25 reports per page
- Page navigation at bottom

**Sorting:**
- Click any sortable column header to sort
- Approved status, title, category, status, creator, dates all sortable

## Creating Reports

### Step 1: Select Category

1. Click **New Report** button
2. **Category Selection Dialog** opens
3. Select report category from dropdown
4. Click **Continue**

**Categories:**
- Defined by admin in category management
- Each authority can have custom categories
- Examples: Incident Report, Inspection Report, etc.

### Step 2: Fill Report Form

Dynamic form opens based on selected category and authority type.

**Form provided by:** `AuthorityAddReportDialog` component (async loaded)

**Common Fields** (passed to component):
- Categories list
- Employees list
- Persons list
- Companies list
- Report codes list
- Report statuses list
- Current authority context

**Form Behavior:**
- Fields vary based on custom field configuration
- Required fields marked
- Validation before save
- Auto-saves to database via API

3. Fill all required fields
4. Click **Save**
5. Report created and appears in table

## Editing Reports

### Edit Your Own Reports

1. Click **Edit** button (file-document-edit icon) on report row
2. Edit dialog opens with current data
3. Modify any fields
4. Click **Save** to update
5. Report refreshes in table

**Edit Form:**
- Same component as Add: `AuthorityEditReportDialog`
- Pre-filled with current report data
- All fields editable (subject to custom field configuration)

### View Shared Reports (Read-Only)

1. In "Shared With Me" or "All Shared" tab
2. Click **Edit** button (file-document-edit icon)
3. **Shared Report View** dialog opens (fullscreen)
4. Read-only display shows:
   - Full report details
   - Source authority information
   - Sharing metadata (shared_at, access_level)
   - Creator details
   - All custom field values

**Access Levels:**
- `read` - Read-only access (most common for shared reports)
- `edit` - Edit access (if explicitly granted)

## Deleting Reports

1. Click **Delete** button (red trash icon) on report row
2. Confirmation dialog appears
3. Shows report title in warning
4. Click **Delete** to confirm
5. Or **Cancel** to abort
6. Report removed from database

**Warning:** Deletion is permanent.

## Sharing Reports

### Share a Report

1. Click **Share** button (share-variant icon) on report row
2. **Report Sharing Dialog** opens
3. Configure sharing settings:
   - Select recipient authorities
   - Set access level (read/edit)
   - Optional expiration settings
4. Click **Share**
5. Report appears in recipient's "Shared With Me" tab

**Requirements:**
- SHARE_REPORT or ALL_PERMISSIONS permission
- WRITE_REPORT permission (canEdit)

**Sharing Indicators:**
- Green arrow-up-right icon in "Shared By Me" or "All Shared" tabs
- Tooltip shows recipient names/authorities

### View Shared Reports

**Shared With Me Tab:**
- Shows reports other authorities shared with you
- Blue arrow-down-left icon
- Tooltip shows source authority
- Click to view in read-only mode

**Shared By Me Tab:**
- Shows reports you shared with others
- Green arrow-up-right icon
- Tooltip shows recipients
- Full edit access retained

**All Shared Tab:**
- Combined view of both incoming and outgoing shares
- Sharing text column explains direction
- Mixed icons based on sharing type

## Report Features

### Pin/Unpin Reports

**Pin Important Reports:**
1. Click **Pin** button (pin icon) on report row
2. Report marked as pinned
3. Blue pin icon appears in report info column
4. Pinned status persists

**Unpin:**
1. Click **Unpin** button (pin-off icon)
2. Pin removed

**Use Case:** Keep frequently accessed reports easily identifiable

### Download PDF

1. Click **Download** button (download icon) on report row
2. System generates PDF (API call with blob response)
3. Loading spinner shows during generation
4. PDF downloads with filename format: `report_{id}_{title}.pdf`

**API Endpoint:** `GET /report/?action=getReportPdf&report_id={id}`

### Missing Employees

**What It Means:**
- Some reports require input from multiple employees
- `missing_employees` array tracks who hasn't provided input yet

**Visual Indicator:**
- Orange account-alert icon in status column
- Tooltip shows names of missing employees
- Format: "[servicenumber] name"

**Filter by Missing:**
- Click **Missing** filter button
- Shows only reports where YOU are in missing_employees array

## Filtering Reports

### Filter Type Buttons

**All (Count):**
- Shows all reports
- Number indicates total report count

**Own (Count):**
- Shows only reports you created (creator === currentUserId)
- Number shows your report count

**Missing (Count):**
- Shows reports needing YOUR input (you in missing_employees)
- Number shows how many reports need you

**Incomplete (Count):**
- Shows reports with ANY missing employee data
- Number shows incomplete report count

**Open (Count):**
- Shows unapproved reports (approved === false)
- Number shows unapproved count

**Pending (Count):**
- Shows complete but unapproved reports
- Must have: no missing employees AND not approved
- Number shows pending approval count

### Text Search

**Searches Across:**
- Report title
- Location field
- Report code ID

**Behavior:**
- Case-insensitive
- Partial matching (contains)
- Real-time filtering as you type
- Clear button to reset

### Dropdown Filters

**Category Filter:**
- Select specific category
- Shows only reports in that category
- Exact ID match

**Creator Filter:**
- Select specific employee
- Shows only reports created by that employee
- Dropdown shows: "[servicenumber] name"

### Custom Field Filters

**When Custom Fields Exist:**
- Expansion panel appears in filter card
- Shows count: "Custom Fields (X)"
- Expand to see all available filters

**Filter Types by Field Type:**

**Text/Textarea:**
- Text input field
- Case-insensitive contains search

**Number:**
- Number input field
- Exact match

**Date:**
- Date picker
- Exact date match

**Boolean:**
- Yes/No dropdown
- Exact true/false match

**Select (single):**
- Dropdown with options
- Exact value match

**Multiselect:**
- Multi-select with chips
- Matches if ANY selected value present

**Combined Filtering:**
- All filters work together (AND logic)
- Only reports matching ALL active filters shown
- Empty/null filters ignored

## Data Model

**Report (Base):**
```typescript
interface Report {
  id: number;
  title: string;
  description?: string;
  text?: string; // Report content
  category_id: number;
  cat_name?: string; // Category name (joined)
  report_code_id?: number;
  code_name?: string; // Code name (joined)
  status_id?: number;
  status_name?: string; // Status name (joined)
  creator: number; // Employee ID
  emp_name?: string; // Creator name (joined)
  created_at: string; // YYYY-MM-DD HH:MM:SS
  updated_at: string; // YYYY-MM-DD HH:MM:SS
  approved: boolean; // Approval status
  pinned: boolean; // Pin status
  location?: string;
  custom_fields: string | object; // JSON string or object
  missing_employees?: number[]; // Employee IDs needing input
  authority_id: number;
}
```

**Category:**
```typescript
interface Category {
  id: number;
  name: string;
  authority_id: number;
}
```

**ReportCode:**
```typescript
interface ReportCode {
  id: number;
  code: string;
  name: string;
  authority_id: number;
}
```

**ReportStatus:**
```typescript
interface ReportStatus {
  id: number;
  name: string;
  color?: string;
  authority_id: number;
}
```

**Employee (for creator):**
```typescript
interface Employee {
  id: number;
  name: string;
  servicenumber: number;
  display_name: string; // "[servicenumber] name"
}
```

## API Integration

**Base Path:** `/report/`

**Get All Reports:**
```
GET /report/?action=getReports
Response: Report[]
```

**Get Categories:**
```
GET /report/?action=getCategories
Response: Category[]
```

**Get Employees:**
```
GET /report/?action=getEmployees
Response: Employee[]
```

**Get Report Codes:**
```
GET /report/?action=getCodes
Response: ReportCode[]
```

**Get Report Statuses:**
```
GET /report/?action=getStatuses
Response: ReportStatus[]
```

**Get Persons:**
```
GET /personfile/?action=getPersons
Response: PersonFile[]
```

**Get Companies:**
```
GET /company/?action=getOnlyCompanies
Response: Company[]
```

**Get Custom Fields:**
```
GET /admin/report_fields.php?action=getReportFields
Response: CustomField[]
```

**Delete Report:**
```
POST /report/?action=deleteReport
Payload: { id: number }
```

**Pin/Unpin Report:**
```
POST /report/?action=pinReport
Payload: { id: number, pinned: boolean }
```

**Download PDF:**
```
GET /report/?action=getReportPdf&report_id={id}
Response: Blob (PDF file)
```

**Log Access:**
```
POST /logging/?action=logAccess
Payload: {
  report_id: number,
  action: string
}
```

**Sharing API (via ReportService):**
- `ReportService.getSharedReports()` - Get reports shared with/by you
- `ReportService.getReportSharing(reportId)` - Get sharing info for report
- `ReportService.getSharedReport(reportId)` - Get full shared report details

## Admin Configuration

**Admin Routes** (accessible from Start → Administration → Reports):

**Custom Fields:** `/admin/reportfields`
- Define custom field types
- Configure per authority
- Set field properties (name, type, required, etc.)

**Categories:** Route unknown (managed via main interface)
- Create report categories
- Organize reports by type

**Report Status:** Route unknown
- Configure workflow statuses
- Set status colors and names

**Report Codes:** Route unknown
- Organize report classification codes
- Assign codes to reports

## Multi-Tenant Security

**Authority Isolation:**
- All reports scoped to `authority_id`
- All categories scoped to `authority_id`
- All custom fields scoped to `authority_id`
- Users only see data from their organization
- Backend filters by authority automatically
- Sharing allows cross-authority access (with tracking)

## Desktop Window Mode

**Props Support:**
```typescript
interface Props {
  meta?: Record<string, any>;
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
  desktopWindow?: boolean;
  id?: string | number; // Report ID to auto-open
}
```

**Auto-Open Feature:**
- Pass `id` prop or `?id=X` query parameter
- On mount, automatically opens edit/view dialog for that report
- Useful for notifications/links

## Limitations

**What's NOT Available:**

### Report Features
- ❌ No report templates in UI (only Add/Edit dialogs)
- ❌ No draft auto-save
- ❌ No report versioning/history
- ❌ No report comments/notes section
- ❌ No file attachments to reports (use File Manager separately)
- ❌ No image upload to reports
- ❌ No report cloning/duplication
- ❌ No bulk operations (multi-select)
- ❌ No report export except PDF

### Workflow Features
- ❌ No multi-step approval workflow
- ❌ No approval routing/assignment
- ❌ No workflow stages beyond status
- ❌ No automated status transitions
- ❌ No workflow notifications

### Reporting & Analytics
- ❌ No report statistics dashboard
- ❌ No analytics on report data
- ❌ No charts/graphs of report trends
- ❌ No export to Excel/CSV from table
- ❌ No report grouping/aggregation
- ❌ No scheduled report generation

### Advanced Filtering
- ❌ No date range filters (created/updated)
- ❌ No saved filter presets
- ❌ No advanced search builder
- ❌ No filter by person/company associations
- ❌ No filter by shared status

### Sharing Features
- ❌ No expiration dates on shares
- ❌ No share link generation (direct sharing only)
- ❌ No public/anonymous sharing
- ❌ No share notifications
- ❌ No share revocation UI (must use sharing dialog)
- ❌ No audit log of sharing changes

### UI Limitations
- ❌ No list view (only table)
- ❌ No detail preview pane
- ❌ No full-screen report view (except shared reports)
- ❌ No print view optimization
- ❌ No column visibility customization
- ❌ No column reordering
- ❌ No inline editing

**Current Reality:**
This is a **dynamic report management system** with custom field support, filtering, sharing, and status workflow. It is not a comprehensive reporting platform, business intelligence tool, or document management system with versioning.

## Use Cases

**Structured Reporting:**
- Create incident reports with custom fields
- Track inspection reports by category
- Organize reports with codes and statuses
- Standardize data collection per authority

**Collaboration:**
- Share reports across authorities
- Track who needs to provide input
- Manage approval workflows via status
- Download PDFs for distribution

**Organization:**
- Filter by multiple criteria
- Pin important reports
- Search across reports
- Tab-based views for different perspectives

## Best Practices

**Creating Reports:**
1. Choose appropriate category first
2. Fill all required custom fields
3. Associate relevant persons/companies
4. Select correct report code
5. Set initial status

**Using Filters:**
1. Start with filter type buttons for quick views
2. Combine with search for specific reports
3. Use custom field filters for detailed searches
4. Clear filters when switching tasks

**Sharing Reports:**
1. Only share completed reports
2. Set appropriate access level (usually read)
3. Track sharing in "Shared By Me" tab
4. Review shared reports regularly

**Data Quality:**
1. Use consistent report codes
2. Update status as work progresses
3. Ensure required employees complete input
4. Pin critical reports for quick access

## Troubleshooting

### Can't Create Report

**Problem:** New Report button missing or doesn't work

**Solutions:**
1. Check WRITE_REPORT permission
2. Verify categories exist in system
3. Ensure custom fields configured (if required)
4. Refresh page

### Report Not Saving

**Problem:** Save doesn't persist in Add/Edit dialog

**Solutions:**
1. Check all required custom fields filled
2. Verify internet connection
3. Check browser console for API errors
4. Ensure all dropdown data loaded (categories, codes, statuses)

### Filters Not Working

**Problem:** Filters don't show expected reports

**Solutions:**
1. Check multiple filters not conflicting
2. Clear search field
3. Reset custom field filters
4. Verify filter button selection (all/own/etc.)
5. Refresh page to reload data

### Can't Share Report

**Problem:** Share button missing or doesn't work

**Solutions:**
1. Check SHARE_REPORT or ALL_PERMISSIONS permission
2. Verify WRITE_REPORT permission (canEdit required)
3. Check if sharing dialog loads
4. Review browser console for errors

### PDF Download Fails

**Problem:** PDF doesn't download or errors

**Solutions:**
1. Check report has content
2. Verify backend PDF generation working
3. Check browser console for API errors
4. Try different browser
5. Check popup blocker settings

### Missing Employees Not Updating

**Problem:** Missing employees indicator incorrect

**Solutions:**
1. Verify employee marked as complete in report
2. Check backend updates missing_employees array
3. Refresh page to reload report data
4. Review report edit form for completion status

## Related Documentation

- [📄 Documents](/guide/documents) - Document management
- [👥 Person Files](/guide/person-files) - Person associations
- [🏢 Companies](/guide/companies) - Company associations

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
