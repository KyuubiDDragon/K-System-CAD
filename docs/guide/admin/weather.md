# Weather Management

Manage weather forecast data displayed in K-Systems weather widgets and views.

## Overview

Weather Management allows administrators to manually enter weather forecast data that appears throughout the system, including the weather widget on the desktop and in the weather view.

**Important:** This is **manual weather entry**, not automatic API integration. Weather data must be entered by hand.

**Key Features:**
- Add weather forecast entries
- Edit existing forecasts
- Delete forecast entries
- 10 weather icon options
- Color-coded temperature displays
- Weather warnings

**Required Permission:** `ADMIN_WEATHER` or `ADMIN_SYSTEM`

## Access

**Desktop:** Start Menu → Administration → Weather Management
**Menu:** Administration → Weather
**Route:** `/admin/weather`

## Weather Forecast List

### Interface

**Weather Table Columns:**
- **Date**: Calendar icon + date (DD.MM.YYYY format)
- **Day**: Weekday name (chip display)
- **Min Temperature**: Minimum temperature in °C
  - Blue if below 0°C
  - Orange if above 25°C
- **Max Temperature**: Maximum temperature in °C
  - Blue if below 0°C
  - Orange if above 25°C
  - Deep orange if above 30°C
- **Weather Icon**: Visual weather condition
  - Colored by condition type
  - Tooltip shows condition name
- **Humidity**: Percentage
  - Blue icon if >80%
  - Amber icon if <30%
- **Wind Speed**: Speed in km/h
  - Red if >40 km/h
  - Amber if >25 km/h
- **Warning**: Weather warning text (if present)
  - Chip with alert icon
- **Actions**: Edit, Delete

**Sorting:**
- Sorted by date (descending)
- Most recent date first

## Adding Weather Forecasts

### Create Weather Entry

**Add Forecast:**
1. Click **+ Add Weather** button
2. Fill weather information:

**Required Fields:**

**Date** (required):
- Date picker
- Select forecast date
- Format: YYYY-MM-DD

**Weekday** (required):
- Select from dropdown:
  - Monday (Montag)
  - Tuesday (Dienstag)
  - Wednesday (Mittwoch)
  - Thursday (Donnerstag)
  - Friday (Freitag)
  - Saturday (Samstag)
  - Sunday (Sonntag)

**Weather Icon** (required):
- Visual grid selector
- Click icon to select
- 10 icon options:
  1. **Sunny** (mdi-weather-sunny) - Orange/amber
  2. **Partly Cloudy** (mdi-weather-partly-cloudy) - Light blue
  3. **Rainy** (mdi-weather-rainy) - Blue
  4. **Cloudy** (mdi-weather-cloudy) - Gray
  5. **Thunderstorm** (mdi-weather-lightning) - Purple
  6. **Thunderstorm with Rain** (mdi-weather-lightning-rainy) - Deep purple
  7. **Windy** (mdi-weather-windy) - Blue-gray
  8. **Foggy** (mdi-weather-fog) - Light gray
  9. **Snow** (mdi-weather-snowy) - Cyan (light)
  10. **Sleet** (mdi-weather-snowy-rainy) - Cyan

**Min Temperature** (required):
- Number input
- In degrees Celsius (°C)
- Can be negative
- Example: -5, 10, 18

**Max Temperature** (required):
- Number input
- In degrees Celsius (°C)
- Should be >= min temperature
- Example: 5, 22, 28

**Humidity** (required):
- Number input
- Percentage (%)
- 0-100
- Example: 45, 65, 80

**Wind Speed** (required):
- Number input
- In kilometers per hour (km/h)
- Example: 10, 25, 45

**Weather Warning** (optional):
- Text field
- Warning message or alert
- Example: "Strong winds expected"
- Shows with alert icon if present
- Can be left empty

3. Click **Save**
4. Weather entry created

## Editing Weather Forecasts

### Update Weather Entry

**Edit Forecast:**
1. Click **Edit** button on weather row
2. Edit weather dialog opens
3. Modify any fields:
   - Date
   - Weekday
   - Weather icon
   - Temperatures
   - Humidity
   - Wind speed
   - Warning text
4. Click **Save**
5. Changes applied immediately

**All fields editable** when editing existing forecast.

## Deleting Weather Forecasts

### Remove Weather Entry

**Delete Forecast:**
1. Click **Delete** button on weather row
2. Confirmation dialog shows:
   - Weather icon with condition name
   - Temperature range
   - Humidity
   - Wind speed
   - Warning: "Diese Wettervorhersage wird unwiderruflich gelöscht"
3. Click **Delete** to confirm
4. Weather entry deleted permanently

**Warning:** Deletion is permanent and cannot be undone.

## Weather Icons

### Icon Options

**Available Weather Conditions:**

1. **Sunny** (☀️)
   - Clear skies
   - Full sun
   - Color: Amber/orange

2. **Partly Cloudy** (⛅)
   - Some clouds
   - Sun visible
   - Color: Light blue

3. **Rainy** (🌧️)
   - Rain
   - Wet conditions
   - Color: Blue

4. **Cloudy** (☁️)
   - Overcast
   - Full cloud cover
   - Color: Gray

