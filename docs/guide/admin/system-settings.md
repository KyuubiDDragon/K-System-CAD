# System Settings

Configure system-wide settings including branding, theme colors, system preferences, and more.

## Overview

System Settings allows administrators to configure application-wide settings that affect the entire system interface and behavior.

**Key Areas:**
- Basic settings (site name, logo, company info)
- Theme and design settings (comprehensive color system)
- System preferences (language, date format, session timeout)
- Branding and export settings
- Contact and support information

**Required Permission:** `ADMIN_SYSTEM`

## Access

**Desktop:** Start Menu → Administration → System Settings
**Menu:** Administration → Settings
**Route:** `/admin/settings`

## Settings Organization

Settings are organized into 5 collapsible panels:

1. Basic Settings
2. Design Settings (Theme)
3. System Settings
4. Branding & Export
5. Contact & Support

## 1. Basic Settings

### Site Information

**Configure Basic Info:**
1. Expand **Basic Settings** panel
2. Fill fields:

**Site Name** (required):
- Application display name
- Shows in interface header
- Browser tab title
- Example: "K-Systems Management"

**Site Logo** (required):
- URL to main logo image
- Header logo
- Example: `/img/logo.png`
- Preview shown below field

**Employee Navigation Label** (required):
- Custom label for employee section
- Navigation menu text
- Example: "Mitarbeiter", "Employees", "Personnel"

**Company Name** (required):
- Organization legal name
- Used in documents and emails
- Example: "Los Santos Fire Department"

## 2. Design Settings (Theme)

### Comprehensive Theme System

**Theme Controls:**
- **Dark Mode**: Toggle dark/light mode
- **Reset to Default**: Restore default color scheme
- **Enable Gradients**: Enable gradient effects

### Main Colors

**Configure Primary Colors:**

**Primary Color**:
- Main brand color
- Buttons, links, accents
- Hex input + color picker
- Default: `#3B82F6`

**Secondary Color**:
- Secondary brand color
- Complementary elements
- Default: `#343541`

**Accent Color**:
- Highlight color
- Call-to-action elements
- Default: `#10B981`

**Background Color**:
- Main background
- Page background
- Default: `#111723`

**Surface Color**:
- Cards and panels
- Elevated elements
- Default: `#111827`

### Text on Color Settings

**Configure Readable Text Colors:**

For each color, set text color that appears on it:

**Text on Primary**:
- Text shown on primary colored elements
- Preview: "Aa" sample with live colors
- Default: `#FFFFFF` (white)

**Text on Secondary**:
- Text on secondary elements
- Default: `#FFFFFF`

**Text on Accent**:
- Text on accent colored elements
- Default: `#121212` (dark)

**Text on Background**:
- Main text color
- Body text throughout app
- Default: `#FFFFFF`

**Text on Surface**:
- Text on cards/panels
- Default: `#FFFFFF`

**Live Preview**:
- Each field shows "Aa" sample
- Background: The color it's for
- Foreground: The text color you're setting
- Updates as you type

### Color Picker

