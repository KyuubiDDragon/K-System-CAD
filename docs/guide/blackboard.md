# Blackboard & Bulletin System

Simple bulletin board system for posting announcements to your organization with rich text formatting and read receipts.

## Overview

Features:
- Create posts with title and rich text content
- Three board types (admin, employee, global)
- Pin important posts to top
- Read receipts ("Lesebestätigung")
- Color-coded entries
- TiptapEditor with rich formatting
- YouTube/Twitch video embedding
- Authority-specific isolation

**Required Permission:** `READ_BLACKBOARD_[TYPE]` (view), `WRITE_BLACKBOARD_[TYPE]` (create/edit)

## Accessing Blackboard

**Desktop:** Double-click **Blackboard** icon
**Menu:** Start Menu → Communication → Blackboard
**Routes:**
- `/blackboard/admin` - Admin board
- `/blackboard/employee` - Employee board
- `/blackboard/global` - Global board

## Board Types

**Admin Board** (`WRITE_BLACKBOARD_ADMIN`)
- Administrative announcements
- Management posts
- Requires admin permissions

**Employee Board** (`WRITE_BLACKBOARD_EMPLOYEE`)
- Employee-level announcements
- Department updates
- Read receipts available

**Global Board** (`WRITE_BLACKBOARD_GLOBAL`)
- Organization-wide announcements
- Company news
- No read receipts

**Permission Structure:**
- Each board type has separate READ/WRITE permissions
- Format: `WRITE_BLACKBOARD_ADMIN`, `READ_BLACKBOARD_EMPLOYEE`, etc.
- Users with `ALL_PERMISSIONS` have full access

## Interface Layout

### Main View

**Top Section:**
- Info alert explaining current board type
- **Add Entry** button (if you have WRITE permission)

**Entry Display:**
- Title and author
- Date posted (DD.MM.YYYY HH:MM format)
- Colored left border (customizable)
- Rich text content with embedded videos
- Pin icon if pinned
- Read confirmation section (admin/employee boards only)
- Action buttons: Pin/Unpin, Edit, Delete

**Entry Order:**
- Pinned posts always at top
- Within each section (pinned/unpinned), sorted by date descending

**Loading States:**
- Progress spinner while fetching
- Empty state icon and message if no entries

## Creating Posts

### New Entry

1. Click **Add Entry** button (requires `WRITE_BLACKBOARD_[TYPE]` permission)
2. Dialog opens with form

**Form Fields:**

**Title*** (required)
- Text field
- Clear, descriptive title
- Example: "Office Closure - Holiday Schedule"

**Author*** (required)
- Auto-filled with your username
- Can be edited

**Content*** (required)
- TiptapEditor rich text editor
- Full formatting options:
  - Bold, italic, underline
  - Lists (bullet, numbered)
  - Links
  - Headings
  - Text alignment
  - Code blocks
- Video embedding:
  - YouTube URLs automatically converted to embeds
  - Twitch URLs supported (clips and videos)
- Character limit handled by editor
- Shows validation error if empty

**Border Color**
- Color picker with predefined swatches
- Categories:
  - Blues: #1976D2, #2196F3, #03A9F4, #00BCD4
  - Greens/Yellows: #4CAF50, #8BC34A, #CDDC39, #FFEB3B
  - Oranges/Reds: #FF9800, #FF5722, #F44336, #E91E63
  - Purples/Greys: #673AB7, #9C27B0, #607D8B, #9E9E9E
  - Greyscale: #FFFFFF, #BDBDBD, #757575, #212121
- Default: #212121 (dark grey)
- Appears as 4px left border on entry card

**Options:**

- **Lesebestätigung erforderlich?** (Read confirmation required)
  - Checkbox
  - Only available for admin/employee boards (NOT global)
  - When enabled, shows "Mark as Read" button
  - Tracks who has read the post

- **Pin?** (Pin to top)
  - Checkbox
  - Pinned posts appear at top of board
  - Highlighted with pin icon
  - Special border styling

3. Click **Save** to publish
4. Or click **Cancel** to discard

**Validation:**
- Title is required
- Author is required
- Content is required
- Save button disabled until all fields valid

