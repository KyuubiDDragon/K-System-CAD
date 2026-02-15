# Troubleshooting Guide

This guide helps you resolve common issues when using K-Systems.

::: tip Quick Diagnosis
Most issues can be resolved by:
1. Refreshing the page (F5)
2. Clearing browser cache
3. Logging out and back in
4. Checking your internet connection
:::

---

## Login Issues

### Problem: Cannot Log In

**Symptoms:** Login button doesn't work, or you get "Invalid credentials" error.

**Solutions:**

1. **Verify credentials are correct:**
   - Check username (case-sensitive)
   - Check password (case-sensitive)
   - Ensure Caps Lock is OFF

2. **Check organization/authority selection:**
   - Ensure you selected the correct organization from dropdown
   - Try selecting it again

3. **Clear browser cookies:**
   ```
   Chrome: Ctrl+Shift+Delete > Select "Cookies" > Clear data
   Firefox: Ctrl+Shift+Delete > Select "Cookies" > Clear
   ```

4. **Try different browser:**
   - Test in Chrome, Firefox, or Edge
   - If it works in another browser, clear first browser's cache

5. **Check with administrator:**
   - Your account may be disabled
   - Password may have been reset

### Problem: "Session Expired" Message

**Symptoms:** Frequent logouts or "Session expired, please login again" messages.

**Causes:**
- JWT token expired (default: 1 hour)
- Computer clock is incorrect
- Browser cookies disabled

**Solutions:**

1. **Check computer clock:**
   - Ensure date and time are correct
   - Enable automatic time sync

2. **Enable browser cookies:**
   - Chrome: Settings > Privacy > Cookies > "Allow all cookies"
   - Firefox: Settings > Privacy > Standard

3. **Stay active:**
   - Sessions expire after inactivity
   - Perform actions regularly to keep session alive

4. **Contact administrator:**
   - They can increase JWT_EXPIRATION_TIME if too short

### Problem: "Authority not found"

**Symptoms:** Can't see your organization in the login dropdown.

**Solutions:**

1. **Verify organization is active:**
   - Contact administrator
   - Organization may have been deactivated

2. **Clear browser cache:**
   - Old data may be cached

3. **Check URL:**
   - Ensure you're accessing the correct K-Systems instance

---

## Page Loading Issues

### Problem: Blank White Page

**Symptoms:** Page loads but shows nothing, or shows only header.

**Solutions:**

1. **Hard refresh:**
   ```
   Windows/Linux: Ctrl+F5 or Ctrl+Shift+R
   Mac: Cmd+Shift+R
   ```

2. **Clear browser cache:**
   - Clear all cached data
   - Restart browser

3. **Check browser console for errors:**
   - Press F12
   - Go to "Console" tab
   - Look for red error messages
   - Screenshot errors and send to administrator

4. **Disable browser extensions:**
   - Ad blockers can interfere
   - Try incognito/private mode (Ctrl+Shift+N)

5. **Update browser:**
   - Ensure browser is up-to-date
   - K-Systems requires modern browser features

### Problem: Page Loads Slowly

**Symptoms:** Interface takes long time to load or respond.

**Solutions:**

1. **Check internet connection:**
   - Test speed: speedtest.net
   - Minimum recommended: 5 Mbps download

2. **Close unnecessary tabs:**
   - Each tab consumes memory
   - Close other applications

3. **Clear browser data:**
   - Cache can become bloated
   - Clear and restart browser

4. **Check server status:**
   - Ask other users if they experience same issue
   - If yes, contact administrator (server may be overloaded)

### Problem: "404 Not Found" Errors

**Symptoms:** Clicking links leads to 404 error page.

**Solutions:**

1. **Refresh the page:**
   - URL may be outdated

2. **Navigate via sidebar:**
   - Use main navigation instead of direct links

3. **Clear browser cache:**
   - Cached routes may be outdated

4. **Report to administrator:**
   - May indicate broken links in application

---

## Feature Access Issues

### Problem: Can't See a Module/Feature

**Symptoms:** Expected module is missing from sidebar.

**Solutions:**

1. **Check if feature is enabled for you:**
   - Contact administrator
   - Request access to specific feature

2. **Expand sidebar categories:**
   - Some modules are nested in categories
   - Click category to expand

3. **Use search:**
   - Press Ctrl+K / Cmd+K
   - Search for module name
   - If it doesn't appear in search, you don't have access

4. **Check permissions:**
   - You need specific permissions to access modules
   - Administrator can grant access

