# Internal Messages

The K-Systems messaging system provides internal communication between users and groups with organizational features.

## Overview

The Messaging system allows you to:
- Send messages to users and groups
- Organize messages with folders
- Track read/unread status
- Pin important messages
- Reply to and forward messages
- Add personal notes to messages

**Required Permission:** `READ_MESSAGES`

## Accessing Messages

**Route:** `/message`

**How to Access:**
- Desktop: Double-click **Messages** icon
- Menu: Start Menu → Communication → Messages
- Or navigate directly to `/message`

## Interface Layout

### Navigation Sidebar

The left sidebar has four main views:

1. **Inbox** - Messages received (shows unread count)
2. **Sent** - Messages you sent
3. **Folders** - Manage your message folders
4. **Trash** - Deleted messages (can restore or permanently delete)

### Context Filters

Filter messages by:
- **Personal** - Your personal messages (shows unread count in inbox)
- **Group Filters** - Messages for groups you belong to (each shows unread count)

Click the filter buttons to view only messages for that context.

## Sending Messages

### Compose New Message

1. Click **+ New Message** button
2. **Select Sender**:
   - Send as yourself (Personal)
   - Or send as a group you belong to
3. **Select Recipients**:
   - Type to search for users or groups
   - Select multiple recipients
   - Each recipient receives a separate message
4. **Enter Subject**: Message title
5. **Compose Message**: Use the rich text editor to format your message
   - Bold, italic, underline
   - Headers, lists
   - Links
   - Code blocks
