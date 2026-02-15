# Collaborative Whiteboard

Real-time collaborative whiteboard for team brainstorming, diagrams, and visual collaboration with drawing tools and shared access.

## Overview

Features:
- Real-time collaborative drawing
- Freehand pen and eraser tools
- 5 geometric shapes (line, rectangle, circle, triangle, arrow)
- Color customization (15 presets + custom colors)
- 3 pen sizes (thin, medium, thick)
- Undo/Redo actions
- Save as PNG image
- Access control (private, public, join code)
- Persistent storage

**Required Permission:** `READ_WHITEBOARD` (view), `WRITE_WHITEBOARD` (create/edit), `DELETE_WHITEBOARD` (delete)

## Accessing Whiteboard

**Route:** `/whiteboard`

**How to Access:**
- Desktop: Double-click **Whiteboard** icon
- Menu: Start Menu → Collaboration → Whiteboard
- Or navigate directly to `/whiteboard`

## Interface Layout

The whiteboard has two main modes:

### Whiteboard Manager (List View)

**Three Tabs:**

1. **My Boards** - Your whiteboards
2. **Public Boards** - Organization-wide public whiteboards
3. **Join with Code** - Enter access code to join private whiteboards

**Whiteboard Cards:**
- Whiteboard name
- Creation date
- Participant count
- Access type (Private/Public/Code)
- Join code (if you're the owner)
- Actions: Open, Delete (owner only)

### Canvas View (Drawing Mode)

**Top Toolbar:**
- **Tools**: Pen, Eraser, Shapes, Select
- **Colors**: 15 preset colors + custom color picker
- **Pen Sizes**: Thin (2px), Medium (5px), Thick (10px)
- **Actions**: Undo, Redo, Clear Canvas, Save PNG
- **Exit**: Return to whiteboard list

**Drawing Area:**
- Full-screen HTML5 canvas
- Real-time synchronization with other users
- Touch-enabled for tablets/mobile

## Creating Whiteboards

### New Whiteboard

1. Click **+ New Whiteboard** button
2. Fill in details:

**Whiteboard Information:**
- **Name** (required): Whiteboard title
- **Background Color**: Choose from color picker (default: white)
- **Access Type**:
  - **Private**: Only you can access
  - **Public**: Anyone in your organization can see and join
  - **Join Code**: Requires 6-character code to join

3. Click **Create**
4. New whiteboard appears in "My Boards"

### Access Codes

**For Code-Based Whiteboards:**
- System auto-generates 6-character code
- Or enter custom code (6 characters)
- Share code with collaborators
- Code shown on whiteboard card (owner only)

## Joining Whiteboards

### Join Public Whiteboard

1. Go to **Public Boards** tab
2. Click **Open** on any whiteboard
3. Canvas view opens
4. Start drawing

### Join with Code

1. Go to **Join with Code** tab
2. Enter 6-character access code
3. Click **Join**
4. If code is valid, canvas view opens
5. Whiteboard also appears in your "My Boards" for quick access

### Join Own Whiteboard

1. Go to **My Boards** tab
2. Click **Open** on whiteboard
3. Canvas view opens

## Drawing Tools

### Pen Tool

**Freehand Drawing:**
1. Select **Pen** tool (pencil icon)
2. Choose color from palette
3. Select pen size (thin/medium/thick)
4. Click and drag on canvas to draw
5. Lines appear in real-time for all users

**Touch Support:**
- Touch and drag with finger or stylus
- Supports multi-touch gestures

### Eraser Tool

**Remove Drawings:**
1. Select **Eraser** tool (eraser icon)
2. Eraser size is 3x larger than selected pen size
3. Click and drag over elements to erase
4. Erased elements removed for all users in real-time

**Note**: Eraser removes entire strokes/shapes, not partial elements.

### Shapes Tool

**Draw Geometric Shapes:**
1. Select **Shapes** tool (square icon)
2. Choose shape type from dropdown:
   - **Line** - Straight line
   - **Rectangle** - Rectangle outline
   - **Circle** - Circle outline
   - **Triangle** - Triangle outline
   - **Arrow** - Line with arrowhead
3. Choose color and line width
4. Click and drag to draw shape:
   - Click = start point
   - Drag = end point / size
   - Release = finalize shape

**Shape Properties:**
- Outline only (no fill color)
- Line width based on pen size setting
- Color from color picker

### Select Tool

**Selection (Limited Functionality):**
- Select tool button exists in toolbar
- Currently does not support:
  - Moving elements
  - Resizing elements
  - Deleting selected elements
  - Copy/paste

**Note**: This tool is a UI placeholder with limited implementation.

## Customization Options

### Colors

**15 Preset Colors:**
- Black, White, Red, Green, Blue
- Yellow, Magenta, Cyan, Orange, Purple
- Brown, Gray, Light Pink, Light Green, Light Blue

**Custom Color:**
- Click color picker icon
- Select any hex color
- Color saved for current session

### Pen Sizes

**Three Sizes:**
- **Thin** - 2px width (fine details)
- **Medium** - 5px width (default, general use)
- **Thick** - 10px width (bold strokes, headers)

**Size Preview:**
- Dots show size visually in toolbar
- Size applies to pen and shape tools
- Eraser is 3x the selected size

## Canvas Actions

### Undo

- Click **Undo** button (↶ icon)
- Reverses last action (drawing stroke or shape)
- Can undo multiple times
- Undo stack maintained per session

### Redo

- Click **Redo** button (↷ icon)
- Reapplies undone action
- Redo available after undo
- Cleared when new action taken

### Clear Canvas

1. Click **Clear Canvas** button (trash icon)
2. Confirm in dialog
3. All elements removed for all users
4. Cannot be undone

**Warning**: Clearing canvas is permanent and affects all collaborators.

### Save as PNG

1. Click **Save** button (download icon)
2. Canvas saved in two ways:
   - **PNG download** - Downloads image to your device
   - **Snapshot to database** - Saves image to server for later reference

**Saved Snapshot:**
- Stored in database with whiteboard
- Can be retrieved later
- Includes all elements at time of save

## Real-Time Collaboration

### How It Works

**Synchronization:**
- All drawing actions broadcast in real-time via Socket.io
- Changes appear instantly for all connected users
- Dual approach: API + Socket.io for reliability
- Each stroke/shape synced individually

**Connection Status:**
- Alert shown if connection lost
- Reconnect button to restore connection
- Automatic reconnection attempts

**Participant Count:**
- Shown on whiteboard cards
- Updates when users join/leave
- Visible in canvas view

### Collaborative Features

**What's Synchronized:**
- Pen strokes (freehand drawing)
- Eraser actions (element removal)
- Shapes (all 5 types)
- Canvas clear actions
- Element additions/deletions

**What's NOT Synchronized:**
- User cursors (no live cursor positions)
- Undo/redo (local only)
- User presence indicators (no avatars/names on canvas)
- Selection state

**Multi-User Drawing:**
- Multiple users can draw simultaneously
- No drawing conflicts (all actions applied)
- Changes persist in database
- Historical elements loaded when joining

## Managing Whiteboards

### View Whiteboards

**My Boards Tab:**
- Shows all whiteboards you created
- Shows whiteboards you joined via code
- Sorted by creation date (newest first)

**Public Boards Tab:**
- Shows all public whiteboards in your organization
- Sorted by creation date
- Anyone can open and contribute

### Delete Whiteboard

**Owner Only:**
1. Find whiteboard in "My Boards"
2. Click **Delete** button (trash icon)
3. Confirm deletion
4. Whiteboard soft-deleted (archived)

**Note**: Only whiteboard owners can delete. Deletion affects all users who have access.

### Refresh Lists

- Click **Refresh** button to reload whiteboard lists
- Updates participant counts
- Shows newly created public whiteboards
- Ensures latest data displayed

## Access Control

### Private Whiteboards

- Only creator can access
- Not visible to other users
- No join code
- Full owner control

### Public Whiteboards

- Visible to all organization users
- Anyone can open and draw
- Appears in "Public Boards" tab
- No access code needed

### Code-Based Whiteboards

- Requires 6-character join code
- Share code with specific collaborators
- Not visible in public boards
- Code shown to owner on whiteboard card

**Join Code Format:**
- 6 alphanumeric characters
- Auto-generated or custom
- Case-insensitive

## Persistence and Storage

### Database Storage

**All elements saved:**
- Drawing strokes (pen/eraser)
- Shapes (5 types)
- Element metadata (color, size, user)
- Timestamps

**Load Behavior:**
- Opening whiteboard loads all historical elements
- Elements rendered in chronological order
- Real-time elements added as they occur

### Snapshots

**PNG Snapshots:**
- Created when clicking Save button
- Full canvas exported as PNG image
- Saved to database
- Available for download

**Snapshot Uses:**
- Backup of whiteboard state
- Share outside system (as image)
- Documentation/reporting

## Permission System

### View Whiteboards

**Permission**: `READ_WHITEBOARD`

Can:
- View whiteboard lists
- Open whiteboards (if access granted)
- See existing drawings

Cannot:
- Draw or add elements
- Delete whiteboards

### Create/Edit Whiteboards

**Permission**: `WRITE_WHITEBOARD`

Can:
- Create new whiteboards
- Draw with all tools
- Use eraser
- Clear canvas
- Save snapshots

### Delete Whiteboards

**Permission**: `DELETE_WHITEBOARD`

Can:
- Delete own whiteboards
- Archive whiteboards

**Note**: Only whiteboard owner can delete, regardless of permission.

### Multi-Tenant Security

- All whiteboards scoped to `authority_id`
- Users only see whiteboards within their organization
- Cross-organization access prevented
- Socket rooms isolated by authority

## Responsive Design

### Desktop

- Full-screen canvas utilizes entire viewport
- Toolbar at top with undo/redo buttons
- Mouse and trackpad support

### Tablet/Mobile

- Touch-enabled drawing
- Finger or stylus input
- Touch gestures supported
- Responsive toolbar

### Window Resize

- Canvas content preserved on resize
- Automatic redraw
- No element loss

## Dark Mode Support

**Automatic Background:**
- Light mode: White canvas background
- Dark mode: Dark gray canvas background
- Background color set when creating whiteboard
- Consistent experience across themes

## Tips & Best Practices

**Effective Collaboration:**
- Use clear canvas before starting new session
- Choose high-contrast colors for visibility
- Use shapes for structured diagrams
- Use pen for freehand sketches and annotations
- Save snapshots regularly for backups

**Drawing Tips:**
- Thin pen for details, thick for emphasis
- Use arrow shapes to show relationships
- Circle tool for highlighting areas
- Rectangle tool for boxing content
- Eraser for quick corrections

**Access Control:**
- Private for personal brainstorming
- Public for organization-wide collaboration
- Code-based for team-specific sessions
- Share codes securely (don't post publicly)

**Performance:**
- Avoid excessive detail (thousands of strokes)
- Clear canvas when starting fresh topics
- Save and create new whiteboard for major changes
- Limit simultaneous users to ~10 for best performance

## Troubleshooting

### Connection Lost

**Problem**: "Connection lost" alert appears

**Solutions:**
1. Click **Reconnect** button in alert
2. Check internet connection
3. Refresh browser page
4. Try reopening whiteboard

### Drawings Not Syncing

**Problem**: Other users don't see my drawings

**Solutions:**
1. Check connection status (look for alert)
2. Verify you have `WRITE_WHITEBOARD` permission
3. Ensure whiteboard is open for other users
4. Refresh all browsers

### Can't Join with Code

**Problem**: Code doesn't work

**Solutions:**
1. Verify code is exactly 6 characters
2. Check code with whiteboard owner
3. Ensure whiteboard hasn't been deleted
4. Try copying/pasting code instead of typing

### Save Not Working

**Problem**: Save button doesn't download or save

**Solutions:**
1. Check browser allows downloads
2. Verify `WRITE_WHITEBOARD` permission
3. Try different browser
4. Check browser console for errors

### Whiteboard Won't Delete

**Problem**: Delete button disabled or fails

**Solutions:**
1. Verify you're the whiteboard owner
2. Check you have `DELETE_WHITEBOARD` permission
3. Try refreshing page
4. Contact administrator if persistent

## Related Documentation

- [Getting Started](/guide/getting-started) - Learn the basics of K-Systems
- [Messages](/guide/messages) - Team communication
- [Calendar](/guide/calendar) - Schedule whiteboard sessions

---

**Last Updated:** 2025-10-01
**Version:** 2.0.0 (Corrected to match actual implementation)
