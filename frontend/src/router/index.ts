import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
// Importiere den Pinia Auth Store
import { useAuthStore } from '@/stores/auth';
import { useUIStore } from '@/stores/ui';
import { usePermissionRefresh } from '@/composables/usePermissionRefresh';
import { useModulePermission } from '@/composables/useModulePermission';
import { apiClientAuth } from '@/api';

// Nur die wichtigsten Views statisch importieren
import LoginView from '@/views/LoginView.vue';
import HomeView from '@/views/HomeView.vue';
import DashboardView from '@/views/DashboardViewNew.vue';
import BlackboardView from '@/views/BlackboardView.vue';
import DocumentView from '@/views/DocumentView.vue';
import MapView from '@/views/MapView.vue';
import MapSharedView from '@/views/MapSharedView.vue';
import ProfileView from '@/views/ProfileView.vue';
import AdminUserView from '@/views/admin/UserView.vue';
import AdminRolesView from '@/views/admin/RoleView.vue';
import TodoView from '@/views/TodoView.vue';

// --- Routen Definitionen ---
const routes: Array<RouteRecordRaw> = [
    { path: "/", name: "login", component: LoginView },
    { path: "/home", redirect: "/desktop" }, // Redirect home to desktop
    { path: "/dashboard", name: "dashboard", component: DashboardView, meta: { requiresAuth: true } },
    { path: "/desktop", name: "desktop", component: () => import('@/components/desktop/DesktopView.vue'), meta: { requiresAuth: true } },
    { path: "/employee", name: "employee", component: () => import('@/views/EmployeeView.vue'), meta: { requiresAuth: true, requiredModule: 'employee', requiredAction: 'read', requiredFeature: 'employee' }},
    { path: "/invoice", name: "invoice", component: () => import('@/views/InvoiceView.vue'), meta: { requiresAuth: true, requiredModule: 'invoice', requiredAction: 'read', requiredFeature: 'invoice' }},
    { path: "/invoiceitems", name: "invoiceitems", component: () => import('@/views/InvoiceItemsView.vue'), meta: { requiresAuth: true, requiredModule: 'invoice.items', requiredAction: 'read', requiredFeature: 'invoice' }},
    { path: "/filemanager", name: "filemanager", component: () => import('@/views/FileManagerView.vue'), meta: { requiresAuth: true, requiredModule: 'filemanager', requiredAction: 'read', requiredFeature: 'filemanager' }},
    { path: "/dispatch", name: "dispatch", component: () => import('@/views/DispatchView.vue'), meta: { requiresAuth: true, requiredModule: 'dispatch', requiredAction: 'read', requiredFeature: 'dispatch' }},
    { path: "/vehicle", name: "vehicle", component: () => import('@/views/VehicleView.vue'), meta: { requiresAuth: true, requiredModule: 'dispatch.vehicle', requiredAction: 'read', requiredFeature: 'dispatch' }},
    { path: "/reportcategory", name: "reportcategory", component: () => import('@/views/ReportCategorieView.vue'), meta: { requiresAuth: true, requiredModule: 'report.department', requiredAction: 'read', requiredFeature: 'reports' }},
    { path: "/reporttemplate", name: "reporttemplate", component: () => import('@/views/ReportTemplateView.vue'), meta: { requiresAuth: true, requiredModule: 'report.template', requiredAction: 'read', requiredFeature: 'reports' }},
    { path: "/reportstatus", name: "reportstatus", component: () => import('@/views/ReportStatusView.vue'), meta: { requiresAuth: true, requiredModule: 'report.status', requiredAction: 'read', requiredFeature: 'reports' }},
    { path: "/report", name: "report", component: () => import('@/views/ReportView.vue'), meta: { requiresAuth: true, requiredModule: 'report', requiredAction: 'read', requiredFeature: 'reports' }},
    { path: "/reportcode", name: "reportcode", component: () => import('@/views/ReportCodeView.vue'), meta: { requiresAuth: true, requiredModule: 'report.code', requiredAction: 'read', requiredFeature: 'reports' }},
    { path: "/crew", name: "crew", component: () => import('@/views/CrewView.vue'), meta: { requiresAuth: true, requiredModule: 'dispatch.crew', requiredAction: 'read', requiredFeature: 'dispatch' }},
    { path: "/vacation", name: "vacation", component: () => import('@/views/VacationView.vue'), meta: { requiresAuth: true, requiredModule: 'employee', requiredAction: 'read', requiredFeature: 'employee' }},
    { path: "/trainingassign", name: "trainingassign", component: () => import('@/views/TrainingView.vue'), meta: { requiresAuth: true, requiredModule: 'training', requiredAction: 'read', requiredFeature: 'training' }},
    { path: "/export", name: "export", component: () => import('@/views/ExportView.vue'), meta: { requiresAuth: true, requiredModule: 'employee', requiredAction: 'read', requiredFeature: 'employee' }},
    { path: "/reportadditional", name: "reportadditional", component: () => import('@/views/ReportAdditionalView.vue'), meta: { requiresAuth: true, requiredModule: 'report.additionals', requiredAction: 'read', requiredFeature: 'reports' }},
    { path: "/application", name: "application", component: () => import('@/views/ApplicationView.vue'), meta: { requiresAuth: true, requiredModule: 'application', requiredAction: 'read', requiredFeature: 'application' }},
    { path: "/calendar", name: "calendar", component: () => import('@/views/CalendarView.vue'), meta: { requiresAuth: true, requiredModule: 'calendar', requiredAction: 'read', requiredFeature: 'calendar' }},
    { path: "/document", name: "documents", component: DocumentView, meta: { requiresAuth: true, requiredModule: 'document.document', requiredAction: 'read', requiredFeature: 'document', docType: 'document' }},
    { path: "/document/global", name: "documentsglobal", component: DocumentView, meta: { requiresAuth: true, requiredModule: 'document.global', requiredAction: 'read', requiredFeature: 'authorities', docType: 'global' }},
    { path: "/test", name: "tests", component: () => import('@/views/TestView.vue'), meta: { requiresAuth: true, requiredModule: 'training.test', requiredAction: 'read', requiredFeature: 'training' }},
    { path: "/training", name: "trainings", component: DocumentView, meta: { requiresAuth: true, requiredModule: 'document.training', requiredAction: 'read', requiredFeature: 'document', docType: 'training' }},
    { path: "/department", name: "department", component: DocumentView, meta: { requiresAuth: true, requiredModule: 'document.department', requiredAction: 'read', requiredFeature: 'document', docType: 'department' }},
    { path: "/administration", name: "administration", component: DocumentView, meta: { requiresAuth: true, requiredModule: 'document.administration', requiredAction: 'read', requiredFeature: 'document', docType: 'administration' }},
    { path: "/fireprotection", name: "fireprotection", component: () => import('@/views/FireprotectionView.vue'), meta: { requiresAuth: true, requiredModule: 'fireprotection', requiredAction: 'read' }},
    { path: "/company", name: "company", component: () => import('@/views/CompanyView.vue'), meta: { requiresAuth: true, requiredModule: 'company', requiredAction: 'read', requiredFeature: 'company' }},
    { path: "/template", name: "template", component: () => import('@/views/TemplateView.vue'), meta: { requiresAuth: true, requiredModule: 'template', requiredAction: 'read', requiredFeature: 'template' }},
    { path: "/person", name: "person", component: () => import('@/views/PersonView.vue'), meta: { requiresAuth: true, requiredModule: 'person.file', requiredAction: 'read', requiredFeature: 'person_file' }},
    { path: "/vehicleFile", name: "vehicleFile", component: () => import('@/views/VehicleFileView.vue'), meta: { requiresAuth: true, requiredModule: 'vehicle.file', requiredAction: 'read', requiredFeature: 'vehicle_file' }},
    { path: "/apartmentFile", name: "apartmentFile", component: () => import('@/views/ApartmentView.vue'), meta: { requiresAuth: true, requiredModule: 'apartment.file', requiredAction: 'read', requiredFeature: 'apartment_file' }},
    
    // Blackboard Routes
    // Only Global Board (cross-authority, fixed) - Legacy admin/employee removed, replaced by dynamic areas
    { path: "/blackboard/global", name: "blackboardGlobal", component: BlackboardView, meta: { requiresAuth: true, requiredModule: 'blackboard.global', requiredAction: 'read', requiredFeature: 'authorities', boardType: 'global' }},

    // NEW: Blackboard Areas
    {
      path: "/blackboard/area/:area_id",
      name: "blackboardArea",
      component: BlackboardView,
      props: route => ({
        area_id: Number(route.params.area_id),
        area_key: route.query.area_key
      }),
      meta: { requiresAuth: true, requiredModule: 'blackboard.area', requiredAction: 'read', requiredFeature: 'blackboard' }
    },
    {
      path: "/admin/blackboard-areas",
      name: "blackboardAreas",
      component: () => import('@/views/admin/BlackboardAreasView.vue'),
      meta: { requiresAuth: true, requiredModule: 'blackboard.areas', requiredAction: 'admin', requiredFeature: 'blackboard' }
    },

    { path: "/companytype", name: "companytype", component: () => import('@/views/CompanyTypeView.vue'), meta: { requiresAuth: true, requiredModule: 'company.type', requiredAction: 'read', requiredFeature: 'company' }},
    { path: "/company/website", name: "companywebsite", component: () => import('@/components/WebsiteManager.vue'), meta: { requiresAuth: true, requiredModule: 'company.websites', requiredAction: 'read', requiredFeature: 'company_websites' }},
    { path: "/website/:id", name: "website", component: () => import('@/views/WebsiteView.vue'), meta: { requiresAuth: false, isAppWindow: true }},
    { path: "/todo", name: "todo", component: TodoView, meta: { requiresAuth: true, requiredModule: 'todo', requiredAction: 'read', requiredFeature: 'todo' }},
    { path: '/profile', name: "profile", component: ProfileView, meta: { requiresAuth: true, requiredModule: 'account', requiredAction: 'read' }},
    { path: '/admin/applicationquestions', name: "admin_applicationquestions", component: () => import('@/views/admin/ApplicationQuestionsView.vue'), meta: { requiresAuth: true, requiredModule: 'application', requiredAction: 'admin', requiredFeature: 'application' }},
    { path: '/admin/users', name: "admin_users", component: AdminUserView, meta: { requiresAuth: true, requiredModule: 'admin.users', requiredAction: 'read', requiredFeature: 'default' }},
    { path: '/admin/roles', name: "admin_roles", component: AdminRolesView, meta: { requiresAuth: true, requiredModule: 'admin.roles', requiredAction: 'read', requiredFeature: 'default' }},
    { path: '/admin/settings', name: "admin_settings", component: () => import('@/views/admin/SettingsView.vue'), meta: { requiresAuth: true, requiredModule: 'admin.settings', requiredAction: 'read', requiredFeature: 'default' }},
    { path: '/admin/authority-branding', name: "admin_authority_branding", component: () => import('@/views/admin/AuthorityBrandingView.vue'), meta: { requiresAuth: true, requiredModule: 'admin.authority', requiredAction: 'admin', requiredFeature: 'default' }},
    { path: '/admin/weather', name: "admin_weather", component: () => import('@/views/admin/WeatherView.vue'), meta: { requiresAuth: true, requiredModule: 'admin.weather', requiredAction: 'read', requiredFeature: 'weather' }},
    { path: '/admin/employees', name: "admin_employee", component: () => import('@/views/admin/EmployeeView.vue'), meta: { requiresAuth: true, requiredModule: 'employee', requiredAction: 'admin', requiredFeature: 'employee' }},
    { path: '/admin/trainings', name: "admin_training", component: () => import('@/views/admin/TrainingView.vue'), meta: { requiresAuth: true, requiredModule: 'training', requiredAction: 'admin', requiredFeature: 'training' }},
    { path: '/admin/map', name: "admin_map", component: () => import('@/views/admin/MapView.vue'), meta: { requiresAuth: true, requiredModule: 'map', requiredAction: 'admin', requiredFeature: 'map' }},
    { path: '/admin/authorities', name: "admin_authorities", component: () => import('@/views/admin/AuthorityView.vue'), meta: { requiresAuth: true, requiredModule: 'system', requiredAction: 'admin', requiredFeature: 'system_admin' }},
    { path: '/admin/authorityfields', name: "admin_authorityfields", component: () => import('@/components/Admin/AuthorityFieldManager.vue'), meta: { requiresAuth: true, requiredModule: 'admin.authority.fields', requiredAction: 'admin', requiredFeature: 'person_file' }},
    { path: '/admin/reportfields', name: "admin_reportfields", component: () => import('@/views/admin/ReportFieldsView.vue'), meta: { requiresAuth: true, requiredModule: 'report', requiredAction: 'admin', requiredFeature: 'reports' }},
    { path: '/admin/logs', name: "admin_logs", component: () => import('@/views/admin/LogsView.vue'), meta: { requiresAuth: true, requiredModule: 'system', requiredAction: 'admin', requiredFeature: 'system_admin' }},
    { path: '/map', name: "map", component: MapView, meta: { requiresAuth: true, requiredModule: 'map', requiredAction: 'read', requiredFeature: 'map' }},
    { path: '/map/global', name: "mapglobal", component: MapView, meta: { requiresAuth: true, requiredModule: 'map.global', requiredAction: 'read', requiredFeature: 'authorities', mapType: 'global' }},
    { path: '/map/shared/:token', name: "mapshared", component: MapSharedView, meta: { requiresAuth: false }}, // Public shared map view
    { path: '/mapOnly', name: "mapOnly", component: () => import('@/views/MapOnlyView.vue'), meta: { requiresAuth: false }},
    { path: '/cheatsheet', name: "cheatsheet", component: () => import('@/views/CheatsheetView.vue'), meta: { requiresAuth: true, requiredModule: 'cheatsheet', requiredAction: 'read' }},
    { path: '/admin/cheatsheet', name: "admin_cheatsheet", component: () => import('@/views/admin/CheatsheetView.vue'), meta: { requiresAuth: true, requiredModule: 'cheatsheet', requiredAction: 'admin' }},
    { path: '/desktopDebug', name: "desktopDebug", component: () => import('@/views/DesktopDebug.vue'), meta: { requiresAuth: true }},
    { path: '/whiteboard', name: "whiteboard", component: () => import('@/views/WhiteboardView.vue'), meta: { requiresAuth: true, requiredModule: 'whiteboard', requiredAction: 'read', requiredFeature: 'whiteboard' }},
    { path: '/waterduck', name: "waterduck", component: () => import('@/components/desktop/WaterDuck.vue'), meta: { requiresAuth: false, isAppWindow: true }},
    { path: '/preview/website/:id', name: "website_preview", component: () => import('@/views/WebsitePreviewView.vue'), meta: { requiresAuth: true, requiredModule: 'company.websites', requiredAction: 'read', requiredFeature: 'company_websites' }},
    { path: '/admin/documentareas', name: "admin_documentareas", component: () => import('@/views/admin/DocumentAreasView.vue'), meta: { requiresAuth: true, requiredModule: 'document.areas', requiredAction: 'admin', requiredFeature: 'document' }},
    { path: '/documentarea/:key', name: "documentarea", component: DocumentView, meta: { requiresAuth: true, requiredFeature: 'document' }, props: true },
    { path: '/mail', name: "mail", component: () => import('@/views/MailView.vue'), meta: { requiresAuth: true, requiredModule: 'mail', requiredAction: 'read', requiredFeature: 'mail' }},
    { path: '/mail/settings', name: "mail_settings", component: () => import('@/views/MailSettingsView.vue'), meta: { requiresAuth: true, requiredModule: 'mail', requiredAction: 'write', requiredFeature: 'mail' }},
    { path: '/admin/mail', name: "admin_mail", component: () => import('@/views/admin/MailAdminView.vue'), meta: { requiresAuth: true, requiredModule: 'mail', requiredAction: 'admin', requiredFeature: 'mail' }},
];

