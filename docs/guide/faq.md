# Frequently Asked Questions (FAQ)

Common questions and answers about using K-Systems.

---

## General Questions

### What is K-Systems?

K-Systems is a comprehensive multi-tenant enterprise management system designed primarily for fire departments and emergency services. It provides tools for managing employees, reports, documents, operations, training, and communication - all in one integrated platform.

### Who can use K-Systems?

K-Systems is suitable for:
- Fire departments and emergency services
- Multi-department organizations
- Service providers managing multiple clients
- Any organization needing robust personnel and operations management

### Is K-Systems available in my language?

Currently, K-Systems supports:
- **English** (en)
- **German** (de)

You can switch languages in your profile settings.

### What browsers are supported?

K-Systems works best on modern browsers:
- Google Chrome 90+
- Mozilla Firefox 88+
- Microsoft Edge 90+
- Safari 14+

JavaScript and cookies must be enabled.

---

## Login & Access

### I can't log in. What should I do?

**Check these common issues:**

1. **Correct organization/authority selected?**
   - Make sure you selected the right organization from the dropdown

2. **Caps Lock on?**
   - Passwords are case-sensitive

3. **Correct credentials?**
   - Verify username and password with your administrator

4. **Account active?**
   - Your account may have been deactivated - contact your admin

5. **Browser cookies enabled?**
   - K-Systems requires cookies to function

### I forgot my password. How do I reset it?

Contact your system administrator. They can reset your password for you. K-Systems does not currently have a self-service password reset feature.

### What is an "Authority" in K-Systems?

An "Authority" is another term for your organization or tenant in the multi-tenant system. It's the entity you belong to and determines which data you can access. Each authority has complete data isolation from others.

### Why do I see "Insufficient permissions" errors?

You don't have the required permission for that action. K-Systems uses role-based access control. Contact your administrator to request additional permissions if needed.

### Can I log in from multiple devices?

Yes, you can log in from multiple devices simultaneously. However, if you log out from one device, your session on other devices will remain active until the token expires (usually 1 hour).

---

## Navigation & Interface

### How do I switch between Sidebar and Desktop mode?

**From Sidebar Mode:**
1. Click the **Desktop** icon in the sidebar menu
2. The interface will switch to Desktop mode

**From Desktop Mode:**
1. Click the **Sidebar** button (usually in top-left or via Start menu)
2. The interface will switch to Sidebar mode

You can also switch in Settings.

### I can't find a module/feature. Where is it?

**Possible reasons:**

1. **Not enabled for your account**
   - Contact your administrator to enable the feature

2. **No permission to access**
   - You need specific permissions to see modules

3. **Hidden in collapsed sidebar**
   - Expand the sidebar or use the search function

**Use the search:** Press `Ctrl+K` (Windows/Linux) or `Cmd+K` (Mac) to search for any module or feature.

### How do I search for something?

1. Click the **search icon** (🔍) in the top bar
2. Or press `Ctrl+K` / `Cmd+K`
3. Type your search query
4. Select from the results

You can search for:
- Module names (e.g., "employees", "reports")
- Employee names
- Document titles
- Any content in the system

### The interface is in the wrong language. How do I change it?

1. Click your **profile picture/name** (top right)
2. Select **Profile Settings**
3. Find **Language** setting
4. Choose **English** or **Deutsch**
5. Click **Save**
6. Refresh the page

---

## Employee Management

### How do I add a new employee?

**Requirements:** You need `WRITE_EMPLOYEE` permission.

1. Navigate to **Employees** in the sidebar
2. Click **"+ New Employee"** or **"Add Employee"** button
3. Fill in required fields:
   - Name
   - Company/Department
   - Rank (if applicable)
4. Add optional information (contact, licenses, etc.)
5. Click **Save**

[Learn more: Employee Management Guide](/guide/employee-management)

### Can I import employees from a CSV/Excel file?

This feature depends on your K-Systems version. Check with your administrator if bulk import is available.

### How do I manage employee vacations?

1. Go to **Employees** module
2. Click on **Vacation/Absence** (or find it in submenu)
3. Add new vacation entry:
   - Select employee
   - Choose dates
   - Select type (vacation, sick leave, training, etc.)
4. Save

Employees with vacation are marked in the employee list.

### How do I track employee training?

1. Navigate to **Training** module
2. Select **Training Assignments**
3. Assign training to employees
4. Track completion status

