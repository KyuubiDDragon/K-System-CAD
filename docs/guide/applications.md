# Applications & Job Applications

Application management system for tracking job applications with customizable questions, status workflow, and interview scheduling.

## Overview

Features:
- Job application CRUD operations
- Customizable application questions per job type
- Three status types (Pending, Approved, Rejected)
- Interview date/time scheduling
- Optional calendar integration
- Job type filtering
- Status filtering
- Search and sorting
- Card-based display
- Authority-specific data isolation

**Required Permission:** `READ_APPLICATION` (view), `WRITE_APPLICATION` (create/edit), `DELETE_APPLICATION` (delete)

## Accessing Applications

**Desktop:** Double-click **Applications** icon
**Menu:** Start Menu → HR → Applications
**Route:** `/application`

## Interface Layout

### Top Section - Filters & Actions

**Action Buttons:**
- **New Application** button (if WRITE permission)

**Search & Sort:**
- Search field (filters by applicant name)
- Sort dropdown:
  - Zuletzt hinzugefügt (newest first) - default
  - Name A-Z
  - Name Z-A
  - Datum aufsteigend (interview date ascending)
  - Datum absteigend (interview date descending)

**Filters:**
- **Job Type Checkboxes** - Filter by job types
  - All types selected by default
  - Uncheck to hide

- **Status Checkboxes** - Filter by application status
  - Ausstehend (Pending) - Orange
  - Angenommen (Approved) - Green
  - Abgelehnt (Rejected) - Red
  - All checked by default

### Main Section - Application Cards

**Card Grid:**
- Responsive grid layout
- 3 columns on large screens
- Cards color-coded by status:
  - **Orange** - Pending
  - **Green** - Approved
  - **Red** - Rejected

**Card Content:**
- **Header:** Applicant name
- **Job Type:** Display job type name
- **Status Chip:** Current status with icon
- **Email:** Applicant email
- **Phone:** Phone number
- **Birthdate:** DD.MM.YYYY format
- **Interview Date:** DD.MM.YYYY (if scheduled)
- **Interview Time:** HH:MM (if scheduled)
- **Added:** Date added to system
- **Edit Button:** Opens edit dialog (if WRITE permission)

**Empty State:**
- "No applications found" message
- Icon display

## Creating Applications

### New Application Dialog

1. Click **New Application** button
2. Dialog opens with tabs

**Tab 1: Info (Basic Information)**

**Applicant Name*** (required)
- Text field
- Full name of applicant

**Job Type*** (required)
- Dropdown select
- Fetched from employee job types
- Determines which questions to ask

**Email*** (required)
- Email field
- Validated format (requires @ and .)

**Phone Number**
- Text field
- Contact number

**Birthdate**
- Date picker
- Optional

**Status*** (required)
- Dropdown select
- Options:
  - Ausstehend (Pending) - default
  - Angenommen (Approved)
  - Abgelehnt (Rejected)

**Additional Info**
- Textarea
- Free-form notes

**Tab 2: Interview & Questions**

**Interview Date**
- Date picker
- Optional

**Interview Time**
- Time picker
- Optional

**Add to Calendar**
- Checkbox
- When checked, sends flag to backend
- Backend handles calendar integration

**Application Questions:**
- Dynamic questions based on selected Job Type
- Questions filtered by:
  - Same job type
  - OR questions with type = 0 (applies to all types)
- Each question shows:
  - Question text
  - Answer textarea

**Questions Load Logic:**
- Changes when Job Type selected/changed
- Answers array updates automatically
- Edit mode pre-fills existing answers

3. Click **Save** to create application
4. Or **Cancel** to discard

**Validation:**
- Name, Job Type, Email, Status required
- Email format validated
- Save disabled until valid
- Tabs hide invalid fields - shows warning if validation fails

## Editing Applications

### Edit Existing Application

1. Click **Edit** button on application card
2. Dialog opens with current data
3. Modify any fields across tabs
4. Questions automatically loaded for application's job type
5. Existing answers pre-filled
6. Click **Save** to update
7. Or **Cancel** to discard

**Edit Behavior:**
- All fields editable
- Questions cannot change (based on type)
- Status can be updated
- Answers can be modified

## Filtering Applications

### By Job Type

1. Checkboxes at top for each job type
2. Uncheck to hide applications of that type
3. Default: All types shown
4. At least one must be selected

### By Status

1. Three status checkboxes:
   - Ausstehend (Pending)
   - Angenommen (Approved)
   - Abgelehnt (Rejected)
2. Uncheck to hide that status
3. Default: All statuses shown

### By Name

1. Type in search field
2. Filters by applicant name
3. Case-insensitive
4. Real-time filtering

