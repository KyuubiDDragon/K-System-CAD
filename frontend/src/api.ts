import axios, { type AxiosInstance, type InternalAxiosRequestConfig, type AxiosResponse, type AxiosError } from 'axios';
// Optional: Importiere Router/Store für Interceptors
// import router from '@/router';
// import { useAuthStore } from '@/stores/auth'; // Be cautious importing stores here to avoid circular dependencies

// --- Gemeinsame Konfiguration ---
const baseConfig = {
    baseURL: import.meta.env.VITE_API_URL, // Ensure VITE_API_URL is defined in your .env file
    headers: {
        'Content-Type': 'application/json',
    },
};

// --- Instanz für authentifizierte Aufrufe (mit Cookies) ---
export const apiClientAuth: AxiosInstance = axios.create({
    ...baseConfig,
    withCredentials: true, // Standardmäßig Credentials senden
});

// --- Instanz für öffentliche Aufrufe (ohne Cookies) ---
export const apiClientPublic: AxiosInstance = axios.create({
    ...baseConfig,
    withCredentials: false, // Standardmäßig KEINE Credentials senden
});

// --- Interceptors ---

// URL-Standardisierung: Fügt führende und Trailing-Slashes wenn nötig hinzu
const standardizeUrl = (config: InternalAxiosRequestConfig): InternalAxiosRequestConfig => {
    if (config.url) {
        // Stelle sicher, dass die URL mit einem / beginnt
        if (!config.url.startsWith('/')) {
            config.url = '/' + config.url;
        }

        // Prüfe, ob es sich um eine PHP-Datei handelt
        const isPhpFile = config.url.includes('.php');
        
        // Bei PHP-Dateien keinen zusätzlichen Slash vor dem Fragezeichen hinzufügen
        if (!isPhpFile) {
            // Füge / vor Fragezeichen hinzu, falls es nicht bereits vorhanden ist
            const questionMarkIndex = config.url.indexOf('?');
            if (questionMarkIndex > 0) {
                // Wenn ein Fragezeichen existiert, prüfe ob davor ein / ist
                if (config.url.charAt(questionMarkIndex - 1) !== '/') {
                    config.url = config.url.substring(0, questionMarkIndex) + '/' + config.url.substring(questionMarkIndex);
                }
            } else if (config.params && Object.keys(config.params).length > 0) {
                // Wenn keine Query-Parameter in der URL, aber in config.params, füge / am Ende hinzu
                if (!config.url.endsWith('/')) {
                    config.url = config.url + '/';
                }
            } else if (config.method !== 'get' && !config.url.endsWith('/')) {
                // Für nicht-GET-Anfragen ohne Query-Parameter, füge / am Ende hinzu
                config.url = config.url + '/';
            }
        } else {
            // Bei PHP-Dateien KEINEN Slash am Ende hinzufügen, auch wenn config.params vorhanden ist
            // Entferne sogar einen nachgestellten Slash, falls vorhanden
            if (config.url.endsWith('/')) {
                // Entferne den Slash am Ende, aber NUR wenn er nach ".php" kommt
                const phpIndex = config.url.indexOf('.php');
                if (phpIndex > 0 && phpIndex === config.url.length - 5 - 1) { // ".php/" = 5 Zeichen
                    config.url = config.url.substring(0, config.url.length - 1);
                }
            }
        }
    }
    return config;
};

