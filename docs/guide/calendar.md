# Calendar & Events

The Calendar system allows you to schedule events, assign attendees, and organize team calendars with calendar groups.

## Overview

Features:
- Event scheduling with date/time
- User assignments (attendees)
- Calendar groups with member management
- Basic recurring events (daily, monthly)
- Privacy settings (public/private)
- Color-coded events
- Drag-and-drop event rescheduling
- Event filtering by attendees

**Required Permission:** `READ_CALENDAR` (view), `WRITE_CALENDAR` (create/edit)

## Accessing Calendar

**Route:** `/calendar`

**How to Access:**
- Desktop: Double-click **Calendar** icon
- Menu: Start Menu → Communication → Calendar
- Or navigate directly to `/calendar`

## Calendar View

### Month View

The calendar displays in **month view only**. This view shows:
- Full month grid
- Events displayed in day cells (up to 5 visible)
- Color-coded events
- "Show More" link when more than 5 events per day

**Note**: Week, Day, and Agenda views are not available in the current implementation.

### Navigation

- **Previous/Next Month**: Use arrow buttons in toolbar
- **Today**: Click "Today" button to jump to current month
- **Date Picker**: Click month/year to select specific date

## Creating Events

### Quick Create

1. Click an empty day cell
2. The new event dialog opens with start/end dates pre-filled
3. Enter event details
4. Click **Create**

**Time Rounding**: When creating events, minutes are rounded to 15-minute intervals (0, 15, 30, 45).

### New Event Form

Click **+ New Event** or click a day cell to open the event creation form:

#### **Basic Information**

- **Title** (required): Event name
- **Subtitle**: Short description
- **Description**: Full event details (supports multiple lines)

#### **Date & Time**

- **Start Date/Time**: When event begins
- **End Date/Time**: When event ends
- Uses datetime picker with date and time selection

#### **Attendees**

- Search and select users from dropdown
- Multiple attendees can be assigned
- Selected users appear as chips
- All assigned users can see the event

**Note**: Assigning attendees or a group automatically marks the event as private.

#### **Calendar Group**

- Assign event to a calendar group (optional)
- Only groups you belong to appear in list
- Group events visible to all group members
- Leave empty for personal event

**Note**: Assigning to a group automatically marks the event as private.

#### **Recurring Event**

Basic recurrence options:
- **None**: One-time event (default)
- **Daily**: Event repeats every day
- **Monthly**: Event repeats on same date each month

**Limitations**: Weekly and yearly recurrence are not available.

#### **Privacy**

- **Public**: Event visible to everyone (default for personal events without attendees)
- **Private**: Event visible only to creator, attendees, and group members
  - Automatically enabled when attendees or group assigned
  - Lock icon (🔒) displayed on private events

#### **Color**