5. **Thunderstorm** (⛈️)
   - Lightning
   - Severe weather
   - Color: Purple

6. **Thunderstorm with Rain** (⛈️🌧️)
   - Rain and lightning
   - Severe wet weather
   - Color: Deep purple

7. **Windy** (💨)
   - High winds
   - Breezy conditions
   - Color: Blue-gray

8. **Foggy** (🌫️)
   - Low visibility
   - Fog/mist
   - Color: Light gray

9. **Snow** (❄️)
   - Snowing
   - Snow accumulation
   - Color: Light cyan

10. **Sleet** (🌨️)
    - Mixed precipitation
    - Snow and rain
    - Color: Cyan

## Temperature Color Coding

### Automatic Color Coding

**Minimum Temperature:**
- **Blue**: < 0°C (freezing)
- **Orange**: > 25°C (warm)
- **Default**: Normal range

**Maximum Temperature:**
- **Blue**: < 0°C (freezing)
- **Deep Orange**: > 30°C (hot)
- **Orange**: > 25°C (warm)
- **Default**: Normal range

**Humidity:**
- **Blue icon**: > 80% (high humidity)
- **Amber icon**: < 30% (low humidity)
- **Default**: Normal range

**Wind Speed:**
- **Red**: > 40 km/h (strong winds)
- **Amber**: > 25 km/h (moderate winds)
- **Default**: Light winds

Colors apply automatically based on values entered.

## How Weather Data is Used

### System Integration

**Weather Widget:**
- Desktop weather widget displays this data
- Current day and upcoming days shown
- Icons and temperatures displayed
- Updates when new data added

**Weather View:**
- Full weather forecast view
- Shows all entered forecast data
- Accessible to all users
- Read-only for non-admin users

**Data Source:**
- Manual entry only
- No automatic API integration
- No live weather updates
- Administrators must maintain data

## API Reference

### Weather Endpoints

```http
GET /admin/weather?action=getWeather
Returns: Array of all weather forecast entries

POST /admin/weather?action=insertWeather
Body: {
  date,
  day,
  min_temp,
  max_temp,
  icon,
  humidity,
  wind_speed,
  warning?
}
Returns: Success message

POST /admin/weather?action=updateWeather
Body: {
  id,
  date,
  day,
  min_temp,
  max_temp,
  icon,
  humidity,
  wind_speed,
  warning?
}
Returns: Success message

POST /admin/weather?action=deleteWeather
Body: { id }
Returns: Success message
```

## TypeScript Interface

```typescript
interface Weather {
  id: number;
  date: string; // YYYY-MM-DD format
  day: string;
  min_temp: number;
  max_temp: number;
  icon: string; // MDI icon name
  humidity: number;
  warning: string | null;
  wind_speed: number;
}

interface IconOption {
  name: string;
  icon: string; // MDI icon name
}
```

## Best Practices

**Do:**
✅ Enter forecasts for upcoming week
✅ Update forecasts regularly
✅ Use appropriate weather icons
✅ Add warnings for severe weather
✅ Set realistic temperature ranges
✅ Keep humidity between 0-100%
✅ Delete old forecast entries

**Don't:**
❌ Leave old forecasts without cleaning up
❌ Enter unrealistic values
❌ Forget to set all required fields
❌ Use wrong weather icons
❌ Set max temp lower than min temp
❌ Leave data unchanged for long periods

## Troubleshooting

### Weather Not Showing in Widget

**Solutions:**
1. Verify forecast entry exists for current/upcoming dates
2. Check dates are correct format
3. Refresh weather widget
4. Check weather widget is enabled
5. Verify user has permission to view weather

### Can't Save Weather Entry

**Check:**
1. All required fields filled
2. Date format correct
3. Numbers in valid range
4. Weather icon selected
5. Max temp >= min temp

### Wrong Icon Showing

**Solutions:**
1. Edit weather entry
2. Select correct icon from grid
3. Save changes
4. Widget should update

### Colors Not Showing

**Check:**
1. Values entered trigger color thresholds
2. Temperature, humidity, wind speed values
3. Colors apply automatically based on values
4. No manual color selection needed

## Limitations

The following features mentioned in some documentation **do not exist** in the current codebase:

❌ **Not Available:**
- Automatic weather API integration (OpenWeather, Weather.gov, etc.)
- Weather API key configuration
- Automatic weather updates
- Location-based weather
- Multiple location weather
- Weather radar/maps
- Satellite imagery
- Hourly forecasts (only daily)
- Extended forecasts beyond manual entry
- Weather alerts from external sources
- Historical weather data
- Weather statistics/analytics
- Bulk import from CSV/JSON
- Export functionality
- Weather widget customization in admin UI
- Automatic cleanup of old entries
- Weather data validation against real weather
- Atmospheric pressure data
- UV index
- Visibility data
- Precipitation probability
- Wind direction
- "Feels like" temperature
- Sunrise/sunset times

---

## Next Steps

- [Desktop Widgets](/guide/desktop-widgets) - See how weather appears
- [System Settings](/guide/admin/system-settings) - Configure system settings
- [Desktop Interface](/guide/desktop-interface) - Desktop weather widget
