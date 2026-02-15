# Desktop Interface

K-Systems features a Windows-style desktop interface that provides a familiar user experience.

## Overview

The desktop interface consists of several key components:

- **Desktop Background**: Full-screen workspace area
- **Desktop Icons**: Quick-access icons for applications (left side)
- **Windows**: Draggable and resizable application windows
- **Taskbar**: Bottom bar with start menu, windows, and system tray
- **Start Menu**: Application launcher with categories
- **Widgets**: Desktop widgets for quick information

## Desktop Components

### Desktop Background

The desktop background is the main workspace area.

**Features:**
- Default background image: `/frontend/public/img/bg.jpg`
- Full-screen display
- Applications and icons appear on top

### Desktop Icons

Desktop icons provide quick access to applications.

**Features:**
- Located on left side of desktop
- Double-click to launch application
- Single-click to select
- Icons generated based on user permissions

**Opening Apps from Icons:**
1. Locate icon on desktop (left side)
2. Double-click icon
3. Application opens in new window

### Windows

Applications open in resizable, draggable windows.

**Window Components:**
- **Title Bar**: Shows app icon and name
- **Minimize Button (−)**: Hide window to taskbar
- **Maximize Button (□)**: Expand to full screen
- **Close Button (×)**: Close application

**Window Management:**

**Move Window:**
1. Click and hold title bar
2. Drag window to new position
3. Release to place

**Resize Window:**
1. Hover over window edge or corner
2. Cursor changes to resize arrows
3. Drag to resize
4. Release when done

**Minimize Window:**
- Click minimize button (−)
- Window hides to taskbar
- Click taskbar button to restore

**Maximize Window:**
- Click maximize button (□)
- Window expands to full screen
- Click again or double-click title bar to restore

**Close Window:**
- Click close button (×)
- Application closes
- Window removed from desktop and taskbar

**Window Focus:**
- Click window to bring to front
- Active window appears on top
- Multiple windows can be open

### Taskbar

The taskbar is at the bottom of the screen.

**Taskbar Sections:**

**1. Start Button (Windows Icon)**
- Opens Start Menu
- Shows all available applications

**2. Search Button (Magnifying Glass)**
- Opens global search
- Keyboard shortcut: Ctrl+K

**3. Pinned Applications**
- WaterDuck browser (pinned by default)
- Quick launch without Start Menu

**4. Open Windows**
- Shows all open application windows
- Click to focus/restore window
- Active window highlighted
- Grouped by application type

**5. System Tray (Right Side)**
- **Weather**: Current temperature and condition
- **Messages**: Email icon with unread count badge
- **Reports**: Document icon with open reports count
- **WiFi/Volume/Battery**: System indicators
- **Language Switcher**: Toggle between DE/EN

**6. Clock & Date**
- Current time (HH:MM)
- Current date (DD.MM.YYYY)
- Click to open calendar

### Start Menu

Access all K-Systems applications from the Start Menu.

**Opening Start Menu:**
1. Click Windows icon in taskbar (bottom-left)
2. Menu slides up from bottom
3. Browse or search for apps

**Start Menu Structure:**
- **User Section**: Avatar, name, and status (top)
- **Applications Grid**: Favorite apps (up to 8 apps)
- **Tools & Utilities Grid**: Calculator, games, and utilities
- **All Apps List**: Complete alphabetical list
- **Actions**: Logout and close buttons (bottom)

**Application Categories:**

Apps are organized into functional groups:
- **Employee Management**: Employee, Training, Vacation, Crew
- **Reports & Documents**: Reports, Documents, Templates
- **Communication**: Messages, Calendar, Todos, Whiteboard
- **Dispatch & Operations**: Dispatch, Map, Fireprotection
- **File Management**: Person Files, Vehicle Files, Apartment Files
- **Finance**: Invoices, Companies, Applications
- **Administrative**: User Management, Roles, Settings (admin only)
- **Tools & Utilities**: Calculator, Minesweeper, Solitaire, Sudoku, QuackleJump, Weather, Whiteboard, WaterDuck

