# Todo Lists & Task Management

Task management system with hierarchical todo lists, subtasks, user assignments, priorities, and due dates.

## Overview

Features:
- Hierarchical organization (folders → lists → todos → subtasks)
- User assignments (multiple users per todo)
- Due dates with visual warnings
- Priority levels (low, medium, high)
- Subtask checklists with progress tracking
- Completion tracking with filtering
- Inline editing throughout

**Required Permission:** `READ_TODO` (view), `WRITE_TODO` (create/edit)

## Accessing Todo Management

**Route:** `/todo`

**How to Access:**
- Desktop: Double-click **Todo** icon
- Menu: Start Menu → Productivity → Todo Lists
- Or navigate directly to `/todo`

## Interface Layout

The todo interface has a **three-column layout**:

### Column 1: Lists Panel (Left)

**Folder Groups:**
- Expandable folder groups containing sublists
- Click to expand/collapse folders
- Double-click folder name to rename

**Actions:**
- **Create Folder** - Click + button in toolbar
- **Create Sublist** - Type in text field within folder, press Enter
- **Delete Sublist** - Click trash icon next to sublist

**Organization:**
- Lists automatically sorted alphabetically
- Folders can contain multiple sublists
- Two-level hierarchy: Folders → Sublists

### Column 2: Todos Panel (Middle)

**List Header:**
- Shows selected list name
- Double-click to rename list

**Todo Items:**
- Checkbox to mark complete/incomplete
- Todo title
- Due date indicator (if set)
- Priority flag icon (colored by importance)
- Subtask progress counter (e.g., "2 / 5 erledigt")

**Toolbar:**
- "Show completed" toggle - Hide/show completed todos

**Add Todo:**
- Text field at bottom of list
- Type todo title and press Enter to create

**Visual Indicators:**
- **Red left border** - Overdue (past due date)
- **Orange left border** - Due today
- **Strikethrough + gray** - Completed
- **Green flag** - Low priority
- **Orange flag** - Medium priority
- **Red flag** - High priority

### Column 3: Todo Details Panel (Right)

**When a todo is selected:**

**Todo Title:**
- Double-click to edit inline

**Importance (Priority):**
- Dropdown selector
- Options: Low (green), Medium (orange), High (red)

**Due Date:**
- Date picker (DD.MM.YYYY format)
- Can be cleared/unset

**Assigned Users:**
- Multi-select dropdown
- Shows users with todo permissions for this list
- Multiple users can be assigned
- Users shown as chips with remove (X) buttons

**Description/Notes:**
- Multi-line text area
- Optional field for additional details

**Subtasks (Teilaufgaben):**
- Checkbox list of subtasks
- Add new: Text field at bottom, press Enter
- Toggle completion: Click checkbox
- Rename: Double-click subtask text
- Delete: Click trash icon
- Toggle "Show completed subtasks" to hide/show finished items

**Actions:**
- **Delete Todo** - Permanently delete todo (with confirmation)

## Creating Todo Lists

### Create Folder

1. Click **+** button in Lists panel toolbar
2. Enter folder name in dialog
3. Click **Save**
4. New folder appears in list

### Create Sublist

1. Expand a folder
2. Type sublist name in the text field within folder
3. Press **Enter**
4. New sublist created under that folder

### Delete List

1. Click trash icon next to sublist name
2. Confirm deletion in dialog
3. List and all its todos are permanently deleted

**Warning**: Deleting a list deletes all todos within it. This cannot be undone.

## Creating and Managing Todos

### Create Todo

**Method 1: Quick Create**
1. Select a list from Lists panel
2. Type todo title in text field at bottom of Todos panel
3. Press **Enter**
4. Todo created with default settings (no due date, low priority, unassigned)

**Method 2: Create with Details**
1. Create todo using quick method
2. Click todo to select it
3. Edit details in right panel (priority, due date, users, description, subtasks)

### Edit Todo

**Edit Title:**
- Double-click todo title in middle panel
- Type new name
- Press Enter or click away to save

**Set Priority:**
1. Select todo
2. In right panel, click Importance dropdown
3. Choose: Low, Medium, or High

**Set Due Date:**
1. Select todo
2. In right panel, click due date picker
3. Select date from calendar

**Assign Users:**
1. Select todo
2. In right panel, click Assigned Users dropdown
3. Select one or more users
4. Users appear as chips
5. Click X on chip to remove assignment

**Add Description:**
1. Select todo
2. In right panel, type in Description field
3. Changes save automatically

### Complete Todo

**Mark as Complete:**
- Click checkbox next to todo in middle panel
- Todo becomes strikethrough and grayed out

**Mark as Incomplete:**
- Click checkbox again to uncheck
- Todo returns to normal appearance

**Filter Completed:**
- Use "Show completed" toggle in toolbar
- Hides completed todos from view
- Toggle again to show them

### Delete Todo

1. Select todo
2. Scroll to bottom of right panel
3. Click **Delete** button
4. Confirm deletion
5. Todo permanently removed

## Working with Subtasks

### Add Subtask

1. Select a todo
2. In right panel, scroll to Subtasks section
3. Type subtask name in text field
4. Press **Enter**
5. Subtask appears in list with checkbox

### Complete Subtask