## Editing Posts

### Edit Entry

1. Click **Edit** icon (pencil) on entry
2. Dialog opens with current data pre-filled
3. Modify any fields:
   - Title
   - Author
   - Content
   - Border color
   - Read confirmation setting
   - Pin status
4. Click **Save** to update
5. Changes applied immediately

**Edit Permissions:**
- Requires `WRITE_BLACKBOARD_[TYPE]` permission
- No author restriction in code (anyone with WRITE can edit)
- Best practice: only edit your own posts

**Edit Behavior:**
- All fields editable
- TiptapEditor initialized with raw HTML
- Video embeds preserved in content
- Color preserved from original

## Deleting Posts

### Delete Entry

1. Click **Delete** icon (trash) on entry
2. Confirmation dialog appears:
   - Warning icon
   - "Are you sure you want to delete '[title]'?"
   - Warning about permanent deletion
3. Click **Delete** to confirm
4. Or **Cancel** to abort
5. Entry removed from database

**Delete Permissions:**
- Requires `canDelete` permission (from route.meta or props)
- Delete button only shows if you have permission
- Permanent deletion (not soft delete)

**Warning:** Deletion is permanent. Entry cannot be recovered.

## Pin/Unpin Posts

### Pin Entry

1. Click **Pin** icon (grey pin) on entry
2. Entry immediately pinned
3. Moves to top of board
4. Pin icon turns primary color
5. Entry gets special pinned styling (blue border)

### Unpin Entry

1. Click **Unpin** icon (primary pin-off) on pinned entry
2. Entry immediately unpinned
3. Returns to chronological order
4. Pin icon removed
5. Pinned styling removed

**Pin Features:**
- Multiple posts can be pinned
- Pinned posts always sort to top
- Within pinned section, sorted by date descending
- Visual indicators:
  - Pin icon in title area
  - Pin icon in actions
  - Special border color
  - `.pinned-entry` CSS class

**Best Practices:**
- Pin only critical information
- Unpin when no longer urgent
- Review pinned posts regularly

## Read Confirmations

### Mark as Read

**Available on:**
- Admin board
- Employee board
- NOT on global board

**When Enabled:**
1. Entry shows read confirmation section
2. Displays "Gelesen von:" (Read by:)
3. Shows comma-separated list of readers
4. Or "Noch niemand" (Nobody yet) if no readers
5. **Mark as Read** button on right

**Mark Entry as Read:**
1. Scroll to read confirmation section
2. Click **Mark as Read** button
3. Button shows loading state
4. Your username added to readers list
5. Success toast notification

**Read Receipt Display:**
- Small chips for each reader
- Info color (blue tonal)
- Label variant
- Wrapping if many readers
- Eye icon indicator

**Tracking:**
- Backend API: `action=setReaded`
- POST to `/api/blackboard/?action=setReaded&boardType=[type]`
- Sends entry `id`
- Updates `readers` field (comma-separated string)

**Use Cases:**
- Mandatory reading confirmation
- Compliance tracking
- Important announcements
- Policy acknowledgments

## Video Embedding

### Supported Platforms

**YouTube:**
- Full video URLs: `https://www.youtube.com/watch?v=VIDEO_ID`
- Short URLs: `https://youtu.be/VIDEO_ID`
- Automatically extracts video ID
- Converts to iframe embed: `https://www.youtube.com/embed/VIDEO_ID`

**Twitch:**
- Clips: `https://clips.twitch.tv/CLIP_ID`
- Videos: `https://twitch.tv/videos/VIDEO_ID`
- Requires parent parameter for embedding
- Live channels not supported

**How It Works:**
1. Editor uses `<oembed url="...">` tags
2. On display, `convertOembedToIframe()` function processes HTML
3. Creates actual `<iframe>` elements
4. Replaces oembed tags
5. Renders embedded videos

**Iframe Attributes:**
- Width: 100%
- Height: 315px
- Aspect ratio: 16/9
- Frameborder: 0
- Allowfullscreen: true
- Permissions: accelerometer, autoplay, clipboard-write, etc.

