# Map & Location Markers

Interactive map system for managing location markers with categories and custom icons using Leaflet.js.

## Overview

Features:
- Interactive Leaflet map with zoom/pan
- Custom markers with coordinates
- Marker categories with custom icons
- Three map styles (Atlas, Satellite, Grid)
- Category filtering
- Search functionality
- Double-click to add markers
- Click marker for popup details
- Sidebar with category organization
- Authority-specific data isolation

**Required Permission:** `READ_MAP` (view), `WRITE_MAP` (create/edit), `DELETE_MAP` (delete)

## Accessing Map

**Desktop:** Double-click **Map** icon
**Menu:** Start Menu → Operations → Map
**Route:** `/map`

## Interface Layout

### Left Side - Map Display

**Map Styles (Top Controls):**
- **Atlas** (mdi-map) - Standard street map
- **Satellite** (mdi-satellite-variant) - Aerial imagery
- **Grid** (mdi-grid) - Grid-based view

**Map Instance:**
- Leaflet map component
- Default zoom: 4
- Center: Configurable center point
- CRS: Custom coordinate system (configurable)
- Double-click to add marker

**Markers:**
- Displayed as custom icons based on category
- Click to open popup with details
- Popup shows:
  - Marker name
  - CEO (if category != 1)
  - Phone number (if category != 1)
  - Location (always shown)
  - Edit button (if WRITE permission)
  - Delete button (if DELETE permission)

### Right Side - Categories & Markers

**Sidebar Header:**
- Locations title with map-marker icon

**Filters:**
- **Category Filter**: Multi-select dropdown
  - Chips for selected categories
  - Closable chips
  - Filters visible markers

- **Search Field**: Text search
  - Magnify icon
  - Filters markers by name

**Location List:**
- Expansion panels grouped by category
- Each category panel shows:
  - Folder icon + category name
  - Count badge (number of markers in category)
  - List of markers when expanded:
    - Map marker icon
    - Marker name (title)
    - Location (subtitle)
    - Click to focus on marker (zoom + center)

**Empty State:**
- "No locations in category" when category has no matching markers

## Map Styles

### Changing Map Style

Click one of three style buttons:

**Atlas (styleAtlas):**
- Standard street map
- Roads, labels, terrain

**Satellite (styleSatelite):**
- Aerial/satellite imagery
- Real imagery view

**Grid (styleGrid):**
- Grid-based map
- Custom grid layout

**Active Style:**
- Highlighted in primary color
- Tonal variant for active button

## Creating Markers

### Add Marker by Double-Click

1. Double-click on map location
2. Dialog opens with form
3. Coordinates auto-filled from click position

**Form Fields:**

**Name*** (required)
- Text field
- Marker display name
- Icon: mdi-tag

**CEO** (optional)
- Text field
- CEO or contact person name
- Icon: mdi-account-tie
- Only shown in popup if category != 1

**Phone Number** (optional)
- Text field
- Contact phone number
- Icon: mdi-phone
- Only shown in popup if category != 1

**Location** (optional)
- Text field
- Address or location description
- Icon: mdi-map-marker

**Category*** (required)
- Dropdown select
- Choose from available categories
- Icon: mdi-shape
- Determines marker icon on map

4. Click **Save** to create marker
5. Marker appears on map immediately
6. Cancel to discard

**Validation:**
- Name is required
- Category is required
- Save button disabled until both filled

## Editing Markers

### Edit Existing Marker

1. Click marker on map
2. Popup opens
3. Click **Edit** button (if WRITE permission)
4. Edit dialog opens with current data
5. Modify any fields
6. Click **Save** to update
7. Or **Cancel** to discard

**Edit Form:**
- Same fields as Add Marker
- Pre-filled with current values
- Coordinates remain unchanged (not editable)

## Deleting Markers

### Delete Marker

1. Click marker on map
2. Popup opens
3. Click **Delete** button (if DELETE permission)
4. Confirmation dialog appears
5. Confirm to delete
6. Marker removed from map and database

**Warning:** Deletion is permanent.

## Focusing on Markers

### Zoom to Marker

**From Sidebar:**
1. Expand category panel
2. Click marker in list
3. Map centers on marker
4. Map zooms to marker location
5. Popup opens automatically

**Use Case:**
- Quick navigation to specific locations
- Find marker without panning map

## Category Filtering

### Filter by Categories

1. Click category dropdown in sidebar
2. Select one or more categories
3. Only markers from selected categories shown
4. Chips display selected categories
5. Click X on chip to remove filter
6. Clear all to show all markers

**Combined with Search:**
- Category filter AND search both apply
- Only markers matching both shown

## Search