6. **Optional Folder Assignment**:
   - Assign to sender folder (your organization)
   - Assign to recipient folder (recipient's organization)
7. Click **Send**

**Note**: When sending to multiple recipients, each person receives an individual message (not a group conversation).

## Reading Messages

### Open a Message

In Inbox, Sent, or Trash view:
1. Click on the message title to open it
2. The message opens in full-screen reading view

### Message Reading View

**Toolbar Actions**:
- **Back** - Return to message list
- **Reply** - Reply to the sender
- **Forward** - Forward to new recipients
- **Save** - Save folder/note changes

**Message Information**:
- Sender name (with group badge if sent as group)
- Recipient name(s) (with group badge if sent to group)
- Date and time sent

**Message Content**:
- Subject displayed in toolbar
- Full message body (formatted HTML)

**Additional Features**:
- **Folder Assignment**: Assign message to a folder
  - In Inbox/Trash: Assign to recipient folder
  - In Sent view: Assign to sender folder
- **Personal Notes**: Add private notes to the message
  - In Inbox/Trash: Recipient notes (your notes)
  - In Sent view: Sender notes (your notes)
- **Pin Message** (Inbox only): Pin important messages to top of inbox
- **Delete**: Move message to trash

**Auto-Mark as Read**: Messages are automatically marked as read when you open them (inbox only).

## Message Actions

### In Inbox

**From Message Table**:
- **Toggle Read/Unread**: Click eye icon to mark as read/unread
- **Pin/Unpin**: Click pin icon to keep message at top of inbox
- **Delete**: Click trash icon to move to trash

**From Reading View**:
- **Reply**: Pre-fills sender and subject with "Re: [original subject]", includes quoted original message
- **Forward**: Select new recipients, subject pre-filled with "Fwd: [original subject]", includes quoted original message
- **Assign Folder**: Choose a folder from dropdown and click Save
- **Add Notes**: Type notes in the notes field and click Save
- **Pin/Unpin**: Toggle pin status
- **Delete**: Move to trash

### In Sent View

**From Message Table**:
- **Delete**: Move to trash

**From Reading View**:
- **Forward**: Forward to new recipients
- **Assign Folder**: Assign to sender folder
- **Add Notes**: Add sender notes
- **Delete**: Move to trash

### In Trash View

**From Message Table**:
- **Restore**: Move message back to inbox/sent
- **Permanently Delete**: Remove from database (cannot be undone)

**From Reading View**:
- **Restore**: Return message to inbox/sent
- **Permanently Delete**: Remove permanently

## Organizing with Folders

### Viewing Folders

1. Click **Folders** in the sidebar
2. See all your personal and group folders

### Creating a Folder

1. Go to **Folders** view
2. Click **+ New Folder**
3. Enter folder name
4. Select owner:
   - **Personal**: Your personal folder
   - **Group**: Folder for a specific group (only groups where you have folder management permission)
5. Click **Save**

### Editing a Folder

1. Click edit icon next to folder name
2. Change folder name
3. Click **Save**

**Note**: Folder owner (personal vs. group) cannot be changed after creation.

### Deleting a Folder

1. Click delete icon next to folder name
2. Confirm deletion

**Note**: Deleting a folder does not delete messages, only the folder itself. Messages will no longer show a folder assignment.

### Assigning Messages to Folders

**From Reading View**:
1. Open a message
2. Select a folder from the folder dropdown
3. Click **Save** button

**Folder Assignment Context**:
- **Inbox/Trash**: Assign to recipient folders (your folders)
- **Sent**: Assign to sender folders (your folders)

### Filtering by Folder

In Inbox, Sent, or Trash view:
1. Use the folder filter dropdown above the message table
2. Select a folder to see only messages in that folder
3. Select "All" to see all messages

## Message Status Features

### Read/Unread Status

**Unread Messages**:
- Shown in bold in message list
- Unread count displayed on Inbox sidebar item
- Unread count shown for each Personal/Group filter

**Mark as Read**:
- Automatically marked when you open a message
- Manually toggle by clicking eye icon in message table

**Mark as Unread**:
- Click eye icon to mark read message as unread (inbox only)

### Pinning Messages

**Pin a Message** (Inbox only):
- From table: Click pin icon
- From reading view: Click pin button

**Pinned Messages**:
- Appear at the top of inbox
- Pinned messages sorted newest first
- Regular messages appear below pinned messages
- Pin icon shown in message table

**Unpin a Message**:
- Click pin icon/button again

## Search and Filtering

### Search Messages

Use the search box to find messages by:
- Subject/title
- Message content
- Sender name
- Recipient name

### Combine Filters

You can combine multiple filters:
1. Select Personal or Group context
2. Select a folder
3. Type search text
4. All filters apply together

## Reply and Forward

### Reply to a Message

1. Open a message in inbox
2. Click **Reply** button
3. The compose dialog opens with:
   - Recipient: Original sender
   - Subject: "Re: [Original Subject]"
   - Message: Original message quoted at bottom
4. Type your reply above the quoted message
5. Click **Send**

### Forward a Message

1. Open any message
2. Click **Forward** button
3. The compose dialog opens with:
   - Recipients: Empty (select new recipients)
   - Subject: "Fwd: [Original Subject]"
   - Message: Original message quoted
4. Add recipients
5. Add your message above the quoted content
6. Click **Send**

## Delete Management

### Soft Delete (Move to Trash)

**From Inbox or Sent**:
- Click delete icon in message table
- Or click **Delete** button in reading view
- Message moves to Trash view

**Deleted Message Tracking**:
- System tracks separately if sender or recipient deleted the message
- Deleting from inbox marks `deleted_by_recipient = true`
- Deleting from sent marks `deleted_by_sender = true`
- Message appears in trash for the person who deleted it

### Restore from Trash

1. Go to **Trash** view
2. Click restore icon in message table
3. Or open message and click **Restore** button
4. Message returns to Inbox (if you're recipient) or Sent (if you're sender)

### Permanent Delete

1. Go to **Trash** view
2. Click permanently delete icon in message table
3. Or open message and click **Permanently Delete** button
4. Confirm deletion
5. Message removed from database

**Warning**: Permanent deletion cannot be undone.

## Group Messaging Features

### Sending as a Group

If you belong to groups:
1. In compose dialog, select sender dropdown
2. Choose a group instead of "Personal"
3. Message will show as sent by the group
4. Group badge appears next to sender name

### Sending to a Group

1. In compose dialog, select recipients
2. Type group name to search
3. Select the group
4. All group members will receive the message

### Group Folders

Create folders for group use:
1. Go to **Folders** view
2. Click **+ New Folder**
3. Select a group as owner (requires "can_manage_folders" permission for that group)
4. All group members can assign messages to group folders

### Group Filtering

- Each group you belong to appears as a filter button
- Click group filter to see only messages for that group context
- Unread count shows for each group

## Tips & Best Practices

**Organizing Messages**:
- Create folders for different topics (Projects, Important, Archive, etc.)
- Pin urgent messages in inbox
- Use notes to add context or reminders
- Regularly clean up trash to free space

**Writing Messages**:
- Use clear, descriptive subjects
- Format messages with headers and lists for readability
- Reply to keep context rather than starting new threads
- Check recipient list before sending to multiple people

**Managing Inbox**:
- Mark messages as unread if you need to follow up later
- Pin important messages that need attention
- Use folder filters to focus on specific topics
- Archive to folders instead of deleting when possible

## Troubleshooting

### Messages Not Appearing

**Problem**: Sent a message but recipient doesn't see it

**Solutions**:
1. Check if message appears in your Sent view
2. Verify recipient name was correct
3. Ask recipient to check their group filters (if sent to group)
4. Refresh the page

### Can't Find a Message

**Problem**: Looking for a message but can't find it

**Solutions**:
1. Check if it's in Trash view
2. Clear all filters (Personal/Group, Folder)
3. Use search to find by subject or sender
4. Check if accidentally marked as read (if looking for unread)

### Folder Not Showing

**Problem**: Created a folder but can't see it in dropdown

**Solutions**:
1. Go to Folders view to verify it was created
2. Check if folder is for correct owner (Personal vs. Group)
3. Refresh the page
4. If group folder, verify you have folder management permission

### Can't Send to Group

**Problem**: Don't see a group in recipient list

**Solutions**:
1. Verify you're a member of that group
2. Check with administrator if group still exists
3. Refresh the page to reload group list

## Administration

### Admin Configuration

**Route:** `/admin/messages`

**Permission Required:** `ADMIN_READ_MESSAGES`

Administrators can:
- Manage message groups
- Add/remove group members
- Set group permissions
- Configure group folder permissions

See [Admin: Message Groups](/guide/admin/messages) for administrative features.

## Related Documentation

- [Getting Started](/guide/getting-started) - Learn the basics of K-Systems
- [Employee Management](/guide/employee-management) - Managing user accounts
- [Admin: Message Groups](/guide/admin/messages) - Administrative configuration

---

**Last Updated:** 2025-10-01
**Version:** 2.0.0 (Corrected to match actual implementation)
