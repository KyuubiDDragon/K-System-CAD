# Developer Tools

K-Systems includes several powerful developer and debugging tools designed to help developers and administrators monitor, debug, and analyze system behavior. These tools provide deep insights into the application's runtime state, WebSocket connections, and system activity.

::: warning Developer/Admin Only
These tools require developer or administrator permissions. They expose sensitive system information and should only be accessed by authorized personnel.
:::

::: tip User Reference
Looking for the Cheatsheet with 10-codes and emergency numbers? See the [Cheatsheet Guide](./cheatsheet).
:::

## Overview

The developer tools suite includes:

- **Desktop Debug** - Desktop mode debugging and state inspection
- **Socket Debug** - WebSocket connection diagnostics and monitoring
- **System Logs** - Comprehensive audit log viewer (Admin only)

## Desktop Debug

**Access:** `/desktopDebug`

The Desktop Debug tool helps developers troubleshoot desktop mode functionality, which allows K-Systems to run as an embedded application within desktop frameworks or kiosk modes.

### What is Desktop Mode?

Desktop mode is a special display mode where:
- Navigation elements can be hidden
- The UI adapts for embedded/kiosk display
- URL parameters control the interface state
- CSS classes are dynamically applied to the HTML element

### Features

#### 1. **Current Mode Display**
Shows whether desktop mode is currently enabled or disabled.

#### 2. **Query Parameter Inspector**
Displays all URL query parameters that affect desktop mode:
- `desktop` - Enable/disable desktop mode
- `hideLeftNav` - Hide left navigation sidebar
- `hideTopNav` - Hide top navigation bar

#### 3. **CSS Class Inspector**
Shows which CSS classes are currently applied:
- `desktop-mode` - Main desktop mode class
- `hide-left-nav` - Left navigation hidden
- `hide-top-nav` - Top navigation hidden

#### 4. **UI Store State Viewer**
Displays the complete UI store state in JSON format, showing:
- `isDesktopMode` - Current desktop mode status
- Other UI-related state variables

### Actions

**Toggle Desktop Mode**
- Manually toggle desktop mode on/off
- Applies/removes the `desktop-mode` CSS class
- Updates the UI store state

**Refresh Page**
- Reloads the entire page
- Useful after making configuration changes

**Open with Desktop Parameters**
- Opens the current page with desktop mode query parameters
- Sets `desktop=true`, `hideLeftNav=false`, `hideTopNav=false`
- Useful for testing desktop mode behavior

### Use Cases

- **Debugging Layout Issues** - Verify CSS classes are applied correctly
- **Testing Embedded Scenarios** - Simulate kiosk or embedded display modes
- **State Validation** - Ensure desktop mode state persists correctly
- **Integration Testing** - Test desktop mode with different parameter combinations

### Example URL Parameters

```
# Enable desktop mode with hidden navigation
/?desktop=true&hideLeftNav=true&hideTopNav=true

# Desktop mode with visible navigation
/?desktop=true&hideLeftNav=false&hideTopNav=false

# Disable desktop mode
/?desktop=false
```

## Socket Debug

**Access:** `/socketDebug`

The Socket Debug tool provides comprehensive diagnostics for WebSocket (Socket.io) connections, which power K-Systems' real-time features like chat, notifications, and collaborative whiteboard.

### Architecture Overview

K-Systems uses multiple Socket.io namespaces:
- `/chat` - Real-time chat messaging
- `/notification` - System notifications
- `/status` - User online/offline status
- `/whiteboard` - Collaborative whiteboard drawing

### Features

#### 1. **Connection Status Panel**
Displays the current status of all socket connections:
- **Connected** (green) - Socket is active and working
- **Disconnected** (gray) - Socket is not connected
- **Reconnecting** (orange) - Socket is attempting to reconnect
- **Error** (red) - Connection error occurred

Each connection card shows:
- Socket namespace name
- Current status
- Socket ID (when connected)

#### 2. **Authentication Panel**
Displays JWT token information:
- **Token Preview** - First/last characters of the token
- **Source** - Where the token was retrieved from (localStorage, cookie, etc.)
- **Validity** - Whether the token is valid
- **Cookie Status** - Authentication cookie presence

#### 3. **Connection Test Panel**
Shows results from connection tests:
- Test status (success, timeout, error)
- Test messages and responses
- Timestamp of each test