Choose event color for visual identification:
- Predefined swatches: Green (#008000), Orange (#FFA500), Red (#FF0000)
- Custom color picker available
- Events display in selected color on calendar

### Save Event

Click **Create** to save the event. The calendar refreshes automatically.

## Managing Events

### View Event Details

**Double-click an event** to open the detail view with full information:
- Title, subtitle, description
- Start and end date/time
- Assigned attendees
- Calendar group (if any)
- Recurrence pattern
- Privacy status
- Color

### Edit Event

1. Double-click event to open details
2. Modify any fields
3. Click **Save** to update

**Permissions**: You can edit events you created or events in groups where you have edit permission.

### Delete Event

1. Open event details
2. Click **Delete** button
3. Confirm deletion in dialog
4. Event is permanently removed

**Permissions**: You can delete events you created or events in groups where you have delete permission.

### Move/Reschedule Event

**Drag and Drop:**
1. Click and hold an event
2. Drag to new date
3. Release to drop
4. Event time is preserved, only date changes
5. Event automatically updates

**Or Edit:**
1. Open event details
2. Change start/end date/time
3. Click **Save**

## Show More Events

When a day has more than 5 events:
1. First 5 events display in the day cell
2. "Show More" link appears
3. Click link to open popup dialog
4. Dialog shows all events for that day
5. Click any event in popup to view details

## Event Filtering

### Filter by Attendees

Use the filter sidebar to show only events assigned to specific users:
1. Open filter panel (if available)
2. Select users from "Assigned To" dropdown
3. Calendar shows only events matching selected users
4. Clear filter to show all events

**Note**: Other filtering options (groups, categories, date ranges) are not currently available.

## Calendar Groups

Calendar groups allow teams to share calendars and collaborate on events.

### Accessing Group Manager

1. Click **Groups** button in calendar toolbar (or sidebar toggle)
2. Group Manager panel opens
3. View all calendar groups

### Creating a Group

**If you have permission:**
1. Open Group Manager
2. Click **+ New Group**
3. Fill in group details:
   - **Name** (required): Group name
   - **Description**: Optional group description
   - **Color**: Choose from 24 predefined colors for visual identification
4. Click **Save**

### Managing Group Members

**Add Members:**
1. Edit group
2. In members section, select users from dropdown
3. Multiple users can be selected
4. Click **Save**

**Set Member Permissions:**
Each member can have these permissions:
- **Can Edit**: Member can edit group events
- **Can Delete**: Member can delete group events
- **Can Manage Members**: Member can add/remove other members

**Remove Members:**
1. Edit group
2. Click X on member chip
3. Click **Save**

### Editing a Group

1. Open Group Manager
2. Click edit icon next to group name
3. Modify name, description, color, or members
4. Click **Save**

### Deleting a Group

1. Open Group Manager
2. Click delete icon next to group name
3. Confirm deletion
4. Group and membership removed (events remain but unassigned from group)

### Using Groups in Events

When creating or editing an event:
1. Select group from "Calendar Group" dropdown
2. All group members will see the event
3. Event displays in group color (unless custom color set)
4. Event automatically marked as private

## Event Privacy

### Public Events

- Default for personal events without attendees
- Visible to all users in the system
- Full details visible

### Private Events

- Visible only to:
  - Event creator
  - Assigned attendees
  - Group members (if assigned to group)
- Lock icon (🔒) displayed on calendar
- Automatically enabled when:
  - Attendees are assigned
  - Event assigned to calendar group

### Switching Privacy

1. Open event details
2. Toggle "Private Event" checkbox
3. Click **Save**

**Note**: Confidential privacy level is not available.

## Recurring Events

### Setting Up Recurrence

1. When creating or editing event, expand **Recurring** section
2. Select recurrence pattern:
   - **None**: Single event (default)
   - **Daily**: Repeats every day
   - **Monthly**: Repeats on same date each month
3. Click **Save**

**Limitations**:
- No weekly recurrence option
- No yearly recurrence option
- No end date or occurrence limit settings
- Recurring events repeat indefinitely

### Editing Recurring Events

When you edit a recurring event:
- Changes apply to all occurrences
- No option to edit single occurrence

### Deleting Recurring Events

When you delete a recurring event:
- Entire series is deleted
- No option to delete single occurrence

## Permission System

Calendar respects the K-Systems permission system:

### View Calendar
- Requires: `READ_CALENDAR`
- All users typically have this by default

### Create Events
- Requires: `WRITE_CALENDAR`
- Can create personal events and group events (if group member)

### Edit Events
- Must be event creator OR
- Must have `can_edit` permission in the event's group

### Delete Events
- Must be event creator OR
- Must have `can_delete` permission in the event's group

### Manage Groups
- Group creator can always manage
- Members with `can_manage_members` permission can add/remove members

## Tips & Best Practices

**Creating Events:**
- Use clear, descriptive titles
- Add attendees to keep everyone informed
- Use calendar groups for team events
- Choose meaningful colors for quick visual identification
- Add full description for complex events

**Managing Calendar:**
- Use drag-and-drop for quick rescheduling
- Assign events to groups for team visibility
- Mark sensitive events as private
- Use recurring events for regular meetings (daily/monthly patterns)

**Using Groups:**
- Create groups for departments, teams, or projects
- Set appropriate member permissions
- Use group colors to visually distinguish different teams
- Add descriptions to clarify group purpose

## Troubleshooting

### Can't Create Event

**Problem**: New Event button doesn't work or form doesn't save

**Solutions:**
1. Verify you have `WRITE_CALENDAR` permission
2. Check that calendar feature is enabled for your authority
3. Ensure start time is before end time
4. Fill in required field (Title)

### Event Not Showing

**Problem**: Created event doesn't appear on calendar

**Solutions:**
1. Check if date filter is active
2. Verify event wasn't created on different month
3. Clear any attendee filters
4. Refresh the page

### Can't Edit/Delete Event

**Problem**: No edit/delete buttons or they're disabled

**Solutions:**
1. Verify you created the event
2. If group event, check you have edit/delete permission in that group
3. Check that you have `WRITE_CALENDAR` permission

### Group Not Appearing

**Problem**: Created group doesn't show in dropdown

**Solutions:**
1. Refresh the page to reload groups
2. Verify you're a member of the group
3. Check Group Manager to confirm group exists

### Drag-and-Drop Not Working

**Problem**: Can't drag events to new dates

**Solutions:**
1. Ensure you have edit permission for the event
2. Try clicking and holding longer before dragging
3. Make sure you're dragging to a valid date cell
4. If issue persists, use edit form to change date

## Related Documentation

- [Getting Started](/guide/getting-started) - Learn the basics of K-Systems
- [Messages](/guide/messages) - Internal messaging system
- [Employee Management](/guide/employee-management) - Managing users who can be assigned to events

---

**Last Updated:** 2025-10-01
**Version:** 2.0.0 (Corrected to match actual implementation)
