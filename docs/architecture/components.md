# System Components

This document provides a comprehensive overview of the K-Systems architecture and its major components.

## System Architecture Overview

K-Systems is a modern, multi-tenant enterprise management platform built with a three-tier architecture:

```
┌─────────────────┐
│   Frontend      │  Vue 3 + Vuetify 3
│   (Port 5173)   │  TypeScript + Pinia
└────────┬────────┘
         │ HTTP/WebSocket
┌────────┴────────┐
│   Backend API   │  PHP 8.4 + Apache
│   (Port 8080)   │  JWT Authentication
└────────┬────────┘
         │ PDO
┌────────┴────────┐
│   Database      │  MariaDB/MySQL
│   (Port 3306)   │  Multi-tenant Schema
└─────────────────┘
         │
┌────────┴────────┐
│ Socket Server   │  Node.js + Socket.io
│   (Port 3001)   │  Real-time Events
└─────────────────┘
```

## Frontend Architecture

### Technology Stack

- **Framework**: Vue 3 with Composition API
- **UI Library**: Vuetify 3 (Material Design)
- **Language**: TypeScript
- **State Management**: Pinia stores
- **Build Tool**: Vite
- **Routing**: Vue Router 4
- **Internationalization**: Vue I18n

### Frontend Structure

```
frontend/
├── public/
│   ├── img/               # Static images including bg.jpg
│   └── locales/           # Translation files
├── src/
│   ├── api.ts            # Centralized API client
│   ├── router.ts         # Application routing
│   ├── main.ts           # Application entry point
│   ├── App.vue           # Root component
│   ├── components/       # Reusable Vue components
│   ├── views/            # Feature-based views
│   ├── stores/           # Pinia state stores
│   ├── composables/      # Composition API helpers
│   ├── locales/          # i18n translations
│   ├── types/            # TypeScript type definitions
│   └── utils/            # Utility functions
└── package.json
```

### Key Frontend Components

#### Desktop Mode

K-Systems features a Windows-style desktop interface:

- **Start Menu**: Application launcher with categorized apps
- **Taskbar**: Quick access to running applications
- **Desktop**: Icon-based shortcuts to applications
- **Window Manager**: Multiple resizable, draggable windows
- **Window Controls**: Minimize, maximize, close buttons

#### State Management (Pinia Stores)

```typescript
// stores/auth.ts
export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
    permissions: []
  }),
  actions: {
    async login(credentials) { ... },
    logout() { ... }
  }
})
```

Common stores:
- `authStore`: Authentication and user state
- `employeeStore`: Employee data management
- `reportStore`: Report data and operations
- `messageStore`: Messaging state
- `calendarStore`: Calendar events
- `notificationStore`: System notifications

#### API Layer

Centralized API client in `/src/api.ts`:

```typescript
const API_URL = import.meta.env.VITE_API_URL

export const api = {
  async get(endpoint: string) {
    const response = await fetch(`${API_URL}${endpoint}`, {
      credentials: 'include',
      headers: { 'Authorization': `Bearer ${token}` }
    })
    return response.json()
  },
  // ... post, put, delete methods
}
```

#### Permission System

Uses `usePermissionCheck` composable:

```typescript
import { usePermissionCheck } from '@/composables/usePermissionCheck'

const { hasPermission } = usePermissionCheck()

if (hasPermission('WRITE_REPORT')) {
  // Show edit button
}
```

### Styling and Themes

- **Default Theme**: Custom Vuetify theme configuration
- **Desktop Background**: `/public/img/bg.jpg`
- **Responsive Design**: Mobile-friendly layouts
- **Dark Mode**: Theme toggle support

## Backend Architecture

### Technology Stack

- **Language**: PHP 8.4
- **Web Server**: Apache with mod_php
- **Database Access**: PDO (PHP Data Objects)
- **Authentication**: JWT (JSON Web Tokens)
- **Session Management**: Cookie-based auth tokens

### Backend Structure

