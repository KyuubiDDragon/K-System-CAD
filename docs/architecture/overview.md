# Architecture Overview

K-Systems is built on a modern three-tier architecture consisting of a Vue.js frontend, PHP backend API, and Node.js real-time server.

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                         Frontend                             │
│  Vue 3 + TypeScript + Vuetify (Port 5173/80)               │
│  - Desktop Mode Interface                                    │
│  - Window Management System                                  │
│  - 76+ Vue Components                                        │
│  - 5 Pinia Stores                                           │
└────────────────┬────────────────────────────────────────────┘
                 │
                 │ HTTPS/WSS
                 │
    ┌────────────┴────────────┬───────────────────────┐
    │                         │                       │
    ▼                         ▼                       ▼
┌─────────┐          ┌──────────────┐      ┌──────────────┐
│ Backend │          │ Socket Server│      │   Database   │
│   PHP   │◄────────►│   Node.js    │◄────►│   MariaDB    │
│ (8080)  │          │   (3001)     │      │   (3306)     │
│         │          │              │      │              │
│ 50+ API │          │ Real-time    │      │ 104 Tables   │
│Endpoints│          │ WebSocket    │      │ Multi-tenant │
└─────────┘          └──────────────┘      └──────────────┘
     │                      │
     └──────────┬───────────┘
                │
                ▼
         ┌─────────────┐
         │   Logging   │
         │   System    │
         └─────────────┘
```

## Core Components

### Frontend Layer

**Technology Stack:**
- Vue 3.5 with Composition API
- TypeScript 5.8
- Vuetify 3.8 (Material Design)
- Pinia 3.0 (State Management)
- Socket.io Client 4.8

**Key Features:**
- **Desktop Interface**: Windows-style UI with draggable windows
- **Component Library**: 76+ reusable Vue components
- **State Management**: 5 Pinia stores for app state
- **Real-time Updates**: Socket.io integration
- **Routing**: 91 routes with guards and permissions
- **i18n**: Multi-language support (English, German)

**Directory Structure:**
```
frontend/src/
├── components/      # 76 Vue components
├── views/           # 56 route views
├── stores/          # 5 Pinia stores
├── composables/     # Reusable composition functions
├── services/        # API service layer
├── router/          # Vue Router configuration
└── types/           # TypeScript type definitions
```

[Learn more about Frontend Architecture →](/architecture/frontend/vue-architecture)

### Backend Layer

**Technology Stack:**
- PHP 8.x
- MariaDB 10.11
- PDO for database access
- JWT Authentication (Firebase PHP-JWT)
- Composer for dependencies

**Key Features:**
- **RESTful API**: 50+ endpoints
- **JWT Authentication**: Secure token-based auth
- **Multi-tenant**: Complete data isolation
- **Permission System**: Role-based access control
- **Database Abstraction**: PDO with prepared statements

**Directory Structure:**
```
backend/
├── admin/              # Admin endpoints
├── login/              # Authentication
├── employee/           # Employee management
├── report/             # Report system
├── document/           # Document management
├── message/            # Messaging system
├── calendar/           # Calendar/events
├── utils/              # Helper functions
├── db.php              # Database connection
├── jwt.php             # JWT functions
├── auth_check.php      # Auth middleware
└── bootstrap.php       # Application init
```

[Learn more about Backend Architecture →](/architecture/backend/php-api)

### Socket Server Layer

**Technology Stack:**
- Node.js
- Express 4.18
- Socket.io 4.7
- Winston 3.17 (Logging)
- JWT for authentication

**Key Features:**
- **Real-time Communication**: WebSocket connections
- **Multiple Namespaces**: Chat, notifications, status
- **Authentication**: JWT-based socket auth
- **Broadcasting**: User-specific and global broadcasts
- **Logging**: Comprehensive Winston logging

**Namespaces:**
- `/notification` - System notifications and toasts
- `/chat` - Real-time messaging
- `/status` - User online/offline status
- `/dispatch` - Dispatch operations
- `/whiteboard` - Collaborative whiteboard

[Learn more about Socket Server →](/architecture/socket/realtime)

### Database Layer

**Database:** MariaDB 10.11

**Schema:**
- 104+ tables with `kdd_` prefix
- Multi-tenant with `authority_id` column
- Foreign key constraints for referential integrity
- Indexes for performance

**Key Tables:**
- **Users & Auth**: kdd_users, kdd_roles, kdd_permissions
- **Multi-tenant**: kdd_authorities, kdd_authority_features
- **Employee**: kdd_employee, kdd_employee_department
- **Reports**: kdd_reports, kdd_report_custom_fields
- **Documents**: kdd_doc_documents, kdd_doc_areas
- **Messages**: kdd_messages, kdd_message_groups
- **Calendar**: kdd_calendar, kdd_calendar_assigned

[Learn more about Database Schema →](/architecture/backend/database)

## Communication Flow

### Request Flow

#### Standard API Request
```
1. User interacts with UI
   ↓
