# Global Search

K-Systems features a global search system that allows you to quickly find employees, reports, documents, and other data across the system.

## Overview

The Global Search provides:
- Search across 16 data categories
- Permission-aware results (only shows what you can access)
- Recent search history (persisted to browser)
- Quick action buttons
- Keyboard shortcuts
- Search prefix support for category-specific searches

**Required Permission:** Varies by category (READ_EMPLOYEE, READ_REPORT, etc.)

## Opening Global Search

### Keyboard Shortcut

Press **Ctrl+K** (Windows/Linux) or **Cmd+K** (Mac) from anywhere in the application to open the global search dialog.

### Close Search

Press **Esc** to close the search dialog.

## Search Interface

The search appears as a centered modal dialog overlay with:

**When Empty (No Search Query):**
- Recent searches list (last 10 searches)
- Quick action buttons:
  - Create New Person
  - Create New Report

**When Searching:**
- Search input field
- Results grouped by category
- Up to 5 results per category shown
- "Show All" button to expand category

## Search Categories

The search indexes the following 16 categories:

1. **Person** - Person files (Personenakten)
2. **Company** - Company records (Firmen)
3. **Report** - Reports (Berichte)
4. **Document** - Documents (Dokumente)
5. **Vehicle** - Vehicle files (Fahrzeugakten)
6. **Employee** - Employees (Mitarbeiter)
7. **Calendar** - Calendar events (Kalender)
8. **Todo** - Todo items (Aufgaben)
9. **Blackboard** - Blackboard entries (Schwarzes Brett)
10. **Invoice** - Invoices (Rechnungen)
11. **Message** - Internal messages (Nachrichten)
12. **Training** - Training records (Schulungen)
13. **Application** - Applications/Apps (Anwendungen)
14. **Note** - Notes (Notizen)
15. **Map** - Map entries (Karte)
16. **Dispatch** - Dispatch records (Einsatzleitstelle)

**Note**: You will only see categories you have permission to access. Categories require their respective READ permissions.

## Using Search

### Basic Search

1. Press **Ctrl+K** to open search
2. Type your search query
3. Results appear automatically grouped by category
4. Use **arrow keys** (↑↓) to navigate results
5. Press **Enter** to open selected result
6. Or click a result to open it

### Search Prefix Support

You can prefix your search to filter by specific category:

**Category Prefixes:**
- `person:` - Search persons only
- `company:` - Search companies only
- `report:` - Search reports only
- `document:` - Search documents only
- `vehicle:` - Search vehicles only
- `employee:` - Search employees only
- `calendar:` - Search calendar events only
- `todo:` - Search todos only
- `blackboard:` - Search blackboard only
- `invoice:` - Search invoices only
- `message:` - Search messages only
- `training:` - Search training only
- `application:` - Search applications only
- `note:` - Search notes only
- `map:` - Search map only
- `dispatch:` - Search dispatch only

**Example:**
```
employee:John Smith
```
Searches only in employees for "John Smith"

**Example:**
```
report:Fire
```
Searches only in reports for "Fire"

### Fuzzy Search Parameter

The search supports a fuzzy search mode (enabled via backend API parameter) that provides typo-tolerant matching. This is configured on the backend.

## Search Behavior

**Minimum Characters:**
- Must type at least 1 character to search

**Results Limit:**
- Shows up to 5 results per category by default
- Click "Show All" to expand category and see more results

**Permission Filtering:**
- Results are automatically filtered by authority_id (multi-tenant)
- You only see records you have permission to access

**Result Format:**
Each result shows:
- **Icon** - Category-specific icon
- **Title** - Primary information (name, title, etc.)
- **Subtitle** - Secondary information (department, status, date, etc.)
- **Route** - Click to navigate to detail view

## Recent Searches

**Recent Search History:**
- Last 10 searches are saved
- Persisted to browser localStorage
- Displayed when search dialog opens (empty query)
- Click a recent search to repeat it
- Click "Clear History" to remove all recent searches

**Storage:**
- Saved as `localStorage.setItem('recentSearches', JSON.stringify(searches))`
- Persists across browser sessions
- Limited to 10 most recent items

## Quick Actions

When search dialog opens with no query, two quick action buttons appear:

1. **Create New Person** - Opens person creation dialog
   - Only visible if user has permission to create persons

2. **Create New Report** - Opens report creation dialog
   - Only visible if user has permission to create reports

## Keyboard Shortcuts

### Open/Close
- **Ctrl+K** (or **Cmd+K** on Mac) - Open search
- **Esc** - Close search

### Navigation
- **↓** (Down Arrow) - Select next result
- **↑** (Up Arrow) - Select previous result
- **Enter** - Open selected result
- **Tab** - Move to next result (alternative to down arrow)

## API Integration

**Endpoint:** `/search/`

**Method:** GET