**Combined Filtering:**
- All filters work together (AND logic)
- Only applications matching all criteria shown

## Sorting Applications

**Sort Options:**
- **Zuletzt hinzugefügt** - Newest first (by added date)
- **Name A-Z** - Alphabetical ascending
- **Name Z-A** - Alphabetical descending
- **Datum aufsteigend** - Interview date ascending
- **Datum absteigend** - Interview date descending

**Behavior:**
- Dropdown selection
- Applies to filtered results
- Default: Zuletzt hinzugefügt

## Application Questions

### Question Management

**Admin Setup:**
- Questions configured in admin/backend
- Each question has:
  - Question text
  - Associated job type (or 0 for all types)

**Dynamic Loading:**
- When job type selected, questions filter automatically
- Type-specific questions + universal questions (type=0) both load
- Questions appear in Tab 2

**Answer Structure:**
```typescript
{
  applicant_id: number;
  question_id: number;
  question: string; // Question text
  answer: string;   // Applicant's answer
}
```

**Backend Payload:**
```javascript
answers: [
  { question_id: 1, answer: "..." },
  { question_id: 2, answer: "..." }
]
```

## Data Model

**Applicant:**
```typescript
interface Applicant {
  id: number;
  name: string;
  type: number; // Job type ID
  type_name?: string; // Job type name (joined)
  email: string;
  phonenumber?: string;
  birthdate?: string; // YYYY-MM-DD
  status: 'pending' | 'approved' | 'rejected';
  info?: string; // Additional notes
  jobinterviewDate?: string; // YYYY-MM-DD
  jobinterviewTime?: string; // HH:MM
  added: string; // DateTime added
  addToCalendar: boolean | number; // 0/1 or true/false
  answers: Answer[];
  authority_id: number;
}
```

**Question:**
```typescript
interface Question {
  id: number;
  question: string;
  type: number; // Job type ID (0 = all types)
  authority_id: number;
}
```

**JobType:**
```typescript
interface JobType {
  id: number;
  name: string;
}
```

**ApplicationData (form):**
```typescript
interface ApplicationData {
  newApplication: boolean;
  id: number;
  name: string;
  type: number;
  email: string;
  phonenumber: string;
  birthdate: string;
  status: string;
  info: string;
  jobinterviewDate: string;
  jobinterviewTime: string;
  added: string;
  addToCalendar: boolean;
  answers: Answer[];
}
```

## API Integration

**Base Path:** `/application/`

**Get Applications:**
```
GET /application/?action=getApplicants
Response: Applicant[]
```

**Get Questions:**
```
GET /application/?action=getQuestions
Response: Question[]
```

**Get Job Types:**
```
GET /employee/index.php?action=getJobTypes
Response: JobType[]
```

**Add/Edit Application:**
```
POST /application?action=addApplication
Payload: {
  id?: number;
  name: string;
  type: number;
  email: string;
  phonenumber?: string;
  birthdate?: string;
  status: string;
  info?: string;
  jobinterviewDate?: string;
  jobinterviewTime?: string;
  addToCalendar: 0 | 1;
  answers: [
    { question_id: number, answer: string }
  ]
}
```

**Error Handling:**
- Toast notifications for errors
- Console logging for debugging
- Loading states during operations
- Form validation before submit

## Status Workflow

### Status Types

**Pending (Ausstehend):**
- Orange color
- Default status for new applications
- Indicates awaiting review

**Approved (Angenommen):**
- Green color
- Application accepted
- Applicant moves to hiring process

**Rejected (Abgelehnt):**
- Red color
- Application declined
- No further action

**Status Icons:**
- Pending: mdi-clock-outline
- Approved: mdi-check-circle
- Rejected: mdi-close-circle

## Interview Scheduling

### Schedule Interview

1. Open application (new or edit)
2. Go to Interview & Questions tab
3. Select interview date (date picker)
4. Select interview time (time picker)
5. Check "Add to Calendar" if desired
6. Save application

**Calendar Integration:**
- When "Add to Calendar" checked, flag sent as 1
- Backend processes calendar entry (implementation-dependent)
- No UI calendar shown in application view

## Multi-Tenant Security