// Token Interceptor - adds JWT token from cookies to Authorization header
const addTokenHeader = (config: InternalAxiosRequestConfig): InternalAxiosRequestConfig => {
    // Only add token header for authenticated client
    if (config.withCredentials) {
        let token: string | null = null;

        // First try to get token from localStorage/sessionStorage (for embedded browsers like FiveM)
        try {
            const storedToken = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
            // Filter out invalid values like 'null', 'undefined', empty strings
            if (storedToken && storedToken !== 'null' && storedToken !== 'undefined' && storedToken.trim() !== '') {
                token = storedToken;
                console.log('Retrieved token from localStorage/sessionStorage');
            } else if (storedToken && (storedToken === 'null' || storedToken === 'undefined')) {
                // Clean up invalid tokens
                localStorage.removeItem('auth_token');
                sessionStorage.removeItem('auth_token');
                console.log('Removed invalid token from storage');
            }
        } catch (e) {
            console.log('Unable to access localStorage/sessionStorage:', e);
        }

        // If no token in storage, try to get from cookie
        if (!token) {
            try {
                const cookies = document.cookie.split(';').reduce((acc, cookie) => {
                    const [key, value] = cookie.trim().split('=');
                    acc[key] = value;
                    return acc;
                }, {} as Record<string, string>);

                const cookieToken = cookies['auth_token'];
                // Filter out invalid values
                if (cookieToken && cookieToken !== 'null' && cookieToken !== 'undefined' && cookieToken.trim() !== '') {
                    token = cookieToken;
                    console.log('Retrieved token from cookie');
                }
            } catch (e) {
                console.log('Unable to access cookies:', e);
            }
        }

        // Only set Authorization header if we have a valid token
        if (token && !config.headers?.Authorization) {
            if (!config.headers) {
                config.headers = {};
            }
            config.headers.Authorization = `Bearer ${token}`;
            console.log('Added JWT token to Authorization header');
        } else if (!token) {
            console.log('No auth token found in storage or cookies');
        }
    }
    return config;
};

// Request Logger
const logRequest = (config: InternalAxiosRequestConfig): InternalAxiosRequestConfig => {
    console.log(
        'Axios Request wird gesendet:',
        config.method?.toUpperCase(),
        config.url,
        'Credentials:',
        config.withCredentials,
        'Has Auth Header:',
        !!config.headers?.Authorization
    );
    // You can add more request modifications here if needed
    return config;
};

// Request Error Handler
const handleRequestError = (error: AxiosError): Promise<AxiosError> => {
    console.error('Axios Request Fehler:', error);
    return Promise.reject(error);
};

// Response Success Handler (optional, often just pass through)
const handleResponseSuccess = (response: AxiosResponse): AxiosResponse => {
    // You can process successful responses globally here if needed
    return response;
};

// ✅ Refresh Token Logic with Request Queue
// NOTE: We use authStore.isRefreshing instead of a local variable to prevent race conditions
let failedQueue: Array<{ resolve: (value?: any) => void; reject: (reason?: any) => void }> = [];

const processQueue = (error: Error | null = null, token: string | null = null) => {
    failedQueue.forEach(prom => {
        if (error) {
            prom.reject(error);
        } else {
            prom.resolve(token);
        }
    });

    failedQueue = [];
};

// Response Error Handler with Auto-Refresh
const handleResponseError = async (error: AxiosError): Promise<any> => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean };

    console.error('Axios Response Fehler:', error.response?.status, originalRequest?.url, error.message);

    if (error.response && error.response.status === 401 && originalRequest) {
        // ✅ Don't retry refresh or logout endpoints (prevent infinite loop)
        if (originalRequest.url?.includes('action=refresh') || originalRequest.url?.includes('action=logout')) {
            console.error('🔄 Auth endpoint failed with 401 - ' + (originalRequest.url?.includes('logout') ? 'already logged out' : 'refresh failed'));
            // Don't attempt refresh if we're already in logout/refresh flow
            await performLogout();
            return Promise.reject(error);
        }

        // ✅ Only retry once per request
        if (originalRequest._retry) {
            console.error('🔄 Already retried this request - logging out');
            await performLogout();
            return Promise.reject(error);
        }

        originalRequest._retry = true;

        // ✅ CRITICAL: Use authStore.isRefreshing instead of local variable to prevent race conditions
        const { useAuthStore } = await import('@/stores/auth');
        const authStore = useAuthStore();

        // ✅ If already refreshing, queue this request
        if (authStore.isRefreshing) {
            console.log('🔄 Token refresh already in progress, queueing request');
            return new Promise((resolve, reject) => {
                failedQueue.push({ resolve, reject });
            }).then(() => {
                // Retry original request after refresh completes
                return apiClientAuth(originalRequest);
            }).catch(err => {
                return Promise.reject(err);
            });
        }

        // ✅ Call refreshToken - it will set isRefreshing = true internally
        try {
            console.log('🔄 Access token expired, attempting refresh...');

            await authStore.refreshToken();

            console.log('✅ Token refreshed successfully, retrying original request');

            // ✅ CRITICAL: Update Authorization header with NEW token
            const newToken = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
            if (newToken && originalRequest.headers) {
                originalRequest.headers.Authorization = `Bearer ${newToken}`;
                console.log('✅ Updated Authorization header with new token');
            }

            // Process queued requests
            processQueue(null, 'success');

            // Retry the original request with NEW token in header
            return apiClientAuth(originalRequest);

        } catch (refreshError) {
            console.error('❌ Token refresh failed:', refreshError);

            // Process queued requests with error
            processQueue(new Error('Token refresh failed'), null);

            // Refresh failed - logout required
            await performLogout();
            return Promise.reject(refreshError);
        }
    }

    return Promise.reject(error);
};

