# Desktop Mode

K-Systems features a Windows-style desktop interface that provides a familiar desktop operating system experience in your browser.

## Overview

Desktop Mode is a windowing system that mimics a traditional desktop OS with:

- Multiple application windows
- Taskbar with open applications and system tray
- Start Menu for launching applications
- Desktop icons for quick access
- Window management (minimize, maximize, close)
- Desktop widgets for information at-a-glance

## Desktop Components

### Desktop Background

The desktop background provides the workspace foundation.

**Features:**
- Default background image at `/frontend/public/img/bg.jpg`
- Customizable per user (if customization features are implemented)
- Full-screen desktop area

### Desktop Icons

Desktop icons provide quick access to applications.

**Features:**
- Displayed on the left side of desktop
- Double-click to launch applications
- Single-click to select
- Icons generated from available applications based on permissions

### Windows

Applications open in independent, manageable windows.

**Window Components:**
- **Title Bar**: Application icon and name
- **Control Buttons**: Minimize (−), Maximize (□), Close (×)
- **Content Area**: Application interface
- **Resizable**: Drag edges/corners to resize

**Window Controls:**

**Moving Windows:**
- Click and drag title bar to move
- Release to place window

**Resizing Windows:**
- Hover over window edge or corner
- Drag to resize
- Minimum and maximum sizes enforced

**Minimizing:**
- Click minimize button (−)
- Window hides to taskbar
- Click taskbar button to restore

**Maximizing:**
- Click maximize button (□)
- Window expands to full screen
- Click again to restore

**Closing:**
- Click close button (×)
- Application closes
- Window removed from desktop

**Window Stacking:**
- Click window to bring to front
- Active window appears on top
- Multiple windows can be open

### Taskbar

The taskbar is located at the bottom of the screen and provides application management.

**Taskbar Sections:**

**1. Start Button (Windows Icon)**
- Opens Start Menu
- Access all available applications

**2. Search Button (Magnifying Glass)**
- Opens global search
- Find applications and data quickly

**3. Pinned Applications**
- WaterDuck browser pinned by default
- Quick launch without Start Menu

**4. Open Windows**
- Shows all currently open application windows
- Click to focus/restore window
- Active window highlighted

**5. System Tray**
- Weather widget (shows current temperature and condition)
- Messages icon (with unread count badge)
- Reports icon (with open reports count)
- WiFi, volume, battery indicators
- Language switcher (DE/EN)

**6. Clock & Date**
- Current time (HH:MM format)
- Current date (DD.MM.YYYY format)
- Click to open calendar

### Start Menu

The Start Menu provides access to all K-Systems applications.

**Start Menu Structure:**
- **User Section**: Avatar, name, and status (top)
- **Applications Grid**: Favorite apps (up to 8)
- **Tools & Utilities Grid**: Calculator, games, utilities
- **All Apps List**: Complete alphabetical app list
- **Actions**: Logout and close buttons

**Application Categories:**

Applications are organized into groups:
- **Employee Management**: Employee, Training, Vacation, Crew
- **Reports & Documents**: Reports, Documents, Templates
- **Communication**: Messages, Calendar, Todos, Whiteboard
- **Dispatch & Operations**: Dispatch, Map, Fireprotection
- **File Management**: Person Files, Vehicle Files, Apartment Files
- **Finance**: Invoices, Companies, Applications
- **Administrative**: User Management, Roles, Settings (admin only)
- **Tools & Utilities**: Calculator, Minesweeper, Solitaire, Sudoku, QuackleJump, Weather, Whiteboard, WaterDuck

**Opening Start Menu:**
1. Click Windows icon in taskbar (bottom-left)
2. Menu slides up from taskbar
3. Browse or search for applications
4. Click app to launch

**Search Functionality:**
- Type application name in search bar (if available)
- Real-time filtering
- Press Enter or click result to launch

### Desktop Widgets

Widgets provide quick information without opening full applications.

