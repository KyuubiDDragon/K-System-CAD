import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'K-Systems Documentation',
  description: 'Complete documentation for K-Systems enterprise management platform',
  base: '/docs/',
  ignoreDeadLinks: true,

  locales: {
    root: {
      label: 'English',
      lang: 'en',
      title: 'K-Systems Documentation',
      description: 'Complete documentation for K-Systems enterprise management platform'
    },
    de: {
      label: 'Deutsch',
      lang: 'de',
      title: 'K-Systems Dokumentation',
      description: 'Vollständige Dokumentation für die K-Systems Enterprise-Management-Plattform'
    }
  },

  themeConfig: {
    logo: '/logo.png',

    nav: [
      { text: 'Home', link: '/' },
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'API Reference', link: '/api/overview' },
      { text: 'Architecture', link: '/architecture/overview' }
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Getting Started',
          items: [
            { text: 'Introduction', link: '/guide/introduction' },
            { text: 'Getting Started', link: '/guide/getting-started' },
            { text: 'Installation (Admin)', link: '/guide/installation' },
            { text: 'Desktop Mode', link: '/guide/desktop-mode' },
            { text: 'Desktop Customization', link: '/guide/desktop-customization' },
            { text: 'Desktop Widgets', link: '/guide/desktop-widgets' },
            { text: 'Desktop Apps & Games', link: '/guide/desktop-apps' },
            { text: 'Search', link: '/guide/search' }
          ]
        },
        {
          text: 'Help & Support',
          items: [
            { text: 'FAQ', link: '/guide/faq' },
            { text: 'Troubleshooting', link: '/guide/troubleshooting' }
          ]
        },
        {
          text: 'Communication & Collaboration',
          items: [
            { text: 'Messages', link: '/guide/messages' },
            { text: 'Mail System', link: '/guide/mail' },
            { text: 'Calendar', link: '/guide/calendar' },
            { text: 'Todo Lists', link: '/guide/todos' },
            { text: 'Whiteboard', link: '/guide/whiteboard' },
            { text: 'Blackboard', link: '/guide/blackboard' }
          ]
        },
        {
          text: 'Reports & Documents',
          items: [
            { text: 'Reports', link: '/guide/reports' },
            { text: 'Documents', link: '/guide/documents' },
            { text: 'Templates', link: '/guide/templates' }
          ]
        },
        {
          text: 'Employee Management',
          items: [
            { text: 'Employee Management', link: '/guide/employee-management' },
            { text: 'Training', link: '/guide/training' },
            { text: 'Vacation', link: '/guide/vacation' },
            { text: 'Crew Management', link: '/guide/crew-management' }
          ]
        },
        {
          text: 'Operations & Dispatch',
          items: [
            { text: 'Dispatch', link: '/guide/dispatch' },
            { text: 'Map & Locations', link: '/guide/map' },
            { text: 'Fireprotection', link: '/guide/fireprotection' }
          ]
        },
        {
          text: 'File Management',
          items: [
            { text: 'Person Files', link: '/guide/person-files' },
            { text: 'Vehicle Files', link: '/guide/vehicle-files' },
            { text: 'Apartment Files', link: '/guide/apartment-files' },
            { text: 'File Manager', link: '/guide/file-manager' }
          ]
        },
        {
          text: 'Finance & Business',
          items: [
            { text: 'Invoices', link: '/guide/invoices' },
            { text: 'Companies', link: '/guide/companies' },
            { text: 'Company Websites', link: '/guide/company-websites' },
            { text: 'Applications', link: '/guide/applications' }
          ]
        },
        {
          text: 'Workflows',
          collapsed: true,
          items: [
            { text: 'Daily Operations', link: '/guide/workflows/daily-operations' },
            { text: 'Report Creation', link: '/guide/workflows/report-creation' },
            { text: 'Document Management', link: '/guide/workflows/document-management' },
            { text: 'Employee Onboarding', link: '/guide/workflows/employee-onboarding' }
          ]
        },
        {
          text: 'User Settings',
          items: [
            { text: 'Profile Settings', link: '/guide/profile-settings' }
          ]
        },
        {
          text: 'Reference',
          items: [
            { text: 'Cheatsheet', link: '/guide/cheatsheet' }
          ]
        },
        {
          text: 'Administration',
          collapsed: false,
          items: [
            { text: 'User Management', link: '/guide/admin/user-management' },
            { text: 'Role Management', link: '/guide/admin/roles' },
            { text: 'Permissions Guide', link: '/guide/admin/permissions' },
            { text: 'Backup & Recovery', link: '/guide/admin/backup' },
            { text: 'System Settings', link: '/guide/admin/system-settings' },
            { text: 'Authority Branding', link: '/guide/admin/authority-branding' },
            { text: 'Weather Widget', link: '/guide/admin/weather' },
            { text: 'Super Admin: Authorities', link: '/guide/admin/authorities' }
          ]
        },
        {
          text: 'Developer Tools',
          collapsed: true,
          items: [
            { text: 'Developer Tools', link: '/guide/developer-tools' }
          ]
        }
      ],

      '/architecture/': [
        {
          text: 'Architecture',
          items: [
            { text: 'Overview', link: '/architecture/overview' },
            { text: 'System Components', link: '/architecture/components' },
            { text: 'Multi-Tenant Architecture', link: '/architecture/multi-tenant' },
            { text: 'Database Schema', link: '/architecture/database' },
            { text: 'Security Architecture', link: '/architecture/security' }
          ]
        }
      ],

      '/api/': [
        {
          text: 'Getting Started',
          items: [
            { text: 'Overview', link: '/api/overview' },
            { text: 'Authentication', link: '/api/authentication' }
          ]
        },
        {
          text: 'Core APIs',
          items: [
            { text: 'Employee API', link: '/api/employee' },
            { text: 'Report API', link: '/api/report' },
            { text: 'Document API', link: '/api/document' },
            { text: 'Message API', link: '/api/message' },
            { text: 'Calendar API', link: '/api/calendar' },
            { text: 'Invoice API', link: '/api/invoice' }
          ]
        },
        {
          text: 'Operations APIs',
          items: [
            { text: 'Training API', link: '/api/training' },
            { text: 'Vacation API', link: '/api/vacation' },
            { text: 'Todo API', link: '/api/todo' },
            { text: 'Dispatch API', link: '/api/dispatch' }
          ]
        },
        {
          text: 'Admin APIs',
          items: [
            { text: 'User Management', link: '/api/admin/user' },
            { text: 'Role Management', link: '/api/admin/role' }
          ]
        },
        {
          text: 'Real-Time',
          items: [
            { text: 'WebSocket API', link: '/api/websocket' }
          ]
        }
      ],

      // German Sidebars
      '/de/guide/': [
        {
          text: 'Erste Schritte',
          items: [
            { text: 'Erste Schritte', link: '/de/guide/erste-schritte' },
            { text: 'Desktop-Modus', link: '/de/guide/desktop-modus' },
            { text: 'Desktop-Anpassung', link: '/de/guide/desktop-anpassung' },
            { text: 'Desktop-Widgets', link: '/de/guide/desktop-widgets' },
            { text: 'Desktop-Apps & Spiele', link: '/de/guide/desktop-apps' },
            { text: 'Suche', link: '/de/guide/suche' }
          ]
        },
        {
          text: 'Kommunikation & Zusammenarbeit',
          items: [
            { text: 'Nachrichten', link: '/de/guide/nachrichten' },
            { text: 'Kalender', link: '/de/guide/kalender' },
            { text: 'Aufgaben', link: '/de/guide/aufgaben' },
            { text: 'Whiteboard', link: '/de/guide/whiteboard' },
            { text: 'Schwarzes Brett', link: '/de/guide/schwarzes-brett' }
          ]
        },
        {
          text: 'Berichte & Dokumente',
          items: [
            { text: 'Berichte', link: '/de/guide/berichte' },
            { text: 'Dokumente', link: '/de/guide/dokumente' },
            { text: 'Vorlagen', link: '/de/guide/vorlagen' }
          ]
        },
        {
          text: 'Mitarbeiterverwaltung',
          items: [
            { text: 'Mitarbeiterverwaltung', link: '/de/guide/mitarbeiterverwaltung' },
            { text: 'Schulungen', link: '/de/guide/schulungen' },
            { text: 'Urlaub', link: '/de/guide/urlaub' },
            { text: 'Mannschaftsverwaltung', link: '/de/guide/mannschaftsverwaltung' }
          ]
        },
        {
          text: 'Einsatz & Dispatch',
          items: [
            { text: 'Einsatzleitung', link: '/de/guide/einsatzleitung' },
            { text: 'Karte & Standorte', link: '/de/guide/karte' },
            { text: 'Brandschutz', link: '/de/guide/brandschutz' }
          ]
        },
        {
          text: 'Aktenverwaltung',
          items: [
            { text: 'Personenakten', link: '/de/guide/personenakten' },
            { text: 'Fahrzeugakten', link: '/de/guide/fahrzeugakten' },
            { text: 'Gebäude-Akten', link: '/de/guide/gebaeude-akten' },
            { text: 'Dateimanager', link: '/de/guide/dateimanager' }
          ]
        },
        {
          text: 'Finanzen & Geschäft',
          items: [
            { text: 'Rechnungen', link: '/de/guide/rechnungen' },
            { text: 'Firmen', link: '/de/guide/firmen' },
            { text: 'Bewerbungen', link: '/de/guide/bewerbungen' }
          ]
        },
        {
          text: 'Benutzereinstellungen',
          items: [
            { text: 'Profil-Einstellungen', link: '/de/guide/profil-einstellungen' }
          ]
        },
        {
          text: 'Referenz',
          items: [
            { text: 'Spickzettel', link: '/de/guide/spickzettel' }
          ]
        },
        {
          text: 'Administration',
          collapsed: false,
          items: [
            { text: 'Benutzerverwaltung', link: '/de/guide/admin/benutzerverwaltung' },
            { text: 'Rollenverwaltung', link: '/de/guide/admin/rollenverwaltung' },
            { text: 'Systemeinstellungen', link: '/de/guide/admin/systemeinstellungen' },
            { text: 'Authority Branding', link: '/de/guide/admin/authority-branding' },
            { text: 'Wetter-Widget', link: '/de/guide/admin/wetter' },
            { text: 'Super Admin: Behörden', link: '/de/guide/admin/behoerden' }
          ]
        },
        {
          text: 'Entwickler-Tools',
          collapsed: true,
          items: [
            { text: 'Entwickler-Tools', link: '/de/guide/entwickler-tools' }
          ]
        }
      ],

      '/de/architecture/': [
        {
          text: 'Architektur',
          items: [
            { text: 'Übersicht', link: '/de/architecture/uebersicht' },
            { text: 'Systemkomponenten', link: '/de/architecture/komponenten' },
            { text: 'Mandantenfähigkeit', link: '/de/architecture/mandantenfaehigkeit' },
            { text: 'Datenbankschema', link: '/de/architecture/datenbank' },
            { text: 'Sicherheitsarchitektur', link: '/de/architecture/sicherheit' }
          ]
        }
      ],

      '/de/api/': [
        {
          text: 'Erste Schritte',
          items: [
            { text: 'Übersicht', link: '/de/api/uebersicht' },
            { text: 'Authentifizierung', link: '/de/api/authentifizierung' }
          ]
        },
        {
          text: 'Kern-APIs',
          items: [
            { text: 'Mitarbeiter-API', link: '/de/api/mitarbeiter' },
            { text: 'Berichte-API', link: '/de/api/berichte' },
            { text: 'Dokumente-API', link: '/de/api/dokumente' },
            { text: 'Nachrichten-API', link: '/de/api/nachrichten' },
            { text: 'Kalender-API', link: '/de/api/kalender' },
            { text: 'Rechnungen-API', link: '/de/api/rechnungen' }
          ]
        },
        {
          text: 'Operations-APIs',
          items: [
            { text: 'Schulungen-API', link: '/de/api/schulungen' },
            { text: 'Urlaub-API', link: '/de/api/urlaub' },
            { text: 'Aufgaben-API', link: '/de/api/aufgaben' },
            { text: 'Einsatzleitung-API', link: '/de/api/einsatzleitung' }
          ]
        },
        {
          text: 'Admin-APIs',
          items: [
            { text: 'Benutzerverwaltung', link: '/de/api/admin/benutzer' },
            { text: 'Rollenverwaltung', link: '/de/api/admin/rollen' }
          ]
        },
        {
          text: 'Echtzeit',
          items: [
            { text: 'WebSocket-API', link: '/de/api/websocket' }
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/yourusername/K-Systems' }
    ],

    footer: {
      message: 'K-Systems Enterprise Management Platform',
      copyright: 'Copyright © 2025'
    },

    search: {
      provider: 'local'
    }
  }
})