// --- Vue Router Instanz ---
const router = createRouter({
    history: createWebHistory(), // Verwende createWebHistory für Vite
    routes,
});

let isInitialAuthCheckDone = false; // Flag, um den Check nur einmal pro App-Load zu erzwingen

router.beforeEach(async (to, from, next) => {
    console.log(`🚦 Navigating to: ${String(to.name || to.path)}`);
    console.log('--- META START beforeEach:', to.meta); // <-- Log 1
    console.log(`🚦 Navigating to: ${String(to.name || to.path)}, isAppWindow: ${Boolean(to.meta.isAppWindow)}`);
    const authStore = useAuthStore();

    // First, check if this is an app window route that should be accessible regardless of auth
    if (to.meta.isAppWindow) {
        console.log('--- META after isAppWindow check:', to.meta); // <-- Log 2
        console.log('🪟 App window route detected, allowing direct access to:', to.path);
        next();
        return; // Stop further processing
    }

    // --- Initialer Authentifizierungscheck (für geschützte Routen UND Login-Route) ---
    // WICHTIG: Auch bei Login-Route prüfen, damit eingeloggte User weitergeleitet werden
    // ABER: Nur wenn wir einen Token haben (sonst Endlosschleife bei 401)
    if (!isInitialAuthCheckDone && (to.meta.requiresAuth || to.name === 'login')) {
        console.log('--- META before initial auth check:', to.meta); // <-- Log 3

        // ✅ CRITICAL FIX: Only check auth status if we actually have a token
        // Without a token, checkAuthStatus will fail with 401, trigger refresh (also 401),
        // call performLogout, redirect to login, and create an infinite loop
        const hasToken = !!(
            localStorage.getItem('auth_token') ||
            sessionStorage.getItem('auth_token') ||
            document.cookie.split(';').some(c => c.trim().startsWith('auth_token='))
        );

        console.log(`🔑 Token check: ${hasToken ? 'Token found' : 'No token found'}`);

        // Only perform auth check if we have a token OR if we're on a protected route
        // (protected routes need the 401 to properly redirect to login)
        if (hasToken || to.meta.requiresAuth) {
            console.log('--- META after initial auth check:', to.meta); // <-- Log 4
            // Nur wenn der Check noch nicht lief UND der Store nicht bereits lädt
            if (!authStore.isLoading) {
                console.log('🚀 Performing initial auth status check...');
                try {
                    await authStore.checkAuthStatus(); // Warten bis abgeschlossen
                } catch (e) {
                    console.error("Initial auth check failed:", e);
                    // Fehler wird hier behandelt, der State (isLoggedIn=false) führt ggf. unten zum Redirect
                }
            } else {
                 // Wenn bereits geladen wird (sollte selten sein, aber sicherheitshalber warten)
                 console.log('⏳ Initial auth check already in progress, waiting...');
                 while (authStore.isLoading) {
                     await new Promise(resolve => setTimeout(resolve, 50)); // Kurz warten
                 }
                 console.log('✅ Initial auth check finished (was already in progress).');
            }
        } else {
            console.log('⏭️ Skipping auth check - no token found and not a protected route');
        }

        isInitialAuthCheckDone = true; // Markiere den initialen Check als erledigt
        console.log(`🏁 Initial auth check complete. Logged in: ${authStore.isLoggedIn}`);
    }

    // --- Berechtigungen laden, falls noch nicht geladen ---
    // MIGRATION NOTE (Phase 3): With JWT containing permissions, this should rarely trigger
    // Check works for both bitmask object and legacy array formats
    if (authStore.isLoggedIn && !authStore.user?.permissions) {
        console.log('🔒 Permissions not loaded yet (fallback for legacy systems)...');
        console.log('--- META before permission refresh:', to.meta); // <-- Log 5
        // ... await permissionRefresh.refreshPermissions(); ...
        console.log('--- META after permission refresh:', to.meta); // <-- Log 6
        try {
            // Use our more direct approach to refresh permissions consistently
            const permissionRefresh = usePermissionRefresh();
            await permissionRefresh.refreshPermissions();
            console.log('✅ Permissions loaded successfully (legacy fallback)');
        } catch (e) {
            console.error("Failed to load permissions:", e);
            // Auch wenn die Berechtigungen nicht geladen werden konnten, fahren wir mit Navigation fort
            // Der Zugriff wird später ggf. durch leere Berechtigungen verhindert
        }
    }

    // --- Load Layout Preference on Initial Page Load (F5 Reload) ---
    // This ensures isDesktopMode is set BEFORE navigation/rendering
    if (authStore.isLoggedIn && isInitialAuthCheckDone) {
        const uiStore = useUIStore();

        // Only load if not already loaded
        if (uiStore.layoutPreference === undefined) {
            console.log('🎨 Loading layout preference for initial page load...');
            try {
                await uiStore.loadLayoutPreference();
                const savedPreference = uiStore.layoutPreference;
                console.log('🎨 Layout preference loaded:', savedPreference);

                // Set isDesktopMode based on preference BEFORE navigation
                if (savedPreference === 'desktop') {
                    uiStore.setDesktopMode(true);
                    console.log('✅ Set isDesktopMode = true based on preference');
                } else if (savedPreference === 'sidebar') {
                    uiStore.setDesktopMode(false);
                    console.log('✅ Set isDesktopMode = false based on preference');
                }
                // If savedPreference is null, keep default (false)
            } catch (e) {
                console.error('Failed to load layout preference:', e);
            }
        }
    }

    // --- Force Desktop Mode for /desktop route ---
    // If navigating to /desktop route, always enable desktop mode
    if (to.path === '/desktop' && authStore.isLoggedIn) {
        const uiStore = useUIStore();
        if (!uiStore.isDesktopMode) {
            console.log('🖥️ Forcing desktop mode for /desktop route');
            uiStore.setDesktopMode(true);
        }
    }

    // --- Status nach potentiellem Check neu auswerten ---
    const isUserLoggedIn = authStore.isLoggedIn;
    const user = authStore.user;

    // --- Banned Check ---
    if (isUserLoggedIn && user && 'banned' in user && user.banned === true) {
        console.warn('🚫 User is banned. Logging out and redirecting to login.');
        await authStore.logout(); // Sicherstellen, dass Logout fertig ist
        next({ name: 'login' });
        return; // Weitere Verarbeitung stoppen
    }

    console.log('--- META after banned check:', to.meta); // <-- Log 7

    // --- Auth Required Check ---
    // Wenn die Route Authentifizierung erfordert UND der User (nach dem Check) NICHT eingeloggt ist
    if (to.meta.requiresAuth && !isUserLoggedIn) {
        console.log('🚫 Auth required, redirecting to login.');
        // Optional: Redirect-Pfad für späteres Zurückleiten speichern
        next({ name: 'login', query: { redirect: to.fullPath } });
        return; // Weitere Verarbeitung stoppen
    }

    // --- Already Logged In Check ---
    // Wenn der User eingeloggt ist UND versucht, die Login-Seite aufzurufen
    if (isUserLoggedIn && to.name === 'login') {
        console.log('👤 Already logged in, redirecting from Login based on layout preference.');
        console.log('--- META before login redirect:', to.meta); // <-- Log 9

        // Load layout preference and redirect accordingly
        const uiStore = useUIStore();
        await uiStore.loadLayoutPreference();
        const savedPreference = uiStore.layoutPreference;

        // Only redirect to desktop if explicitly set to 'desktop'
        // Default to sidebar (dashboard) for better UX
        if (savedPreference === 'desktop') {
            next({ name: 'desktop' });
        } else {
            // For 'sidebar' or null/undefined, go to dashboard
            next({ name: 'dashboard' });
        }
        return; // Weitere Verarbeitung stoppen
    }

    console.log('--- META entering permission check block:', to.meta); // <-- Log 10
    // --- Permission Check (Nur wenn Auth Required/User Logged In) ---
    if (to.meta.requiresAuth && isUserLoggedIn) {
        let hasPermission = true;
        let hasFeatureAccess = true;

        // Check permissions
        if (to.meta.requiredModule && to.meta.requiredAction) {
            const { hasModulePermission } = useModulePermission();
            hasPermission = hasModulePermission(to.meta.requiredModule as string, to.meta.requiredAction as string);
        }

        // Check if the required feature is active
        if (to.meta.requiredFeature) {
            const activeFeatures = user?.active_features ?? [];
            
            // Make sure activeFeatures is an array
            if (!Array.isArray(activeFeatures)) {
                console.warn('⚠️ user.active_features ist kein Array:', activeFeatures);
                hasFeatureAccess = false;
            } else {
                // For ALL_PERMISSIONS users, we still need to check if the feature is active
                hasFeatureAccess = activeFeatures.includes(to.meta.requiredFeature);
                
                if (!hasFeatureAccess) {
                    console.log(`🚫 Feature not active: ${to.meta.requiredFeature} for route: ${String(to.name)}`);
                }
            }
        }

        // Only allow access if both permission and feature checks pass
        if (!hasPermission || !hasFeatureAccess) {
            console.log(`🚫 Access Denied for: ${String(to.name)}. ` + 
                        `Permission: ${hasPermission ? '✅' : '❌'}, ` + 
                        `Feature: ${hasFeatureAccess ? '✅' : '❌'}`);
            next({ name: 'dashboard' }); // Sicherer Redirect
            return;
        }

        // --- Set Dynamic Meta Fields (canEdit/canDelete/canCreate) ---
        if (to.meta.requiredModule) {
            const { hasModulePermission } = useModulePermission();
            const module = to.meta.requiredModule as string;
            to.meta.canEdit = hasModulePermission(module, 'write');
            to.meta.canDelete = hasModulePermission(module, 'delete');
            to.meta.canCreate = hasModulePermission(module, 'create');
        } else {
             to.meta.canEdit = false;
             to.meta.canDelete = false;
             to.meta.canCreate = false;
        }
    } else {
         to.meta.canEdit = false;
         to.meta.canDelete = false;
         to.meta.canCreate = false;
    }

    // --- Navigation erlauben ---
    console.log('✅ Access granted.');
    next(); // Navigation fortsetzen
});