Each color field has:
- Hex code input (e.g., #FF0000)
- Color picker button
- Live preview of current color
- Validation for valid hex format

### Preview Button

**Preview Theme:**
1. Click **Preview** button
2. Design preview dialog opens
3. Shows mockup with:
   - Header with logo and site name
   - Navigation menu
   - Sidebar
   - Content area with card
   - Primary and secondary buttons
   - Form inputs
4. All styled with your color choices
5. Close preview when done

**Preview Features:**
- Real-time color application
- See colors in context
- Test before saving
- Verify text readability

## 3. System Settings

### System Preferences

**Configure System Behavior:**

**Default Language**:
- Select: de, en, fr, es, it
- System-wide default
- New users get this language

**Date Format**:
- Multiple format options
- DD.MM.YYYY, MM/DD/YYYY, YYYY-MM-DD, etc.
- Affects date displays throughout system

**Default Start Page**:
- Page shown after login
- Options:
  - Dashboard
  - Profile
  - Messages
  - Calendar
  - Documents

**Session Timeout**:
- Idle timeout in minutes
- Slider: 5-240 minutes (step 5)
- User logged out after inactivity
- Default: 60 minutes

**Enable Desktop Notifications**:
- Browser notifications
- Toggle on/off
- Requires user permission

**Enable User Activity Tracking**:
- Track user actions
- System analytics
- Toggle on/off

## 4. Branding & Export

### Document Branding

**PDF Header Logo**:
- URL to logo for PDF exports
- Appears in document headers
- Preview shown
- Example: `/img/logo_header.png`

**PDF Footer Text**:
- Text in PDF footers
- Company info, copyright, etc.
- Example: "© 2024 Organization Name"

### Export Settings

**Export Date Format**:
- Date format in exported files
- Dropdown selection
- Independent from display format

**Default Export Format**:
- Select default format
- Options: PDF, XLSX, CSV, JSON
- Used when export format not specified

## 5. Contact & Support

### Support Information

**Support Email**:
- Email for support requests
- Email validation
- Example: support@organization.com

**Support Phone**:
- Phone number for support
- Optional
- Example: +1 (555) 123-4567

**Help Page Content**:
- Textarea field
- Auto-grows with content
- Markdown or plain text
- Shown in help section
- Instructions, FAQ, etc.

## Saving Settings

### Save Theme

**Apply Changes:**
1. Make desired changes in any panel
2. Click **Save** button
3. Theme applies immediately to:
   - Current interface
   - DOM styling
   - localStorage (persists across sessions)
4. Settings saved to database
5. All users see new theme

**Theme Application:**
- Applies instantly without reload
- CSS variables updated
- Vuetify theme updated
- Cache invalidated with force refresh

**Save Button:**
- Always available (not disabled)
- Saves all panels at once
- Success notification shown
- Errors displayed if any

## Preview Mode

### Preview Design

**Test Before Saving:**
1. Configure colors
2. Click **Preview** button
3. Dialog shows mockup interface:
   - Header section
   - Navigation items
   - Sidebar
   - Main content card
   - Sample buttons (primary/secondary)
   - Form input examples
4. Verify colors look good
5. Check text readability
6. Close preview
7. Adjust if needed
8. Save when satisfied

**Preview Dialog:**
- Full-height dialog
- Styled with current settings
- Interactive preview
- Close button to exit

## Default Color Scheme

### Default Values

**Default Dark Theme:**
```
Primary:     #3B82F6 (blue)
Secondary:   #343541 (dark gray)
Accent:      #10B981 (green)
Background:  #111723 (very dark blue)
Surface:     #111827 (dark gray)

Text Colors:
On Primary:     #FFFFFF (white)
On Secondary:   #FFFFFF (white)
On Accent:      #121212 (black)
On Background:  #FFFFFF (white)
On Surface:     #FFFFFF (white)

Other:
Dark Mode:         true
Enable Gradients:  true
Border Radius:     8px
```

**Reset to Default:**
- Click **Reset to Default Colors**
- Restores above values
- Confirmation prompt
- Can save or discard

## API Reference

### Settings Endpoints

```http
GET /admin/settings?action=getGlobalSettings
Returns: All system settings {
  siteName,
  siteLogo,
  employeeNavigation,
  companyName,
  darkMode,
  primaryColor,
  secondaryColor,
  accentColor,
  backgroundColor,
  surfaceColor,
  onPrimaryColor,
  onSecondaryColor,
  onAccentColor,
  onBackgroundColor,
  onSurfaceColor,
  enableGradients,
  defaultLanguage,
  dateFormat,
  defaultStartPage,
  sessionTimeout,
  enableNotifications,
  enableUserTracking,
  pdfHeaderLogo,
  pdfFooterText,
  exportDateFormat,
  defaultExportFormat,
  supportEmail,
  supportPhone,
  helpPageContent
}

POST /admin/settings?action=updateSettings
Body: All settings fields
Returns: Success message
```

## TypeScript Interface

```typescript
interface Settings {
  // Basic
  siteName: string;
  siteLogo: string;
  employeeNavigation: string;
  companyName: string;

  // Theme
  darkMode: boolean;
  primaryColor: string;
  secondaryColor: string;
  accentColor: string;
  backgroundColor: string;
  surfaceColor: string;
  tertiaryColor: string;
  infoColor: string;
  successColor: string;
  warningColor: string;
  errorColor: string;

  // Text on colors
  onPrimaryColor: string;
  onSecondaryColor: string;
  onAccentColor: string;
  onBackgroundColor: string;
  onSurfaceColor: string;
  onSuccessColor: string;
  onInfoColor: string;
  onWarningColor: string;
  onErrorColor: string;

  // Other theme
  borderRadius: number;
  enableGradients: boolean;

  // System
  defaultLanguage: string;
  dateFormat: string;
  defaultStartPage: string;
  sessionTimeout: number;
  enableNotifications: boolean;
  enableUserTracking: boolean;

  // Branding & Export
  pdfHeaderLogo: string;
  pdfFooterText: string;
  exportDateFormat: string;
  defaultExportFormat: string;

  // Contact & Support
  supportEmail: string;
  supportPhone: string;
  helpPageContent: string;
}
```

## Best Practices

**Do:**
✅ Test colors with preview before saving
✅ Ensure text readable on backgrounds
✅ Use consistent color scheme
✅ Set reasonable session timeout
✅ Configure support contact info
✅ Test theme in both light and dark mode (if supported)
✅ Use hex color codes (#RRGGBB format)
✅ Preview on different screen sizes

**Don't:**
❌ Use poor contrast color combinations
❌ Set very short session timeouts
❌ Leave support info blank
❌ Use invalid hex colors
❌ Forget to save after changes
❌ Use extreme colors that hurt readability
❌ Change too many settings at once without testing

## Troubleshooting

### Colors Not Applying

**Solutions:**
1. Click Save button
2. Clear browser cache (Ctrl+F5)
3. Logout and login again
4. Check valid hex format
5. Verify changes saved successfully

### Preview Not Showing Colors

**Check:**
1. Verify colors entered correctly
2. Check hex format valid
3. Close and reopen preview
4. Try different colors
5. Check browser console for errors

### Settings Not Saving

**Solutions:**
1. Check all required fields filled
2. Verify field validation passed
3. Check network connection
4. Review browser console for errors
5. Try saving individual panels

### Theme Looks Wrong

**Solutions:**
1. Click "Reset to Default Colors"
2. Start with default theme
3. Adjust one color at a time
4. Use preview frequently
5. Test text readability
6. Check "text on color" values

## Limitations

The following features mentioned in some documentation **do not exist** in the current codebase:

❌ **Not Available:**
- Tertiary, info, success, warning, error colors (defined but not exposed in UI)
- Border radius customization (defined but not exposed in UI)
- Font family selection
- Font size customization
- Multiple theme presets/profiles
- Theme export/import
- A/B testing for themes
- Light mode theme configuration (only dark mode available)
- Per-user theme preferences
- Automatic theme switching (time-based)
- Custom CSS injection
- Advanced gradient controls
- Animation speed settings
- Email server configuration (SMTP settings not in this UI)
- API rate limiting configuration
- Backup/restore settings
- Database optimization controls
- Cache management
- Log viewing
- System monitoring
- Update management

---

## Next Steps

- [Authority Branding](/guide/admin/authority-branding) - Authority-specific branding
- [User Management](/guide/admin/users) - Manage system users
- [Desktop Interface](/guide/desktop-interface) - See theme in action