[Learn more: Training Guide](/guide/training)

---

## Documents

### How do I upload a document?

**Requirements:** You need `WRITE_DOCUMENT` permission for the specific area.

1. Go to **Documents** module
2. Select the appropriate **document area** (Training, Department, Administration, etc.)
3. Click **"+ New Document"** or **"Upload"**
4. Either:
   - **Upload file:** Select file from your computer
   - **Create new:** Use the rich text editor
5. Set document title and permissions
6. Click **Save**

[Learn more: Documents Guide](/guide/documents)

### Who can see my documents?

Document visibility is controlled by:

1. **Document Area Permissions:** Each area has its own access control
2. **Document-Level Permissions:** You can set read/write/delete permissions per document
3. **Role Permissions:** Your role determines which areas you can access

Check the document permissions dialog when creating/editing documents.

### Can I share documents with other organizations?

Yes, if you have access to the **Authority Exchange** (global documents) area. These documents are visible across all organizations in the system.

### I can't edit a document. Why?

**Possible reasons:**

1. **No write permission:** You have read-only access
2. **Document locked:** Someone else is editing it
3. **Insufficient role permissions:** Contact your administrator

### How do I organize documents into folders?

K-Systems uses **Document Areas** instead of traditional folders. Each area can have categories and subcategories. Ask your administrator to create custom document areas for better organization.

---

## Reports

### How do I create a new report?

**Requirements:** You need `WRITE_REPORT` permission.

1. Navigate to **Reports**
2. Click **"+ New Report"**
3. Select **report category** (e.g., Incident, Training, Inspection)
4. Fill in required fields
5. Add optional attachments or custom fields
6. Select **status** (e.g., Open, In Progress, Completed)
7. Click **Save** or **Submit**

[Learn more: Reports Guide](/guide/reports)

### Can I export reports to PDF?

Yes, most reports have a **PDF Export** button in the detail view. Click it to generate and download a PDF version.

### How do I share a report with another organization?

If the report system supports cross-authority sharing:
1. Edit the report
2. Look for **"Share with authorities"** option
3. Select target organizations
4. Save

### What are report templates?

Report templates are pre-configured report structures with predefined fields. They help you create reports faster and maintain consistency. Select a template when creating a new report.

### Can I add custom fields to reports?

Yes, if you're an administrator. Go to **Admin > Report Fields** to configure custom fields per authority.

---

## Calendar & Events

### How do I create an event?

**Requirements:** You need `WRITE_CALENDAR` permission.

1. Open **Calendar** module
2. Click **"+ New Event"** or click directly on a date/time
3. Fill in event details:
   - Title
   - Date and time
   - Duration
   - Assigned users
   - Description
4. Click **Save**

[Learn more: Calendar Guide](/guide/calendar)

### How do I invite people to an event?

When creating/editing an event:
1. Find **"Assigned Users"** or **"Participants"** field
2. Select users from the list
3. Save the event

Invited users will see the event in their calendar.

### Can I create recurring events?

Yes, when creating an event, look for **"Recurring Event"** option and set the recurrence pattern (daily, weekly, monthly).

### How do I view only my events?

Use calendar filters:
1. Click **Filter** or **Options**
2. Select **"My Events Only"** or deselect calendar groups
3. The view will update

---

## Messages & Communication

### How do I send a message to someone?

1. Navigate to **Messages** or **Mail**
2. Click **"Compose"** or **"+ New Message"**
3. Select recipient(s)
4. Enter subject and message
5. Optionally attach files
6. Click **Send**

[Learn more: Messages Guide](/guide/messages)

### Can I attach files to messages?

Yes, use the attachment button when composing a message. Supported file types depend on your system configuration.

### How do I organize messages into folders?

1. Go to **Messages**
2. Look for **"Folders"** in the sidebar
3. Create new folders with **"+ New Folder"** if available
4. Drag messages into folders or use **Move to** option

### What's the difference between Messages and Mail?

- **Messages:** Internal messaging system within K-Systems
- **Mail:** If enabled, can integrate with external email accounts

Check with your administrator which systems are available.

### How do I know if someone read my message?

K-Systems currently does not have read receipts. Check with your administrator if this feature is enabled.

---

## Dispatch & Operations

### How do I create a dispatch call?

**Requirements:** You need `WRITE_DISPATCH` permission.