// Helper function to perform logout
async function performLogout() {
    console.log('🚪 Performing logout due to auth failure');

    // Clear any stale tokens from localStorage AND sessionStorage
    try {
        // Clear localStorage
        localStorage.removeItem('authToken');
        localStorage.removeItem('token');
        localStorage.removeItem('jwt');
        localStorage.removeItem('auth_token');

        // Clear sessionStorage (CRITICAL - prevents infinite loop!)
        sessionStorage.removeItem('authToken');
        sessionStorage.removeItem('token');
        sessionStorage.removeItem('jwt');
        sessionStorage.removeItem('auth_token');

        console.log('✅ Cleared stale tokens from localStorage and sessionStorage');
    } catch (e) {
        console.error('❌ Error clearing storage tokens:', e);
    }

    // Use dynamic import to avoid circular dependency
    try {
        const router = (await import('@/router')).default;
        const { useAuthStore } = await import('@/stores/auth');
        const authStore = useAuthStore();

        // Only trigger logout if user is currently logged in
        if (authStore.isLoggedIn) {
            console.log('Triggering logout');
            // Clear auth state without calling backend (already returned 401)
            authStore._setUser(null);
            authStore.isLoading = false;

            // Navigate to login
            await router.push('/');
        }
    } catch (importError) {
        console.error('Error importing auth dependencies:', importError);
    }
}

// Add interceptors to the authenticated client
// Füge den URL-Standardisierer VOR dem Logger hinzu, damit er geloggt wird
apiClientAuth.interceptors.request.use(standardizeUrl, handleRequestError);
apiClientAuth.interceptors.request.use(addTokenHeader, handleRequestError);
apiClientAuth.interceptors.request.use(logRequest, handleRequestError);
apiClientAuth.interceptors.response.use(handleResponseSuccess, handleResponseError);

// Add interceptors to the public client
apiClientPublic.interceptors.request.use(standardizeUrl, handleRequestError);
apiClientPublic.interceptors.request.use(logRequest, handleRequestError);
apiClientPublic.interceptors.response.use(handleResponseSuccess, handleResponseError);

// --- Optional: Export specific API functions ---
// Example structure if you prefer exporting functions over instances:
/*
interface NewsItem { // Define your types
    id: number;
    title: string;
    // ... other properties
}

export const newsApi = {
    getAll: async (): Promise<NewsItem[]> => {
        // Decide whether to use apiClientAuth or apiClientPublic
        const response = await apiClientAuth.get<{ news: NewsItem[] }>('/index.php', {
            params: { action: 'get-news', _t: Date.now() }
        });
        return response.data.news; // Assuming API returns { news: [...] }
    },
    // ... other news related functions
}

export const publicApi = {
   getSomeInfo: async (): Promise<any> => { // Replace 'any' with a specific type
      const response = await apiClientPublic.get('/index.php', {
          params: { action: 'get-public-info' }
      });
      return response.data;
   }
}
*/