```
backend/
├── bootstrap.php         # Core initialization
├── db.php               # Database connection
├── auth_check.php       # JWT validation
├── login/               # Authentication endpoints
├── employee/            # Employee module
├── report/              # Report module
├── document/            # Document module
├── message/             # Messaging module
├── calendar/            # Calendar module
├── invoice/             # Invoice module
├── training/            # Training module
├── dispatch/            # Dispatch module
├── todo/                # Todo module
├── admin/               # Admin endpoints
│   ├── user/           # User management
│   ├── roles/          # Role management
│   └── settings/       # System settings
├── utils/               # Utility functions
│   ├── permission_helper.php
│   └── authority_helper.php
├── logging/             # Audit logging
└── uploads/             # File uploads (authority-specific)
```

### Request Flow

1. **Client Request**: Frontend sends HTTP request with JWT cookie
2. **Bootstrap**: `bootstrap.php` initializes environment
3. **Database Connection**: `db.php` establishes PDO connection
4. **Authentication**: `auth_check.php` validates JWT token
5. **Authority Validation**: Check user's authority context
6. **Permission Check**: Verify user has required permissions
7. **Action Routing**: Execute requested action
8. **Response**: Return JSON response

### Module Pattern

Each backend module follows this structure:

```php
<?php
// Module: backend/example/index.php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth_check.php';

$userId = $decoded_jwt->userId ?? null;
$authority = $decoded_jwt->authority ?? null;
$authorityId = $decoded_jwt->authority_id ?? null;

$action = $_REQUEST['action'] ?? '';

// Permission mapping
$permissions_map = [
    'getData' => 'READ_EXAMPLE',
    'saveData' => 'WRITE_EXAMPLE'
];

// Permission check
require_once __DIR__ . '/../utils/permission_helper.php';
if (!hasPermission($userPermissions, $permissions_map[$action])) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit();
}

// Execute action
switch ($action) {
    case 'getData': getData($pdo, $authority); break;
    case 'saveData': saveData($pdo, $userId, $authority); break;
}
```

### Authentication System

#### JWT Token Structure

```json
{
  "userId": 42,
  "username": "john.doe",
  "authority": "authority1",
  "authority_id": 1,
  "permissions": ["READ_REPORT", "WRITE_REPORT"],
  "roles": ["Firefighter", "Admin"],
  "iat": 1706745600,
  "exp": 1706832000
}
```

#### Login Flow

1. User submits credentials to `/backend/login/`
2. Backend validates against database
3. Generate JWT token with user context
4. Set secure HTTP-only cookie
5. Return user information to frontend

#### Authority Context

Every request includes `authority_id` for multi-tenant isolation:

```php
// Validate authority
if (!isValidAuthority($pdo, $authorityId)) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid authority context"]);
    exit();
}

// All queries include authority_id
$stmt = $pdo->prepare("SELECT * FROM employees WHERE authority_id = :aid");
$stmt->bindParam(':aid', $authorityId);
```

## Database Architecture

### Database Platform

- **DBMS**: MariaDB 10.11+ / MySQL 8.0+
- **Character Set**: UTF-8 (utf8mb4)
- **Engine**: InnoDB for transactions and foreign keys

### Table Naming Convention

All tables use the `kdd_` prefix:
- `kdd_employees`
- `kdd_reports`
- `kdd_documents`
- `kdd_users`
- etc.

### Multi-Tenant Schema

Every table includes `authority_id` column:

```sql
CREATE TABLE kdd_example (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    authority_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (authority_id) REFERENCES kdd_authorities(id)
);
```

### Core Tables

#### Authority Management
- `kdd_authorities`: Organization/tenant definitions
- `kdd_authority_features`: Available features per authority
- `kdd_authority_feature_access`: Authority feature assignments

#### User Management
- `kdd_users`: User accounts
- `kdd_roles`: Role definitions
- `kdd_user_roles`: User-role assignments
- `kdd_permissions`: Available permissions
- `kdd_role_permissions`: Role-permission assignments