1. Navigate to **Dispatch** module
2. Click **"+ New Call"** or **"Create Dispatch"**
3. Enter call details:
   - Location
   - Type of emergency
   - Priority
4. Assign vehicles and crew
5. Click **Save** or **Dispatch**

[Learn more: Dispatch Guide](/guide/dispatch)

### How do I see available vehicles?

In the **Dispatch** module, there's usually a **Vehicles** panel showing:
- Available vehicles
- Vehicles in use
- Vehicle status

### Can I track vehicles on a map?

Yes, if the map integration is enabled. Go to **Map** module to see vehicle locations and dispatch markers.

---

## Desktop Mode

### What is Desktop Mode?

Desktop Mode is a unique Windows-style interface where you can:
- Open multiple applications in separate windows
- Drag and position windows freely
- Use a taskbar to manage open windows
- Add desktop icons for quick access
- Use widgets (weather, calendar, notes)

[Learn more: Desktop Interface Guide](/guide/desktop-interface)

### How do I customize the desktop background?

1. Enter Desktop Mode
2. Right-click on desktop or open **Desktop Settings**
3. Choose **"Change Background"**
4. Select from available backgrounds or upload your own
5. Apply changes

### Can I rearrange desktop icons?

Yes, simply drag and drop desktop icons to rearrange them. Their positions are saved automatically.

### How do I add/remove desktop widgets?

1. In Desktop Mode, open **Start Menu**
2. Go to **Settings** > **Desktop Customization**
3. Enable/disable widgets:
   - Weather widget
   - Calendar widget
   - Notes widget
4. Drag widgets to position them on desktop

---

## Profile & Settings

### How do I change my password?

1. Click your **profile picture/name** (top right)
2. Select **Profile Settings**
3. Find **"Change Password"** section
4. Enter:
   - Current password
   - New password
   - Confirm new password
5. Click **Save** or **Update Password**

### How do I upload a profile picture?

1. Go to **Profile Settings**
2. Click on your current avatar/picture
3. Choose **"Upload Photo"** or **"Change Avatar"**
4. Select image from your computer
5. Crop if needed
6. Save

### Can I change my username?

Usually, no. Usernames are typically set by administrators and cannot be changed by users. Contact your administrator if you need a username change.

### How do I change notification settings?

If notification settings are available:
1. Go to **Profile Settings** or **Settings**
2. Find **Notifications** section
3. Toggle which notifications you want to receive
4. Save changes

---

## Permissions & Access

### How do I know what permissions I have?

Permissions aren't directly visible in the UI, but you can tell by:
- Which modules appear in the sidebar (features you can access)
- Which buttons are enabled (actions you can perform)
- Error messages ("Insufficient permissions" means you lack that permission)

Contact your administrator for a complete list of your permissions.

### I need access to a feature. How do I request it?

1. Contact your **system administrator**
2. Explain which feature/module you need
3. Provide justification for why you need access
4. Administrator will evaluate and grant permissions if approved

### What's the difference between a role and a permission?

- **Role:** A collection of permissions (e.g., "Admin", "Member", "Manager")
- **Permission:** A specific right to perform an action (e.g., "READ_EMPLOYEE", "WRITE_REPORT")

Users are assigned roles, and roles contain permissions.

### Can I have multiple roles?

Yes, users can be assigned multiple roles. Your effective permissions are the combination of all your roles.

---

## Technical Issues

### The page is not loading. What should I do?

1. **Check internet connection**
2. **Refresh the page:** Press `F5` or `Ctrl+R`
3. **Clear browser cache:**
   - Chrome: `Ctrl+Shift+Delete`
   - Firefox: `Ctrl+Shift+Delete`
   - Select "Cached images and files" and clear
4. **Try a different browser**
5. **Check if other users have the same issue**
6. **Contact your administrator if problem persists**

[See more: Troubleshooting Guide](/guide/troubleshooting)

### I'm getting "Session expired" messages frequently

**Possible causes:**

1. **Token expiration:** Sessions expire after inactivity (usually 1 hour)
2. **Clock sync issues:** Ensure your computer's clock is correct
3. **Cookie issues:** Ensure cookies are enabled in your browser

**Solution:** Log out and log back in. If it persists, contact your administrator.

### Features are missing or look broken

