# Desktop Widgets

Desktop widgets provide quick access to information without opening full applications.

## Overview

K-Systems offers three desktop widgets:

- **Notes Widget** - Quick sticky notes with auto-save
- **Calendar Widget** - Today's events at a glance
- **Weather Widget** - Current weather and 5-day forecast

Each widget can be moved, resized, minimized, and closed independently.

## Widget Container

All widgets share common features through the widget container system.

### Widget Controls

**Header Bar:**
- **Icon and Title**: Identifies the widget type
- **Minimize Button (−)**: Collapse widget to title bar only
- **Close Button (×)**: Remove widget from desktop

**Widget Features:**
- **Draggable**: Click and drag title bar to move
- **Resizable**: Drag bottom-right corner to resize
- **Auto-save**: Position and size saved automatically
- **Focus Management**: Click widget to bring to front

### Moving Widgets

**Reposition Widget:**
1. Click widget title bar
2. Drag to desired location
3. Release mouse button
4. Position saves automatically

### Resizing Widgets

**Change Widget Size:**
1. Hover over bottom-right corner
2. Cursor changes to resize icon
3. Click and drag to resize
4. Release when desired size reached

**Size Limits:**
- Minimum width: 200-320px (varies by widget)
- Minimum height: 150-400px (varies by widget)
- Maximum size: Based on screen resolution

### Managing Widgets

**Minimize Widget:**
- Click minimize button (−)
- Widget collapses to title bar
- Double-click title bar to restore

**Close Widget:**
- Click close button (×)
- Widget disappears from desktop
- Re-enable from desktop menu

**Focus Widget:**
- Click anywhere on widget
- Widget moves to front (above other widgets)

## Notes Widget

Quick sticky notes for temporary information.

### Features

- Multi-line text editor
- Auto-save every 1 second while typing
- Auto-save every 30 seconds when idle
- Saves to backend API or localStorage (fallback)
- Clear and download buttons

**Display:**
- Green title bar icon (`mdi-note-text`)
- Clean text area interface
- Default size: 350×400px

### Using Notes Widget

**Add/Edit Notes:**
1. Click inside notes area
2. Type your notes
3. Content auto-saves
4. "Saving..." indicator shows during save

**Auto-Save Behavior:**
- Saves 1 second after you stop typing
- Saves every 30 seconds when idle
- Shows save status in footer

### Notes Actions

**Clear Notes:**
1. Click delete button (trash icon) in footer
2. Confirm to clear all content

**Download Notes:**
1. Click download button in footer
2. Notes saved as `notes-YYYY-MM-DD.txt`
3. Downloads to browser download folder

**Save Status:**
- Shows "Saving..." with spinner while saving
- Shows "Last saved X minutes ago" when complete
- Shows error icon if save fails

### Widget Settings

**Default Configuration:**
- Position: Right side of desktop
- Size: 350px wide, 400px tall
- Min size: 300×300px
- Max size: 600×800px

## Calendar Widget

View today's events without opening the full calendar.

### Features

- Displays today's date
- Shows up to 5 upcoming events
- All-day events displayed first
- Auto-refreshes every 5 minutes
- Click event to open full calendar

**Display:**
- Purple title bar icon (`mdi-calendar`)
- Event cards with color coding
- Default size: 350×450px

### Using Calendar Widget

**View Events:**
- Today's date shown at top
- Events listed chronologically
- All-day events at top
- Timed events below with timestamps

**Event Display:**
- Event time on left
- Event title as heading
- Location with map marker icon
- Color bar shows event category

**Interact with Events:**
- Hover over event for highlight
- Click to open full calendar application

### Calendar Actions

**View More Events:**
- "View More" button if more than 5 events
- Shows count of additional events
- Click to open full calendar

**Open Full Calendar:**
- Click "View Calendar" button at bottom
- Opens calendar app in desktop window

### Widget Settings

**Default Configuration:**
- Position: Upper-right area of desktop
- Size: 350px wide, 450px tall
- Min size: 300×350px
- Max size: 500×600px
- Refresh: Every 5 minutes

**Data Source:**
- Fetches from `/calendar/?action=getEventsToday`
- Shows events for current day only

### Widget States

**Loading State:**
- Progress spinner displayed
- "Loading..." indicator

**Error State:**
- Red alert icon
- Error message
- "Retry" button

**No Events:**
- "No events today" message
- Calendar icon

## Weather Widget

Display current weather and 5-day forecast.

### Features

- Current temperature (°C)
- Weather condition description
- "Feels like" temperature
- Humidity percentage
- Wind speed (km/h)
- Atmospheric pressure (hPa)
- Visibility (km)
- 5-day forecast
- Auto-refreshes every 10 minutes

