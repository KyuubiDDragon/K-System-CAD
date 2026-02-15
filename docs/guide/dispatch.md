# Dispatch Operations

Simple drag-and-drop system for assigning employees and vehicles to dispatch units with real-time WebSocket synchronization.

## Overview

Features:
- Drag-and-drop employee assignment
- Drag-and-drop vehicle assignment
- Multiple dispatch cards
- Status field per dispatch
- Real-time WebSocket updates
- Search employees and vehicles
- Color-coded dispatch headers
- Authority-specific isolation

**Required Permission:** `READ_DISPATCH` (view), `WRITE_DISPATCH` (assign)

## Accessing Dispatch

**Desktop:** Double-click **Dispatch** icon
**Menu:** Start Menu → Operations → Dispatch
**Route:** `/dispatch`

## Interface Layout

### Left Column - Available Resources

**Employees List:**
- Search field with magnify icon
- Count badge showing available employees
- Draggable employee cards showing:
  - Avatar with first initial
  - Service number [123]
  - Employee name
  - Rank name (grey text)
- Loading indicator while fetching
- Empty state if no employees

**Vehicles List:**
- Search field with magnify icon
- Count badge showing available vehicles
- Draggable vehicle cards showing:
  - Car icon avatar (warning color)
  - Vehicle title
  - Number plate
  - Rank (if applicable)
- Loading indicator while fetching
- Empty state if no vehicles

**Search Functionality:**
- Real-time filtering as you type
- Filters by name/service number for employees
- Filters by title/numberplate for vehicles
- Clearable search field
- Sticky at top of scroll area

### Right Column - Dispatch Cards

**Dispatch Grid:**
- Responsive grid layout (2-6 columns based on screen size)
- Each dispatch card shows:
  - Colored toolbar with dispatch name
  - Employee drop zone
  - Vehicle drop zone
  - Status text field with save button
  - Drop placeholders when empty

**Dispatch Card Structure:**

**Header:**
- Color-coded toolbar (customizable color per dispatch)
- Dispatch name/title

**Employee Drop Zone:**
- "Mitarbeiter" section header
- Drop area for employees
- Shows draggable cards for assigned employees
- Placeholder: "Mitarbeiter hierher ziehen"
- Blue tonal variant for assigned cards

**Vehicle Drop Zone:**
- "Fahrzeuge" section header
- Drop area for vehicles
- Shows draggable cards for assigned vehicles
- Placeholder: "Fahrzeug hierher ziehen"
- Amber tonal variant for assigned cards

**Status Footer:**
- Text field for status updates
- Save icon button (click or press Enter)
- Compact density
- Loading state during save

## Assigning Resources

### Assign Employee to Dispatch

**Via Drag-and-Drop:**
1. Find employee in left employee list
2. Drag employee card
3. Drop into dispatch card employee zone
4. Employee appears in dispatch
5. API called automatically
6. WebSocket broadcasts update to all connected clients

**Clone Behavior:**
- Employee can be assigned to multiple dispatches
- Original stays in source list (pull: 'clone')
- Each dispatch gets independent copy

### Assign Vehicle to Dispatch

**Via Drag-and-Drop:**
1. Find vehicle in left vehicle list
2. Drag vehicle card
3. Drop into dispatch card vehicle zone
4. Vehicle appears in dispatch
5. API called automatically
6. WebSocket broadcasts update

**Clone Behavior:**
- Vehicle can be assigned to multiple dispatches
- Original stays in source list (pull: 'clone')
- Each dispatch gets independent copy

### Remove Assignment

**Remove Employee:**
1. Drag employee card from dispatch employee zone
2. Drop back to employee source list
3. Employee removed from dispatch
4. API called automatically
5. WebSocket broadcasts update

**Remove Vehicle:**
1. Drag vehicle card from dispatch vehicle zone
2. Drop back to vehicle source list
3. Vehicle removed from dispatch
4. API called automatically
5. WebSocket broadcasts update

**Alternative - Drag Between Dispatches:**
- Can drag employee/vehicle directly between dispatch cards
- Moves assignment from one dispatch to another
- Triggers remove from old dispatch and add to new

## Status Updates

### Update Dispatch Status

1. Click in dispatch status text field
2. Type status text (e.g., "En Route", "On Scene", "Returning")
3. Click save icon OR press Enter
4. Loading indicator shows during save
5. Status saved to database
6. WebSocket broadcasts update to all clients

**Status Field:**
- Free-form text input
- No predefined options
- Compact density
- Outlined variant
- Primary color
- Hide details (no validation messages)

## Real-Time WebSocket Updates

### Automatic Synchronization

**WebSocket Integration:**
- Connected via `/dispatch` namespace
- Receives updates from other users in real-time
- Updates dispatch assignments live

**Update Events:**

**Dispatch Updated:**
```javascript
{
  type: 'dispatch-updated',
  data: {
    dispatchId: number,
    employees: Employee[],
    vehicles: Vehicle[],
    status: string
  }
}
```