1. **Clear browser cache**
2. **Disable browser extensions** that might interfere (ad blockers, etc.)
3. **Update your browser** to the latest version
4. **Try incognito/private browsing mode**
5. **Check browser console** for errors (F12 > Console tab)
6. **Report to administrator** with screenshots of the issue

### File uploads are failing

**Check:**

1. **File size:** Is your file too large? (Usually limit is 64MB)
2. **File type:** Is the file type allowed?
3. **Internet connection:** Stable connection required for large files
4. **Browser:** Try a different browser
5. **Storage space:** Server may be out of space (contact admin)

### Real-time features aren't working (chat, notifications)

**Real-time features require WebSocket connection:**

1. **Check firewall/proxy:** Some networks block WebSocket
2. **Browser support:** Ensure browser supports WebSocket
3. **Refresh page:** Sometimes connection is lost
4. **Contact administrator:** Socket server may be down

---

## Mobile & Tablets

### Can I use K-Systems on my phone/tablet?

K-Systems has a responsive design and works on mobile devices, but the desktop experience is optimized for larger screens. Some features may be limited on mobile.

**Recommended:**
- **Desktop/Laptop:** Full experience
- **Tablet:** Most features work well
- **Phone:** Basic functionality (viewing data, simple tasks)

### Is there a mobile app?

Currently, K-Systems does not have a dedicated mobile app. Use your mobile browser to access the web interface.

---

## Data & Privacy

### Is my data secure?

Yes, K-Systems implements multiple security layers:
- JWT token-based authentication
- Role-based access control
- Multi-tenant data isolation (your organization's data is completely separated)
- HTTPS encryption (in production)
- Regular security updates

### Can other organizations see my data?

No. K-Systems uses strict multi-tenant isolation. Each organization (authority) has completely isolated data. Other organizations cannot see your data unless you explicitly share documents via "Authority Exchange".

### How long is data retained?

Data retention policies depend on your organization's configuration. Contact your administrator for specific retention policies.

### Can I export my data?

Depending on your permissions, you may be able to export:
- Reports to PDF
- Employee lists to CSV (if available)
- Documents by downloading them

Contact your administrator for bulk data export options.

---

## Getting More Help

### Where can I find more documentation?

- **[User Guide](/guide/introduction)** - Complete feature documentation
- **[Desktop Interface Guide](/guide/desktop-interface)** - Desktop mode details
- **[Module-Specific Guides](/guide/)** - Detailed guides for each feature
- **[Troubleshooting](/guide/troubleshooting)** - Common problems and solutions

### Who do I contact for support?

**For usage questions:**
- Contact your **system administrator**
- Check this FAQ and documentation first

**For technical issues:**
- Report to your **IT department** or **system administrator**
- Provide details: what you were doing, error messages, screenshots

**For feature requests:**
- Discuss with your **administrator**
- Administrators can submit feature requests to developers

### How do I report a bug?

1. **Document the issue:**
   - What were you trying to do?
   - What happened instead?
   - Error messages (exact text or screenshot)
   - Steps to reproduce

2. **Report to administrator:**
   - Provide all documentation
   - Administrator will verify and escalate if needed

3. **Workaround:**
   - Ask if there's a temporary workaround while bug is fixed

---

## Tips & Best Practices

### How can I work more efficiently in K-Systems?

**Tips:**

1. **Use keyboard shortcuts:**
   - `Ctrl+K` / `Cmd+K` - Global search
   - Learn module-specific shortcuts

2. **Customize your desktop:**
   - Arrange icons for frequently used modules
   - Add widgets for quick information

3. **Use filters and search:**
   - Don't scroll through long lists
   - Use search and filters to find what you need quickly

4. **Organize documents:**
   - Use clear, descriptive names
   - Tag documents appropriately
   - Use the right document areas

5. **Set up notifications:**
   - Stay informed without checking constantly

6. **Learn workflows:**
   - Review [workflow guides](/guide/workflows/) for common tasks

### What should I do before logging out?

- **Save your work:** Ensure all changes are saved
- **Close open documents:** Others may need to edit them
- **Check for notifications:** Address any urgent items

### How often should I change my password?

Follow your organization's password policy. Generally:
- Change every 90 days minimum
- Change immediately if you suspect it's compromised
- Use strong, unique passwords

---

**Still have questions?** Contact your system administrator or check the [Troubleshooting Guide](/guide/troubleshooting).

**Last Updated:** 2025-10-27
