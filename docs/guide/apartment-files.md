# Apartment & Building Files

Simple building directory for storing basic building information with async component-based detail views.

## Overview

Features:
- Building/apartment records with basic information
- CRUD operations (Create, Read, Update, Delete)
- Search functionality (name, location, street, house number)
- Async component loading for detail views
- Authority-specific data isolation
- Table view with pagination

**Required Permission:** `READ_APARTMENT_FILE` (view), `WRITE_APARTMENT_FILE` (create/edit), `DELETE_APARTMENT_FILE` (delete)

## Accessing Apartment Files

**Desktop:** Double-click **Apartment Files** icon
**Menu:** Start Menu → Files → Apartment Files
**Route:** `/apartmentFile`

## Interface Layout

### Main View - Building Table

**Top Section:**
- **New Apartment** button (if you have WRITE permission)
- Info alert with explanation

**Building Table Columns:**
- **Name** - Building name (clickable to view)
- **Ort** (Location) - City/area
- **Straße** (Street) - Street name
- **Hausnr.** (House number) - Building number
- **Aktionen** (Actions) - Edit and Delete buttons

**Table Features:**
- 25 items per page
- Sortable columns
- Hover effects on rows
- Loading indicator while fetching
- Empty state with icon
- Client-side search filtering

**Search Bar:**
- Located in toolbar (top-right)
- Filters by: name, location, street, house number
- Real-time filtering
- Clearable field
- Max-width 400px

### Detail Views

**View Modes:**
- **Add View** - Create new building (full-screen component)
- **Edit View** - Modify existing building (full-screen component)
- **View View** - Read-only building details (full-screen component)

**Navigation:**
- When detail view opens, table hides
- Back button appears at top
- Click back to return to table

**Component Loading:**
- Async components loaded on-demand
- Authority-specific components from `/components/ApartmentFile/Authority/`
- Components: `Add.vue`, `Edit.vue`, `View.vue`

## Creating Buildings

### New Building

1. Click **New Apartment** button (requires `WRITE_APARTMENT_FILE` permission)
2. Add dialog component loads
3. Fill in building information in component form
4. Component emits `apartment-added` event on success
5. Table refreshes automatically
6. Success toast notification
7. Returns to table view

**Permissions:**
- Button only shows if `canEdit` is true
- Checks `props.canEdit` or `route.meta.canEdit`

**Component:**
- `AuthorityAddApartmentFile`
- Async loaded from `@/components/ApartmentFile/Authority/Add.vue`
- Full-screen view replaces table
- Handles own form validation and API calls

## Viewing Buildings

### View Building Details

1. Click on building name in table
2. View dialog component loads
3. Read-only display of building information
4. Close button returns to table

**Component:**
- `AuthorityViewApartmentFile`
- Async loaded from `@/components/ApartmentFile/Authority/View.vue`
- Receives `apartment-to-view` prop (copy of apartment object)
- Emits `close` event to return to table

**Navigation:**
- Name column is styled as link (cursor pointer)
- Click anywhere on name to open view

## Editing Buildings

### Edit Building

1. Click **Edit** icon (pencil) on building row
2. Edit dialog component loads
3. Modify building information in component form
4. Component emits `apartment-updated` event on success
5. Table refreshes automatically
6. Success toast notification
7. Returns to table view

**Edit Permissions:**
- Button only shows if `canEdit` is true
- Checks `props.canEdit` or `route.meta.canEdit`

**Component:**
- `AuthorityEditApartmentFile`
- Async loaded from `@/components/ApartmentFile/Authority/Edit.vue`
- Receives `apartment-to-edit` prop (copy of apartment object)
- Full-screen view replaces table
- Handles own form validation and API calls

## Deleting Buildings

### Delete Building

1. Click **Delete** icon (trash) on building row
2. Confirmation dialog appears:
   - Error icon and title
   - "Are you sure?" message with building name
   - Warning about permanent deletion
3. Click **Delete** to confirm
4. Or **Cancel** to abort
5. Building removed from database
6. Table refreshes automatically
7. Success toast notification

**Delete Permissions:**
- Button only shows if `canDelete` is true
- Checks `props.canDelete` or `route.meta.canDelete`

**Confirmation Dialog:**
- Max-width: 500px
- Dark theme
- Persistent (must choose option)
- Loading state on delete button during API call