**Available Widgets:**
- **Weather Widget**: Current temperature, condition, location, forecast
- **Calendar Widget**: Today's events and calendar view
- **Notes Widget**: Quick note-taking with auto-save

See [Desktop Widgets](/guide/desktop-widgets) for detailed information.

## Using Desktop Mode

### Opening Applications

**Method 1: Desktop Icons**
1. Locate application icon on desktop (left side)
2. Double-click icon
3. Application opens in new window

**Method 2: Start Menu**
1. Click Windows icon in taskbar
2. Browse application categories or use search
3. Click application to launch

**Method 3: Taskbar Search**
1. Click search icon in taskbar
2. Type to search
3. Click result to open

**Method 4: Pinned Apps (WaterDuck)**
1. Click pinned app icon in taskbar
2. App launches immediately

### Managing Multiple Windows

**Opening Multiple Windows:**
- Each application runs in separate window
- Open as many windows as needed
- Switch between windows by clicking

**Switching Windows:**
- Click any visible window to focus it
- Click taskbar button to restore minimized window
- Active window has highlighted taskbar button

**Arranging Windows:**
- Drag windows by title bar to position
- Resize windows as needed
- Minimize unused windows to taskbar

## Desktop Mode Features

**Multi-Tasking:**
- Run multiple applications simultaneously
- Each window maintains independent state
- Switch between windows instantly

**Persistent State:**
- Window positions remembered
- Application state preserved
- Minimized windows stay in taskbar

**Taskbar Management:**
- See all open applications at a glance
- Grouped windows by application type
- Notification badges on system tray icons

**Language Switching:**
- Click language code in taskbar (e.g., "DE", "EN")
- Select desired language from menu
- Interface updates immediately

## Responsive Design

Desktop Mode adapts to screen size:

**Desktop (≥1024px):**
- Full desktop mode with all features
- Multiple windows supported
- Complete taskbar and system tray

**Tablet (768px - 1023px):**
- Simplified desktop mode
- Touch-optimized controls
- Compact taskbar

**Mobile (<768px):**
- Automatic fallback to mobile view
- Single-app interface
- Bottom navigation

## Performance

**Optimal Performance:**
- Keep 5-10 windows open maximum
- Close unused applications
- Minimize instead of having many windows visible

**Browser Requirements:**
- Modern browser (Chrome 90+, Firefox 88+, Edge 90+)
- Hardware acceleration recommended
- 8GB+ RAM recommended for best experience

## Troubleshooting

### Windows Not Opening

**Solution:**
1. Check browser console (F12) for errors
2. Verify application permissions
3. Refresh page (F5)
4. Clear browser cache

### Taskbar Not Responding

**Solution:**
1. Refresh page (F5)
2. Check browser console for errors
3. Clear browser cache and reload

### Icons Not Loading

**Solution:**
1. Refresh page (F5)
2. Verify logged in correctly
3. Check permissions for applications

## Limitations

The following features mentioned in documentation **do not exist** in the current codebase:

❌ **Not Available:**
- Custom background upload/selection (only default background)
- Icon sorting options (Priority, Alphabetical, Category)
- Reset icon layout functionality
- Taskbar auto-hide feature
- Taskbar position changes (left, right, top) - only bottom position
- Window snapping to screen edges
- Virtual desktops
- Window groups
- Gesture support
- Keyboard shortcuts (most shortcuts rely on browser)
- Desktop settings panel (right-click menu)
- Theme switching (light/dark mode for desktop)

## Next Steps

- [Desktop Interface](/guide/desktop-interface) - Learn the desktop components
- [Desktop Apps](/guide/desktop-apps) - Explore available applications
- [Desktop Widgets](/guide/desktop-widgets) - Add widgets to desktop
- [Global Search](/guide/search) - Use search functionality

---

**Note**: Desktop Mode provides a familiar Windows-like experience for users who prefer traditional desktop interfaces. The system automatically adapts to different screen sizes for optimal usability.
