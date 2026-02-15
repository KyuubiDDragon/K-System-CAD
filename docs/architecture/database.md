# Database Architecture

This document provides a comprehensive overview of the K-Systems database schema, table relationships, and data model.

## Database Overview

### Platform

- **DBMS**: MariaDB 10.11+ or MySQL 8.0+
- **Character Set**: UTF-8 (utf8mb4_unicode_ci)
- **Storage Engine**: InnoDB
- **Database Name**: `ksystems`

### Statistics

- **Total Tables**: 109+ tables
- **Table Prefix**: `kdd_` (all tables)
- **Multi-Tenant**: All data tables include `authority_id`
- **Indexes**: Primary keys, foreign keys, and performance indexes

## Schema Organization

### Core System Tables

#### Authority Management

**kdd_authorities**
- Primary authority/tenant definitions
- Columns: `id`, `name`, `display_name`, `description`, `active`

**kdd_authority_features**
- Available system features
- Examples: dispatch, employee, reports, invoice

**kdd_authority_features_rel**
- Maps which features are enabled for each authority
- Links: `authority_id` → `feature_id`

**kdd_authority_fields**
- Custom fields configuration per authority
- Allows authority-specific data customization

### User and Access Control

**kdd_users**
```sql
CREATE TABLE kdd_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,  -- bcrypt hashed
    email VARCHAR(255),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    authority_id INT NOT NULL,
    active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id)
);
```

**kdd_roles**
```sql
CREATE TABLE kdd_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_name_authority (name, authority_id),
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id)
);
```