#### 4. **Error Panel**
Displays recent connection errors:
- Error message details
- Socket namespace that encountered the error
- Timestamp of the error

#### 5. **Network Status Panel**
Shows network connectivity information:
- **Server Reachable** - Whether the socket server is accessible
- **Socket Server URL** - The configured socket server endpoint
- **Browser URL** - Current browser location

#### 6. **Environment Panel**
Displays environment configuration:
- Socket server URL from environment variables
- API URL configuration
- Other relevant environment settings

#### 7. **Manual Testing Panel**
Provides console commands for advanced debugging:

```javascript
// Show diagnostic UI
window.socketHelper.runDiagnostic()

// Test token retrieval
window.socketHelper.testToken()

// Test manual connection
window.socketHelper.testManualConnection()

// Reset and reconnect all sockets
window.socketHelper.resetAndReconnect()
```

### Actions

**Initialize Connections**
- Manually trigger socket initialization
- Useful after configuration changes

**Test All Connections**
- Sends test events to all socket namespaces
- Validates bidirectional communication
- Times out after 5 seconds per connection

**Reset All Connections**
- Disconnects all sockets
- Clears socket references
- Reinitializes connections after 1 second delay

**Test CORS**
- Checks Cross-Origin Resource Sharing configuration
- Validates that the browser can communicate with the socket server

**Check Network**
- Tests network connectivity
- Validates server accessibility

### Common Issues and Solutions

#### Issue: All Sockets Disconnected
**Symptoms:** All connections show "disconnected" status

**Solutions:**
1. Check that socket server is running (port 3001 by default)
2. Verify `VITE_SOCKET_URL` environment variable
3. Click "Initialize Connections" to retry
4. Check browser console for connection errors

#### Issue: Authentication Failed
**Symptoms:** Connections fail immediately after connecting

**Solutions:**
1. Verify JWT token is present in Authentication panel
2. Check token validity
3. Log out and log back in to get a fresh token
4. Verify socket server authentication middleware

#### Issue: CORS Errors
**Symptoms:** Browser console shows CORS-related errors

**Solutions:**
1. Click "Test CORS" to verify configuration
2. Check socket server CORS settings in `/socket-server/index.js`
3. Ensure frontend URL is in allowed origins list

#### Issue: Intermittent Disconnections
**Symptoms:** Sockets repeatedly connect and disconnect

**Solutions:**
1. Check network stability
2. Review server logs for errors
3. Verify firewall/proxy settings
4. Check for WebSocket protocol support

### Auto-Refresh

The Socket Debug view automatically refreshes connection status every 3 seconds, providing real-time monitoring without manual intervention.

## System Logs (Admin)

**Access:** `/admin/logs`
**Permission Required:** `SYSTEM_ADMIN`

The System Logs viewer provides comprehensive audit trail functionality, tracking all database changes and user actions within K-Systems.

### Overview

Every database operation (INSERT, UPDATE, DELETE) is automatically logged to the `system_logs` table, creating a complete audit trail of system activity. This is essential for:
- Compliance and regulatory requirements
- Security auditing
- Troubleshooting data issues
- Understanding user behavior
- Tracking system changes

### Features

#### 1. **Statistics Dashboard**

Collapsible statistics panel showing:

**Total Logs Count**
- Total number of log entries in the system

**Top Users**
- Users with the most logged actions
- Shows username and action count
- Expandable to show all users

**Action Types**
- Breakdown by action type (INSERT, UPDATE, DELETE, LOGIN)
- Color-coded chips for quick identification
- Count for each action type

**Top Tables**
- Most frequently modified database tables
- Useful for identifying high-activity areas

#### 2. **Advanced Filtering**

**Date Range Filters**
- Start date - Filter logs from this date forward
- End date - Filter logs up to this date
- Visual date picker for easy selection

**Action Type Filter**
- Filter by specific action (INSERT, UPDATE, DELETE, LOGIN)
- Dynamically populated from actual log data

**Table Name Filter**
- Filter by specific database table
- Dynamically populated from actual log data

**Authority Filter** (System Admin only)
- Filter logs by organization/authority
- Useful in multi-tenant environments

**Search**
- Free-text search across all log fields
- Searches usernames, table names, values, etc.

#### 3. **Data Table**

Paginated table with sortable columns:

