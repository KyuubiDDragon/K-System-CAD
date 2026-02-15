# Profile Settings

User profile management for personalizing your K-Systems experience, managing report templates, and security settings.

## Overview

The Profile Settings page allows you to:
- View your account information
- Change your password
- Configure document view preferences
- Manage report template images
- Set your signature for reports

**Required Permission:** `READ_ACCOUNT` (all users have this by default)

## Accessing Profile Settings

**Route:** `/profile`

**How to Access:**
- Click your username in the top navigation bar
- Or navigate directly to `/profile`

## Profile Information

### Account Overview

**View Your Information:**
- **Username**: Your system username (read-only)
- **Last Login**: When you last logged into the system
- **Roles**: All roles assigned to your account

This information is display-only and cannot be edited here. Contact your administrator to update username or roles.

**Refresh Button**: Click the refresh icon to reload your account information from the server.

## Personal Settings

### Document View Preference

Choose how documents are displayed throughout the system:

**View Options:**
1. **Kachel** (Tiles): Display documents as visual cards/tiles
2. **Tabelle** (Table): Display documents in a traditional table format

**How to Change:**
1. Navigate to **Personal Settings** tab (Konto)
2. Select your preferred view from the dropdown
3. Click **Save**
4. Your selection is saved immediately and applies to all document views

## Templates & Signatures

### Organization Branding Templates

Configure header and footer images for your organization's reports.

**Header Image:**
- Enter the URL to your organization's header image
- This appears at the top of generated reports
- Preview the image by clicking the eye icon

**Footer Image:**
- Enter the URL to your organization's footer image
- This appears at the bottom of generated reports
- Preview the image by clicking the eye icon

**Save Organization Branding:**
Click **Save** button to apply your header and footer images to all reports.

### Neutral Templates

Configure header and footer images for neutral reports (reports without organization branding).

**Use Case**: Some reports may need to be generated without specific organization branding. These neutral templates will be used in those cases.

**Configuration**:
- Neutral Header Image URL
- Neutral Footer Image URL
- Preview each image before saving
- Save separately from organization branding

### Signature

Add your personal signature to appear on reports you create or approve.

**How to Configure:**
1. Go to **Templates** tab (Vorlagen)
2. Scroll to **Unterschrift** section
3. Enter the URL to your signature image
4. Click preview (eye icon) to verify it looks correct
5. Click **Save**

**Image Requirements:**
- Must be a publicly accessible URL
- Recommended: PNG with transparent background
- Recommended size: 300x100 pixels
- Keep file size small for faster loading

## Security

### Change Password

Update your account password for security.

**Password Requirements:**
- Minimum 8 characters
- At least one uppercase letter recommended
- At least one number recommended
- At least one special character recommended

**Steps to Change Password:**
1. Navigate to **Security** tab (Sicherheit)
2. Enter your **Current Password** (for verification)
3. Enter your **New Password**
4. **Repeat New Password** (must match exactly)
5. Click **Change Password**
6. You'll see a confirmation message when successful

**After Changing:**
- All form fields are automatically cleared
- You remain logged in with your new password
- Your next login will require the new password

**Security Tips:**

::: tip Strong Passwords
Use at least 8 characters with a mix of uppercase, lowercase, numbers, and special characters (@$!%*?&).
:::

::: warning Regular Changes
Change your password regularly for security, especially if you suspect unauthorized access.
:::

::: tip Never Share
Never share your password with anyone, including administrators. K-Systems staff will never ask for your password.
:::

## Image Preview

When you configure template images or signatures, you can preview them before saving:

**Preview Feature:**
- Click the eye icon (👁️) next to any image URL field
- A dialog opens showing the full image
- If the image fails to load, you'll see an error message
- Close the preview by clicking the X button

**Troubleshooting Image Preview:**
- Verify the URL is correct and publicly accessible
- Check that the image format is supported (JPG, PNG, GIF)
- Ensure the URL uses HTTPS (not HTTP)
- Try opening the URL in a new browser tab to verify it works

## Tips & Best Practices

**Document View:**
- Choose **Tiles** for visual browsing with thumbnails
- Choose **Table** for detailed information and faster scanning of many items

**Template Images:**
- Use high-quality images for professional appearance
- Keep file sizes reasonable (under 500KB) for faster loading
- Use consistent dimensions across all reports for uniform look
- Test how images look when printed or saved as PDF

**Signatures:**
- Use a transparent background PNG for best results
- Make sure your signature is legible at small sizes
- Consider using a digital signature service for legal documents
- Test your signature on a sample report before using widely

**Password Security:**
- Use a unique password not used on other sites
- Consider using a password manager
- Change password if you suspect compromise
- Don't write down your password

## Troubleshooting

### Changes Not Saving

**Problem**: Clicked Save but changes don't persist

**Solutions:**
1. Check your internet connection
2. Look for error messages in red
3. Ensure all required fields are filled
4. Try refreshing the page and making changes again
5. Clear browser cache and try again

### Password Change Fails

**Problem**: Can't change password

**Solutions:**
1. Verify you entered current password correctly
2. Check new password meets requirements (8+ characters)
3. Ensure new password and repeat password match exactly
4. Try clearing the form and starting over
5. Check Caps Lock is not accidentally on

### Images Not Previewing

**Problem**: Preview shows error or doesn't load

**Solutions:**
1. Verify image URL is correct
2. Check image is publicly accessible (open URL in new tab)
3. Ensure URL starts with https://
4. Try a different image URL
5. Contact your system administrator if images are hosted internally

### Profile Information Not Updating

**Problem**: Account info (username, roles) needs updating

**Solution**: Contact your system administrator. These fields are managed centrally and cannot be self-edited for security reasons.

## Related Documentation

- [Getting Started](/guide/getting-started) - Learn the basics of K-Systems
- [Reports](/guide/reports) - How to create and manage reports using your templates
- [Documents](/guide/documents) - Working with documents and your view preferences
- [Admin: User Management](/guide/admin/users) - For administrators managing user accounts

---

**Last Updated:** 2025-10-01
**Version:** 2.0.0 (Corrected to match actual implementation)