// --- New API instance ---
const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL, // Optional: Set base URL if consistent
    withCredentials: true, // <-- IMPORTANT: This tells Axios to send cookies
    headers: {
        'Content-Type': 'application/json', // Adjust if needed, often set automatically
        // You might not need the Authorization header anymore if using cookies
    }
});

export default api;

// We are exporting the instances directly as per the original file structure.
// Ensure you are consistently using these named exports in your components.

// Authority API endpoints
export const authorityApi = {
    // Get all authorities
    getAuthorities: async () => {
        const response = await apiClientAuth.get('/admin/authority/index.php');
        return response;
    },
    
    // Get a single authority
    getAuthority: async (id: number) => {
        const response = await apiClientAuth.get(`/admin/authority/index.php/${id}`);
        return response;
    },
    
    // Get all features
    getFeatures: async () => {
        const response = await apiClientAuth.get('/admin/authority/index.php', {
            params: { action: 'features' }
        });
        return response;
    },
    
    // Get features for a specific authority
    getAuthorityFeatures: async (id: number) => {
        const response = await apiClientAuth.get(`/admin/authority/index.php/${id}/features`);
        return response;
    },
    
    // Create a new authority
    createAuthority: async (data: any) => {
        const response = await apiClientAuth.post('/admin/authority/index.php', data);
        return response;
    },
    
    // Update an authority
    updateAuthority: async (id: number, data: any) => {
        const response = await apiClientAuth.put(`/admin/authority/index.php/${id}`, data);
        return response;
    },
    
    // Delete an authority
    deleteAuthority: async (id: number) => {
        const response = await apiClientAuth.delete(`/admin/authority/index.php/${id}`);
        return response;
    },
    
    // Update features for an authority
    updateAuthorityFeatures: async (id: number, features: number[]) => {
        const response = await apiClientAuth.post(`/admin/authority/index.php/${id}/features`, { features });
        return response;
    }
};

// Neue API-Funktionen für Desktop-Einstellungen
export const desktopApi = {
    /**
     * Holt die Desktopeinstellungen des aktuellen Benutzers
     */
    getSettings: async () => {
        console.log('desktopApi.getSettings() wird aufgerufen');
        try {
            const response = await apiClientAuth.get('/desktop/', {
                params: { 
                    action: 'getSettings',
                    _t: Date.now() // Cache-Busting
                }
            });
            console.log('desktopApi.getSettings() - Antwort erhalten:', response.data);
            return response.data;
        } catch (error) {
            console.error('desktopApi.getSettings() - Fehler:', error);
            throw error;
        }
    },

    /**
     * Speichert die Desktopeinstellungen für den aktuellen Benutzer
     * @param settings Objekt mit den Einstellungen (background, icon_positions, window_layouts, theme)
     */
    saveSettings: async (settings: {
        background?: string,
        icon_positions?: object,
        window_layouts?: object,
        theme?: string
    }) => {
        console.log('desktopApi.saveSettings() wird aufgerufen mit:', settings);
        
        // Formatierte JSON-Daten vorbereiten
        const formattedData = {
            ...settings,
            icon_positions: typeof settings.icon_positions === 'object' 
                ? JSON.stringify(settings.icon_positions)
                : settings.icon_positions,
            window_layouts: typeof settings.window_layouts === 'object'
                ? JSON.stringify(settings.window_layouts)
                : settings.window_layouts
        };

        console.log('desktopApi.saveSettings() - Formatierte JSON-Daten:', formattedData);

        try {
            const response = await apiClientAuth.post('/desktop/', 
                formattedData,
                {
                    params: { action: 'saveSettings' }
                }
            );
            console.log('desktopApi.saveSettings() - Antwort erhalten:', response.data);
            return response.data;
        } catch (error) {
            console.error('desktopApi.saveSettings() - Fehler:', error);
            throw error;
        }
    },

    /**
     * Lädt ein neues Hintergrundbild hoch
     * @param backgroundFile Das Bilddatei-Objekt
     */
    uploadBackground: async (backgroundFile: File) => {
        const formData = new FormData();
        formData.append('background', backgroundFile);

        const response = await apiClientAuth.post('/desktop/', 
            formData,
            {
                params: { action: 'uploadBackground' },
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }
        );
        return response.data;
    },

    /**
     * Updates specific desktop settings (alias for saveSettings for widget compatibility)
     * @param settings Objekt mit den zu aktualisierenden Einstellungen
     */
    updateSettings: async (settings: {
        widgets?: {
            active?: string[],
            positions?: object
        },
        background?: string,
        icon_positions?: object,
        window_layouts?: object,
        theme?: string
    }) => {
        console.log('desktopApi.updateSettings() wird aufgerufen mit:', settings);
        
        // Convert widgets format to flat structure if present
        const flatSettings: any = { ...settings };
        if (settings.widgets) {
            flatSettings.widget_state = JSON.stringify(settings.widgets);
            delete flatSettings.widgets;
        }
        
        return await desktopApi.saveSettings(flatSettings);
    }
};

