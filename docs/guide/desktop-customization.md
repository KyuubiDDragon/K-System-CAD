# Desktop Customization

Customize your K-Systems desktop to match your workflow with available personalization options.

## Overview

Available Customization:
- Change desktop background
- Manually position desktop icons
- Add/configure desktop widgets
- Adjust taskbar position and auto-hide
- Pin apps to taskbar

**Note:** This documentation describes the current implementation. More advanced customization features may be added in future updates.

## Changing Desktop Background

### Background Selector

**Available Options:**

**Preset Backgrounds:**
- Authority Default (organization-specific)
- Standard (default K-Systems background)
- bg2 (alternative background)
- bg3 (alternative background)

**Custom Upload:**
1. Click background selector widget or access via settings
2. Click **Upload Background**
3. Select image file:
   - Supported formats: JPG, PNG, WEBP
   - Recommended size: 1920x1080 or higher
   - Max file size: 5MB
4. Image uploads to server
5. Background changes immediately

**Technical Details:**
- Background stored in localStorage (`desktop-background`)
- Authority-specific defaults available
- Custom backgrounds uploaded to `/desktop/?action=uploadBackground`

### Limitations

**What's NOT Available:**
- Background fit options (cover/contain/stretch/repeat) - uses CSS background-size: cover
- Blur effect slider
- Brightness adjustment slider
- Colored overlay
- "Set as Desktop Background" from File Manager
- Multiple background rotation/slideshow

## Icon Management

### Manual Positioning

**Rearranging Icons:**
1. Click and drag any desktop icon
2. Drop in desired position
3. Position saves automatically to localStorage
4. Grid snapping enabled (20px grid, hardcoded)

**How It Works:**
- Icon positions stored in localStorage (`desktop-icon-positions`)
- Each icon has x, y coordinates
- Grid snapping prevents pixel-perfect placement
- Position data: `{ iconId: { x: number, y: number } }`

**Icon Size:**
- Fixed at 110px width × 130px height
- Cannot be adjusted (hardcoded in CSS)

### Limitations

**What's NOT Available:**
- Toggle to disable grid snapping
- Icon size options (Small/Medium/Large)
- Icon spacing adjustment
- "Sort Icons" button in UI
- Alphabetical auto-sort
- Category-based auto-organization with folders
- "Reset Layout" button
- Icon alignment tools

**Note:** Icon sorting exists in code (`DesktopView.vue`) but no UI to trigger it.

## Desktop Widgets

### Available Widgets

**1. Weather Widget**
- Current temperature
- Weather conditions
- Location display
- Updates from backend weather API

**2. Calendar Widget**
- Current month calendar
- Today's events list
- Click event to view details

**3. Notes Widget**
- Multiple color-coded sticky notes
- Tags support
- Auto-save to backend
- Move/resize individual notes

### Managing Widgets

**Widget Persistence:**
- Widget states saved to localStorage (`desktop-widgets`)
- Position, size, and visibility restored on reload

**Moving Widgets:**
1. Click widget header/title bar
2. Drag to new position
3. Drop anywhere on desktop
4. Position saves automatically

**Closing Widgets:**
- Click ✖ on widget header
- Widget closes and state saved

### Limitations

