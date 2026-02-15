/**
 * useMenuItems Composable
 * Zentrale Menü-Generierung für Desktop-Modus UND Sidebar-Modus
 */

import { ref, computed, ComputedRef } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import { apiClientAuth } from '@/api';
import { useModulePermission } from './useModulePermission';
import type { MenuItem, DocArea } from '@/types/Menu';

export interface BlackboardArea {
  id: number;
  key: string;
  name: string;
  icon: string;
  is_active: boolean;
  permissions?: {
    can_read: boolean;
    can_write: boolean;
    can_delete: boolean;
  };
}

export function useMenuItems() {
  const router = useRouter();
  const authStore = useAuthStore();
  const { t } = useI18n();

  // State
  const menuItems = ref<MenuItem[]>([]);
  const documentAreas = ref<DocArea[]>([]);
  const blackboardAreas = ref<BlackboardArea[]>([]);
  const loading = ref(false);

  /**
   * Check if user can access a route
   * Supports both new module-based and legacy string-based permissions
   */
  function canAccessRoute(path: string): boolean {
    const targetRoute = router.getRoutes().find(r => r.path === path);
    if (!targetRoute?.meta) return true; // Allow if no meta defined

    const requiredModule = targetRoute.meta.requiredModule as string | undefined;
    const requiredAction = targetRoute.meta.requiredAction as string | undefined;
    const requiredFeature = targetRoute.meta.requiredFeature as string | undefined;
    const userFeatures = authStore.user?.active_features || [];

    // Check permission
    let hasPermission = true;

    if (requiredModule && requiredAction) {
      const { hasModulePermission } = useModulePermission();
      hasPermission = hasModulePermission(requiredModule, requiredAction);
    }

    // Check feature
    const hasFeature =
      !requiredFeature ||
      userFeatures.includes(requiredFeature);

    return hasPermission && hasFeature;
  }

  /**
   * Check if user can access any route from a list
   */
  function canAccessAnyRoute(paths: string[]): boolean {
    return paths.some(path => canAccessRoute(path));
  }

  /**
   * Fetch document areas from backend
   */
  async function fetchDocumentAreas(): Promise<void> {
    try {
      const response = await apiClientAuth.post('/document/?action=getAreas');
      documentAreas.value = response.data.filter((area: DocArea) =>
        area.is_active && area.permissions?.can_read
      );
    } catch (error) {
      console.error('Failed to fetch document areas:', error);
      documentAreas.value = [];
    }
  }

  /**
   * Fetch blackboard areas from backend
   */
  async function fetchBlackboardAreas(): Promise<void> {
    try {
      const response = await apiClientAuth.get('/blackboard/?action=getAreas');
      blackboardAreas.value = response.data.filter((area: BlackboardArea) =>
        area.is_active && area.permissions?.can_read
      );
    } catch (error) {
      console.error('Failed to fetch blackboard areas:', error);
      blackboardAreas.value = [];
    }
  }

  /**
   * Generate complete menu structure
   */
  function generateMenuItems(): MenuItem[] {
    const items: MenuItem[] = [];

    // ========================================
    // DASHBOARD
    // ========================================
    if (canAccessRoute('/dashboard')) {
      items.push({
        id: 'dashboard',
        title: t('tabs.dashboard'),
        icon: 'mdi-view-dashboard-outline',
        route: '/dashboard',
        color: '#3b82f6',
        pinnable: true,
        order: 1,
      });
    }

    // ========================================
    // WATERDUCK BROWSER (Single App - No Group)
    // ========================================
    // WaterDuck Browser - shown individually, not in a group
    items.push({
      id: 'waterduck',
      title: t('tabs.waterduck'),
      icon: '/img/waterduck.png',
      route: '/waterduck',
      color: '#1976D2',
      isDesktopApp: true,
      isPinned: true,
      order: 99,
    });

    // ========================================
    // ADMINISTRATION
    // ========================================
    if (canAccessAnyRoute([
      '/admin/users',
      '/admin/roles',
      '/admin/employees',
      '/admin/trainings',
      '/admin/applicationquestions',
      '/admin/mail',
      '/admin/map',
      '/admin/weather',
      '/admin/settings',
      '/admin/authorities',
      '/admin/authorityfields',
      '/admin/reportfields',
      '/admin/cheatsheet',
      '/admin/documentareas',
      '/admin/logs',
    ])) {
      const adminChildren: MenuItem[] = [];

      if (canAccessRoute('/admin/users')) {
        adminChildren.push({
          id: 'admin-users',
          title: t('tabs.users'),
          icon: 'mdi-account-multiple-outline',
          route: '/admin/users',
          color: '#4f46e5',
        });
      }

      if (canAccessRoute('/admin/roles')) {
        adminChildren.push({
          id: 'admin-roles',
          title: t('tabs.roles'),
          icon: 'mdi-account-key-outline',
          route: '/admin/roles',
          color: '#3b82f6',
        });
      }

      // NOTE: admin/authorities moved to System menu (super admin only)

      if (canAccessRoute('/admin/authorityfields')) {
        adminChildren.push({
          id: 'admin-authorityfields',
          title: t('tabs.authorityFields'),
          icon: 'mdi-form-select',
          route: '/admin/authorityfields',
          color: '#65a30d',
        });
      }

      if (canAccessRoute('/admin/reportfields')) {
        adminChildren.push({
          id: 'admin-reportfields',
          title: t('tabs.reportFields'),
          icon: 'mdi-notebook-edit-outline',
          route: '/admin/reportfields',
          color: '#16a34a',
        });
      }

      if (canAccessRoute('/admin/employees')) {
        adminChildren.push({
          id: 'admin-employees',
          title: t('tabs.employee'),
          icon: 'mdi-account-group-outline',
          route: '/admin/employees',
          color: '#2563eb',
        });
      }

      if (canAccessRoute('/admin/trainings')) {
        adminChildren.push({
          id: 'admin-trainings',
          title: t('tabs.training'),
          icon: 'mdi-school-outline',
          route: '/admin/trainings',
          color: '#1d4ed8',
        });
      }

      if (canAccessRoute('/admin/applicationquestions')) {
        adminChildren.push({
          id: 'admin-applicationquestions',
          title: t('tabs.applicationQuestions'),
          icon: 'mdi-help-circle-outline',
          route: '/admin/applicationquestions',
          color: '#0ea5e9',
        });
      }

      if (canAccessRoute('/admin/mail')) {
        adminChildren.push({
          id: 'admin-mail',
          title: 'Mail-System',
          icon: 'mdi-shield-account',
          route: '/admin/mail',
          color: '#06b6d4',
        });
      }

      if (canAccessRoute('/admin/map')) {
        adminChildren.push({
          id: 'admin-map',
          title: t('tabs.map'),
          icon: 'mdi-map-outline',
          route: '/admin/map',
          color: '#14b8a6',
        });
      }

      if (canAccessRoute('/admin/weather')) {
        adminChildren.push({
          id: 'admin-weather',
          title: t('desktop.weather'),
          icon: 'mdi-weather-partly-cloudy',
          route: '/admin/weather',
          color: '#10b981',
        });
      }

      if (canAccessRoute('/admin/documentareas')) {
        adminChildren.push({
          id: 'admin-documentareas',
          title: t('tabs.documentAreas'),
          icon: 'mdi-folder-multiple-outline',
          route: '/admin/documentareas',
          color: '#0d9488',
        });
      }

      if (canAccessRoute('/admin/blackboard-areas')) {
        adminChildren.push({
          id: 'admin-blackboard-areas',
          title: t('tabs.blackboardAreas'),
          icon: 'mdi-bulletin-board',
          route: '/admin/blackboard-areas',
          color: '#0891b2',
        });
      }

      if (canAccessRoute('/admin/settings')) {
        adminChildren.push({
          id: 'admin-settings',
          title: t('tabs.authorityBranding'),  // Changed from tabs.settings
          icon: 'mdi-palette-outline',  // Changed to branding icon
          route: '/admin/settings',
          color: '#8b5cf6',  // Changed to purple
        });
      }

      if (canAccessRoute('/admin/logs')) {
        adminChildren.push({
          id: 'admin-logs',
          title: t('tabs.systemLogs'),
          icon: 'mdi-database-search',
          route: '/admin/logs',
          color: '#a855f7',
        });
      }

      if (canAccessRoute('/admin/cheatsheet')) {
        adminChildren.push({
          id: 'admin-cheatsheet',
          title: t('tabs.cheatsheet'),
          icon: 'mdi-book-open-variant',
          route: '/admin/cheatsheet',
          color: '#06b6d4',
        });
      }

      if (adminChildren.length > 0) {
        items.push({
          id: 'admin',
          title: t('tabs.administration'),
          icon: 'mdi-shield-account-outline',
          route: '/admin/users',
          color: '#9333ea',
          isGroup: true,
          children: adminChildren,
          pinnable: true,
          order: 2,
        });
      }
    }

    // ========================================
    // SYSTEM GROUP (Super Admin Only)
    // ========================================
    const { hasAllPermissions } = useModulePermission();

    if (hasAllPermissions.value) {
      const systemChildren: MenuItem[] = [];

      if (canAccessRoute('/admin/authorities')) {
        systemChildren.push({
          id: 'system-authorities',
          title: t('tabs.authorities'),
          icon: 'mdi-domain',
          route: '/admin/authorities',
          color: '#84cc16',
        });
      }

      if (systemChildren.length > 0) {
        items.push({
          id: 'system',
          title: t('tabs.system'),
          icon: 'mdi-cog-outline',
          route: '/admin/authorities',
          color: '#ef4444',
          isGroup: true,
          children: systemChildren,
          pinnable: true,
          order: 99, // Show at the end
        });
      }
    }

    // ========================================
    // DISPATCH GROUP
    // ========================================
    if (canAccessAnyRoute(['/dispatch', '/vehicle', '/crew'])) {
      const dispatchChildren: MenuItem[] = [];

      if (canAccessRoute('/dispatch')) {
        dispatchChildren.push({
          id: 'dispatch',
          title: t('tabs.dispatch'),
          icon: 'mdi-fire-truck',
          route: '/dispatch',
          color: '#ef4444',
        });
      }

      if (canAccessRoute('/vehicle')) {
        dispatchChildren.push({
          id: 'vehicle',
          title: t('tabs.vehicle'),
          icon: 'mdi-car',
          route: '/vehicle',
          color: '#dc2626',
        });
      }

      if (canAccessRoute('/crew')) {
        dispatchChildren.push({
          id: 'crew',
          title: t('tabs.crew'),
          icon: 'mdi-account-group',
          route: '/crew',
          color: '#b91c1c',
        });
      }

      if (dispatchChildren.length > 0) {
        items.push({
          id: 'dispatch-group',
          title: t('tabs.dispatch'),
          icon: 'mdi-fire-truck',
          color: '#ef4444',
          isGroup: true,
          children: dispatchChildren,
          pinnable: true,
          order: 3,
        });
      }
    }

    // ========================================
    // BLACKBOARD GROUP (Dynamic Areas Only)
    // ========================================
    if (blackboardAreas.value && blackboardAreas.value.length > 0) {
      const blackboardChildren: MenuItem[] = [];

      // Dynamic blackboard areas from database (replaces legacy admin/employee boards)
      blackboardAreas.value.forEach((area, index) => {
        if (area.is_active && area.permissions?.can_read) {
          blackboardChildren.push({
            id: `blackboard-area-${area.key}`,
            title: area.name,
            icon: area.icon || 'mdi-bulletin-board',
            route: `/blackboard/area/${area.id}`,
            color: index % 2 === 0 ? '#f59e0b' : '#d97706',
          });
        }
      });

      if (blackboardChildren.length > 0) {
        items.push({
          id: 'blackboard-group',
          title: t('tabs.blackboard'),
          icon: 'mdi-bulletin-board',
          color: '#f59e0b',
          isGroup: true,
          children: blackboardChildren,
          order: 4,
        });
      }
    }

    // ========================================
    // AUTHORITIES GROUP (Cross-Authority / Behördenaustausch)
    // ========================================
    if (canAccessAnyRoute(['/blackboard/global', '/document/global', '/map/global'])) {
      const authoritiesChildren: MenuItem[] = [];

      if (canAccessRoute('/blackboard/global')) {
        authoritiesChildren.push({
          id: 'blackboard-global',
          title: t('tabs.blackboard'),
          icon: 'mdi-bulletin-board',
          route: '/blackboard/global',
          color: '#84cc16',
        });
      }

      if (canAccessRoute('/document/global')) {
        authoritiesChildren.push({
          id: 'document-global',
          title: t('tabs.document'),
          icon: 'mdi-file-document',
          route: '/document/global',
          color: '#65a30d',
        });
      }

      if (canAccessRoute('/map/global')) {
        authoritiesChildren.push({
          id: 'map-global',
          title: t('tabs.map'),
          icon: 'mdi-map',
          route: '/map/global',
          color: '#4d7c0f',
        });
      }

      if (authoritiesChildren.length > 0) {
        items.push({
          id: 'authorities-group',
          title: t('tabs.authorities'),
          icon: 'mdi-home-group',
          color: '#84cc16',
          isGroup: true,
          children: authoritiesChildren,
          order: 5,
        });
      }
    }

    // ========================================
    // TEMPLATES
    // ========================================
    if (canAccessRoute('/template')) {
      items.push({
        id: 'templates',
        title: t('tabs.template'),
        icon: 'mdi-file-edit-outline',
        route: '/template',
        color: '#22c55e',
        order: 6,
      });
    }

    // ========================================
    // WEBSITE MANAGER
    // ========================================
    if (canAccessRoute('/company/website')) {
      items.push({
        id: 'companywebsite',
        title: t('tabs.websiteManager'),
        icon: 'mdi-web',
        route: '/company/website',
        color: '#a21caf',
        order: 7,
      });
    }

    // ========================================
    // FILES/RECORDS GROUP
    // ========================================
    if (canAccessAnyRoute(['/person', '/vehicleFile', '/apartmentFile'])) {
      const filesChildren: MenuItem[] = [];

      if (canAccessRoute('/person')) {
        filesChildren.push({
          id: 'person',
          title: t('tabs.person'),
          icon: 'mdi-account',
          route: '/person',
          color: '#8b5cf6',
        });
      }

      if (canAccessRoute('/vehicleFile')) {
        filesChildren.push({
          id: 'vehicleFile',
          title: t('tabs.vehicle'),
          icon: 'mdi-car',
          route: '/vehicleFile',
          color: '#7c3aed',
        });
      }

      if (canAccessRoute('/apartmentFile')) {
        filesChildren.push({
          id: 'apartmentFile',
          title: t('tabs.apartment'),
          icon: 'mdi-home',
          route: '/apartmentFile',
          color: '#6d28d9',
        });
      }

      if (filesChildren.length > 0) {
        items.push({
          id: 'files-group',
          title: t('tabs.records'),
          icon: 'mdi-folder-outline',
          color: '#8b5cf6',
          isGroup: true,
          children: filesChildren,
          order: 8,
        });
      }
    }

    // ========================================
    // EMPLOYEES GROUP
    // ========================================
    if (canAccessAnyRoute(['/employee', '/vacation', '/export'])) {
      const employeesChildren: MenuItem[] = [];

      if (canAccessRoute('/employee')) {
        employeesChildren.push({
          id: 'employee',
          title: t('tabs.employee'),
          icon: 'mdi-account-group',
          route: '/employee',
          color: '#a855f7',
        });
      }

      if (canAccessRoute('/vacation')) {
        employeesChildren.push({
          id: 'vacation',
          title: t('tabs.vacation'),
          icon: 'mdi-beach',
          route: '/vacation',
          color: '#9333ea',
        });
      }

      if (canAccessRoute('/export')) {
        employeesChildren.push({
          id: 'export',
          title: t('tabs.export'),
          icon: 'mdi-table-arrow-right',
          route: '/export',
          color: '#8b5cf6',
        });
      }

      if (employeesChildren.length > 0) {
        items.push({
          id: 'employees-group',
          title: t('tabs.employee'),
          icon: 'mdi-account-group-outline',
          color: '#a855f7',
          isGroup: true,
          children: employeesChildren,
          pinnable: true,
          order: 9,
        });
      }
    }

    // ========================================
    // COMPANIES GROUP
    // ========================================
    if (canAccessAnyRoute(['/company', '/companytype'])) {
      const companiesChildren: MenuItem[] = [];

      if (canAccessRoute('/company')) {
        companiesChildren.push({
          id: 'company',
          title: t('tabs.company'),
          icon: 'mdi-domain',
          route: '/company',
          color: '#d946ef',
        });
      }

      if (canAccessRoute('/companytype')) {
        companiesChildren.push({
          id: 'companytype',
          title: t('tabs.companyType'),
          icon: 'mdi-shape',
          route: '/companytype',
          color: '#c026d3',
        });
      }

      if (companiesChildren.length > 0) {
        items.push({
          id: 'companies-group',
          title: t('tabs.company'),
          icon: 'mdi-domain',
          color: '#d946ef',
          isGroup: true,
          children: companiesChildren,
          order: 10,
        });
      }
    }

    // ========================================
    // INVOICES GROUP
    // ========================================
    if (canAccessAnyRoute(['/invoice', '/invoiceitems'])) {
      const invoicesChildren: MenuItem[] = [];

      if (canAccessRoute('/invoice')) {
        invoicesChildren.push({
          id: 'invoice',
          title: t('tabs.invoice'),
          icon: 'mdi-currency-usd',
          route: '/invoice',
          color: '#ec4899',
        });
      }

      if (canAccessRoute('/invoiceitem')) {
        invoicesChildren.push({
          id: 'invoiceitem',
          title: t('tabs.invoiceItem'),
          icon: 'mdi-receipt',
          route: '/invoiceitems',
          color: '#db2777',
        });
      }

      if (invoicesChildren.length > 0) {
        items.push({
          id: 'invoices-group',
          title: t('tabs.invoices'),
          icon: 'mdi-currency-usd',
          color: '#ec4899',
          isGroup: true,
          children: invoicesChildren,
          order: 11,
        });
      }
    }

    // ========================================
    // REPORTS GROUP
    // ========================================
    if (canAccessAnyRoute([
      '/report',
      '/reportcategory',
      '/reporttemplate',
      '/reportcode',
      '/reportadditional',
      '/reportstatus',
    ])) {
      const reportsChildren: MenuItem[] = [];

      if (canAccessRoute('/report')) {
        reportsChildren.push({
          id: 'report',
          title: t('tabs.report'),
          icon: 'mdi-book-open-variant',
          route: '/report',
          color: '#f43f5e',
        });
      }

      if (canAccessRoute('/reportcategory')) {
        reportsChildren.push({
          id: 'reportcategory',
          title: t('tabs.category'),
          icon: 'mdi-shape',
          route: '/reportcategory',
          color: '#e11d48',
        });
      }

      if (canAccessRoute('/reporttemplate')) {
        reportsChildren.push({
          id: 'reporttemplate',
          title: t('tabs.template'),
          icon: 'mdi-file-document-edit',
          route: '/reporttemplate',
          color: '#be123c',
        });
      }

      if (canAccessRoute('/reportcode')) {
        reportsChildren.push({
          id: 'reportcode',
          title: t('tabs.code'),
          icon: 'mdi-code-tags',
          route: '/reportcode',
          color: '#9f1239',
        });
      }

      if (canAccessRoute('/reportadditional')) {
        reportsChildren.push({
          id: 'reportadditional',
          title: t('tabs.additional'),
          icon: 'mdi-plus-box',
          route: '/reportadditional',
          color: '#881337',
        });
      }

      if (canAccessRoute('/reportstatus')) {
        reportsChildren.push({
          id: 'reportstatus',
          title: t('tabs.status'),
          icon: 'mdi-clipboard-check',
          route: '/reportstatus',
          color: '#4c0519',
        });
      }

      if (reportsChildren.length > 0) {
        items.push({
          id: 'reports-group',
          title: t('tabs.report'),
          icon: 'mdi-book-open-variant',
          color: '#f43f5e',
          isGroup: true,
          children: reportsChildren,
          pinnable: true,
          order: 12,
        });
      }
    }

    // ========================================
    // DOCUMENTS GROUP
    // ========================================
    if (canAccessAnyRoute(['/documentarea', '/document']) || (documentAreas.value && documentAreas.value.length > 0)) {
      const documentsChildren: MenuItem[] = [];

      // Dynamic document areas (excluding global - shown under Authorities)
      if (documentAreas.value && documentAreas.value.length > 0) {
        documentAreas.value.forEach((area, index) => {
          // Skip global area (shown under Authorities group)
          if (area.key === 'global') return;

          if (area.is_active && area.permissions?.can_read) {
            documentsChildren.push({
              id: `documentarea-${area.key}`,
              title: area.name,
              icon: area.icon || 'mdi-file-document-outline',
              route: `/documentarea/${area.key}`,
              color: index % 2 === 0 ? '#ea580c' : '#c2410c',
            });
          }
        });
      }

      if (documentsChildren.length > 0) {
        items.push({
          id: 'documents-group',
          title: t('tabs.document'),
          icon: 'mdi-file-document-outline',
          color: '#f97316',
          isGroup: true,
          children: documentsChildren,
          pinnable: true,
          order: 13,
        });
      }
    }

    // ========================================
    // ORGANIZATION GROUP
    // ========================================
    if (canAccessAnyRoute(['/todo', '/calendar', '/application', '/mail'])) {
      const organizationChildren: MenuItem[] = [];

      if (canAccessRoute('/todo')) {
        organizationChildren.push({
          id: 'todo',
          title: t('tabs.todo'),
          icon: 'mdi-checkbox-marked-circle',
          route: '/todo',
          color: '#eab308',
        });
      }

      if (canAccessRoute('/calendar')) {
        organizationChildren.push({
          id: 'calendar',
          title: t('tabs.calendar'),
          icon: 'mdi-calendar',
          route: '/calendar',
          color: '#ca8a04',
        });
      }

      if (canAccessRoute('/application')) {
        organizationChildren.push({
          id: 'application',
          title: t('tabs.application'),
          icon: 'mdi-file-account',
          route: '/application',
          color: '#a16207',
        });
      }

      if (canAccessRoute('/mail')) {
        organizationChildren.push({
          id: 'mail',
          title: t('tabs.mail'),
          icon: 'mdi-email-outline',
          route: '/mail',
          color: '#06b6d4',
        });
      }

      if (organizationChildren.length > 0) {
        items.push({
          id: 'organization-group',
          title: t('tabs.organization'),
          icon: 'mdi-format-list-checks',
          color: '#eab308',
          isGroup: true,
          children: organizationChildren,
          pinnable: true,
          order: 14,
        });
      }
    }

    // ========================================
    // FILE MANAGER
    // ========================================
    if (canAccessRoute('/filemanager')) {
      items.push({
        id: 'filemanager',
        title: t('tabs.fileManager'),
        icon: 'mdi-file-tree',
        route: '/filemanager',
        color: '#84cc16',
        pinnable: true,
        order: 15,
      });
    }

    // ========================================
    // TRAINING GROUP
    // ========================================
    if (canAccessAnyRoute(['/trainingassign', '/test'])) {
      const trainingChildren: MenuItem[] = [];

      if (canAccessRoute('/trainingassign')) {
        trainingChildren.push({
          id: 'trainingassign',
          title: t('tabs.overview'),
          icon: 'mdi-view-dashboard',
          route: '/trainingassign',
          color: '#10b981',
        });
      }

      if (canAccessRoute('/test')) {
        trainingChildren.push({
          id: 'test',
          title: t('tabs.generateTest'),
          icon: 'mdi-file-document-edit',
          route: '/test',
          color: '#059669',
        });
      }

      if (trainingChildren.length > 0) {
        items.push({
          id: 'training-group',
          title: t('tabs.training'),
          icon: 'mdi-school',
          color: '#10b981',
          isGroup: true,
          children: trainingChildren,
          order: 16,
        });
      }
    }

    // ========================================
    // MISC GROUP
    // ========================================
    if (canAccessAnyRoute(['/fireprotection', '/cheatsheet'])) {
      const miscChildren: MenuItem[] = [];

      if (canAccessRoute('/fireprotection')) {
        miscChildren.push({
          id: 'fireprotection',
          title: t('tabs.fireProtection'),
          icon: 'mdi-fire',
          route: '/fireprotection',
          color: '#0ea5e9',
        });
      }

      if (canAccessRoute('/cheatsheet')) {
        miscChildren.push({
          id: 'cheatsheet',
          title: t('tabs.cheatsheet'),
          icon: 'mdi-file-document',
          route: '/cheatsheet',
          color: '#0284c7',
        });
      }

      if (miscChildren.length > 0) {
        items.push({
          id: 'misc-group',
          title: t('tabs.misc'),
          icon: 'mdi-fire',
          color: '#0ea5e9',
          isGroup: true,
          children: miscChildren,
          order: 17,
        });
      }
    }

    // ========================================
    // MAP
    // ========================================
    if (canAccessRoute('/map')) {
      items.push({
        id: 'map',
        title: t('tabs.map'),
        icon: 'mdi-map',
        route: '/map',
        color: '#3b82f6',
        pinnable: true,
        order: 18,
      });
    }

    // ========================================
    // PROFILE
    // ========================================
    if (canAccessRoute('/profile')) {
      items.push({
        id: 'profile',
        title: t('tabs.profile'),
        icon: 'mdi-account-circle',
        route: '/profile',
        color: '#6b7280',
        pinnable: true,
        order: 19,
      });
    }

    // ========================================
    // WIDGETS REMOVED - No longer shown in menu
    // ========================================
    // Widgets have been removed from the navigation menu as requested

    // ========================================
    // EXIT DESKTOP (Desktop only)
    // ========================================
    items.push({
      id: 'exit',
      title: t('desktop.exitDesktop'),
      icon: 'mdi-exit-to-app',
      action: 'exitDesktop',
      color: '#475569',
      hideOnSidebar: true,
      order: 100,
    });

    // Sort by order
    items.sort((a, b) => (a.order || 0) - (b.order || 0));

    menuItems.value = items;
    return items;
  }

  /**
   * Build hierarchical menu structure (for sidebar) with section headers
   */
  function buildHierarchicalMenu(): MenuItem[] {
    const items = menuItems.value.filter(item => !item.hideOnSidebar);
    const organized: MenuItem[] = [];

    // Helper to find item by id
    const findItem = (id: string) => items.find(item => item.id === id);

    // ========================================
    // SECTION: DASHBOARD (no header)
    // ========================================
    const dashboard = findItem('dashboard');
    if (dashboard) {
      organized.push(dashboard);
    }

    // ========================================
    // SECTION: ADMINISTRATION (Position 2)
    // ========================================
    const admin = findItem('admin');
    if (admin) {
      organized.push({
        id: 'section-admin',
        title: t('menu.sections.administration'),
        icon: 'mdi-shield-account-outline',
        isSectionHeader: true,
      });
      if (admin.isGroup && admin.children) {
        organized.push({
          ...admin,
          children: admin.children.filter(child => !child.hideOnSidebar)
        });
      } else {
        organized.push(admin);
      }
    }

    // ========================================
    // SECTION: EINSATZ & KOMMUNIKATION (Position 3)
    // ========================================
    const dispatchItems = [
      findItem('dispatch-group'),
      findItem('blackboard-group'),
      findItem('authorities-group'),
    ].filter(Boolean) as MenuItem[];

    if (dispatchItems.length > 0) {
      organized.push({
        id: 'section-dispatch',
        title: t('menu.sections.dispatch'),
        icon: 'mdi-fire-truck',
        isSectionHeader: true,
      });
      dispatchItems.forEach(item => {
        if (item.isGroup && item.children) {
          organized.push({
            ...item,
            children: item.children.filter(child => !child.hideOnSidebar)
          });
        } else {
          organized.push(item);
        }
      });
    }

    // ========================================
    // SECTION: AKTEN (Position 4) - includes Documents
    // ========================================
    const aktenItems = [
      findItem('files-group'),
      findItem('employees-group'),
      findItem('companies-group'),
      findItem('invoices-group'),
      findItem('reports-group'),
      findItem('documents-group'), // Documents now under Records
    ].filter(Boolean) as MenuItem[];

    if (aktenItems.length > 0) {
      organized.push({
        id: 'section-akten',
        title: t('menu.sections.records'),
        icon: 'mdi-folder-outline',
        isSectionHeader: true,
      });
      aktenItems.forEach(item => {
        if (item.isGroup && item.children) {
          organized.push({
            ...item,
            children: item.children.filter(child => !child.hideOnSidebar)
          });
        } else {
          organized.push(item);
        }
      });
    }

    // ========================================
    // SECTION: ORGANISATION
    // ========================================
    const organisationItems = [
      findItem('organization-group'),
      findItem('filemanager'),
      findItem('training-group'),
    ].filter(Boolean) as MenuItem[];

    if (organisationItems.length > 0) {
      organized.push({
        id: 'section-organization',
        title: t('menu.sections.organization'),
        icon: 'mdi-format-list-checks',
        isSectionHeader: true,
      });
      organisationItems.forEach(item => {
        if (item.isGroup && item.children) {
          organized.push({
            ...item,
            children: item.children.filter(child => !child.hideOnSidebar)
          });
        } else {
          organized.push(item);
        }
      });
    }

    // ========================================
    // SECTION: SONSTIGES (Misc)
    // ========================================
    const miscItems = [
      findItem('templates'),
      findItem('companywebsite'),
      findItem('misc-group'),
      findItem('map'),
      findItem('profile'),
    ].filter(Boolean) as MenuItem[];

    if (miscItems.length > 0) {
      organized.push({
        id: 'section-misc',
        title: t('menu.sections.misc'),
        icon: 'mdi-dots-horizontal',
        isSectionHeader: true,
      });
      miscItems.forEach(item => {
        if (item.isGroup && item.children) {
          organized.push({
            ...item,
            children: item.children.filter(child => !child.hideOnSidebar)
          });
        } else {
          organized.push(item);
        }
      });
    }

    // ========================================
    // SECTION: WATERDUCK (Individual App)
    // ========================================
    const waterduck = findItem('waterduck');
    if (waterduck) {
      organized.push(waterduck);
    }

    return organized;
  }

  /**
   * Get flat menu for desktop (no hierarchy)
   */
  function getDesktopMenu(): MenuItem[] {
    return menuItems.value.filter(item => !item.hideOnDesktop);
  }

  /**
   * Find menu item by ID (recursive)
   */
  function findMenuItem(id: string): MenuItem | null {
    function searchRecursive(items: MenuItem[]): MenuItem | null {
      for (const item of items) {
        if (item.id === id) return item;
        if (item.children) {
          const found = searchRecursive(item.children);
          if (found) return found;
        }
      }
      return null;
    }
    return searchRecursive(menuItems.value);
  }

  /**
   * Initialize menu (fetch data + generate items)
   */
  async function initializeMenu(): Promise<void> {
    loading.value = true;
    try {
      await Promise.all([
        fetchDocumentAreas(),
        fetchBlackboardAreas(),
      ]);
      generateMenuItems();
    } catch (error) {
      console.error('Failed to initialize menu:', error);
    } finally {
      loading.value = false;
    }
  }

  return {
    // State
    menuItems,
    documentAreas,
    blackboardAreas,
    loading,

    // Methods
    generateMenuItems,
    buildHierarchicalMenu,
    getDesktopMenu,
    fetchDocumentAreas,
    fetchBlackboardAreas,
    findMenuItem,
    canAccessRoute,
    canAccessAnyRoute,
    initializeMenu,
  };
}