**Behavior:**
- When another user assigns employee/vehicle, your view updates automatically
- When another user updates status, status field updates
- Local changes trigger broadcasts to all other connected clients
- Prevents data conflicts

**Socket Initialization:**
- Initializes on component mount
- Registers update handler
- Listens for `dispatch-updated` events
- Cleans up on component unmount

## Data Model

**Employee Interface:**
```typescript
interface Employee {
  id: number;
  name: string;
  servicenumber: string;
  rank_name: string;
}
```

**Vehicle Interface:**
```typescript
interface Vehicle {
  id: number;
  title: string;
  numberplate: string;
  rank: string | null;
}
```

**Dispatch Interface:**
```typescript
interface Dispatch {
  id: number;
  name: string;
  status: string | null;
  color?: string; // Toolbar color
  employees: Employee[];
  vehicles: Vehicle[];
}
```

## API Integration

**Base Path:** `/dispatch/`

**Actions:**

**Get Employees:**
```
GET /dispatch/?action=getEmployees
Response: { data: Employee[] }
```

**Get Vehicles:**
```
GET /dispatch/?action=getVehicles
Response: { data: Vehicle[] }
```

**Get Dispatches:**
```
GET /dispatch/?action=getDispatches
Response: { data: Dispatch[] }
```

**Assign Employee:**
```
POST /dispatch/?action=assignEmployee
Payload: { dispatch_id: number, employee_id: number }
```

**Remove Employee:**
```
POST /dispatch/?action=removeEmployee
Payload: { dispatch_id: number, employee_id: number }
```

**Assign Vehicle:**
```
POST /dispatch/?action=assignVehicle
Payload: { dispatch_id: number, vehicle_id: number }
```

**Remove Vehicle:**
```
POST /dispatch/?action=removeVehicle
Payload: { dispatch_id: number, vehicle_id: number }
```

**Update Status:**
```
POST /dispatch/?action=updateStatus
Payload: { dispatch_id: number, status: string }
```

**Error Handling:**
- API errors shown via toast notifications
- Console logging for debugging
- Loading states during operations

## Drag-and-Drop Library

**VueDraggable:**
- Library: `vuedraggable`
- Used for all drag-and-drop functionality

**Employee Groups:**
```javascript
{
  name: 'employees',
  pull: 'clone',  // Clone from source
  put: true       // Accept drops
}
```

**Vehicle Groups:**
```javascript
{
  name: 'vehicles',
  pull: 'clone',
  put: true
}
```

**Events:**
- `@add` - Item added to drop zone
- `@remove` - Item removed from drop zone
- Triggers API calls automatically

**Sort Disabled:**
- Source lists have `:sort="false"`
- Prevents reordering in source lists
- Only drag-to-assign is allowed

## Desktop Window Mode

**Props Support:**
```typescript
interface Props {
  meta?: Record<string, any>;
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
  id?: number | string; // Dispatch ID to highlight
}
```

**Highlight Feature:**
- Pass `id` prop or `?id=X` query parameter
- On mount, scrolls to dispatch card with that ID
- Adds highlight animation (3 second pulse)
- Uses `#dispatch-${id}` element selector

## Multi-Tenant Security

**Authority Isolation:**
- All employees, vehicles, and dispatches scoped to `authority_id`
- Users only see resources from their organization
- Backend filters by authority automatically
- Cross-organization access prevented

**Permission Checks:**
- `READ_DISPATCH` - View dispatch board
- `WRITE_DISPATCH` - Assign employees/vehicles, update status

## Styling & Theme

**Dark Theme:**
- Card backgrounds with elevation
- Primary color toolbars
- Blue tonal for employee cards
- Amber tonal for vehicle cards

**Layout:**
- Left column: 3-4 columns (responsive)
  - Employees: 50% width on md screens
  - Vehicles: 50% width on md screens
- Right column: 7-9 columns (responsive)
  - Dispatch grid: 2-6 columns per row

**Responsive Breakpoints:**
- xs (12 cols): 1 dispatch per row
- sm (4 cols): 3 dispatches per row
- lg (3 cols): 4 dispatches per row
- xl (2 cols): 6 dispatches per row

**Drop Zones:**
- Minimum height for empty zones
- Grey background placeholders
- Drag icon and helper text
- Smooth transitions on drag

## Limitations

**What's NOT Available:**

### Incident Management
- ❌ Create incidents
- ❌ Incident numbers
- ❌ Incident types/categories
- ❌ Priority levels
- ❌ Incident timeline
- ❌ Incident details
- ❌ Caller information
- ❌ Location/address entry
- ❌ Cross streets
- ❌ Access notes

### Dispatch Management
- ❌ Automatic dispatch recommendations
- ❌ Unit selection based on proximity
- ❌ Response time tracking
- ❌ Dispatch times (notified, en route, on scene, cleared)
- ❌ Unit status workflow
- ❌ Dispatch history per incident
- ❌ Multiple incidents
- ❌ Incident-to-unit relationships

### Status Management
- ❌ Predefined status options
- ❌ Status colors/chips
- ❌ Status workflow
- ❌ Status history
- ❌ Status timeline
- ❌ Automatic status updates
- ❌ Status transitions (Available → En Route → On Scene → Returning → Available)