#### Employee Management
- `kdd_employees`: Employee records
- `kdd_departments`: Department structure
- `kdd_ranks`: Employee ranks/positions
- `kdd_employee_training`: Training assignments
- `kdd_employee_certifications`: Certifications

#### Operations
- `kdd_reports`: Incident reports
- `kdd_report_custom_fields`: Custom report fields
- `kdd_documents`: Document metadata
- `kdd_document_areas`: Document categorization
- `kdd_crews`: Dispatch units
- `kdd_crew_employees`: Crew assignments
- `kdd_crew_vehicles`: Vehicle assignments

#### Communication
- `kdd_messages`: Message system
- `kdd_message_groups`: Group conversations
- `kdd_calendar_events`: Calendar entries
- `kdd_calendar_invites`: Event RSVPs

#### Business
- `kdd_companies`: Company directory
- `kdd_invoices`: Invoice records
- `kdd_invoice_entries`: Invoice line items
- `kdd_applicants`: Job applications

### Indexes and Performance

Key indexes for performance:
- Primary keys on all tables
- Foreign key indexes for relationships
- `authority_id` indexes for multi-tenant queries
- Composite indexes on frequently queried columns
- Full-text indexes on searchable content

## Socket Server Architecture

### Technology Stack

- **Runtime**: Node.js 20+
- **Framework**: Socket.io for WebSocket connections
- **Authentication**: JWT token validation
- **Port**: 3001

### Socket Server Structure

```
socket-server/
├── server.js            # Main server file
├── handlers/            # Event handlers
│   ├── chat.js
│   ├── notification.js
│   ├── status.js
│   └── whiteboard.js
├── middleware/          # Authentication middleware
└── package.json
```

### Socket Namespaces

#### `/chat` Namespace
- Real-time chat messaging
- Typing indicators
- Read receipts
- Online status

#### `/notification` Namespace
- System notifications
- Alert broadcasts
- Event notifications

#### `/status` Namespace
- User online/offline status
- Activity tracking
- Presence management

#### `/whiteboard` Namespace
- Collaborative drawing
- Real-time canvas sync
- Multi-user editing

### Connection Flow

```javascript
// Client connection
import io from 'socket.io-client'

const socket = io('http://localhost:3001/chat', {
  auth: {
    token: localStorage.getItem('token')
  }
})

socket.on('connect', () => {
  console.log('Connected to socket server')
})

socket.on('message', (data) => {
  console.log('New message:', data)
})
```

### Authentication

Socket server validates JWT tokens:

```javascript
io.use((socket, next) => {
  const token = socket.handshake.auth.token
  try {
    const decoded = jwt.verify(token, SECRET_KEY)
    socket.userId = decoded.userId
    socket.authority = decoded.authority
    next()
  } catch (err) {
    next(new Error('Authentication failed'))
  }
})
```

### Real-Time Events

Common event patterns:

```javascript
// Emit to specific user
io.to(`user_${userId}`).emit('notification', data)

// Emit to authority
io.to(`authority_${authorityId}`).emit('broadcast', data)

// Emit to room
io.to(`chat_${roomId}`).emit('message', data)
```

## Deployment Architecture

### Docker Containers

K-Systems uses Docker Compose for orchestration:

```yaml
services:
  frontend:
    build: ./frontend
    ports: ["5173:5173"]

  backend:
    build: ./backend
    ports: ["8080:80"]

  socket-server:
    build: ./socket-server
    ports: ["3001:3001"]

  database:
    image: mariadb:10.11
    ports: ["3306:3306"]
```

### Environment Configuration

Environment variables managed via `.env` files:

```bash
# Frontend (.env)
VITE_API_URL=http://localhost:8080/backend
VITE_SOCKET_URL=http://localhost:3001

# Backend (.env)
DB_HOST=localhost
DB_NAME=ksystems
DB_USER=ksystems_user
DB_PASS=secure_password
JWT_SECRET=your_secret_key
```

### File Storage

Uploads are organized by authority:

