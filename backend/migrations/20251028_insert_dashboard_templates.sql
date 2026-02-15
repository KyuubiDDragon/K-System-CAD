-- Migration: Insert default dashboard templates
-- Date: 2025-10-28
-- Description: Inserts pre-built dashboard templates for different user roles

-- Clear existing templates (for re-running migration)
DELETE FROM kdd_dashboard_templates WHERE template_key IN ('dispatcher', 'hr_manager', 'report_manager', 'member', 'employee', 'admin');

-- Insert default templates
INSERT INTO kdd_dashboard_templates (template_key, name, description, icon, required_permissions, layout, widgets, category, sort_order) VALUES

-- 1. Dispatcher Dashboard
('dispatcher', 'Dispatcher Dashboard', 'Optimized for dispatch operations with crew, vehicle, and map widgets', 'mdi-truck-fast',
'["READ_DISPATCH", "READ_VEHICLE", "READ_CREW"]',
'[{"i": "quick-dispatch", "x": 0, "y": 0, "w": 12, "h": 4}, {"i": "vehicle-status", "x": 0, "y": 4, "w": 4, "h": 5}, {"i": "calendar-widget", "x": 4, "y": 4, "w": 4, "h": 6}, {"i": "weather-widget", "x": 8, "y": 4, "w": 4, "h": 5}, {"i": "blackboard-feed", "x": 0, "y": 10, "w": 8, "h": 5}, {"i": "active-users", "x": 8, "y": 10, "w": 4, "h": 5}]',
'{"quick-dispatch": {"type": "quick-dispatch", "title": "Quick Dispatch", "refreshInterval": 30}, "vehicle-status": {"type": "vehicle-status", "title": "Vehicle Status", "showMap": true}, "calendar-widget": {"type": "calendar-widget", "title": "Calendar", "daysAhead": 7}, "weather-widget": {"type": "weather-widget", "title": "Weather", "location": "authority"}, "blackboard-feed": {"type": "blackboard-feed", "title": "Announcements", "maxItems": 5}, "active-users": {"type": "active-users", "title": "Active Users", "showStatus": true}}',
'operational', 1),

-- 2. HR Manager Dashboard
('hr_manager', 'HR Manager Dashboard', 'Employee management, vacations, applications, and training overview', 'mdi-account-tie',
'["READ_EMPLOYEE"]',
'[{"i": "employee-overview", "x": 0, "y": 0, "w": 6, "h": 6}, {"i": "vacation-calendar", "x": 6, "y": 0, "w": 6, "h": 6}, {"i": "active-users", "x": 0, "y": 6, "w": 4, "h": 5}, {"i": "recent-documents", "x": 4, "y": 6, "w": 8, "h": 5}]',
'{"employee-overview": {"type": "employee-overview", "title": "Employee Overview", "chartType": "donut"}, "vacation-calendar": {"type": "vacation-calendar", "title": "Vacation Calendar", "showTimeline": true}, "active-users": {"type": "active-users", "title": "Active Users"}, "recent-documents": {"type": "recent-documents", "title": "Recent Documents", "maxItems": 5}}',
'management', 2),

-- 3. Report Manager Dashboard
('report_manager', 'Report Manager Dashboard', 'Report analytics, statistics, and quick report creation', 'mdi-file-chart',
'["READ_REPORT"]',
'[{"i": "report-analytics", "x": 0, "y": 0, "w": 8, "h": 5}, {"i": "open-reports", "x": 8, "y": 0, "w": 4, "h": 5}, {"i": "calendar-widget", "x": 0, "y": 5, "w": 6, "h": 6}, {"i": "messages-feed", "x": 6, "y": 5, "w": 6, "h": 6}]',
'{"report-analytics": {"type": "report-analytics", "title": "Report Analytics", "groupBy": "category"}, "open-reports": {"type": "open-reports", "title": "Open Reports", "filterBy": "status"}, "calendar-widget": {"type": "calendar-widget", "title": "Calendar"}, "messages-feed": {"type": "messages-feed", "title": "Messages", "maxItems": 5}}',
'management', 3),