### Location Features
- ❌ Map integration
- ❌ GPS tracking
- ❌ Unit locations
- ❌ Incident locations
- ❌ Route display
- ❌ Distance calculations
- ❌ Travel time estimates
- ❌ Geofencing

### Communication
- ❌ Radio communication log
- ❌ Message units
- ❌ Notifications to assigned personnel
- ❌ Alerts
- ❌ Text-to-speech dispatch
- ❌ Mobile app integration

### Resource Management
- ❌ Unit availability tracking
- ❌ Equipment tracking
- ❌ Apparatus status
- ❌ Station locations
- ❌ Minimum staffing checks
- ❌ Qualification requirements
- ❌ Equipment requirements per incident type

### Crew Information
- ❌ Display crew certifications
- ❌ Display crew roles/positions
- ❌ Acting assignments
- ❌ Crew change tracking
- ❌ Contact information

### Reporting & Analytics
- ❌ Response time reports
- ❌ Incident reports
- ❌ Unit utilization
- ❌ Performance metrics
- ❌ Export functionality
- ❌ Statistics
- ❌ Trend analysis

### Advanced Features
- ❌ Mutual aid requests
- ❌ Resource requests
- ❌ Callback lists
- ❌ Overtime tracking
- ❌ NFIRS integration
- ❌ CAD integration
- ❌ AVL (Automatic Vehicle Location)
- ❌ Unit recommendations
- ❌ Dispatch tones/alerts

### UI Limitations
- ❌ No filters for dispatch cards
- ❌ No sorting options
- ❌ No pagination
- ❌ No dispatch card management (create/edit/delete dispatches via UI)
- ❌ No color picker for dispatch colors
- ❌ No keyboard shortcuts
- ❌ No bulk operations
- ❌ No undo/redo

**Current Reality:**
This is a **simple drag-and-drop assignment board** for organizing employees and vehicles into dispatch groups. It is not a comprehensive dispatch, incident management, or CAD (Computer-Aided Dispatch) system.

## Use Cases

**Simple Assignment Board:**
- Organize employees into dispatch units/teams
- Assign vehicles to dispatch groups
- Track basic status per dispatch
- Real-time collaboration with other users

**Shift Staffing:**
- Drag employees to assign to units
- Assign vehicles to crews
- Update status as situations change
- See assignments from other dispatchers

**Basic Organization:**
- Group resources into dispatch cards
- Visual representation of assignments
- Quick drag-and-drop interface
- Minimal clicks to assign/remove

## Best Practices

**Organizing Resources:**
1. Use search to quickly find employees/vehicles
2. Drag from source lists to dispatch cards
3. Remove by dragging back to source
4. Keep dispatch cards organized by unit type

**Status Updates:**
1. Use consistent status terminology
2. Update status as situation changes
3. Keep status current for all users
4. Press Enter to save quickly

**Real-Time Collaboration:**
1. Be aware other users can see your changes instantly
2. Avoid conflicting assignments
3. Communicate with team about major changes
4. Trust the WebSocket updates

## Troubleshooting

### Can't Drag Employee/Vehicle

**Problem:** Dragging doesn't work

**Solutions:**
1. Verify browser supports drag-and-drop
2. Check if employee/vehicle list loaded
3. Refresh page
4. Check browser console for errors
5. Ensure no browser extensions blocking drag-and-drop

### Assignment Not Saving

**Problem:** Drag works but doesn't persist

**Solutions:**
1. Check internet connection
2. Verify you have `WRITE_DISPATCH` permission
3. Check browser console for API errors
4. Check network tab for failed requests
5. Verify backend is running

### Status Not Saving

**Problem:** Status field doesn't save

**Solutions:**
1. Click save icon or press Enter
2. Wait for loading indicator to finish
3. Check internet connection
4. Check browser console for errors
5. Verify you have `WRITE_DISPATCH` permission

### WebSocket Not Updating

**Problem:** Changes from other users don't appear

**Solutions:**
1. Check WebSocket connection in network tab
2. Verify socket-server is running (port 3001)
3. Refresh page to reconnect
4. Check browser console for socket errors
5. Verify authentication token is valid

### Resources Not Loading

**Problem:** Employee or vehicle list empty

**Solutions:**
1. Check you have employees/vehicles in system
2. Verify `READ_DISPATCH` permission
3. Check browser console for API errors
4. Verify authority_id is correct
5. Check backend is running

### Dispatch Cards Not Showing

**Problem:** No dispatch cards visible

**Solutions:**
1. Dispatch cards must be created in backend/database
2. No UI to create dispatches (backend/admin only)
3. Verify dispatches exist for your authority
4. Check browser console for API errors
5. Refresh page

## Related Documentation

- [👥 Employee Management](/guide/employee-management) - Managing employees
- [🚗 Vehicle Files](/guide/vehicle-files) - Vehicle tracking
- [👷 Crew Management](/guide/crew-management) - Crew organization

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