### Problem: "Insufficient Permissions" Error

**Symptoms:** Error message when trying to perform action.

**What it means:**
- You don't have permission for that specific action
- You may have read-only access (can view but not edit)

**Solutions:**

1. **Verify you need this permission:**
   - Understand what permission is required
   - Example: Creating reports requires WRITE_REPORT permission

2. **Contact administrator:**
   - Request specific permission
   - Explain why you need it
   - Provide use case

3. **Check your role:**
   - Ask administrator what role(s) you have
   - Roles determine your permissions

### Problem: Buttons are Disabled/Grayed Out

**Symptoms:** Can see feature but can't click buttons.

**Causes:**
- Missing permissions
- Form validation errors
- Resource locked by another user
- Feature not fully loaded

**Solutions:**

1. **Check for error messages:**
   - Red text near form fields
   - Fix validation errors first

2. **Wait for page to fully load:**
   - Buttons may be disabled until data loads

3. **Refresh the page:**
   - Page state may be corrupted

4. **Check permissions:**
   - You may have read-only access

---

## Data & Content Issues

### Problem: Data Not Saving

**Symptoms:** Click Save but changes don't persist.

**Solutions:**

1. **Check for validation errors:**
   - Red text or error messages near fields
   - Fill in all required fields (marked with *)

2. **Check browser console:**
   - Press F12 > Console
   - Look for red errors
   - Screenshot and send to administrator

3. **Check internet connection:**
   - Save requires network connection
   - Check if connection dropped

4. **Try again:**
   - Click Save again
   - Sometimes network requests fail temporarily

5. **Check permissions:**
   - You may not have write permission
   - Contact administrator

### Problem: Changes Not Visible to Others

**Symptoms:** You made changes but colleagues don't see them.

**Solutions:**

1. **Verify changes were saved:**
   - Look for success message
   - Refresh your page and check if changes persist

2. **Ask colleagues to refresh:**
   - They may be viewing cached data
   - Hard refresh: Ctrl+F5

3. **Check permissions:**
   - Colleagues may not have permission to view that data

4. **Wait a moment:**
   - Some updates may take a few seconds to propagate

### Problem: Search Not Finding Results

**Symptoms:** Search returns no results when you know data exists.

**Solutions:**

1. **Check spelling:**
   - Search is often case-insensitive but spelling must be correct

2. **Use partial search:**
   - Type only part of the word
   - Example: "Hans" instead of "Hans Müller"

3. **Check filters:**
   - Applied filters may be hiding results
   - Reset filters and search again

4. **Check permissions:**
   - You can only search data you have access to

5. **Try advanced search:**
   - If available, use advanced search with specific fields

---

## File Upload Issues

### Problem: File Upload Fails

**Symptoms:** Upload progress bar stops or shows error.

**Solutions:**

1. **Check file size:**
   - Maximum file size usually: 64MB
   - Compress large files or split them

2. **Check file type:**
   - Some file types may be restricted
   - Try renaming file extension if appropriate

3. **Check internet connection:**
   - Stable connection required for large uploads
   - Avoid WiFi for large files if possible

4. **Try again:**
   - Network interruptions can cause failures
   - Retry upload

5. **Use different browser:**
   - Sometimes browser-specific issues occur

6. **Contact administrator:**
   - Server may be out of storage space
   - Upload limits may need adjustment

### Problem: Uploaded Files Not Visible

**Symptoms:** File uploaded successfully but doesn't appear in list.

**Solutions:**

1. **Refresh the page:**
   - Ctrl+F5 for hard refresh

2. **Check filters:**
   - File may be hidden by active filters

3. **Check permissions:**
   - File may have been saved in wrong location
   - Or you don't have permission to view it

4. **Wait a moment:**
   - Processing may take a few seconds

---

## Real-Time Features (Socket Connection)

### Problem: No Real-Time Updates

**Symptoms:** Changes made by others don't appear without refresh. Notifications don't pop up.

**Causes:**
- WebSocket connection failed
- Firewall/proxy blocking WebSocket
- Socket server offline

**Solutions:**

1. **Check connection indicator:**
   - Look for connection status icon (usually in top bar)
   - Red/offline means no connection

2. **Refresh the page:**
   - Reconnects WebSocket

3. **Check firewall/proxy:**
   - Some corporate networks block WebSocket
   - Ask IT department to whitelist socket connection