**Display:**
- Blue title bar icon (`mdi-weather-partly-cloudy`)
- Large weather icon
- Prominent temperature
- Default size: 280×400px

### Using Weather Widget

**Current Weather:**
- Large weather icon (sun, clouds, rain, etc.)
- Temperature in large numbers
- Location: "Los Santos"
- Weather condition text
- "Feels like" temperature

**Weather Details:**
- Humidity with water drop icon
- Wind speed with wind icon
- Pressure with gauge icon
- Visibility with eye icon

### Weather Forecast

**5-Day Forecast:**
- Today through 4 days ahead
- Day name (e.g., "Today", "Tomorrow", "Wed")
- Weather icon with color coding
- High and low temperatures

**Weather Icons:**
- Sun (orange) - Clear/Sunny
- Clouds (grey) - Cloudy
- Rain (blue) - Rainy
- Storm (purple) - Thunderstorms
- Snow (light blue) - Snow
- Fog (grey) - Fog/Mist

### Widget Settings

**Default Configuration:**
- Position: Top-right corner
- Size: 280px wide, 400px tall
- Min size: 320×400px
- Max size: 450×600px
- Refresh: Every 10 minutes
- Location: Los Santos (fixed)

**Data Source:**
- Fetches from `/weather/?action=getWeather`
- Returns 5-day forecast array

### Widget States

**Loading State:**
- Progress spinner
- "Loading..." indicator

**Error State:**
- Red tornado icon
- Error message
- "Retry" button
- Falls back to demo data

## Widget Data and Storage

### Data Persistence

**Notes Widget:**
- Backend: Saves to `/notes/` endpoint
- Fallback: localStorage (`widget-notes-{noteId}`)
- Auto-save: Debounced 1 second
- Backup: Every 30 seconds

**Calendar Widget:**
- Data source: `/calendar/?action=getEventsToday`
- Cache: Client-side, 5-minute refresh
- Read-only display

**Weather Widget:**
- Data source: `/weather/?action=getWeather`
- Cache: Client-side, 10-minute refresh
- Fallback: Mock data on error

### Widget Positioning

**Default Positions:**

**Notes Widget:**
- Right side of screen
- Upper area

**Calendar Widget:**
- Right side of screen
- Below weather widget

**Weather Widget:**
- Right side of screen
- Top corner

All positions are automatically saved and restored.

## Tips and Best Practices

### Productivity

**Notes Widget:**
- Use for temporary information only
- Download important notes as backup
- Clear regularly to avoid clutter

**Calendar Widget:**
- Quick glance at daily schedule
- Position where easily visible
- Use full calendar for detailed planning

**Weather Widget:**
- Check before planning outdoor activities
- Position in frequently viewed area

### Organization

**Widget Placement:**
- Place frequently used widgets higher
- Leave space between widgets
- Don't overlap widgets
- Group related widgets together

**Widget Management:**
- Close unused widgets to save space
- Re-enable as needed
- Keep only 2-3 widgets open

## Troubleshooting

### Widget Won't Open

**Solution:**
1. Check if already open
2. Refresh page (F5)
3. Clear browser cache

### Notes Not Saving

**Solution:**
1. Check internet connection
2. Check browser console for errors
3. Notes fall back to localStorage
4. Download notes as backup

### Calendar Events Not Loading

**Solution:**
1. Check internet connection
2. Click retry button
3. Refresh widget (close and reopen)

### Weather Not Updating

**Solution:**
1. Check internet connection
2. Wait for auto-refresh (10 minutes)
3. Close and reopen widget

## Limitations

The following features mentioned in documentation **do not exist** in the current codebase:

❌ **Not Available:**
- Widget settings panel (gear icon on widget)
- Multiple notes widgets with different IDs
- Notes widget rich text support (bold, italic, lists)
- Notes widget color coding
- Notes widget multiple tabs
- Calendar widget event filtering
- Calendar widget quick event creation (double-click date)
- Weather location customization (fixed to Los Santos)
- Clock widget (analog/digital)
- Clock widget timezone support
- Clock widget alarms
- Quick Actions widget
- Widget themes and colors
- Widget profiles (work, personal)
- Widget sharing
- Adding/removing widgets via right-click menu
- Widget snap-to-edges

---

## Next Steps

- [Desktop Customization](/guide/desktop-customization) - Customize your desktop
- [Desktop Interface](/guide/desktop-interface) - Learn the desktop
- [Calendar Guide](/guide/calendar) - Use the full calendar
- [Desktop Mode](/guide/desktop-mode) - Understanding desktop mode