**Using Start Menu:**
1. Click Windows icon in taskbar
2. Browse application sections
3. Click application to launch
4. Start Menu closes automatically

### Widgets

Desktop widgets provide quick information at a glance.

**Available Widgets:**
- **Weather Widget**: Current temperature, condition, 5-day forecast
- **Calendar Widget**: Today's events
- **Notes Widget**: Quick sticky notes with auto-save

**Widget Features:**
- Drag to reposition
- Resize by dragging corner
- Minimize to title bar
- Close with × button
- Positions automatically saved

See [Desktop Widgets](/guide/desktop-widgets) for detailed information.

## Using the Desktop

### Opening Applications

**Method 1: Desktop Icons**
1. Find app icon on desktop (left side)
2. Double-click icon
3. App opens in window

**Method 2: Start Menu**
1. Click Windows icon in taskbar
2. Browse or search for app
3. Click to launch

**Method 3: Search**
1. Click search icon in taskbar
2. Or press Ctrl+K
3. Type app name
4. Click result to open

**Method 4: Taskbar (Pinned Apps)**
1. Click pinned app icon in taskbar
2. App launches immediately

### Managing Windows

**Multiple Windows:**
- Open multiple apps simultaneously
- Each runs in separate window
- Click window to bring to front

**Switching Windows:**
- Click visible window to focus
- Click taskbar button to restore minimized window
- Active window highlighted in taskbar

**Arranging Windows:**
- Drag windows to position
- Resize as needed
- Minimize unused windows

## Desktop Features

**Multi-Tasking:**
- Run multiple applications at once
- Each window independent
- Switch between windows instantly

**Language Switching:**
- Click language code in taskbar (DE/EN)
- Select from dropdown menu
- Interface updates immediately

**System Tray Notifications:**
- Red badge on Messages icon (unread count)
- Red badge on Reports icon (open reports count)
- Click icons to open respective apps

## Tips & Tricks

### Productivity

- **Use Multiple Windows**: Work on multiple tasks simultaneously
- **Pin Favorite Apps**: WaterDuck pre-pinned in taskbar
- **Use Search**: Fastest way to find apps (Ctrl+K)
- **Use Widgets**: Quick glance at weather, calendar, notes

### Window Management

- **Double-click Title Bar**: Quick maximize/restore
- **Minimize When Done**: Keep desktop clean
- **Use Taskbar**: Quick access to open windows
- **Close Unused Windows**: Better performance

## Troubleshooting

### Icons Not Loading

**Solution:**
1. Refresh page (F5)
2. Re-login if needed
3. Check permissions

### Window Stuck Off-Screen

**Solution:**
1. Close window from taskbar (click taskbar button)
2. Reopen application
3. Window opens in default position

### Taskbar Not Responding

**Solution:**
1. Refresh page (F5)
2. Clear browser cache
3. Re-login if needed

## Limitations

The following features mentioned in documentation **do not exist** in the current codebase:

❌ **Not Available:**
- Desktop background customization (upload/selection)
- Icon sorting options (by priority, alphabetically, by category)
- Icon organization features (auto-sort, reset layout)
- Right-click desktop menu
- Desktop settings panel
- Theme switching (light/dark mode)
- Keyboard navigation for desktop icons
- Icon badges with notification counts
- Folder icons grouping

---

## Next Steps

- [Desktop Customization](/guide/desktop-customization) - Personalize desktop
- [Desktop Apps](/guide/desktop-apps) - Explore available apps
- [Desktop Widgets](/guide/desktop-widgets) - Add widgets
- [Desktop Mode](/guide/desktop-mode) - Understanding the desktop system
- [Global Search](/guide/search) - Using search

---

**Note**: If you've used Windows, you'll feel right at home with K-Systems' desktop interface!
