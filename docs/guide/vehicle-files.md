# Vehicle Files

Basic vehicle management with inline detail view showing vehicle information, owners/drivers, and descriptions.

## Overview

Features:
- Vehicle information management (brand, model, numberplate, color)
- Owner and driver person assignments
- Registration date tracking
- Stolen/wanted status flags with visual chips
- Inline 3-tab detail view (click numberplate to open)
- Search functionality across vehicle fields
- Desktop window support with deep linking
- Authority-specific data isolation

**Required Permission:** `READ_VEHICLE_FILE` (view), `WRITE_VEHICLE_FILE` (create/edit), `DELETE_VEHICLE_FILE` (delete)

## Access

**Desktop:** Double-click **Vehicle Files** icon
**Menu:** Start Menu → Files → Vehicle Files
**Route:** `/vehicleFile`
**Deep Link:** `/vehicleFile?id={id}` (opens specific vehicle detail view)

## Interface Layout

### Vehicle List Table

**Table Columns:**
- **Numberplate** - License plate (clickable to show inline detail view)
- **Brand** - Vehicle manufacturer
- **Model** - Vehicle model
- **Color** - Vehicle color
- **Actions** - Edit and Delete buttons

**Action Button:**
- **+ New Vehicle** - Create new vehicle file

**Search:**
- Real-time search as you type
- Searches: numberplate, brand, model, color

### Inline Detail View

**Opens when:** Click on numberplate in table, or deep link with `?id={id}`

**Layout:**
- Replaces main table
- Back button at top to return to list
- Card with 3 tabs
- Vehicle brand/model shown in header
- Status chips displayed in header (Stolen/Wanted if applicable)

**Tab 1: Vehicle Info**
- Brand
- Model
- Numberplate
- Color
- Stolen (checkbox)
- Wanted (checkbox)
- Registered (date)

**Tab 2: Owners & Drivers**
- **Owners Section:**
  - Data table showing persons assigned as owners
  - Columns: Name, Phone Number, Email
  - Shows "No owners" message if empty
- **Drivers Section:**
  - Data table showing persons assigned as drivers
  - Columns: Name, Phone Number, Email
  - Shows "No drivers" message if empty

**Tab 3: Description**
- HTML content rendered
- Shows "No description" message if empty
- Read-only display

**Navigation:**
- Click **Back to List** button to return to vehicle table

## Creating Vehicles

### New Vehicle

1. Click **+ New Vehicle** button
2. Add dialog component loads (authority-specific)
3. Fill in vehicle information
4. Assign owners and drivers (person IDs)
5. Add description text (supports HTML)
6. Click **Save**
7. Vehicle added to list

## Viewing Vehicles

### View Vehicle Details (Inline)

1. Click on vehicle **numberplate** in table
2. Inline detail view opens with 3 tabs
3. Tab 1: View vehicle info
4. Tab 2: View owners and drivers tables with person data
5. Tab 3: View description HTML content
6. Click **Back to List** to return to table

**Deep Linking:**
- Opening `/vehicleFile?id=123` automatically shows inline detail for vehicle 123
- Works in desktop windows

## Editing Vehicles

### Edit Vehicle

1. Click **Edit** icon (pencil) on vehicle row
2. Edit dialog component loads
3. Modify vehicle information
4. Update owners/drivers assignments
5. Edit description text
6. Click **Save**
7. Changes applied

## Deleting Vehicles

### Delete Vehicle

1. Click **Delete** icon (trash) on vehicle row
2. Confirm deletion in dialog
3. Vehicle removed from database

**Warning:** Deletion is permanent and cannot be undone.

## Search Functionality

**Search Behavior:**
- Located in toolbar
- Filters vehicles in real-time
- Searches across:
  - Numberplate
  - Brand
  - Model
  - Color

## Data Model

**VehicleFile Interface:**
```typescript
interface VehicleFile {
  id: number;
  owners?: number[];        // Multiple owners allowed
  drivers?: number[];       // Multiple drivers allowed
  brand: string;
  model: string;
  numberplate: string;
  color: string;
  stolen: boolean;
  wanted: boolean;
  registered?: string;      // Date (optional)
  text: string;             // HTML description
  is_deleted: boolean;
}
```

## API Reference

```http
GET /vehiclefile/?action=getVehicles
Returns: VehicleFile[]

GET /vehiclefile/?action=getPersons
Returns: PersonFile[]

POST /vehiclefile/?action=deleteVehicle
Body: { id }
Returns: Success message
```

## Desktop Window Support

**Deep Linking:**
- `route.query.id` - From URL query parameter
- `props.id` - From desktop window props
- `props.meta.id` or `props.meta.vehicleId` - From window metadata

## Best Practices

**Do:**
✅ Fill in all required fields (brand, model, numberplate, color)
✅ Assign owners and drivers from person files
✅ Mark stolen/wanted status accurately
✅ Use Back button to return to list

**Don't:**
❌ Create duplicate entries
❌ Leave numberplate empty
❌ Delete unnecessarily

## Troubleshooting

### Inline Detail View Not Showing

**Solutions:**
1. Check vehicle ID exists
2. Verify persons API is working
3. Refresh page and try again

### Owners/Drivers Not Showing

**Solutions:**
1. Verify persons API returns data: `/vehiclefile/?action=getPersons`
2. Check vehicle.owners and vehicle.drivers arrays have valid IDs
3. Refresh page to reload persons data

### Deep Link Not Working

**Solutions:**
1. Verify vehicle ID exists in database
2. Check vehicle belongs to your authority
3. Try accessing via table click instead

## Limitations

The following features **do not exist**:

❌ **Not Available:**
- Filter by stolen/wanted status
- Stolen/Wanted columns in main table
- Bulk operations
- Export to Excel/CSV/PDF
- Document attachments
- Service history
- Maintenance records
- Insurance tracking
- Accident reports
- GPS/location tracking
- Mileage tracking
- Photo management
- VIN tracking
- Vehicle specifications
- Cost tracking
- Inspection management
- Edit mode in inline detail view (view-only)

---

## Next Steps

- [Person Files](/guide/person-files) - Person contacts for owners/drivers
- [Apartment Files](/guide/apartment-files) - Manage apartment records
- [Companies](/guide/companies) - Company management