**Warning:** Deletion is permanent and cannot be undone.

## Search Functionality

**Search Behavior:**
- Located in toolbar
- Filters buildings in real-time
- Case-insensitive matching
- Searches across multiple fields:
  - Building name
  - Location (city/area)
  - Street name
  - House number

**Implementation:**
- Client-side filtering via computed property
- No API calls on search
- Clear button to reset search
- Search persists until cleared

**Usage:**
1. Type in search field
2. Table filters automatically
3. Shows matching buildings only
4. Clear to see all buildings

## Data Model

**Apartment Interface:**
```typescript
interface Apartment {
  id: number;
  name: string;
  location: string;
  street: string;
  housenumber: string;
  authority_id: number;
  // Additional fields handled by detail components
}
```

**Note:** Full apartment data model is defined in detail components. Main view only displays name, location, street, and house number.

## API Integration

**Base Path:** `/apartmentfile/`

**Actions:**

**Get Apartments:**
```
GET /apartmentfile?action=getApartments
Response: { data: Apartment[] }
```

**Delete Apartment:**
```
POST /apartmentfile?action=deleteApartment
Payload: { id: number }
```

**Add/Edit Actions:**
- Handled by respective detail components
- Not exposed in main view code

**Error Handling:**
- Toast notifications for errors
- Console logging for debugging
- Loading states during operations
- Table refreshes after successful operations

## Component Architecture

**Main View (ApartmentView.vue):**
- Table display
- Search functionality
- Dialog state management
- Event handling
- API calls for list and delete

**Detail Components (Async):**
- Add component: Form for creating
- Edit component: Form for updating
- View component: Read-only display

**Component Communication:**
- Props: Pass apartment data to components
- Events: Components emit events on success/close
- v-model: Dialog visibility binding

**Async Loading:**
```typescript
const AuthorityAddApartmentFile = defineAsyncComponent(
  () => import('@/components/ApartmentFile/Authority/Add.vue')
);
```

