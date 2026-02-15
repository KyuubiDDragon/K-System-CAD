# Vacation & Absence Overview

Employee absence overview showing current and upcoming vacation periods with filtering by timeframes and rank grouping.

## Overview

Features:
- View currently absent employees
- See employees returning within 7/30 days
- See employees with upcoming vacation in 7/30 days
- Filter by rank
- Search employees
- Grouped display by rank

**Required Permission:** `READ_EMPLOYEE`

## Accessing Vacation Overview

**Route:** `/vacation`

**How to Access:**
- Desktop: Double-click **Vacation** icon
- Menu: Start Menu → Employee Management → Vacation
- Or navigate directly to `/vacation`

## Interface Layout

### Filter Buttons

**Absence Status Filters:**
- **Currently Absent** - Employees currently on vacation
- **Returning in 7 Days** - Employees returning from vacation within 7 days
- **Returning in 30 Days** - Employees returning from vacation within 30 days
- **Upcoming in 7 Days** - Employees starting vacation within 7 days
- **Upcoming in 30 Days** - Employees starting vacation within 30 days

**Rank Filter:**
- Filter dropdown to show only specific ranks
- Shows all ranks by default

**Search:**
- Search field to filter employees by name
- Real-time filtering

### Employee Cards

**Card Layout:**
- Employee photo (if available)
- Full name
- Rank
- Vacation period (From - To dates)
- Grouped by rank

**Display Format:**
- Cards organized in rank groups
- Rank header shows rank name
- Employees sorted within each rank

## Using the Vacation Overview

### View Currently Absent Employees

1. Click **Currently Absent** filter button
2. System shows employees on vacation today
3. Cards display vacation start and end dates
4. Grouped by rank

### View Employees Returning Soon

**Returning in 7 Days:**
1. Click **Returning in 7 Days** button
2. Shows employees returning from vacation within next 7 days
3. Useful for planning staffing

**Returning in 30 Days:**
1. Click **Returning in 30 Days** button
2. Shows employees returning from vacation within next 30 days
3. Broader planning view

### View Upcoming Vacations

**Upcoming in 7 Days:**
1. Click **Upcoming in 7 Days** button
2. Shows employees starting vacation within next 7 days
3. Prepare for upcoming absences

**Upcoming in 30 Days:**
1. Click **Upcoming in 30 Days** button
2. Shows employees starting vacation within next 30 days
3. Long-term staffing planning

### Filter by Rank

1. Click rank filter dropdown
2. Select specific rank
3. View updates to show only selected rank
4. Clear filter to see all ranks

### Search for Employee

1. Type employee name in search field
2. List filters in real-time
3. Works across all filter views
4. Clear search to see all results

## How Vacation Data Works

**Data Source:**
- Vacation data comes from employee records
- Managed via Employee Management system
- Not a separate vacation request system

**Setting Vacation:**
- Vacation dates set in Employee Management
- Administrators or HR set vacation periods
- No employee self-service vacation requests

**Calculation:**
- System calculates current status based on today's date
- Compares vacation start/end dates to current date
- Automatically updates daily

## Limitations

**What's NOT Available:**

### Request & Approval System
- ❌ Employee vacation request submission
- ❌ Manager approval workflow
- ❌ Leave balance tracking
- ❌ Leave type selection (sick, personal, etc.)
- ❌ Reason for absence
- ❌ Attachment upload (doctor's notes, etc.)
- ❌ Multi-level approval
- ❌ Denial reasons

### Balance & Accrual
- ❌ Vacation balance display
- ❌ Accrued days tracking
- ❌ Used days vs. remaining
- ❌ Carryover from previous year
- ❌ Automatic accrual rules
- ❌ Prorated vacation for new hires

### Calendar Features
- ❌ Team calendar view
- ❌ Department coverage planning
- ❌ Conflict detection (overlapping vacations)
- ❌ Blackout dates/restricted periods
- ❌ Calendar export (iCal, Google Calendar)

### Substitute Management
- ❌ Assign substitutes during absence
- ❌ Handoff notes
- ❌ Contact person while away

### Notifications
- ❌ Email notifications for approvals
- ❌ Reminders for upcoming vacation
- ❌ Alerts for team members
- ❌ Manager notification of requests

### Reporting
- ❌ Balance reports
- ❌ Usage reports
- ❌ Pending approvals report
- ❌ Coverage gap analysis
- ❌ Accrual reports

### Admin Configuration
- ❌ Leave types configuration
- ❌ Accrual policy settings
- ❌ Approval workflow rules
- ❌ Blackout date management
- ❌ Eligibility rules

**Current Reality:**
This is a **read-only overview** of employee absences based on vacation dates set in the Employee Management system. It is not a full vacation request and approval system.

## Managing Employee Vacation

**To Set Vacation Dates:**
1. Go to Employee Management (`/employee`)
2. Edit employee record
3. Set vacation start and end dates
4. Save employee record
5. Vacation overview automatically updates

**Who Can Set Vacation:**
- Administrators
- Users with `WRITE_EMPLOYEE` permission
- Cannot be set by employees themselves

## Use Cases

**Staffing Coordinator:**
- Check who's currently absent
- Plan coverage for upcoming absences
- See when employees return

**Manager:**
- Monitor team vacation schedule
- Plan projects around absences
- Ensure adequate staffing

**HR:**
- Overview of all department absences
- Identify staffing gaps
- Coordinate vacation schedules

## Tips & Best Practices

**Using Filters Effectively:**
- Use "Currently Absent" for today's staffing
- Use "Returning in 7 Days" for short-term planning
- Use "Upcoming in 30 Days" for long-term planning
- Combine rank filter with date filters for department view

**Planning:**
- Check "Upcoming in 7 Days" weekly
- Review "Upcoming in 30 Days" monthly
- Coordinate with Employee Management for scheduling
- Plan projects around known vacation periods

## Troubleshooting

### Employee Not Showing in List

**Problem:** Employee on vacation not appearing

**Solutions:**
1. Check vacation dates are set in Employee Management
2. Verify dates are in correct format
3. Ensure employee record is active
4. Check filter selection (correct timeframe)
5. Try clearing all filters and searching by name

### Dates Seem Wrong

**Problem:** Vacation dates don't match expectations

**Solutions:**
1. Check employee record in Employee Management
2. Verify vacation start and end dates
3. Ensure dates haven't been changed
4. Check timezone settings if applicable

### Rank Filter Not Working

**Problem:** Rank filter doesn't show expected employees

**Solutions:**
1. Verify employees have correct rank assigned
2. Check rank is spelled correctly in employee records
3. Try clearing filter and reapplying
4. Refresh page

## Related Documentation

- [Employee Management](/guide/employee-management) - Where vacation dates are actually set
- [Calendar](/guide/calendar) - Team calendar and events
- [Getting Started](/guide/getting-started) - K-Systems basics

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