- Click checkbox next to subtask
- Subtask becomes strikethrough
- Progress counter updates (e.g., "1 / 5 erledigt")

### Edit Subtask

- Double-click subtask text
- Edit inline
- Press Enter or click away to save

### Delete Subtask

1. Click trash icon next to subtask
2. Confirm deletion
3. Subtask removed
4. Progress counter updates

### Show/Hide Completed Subtasks

- Use "Show completed subtasks" toggle in Subtasks section
- Hides completed subtasks
- Progress counter still shows all (completed and incomplete)

## Sorting and Organization

### Todo Sorting

Todos are automatically sorted by:
1. **Completion status** - Incomplete first, completed last
2. **Due date** - Soonest due first (overdue at top)
3. **Importance** - High → Medium → Low

This sorting cannot be customized.

### List Sorting

- Folders and sublists sorted alphabetically
- Automatic sorting, cannot be manually reordered

## Visual Indicators

### Due Date Warnings

**Overdue Todos:**
- Red left border
- Due date shown in red

**Due Today:**
- Orange left border
- Due date shown in orange

**Future Due Dates:**
- No special border
- Date shown in normal color

### Priority Indicators

**High Priority:**
- Red flag icon
- Flag appears next to todo title

**Medium Priority:**
- Orange flag icon

**Low Priority:**
- Green flag icon

**No Priority Set:**
- Defaults to Low priority (green flag)

### Progress Indicators

**Subtask Progress:**
- Shown as "X / Y erledigt" on todo
- Example: "2 / 5 erledigt" means 2 out of 5 subtasks complete
- Updates automatically when subtasks toggled

## Inline Editing

Most elements can be edited inline with **double-click**:

**Editable Elements:**
- Folder names
- List names
- Todo titles
- Subtask text

**How to Edit:**
1. Double-click the text
2. Text becomes editable input field
3. Type new value
4. Press Enter or click away to save
5. Changes update immediately

## Permission System

**Read Permission** (`READ_TODO`):
- View todo lists
- View todos and subtasks
- View assigned users

**Write Permission** (`WRITE_TODO`):
- Create folders and lists
- Create todos and subtasks
- Edit all todo details
- Delete todos and lists
- Assign users

**User Assignment:**
- Only users with todo permissions for a specific list can be assigned
- Backend filters available users via `getUsersWithTodoPermissions` endpoint

## Deep Linking

You can link directly to a specific todo:

**URL Format:**
```
/todo?id=123
```

**Behavior:**
- Opens todo view
- Selects the specified todo
- Shows todo details in right panel

## Tips & Best Practices

**Organizing Todos:**
- Create folders for projects or categories
- Use sublists for different areas within a project
- Keep list names short and clear

**Using Priorities:**
- High (red) - Urgent, must do today
- Medium (orange) - Important, do this week
- Low (green) - Can wait, do when time permits

**Setting Due Dates:**
- Always set due dates for time-sensitive tasks
- Use visual warnings to catch overdue items quickly
- Today's date shows orange border for easy scanning

**Using Subtasks:**
- Break complex todos into smaller subtasks
- Check off subtasks as you complete them
- Track progress with counter (X / Y erledigt)
- Hide completed subtasks to reduce clutter

**Assigning Users:**
- Assign todos to specific team members
- Multiple people can be assigned to collaborative tasks
- Assignees can see todos in their lists

**Completing Todos:**
- Mark todos complete when finished
- Use "Show completed" toggle to hide finished items
- Review completed todos periodically
- Completed todos can be unchecked if reopened

## Keyboard Shortcuts

**When creating todo/subtask:**
- **Enter** - Save and create item

**In inline edit mode:**
- **Enter** - Save changes
- **Esc** - Cancel edit

## Troubleshooting

### Can't Create List

**Problem**: New list button doesn't work

**Solutions:**
1. Verify you have `WRITE_TODO` permission
2. Check that todo feature is enabled for your authority
3. Refresh the page
4. Try creating sublist within existing folder

### Todo Not Saving

**Problem**: Edited todo doesn't save changes

**Solutions:**
1. Check internet connection
2. Look for error messages
3. Try refreshing page and editing again
4. Verify you have write permission

### Can't Assign User

**Problem**: User not appearing in assignment dropdown

**Solutions:**
1. Verify user has todo permissions
2. Check user belongs to correct list
3. Refresh page to reload users
4. Contact administrator if user should have access

### Subtasks Not Showing

**Problem**: Created subtasks disappear

**Solutions:**
1. Check "Show completed subtasks" toggle isn't hiding them
2. Verify subtask was saved (check for confirmation)
3. Refresh page to reload data
4. Check if subtask was accidentally deleted

### Due Date Warnings Not Showing

**Problem**: Overdue todos don't have red border

**Solutions:**
1. Verify due date is actually in the past
2. Check date format is correct (DD.MM.YYYY)
3. Refresh page
4. Clear browser cache

## Related Documentation

- [Getting Started](/guide/getting-started) - Learn the basics of K-Systems
- [Calendar](/guide/calendar) - Calendar events and scheduling
- [Messages](/guide/messages) - Team communication

---

**Last Updated:** 2025-10-01
**Version:** 2.0.0 (Corrected to match actual implementation)