2. Vue component calls API service
   ↓
3. Axios sends HTTP request with JWT token
   ↓
4. Backend validates JWT (auth_check.php)
   ↓
5. Backend checks permissions
   ↓
6. Backend queries database (with authority_id)
   ↓
7. Backend returns JSON response
   ↓
8. Frontend updates UI
```

#### Real-time Socket Event
```
1. User performs action
   ↓
2. Backend triggers socket notification
   ↓
3. Socket server receives request
   ↓
4. Socket server validates API key
   ↓
5. Socket server broadcasts to user room
   ↓
6. Frontend receives socket event
   ↓
7. Frontend updates UI in real-time
```

## Data Flow

### Authentication Flow

```
1. User enters credentials
   ↓
2. POST /login with authority_id
   ↓
3. Backend validates password (bcrypt)
   ↓
4. Backend loads roles & permissions
   ↓
5. Backend creates JWT token
   ↓
6. Backend sets HttpOnly cookie
   ↓
7. Frontend stores token in localStorage
   ↓
8. Frontend redirects to desktop
```

### Multi-tenant Data Isolation

```
Every database query includes authority_id:

SELECT * FROM kdd_employee
WHERE authority_id = ? AND id = ?

This ensures:
- Complete data isolation
- No cross-tenant data leaks
- Authority-specific queries
```

## Security Architecture

### Authentication
- **JWT Tokens**: HS256 signed tokens
- **Cookie Storage**: HttpOnly, Secure, SameSite
- **Token Expiration**: 24 hours (30 days with "Remember Me")
- **Automatic Refresh**: Token refresh on activity

### Authorization
- **Role-Based**: Users assigned to roles
- **Permission-Based**: Fine-grained permissions
- **Feature Flags**: Module enable/disable per tenant
- **Dynamic Checks**: Frontend and backend validation

### Data Security
- **Multi-tenant Isolation**: authority_id in all queries
- **Prepared Statements**: SQL injection prevention
- **Input Validation**: Backend validation
- **XSS Protection**: Vue template escaping
- **CORS Configuration**: Controlled origins

[Learn more about Security →](/architecture/security/multi-tenant)

## Scalability Considerations

### Horizontal Scaling
- **Frontend**: Stateless, can run multiple instances behind load balancer
- **Backend**: Stateless API, horizontally scalable
- **Socket Server**: Can use Redis adapter for multi-instance
- **Database**: Master-slave replication possible

### Performance Optimization
- **Frontend**:
  - Code splitting with Vite
  - Lazy loading of routes
  - Component-level caching
  - Virtual scrolling for large lists

- **Backend**:
  - Database query optimization
  - Proper indexing
  - Connection pooling
  - Response caching (future)

- **Socket Server**:
  - Room-based broadcasting
  - Event throttling
  - Connection limits
  - Memory management

## Technology Choices

### Why Vue 3?
- Modern Composition API
- Excellent TypeScript support
- Great ecosystem (Vuetify, Pinia)
- Small bundle size
- Fast virtual DOM

### Why PHP?
- Mature and stable
- Excellent database support
- Wide hosting availability
- Good performance with PHP 8
- Large developer community

### Why Socket.io?
- Reliable WebSocket library
- Automatic fallback to polling
- Room and namespace support
- Broadcasting capabilities
- Good documentation

### Why MariaDB?
- MySQL compatibility
- Better performance
- Open-source
- Active development
- JSON support

## Development vs Production

### Development Environment
- **Frontend**: Vite dev server (Port 5173)
- **Backend**: PHP built-in server or Apache
- **Socket**: Nodemon with auto-restart
- **Database**: Docker container
- **Hot Reload**: Instant updates

### Production Environment
- **Frontend**: Nginx serving static files
- **Backend**: Apache/PHP-FPM
- **Socket**: PM2 process manager
- **Database**: Managed MariaDB instance
- **SSL**: Let's Encrypt certificates
- **Reverse Proxy**: Traefik or Nginx

## Next Steps

Dive deeper into specific architectural components:

- [Frontend Architecture](/architecture/frontend/vue-architecture)
- [Backend Architecture](/architecture/backend/php-api)
- [Socket Server](/architecture/socket/realtime)
- [Database Schema](/architecture/backend/database)
- [Security](/architecture/security/multi-tenant)
- [Technology Stack](/architecture/tech-stack)

---

::: tip Understanding the Architecture
A solid understanding of the architecture will help you contribute effectively to the project and make informed decisions when extending functionality.
:::