**Authority Isolation:**
- All applications scoped to `authority_id`
- All questions scoped to `authority_id`
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
  id?: number | string; // Application ID to open
}
```

**Auto-Open Feature:**
- Pass `id` prop or `?id=X` query parameter
- On mount, automatically opens edit dialog for that application
- Useful for notifications/links

## Styling & Theme

**Dark Theme:**
- Dark card backgrounds
- Primary color accents
- Status-coded cards (orange/green/red)

**Responsive:**
- Grid layout adapts to screen size
- Mobile: Single column
- Tablet: 2 columns
- Desktop: 3 columns

**Cards:**
- Elevation on hover
- Color-coded borders
- Compact information display

## Limitations

**What's NOT Available:**

### Application Features
- ❌ Resume/CV upload
- ❌ Cover letter upload
- ❌ Document attachments
- ❌ Application templates
- ❌ Auto-save drafts
- ❌ Application versioning
- ❌ Application history/timeline

### Workflow Features
- ❌ Multi-step approval workflow
- ❌ Approval routing
- ❌ Review assignments
- ❌ Reviewer comments
- ❌ Approval stages
- ❌ Workflow automation
- ❌ Status change notifications

### Communication Features
- ❌ Email applicants from system
- ❌ Email templates
- ❌ Automated responses
- ❌ Interview invitations
- ❌ Rejection letters
- ❌ Communication log

### Interview Features
- ❌ Interview scheduling conflicts check
- ❌ Interviewer assignments
- ❌ Interview room booking
- ❌ Interview feedback forms
- ❌ Interview scoring
- ❌ Interview notes
- ❌ Multiple interview rounds

### Candidate Tracking
- ❌ Candidate pipeline view
- ❌ Kanban board
- ❌ Drag-drop status changes
- ❌ Candidate scoring/rating
- ❌ Skills tracking
- ❌ Experience tracking
- ❌ Education tracking

### Advanced Filtering
- ❌ Filter by interview date range
- ❌ Filter by date added
- ❌ Filter by custom fields
- ❌ Saved searches
- ❌ Advanced search
- ❌ Filter by question answers

### Reporting & Analytics
- ❌ Application statistics
- ❌ Time-to-hire metrics
- ❌ Source tracking
- ❌ Conversion rates
- ❌ Reports by job type
- ❌ Export to Excel/CSV
- ❌ Analytics dashboard

### Integration Features
- ❌ Job posting integration
- ❌ External job board sync
- ❌ ATS integration
- ❌ Background check integration
- ❌ Email system integration

### Question Features
- ❌ Question templates
- ❌ Question categories
- ❌ Required questions
- ❌ Question validation
- ❌ File upload questions
- ❌ Multiple choice questions
- ❌ Conditional questions

### UI Limitations
- ❌ No table view option (only cards)
- ❌ No detail view without edit
- ❌ No bulk operations
- ❌ No application comparison
- ❌ No print view
- ❌ No application duplication

**Current Reality:**
This is a **simple application tracking system** with customizable questions and basic status management. It is not a comprehensive Applicant Tracking System (ATS) or recruitment platform.

## Use Cases

**Application Management:**
- Collect job applications
- Store applicant information
- Track application status
- Schedule interviews

**Custom Questions:**
- Ask job-type-specific questions
- Universal questions for all applications
- Collect structured answers

**Basic Tracking:**
- Filter by status
- Search applicants
- Sort applications
- Visual status overview

## Best Practices

**Setting Up:**
1. Create job types in employee management first
2. Configure questions for each job type
3. Add universal questions (type=0) for all applicants

**Processing Applications:**
1. Review new applications (Pending status)
2. Schedule interviews as needed
3. Update status to Approved/Rejected
4. Use info field for notes

**Question Design:**
1. Keep questions clear and concise
2. Use type-specific questions for relevant details
3. Universal questions for basic information
4. Review answers before status change

**Filtering:**
1. Use job type filter to focus on specific positions
2. Hide rejected applications to focus on active
3. Search for specific applicants quickly

## Troubleshooting

### Can't Create Application

**Problem:** New Application button doesn't work

**Solutions:**
1. Check `WRITE_APPLICATION` permission
2. Verify feature enabled for authority
3. Refresh page
4. Check browser console for errors

### Application Not Saving

**Problem:** Save doesn't persist

**Solutions:**
1. Check all required fields (name, type, email, status)
2. Verify email format valid
3. Check browser console for API errors
4. Ensure internet connection
5. Check backend running

### Questions Not Loading

**Problem:** No questions appear in tab 2

**Solutions:**
1. Select job type first (tab 1)
2. Verify questions exist for that job type
3. Check questions with type=0 exist
4. Refresh page
5. Check `getQuestions` API

### Status Filter Not Working

**Problem:** Applications don't filter

**Solutions:**
1. Check at least one status is checked
2. Check at least one job type is checked
3. Clear search field
4. Refresh page

### Interview Date Not Saving

**Problem:** Date/time doesn't persist

**Solutions:**
1. Verify date and time selected
2. Check date format valid
3. Check browser console for errors
4. Ensure backend accepts datetime fields

## Related Documentation

- [👥 Employee Management](/guide/employee-management) - Job types configuration
- [📅 Calendar](/guide/calendar) - Calendar integration
- [📄 Documents](/guide/documents) - Document management

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