### Search Markers

1. Type in search field
2. Markers filtered by name in real-time
3. Case-insensitive matching
4. Categories without matching markers hide

**Search Behavior:**
- Filters marker list in sidebar
- Also filters visible markers on map
- Clear search to see all

## Data Model

**Marker:**
```typescript
interface Marker {
  id: number;
  name: string;
  x_coordinate: number; // Longitude
  y_coordinate: number; // Latitude
  location: string;
  ceo?: string;
  phonenumber?: string;
  category_id: number;
  category_name?: string; // Joined from category
  category_icon?: string; // Joined from category
  authority_id: number;
}
```

**Category:**
```typescript
interface Category {
  id: number;
  name: string;
  icon: string; // Icon name/path for marker display
  authority_id: number;
}
```

**Click Position (for new marker):**
```typescript
{
  x_coordinate: number; // From map click event latlng.lng
  y_coordinate: number; // From map click event latlng.lat
}
```

## API Integration

**Base Path:** `/map/`

**Get Markers:**
```
GET /map/?action=getMarkers
Response: { data: Marker[] }
```

**Get Categories:**
```
GET /map/?action=getCategories
Response: { data: Category[] }
```

**Add Marker:**
```
POST /map/?action=addMarker
Payload: {
  name: string;
  x_coordinate: number;
  y_coordinate: number;
  location: string;
  ceo?: string;
  phonenumber?: string;
  category_id: number;
}
```

**Update Marker:**
```
POST /map/?action=updateMarker
Payload: {
  id: number;
  name: string;
  location: string;
  ceo?: string;
  phonenumber?: string;
  category_id: number;
}
```

**Delete Marker:**
```
POST /map/?action=deleteMarker
Payload: { id: number }
```

**Error Handling:**
- Toast notifications for errors
- Console logging for debugging
- Loading states during operations

## Admin Configuration

**Category Management:**
**Route:** `/admin/map` (if exists)

**Create Categories:**
- Define category names
- Assign custom icons
- Icons determine marker appearance on map

**Custom Icons:**
- Set icon per category
- Icon library (mdi icons or custom)
- Color configuration

## Multi-Tenant Security

**Authority Isolation:**
- All markers scoped to `authority_id`
- All categories scoped to `authority_id`
- Users only see data from their organization
- Backend filters by authority automatically
- Cross-organization access prevented

## Desktop Window Mode

**Props Support:**
```typescript
interface Props {
  meta?: Record<string, any>;
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
}
```

**Permission Resolution:**
- Checks props, route.meta, and permissions
- Edit/Delete buttons show based on permissions

## Leaflet Integration

**Library:** Leaflet.js (vue-leaflet)

**Components Used:**
- `<l-map>` - Main map instance
- `<l-marker>` - Individual markers
- `<l-popup>` - Info popups on markers

**Map Options:**
```javascript
{
  crs: configurable, // Coordinate reference system
  zoom: 4,
  center: [lat, lng]
}
```

**Custom Icons:**
```javascript
customIcon(iconName) {
  // Returns Leaflet icon based on category icon
  // Icon library integration
}
```

**Events:**
- `@dblclick` on map → Open add marker dialog
- Click marker → Open popup
- Click list item → Focus on marker

## Styling & Theme

**Dark Theme:**
- Grey-darken-3 backgrounds for dialogs
- Primary color for active elements
- Tonal buttons for map styles

**Map Layout:**
- Split layout: Map left, Sidebar right
- Responsive sizing
- Fixed height map container

**Sidebar:**
- Scrollable content
- Expansion panels for categories
- Compact list density
- Chip badges for counts

## Limitations

**What's NOT Available:**

### GPS & Location Features
- ❌ Real-time location tracking
- ❌ GPS device integration
- ❌ "My Location" button
- ❌ Live position updates
- ❌ Mobile GPS tracking
- ❌ Location history/trails
- ❌ Speed/heading data

### Geofencing
- ❌ Geofence creation
- ❌ Zone boundaries
- ❌ Area monitoring
- ❌ Entry/exit alerts
- ❌ Geofence reports
- ❌ Virtual boundaries

### Routing & Navigation
- ❌ Route planning
- ❌ Turn-by-turn navigation
- ❌ Distance calculation between points
- ❌ Travel time estimates
- ❌ Route optimization
- ❌ Waypoints
- ❌ Alternative routes

### Measurement Tools
- ❌ Distance measurement tool
- ❌ Area calculation
- ❌ Perimeter measurement
- ❌ Radius circles

### Drawing Tools
- ❌ Draw shapes (polygons, circles, rectangles)
- ❌ Draw lines
- ❌ Annotations
- ❌ Custom overlays