**kdd_permissions**
```sql
CREATE TABLE kdd_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**kdd_user_roles**
- Links users to their assigned roles
- Many-to-many: users can have multiple roles

**kdd_role_permissions**
- Links roles to their permissions
- Many-to-many: roles can have multiple permissions

### Employee Management

**kdd_employee**
```sql
CREATE TABLE kdd_employee (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_number VARCHAR(50),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    date_of_birth DATE,
    hire_date DATE,
    department_id INT,
    rank_id INT,
    status ENUM('active', 'inactive', 'suspended', 'terminated'),
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (department_id) REFERENCES kdd_employee_departments(id),
    FOREIGN KEY (rank_id) REFERENCES kdd_employee_ranks(id)
);
```

**kdd_employee_departments**
- Department structure (Operations, Administration, etc.)

**kdd_employee_ranks**
- Position/rank definitions (Firefighter, Captain, Chief, etc.)

**kdd_employee_company**
- Company affiliations for employees

**kdd_employee_company_rel**
- Employee-company relationship mapping

### Report System

**kdd_report**
```sql
CREATE TABLE kdd_report (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_number VARCHAR(50),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    report_date DATETIME NOT NULL,
    location VARCHAR(255),
    employee_id INT,
    category_id INT,
    status ENUM('draft', 'submitted', 'approved', 'archived'),
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (employee_id) REFERENCES kdd_employee(id),
    FOREIGN KEY (category_id) REFERENCES kdd_report_categories(id)
);
```

**kdd_report_categories**
- Report categorization (Fire, Medical, Rescue, etc.)

**kdd_report_custom_fields**
- Dynamic custom fields for reports
- Allows authority-specific report data

**kdd_report_custom_field_values**
- Values for custom fields per report

**kdd_report_share**
- Report sharing functionality
- Cross-authority report sharing (when permitted)

### Document Management

**kdd_doc_documents**
```sql
CREATE TABLE kdd_doc_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    mime_type VARCHAR(100),
    area_id INT,
    category_id INT,
    uploaded_by INT,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (area_id) REFERENCES kdd_doc_areas(id),
    FOREIGN KEY (uploaded_by) REFERENCES kdd_users(id)
);
```

**kdd_doc_areas**
- Document organizational areas

**kdd_doc_categories**
- Document categories within areas

**kdd_doc_area_permissions**
- Fine-grained access control for document areas

### Calendar and Events

**kdd_calendar**
```sql
CREATE TABLE kdd_calendar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    all_day TINYINT(1) DEFAULT 0,
    location VARCHAR(255),
    event_type ENUM('meeting', 'training', 'shift', 'holiday', 'other'),
    created_by INT,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (created_by) REFERENCES kdd_users(id)
);
```

**kdd_calendar_assigned**
- User assignments to calendar events
- RSVP status tracking

**kdd_calendar_groups**
- Calendar event groups

**kdd_calendar_group_members**
- Group membership for calendar events

### Messaging System

**kdd_messages**
```sql
CREATE TABLE kdd_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    recipient_id INT,
    group_id INT,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    read_status TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (sender_id) REFERENCES kdd_users(id),
    FOREIGN KEY (recipient_id) REFERENCES kdd_users(id),
    FOREIGN KEY (group_id) REFERENCES kdd_message_groups(id)
);
```

**kdd_message_groups**
- Group messaging functionality

**kdd_message_group_members**
- Group membership for messaging

**kdd_messages_status**
- Message delivery and read status tracking

### Dispatch and Operations

**kdd_dispatch**
```sql
CREATE TABLE kdd_dispatch (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('engine', 'ladder', 'rescue', 'ambulance', 'command', 'hazmat', 'utility'),
    status ENUM('available', 'on_call', 'out_of_service', 'training', 'maintenance'),
    station VARCHAR(100),
    call_sign VARCHAR(50),
    crew_size INT,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id)
);
```

**kdd_dispatch_employees**
- Employee assignments to dispatch units

**kdd_dispatch_vehicles**
- Vehicle assignments to dispatch units

**kdd_crew**
- Alternative crew management structure

**kdd_crew_employee_rel**
- Crew-employee relationships

### Company and Business

**kdd_companies**
```sql
CREATE TABLE kdd_companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    phone VARCHAR(50),
    email VARCHAR(255),
    tax_id VARCHAR(50),
    type_id INT,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (type_id) REFERENCES kdd_company_types(id)
);
```

**kdd_company_types**
- Company type categorization

**kdd_company_history**
- Company interaction history tracking

**kdd_company_extinguishers**
- Fire safety equipment tracking for companies

### Invoice Management

**kdd_invoices**
```sql
CREATE TABLE kdd_invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    company_id INT,
    person_id INT,
    total_amount DECIMAL(10, 2),
    paid_amount DECIMAL(10, 2) DEFAULT 0,
    status ENUM('unpaid', 'partial', 'paid', 'overdue', 'cancelled'),
    notes TEXT,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (company_id) REFERENCES kdd_companies(id)
);
```

**kdd_invoice_entries**
```sql
CREATE TABLE kdd_invoice_entries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES kdd_invoices(id) ON DELETE CASCADE
);
```

### Training Management

**kdd_training**
- Training program definitions

**kdd_training_assigns**
- Training assignments to employees
- Completion status and certification tracking

**kdd_training_certifications**
- Employee certifications

**kdd_training_requirements**
- Required trainings by rank/position

### Todo and Task Management

**kdd_todo_lists**
```sql
CREATE TABLE kdd_todo_lists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    owner_id INT NOT NULL,
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (owner_id) REFERENCES kdd_users(id)
);
```

**kdd_todos**
- Individual tasks within lists

**kdd_todo_checkboxes**
- Sub-tasks/checkboxes within todos

**kdd_todo_permissions**
- User permissions for todo lists

### File Management Systems

**kdd_person_files**
- Civilian/person records
- Personal information database

**kdd_vehicle_files**
- Vehicle registration database
- Vehicle history tracking

**kdd_apartment**
- Residential property records
- Apartment/building management

**kdd_apartment_rel**
- Person-apartment relationships (owner, tenant, landlord)

### Application Management

**kdd_applicant**
```sql
CREATE TABLE kdd_applicant (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    birthdate DATE NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone_number VARCHAR(30),
    status ENUM('pending', 'approved', 'rejected'),
    type INT NOT NULL,  -- 1=firefighter, 2=administration
    interview_date DATE,
    interview_time TIME,
    info TEXT,
    authority_id INT NOT NULL,
    added DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id)
);
```

**kdd_applicant_questions**
- Application form questions

**kdd_applicant_answers**
- Applicant responses to questions

### Communication and Announcements

**kdd_blackboard**
- Announcement/bulletin board system

**kdd_blackboard_rel**
- Blackboard post relationships

**kdd_whiteboard**
- Collaborative whiteboard data

**kdd_whiteboard_rel**
- Whiteboard sharing and permissions

### System Administration

**kdd_templates**
- Document and report templates

**kdd_template_categories**
- Template organization

**kdd_settings**
- System-wide settings

**kdd_desktop_settings**
- User desktop preferences

**kdd_weather**
- Weather widget configuration

### Audit and Logging

**kdd_access_logs**
```sql
CREATE TABLE kdd_access_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table VARCHAR(255) NOT NULL,
    userid INT NOT NULL,
    entry_id INT,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    authority_id INT NOT NULL,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id),
    FOREIGN KEY (userid) REFERENCES kdd_users(id)
);
```

**kdd_database_logs**
- Database change audit trail
- Tracks create, update, delete operations

**kdd_system_logs**
- General system event logging

### Map and Location Services

**kdd_map_markers**
- Geographic markers and locations

**kdd_map_layers**
- Map layer definitions

**kdd_map_settings**
- Map configuration per authority

## Database Relationships

### Key Relationship Patterns

#### One-to-Many Relationships

```
kdd_authorities (1) ─── (N) kdd_users
kdd_authorities (1) ─── (N) kdd_employees
kdd_authorities (1) ─── (N) kdd_reports
kdd_employee (1) ─── (N) kdd_reports
kdd_invoices (1) ─── (N) kdd_invoice_entries
kdd_todo_lists (1) ─── (N) kdd_todos
kdd_todos (1) ─── (N) kdd_todo_checkboxes
```

#### Many-to-Many Relationships

```
users ←→ kdd_user_roles ←→ roles
roles ←→ kdd_role_permissions ←→ permissions
employees ←→ kdd_employee_company_rel ←→ companies
dispatch ←→ kdd_dispatch_employees ←→ employees
calendar ←→ kdd_calendar_assigned ←→ users
```

## Indexing Strategy

### Primary Keys

All tables have auto-incrementing integer primary keys:
```sql
id INT PRIMARY KEY AUTO_INCREMENT
```

### Multi-Tenant Indexes

Every multi-tenant table has an authority index:
```sql
INDEX idx_authority (authority_id)
```

### Composite Indexes

Common query patterns use composite indexes:
```sql
-- Employee queries by authority and status
INDEX idx_authority_status (authority_id, status)