**What's NOT Available:**
- Right-click desktop menu to enable/disable widgets
- Widget configuration dialogs (⚙️ settings icon exists but no functionality)
- Weather widget: Location/unit/refresh interval settings
- Calendar widget: Weekend toggle, event filtering
- Notes widget: Font settings, auto-save interval configuration
- Widget resize from UI (some widgets support it, others don't)

**Note:** Widgets are enabled/disabled in code, not via user-accessible UI.

## Taskbar Customization

### Taskbar Position

**Change Position:**
- Available in Quick Settings Panel (if enabled)
- Options: Top, Bottom, Left, Right
- Stored in localStorage (`taskbar-position`)
- Applied via CSS positioning

**Auto-Hide Taskbar:**
- Toggle in Quick Settings Panel
- Hides taskbar when not in use
- Show on mouse hover

### Pinned Apps

**Pin to Taskbar:**
- Functionality exists in `CustomizableTaskbar.vue`
- Pinned apps persist across sessions

### Taskbar Elements

**Standard Elements:**
- Start button (left)
- Open application windows
- System tray icons:
  - Search
  - Browser
  - Weather
  - Settings
  - Notifications
- Clock and date (right)
- User menu (right)

### Limitations

**What's NOT Available:**
- Right-click taskbar settings menu
- Taskbar icon size options (hardcoded)
- Show/hide individual system tray icons
- Combine windows toggle
- Taskbar settings dialog

## Quick Settings Panel

**Available Settings:**
- Volume slider (UI only, not functional)
- Brightness slider (UI only, not functional)
- WiFi toggle (UI only)
- Bluetooth toggle (UI only)
- Taskbar position buttons (functional)
- Auto-hide taskbar toggle (functional)
- Quick access apps grid

**Note:** Most Quick Settings are UI placeholders. Only taskbar position and auto-hide are functional.

## Theme Settings

### Dark Mode

**Current Implementation:**
- CSS variables for dark/light theme colors
- Theme switching may be available depending on system settings
- No user-facing toggle in UI

### Limitations

**What's NOT Available:**
- Light/Dark/Auto theme selector
- Custom schedule (day/night switching)
- Theme color picker (Primary/Accent/Background)
- Window transparency slider
- Blur behind windows effect
- Custom theme creation
- Authority branding color overrides

**Note:** Dark mode support exists in CSS but lacks UI controls for user selection.

## Start Menu

### Current Features

**Start Menu Components:**
- All apps alphabetical list
- App categories (Tools & Utilities section)
- Search functionality
- User profile access

**Pin Apps:**
- Pinned apps support exists in `CustomizableTaskbar.vue`

### Limitations

**What's NOT Available:**
- Recent apps tracking (last 10 apps)
- Clear recent apps
- Pin to Start functionality from right-click menu
- App category customization (admin or user)
- Start menu settings

## Saving and Profiles

### Current Persistence

**What's Saved:**
- Desktop background (localStorage)
- Icon positions (localStorage)
- Widget states (localStorage)
- Taskbar position (localStorage)
- Pinned apps (backend)

**Automatic Saving:**
- All changes save immediately
- No manual save required
- Data persists across browser sessions (localStorage)

### Limitations

**What's NOT Available:**
- Export configuration to .json file
- Import configuration from .json
- Desktop profiles system
- Profile switching
- Configuration sharing
- Backup/restore functionality

## Advanced Features

### Current Implementation

**None of the following advanced features are implemented:**
- ❌ Custom CSS editor
- ❌ Keyboard shortcuts customization
- ❌ Animation speed settings
- ❌ Hardware acceleration toggle
- ❌ Double-click vs single-click setting
- ❌ Display settings (resolution, scaling, orientation)
- ❌ Icon spacing controls
- ❌ Performance settings
- ❌ Window effects controls

## Tips and Best Practices

**Organizing Your Desktop:**
- Manually position frequently used apps for quick access
- Use widgets for glanceable information
- Close widgets you don't use to reduce clutter
- Choose a background that doesn't distract from work

**Performance:**
- Limit number of active widgets
- Choose simpler background images
- Consider solid color backgrounds for best performance

**Customization:**
- Upload custom backgrounds that match your brand
- Position taskbar where it's most comfortable (if enabled)
- Enable auto-hide to maximize screen space

## Troubleshooting

### Background Won't Change
- Check file format (must be JPG, PNG, or WEBP)
- Verify file size is under 5MB
- Clear browser cache
- Try a different image

### Icons Won't Stay in Position
- Check browser allows localStorage
- Clear browser cache and reload
- Verify you're not in private/incognito mode

### Widgets Not Loading
- Check internet connection (weather widget requires API access)
- Refresh the page
- Clear browser cache
- Check browser console for errors

### Taskbar Position Not Changing
- Ensure Quick Settings Panel is accessible
- Check localStorage is enabled
- Clear localStorage: `desktop-taskbar-position`
- Refresh the page

## Related Documentation

- [Desktop Mode](/guide/desktop-mode) - Desktop interface overview
- [Desktop Apps](/guide/desktop-apps) - Available applications
- [Desktop Widgets](/guide/desktop-widgets) - Widget details
- [Getting Started](/guide/getting-started) - K-Systems basics

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