### Address Features
- ❌ Address geocoding (address → coordinates)
- ❌ Reverse geocoding (coordinates → address)
- ❌ Address autocomplete
- ❌ Place search

### Advanced Map Features
- ❌ Clustering (many markers grouped)
- ❌ Heat maps
- ❌ Layer controls (multiple data layers)
- ❌ Custom tile layers
- ❌ WMS/WFS layers
- ❌ Offline maps
- ❌ Map printing
- ❌ Screenshot/export

### Marker Features
- ❌ Drag markers to reposition
- ❌ Marker descriptions (rich text)
- ❌ Photo attachments to markers
- ❌ Multiple photos per marker
- ❌ File attachments
- ❌ Marker history/versions
- ❌ Related items (employees, reports, assets)
- ❌ Custom marker colors (only category icon)

### Filtering & Search
- ❌ Date range filters
- ❌ Assigned user filter
- ❌ Status filter
- ❌ Advanced search
- ❌ Saved searches
- ❌ Sort markers (only alphabetical in list)

### Sharing & Collaboration
- ❌ Location sharing
- ❌ Share map links
- ❌ Embed maps
- ❌ Public/private markers
- ❌ Collaborative editing
- ❌ Comments on markers

### Reporting & Export
- ❌ Export markers to CSV/Excel
- ❌ Export map as image
- ❌ Print maps
- ❌ Location reports
- ❌ Analytics
- ❌ Usage statistics

### Integration
- ❌ Import locations from file
- ❌ Bulk marker upload
- ❌ Integration with other modules (vehicles, employees)
- ❌ API for external systems

### UI Limitations
- ❌ Full-screen map mode
- ❌ Street view
- ❌ 3D view
- ❌ Basemap selector (only 3 preset styles)
- ❌ Coordinates display on map
- ❌ Scale bar
- ❌ North arrow
- ❌ Map legend

**Current Reality:**
This is a **simple marker management system** on an interactive map. It allows placing, viewing, editing, and organizing location markers by category. It is not a comprehensive GIS system, GPS tracking platform, or navigation solution.

## Use Cases

**Location Markers:**
- Mark important locations (offices, sites, facilities)
- Store contact information per location
- Categorize locations by type
- Visual map overview

**Organization:**
- Group locations by categories
- Filter by category or search
- Quick navigation to locations
- Visual location directory

**Basic Reference:**
- Store coordinates for locations
- Reference map for planning
- Location lookup

## Best Practices

**Creating Markers:**
1. Use clear, descriptive names
2. Always assign category for organization
3. Include location description for clarity
4. Add CEO/phone for business locations

**Categories:**
1. Create logical category structure
2. Use meaningful category names
3. Assign distinct icons per category
4. Keep category count manageable

**Map Management:**
1. Choose appropriate map style for use case
2. Use category filters to reduce clutter
3. Search for specific locations
4. Click list items for quick navigation

**Data Quality:**
1. Ensure accurate coordinates
2. Update location info when changed
3. Remove outdated markers
4. Keep CEO/phone current

## Troubleshooting

### Can't Add Marker

**Problem:** Double-click doesn't open dialog

**Solutions:**
1. Check `WRITE_MAP` permission
2. Try double-clicking again (timing sensitive)
3. Ensure map is loaded (not still loading)
4. Refresh page

### Marker Not Saving

**Problem:** Save doesn't persist marker

**Solutions:**
1. Check name and category filled (required)
2. Verify internet connection
3. Check browser console for API errors
4. Ensure backend is running

### Map Not Loading

**Problem:** Blank map or tiles not loading

**Solutions:**
1. Check internet connection (tiles load from server)
2. Verify map style configuration
3. Check browser console for errors
4. Try different map style
5. Refresh page

### Markers Not Showing

**Problem:** Markers exist but not visible

**Solutions:**
1. Check category filter (might be filtering out)
2. Clear search field
3. Verify coordinates are valid
4. Check zoom level (zoom out to see more)
5. Refresh page

### Can't Edit/Delete Marker

**Problem:** Buttons missing in popup

**Solutions:**
1. Check `WRITE_MAP` and `DELETE_MAP` permissions
2. Verify marker belongs to your authority
3. Refresh page

### Search Not Working

**Problem:** Search doesn't filter

**Solutions:**
1. Check spelling of marker name
2. Try partial name
3. Clear search and try again
4. Verify markers exist

## Related Documentation

- [🚗 Vehicle Files](/guide/vehicle-files) - Vehicle tracking
- [🏢 Companies](/guide/companies) - Company locations
- [👤 Person Files](/guide/person-files) - Person contacts

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