**Benefits:**
- Reduced initial bundle size
- Components load only when needed
- Better performance for main table view

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
}
```

**Permission Resolution:**
1. Check `props.allPermissions` (grants all)
2. Check individual prop permissions
3. Fall back to `route.meta` permissions
4. Buttons show/hide based on permissions

## Multi-Tenant Security

**Authority Isolation:**
- All buildings scoped to `authority_id`
- Users only see buildings from their organization
- Backend filters by authority automatically
- Cross-organization access prevented

**Permission Checks:**
- `READ_APARTMENT_FILE` - View building list and details
- `WRITE_APARTMENT_FILE` - Create and edit buildings
- `DELETE_APARTMENT_FILE` - Delete buildings

## Styling & Theme

**Dark Theme:**
- Dark cards with elevation
- Primary color accents
- Tonal variants for alerts
- Comfortable density

**Table Styling:**
- Hover effects on rows
- Clickable name links (primary color)
- Action icon buttons (small, text variant)
- Empty and loading states centered

**Responsive:**
- Fluid container with padding
- Table adapts to screen size
- Search field max-width 400px
- Dialogs max-width 500px (delete)

**Detail View Layout:**
- Full-screen when detail view opens
- Table hides completely
- Back button at top
- Clean transition between views

## Limitations

**What's NOT Available:**

### Building Information
- ❌ Building photos/images
- ❌ Construction year
- ❌ Number of floors
- ❌ Number of units
- ❌ Square footage
- ❌ Building type classification
- ❌ Owner/management company
- ❌ Contact information

### Fire Safety
- ❌ Sprinkler system tracking
- ❌ Fire alarm system details
- ❌ Standpipe system
- ❌ Fire extinguisher locations
- ❌ Emergency exits mapping
- ❌ Fire department connection location
- ❌ Knox box information

### Access Information
- ❌ Key location/Knox box
- ❌ Gate codes
- ❌ Building access methods
- ❌ Elevator access details
- ❌ After-hours contacts

### Utilities
- ❌ Gas shutoff location
- ❌ Electric panel location
- ❌ Water shutoff location
- ❌ HVAC system details

### Floor Plans
- ❌ Floor plan upload
- ❌ Floor plan viewer
- ❌ Annotations on floor plans
- ❌ Mark emergency exits
- ❌ Mark utility locations
- ❌ Mark hazards

### Unit Management
- ❌ Individual unit records
- ❌ Unit numbers
- ❌ Occupant information
- ❌ Special needs tracking
- ❌ Pet information
- ❌ Emergency contacts per unit

### Inspections
- ❌ Inspection scheduling
- ❌ Inspection history
- ❌ Inspection checklists
- ❌ Inspection photos
- ❌ Violation tracking
- ❌ Follow-up management

### Pre-Planning
- ❌ Emergency response information
- ❌ Hazardous materials documentation
- ❌ Construction type details
- ❌ Roof access information
- ❌ Basement/underground areas
- ❌ Response strategies

### Advanced Features
- ❌ Map view with locations
- ❌ Building photos/galleries
- ❌ Document attachments
- ❌ Notes or comments
- ❌ Tags or categories
- ❌ Status workflow
- ❌ Last inspection tracking
- ❌ Reporting or analytics
- ❌ Export functionality
- ❌ Import functionality
- ❌ Bulk operations
- ❌ Advanced filtering

### UI Limitations
- ❌ Main table shows limited fields (name, location, street, house number only)
- ❌ No quick view/preview
- ❌ No inline editing
- ❌ No sortable drag-drop
- ❌ No favorites or bookmarks
- ❌ No recent buildings list
- ❌ Table cannot be customized (fixed columns)

**Current Reality:**
This is a **simple building directory** with basic CRUD operations. Full building details and functionality are determined by the authority-specific detail components. It is not a comprehensive building management, inspection, or pre-planning system.

## Use Cases

**Building Directory:**
- Maintain list of buildings
- Store basic address information
- Quick lookup by name or location
- Simple reference database

**Basic Records:**
- Track building names
- Record addresses
- Associate with authority
- Simple organization

## Best Practices

**Creating Buildings:**
1. Use clear, descriptive building names
2. Enter complete address information
3. Be consistent with naming conventions
4. Verify information in detail component before saving

**Searching:**
1. Use partial names for broader results
2. Search by street for area lookups
3. Try house number for specific building
4. Clear search to see full list

**Organization:**
1. Keep records up to date
2. Delete duplicate entries
3. Use consistent address formatting
4. Review data accuracy periodically

## Troubleshooting

### Can't Create Building

**Problem:** New Apartment button doesn't work or is missing

**Solutions:**
1. Check you have `WRITE_APARTMENT_FILE` permission
2. Verify feature enabled for your authority
3. Refresh page
4. Check browser console for errors

### Detail Component Not Loading

**Problem:** Clicking New/Edit/View doesn't show form

**Solutions:**
1. Check browser console for component loading errors
2. Verify authority component exists at correct path
3. Check network tab for failed imports
4. Refresh page and try again

### Building Not Saving

**Problem:** Form submits but doesn't persist

**Solutions:**
1. Check detail component for validation errors
2. Verify internet connection
3. Check browser console for API errors
4. Check backend is running
5. Verify you have `WRITE_APARTMENT_FILE` permission

### Can't Edit Building

**Problem:** Edit button doesn't work

**Solutions:**
1. Check you have `WRITE_APARTMENT_FILE` permission
2. Verify building belongs to your authority
3. Refresh page and try again
4. Check browser console for errors

### Can't Delete Building

**Problem:** Delete fails or button missing

**Solutions:**
1. Check you have `DELETE_APARTMENT_FILE` permission
2. Building may have dependent records
3. Verify building belongs to your authority
4. Check error message in toast
5. Consider editing instead of deleting

### Search Not Working

**Problem:** Search doesn't filter list

**Solutions:**
1. Check spelling of search term
2. Try partial match instead of exact
3. Verify buildings exist with that term
4. Clear search and try again
5. Refresh page

### Buildings Not Loading

**Problem:** Empty table or loading spinner doesn't stop

**Solutions:**
1. Check internet connection
2. Verify backend is running
3. Check browser console for API errors
4. Check network tab for failed requests
5. Verify authority_id is correct

### Back Button Not Working

**Problem:** Can't return to table from detail view

**Solutions:**
1. Click back button at top of screen
2. Refresh page to reset state
3. Check browser console for errors

## Related Documentation

- [🏢 Companies](/guide/companies) - Company management
- [👤 Person Files](/guide/person-files) - Person contacts
- [📄 Documents](/guide/documents) - Document management

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