**Display:**
- Videos render inline in entry content
- Responsive sizing
- Rounded corners (8px)
- Shadow effect
- Margin: 16px vertical

## Data Model

**BlackboardEntry Interface:**
```typescript
interface BlackboardEntry {
  id: number;
  title: string;
  author: string;
  text: string; // Raw HTML from TiptapEditor
  renderedText?: string; // Processed with iframes
  color: string | null; // Hex color
  need_readed: boolean; // Read confirmation required
  pinned: boolean; // Pin to top
  created: string; // DateTime string
  readers: string | null; // Comma-separated usernames
}
```

**Form Data:**
```typescript
interface EntryFormData {
  id?: number | null; // Null for new, ID for edit
  title: string;
  author: string;
  text: string;
  color: string | null;
  need_readed: boolean;
  pinned: boolean;
}
```

**Backend Conversion:**
- Booleans sent as 1 (true) or 0 (false)
- Frontend converts to boolean on fetch
- Both `Number(value) == 1` and `value === true` handled

## API Integration

**Base Path:** `/api/blackboard/`

**Actions:**

**Get Entries:**
```
GET /api/blackboard/?action=getEntries&boardType=[admin|employee|global]
Response: { data: BlackboardEntry[] }
```

**Add Entry:**
```
POST /api/blackboard/?action=addEntry&boardType=[type]
Payload: { title, author, text, color, need_readed: 0|1, pinned: 0|1 }
```

**Edit Entry:**
```
POST /api/blackboard/?action=editEntry&boardType=[type]
Payload: { id, title, author, text, color, need_readed: 0|1, pinned: 0|1 }
```

**Delete Entry:**
```
POST /api/blackboard/?action=deleteEntry&boardType=[type]
Payload: { id: number }
```

**Mark as Read:**
```
POST /api/blackboard/?action=setReaded&boardType=[type]
Payload: { id: number }
```

**Pin Entry:**
```
POST /api/blackboard/?action=pinEntry&boardType=[type]
Payload: { id: number }
```

**Unpin Entry:**
```
POST /api/blackboard/?action=unpinEntry&boardType=[type]
Payload: { id: number }
```

**Error Handling:**
- API errors shown via toast notifications
- Console logging for debugging
- Loading states during operations
- Graceful fallback to empty list on fetch error

## Multi-Tenant Security

**Authority Isolation:**
- All entries scoped to `authority_id`
- Users only see entries from their organization
- Backend filters by authority automatically
- Cross-organization access prevented

**Permission Checks:**
- Granular per board type
- `WRITE_BLACKBOARD_ADMIN`, `WRITE_BLACKBOARD_EMPLOYEE`, `WRITE_BLACKBOARD_GLOBAL`
- Separate READ permissions
- Admin with `ALL_PERMISSIONS` has full access

## Desktop Window Mode

**Props Support:**
```typescript
interface Props {
  meta?: Record<string, any>; // Contains boardType
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
  id?: number | string; // Entry ID to scroll to
}
```

**Board Type Resolution:**
1. Check `props.meta.boardType` (desktop window)
2. Fall back to `route.meta.boardType` (regular route)
3. Default to 'global' if not set

**Entry Highlighting:**
- Pass `id` prop or `?id=X` query parameter
- On mount, scrolls to entry with that ID
- Adds highlight animation (3 second pulse)
- Uses `#entry-${id}` element selector

## Limitations

**What's NOT Available:**

### Content Features
- ❌ Categories/tags for posts
- ❌ File attachments (only inline videos)
- ❌ Comments or discussions
- ❌ Reactions or emojis
- ❌ @mentions in content
- ❌ Search or filtering
- ❌ Image uploads (no file picker)

### Scheduling Features
- ❌ Schedule posts for future
- ❌ Post expiration dates
- ❌ Auto-archive old posts
- ❌ Recurring posts
- ❌ Draft saving
- ❌ Auto-save functionality

### Notification Features
- ❌ Email notifications
- ❌ Push notifications
- ❌ In-app notification badges
- ❌ Reminders for unread posts
- ❌ Digest emails
- ❌ Notification preferences