-- 4. Member Dashboard
('member', 'Member Dashboard', 'Personal dashboard with calendar, messages, todos, and weather', 'mdi-account',
'["CAN_LOGIN"]',
'[{"i": "welcome-banner", "x": 0, "y": 0, "w": 12, "h": 3}, {"i": "calendar-widget", "x": 0, "y": 3, "w": 6, "h": 6}, {"i": "my-vacations", "x": 6, "y": 3, "w": 6, "h": 6}, {"i": "messages-feed", "x": 0, "y": 9, "w": 6, "h": 6}, {"i": "todo-list", "x": 6, "y": 9, "w": 6, "h": 6}, {"i": "weather-widget", "x": 0, "y": 15, "w": 12, "h": 5}]',
'{"welcome-banner": {"type": "welcome-banner", "title": "Welcome", "showStats": true}, "calendar-widget": {"type": "calendar-widget", "title": "My Calendar", "daysAhead": 7}, "my-vacations": {"type": "my-vacations", "title": "My Vacations", "showHistory": false}, "messages-feed": {"type": "messages-feed", "title": "Messages", "maxItems": 10}, "todo-list": {"type": "todo-list", "title": "My Tasks", "showCompleted": false}, "weather-widget": {"type": "weather-widget", "title": "Weather", "showForecast": true}}',
'personal', 4),

-- 5. Employee Dashboard (Typical employee with crew assignment)
('employee', 'Employee Dashboard', 'Typical employee dashboard with crew info, announcements, calendar, and personal widgets', 'mdi-account-hard-hat',
'["CAN_LOGIN"]',
'[{"i": "welcome-banner", "x": 0, "y": 0, "w": 12, "h": 6}, {"i": "my-crew", "x": 0, "y": 6, "w": 6, "h": 6}, {"i": "blackboard-feed", "x": 6, "y": 6, "w": 6, "h": 5}, {"i": "my-vacations", "x": 0, "y": 12, "w": 6, "h": 6}, {"i": "calendar-widget", "x": 6, "y": 12, "w": 6, "h": 6}, {"i": "messages-feed", "x": 0, "y": 18, "w": 6, "h": 6}, {"i": "todo-list", "x": 6, "y": 18, "w": 6, "h": 6}, {"i": "weather-widget", "x": 0, "y": 24, "w": 12, "h": 5}]',
'{"welcome-banner": {"type": "welcome-banner", "title": "Welcome", "showStats": true, "showAvatar": false}, "my-crew": {"type": "my-crew", "title": "My Crew", "showMembers": true}, "blackboard-feed": {"type": "blackboard-feed", "title": "Announcements", "boardType": "employee", "maxItems": 1}, "my-vacations": {"type": "my-vacations", "title": "My Vacations", "showHistory": false}, "calendar-widget": {"type": "calendar-widget", "title": "My Calendar", "daysAhead": 7}, "messages-feed": {"type": "messages-feed", "title": "Messages", "maxItems": 10}, "todo-list": {"type": "todo-list", "title": "My Tasks", "showCompleted": false}, "weather-widget": {"type": "weather-widget", "title": "Weather", "showForecast": true}}',
'personal', 4),

-- 6. Admin Dashboard
('admin', 'Admin Dashboard', 'System administration with user activity, health monitoring, and logs', 'mdi-shield-crown',
'["ADMIN_READ_USERS", "SYSTEM_ADMIN"]',
'[{"i": "system-health", "x": 0, "y": 0, "w": 12, "h": 4}, {"i": "user-activity", "x": 0, "y": 4, "w": 6, "h": 6}, {"i": "recent-logs", "x": 6, "y": 4, "w": 6, "h": 6}]',
'{"system-health": {"type": "system-health", "title": "System Health", "showMetrics": true}, "user-activity": {"type": "user-activity", "title": "User Activity", "period": "24h"}, "recent-logs": {"type": "recent-logs", "title": "Recent Logs", "maxItems": 10, "filterLevel": "all"}}',
'administration', 6);