/**
 * Company Website API
 */
export const companyWebsiteApi = {
  // Website Konfiguration
  getWebsiteConfig: async (companyId: number) => {
    const response = await apiClientAuth.get('/company/website/?action=getConfig', {
      params: { companyId }
    });
    return response.data;
  },

  saveWebsiteConfig: async (companyId: number, config: any) => {
    const response = await apiClientAuth.post('/company/website/?action=saveConfig', {
      companyId,
      config
    });
    return response.data;
  },

  // Pages
  getPages: async (websiteId: number) => {
    const response = await apiClientAuth.get('/company/website/?action=getPages', {
      params: { websiteId }
    });
    return response.data;
  },

  savePage: async (websiteId: number, page: any) => {
    const response = await apiClientAuth.post('/company/website/?action=savePage', {
      websiteId,
      page
    });
    return response.data;
  },

  deletePage: async (pageId: number) => {
    const response = await apiClientAuth.post('/company/website/?action=deletePage', {
      pageId
    });
    return response.data;
  },

  // Posts/News
  getPosts: async (websiteId: number) => {
    const response = await apiClientAuth.get('/company/website/?action=getPosts', {
      params: { websiteId }
    });
    return response.data;
  },

  savePost: async (websiteId: number, post: any) => {
    const response = await apiClientAuth.post('/company/website/?action=savePost', {
      websiteId,
      post
    });
    return response.data;
  },

  deletePost: async (postId: number) => {
    const response = await apiClientAuth.post('/company/website/?action=deletePost', {
      postId
    });
    return response.data;
  },

  // Categories
  getCategories: async (websiteId: number) => {
    const response = await apiClientAuth.get('/company/website/?action=getCategories', {
      params: { websiteId }
    });
    return response.data;
  },

  saveCategory: async (websiteId: number, category: any) => {
    const response = await apiClientAuth.post('/company/website/?action=saveCategory', {
      websiteId,
      category
    });
    return response.data;
  },

  deleteCategory: async (categoryId: number) => {
    const response = await apiClientAuth.post('/company/website/?action=deleteCategory', {
      categoryId
    });
    return response.data;
  },

  // Media/Files
  getMedia: async (websiteId: number) => {
    const response = await apiClientAuth.get('/company/website/?action=getMedia', {
      params: { websiteId }
    });
    return response.data;
  },

  uploadMedia: async (websiteId: number, formData: FormData) => {
    const response = await apiClientAuth.post('/company/website/?action=uploadMedia', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      params: {
        websiteId
      }
    });
    return response.data;
  },

  deleteMedia: async (mediaId: number) => {
    const response = await apiClientAuth.post('/company/website/?action=deleteMedia', {
      mediaId
    });
    return response.data;
  },

  // Contact forms
  getContactSubmissions: async (websiteId: number) => {
    const response = await apiClientAuth.get('/company/website/?action=getContactSubmissions', {
      params: { websiteId }
    });
    return response.data;
  },

  markSubmissionAsRead: async (submissionId: number) => {
    const response = await apiClientAuth.post('/company/website/?action=markSubmissionAsRead', {
      submissionId
    });
    return response.data;
  }
};