-- Report queries by authority and date
INDEX idx_authority_date (authority_id, report_date)

-- Document queries by authority and area
INDEX idx_authority_area (authority_id, area_id)
```

### Foreign Key Indexes

All foreign key columns are indexed:
```sql
INDEX idx_employee (employee_id)
INDEX idx_user (user_id)
INDEX idx_company (company_id)
```

### Unique Constraints

Prevent duplicate entries:
```sql
-- Username unique within authority
UNIQUE KEY uk_username_authority (username, authority_id)

-- Email unique within authority
UNIQUE KEY uk_email_authority (email, authority_id)

-- Role name unique within authority
UNIQUE KEY uk_role_authority (name, authority_id)
```

## Data Types and Standards

### String Fields

- **VARCHAR(50)**: Codes, short identifiers
- **VARCHAR(100)**: Names, titles
- **VARCHAR(255)**: Longer text, emails, paths
- **TEXT**: Descriptions, notes, content
- **MEDIUMTEXT**: Large text content

### Numeric Fields

- **INT**: IDs, counts, quantities
- **DECIMAL(10,2)**: Currency amounts
- **TINYINT(1)**: Boolean flags (0/1)

### Date and Time

- **DATE**: Date only (YYYY-MM-DD)
- **TIME**: Time only (HH:MM:SS)
- **DATETIME**: Date and time
- **TIMESTAMP**: Auto-updating timestamps

### Enumerations

```sql
-- Status enumerations
status ENUM('active', 'inactive', 'suspended', 'terminated')

-- Type enumerations
type ENUM('engine', 'ladder', 'rescue', 'ambulance')

