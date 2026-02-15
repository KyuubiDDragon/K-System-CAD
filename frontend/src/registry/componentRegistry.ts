/**
 * Komponentenregistry
 * Hilfsfunktionen zum Auflösen und Laden von Komponenten für Desktop-Fenster
 */
import { defineAsyncComponent } from 'vue'
import { componentsMap } from './componentsMap'
import router from '@/router'
import { useModulePermission } from '@/composables/useModulePermission'

/**
 * Ermittelt die passende Komponente für ein Desktop-Fenster basierend auf appId oder route
 * 
 * @param appId - Die ID der App (z.B. calculator, weather)
 * @param route - Die Route, falls es sich um eine routenbasierte Anwendung handelt
 * @returns Die aufzulösende Vue-Komponente oder null
 */
export const getComponentForWindow = (appId: string | null | undefined, route: string | null | undefined) => {
  console.log(`Komponente suchen für: appId=${appId}, route=${route}`);
  
  // 1. Direkt nach App-ID suchen (eingebaute Apps)
  if (appId && componentsMap[appId]) {
    console.log(`Komponente gefunden über appId: ${appId}`);
    return defineAsyncComponent(componentsMap[appId]);
  }
  
  // 1.1 Versuche appId mit Unterstrich statt Bindestrich (admin-employees -> admin_employees)
  if (appId && appId.includes('-')) {
    const normalizedAppId = appId.replace(/-/g, '_');
    if (componentsMap[normalizedAppId]) {
      console.log(`Komponente gefunden über normalisierte appId: ${normalizedAppId}`);
      return defineAsyncComponent(componentsMap[normalizedAppId]);
    }
  }
  
  // 2. Nach Route suchen (normale Anwendungen)
  if (route) {
    // Routenpfad normalisieren: /employee -> employee
    const routePath = route.replace(/^\//, '').split('/')[0];
    console.log(`Normalisierter Routenpfad: ${routePath}`);
    
    // Speziell behandeln: Blackboard-Routen (/blackboard/global, /blackboard/area/:id)
    if (routePath === 'blackboard' && route.includes('/')) {
      const segments = route.split('/');
      if (segments.length >= 3) {
        const blackboardType = segments[2];
        const blackboardKey = `blackboard${blackboardType.charAt(0).toUpperCase() + blackboardType.slice(1)}`;
        console.log(`Suche Blackboard-Komponente: ${blackboardKey}`);
        
        if (componentsMap[blackboardKey]) {
          return defineAsyncComponent(componentsMap[blackboardKey]);
        }
      }
    }
    
    // Speziell behandeln: /admin/xyz -> admin_xyz
    if (routePath === 'admin' && route.includes('/')) {
      const adminRouteSegments = route.split('/');
      if (adminRouteSegments.length >= 3) {
        const adminRoute = `admin_${adminRouteSegments[2]}`;
        console.log(`Suche Admin-Komponente: ${adminRoute}`);
        
        if (componentsMap[adminRoute]) {
          return defineAsyncComponent(componentsMap[adminRoute]);
        }
      }
    }
    
    // Normale Routen
    if (componentsMap[routePath]) {
      console.log(`Komponente gefunden über Route: ${routePath}`);
      return defineAsyncComponent(componentsMap[routePath]);
    }
    
    console.log(`Keine Komponente gefunden für Route: ${route}`);
  }
  
  // 3. Fallback auf Standard-Komponente
  console.log('Fallback auf Default-Komponente');
  return defineAsyncComponent(componentsMap['default']);
};

/**
 * Ermittelt Parameter für Komponenten basierend auf der Route
 * 
 * @param route - Die Route der Anwendung
 * @returns Ein Objekt mit relevanten Parametern für die Komponente
 */
export const getRouteParams = (route: string | null | undefined) => {
  if (!route) {
    return {};
  }
  
  try {
    console.log('🔑 getRouteParams - Starting permission check for route:', route);
    
    // Parse query parameters manually from the route string
    const urlParts = route.split('?');
    const routePath = urlParts[0];
    const queryString = urlParts[1] || '';
    const queryParams: Record<string, string> = {};
    
    if (queryString) {
      const params = new URLSearchParams(queryString);
      params.forEach((value, key) => {
        queryParams[key] = value;
      });
      console.log('🔑 Parsed query params:', queryParams);
      console.log('🔑 Query params keys:', Object.keys(queryParams));
      console.log('🔑 Query params values:', Object.values(queryParams));
    }
    
    // Import auth store to check for ALL_PERMISSIONS
    // Note: We can't directly import and use it at module level due to circular dependencies
    let hasAllPerms = false;
    let userPermissions: string[] = [];

    try {
      // Use the composable to check for ALL_PERMISSIONS
      console.log('🔑 Checking ALL_PERMISSIONS using composable');
      const { hasAllPermissions } = useModulePermission();
      hasAllPerms = hasAllPermissions.value;
      console.log('🔑 User has ALL_PERMISSIONS:', hasAllPerms);

      // Also get raw permissions for debugging
      const authStore = window['useAuthStore'] ? window['useAuthStore']() : null;
      if (authStore) {
        userPermissions = authStore.userPermissions || [];
        console.log('🔑 Found user permissions:', userPermissions);
      }
    } catch (e) {
      console.warn('⚠️ Error checking ALL_PERMISSIONS:', e);
    }
    
    // Route über Router auflösen (nur für params, nicht für query)
    const resolvedRoute = router.resolve(routePath);
    console.log('🔑 Resolved route:', resolvedRoute.path);
    
    // Set default permissions based on user role/permissions
    // If user has ALL_PERMISSIONS, they get full access everywhere
    const baseParams = {
      ...resolvedRoute.params,
      ...queryParams, // Include manually parsed query params
      desktop_window: true,
      hideLeftNav: true,
      hideTopNav: false,
      // Important: Set permissions based on ALL_PERMISSIONS flag
      // This aligns with the backend permission structure (can_read, can_write, can_delete)
      canEdit: hasAllPerms ? true : true,   // For non-admins, set based on specific permission check
      canDelete: hasAllPerms ? true : true, // For non-admins, set based on specific permission check
      canCreate: hasAllPerms ? true : true, // For non-admins, set based on specific permission check
      // Also add ALL_PERMISSIONS flag for components that check it directly
      allPermissions: hasAllPerms,
      // Add raw permissions list for debugging
      _rawPermissions: userPermissions,
      // Include query params in meta for components that check props.meta
      meta: {
        ...queryParams, // Include manually parsed query params
        ...resolvedRoute.params
      }
    };
    
    console.log('🔑 Permission flags set:', { 
      canEdit: baseParams.canEdit, 
      canDelete: baseParams.canDelete, 
      canCreate: baseParams.canCreate,
      allPermissions: baseParams.allPermissions 
    });
    
    console.log('🔑 Full baseParams object:', baseParams);
    console.log('🔑 baseParams keys:', Object.keys(baseParams));
    
    // Spezialbehandlung für DocumentView mit verschiedenen docType-Werten
    if (route.startsWith('/document/global')) {
      console.log(`📄 getRouteParams - Processing global document area`);
      return { 
        docType: 'global',
        areaId: 'global',
        key: 'global',
        site: 'global',
        ...baseParams,
        meta: {
          ...baseParams.meta,
          docType: 'global',
          areaId: 'global',
          site: 'global',
          key: 'global'
        }
      };
    } else if (route.startsWith('/documentarea/')) {
      // /documentarea/:key -> Spezialbehandlung für dynamische Dokumentenbereiche
      const key = route.split('/').pop();
      console.log(`📄 getRouteParams - Processing document area with key: ${key}`);
      return {
        docType: key,
        areaId: key,
        key: key,
        site: key,
        ...baseParams,
        meta: {
          ...baseParams.meta,
          docType: key,
          areaId: key,
          site: key,
          key: key
        }
      };
    } else if (route.startsWith('/blackboard/')) {
      // /blackboard/global or /blackboard/area/:id
      const boardType = route.split('/')[2];
      return {
        boardType,
        ...baseParams,
        meta: {
          ...baseParams.meta,
          boardType: boardType,
        }
      };
    }
    
    // Standardparameter für alle anderen Routen
    return baseParams;
  } catch (error) {
    console.error('Fehler beim Auflösen der Route:', error);
    return {
      desktop_window: true,
      hideLeftNav: true, 
      hideTopNav: false,
      // Include default permissions even in error case
      canEdit: true,
      canDelete: true,
      canCreate: true
    };
  }
}; 