// Dedizierter Hook speziell für die dynamischen Dokumentenbereiche
// Hier nutzen wir beforeResolve, damit dieser nach allen anderen Checks kommt
router.beforeResolve(async (to, from, next) => {
    // Nur für die documentarea-Route
    if (to.name === 'documentarea' && to.params.key) {
        console.log(`🗂️ Loading document area: ${to.params.key}`);
        try {
            const response = await apiClientAuth.post('/document/?action=getAreaByKey', {
                key: to.params.key
            });

            const area = response.data;
            if (!area) {
                console.log(`🚫 Document area not found: ${to.params.key}`);
                next({ name: 'dashboard' });
                return;
            }

            // Check permissions from backend
            if (!area.permissions?.can_read) {
                console.log(`🚫 Access denied to document area: ${area.name}`);
                next({ name: 'dashboard' });
                return;
            }

            // Set route meta information
            to.meta.docType = area.key;
            to.meta.areaId = area.id;
            to.meta.canEdit = area.permissions?.can_write || false;
            to.meta.canDelete = area.permissions?.can_delete || false;

            console.log(`✅ Document area access granted: ${area.name} (Edit: ${to.meta.canEdit}, Delete: ${to.meta.canDelete})`);

            next();
        } catch (error) {
            console.error('Error fetching document area:', error);
            next({ name: 'dashboard' });
        }
        return;
    }
    next();
});

// --- TypeScript Augmentation für Meta-Felder ---
declare module 'vue-router' {
    interface RouteMeta {
        requiresAuth?: boolean;
        requiredModule?: string; // Module name for permission check (e.g., 'employee', 'calendar')
        requiredAction?: string; // Action for permission check (e.g., 'read', 'write', 'delete')
        requiredFeature?: string; // The feature that must be active to access this route
        canEdit?: boolean; // Dynamically set by router guard based on write permission
        canDelete?: boolean; // Dynamically set by router guard based on delete permission
        canCreate?: boolean; // Dynamically set by router guard based on create permission
        docType?: string;
        boardType?: string;
        areaId?: number;
        minimal?: boolean;
        isAppWindow?: boolean; // For routes that should be accessible regardless of auth status
    }
}

export default router;