4. **Try different network:**
   - Test on different WiFi or mobile hotspot
   - If it works, problem is with original network

5. **Contact administrator:**
   - Socket server may be down
   - Provide details: timestamp, what you were doing

### Problem: Chat Messages Not Sending

**Symptoms:** Messages in whiteboard or chat don't send.

**Solutions:**

1. **Check real-time connection:**
   - See "No Real-Time Updates" above

2. **Check message content:**
   - Very long messages may fail
   - Special characters may cause issues

3. **Refresh and retry:**
   - Reconnect WebSocket
   - Send message again

---

## Desktop Mode Issues

### Problem: Desktop Mode Not Loading

**Symptoms:** Clicking Desktop icon does nothing, or shows errors.

**Solutions:**

1. **Clear browser cache:**
   - Desktop mode relies on cached assets
   - Clear cache and reload

2. **Check browser compatibility:**
   - Desktop mode requires modern browser
   - Update to latest version

3. **Disable browser extensions:**
   - Some extensions interfere with desktop mode
   - Try in incognito mode

4. **Refresh page:**
   - Hard refresh: Ctrl+F5

### Problem: Desktop Windows Not Draggable

**Symptoms:** Can't move or resize windows.

**Solutions:**

1. **Check if window is maximized:**
   - Maximized windows can't be dragged
   - Click restore button first

2. **Refresh desktop mode:**
   - Exit and re-enter desktop mode

3. **Clear browser cache:**
   - Desktop mode state may be corrupted

### Problem: Desktop Background Not Changing

**Symptoms:** New background doesn't apply.

**Solutions:**

1. **Check file format:**
   - Use JPG or PNG images
   - Maximum size usually 2-5MB

2. **Refresh page:**
   - Changes may require page reload

3. **Clear browser cache:**
   - Old background may be cached

---

## Performance Issues

### Problem: K-Systems Running Slowly

**Symptoms:** Laggy interface, slow response times.

**Solutions:**

1. **Close unnecessary browser tabs:**
   - Each tab consumes resources

2. **Close unused applications:**
   - Free up system memory

3. **Restart browser:**
   - Long browser sessions can accumulate memory leaks

4. **Check system resources:**
   - Windows: Ctrl+Shift+Esc (Task Manager)
   - Mac: Cmd+Space > "Activity Monitor"
   - Ensure CPU and RAM aren't maxed out

5. **Update browser:**
   - Newer versions often have performance improvements

6. **Check internet speed:**
   - Run speed test
   - If slow, contact ISP

### Problem: High CPU/Memory Usage

**Symptoms:** Computer fan loud, system sluggish when using K-Systems.

**Solutions:**

1. **Close desktop mode:**
   - Desktop mode uses more resources
   - Switch to sidebar mode

2. **Close browser tabs:**
   - Limit open K-Systems tabs

3. **Disable widgets:**
   - Desktop widgets consume resources

4. **Update browser:**
   - May have better resource management

5. **Increase system RAM:**
   - If possible, upgrade computer memory

---

## Mobile/Tablet Issues

### Problem: Interface Doesn't Fit Screen

**Symptoms:** Layout broken on mobile device.

**Solutions:**

1. **Rotate device:**
   - Landscape mode works better for many features

2. **Zoom out:**
   - Pinch to zoom out
   - Browser may be zoomed in

3. **Use full-screen mode:**
   - Hide browser URL bar for more space

4. **Use desktop version:**
   - Mobile devices get responsive layout
   - Request desktop site in browser settings for full version

### Problem: Touch Controls Not Working

**Symptoms:** Can't click buttons or drag items on touch device.

**Solutions:**

1. **Tap firmly:**
   - Ensure screen registers touch

2. **Disable gloves mode:**
   - If using gloves, they may interfere

3. **Clean screen:**
   - Dirt/moisture affects touch sensitivity

4. **Restart device:**
   - Touch driver may need reset

5. **Use stylus:**
   - More precise than finger

---

## Printing Issues

### Problem: Can't Print Reports/Documents

**Symptoms:** Print function doesn't work or output is garbled.

**Solutions:**

1. **Use PDF export:**
   - Export to PDF first
   - Then print PDF
   - Better formatting and compatibility

2. **Use browser print:**
   - Ctrl+P / Cmd+P
   - Select printer and options

3. **Check print preview:**
   - Before printing, use print preview
   - Adjust page layout if needed

4. **Update printer drivers:**
   - Outdated drivers can cause issues

### Problem: Printed Output Missing Content

