-- Migration: Create dashboard layout table for customizable dashboards
-- Date: 2025-10-28
-- Description: Allows users to save custom dashboard layouts with widget positions and configurations
-- Includes export/import functionality support

-- Create dashboard_layout table
CREATE TABLE IF NOT EXISTS kdd_dashboard_layout (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    authority_id INT NOT NULL,

    -- Layout configuration (vue-grid-layout format)
    layout JSON NOT NULL COMMENT 'Widget positions and sizes in grid format',

    -- Active widgets and their settings
    widgets JSON NOT NULL COMMENT 'Widget types and individual widget configurations',

    -- Template information
    template VARCHAR(50) DEFAULT 'custom' COMMENT 'Template type: custom, dispatcher, hr_manager, report_manager, member, admin',
    is_template TINYINT(1) DEFAULT 0 COMMENT 'Whether this layout is saved as a template',
    template_name VARCHAR(100) NULL COMMENT 'Name for saved templates',
    template_description TEXT NULL COMMENT 'Description of the template',

    -- Sharing and export
    is_public TINYINT(1) DEFAULT 0 COMMENT 'Whether this template can be imported by other users',
    export_hash VARCHAR(64) NULL UNIQUE COMMENT 'Unique hash for export/import identification',

    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES kdd_users(id) ON DELETE CASCADE,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id) ON DELETE CASCADE,

    -- Ensure one active layout per user
    UNIQUE KEY unique_user_dashboard (user_id, authority_id),

    -- Index for template lookup
    INDEX idx_template (is_template, is_public),
    INDEX idx_export_hash (export_hash)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User dashboard layouts with widget configurations';

-- Create dashboard_templates table for pre-built templates
CREATE TABLE IF NOT EXISTS kdd_dashboard_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(50) NOT NULL UNIQUE COMMENT 'Unique identifier: dispatcher, hr_manager, etc.',
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'mdi-view-dashboard',

    -- Required permissions to use this template
    required_permissions JSON COMMENT 'Array of permission names required',

    -- Layout and widgets configuration
    layout JSON NOT NULL,
    widgets JSON NOT NULL,

    -- Category and ordering
    category VARCHAR(50) DEFAULT 'general',
    sort_order INT DEFAULT 0,

    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pre-built dashboard templates';

-- Insert default templates
INSERT INTO kdd_dashboard_templates (template_key, name, description, icon, required_permissions, layout, widgets, category, sort_order) VALUES
('dispatcher', 'Dispatcher Dashboard', 'Optimized for dispatch operations with crew, vehicle, and map widgets', 'mdi-truck-fast',
    '["READ_DISPATCH", "READ_VEHICLE", "READ_CREW"]',
    '[
        {"i": "quick-dispatch", "x": 0, "y": 0, "w": 12, "h": 4},
        {"i": "vehicle-status", "x": 0, "y": 4, "w": 4, "h": 3},
        {"i": "calendar-widget", "x": 4, "y": 4, "w": 4, "h": 3},
        {"i": "weather-widget", "x": 8, "y": 4, "w": 4, "h": 3},
        {"i": "blackboard-feed", "x": 0, "y": 7, "w": 8, "h": 3},
        {"i": "active-users", "x": 8, "y": 7, "w": 4, "h": 3}
    ]',
    '{
        "quick-dispatch": {"type": "quick-dispatch", "title": "Quick Dispatch", "refreshInterval": 30},
        "vehicle-status": {"type": "vehicle-status", "title": "Vehicle Status", "showMap": true},
        "calendar-widget": {"type": "calendar-widget", "title": "Calendar", "daysAhead": 7},
        "weather-widget": {"type": "weather-widget", "title": "Weather", "location": "authority"},
        "blackboard-feed": {"type": "blackboard-feed", "title": "Announcements", "maxItems": 5},
        "active-users": {"type": "active-users", "title": "Active Users", "showStatus": true}
    }',
    'operational', 1
),

('hr_manager', 'HR Manager Dashboard', 'Employee management, vacations, applications, and training overview', 'mdi-account-tie',
    '["READ_EMPLOYEE"]',
    '[
        {"i": "employee-overview", "x": 0, "y": 0, "w": 6, "h": 4},
        {"i": "vacation-calendar", "x": 6, "y": 0, "w": 6, "h": 4},
        {"i": "training-progress", "x": 0, "y": 4, "w": 6, "h": 3},
        {"i": "pending-applications", "x": 6, "y": 4, "w": 6, "h": 3},
        {"i": "active-users", "x": 0, "y": 7, "w": 4, "h": 2},
        {"i": "recent-documents", "x": 4, "y": 7, "w": 8, "h": 2}
    ]',
    '{
        "employee-overview": {"type": "employee-overview", "title": "Employee Overview", "chartType": "donut"},
        "vacation-calendar": {"type": "vacation-calendar", "title": "Vacation Calendar", "showTimeline": true},
        "training-progress": {"type": "training-progress", "title": "Training Progress", "showPercentage": true},
        "pending-applications": {"type": "pending-applications", "title": "Applications", "maxItems": 5},
        "active-users": {"type": "active-users", "title": "Active Users"},
        "recent-documents": {"type": "recent-documents", "title": "Recent Documents", "maxItems": 5}
    }',
    'management', 2
),