| Column | Description |
|--------|-------------|
| ID | Unique log entry identifier |
| Action | Type of action (INSERT, UPDATE, DELETE) |
| Table | Database table affected |
| User | Username who performed the action |
| Column | Specific column that was modified |
| Record ID | Database record identifier |
| Timestamp | When the action occurred |
| Details | Button to view full log details |

**Table Features:**
- Sortable by any column
- Pagination (25, 50, or 100 items per page)
- Loading skeleton while fetching
- Empty state with helpful message

#### 4. **Log Details Dialog**

Click the eye icon to view complete log details:

**Basic Information:**
- Log ID
- Timestamp (formatted in German locale)
- User (username and ID)
- Action type (color-coded chip)
- Table name
- Record ID
- Column name (if applicable)
- Authority ID (if applicable)

**Value Comparison:**
- **Old Value** - Previous data before change
- **New Value** - New data after change
- JSON formatting for complex data
- Side-by-side comparison for UPDATE actions

#### 5. **Export Functionality**

**CSV Export**
- Exports filtered logs to CSV format
- Respects all active filters
- Suitable for external analysis in Excel, Google Sheets, etc.
- Includes all log fields

#### 6. **Log Deletion** (System Admin only)

**Clear Logs Dialog**

Two deletion modes:

**Delete All Logs**
- Removes all log entries from the system
- Cannot be undone

**Delete Logs Before Date**
- Removes logs older than specified date
- Useful for periodic cleanup
- Retains recent logs for active monitoring

::: danger Warning
Log deletion is permanent and cannot be undone. Always export logs before deletion if you need to retain the data.
:::

### Color Coding

Actions are color-coded for quick identification:

- **INSERT** - Green (success) - New record created
- **UPDATE** - Blue (info) - Existing record modified
- **DELETE** - Red (error) - Record deleted
- **LOGIN** - Orange (warning) - User login event

### Use Cases

#### Compliance Auditing
Track all changes for regulatory compliance requirements (GDPR, HIPAA, SOX, etc.)

#### Security Investigation
Investigate suspicious activity:
1. Filter by specific user
2. Review all their recent actions
3. Check for unauthorized data access
4. Verify login times and patterns

#### Data Recovery
Identify what was changed:
1. Filter by table name and record ID
2. View old value before change
3. Manually restore data if needed

#### System Monitoring
Monitor system health:
1. Review top tables for unusual activity
2. Check for error patterns
3. Identify high-volume users
4. Track system usage trends

#### Troubleshooting
Debug data issues:
1. Find when a record was last modified
2. See who made the change
3. Compare old vs. new values
4. Trace the sequence of changes

### Performance Considerations

The System Logs table can grow very large in active systems. Consider:

- **Regular Cleanup** - Delete old logs periodically (e.g., older than 1 year)
- **Archiving** - Export and archive old logs to external storage
- **Indexing** - Database indexes on frequently queried columns (user_id, table_name, timestamp)
- **Filtering** - Always use date range filters for large datasets

### Database Schema

::: danger Not Implemented
The `system_logs` table does **not currently exist** in the database. This is a proposed schema that would need to be created along with database triggers to capture changes.
:::

Proposed `system_logs` table structure:

```sql
CREATE TABLE system_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  action VARCHAR(50),           -- INSERT, UPDATE, DELETE, LOGIN
  table_name VARCHAR(100),      -- Database table affected
  record_id INT,                -- Record ID in the affected table
  user_id INT,                  -- User who performed the action
  column_name VARCHAR(100),     -- Column that was modified
  old_value TEXT,               -- Previous value (JSON)
  new_value TEXT,               -- New value (JSON)
  timestamp TIMESTAMP,          -- When the action occurred
  authority_id INT,             -- Organization/authority ID
  INDEX idx_timestamp (timestamp),
  INDEX idx_user_id (user_id),
  INDEX idx_table_name (table_name)
);
```

## Security Considerations

::: warning Security Notice
Developer tools expose sensitive system information and should be protected:
:::

### Access Control

1. **Permission-Based Access**
   - Desktop Debug: Developer permission recommended
   - Socket Debug: Developer permission recommended
   - System Logs: `SYSTEM_ADMIN` permission required

2. **Production Environment**
   - Consider disabling developer tools in production
   - Or restrict to specific IP addresses
   - Use VPN for remote access