### User Features
- ❌ Target specific users/departments
- ❌ View who hasn't read (only who has)
- ❌ Send reminders to non-readers
- ❌ User read history
- ❌ Acknowledgment reports

### Management Features
- ❌ Edit history/versioning
- ❌ Post templates
- ❌ Archive functionality
- ❌ Export posts
- ❌ Bulk operations
- ❌ Post statistics/analytics

### UI Limitations
- ❌ Sidebar navigation
- ❌ Quick filters (unread, priority, date)
- ❌ Advanced search
- ❌ Saved searches
- ❌ Category filtering
- ❌ Pagination (all entries load at once)
- ❌ Keyboard shortcuts

### Security Features
- ❌ Edit time limit
- ❌ Author-only edit restriction in UI
- ❌ Edit reason tracking
- ❌ Audit trail
- ❌ Lock comments feature
- ❌ Content moderation tools

**Current Reality:**
This is a **simple bulletin board** for posting announcements with rich text, pinning, and optional read confirmation. It is not a comprehensive communication or collaboration platform.

## Use Cases

**Organizational Announcements:**
- Company-wide news
- Policy updates
- Important notices
- Event announcements

**Department Updates:**
- Team communications
- Project announcements
- Shift changes
- Training notices

**Read Tracking:**
- Mandatory reading confirmation
- Policy acknowledgments
- Important updates requiring confirmation
- Compliance documentation

**Visual Organization:**
- Color-code by importance or category
- Pin urgent items to top
- Separate boards for different audiences

## Best Practices

**Creating Posts:**
1. Use clear, descriptive titles
2. Keep content concise and scannable
3. Use formatting for readability
4. Choose meaningful border colors
5. Enable read confirmation for important items
6. Pin only critical information

**Content Guidelines:**
- One topic per post
- Use headings to structure content
- Include relevant links
- Embed videos for demonstrations
- Proofread before posting

**Management:**
1. Unpin posts when no longer urgent
2. Delete outdated information
3. Use consistent color coding
4. Limit pinned posts to 3-5
5. Review board regularly

**Read Confirmations:**
1. Only enable for truly important posts
2. Use for compliance documentation
3. Check reader list periodically
4. Don't overuse - reduces effectiveness

## Troubleshooting

### Can't Create Post

**Problem:** Add Entry button doesn't work or is missing

**Solutions:**
1. Check you have `WRITE_BLACKBOARD_[TYPE]` permission
2. Verify correct board type in URL
3. Ensure feature enabled for your authority
4. Refresh page
5. Check browser console for errors

### Post Not Saving

**Problem:** Save button disabled or save fails

**Solutions:**
1. Verify all required fields filled:
   - Title
   - Author
   - Content (TiptapEditor must have text)
2. Check validation messages
3. Check browser console for API errors
4. Verify internet connection
5. Try again

### Video Not Embedding

**Problem:** Video URL doesn't convert to embed

**Solutions:**
1. Verify URL format is correct
2. Only YouTube and Twitch supported
3. Twitch live channels not supported
4. Check browser console for conversion errors
5. Try direct embed HTML if needed

### Can't Mark as Read

**Problem:** Mark as Read button doesn't work

**Solutions:**
1. Feature only available on admin/employee boards (not global)
2. Post must have "Lesebestätigung" enabled
3. Check internet connection
4. Verify you're not already in readers list
5. Refresh and try again

### Can't Pin Entry

**Problem:** Pin button doesn't work

**Solutions:**
1. Check you have `WRITE_BLACKBOARD_[TYPE]` permission
2. Verify internet connection
3. Check browser console for errors
4. Refresh page and try again

### Entries Not Loading

**Problem:** Empty board or loading spinner doesn't stop

**Solutions:**
1. Verify boardType is set correctly
2. Check you have READ permission for board type
3. Check network tab for API errors
4. Verify backend is running
5. Check authority_id is correct

## Related Documentation

- [💬 Messages](/guide/messages) - Internal messaging
- [📅 Calendar](/guide/calendar) - Event management
- [📝 Documents](/guide/documents) - Document management
- [✅ Todos](/guide/todos) - Task management

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