```
uploads/
├── authority1/
│   ├── documents/
│   ├── invoices/
│   ├── trainings/
│   └── avatars/
├── authority2/
│   └── ...
```

### Logging and Monitoring

- **Application Logs**: Stored in `/var/log/`
- **Database Audit**: `kdd_access_logs` table
- **Error Logging**: PHP error logs and Node.js console
- **Access Logs**: Apache access logs

## Integration Points

### Frontend ↔ Backend

- HTTP REST API calls
- Cookie-based JWT authentication
- JSON request/response format
- File upload/download endpoints

### Frontend ↔ Socket Server

- WebSocket connections
- Real-time event streaming
- JWT authentication
- Namespace-based channels

### Backend ↔ Database

- PDO prepared statements
- Transaction support
- Connection pooling
- Multi-tenant queries

### Backend ↔ Socket Server

- HTTP API for triggering events
- Shared authentication (JWT)
- Event notification system

## Security Architecture

### Authentication Layers

1. **JWT Validation**: All requests validated
2. **Authority Context**: Multi-tenant isolation
3. **Permission Checks**: Role-based access control
4. **Session Management**: Secure cookie handling

### Data Protection

- Password hashing with bcrypt
- SQL injection prevention (PDO prepared statements)
- XSS prevention (output escaping)
- CSRF protection (token validation)
- File upload validation

### Audit Trail

All critical operations logged:
- User actions
- Data modifications
- Permission changes
- Login attempts
- Failed access attempts

## Scalability Considerations

### Horizontal Scaling

- Frontend: Multiple frontend servers behind load balancer
- Backend: Stateless PHP servers with session sharing
- Socket Server: Socket.io with Redis adapter
- Database: Read replicas for query distribution

### Caching Strategy

- Browser caching for static assets
- API response caching (Redis)
- Database query caching
- Compiled template caching

### Performance Optimization

- Lazy loading of Vue components
- Database query optimization
- CDN for static assets
- Gzip compression
- Connection pooling

## Development Workflow

### Local Development

```bash
# Start all services
docker-compose up -d

# Frontend development
cd frontend
npm run dev

# Backend on Apache
# Already running in Docker

# Socket server development
cd socket-server
npm run dev
```

### Build Process

```bash
# Frontend production build
cd frontend
npm run build

# Backend (no build needed)
# PHP runs directly

# Socket server
cd socket-server
npm start
```

### Testing

- Frontend: Vitest unit tests
- Backend: PHPUnit integration tests
- E2E: Playwright/Cypress
- API: Postman collections

## Technology Versions

### Frontend
- Vue: 3.4+
- Vuetify: 3.5+
- TypeScript: 5.3+
- Vite: 5.0+
- Node.js: 20+ (for build)

### Backend
- PHP: 8.4+
- Apache: 2.4+
- Composer: 2.7+

### Database
- MariaDB: 10.11+ or MySQL: 8.0+

### Socket Server
- Node.js: 20+
- Socket.io: 4.6+

## System Requirements

### Development Environment
- CPU: 4+ cores recommended
- RAM: 8GB minimum, 16GB recommended
- Storage: 20GB available space
- OS: Linux, macOS, or Windows with WSL2

### Production Environment
- CPU: 8+ cores
- RAM: 16GB minimum, 32GB+ recommended
- Storage: SSD with 100GB+ available
- Network: 1Gbps connection
- OS: Ubuntu Server 22.04 LTS or similar

## Monitoring and Maintenance

### Health Checks

Endpoint monitoring:
- Frontend: `http://localhost:5173/`
- Backend: `http://localhost:8080/backend/health`
- Socket: WebSocket connection test
- Database: Connection pool status

### Backup Strategy

- Database: Daily automated backups
- File Uploads: Incremental backups
- Configuration: Version control (Git)
- Logs: Rotation and archival

### Update Process

1. Test updates in development
2. Create database backup
3. Deploy backend updates
4. Deploy frontend build
5. Restart socket server
6. Verify functionality
7. Monitor logs