3. **Sensitive Data**
   - System Logs may contain sensitive user data
   - Old/new values may include passwords, emails, personal information
   - Ensure proper access controls are in place
   - Consider data masking for sensitive fields

### Best Practices

1. **Limit Access**
   - Only grant developer/admin permissions to trusted users
   - Regularly audit who has access
   - Remove access when employees leave

2. **Monitor Usage**
   - Log who accesses developer tools
   - Track what actions are performed
   - Alert on suspicious activity

3. **Data Protection**
   - Encrypt system logs at rest
   - Use HTTPS for all communications
   - Implement proper JWT token security

4. **Regular Reviews**
   - Periodically review system logs
   - Check for unauthorized access
   - Audit configuration changes

## Troubleshooting

### Desktop Debug Issues

**Problem:** Desktop mode not activating
**Solution:** Check URL parameters, verify UI store is working

**Problem:** CSS classes not applying
**Solution:** Check browser console for errors, verify CSS files loaded

### Socket Debug Issues

**Problem:** Socket Debug page is blank
**Solution:** Check that socket diagnostic plugin is initialized

**Problem:** Tests always timeout
**Solution:** Verify socket server is running and accessible

**Problem:** Token shows as "not found"
**Solution:** Log out and log back in to get a fresh JWT token

### System Logs Issues

**Problem:** Logs not appearing
**Solution:** Verify `SYSTEM_ADMIN` permission, check database triggers

**Problem:** Stats not loading
**Solution:** Check browser console, verify backend API is responding

**Problem:** Export fails
**Solution:** Check browser popup blocker, verify file permissions

## Limitations

### Missing Features

The following features are documented but **not currently implemented**:

**System Logs:**
- **Database schema** - The `system_logs` table does not exist in the database
- **Automatic logging** - No database triggers exist for INSERT/UPDATE/DELETE operations
- **Audit trail functionality** - System logs are not being captured
- **CSV export** - Export functionality is not implemented in the backend
- **Log deletion** - Clear logs functionality is not implemented
- **Statistics dashboard** - Backend does not provide statistics endpoints

The System Logs view exists in the frontend (`frontend/src/views/admin/LogsView.vue`) but the backend infrastructure to support it is not implemented.

**Related Documentation:**
- Authentication guide does not exist (`./authentication.md`)
- WebSocket guide does not exist (`./websocket.md`)
- Permissions guide does not exist (`./permissions.md`)
- Multi-tenant guide does not exist (`./multi-tenant.md`)

## Related Documentation

- [Cheatsheet Guide](./cheatsheet.md) - Emergency codes and reference information

## API Reference

### System Logs Endpoints

::: warning Not Implemented
The System Logs API endpoints documented below are **not currently implemented**. The backend file exists at `/backend/admin/logs.php` but does not contain the complete functionality described here.
:::

```http
GET /admin/logs?action=getLogEntries
Parameters:
  - page: number
  - limit: number
  - start_date: YYYY-MM-DD
  - end_date: YYYY-MM-DD
  - action: INSERT|UPDATE|DELETE|LOGIN
  - table_name: string
  - authority_id: number
  - search: string

GET /admin/logs?action=getLogStats
Parameters:
  - startDate: YYYY-MM-DD
  - endDate: YYYY-MM-DD
  - searchTerm: string
  - authorityId: number

POST /admin/logs
Body:
  - action: "clearLogs"
  - mode: "all" | "date"
  - beforeDate: YYYY-MM-DD (if mode=date)

GET /admin/logs?action=getLogEntries&format=csv
(Returns CSV file download)
```

## Developer Notes

### Adding Socket Namespaces

When adding new socket namespaces:

1. Add the namespace to `/socket-server/index.js`
2. Initialize in `/frontend/src/plugins/socket.js` (note: TypeScript file does not exist, use `.js`)
3. Register with diagnostic tool in `/frontend/src/plugins/socket-diagnostic.js`
4. Socket Debug will automatically detect and monitor it

### Custom Log Fields

::: warning Not Implemented
This section describes functionality that does not currently exist. The system logs infrastructure would need to be implemented first.
:::

To track additional information in system logs:

1. Add columns to `system_logs` table
2. Update database triggers to populate new fields
3. Modify `/backend/admin/logs.php` to return new fields
4. Update frontend components to display new fields

---

**Last Updated:** 2025-10-02
**Version:** 1.0.1