('report_manager', 'Report Manager Dashboard', 'Report analytics, statistics, and quick report creation', 'mdi-file-chart',
    '["READ_REPORT"]',
    '[
        {"i": "report-analytics", "x": 0, "y": 0, "w": 8, "h": 5},
        {"i": "open-reports", "x": 8, "y": 0, "w": 4, "h": 5},
        {"i": "report-trend", "x": 0, "y": 5, "w": 6, "h": 4},
        {"i": "quick-report", "x": 6, "y": 5, "w": 6, "h": 4},
        {"i": "calendar-widget", "x": 0, "y": 9, "w": 6, "h": 3},
        {"i": "messages-feed", "x": 6, "y": 9, "w": 6, "h": 3}
    ]',
    '{
        "report-analytics": {"type": "report-analytics", "title": "Report Analytics", "groupBy": "category"},
        "open-reports": {"type": "open-reports", "title": "Open Reports", "filterBy": "status"},
        "report-trend": {"type": "report-trend", "title": "Report Trend", "period": "30days"},
        "quick-report": {"type": "quick-report", "title": "Quick Report", "showTemplates": true},
        "calendar-widget": {"type": "calendar-widget", "title": "Calendar"},
        "messages-feed": {"type": "messages-feed", "title": "Messages", "maxItems": 5}
    }',
    'management', 3
),

('member', 'Member Dashboard', 'Personal dashboard with calendar, messages, todos, and weather', 'mdi-account',
    '["CAN_LOGIN"]',
    '[
        {"i": "welcome-banner", "x": 0, "y": 0, "w": 12, "h": 2},
        {"i": "calendar-widget", "x": 0, "y": 2, "w": 6, "h": 4},
        {"i": "my-vacations", "x": 6, "y": 2, "w": 6, "h": 4},
        {"i": "messages-feed", "x": 0, "y": 6, "w": 6, "h": 4},
        {"i": "todo-list", "x": 6, "y": 6, "w": 6, "h": 4},
        {"i": "weather-widget", "x": 0, "y": 10, "w": 12, "h": 3}
    ]',
    '{
        "welcome-banner": {"type": "welcome-banner", "title": "Welcome", "showStats": true},
        "calendar-widget": {"type": "calendar-widget", "title": "My Calendar", "daysAhead": 7},
        "my-vacations": {"type": "my-vacations", "title": "My Vacations", "showHistory": false},
        "messages-feed": {"type": "messages-feed", "title": "Messages", "maxItems": 10},
        "todo-list": {"type": "todo-list", "title": "My Tasks", "showCompleted": false},
        "weather-widget": {"type": "weather-widget", "title": "Weather", "showForecast": true}
    }',
    'personal', 4
),

('admin', 'Admin Dashboard', 'System administration with user activity, health monitoring, and logs', 'mdi-shield-crown',
    '["ADMIN_READ_USERS", "SYSTEM_ADMIN"]',
    '[
        {"i": "system-health", "x": 0, "y": 0, "w": 12, "h": 4},
        {"i": "user-activity", "x": 0, "y": 4, "w": 6, "h": 4},
        {"i": "feature-usage", "x": 6, "y": 4, "w": 6, "h": 4},
        {"i": "recent-logs", "x": 0, "y": 8, "w": 6, "h": 4},
        {"i": "authority-overview", "x": 6, "y": 8, "w": 6, "h": 4},
        {"i": "permission-summary", "x": 0, "y": 12, "w": 6, "h": 3},
        {"i": "db-status", "x": 6, "y": 12, "w": 6, "h": 3}
    ]',
    '{
        "system-health": {"type": "system-health", "title": "System Health", "showMetrics": true},
        "user-activity": {"type": "user-activity", "title": "User Activity", "period": "24h"},
        "feature-usage": {"type": "feature-usage", "title": "Feature Usage", "chartType": "bar"},
        "recent-logs": {"type": "recent-logs", "title": "Recent Logs", "maxItems": 10, "filterLevel": "all"},
        "authority-overview": {"type": "authority-overview", "title": "Authorities", "showStats": true},
        "permission-summary": {"type": "permission-summary", "title": "Permissions"},
        "db-status": {"type": "db-status", "title": "Database Status", "showSize": true}
    }',
    'administration', 5
);

-- Create audit log for dashboard changes
CREATE TABLE IF NOT EXISTS kdd_dashboard_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    authority_id INT NOT NULL,
    action VARCHAR(50) NOT NULL COMMENT 'created, updated, deleted, exported, imported, template_applied',

    -- Change details
    layout_id INT NULL,
    template_key VARCHAR(50) NULL,
    changes JSON COMMENT 'Details of what changed',

    -- Import/Export tracking
    imported_from VARCHAR(64) NULL COMMENT 'Export hash if imported from another user',

    ip_address VARCHAR(45),
    user_agent TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES kdd_users(id) ON DELETE CASCADE,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id) ON DELETE CASCADE,
    FOREIGN KEY (layout_id) REFERENCES kdd_dashboard_layout(id) ON DELETE SET NULL,

    INDEX idx_user_action (user_id, action),
    INDEX idx_created_at (created_at)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit log for dashboard layout changes';

-- Add sample comment for documentation
-- Usage:
-- 1. Users get automatic dashboard based on their permissions (via kdd_dashboard_templates)
-- 2. Users can customize their dashboard (saved in kdd_dashboard_layout)
-- 3. Users can export their layout (generates export_hash)
-- 4. Other users can import using the export hash
-- 5. All changes are logged in kdd_dashboard_audit