**Symptoms:** Some content doesn't appear in printout.

**Solutions:**

1. **Check print settings:**
   - Ensure "Print background colors and images" is enabled

2. **Use landscape orientation:**
   - Wide tables need landscape

3. **Adjust scaling:**
   - Try 100% scale instead of "Fit to page"

4. **Export to PDF first:**
   - More reliable for complex layouts

---

## Browser-Specific Issues

### Chrome Issues

**Problem: "Aw, Snap!" Error**

**Solution:**
```bash
1. Close Chrome completely
2. Reopen Chrome
3. Clear browsing data (Ctrl+Shift+Delete)
4. Disable hardware acceleration:
   Settings > Advanced > System > Disable "Use hardware acceleration"
```

### Firefox Issues

**Problem: "Secure Connection Failed"**

**Solution:**
```bash
1. Check date/time settings
2. Clear certificates: Settings > Privacy > Certificates > Clear
3. Disable security extensions temporarily
4. Contact administrator if using self-signed certificate
```

### Safari Issues

**Problem: Cookies Not Working**

**Solution:**
```bash
1. Safari > Preferences > Privacy
2. Uncheck "Block all cookies"
3. Ensure "Prevent cross-site tracking" is off for K-Systems
```

---

## Error Messages Explained

### "Network Error"
**Meaning:** Connection to server failed.
**Fix:** Check internet connection, refresh page, contact administrator if persists.

### "Unauthorized" or "403 Forbidden"
**Meaning:** You don't have permission for that action.
**Fix:** Contact administrator to request permission.

### "Session Expired"
**Meaning:** Your login token expired (usually after 1 hour).
**Fix:** Log out and log back in.

### "Bad Request" or "400 Error"
**Meaning:** Invalid data sent to server.
**Fix:** Check form fields for errors, refresh page, try again.

### "Server Error" or "500 Error"
**Meaning:** Server encountered unexpected error.
**Fix:** Wait a moment and try again. If persists, contact administrator with details of what you were doing.

### "Database Error"
**Meaning:** Server couldn't access database.
**Fix:** Contact administrator immediately. This is a critical error.

---

## When to Contact Administrator

Contact your system administrator if:

- ✅ You've tried solutions in this guide without success
- ✅ Error persists for more than 15 minutes
- ✅ Multiple users experience the same issue
- ✅ You see database or server errors
- ✅ Data appears corrupted or missing
- ✅ Security-related concerns
- ✅ Need permissions or feature access

**When contacting admin, provide:**

1. **What you were trying to do**
2. **Exact error message** (screenshot if possible)
3. **Steps to reproduce the issue**
4. **Your username and organization**
5. **Browser and OS version**
6. **Timestamp when issue occurred**

---

## Preventive Measures

### Best Practices to Avoid Issues

**Regular Maintenance:**
- Clear browser cache weekly
- Keep browser updated
- Restart browser daily
- Log out when finished

**Good Habits:**
- Save work frequently
- Don't keep K-Systems open for days
- Use supported browsers
- Enable automatic browser updates

**Security:**
- Don't share your password
- Log out on shared computers
- Use strong passwords
- Change password regularly

---

## Emergency Procedures

### System is Completely Down

**Symptoms:** Can't access K-Systems at all.

**Steps:**

1. **Verify it's not just you:**
   - Ask colleagues if they can access it
   - Try from different network/device

2. **Contact administrator immediately:**
   - Provide details
   - Note timestamp

3. **Document your work:**
   - Keep offline notes of urgent tasks

4. **Wait for updates:**
   - Administrator will provide status updates

### Data Loss or Corruption

**Symptoms:** Important data is missing or incorrect.

**Steps:**

1. **Don't make more changes:**
   - Stop using the affected feature

2. **Document what's wrong:**
   - Screenshot evidence
   - Note what data is affected
   - Write down what you last remember

3. **Contact administrator urgently:**
   - Provide all documentation
   - They may be able to restore from backup

4. **Check with colleagues:**
   - Verify they see the same issue

---

## Additional Resources

- **[FAQ](/guide/faq)** - Frequently asked questions
- **[Getting Started](/guide/getting-started)** - Basic usage guide
- **[User Guide](/guide/introduction)** - Complete documentation
- **[Installation Guide](/guide/installation)** - For administrators

---

**Still experiencing issues?** Contact your system administrator with details of the problem and what solutions you've tried.

**Last Updated:** 2025-10-27