**Parameters:**
- `q` - Search query string (required)
- `fuzzy` - Enable fuzzy matching (optional, boolean)

**Response Format:**
```json
{
  "results": [
    {
      "id": 123,
      "type": "employee",
      "title": "John Smith",
      "subtitle": "Firefighter - Fire Rescue",
      "icon": "mdi-account",
      "route": "/employee/123"
    },
    ...
  ]
}
```

**Multi-Tenant:** All results are automatically filtered by the user's `authority_id` from their JWT token.

## Search by Category

### Employees

**What's Searched:**
- Employee name
- Department
- Rank/Position
- Employee ID

**Required Permission:** `READ_EMPLOYEE`

**Result Shows:**
- Employee name
- Department and rank
- Icon: `mdi-account`

**Navigation:** Click to open employee detail view at `/employee/{id}`

### Reports

**What's Searched:**
- Report title
- Report number/ID
- Category
- Content (if indexed)

**Required Permission:** `READ_REPORT`

**Result Shows:**
- Report title
- Status and date
- Icon: `mdi-file-document`

**Navigation:** Click to open report detail view at `/report/{id}`

### Documents

**What's Searched:**
- Document title
- Document description
- File names
- Document area

**Required Permission:** `READ_DOCUMENT`

**Result Shows:**
- Document title
- Document area
- Icon: `mdi-file`

**Navigation:** Click to open document view at `/document/{id}`

### Calendar Events

**What's Searched:**
- Event title
- Event description
- Location
- Attendees

**Required Permission:** `READ_CALENDAR`

**Result Shows:**
- Event title
- Date and time
- Icon: `mdi-calendar`

**Navigation:** Click to open calendar event at `/calendar?id={id}`

### Messages

**What's Searched:**
- Message title/subject
- Message content
- Sender/recipient names

**Required Permission:** `READ_MESSAGES`

**Result Shows:**
- Message title
- Sender/recipient
- Icon: `mdi-message`

**Navigation:** Click to open message at `/message?id={id}`

### Other Categories

Similar patterns apply for:
- **Persons** (`READ_PERSON_FILE`) → `/person/{id}`
- **Companies** (`READ_COMPANY`) → `/company/{id}`
- **Vehicles** (`READ_VEHICLE_FILE`) → `/vehicleFile/{id}`
- **Todos** (`READ_TODO`) → `/todo?id={id}`
- **Invoices** (`READ_INVOICE`) → `/invoice/{id}`
- **Training** (`READ_TRAINING`) → `/training/{id}`
- **Blackboard** (`READ_BLACKBOARD`) → `/blackboard/{id}`
- **Map** (permission varies) → `/map?id={id}`
- **Dispatch** (permission varies) → `/dispatch?id={id}`
- **Notes** (permission varies) → `/notes?id={id}`
- **Applications** - Launches application

## Tips & Best Practices

**Effective Searching:**
- Start with specific terms for best results
- Use category prefixes to narrow results (e.g., `employee:john`)
- Recent searches show your most common queries
- Press Ctrl+K from anywhere for instant access

**Keyboard Navigation:**
- Ctrl+K is the fastest way to find anything
- Arrow keys and Enter keep hands on keyboard
- Tab through results if you prefer
- Esc quickly closes search

**Privacy:**
- Search only shows records you have permission to access
- Multi-tenant: You only see records from your authority
- Results respect all role-based permissions

## Troubleshooting

### No Results Found

**Problem**: Search returns no results

**Solutions:**
1. Check spelling of search query
2. Try fewer or different keywords
3. Verify you have permission to access that category
4. Check if record exists in the system
5. Remove category prefix to search all categories

### Search Not Opening

**Problem**: Ctrl+K doesn't work

**Solutions:**
1. Check if browser extension is blocking shortcut
2. Try clicking elsewhere on page first (ensure focus)
3. Check browser console for JavaScript errors
4. Try refreshing the page

### Wrong Category Results

**Problem**: Getting results from wrong category

**Solutions:**
1. Use category prefix to filter (e.g., `employee:query`)
2. Check if search term appears in multiple categories
3. Be more specific with search terms

### Recent Searches Not Saving

**Problem**: Recent searches disappear

**Solutions:**
1. Check browser allows localStorage
2. Check private/incognito mode (localStorage may not persist)
3. Check browser storage quota
4. Clear browser cache and try again

## Related Documentation

- [Getting Started](/guide/getting-started) - Learn the basics of K-Systems
- [Desktop Mode](/guide/desktop-mode) - Desktop interface overview
- [Employee Management](/guide/employee-management) - Managing employees
- [Reports](/guide/reports) - Creating and managing reports
- [Documents](/guide/documents) - Working with documents

---

**Last Updated:** 2025-10-01
**Version:** 2.0.0 (Corrected to match actual implementation)
