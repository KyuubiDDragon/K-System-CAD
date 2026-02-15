# Authority Branding

Configure visual branding and customization for your authority including logos, colors, and application settings.

## Overview

Authority Branding allows each authority (organization) to customize their K-Systems appearance with custom logos, color schemes, and application settings.

**Customization Options:**
- Logo upload
- Primary and secondary color customization
- Application title
- Default desktop background upload

**Required Permission:** `ADMIN_AUTHORITY`

## Access

**Desktop:** Start Menu → Administration → Authority Branding
**Menu:** Administration → Branding
**Route:** `/admin/authority-branding`

## Branding Settings

The branding configuration is organized into 3 sections:

### 1. Logo Settings

**Upload Custom Logo:**
1. Expand **Logo Settings** panel
2. Click **Choose File** to select logo
3. Click **Upload Logo** button
4. Logo uploaded and displayed

**Logo Requirements:**
- **Formats**: PNG, JPEG, GIF, WebP
- **Max Size**: 5MB
- **Recommended**: PNG with transparent background
- **Aspect Ratio**: Horizontal logo works best

**Logo Preview:**
- Shows current logo
- If no custom logo: Shows default based on authority name
  - LSFD/Fire authorities: `lsfdschriftzug.png`
  - Other authorities: `logo.png`

**Remove Logo:**
- Click **Remove Logo** button
- Reverts to default logo

**Logo Usage:**
- Desktop interface header
- Login page
- Navigation bar
- Reports and documents

### 2. Color Settings

**Customize Colors:**
1. Expand **Color Settings** panel
2. Configure primary and secondary colors

**Primary Color:**
- Main brand color
- Used for:
  - Primary buttons
  - Links and accents
  - Active states
  - Headers
- Enter hex code (e.g., #1976D2)
- Or use color picker

**Secondary Color:**
- Supporting brand color
- Used for:
  - Secondary buttons
  - Complementary elements
  - Subtle accents
- Enter hex code
- Or use color picker

**Color Preview:**
- Live preview card shows:
  - Primary colored button
  - Secondary colored button
  - How colors look together
- Preview updates as you change colors

**Color Validation:**
- Must be valid hex color code
- Format: #RRGGBB (6 hex digits)
- Examples: #FF0000, #00FF00, #0000FF

### 3. App Settings

**Configure Application Settings:**
1. Expand **App Settings** panel
2. Configure app title and background

**App Title:**
- Custom application title (optional)
- Displayed in interface
- If empty: Uses authority display name
- Example: "LSFD Management System"

**Default Background:**
- Desktop mode background image
- Upload custom background
- Or use default based on authority

**Background Upload:**
1. Click **Choose File**
2. Select background image
3. Click **Upload Background**
4. Background uploaded and preview updates

**Background Requirements:**
- **Formats**: PNG, JPEG, GIF, WebP
- **Max Size**: 10MB
- **Recommended**: 1920x1080px or larger
- **Quality**: High resolution for best appearance

**Background Preview:**
- Shows current background
- If no custom background: Shows default
  - LSFD/Fire: `/img/bg2.png`
  - LSPD/Police: `/img/bg3.png`
  - EMS/Medical: `/img/bg.jpg`
  - Default: `/img/bg.jpg`

**Remove Background:**
- Click **Remove Background** button
- Reverts to default background

## Saving Changes

**Save Branding:**
1. Make desired changes to:
   - Logo
   - Colors
   - App title
   - Background
2. Click **Save** button
3. Changes applied immediately

**Change Detection:**
- Save button disabled if no changes
- Prevents accidental saves
- Detects both form data and file upload changes

**Reset Changes:**
- Click **Reset** button
- Reverts unsaved changes
- Restores to last saved state
- Reset button disabled if no changes

## Default Settings

### Default Logos

**By Authority Name:**
- **LSFD/Fire departments**: `lsfdschriftzug.png`
- **All others**: `logo.png`

### Default Backgrounds

**By Authority Name:**
- **LSFD/Fire departments**: `/img/bg2.png` (fire/emergency theme)
- **LSPD/Police departments**: `/img/bg3.png` (law enforcement theme)
- **EMS/Medical**: `/img/bg.jpg` (medical theme)
- **Default**: `/img/bg.jpg` (general theme)

### Default Colors

If no colors configured, system uses theme defaults from System Settings.

## API Reference

### Branding Endpoints

```http
GET /admin/authority/branding.php?action=getBranding
Returns: Current branding configuration {
  primary_color,
  secondary_color,
  app_title,
  logo_url,
  background_url
}

POST /admin/authority/branding.php?action=updateBranding
Body: {
  primary_color,
  secondary_color,
  app_title
}
Returns: Success message

POST /admin/authority/branding.php?action=uploadLogo
Content-Type: multipart/form-data
Body: logo file
Returns: Success message and logo URL

POST /admin/authority/branding.php?action=uploadBackground
Content-Type: multipart/form-data
Body: background file
Returns: Success message and background URL
```

## File Uploads

**Upload Process:**
- Files uploaded via multipart/form-data
- Stored in authority-specific directory
- Accessible via URL returned from API
- Previous files may be overwritten

**File Validation:**
- File type checked (image formats only)
- File size checked (5MB logo, 10MB background)
- Invalid files rejected with error message

## Best Practices

**Do:**
✅ Use high-quality logo images
✅ Use PNG with transparency for logos
✅ Choose accessible color combinations
✅ Test colors for readability
✅ Use high-resolution backgrounds
✅ Keep branding consistent
✅ Preview before saving

**Don't:**
❌ Use low-resolution images
❌ Exceed file size limits
❌ Use colors with poor contrast
❌ Forget to save changes
❌ Upload copyrighted images without permission
❌ Use inappropriate content

## Troubleshooting

### Logo Not Appearing

**Solutions:**
1. Check file format (PNG, JPEG, GIF, WebP)
2. Verify file size under 5MB
3. Clear browser cache (Ctrl+F5)
4. Check upload completed successfully
5. Verify file uploaded (check preview)

### Background Not Showing

**Solutions:**
1. Check file format is valid
2. Verify file size under 10MB
3. Ensure high enough resolution
4. Clear browser cache
5. Check file uploaded successfully

### Colors Not Applying

**Solutions:**
1. Verify hex color format (#RRGGBB)
2. Click **Save** button
3. Clear browser cache
4. Check validation passed
5. Verify changes saved successfully

### Upload Failed

**Common Causes:**
- File too large
- Invalid file format
- Network connection issue
- Server storage full

**Solutions:**
1. Check file size and format
2. Compress image if too large
3. Try different file
4. Check internet connection
5. Contact administrator if persists

## Limitations

The following features mentioned in some documentation **do not exist** in the current codebase:

❌ **Not Available:**
- Multiple logo variations (secondary, inverted, icon-only)
- Favicon upload
- App icons for mobile/PWA
- Custom font selection
- Advanced color scheme (more than primary/secondary)
- Login page customization beyond logo/colors
- Email template branding configuration in this UI
- Document header/footer templates
- Watermark configuration
- CSS injection
- Theme export/import
- Brand guidelines generation
- Multiple background images
- Background effects (blur, opacity, overlay)
- Logo cropping/editing tools
- Image preview before upload
- Logo/background removal API calls (client-side only)
- Batch upload
- Asset download/export

---

## Next Steps

- [System Settings](/guide/admin/system-settings) - Configure overall theme colors
- [Authority Management](/guide/admin/authorities) - Manage authority settings
- [Desktop Interface](/guide/desktop-interface) - See where branding appears