-- Payment status
status ENUM('unpaid', 'partial', 'paid', 'overdue', 'cancelled')
```

## Query Patterns

### Standard Select with Authority

```sql
SELECT * FROM kdd_employees
WHERE authority_id = ?
AND status = 'active'
ORDER BY last_name, first_name;
```

### Join with Authority Validation

```sql
SELECT r.*, e.first_name, e.last_name
FROM kdd_reports r
JOIN kdd_employees e ON r.employee_id = e.id
WHERE r.authority_id = ?
AND e.authority_id = ?
AND r.report_date >= ?
ORDER BY r.report_date DESC;
```

### Aggregate Queries

```sql
SELECT COUNT(*) as total_reports,
       COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved
FROM kdd_reports
WHERE authority_id = ?
AND report_date >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## Performance Optimization

### Query Optimization Tips

1. **Always filter by authority_id first**
   - Reduces result set early
   - Utilizes authority indexes

2. **Use EXPLAIN to analyze queries**
   ```sql
   EXPLAIN SELECT * FROM kdd_reports WHERE authority_id = 1;
   ```

3. **Limit result sets**
   ```sql
   SELECT * FROM kdd_reports
   WHERE authority_id = ?
   ORDER BY created_at DESC
   LIMIT 100;
   ```

4. **Use EXISTS for checking existence**
   ```sql
   SELECT EXISTS(
     SELECT 1 FROM kdd_users
     WHERE username = ? AND authority_id = ?
   );
   ```

### Connection Pooling

Reuse database connections:
```php
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
```

### Prepared Statements

Always use prepared statements:
```php
$stmt = $pdo->prepare("
    SELECT * FROM kdd_employees
    WHERE authority_id = :aid AND id = :id
");
$stmt->execute([':aid' => $authorityId, ':id' => $employeeId]);
```

## Backup and Recovery

### Backup Strategy

1. **Full Daily Backups**
   ```bash
   mysqldump --single-transaction ksystems > backup_$(date +%Y%m%d).sql
   ```

2. **Authority-Specific Backups**
   ```bash
   mysqldump ksystems \
     --where="authority_id=1" \
     kdd_employees kdd_reports > authority1_backup.sql
   ```

3. **Incremental Binary Logs**
   ```bash
   mysqlbinlog mysql-bin.000001 > incremental.sql
   ```

### Recovery Procedures

```bash
# Full restore
mysql ksystems < backup_20250120.sql

# Point-in-time recovery
mysqlbinlog --stop-datetime="2025-01-20 14:30:00" \
  mysql-bin.000001 | mysql ksystems
```

## Migration Management

### Schema Migrations

Migrations stored in `/backend/migrations/`:

```sql
-- migrations/0001_add_employee_status.sql
ALTER TABLE kdd_employees
ADD COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active';
```

### Version Control

Track schema changes:
```sql
CREATE TABLE kdd_schema_versions (
    version VARCHAR(50) PRIMARY KEY,
    description TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Maintenance Tasks

### Regular Maintenance

1. **Analyze Tables**
   ```sql
   ANALYZE TABLE kdd_employees, kdd_reports, kdd_documents;
   ```

2. **Optimize Tables**
   ```sql
   OPTIMIZE TABLE kdd_messages;
   ```

3. **Check Table Health**
   ```sql
   CHECK TABLE kdd_employees;
   ```

### Cleanup Tasks

```sql
-- Remove old access logs
DELETE FROM kdd_access_logs
WHERE date < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Archive old reports
INSERT INTO kdd_reports_archive
SELECT * FROM kdd_reports
WHERE report_date < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

## Database Security

### User Privileges

```sql
-- Application user (limited privileges)
GRANT SELECT, INSERT, UPDATE, DELETE
ON ksystems.*
TO 'ksystems_app'@'localhost'
IDENTIFIED BY 'secure_password';

-- Read-only reporting user
GRANT SELECT ON ksystems.*
TO 'ksystems_reports'@'localhost';

-- Admin user (full privileges)
GRANT ALL PRIVILEGES ON ksystems.*
TO 'ksystems_admin'@'localhost';
```

### Connection Security

- Use SSL/TLS for database connections
- Restrict access by IP address
- Use strong passwords
- Rotate credentials regularly

The K-Systems database architecture provides a robust, scalable foundation for multi-tenant enterprise management with comprehensive audit trails, flexible data structures, and optimized performance.
