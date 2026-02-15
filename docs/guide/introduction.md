# Introduction

## What is K-Systems?

K-Systems is a multi-tenant web-based management system with a unique desktop-style interface. It provides organizations with tools for managing employees, reports, documents, messaging, scheduling, and operational workflows.

## Key Highlights

### 🖥️ Desktop Interface
Experience a Windows-style interface with:
- **Multiple Windows**: Open multiple applications simultaneously
- **Draggable & Resizable**: Full window management capabilities
- **Start Menu**: Categorized application launcher
- **Taskbar**: Quick access to open windows
- **Desktop Icons**: Customizable icon positioning
- **Widgets**: Weather, calendar, and notes widgets

### 🏢 Multi-Tenant Architecture
Perfect for organizations managing multiple clients or departments:
- **Complete Data Isolation**: Each authority (tenant) has isolated data via authority_id
- **Custom Branding**: Logo, colors, and app title per authority
- **Feature Flags**: Enable/disable modules per tenant
- **Custom Fields**: Authority-specific field definitions (for reports)

### 🔐 Security First
Security features:
- **JWT Authentication**: Secure token-based authentication
- **Role-Based Access Control**: Fine-grained permissions
- **Feature-Based Access**: Control module availability
- **Session Management**: Token expiration handling

### ⚡ Real-Time Features
Stay connected with:
- **Live Updates**: Socket.io for whiteboard and dispatch
- **Messaging**: Internal messaging system with folders
- **Status Indicators**: Basic online/offline tracking

## Core Modules

### Employee Management
- Employee records with departments and ranks
- Training and license tracking
- Vacation management
- Rank-based organization
- Company assignments

### Report System
- Custom field definitions per authority
- Status workflow tracking
- Report sharing between authorities
- Person and company associations
- PDF generation

### Document Management
- Document areas with categories
- Permission-based access control
- Rich text editing with TiptapEditor
- Search and filtering

### Calendar & Events
- Event scheduling with employee assignments
- Calendar groups
- Recurring events support
- Multi-user assignments

### Messaging System
- Private conversations
- Message folders
- Rich text messages
- Real-time delivery

### Additional Features
- **Invoices**: Line items, PDF generation
- **Todo Lists**: Tasks with drag-and-drop sorting
- **Map**: Location markers with Leaflet.js
- **Whiteboard**: Collaborative drawing with Socket.io
- **Blackboard**: Bulletin board system
- **File Manager**: Organized file storage
- **Dispatch**: Emergency dispatch management
- **Training**: Training matrix tracking
- **Applications**: Job application tracking

## Technology Stack

### Frontend
- **Vue 3**: Reactive framework with Composition API
- **TypeScript**: Type-safe development
- **Vuetify 3**: Material Design component library
- **Pinia**: State management
- **Socket.io**: Real-time communication
- **Vite**: Build tool

### Backend
- **PHP 8**: Modern PHP with type declarations
- **MariaDB**: Database system
- **JWT**: Authentication tokens
- **PDO**: Database access
- **Composer**: Dependency management

### Socket Server
- **Node.js**: JavaScript runtime
- **Express**: Web server framework
- **Socket.io**: WebSocket library
- **Winston**: Logging system

### Infrastructure
- **Docker**: Containerization
- **Docker Compose**: Multi-container orchestration
- **Nginx**: Frontend web server
- **Apache/PHP-FPM**: Backend server

## Who Is This For?

K-Systems is suitable for:

- **Multi-Department Organizations**: Managing different departments with data isolation
- **Service Providers**: Offering management solutions to multiple customers
- **Teams**: Collaborative work with role-based access
- **Organizations needing**: Desktop-style web interface with modular features

## System Requirements

### Development
- Node.js 18+
- PHP 8.0+
- MySQL/MariaDB 10.11+
- Composer
- NPM or Yarn

### Production
- Docker & Docker Compose
- 2GB+ RAM
- 10GB+ disk space
- SSL certificate (recommended)

## Next Steps

Ready to get started? Here's what to do next:

1. [🚀 Quick Start Guide](/guide/getting-started) - Get K-Systems up and running
2. [🖥️ Desktop Interface](/guide/desktop-interface) - Learn the interface
3. [👥 Employee Management](/guide/employee-management) - Manage employees
4. [📝 Reports](/guide/reports) - Create and manage reports

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
