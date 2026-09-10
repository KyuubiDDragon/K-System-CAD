// src/stores/auth.ts
import { defineStore } from 'pinia';
import { apiClientAuth } from '@/api'; // Pfad ggf. anpassen
import router from '@/router';
import type { User } from '@/types/User';

// Make auth store globally accessible for component registry
// This helps avoid circular dependencies
declare global {
    interface Window {
        useAuthStore?: () => ReturnType<typeof useAuthStore>;
    }
}

// 2. Mail Config Interface
export interface MailConfig {
    mail_header: string;
    mail_footer: string;
    mail_header_neutral: string;
    mail_footer_neutral: string;
    signature?: string;
}

// 3. Authority Branding Interface
export interface AuthorityBranding {
    logo_url?: string;
    primary_color?: string;
    secondary_color?: string;
    [key: string]: any; // Für zusätzliche Branding-Felder
}

// 4. State Interface
export interface AuthState {
    user: User | null;
    token: string | null;
    isLoading: boolean; // For login/checkAuthStatus
    isRefreshing: boolean; // For token refresh operations
    // ✅ Memory-Fallback für localStorage (Inkognito-Modus)
    mailConfigMemory: MailConfig | null;
    brandingMemory: AuthorityBranding | null;
    // ✅ Token Refresh Interval
    tokenRefreshInterval: number | null;
}

// 3. Pinia Store Definition
export const useAuthStore = defineStore('auth', {
    // --- State ---
    state: (): AuthState => ({
        user: null,
        token: null,
        isLoading: false,
        isRefreshing: false,
        mailConfigMemory: null, // ✅ Memory-Fallback für Inkognito-Modus
        brandingMemory: null, // ✅ Memory-Fallback für Inkognito-Modus
        tokenRefreshInterval: null, // ✅ Token Refresh Timer
    }),

    // --- Getters ---
    getters: {
        isLoggedIn(): boolean {
            return !!this.user;
        },
        currentUser(state): User | null {
            return state.user;
        },
        authIsLoading(state): boolean {
            return state.isLoading;
        },


        /**
         * Returns the user's permissions in their original format.
         * Can be either bitmask format (Record<string, number>) or legacy format (string[]).
         * For backwards compatibility, returns null if no user is logged in.
         */
        userPermissions(state): Record<string, number> | string[] | null {
            return state.user?.permissions ?? null;
        },

        /**
         * Detects the format of the user's permissions.
         * @returns 'bitmask' if permissions are in bitmask format (object)
         * @returns 'legacy' if permissions are in legacy format (array)
         * @returns 'none' if no permissions exist
         */
        permissionsFormat(state): 'bitmask' | 'legacy' | 'none' {
            const perms = state.user?.permissions;
            if (!perms) return 'none';
            if (Array.isArray(perms)) return 'legacy';
            if (typeof perms === 'object') return 'bitmask';
            return 'none';
        },

        /**
         * Returns permissions as bitmask object (for new code).
         * @returns Bitmask permissions object like {"employee": 7, "calendar": 23}
         * @returns null if permissions are not in bitmask format or user not logged in
         */
        permissionsBitmask(state): Record<string, number> | null {
            const perms = state.user?.permissions;
            if (perms && typeof perms === 'object' && !Array.isArray(perms)) {
                return perms;
            }
            return null;
        },

        /**
         * Returns permissions as legacy array (for old code during migration).
         * @returns Legacy permissions array like ["READ_EMPLOYEE", "WRITE_EMPLOYEE"]
         * @returns Empty array if permissions are not in legacy format or user not logged in
         */
        permissionsLegacy(state): string[] {
            const perms = state.user?.permissions;
            if (Array.isArray(perms)) {
                return perms;
            }
            // If bitmask format, return empty array (conversion should be done by usePermissionCheck)
            return [];
        },

        /**
         * Checks if the user has ALL_PERMISSIONS (legacy format only).
         * In bitmask format, ALL_PERMISSIONS is not used.
         * @returns true if user has ALL_PERMISSIONS in legacy format
         */
        hasAllPermissions(state): boolean {
            const perms = state.user?.permissions;
            if (Array.isArray(perms)) {
                return perms.includes('ALL_PERMISSIONS');
            }
            // For bitmask, ALL_PERMISSIONS is not used
            return false;
        },

        /**
         * Optional: Ähnlicher Getter für Rollen, falls benötigt.
         */
        userRoles(state): string[] {
            return state.user?.roles ?? [];
        },

        /**
         * ✅ NEU: Mail-Config aus localStorage (mit Memory-Fallback für Inkognito)
         */
        userMailConfig(state): MailConfig | null {
            // Primary: Aus User-Object (falls vorhanden)
            if (state.user?.mail_header) {
                return {
                    mail_header: state.user.mail_header,
                    mail_footer: state.user.mail_footer || '',
                    mail_header_neutral: state.user.mail_header_neutral || '',
                    mail_footer_neutral: state.user.mail_footer_neutral || '',
                    signature: state.user.signature
                };
            }

            // Secondary: localStorage (Normal-Fall)
            try {
                const stored = localStorage.getItem('user_mail_config');
                if (stored) return JSON.parse(stored);
            } catch (e) {
                console.warn('localStorage nicht lesbar für user_mail_config');
            }

            // Tertiary: Memory-Fallback (Inkognito-Modus)
            return state.mailConfigMemory;
        },

        /**
         * ✅ NEU: Authority-Branding aus localStorage (mit Memory-Fallback)
         */
        authorityBranding(state): AuthorityBranding | null {
            // Primary: localStorage
            try {
                const stored = localStorage.getItem('authority_branding');
                if (stored) return JSON.parse(stored);
            } catch (e) {
                console.warn('localStorage nicht lesbar für authority_branding');
            }

            // Fallback: Memory (Inkognito-Modus)
            return state.brandingMemory;
        }
    },

    // --- Actions ---
    actions: {
        /**
         * Internal helper function to set the user object.
         * For Phase 3 migration: Preserves permissions in their original format (bitmask or legacy).
         * - Bitmask format: {"employee": 7, "calendar": 23} (from JWT)
         * - Legacy format: ["READ_EMPLOYEE", "WRITE_EMPLOYEE"] (from old API)
         * Roles are still normalized to string array format.
         */
        _setUser(userData: User | null) {
            if (userData) {
                console.log('🔄 _setUser: Received raw data:', JSON.stringify(userData));

                // === PERMISSIONS: Store as-is (preserve format) ===
                let finalPermissions: Record<string, number> | string[] = [];
                const rawPermissions = userData.permissions;

                console.log('  Processing permissions:', JSON.stringify(rawPermissions));
                if (rawPermissions && typeof rawPermissions === 'object' && !Array.isArray(rawPermissions)) {
                    // Bitmask format: Store as-is (object with module: bitmask pairs)
                    console.log('  -> Permissions are bitmask format (object), storing as-is');
                    finalPermissions = rawPermissions as Record<string, number>;
                } else if (Array.isArray(rawPermissions)) {
                    // Legacy format: Store as-is (array of permission strings)
                    console.log('  -> Permissions are legacy format (array), storing as-is');
                    finalPermissions = rawPermissions.filter(item => typeof item === 'string') as string[];
                } else if (typeof rawPermissions === 'string' && (rawPermissions as string).trim() !== '') {
                    // Single string: Convert to array for consistency
                    console.log('  -> Permissions is a single string, wrapping in array');
                    finalPermissions = [rawPermissions];
                } else {
                    // Null, undefined, or empty: Default to empty array
                    console.log('  -> Permissions is null, undefined, or empty. Setting to []');
                    finalPermissions = [];
                }

                // === ROLES: Normalize to string array (existing behavior) ===
                let finalRoles: string[] = [];
                const rawRoles = userData.roles;

                console.log('  Processing roles:', JSON.stringify(rawRoles));
                if (rawRoles && typeof rawRoles === 'object' && !Array.isArray(rawRoles)) {
                    // Object: Convert values to array
                    console.log('  -> Roles is object, converting to array');
                    try {
                        finalRoles = Object.values(rawRoles).filter(item => typeof item === 'string') as string[];
                        console.log('  <- Converted roles:', JSON.stringify(finalRoles));
                    } catch (e) {
                        console.error('Error converting roles object:', e);
                        finalRoles = [];
                    }
                } else if (Array.isArray(rawRoles)) {
                    // Array: Store as-is
                    console.log('  -> Roles is already an array');
                    finalRoles = rawRoles.filter(item => typeof item === 'string') as string[];
                } else if (typeof rawRoles === 'string' && (rawRoles as string).trim() !== '') {
                    // Single string: Wrap in array
                    console.log('  -> Roles is a single string, wrapping in array');
                    finalRoles = [rawRoles];
                } else {
                    // Null, undefined, or empty
                    console.log('  -> Roles is null, undefined, or empty. Setting to []');
                    finalRoles = [];
                }

                // Remove duplicates from roles
                finalRoles = Array.from(new Set(finalRoles));

                // Set user state with permissions in original format
                this.user = {
                    ...userData,
                    roles: finalRoles, // Always an array
                    permissions: finalPermissions, // Can be bitmask object or legacy array
                };

            } else {
                // User is null (logout or error)
                this.user = null;
            }
            console.log('🔄 Auth state updated, final user state:', JSON.stringify(this.user));
        },

        _updateUserFields(payload: Partial<User>) {
            if (this.user) {
                const updatedUser = { ...this.user, ...payload };

                // === PERMISSIONS: Preserve format (bitmask or legacy) ===
                if (payload.permissions !== undefined) {
                    let finalPermissions: Record<string, number> | string[] = [];
                    const rawPermissions = payload.permissions;

                    if (rawPermissions && typeof rawPermissions === 'object' && !Array.isArray(rawPermissions)) {
                        // Bitmask format: Store as-is
                        finalPermissions = rawPermissions as Record<string, number>;
                    } else if (Array.isArray(rawPermissions)) {
                        // Legacy format: Store as-is (filter to ensure strings)
                        finalPermissions = rawPermissions.filter(item => typeof item === 'string') as string[];
                    } else if (typeof rawPermissions === 'string' && (rawPermissions as string).trim() !== '') {
                        // Single string: Wrap in array
                        finalPermissions = [rawPermissions];
                    } else {
                        // Null, undefined, or empty
                        finalPermissions = [];
                    }
                    updatedUser.permissions = finalPermissions;
                }

                // === ROLES: Normalize to string array ===
                if (payload.roles !== undefined) {
                    let finalRoles: string[] = [];
                    const rawRoles = payload.roles;

                    if (rawRoles && typeof rawRoles === 'object' && !Array.isArray(rawRoles)) {
                        finalRoles = Object.values(rawRoles).filter(item => typeof item === 'string') as string[];
                    } else if (Array.isArray(rawRoles)) {
                        finalRoles = rawRoles.filter(item => typeof item === 'string') as string[];
                    } else if (typeof rawRoles === 'string' && (rawRoles as string).trim() !== '') {
                        finalRoles = [rawRoles];
                    } else {
                        finalRoles = [];
                    }
                    updatedUser.roles = Array.from(new Set(finalRoles));
                }

                this.user = updatedUser;
                console.log('🔄 Auth state partially updated with:', payload);
            } else {
                console.warn('⚠ _updateUserFields: No user logged in, fields cannot be updated.');
            }
        },

        // --- User data actions (fetchUser, checkAuthStatus, login, logout) ---
        // All user data and permissions come from JWT token
        // No separate permission loading needed

        async fetchUser() {
            console.log('🚀 FetchUser: Lade Benutzerdaten...');
            this.isLoading = true;
            try {
                // JWT token contains all user data including permissions in bitmask format
                const response = await apiClientAuth.get<User>('/user/?action=getUserOverview');
                if (response.data && response.data.id) {
                    console.log('✅ FetchUser: Benutzerdaten empfangen.');
                    this._setUser(response.data);
                } else {
                    console.warn('⚠ FetchUser: Keine gültigen Benutzerdaten in Antwort, setze Logout-Status.');
                    this._setUser(null);
                }
            } catch (error: any) {
                console.warn('⚠ FetchUser: Fehler beim Laden der Benutzerdaten (wahrscheinlich nicht authentifiziert).');
                this._setUser(null);
            } finally {
                this.isLoading = false;
                console.log('🚀 FetchUser: Abgeschlossen.');
            }
        },


        /**
         * Prueft die Sitzung serverseitig.
         *
         * @param force Erzwingt die Pruefung, auch wenn bereits ein Nutzer im
         *   Store steht. Der Router nutzt das beim ersten Laden einer Seite:
         *   Ein Nutzer aus einer abgelaufenen Sitzung ueberlebt das Neuladen im
         *   Store, der Server wurde aber nie gefragt. Der Guard sah dadurch
         *   einen angemeldeten Nutzer, liess die geschuetzte Route zu, und die
         *   Ansicht blieb nach dem 401 leer stehen - ohne Hinweis.
         */
        async checkAuthStatus(force = false) {
            if ((this.user && !force) || this.isLoading) {
                return;
            }

            this.isLoading = true;

            // Don't clear tokens on checkAuthStatus - they might be needed for FiveM
            // Tokens will be cleared on logout or 401 errors instead

            try {
                const response = await apiClientAuth.get<User>('/user/?action=getUserOverview');

                if (response.data && response.data.id) {
                    // JWT contains permissions in bitmask format - no separate loading needed
                    this._setUser(response.data);
                    // Start automatic token refresh (every 10 minutes)
                    this.startTokenRefreshInterval();
                } else {
                    this._setUser(null);
                }
            } catch (error: any) {
                this._setUser(null);
            } finally {
                this.isLoading = false;
            }
        },

        async login(credentials: Record<string, any>): Promise<boolean> {
             // login wird aufgerufen, wenn der User das Login-Formular absendet
            console.log('🚀 Login: Versuche Login...');
            this.isLoading = true;

            try {
                // JWT token contains user data and permissions in bitmask format
                const response = await apiClientAuth.post<{ message: string; user: User; token?: string }>('/login/?action=login', credentials);

                if (response.data && response.data.user) {
                    console.log('✅ Login: Erfolg. Benutzerdaten empfangen.');
                    this._setUser(response.data.user);

                    // ✅ NEU: Mail-Config in localStorage speichern (mit Fallback)
                    if (response.data.user) {
                        const mailConfig: MailConfig = {
                            mail_header: response.data.user.mail_header || '',
                            mail_footer: response.data.user.mail_footer || '',
                            mail_header_neutral: response.data.user.mail_header_neutral || '',
                            mail_footer_neutral: response.data.user.mail_footer_neutral || '',
                            signature: response.data.user.signature
                        };

                        try {
                            localStorage.setItem('user_mail_config', JSON.stringify(mailConfig));
                            console.log('✅ Login: Mail-Config in localStorage gespeichert');
                        } catch (e) {
                            console.warn('⚠️ Login: localStorage nicht verfügbar (Inkognito-Modus?), speichere Mail-Config im Memory');
                            this.mailConfigMemory = mailConfig;
                        }
                    }

                    // ✅ NEU: Authority-Branding in localStorage (mit Fallback)
                    if ((response.data as any).authority_branding) {
                        try {
                            localStorage.setItem('authority_branding', JSON.stringify((response.data as any).authority_branding));
                            console.log('✅ Login: Authority-Branding in localStorage gespeichert');
                        } catch (e) {
                            console.warn('⚠️ Login: localStorage nicht verfügbar, speichere Branding im Memory');
                            this.brandingMemory = (response.data as any).authority_branding;
                        }
                    }

                    // NEU: Speichere Token in localStorage für FiveM Kompatibilität
                    try {
                        let token = null;
                        
                        // Zuerst prüfen ob Token in Response enthalten ist (für FiveM)
                        if (response.data.token) {
                            token = response.data.token;
                            console.log('✅ Login: Token aus Response erhalten (FiveM-Modus)');
                        } else {
                            // Fallback: Versuche Token aus Cookie zu extrahieren
                            const cookies = document.cookie.split(';').reduce((acc, cookie) => {
                                const [key, value] = cookie.trim().split('=');
                                acc[key] = value;
                                return acc;
                            }, {} as Record<string, string>);
                            
                            token = cookies['auth_token'];
                            if (token) {
                                console.log('✅ Login: Token aus Cookie extrahiert');
                            }
                        }
                        
                        if (token) {
                            // Speichere in localStorage und sessionStorage für bessere Kompatibilität
                            localStorage.setItem('auth_token', token);
                            sessionStorage.setItem('auth_token', token);
                            this.token = token; // Speichere auch im Store
                            console.log('✅ Login: Token in localStorage/sessionStorage gespeichert für FiveM Kompatibilität');
                        } else {
                            console.warn('⚠ Login: Kein auth_token gefunden (weder in Response noch in Cookie)');
                        }
                    } catch (e) {
                        console.error('❌ Login: Fehler beim Speichern des Tokens:', e);
                    }

                    // JWT contains permissions in bitmask format - no separate loading needed
                    console.log('✅ Login: Permissions present in JWT/response');

                    // Start automatic token refresh (every 10 minutes)
                    this.startTokenRefreshInterval();

                    // ✅ Record timestamp of successful login for visibility change tracking
                    try {
                        localStorage.setItem('auth_token_last_refresh', Date.now().toString());
                    } catch (e) {
                        console.error('Failed to save login timestamp:', e);
                    }

                    return true; // Login successful with JWT permissions

                } else {
                    console.warn('⚠ Login: Keine Benutzerdaten in Antwort trotz Erfolgsstatus.');
                    this._setUser(null); // Setze User explizit auf null bei Misserfolg
                    return false; // Login fehlgeschlagen
                }
            } catch (error: any) {
                console.error('❌ Login: Fehlgeschlagen.', error);
                this._setUser(null); // Setze User explizit auf null bei Fehler

                // Extract error message from backend response
                const errorMessage = error.response?.data?.error || error.message || 'Login fehlgeschlagen';

                // Throw error with message so it can be caught in LoginView
                throw new Error(errorMessage);
            } finally {
                this.isLoading = false;
            }
        },

        async logout() {
             // logout wird aufgerufen, wenn der User sich ausloggt
            console.log('🚀 Logout: Versuche Logout...');

            // Stop token refresh interval FIRST (prevent refresh during logout)
            this.stopTokenRefreshInterval();

            try {
                // ✅ CRITICAL: Call backend logout FIRST (while cookies still exist!)
                // Backend needs the auth_token cookie to identify which session to deactivate
                await apiClientAuth.post('/login/?action=logout');
                console.log('✅ Logout: Backend Logout-Aufruf erfolgreich (Session deaktiviert).');
            } catch (error) {
                console.error('❌ Logout: Backend Logout-Aufruf fehlgeschlagen.', error);
                // Continue with cleanup even if backend logout fails
            }

            // NOW clear frontend state and cookies (after backend logout)
            // Setze alle User-relevanten Zustände auf null/false
            this._setUser(null);
            this.token = null; // Clear token from store
            this.isLoading = false;

            // Clear any tokens from localStorage AND sessionStorage
            try {
                // Clear localStorage
                localStorage.removeItem('authToken');
                localStorage.removeItem('token');
                localStorage.removeItem('jwt');
                localStorage.removeItem('auth_token');

                // Clear sessionStorage (important for FiveM compatibility)
                sessionStorage.removeItem('authToken');
                sessionStorage.removeItem('token');
                sessionStorage.removeItem('jwt');
                sessionStorage.removeItem('auth_token');

                console.log('✅ Logout: Cleared all tokens from localStorage and sessionStorage');
            } catch (e) {
                console.error('❌ Logout: Error clearing storage tokens:', e);
            }

            // ✅ NEU: localStorage für Mail-Config & Branding aufräumen
            try {
                localStorage.removeItem('user_mail_config');
                localStorage.removeItem('authority_branding');
                localStorage.removeItem('auth_token_last_refresh'); // Remove refresh timestamp
                console.log('✅ Logout: Cleared mail config, branding, and refresh timestamp from localStorage');
            } catch (e) {
                console.warn('⚠️ Logout: localStorage nicht verfügbar beim Cleanup');
            }

            // ✅ NEU: Memory-Fallbacks löschen
            this.mailConfigMemory = null;
            this.brandingMemory = null;

            // Clear auth cookies on all possible paths and domains
            try {
                // Remove auth_token cookie
                document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + window.location.hostname + ';';
                document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + window.location.hostname + ';';

                // Remove jwt cookie if exists
                document.cookie = 'jwt=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                document.cookie = 'jwt=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + window.location.hostname + ';';
                document.cookie = 'jwt=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + window.location.hostname + ';';

                // Remove refresh_token cookie (HttpOnly, but try anyway for safety)
                document.cookie = 'refresh_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                document.cookie = 'refresh_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + window.location.hostname + ';';
                document.cookie = 'refresh_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + window.location.hostname + ';';

                // Remove current_authority cookie
                document.cookie = 'current_authority=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                document.cookie = 'current_authority=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + window.location.hostname + ';';
                document.cookie = 'current_authority=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + window.location.hostname + ';';

                console.log('✅ Logout: Cleared all auth cookies (auth_token, jwt, refresh_token, current_authority)');
            } catch (e) {
                console.error('❌ Logout: Error clearing cookies:', e);
            }

            console.log('🚀 Logout: Abgeschlossen.');
            // Zur Login-Seite navigieren
            router.push('/');
        },

        /**
         * Refresh JWT Token
         * Fetches a new token with fresh permissions from the database
         * Token expiration is 15 minutes, refresh runs every 10 minutes
         */
        async refreshToken() {
            console.log('🔄 RefreshToken: Refreshing JWT token...');

            // ✅ Prevent concurrent refresh attempts
            if (this.isRefreshing) {
                console.log('⏸️ RefreshToken: Already refreshing, skipping...');
                return;
            }

            this.isRefreshing = true;

            // ✅ IMPORTANT: refresh_token is an HttpOnly cookie, so we can't check for it via JavaScript
            // We just attempt the refresh - if the cookie doesn't exist, backend will return 401
            // The cookie is sent automatically with the request via withCredentials: true

            try {
                const response = await apiClientAuth.post<{
                    success: boolean;
                    message: string;
                    token: string;
                    expires_at: string;
                    user: User;
                }>('/login/?action=refresh');

                if (response.data && response.data.success && response.data.user) {
                    console.log('✅ RefreshToken: Token refreshed successfully');

                    // Update user state with fresh permissions
                    this._setUser(response.data.user);

                    // IMPORTANT: Store new token in localStorage AND sessionStorage
                    // This is critical for embedded browsers (FiveM) and F5 reloads
                    if (response.data.token) {
                        try {
                            localStorage.setItem('auth_token', response.data.token);
                            sessionStorage.setItem('auth_token', response.data.token);
                            this.token = response.data.token; // Also update store
                            console.log('✅ RefreshToken: New token stored in localStorage + sessionStorage');
                        } catch (e) {
                            console.error('❌ RefreshToken: Failed to store token in storage:', e);
                        }
                    }

                    console.log('✅ RefreshToken: User permissions updated');

                    // ✅ Record timestamp of successful refresh for visibility change tracking
                    try {
                        localStorage.setItem('auth_token_last_refresh', Date.now().toString());
                    } catch (e) {
                        console.error('Failed to save refresh timestamp:', e);
                    }
                } else {
                    console.warn('⚠️ RefreshToken: Invalid response from server');
                }
            } catch (error: any) {
                console.error('❌ RefreshToken: Failed to refresh token:', error);

                // If refresh fails with 401, refresh token is expired/invalid
                // Clear everything and redirect to login (avoid infinite loop by checking if already at login)
                if (error.response?.status === 401) {
                    console.log('🚨 RefreshToken: Refresh token expired/invalid');
                    // Only logout if we're actually logged in (avoid infinite loop)
                    if (this.isLoggedIn) {
                        await this.logout();
                    } else {
                        // Just clear state and navigate to login
                        this._setUser(null);
                        router.push('/');
                    }
                }

                // CRITICAL: Re-throw error so interceptor knows refresh failed
                throw error;
            } finally {
                this.isRefreshing = false;
            }
        },

        /**
         * Start automatic token refresh
         * Checks and refreshes token every 10 minutes
         * Token lifetime is 15 minutes, so this keeps permissions fresh
         * Also uses Page Visibility API to refresh when tab becomes active
         */
        startTokenRefreshInterval() {
            // Stop any existing interval
            this.stopTokenRefreshInterval();

            console.log('⏱️ StartTokenRefresh: Starting token refresh interval (every 10 minutes)');

            // Refresh token every 10 minutes (600000ms)
            this.tokenRefreshInterval = window.setInterval(() => {
                console.log('⏱️ Token Refresh Interval: Triggering refresh...');
                this.refreshToken().catch(err => {
                    console.error('⏱️ Token Refresh Interval: Failed:', err);
                });
            }, 10 * 60 * 1000); // 10 minutes

            // ✅ CRITICAL FIX: Add Page Visibility API listener
            // This refreshes the token when the user returns to an inactive tab
            // Prevents issue where setInterval is throttled in background tabs
            this.setupVisibilityChangeListener();
        },

        /**
         * Stop automatic token refresh
         * Called during logout or when user state becomes invalid
         */
        stopTokenRefreshInterval() {
            if (this.tokenRefreshInterval !== null) {
                console.log('⏹️ StopTokenRefresh: Stopping token refresh interval');
                clearInterval(this.tokenRefreshInterval);
                this.tokenRefreshInterval = null;
            }

            // Remove visibility change listener
            this.removeVisibilityChangeListener();
        },

        /**
         * Setup Page Visibility API listener
         * Refreshes token when tab becomes visible after being hidden
         * This prevents token expiration when browser throttles setInterval
         */
        setupVisibilityChangeListener() {
            // Remove any existing listener first
            this.removeVisibilityChangeListener();

            // Create the visibility change handler
            const visibilityChangeHandler = () => {
                if (document.visibilityState === 'visible') {
                    console.log('👁️ Tab became visible - checking token validity...');

                    // Check if we should refresh the token
                    // We'll refresh if the last refresh was more than 5 minutes ago
                    const shouldRefresh = this.shouldRefreshOnVisibilityChange();

                    if (shouldRefresh) {
                        console.log('👁️ Token might be stale, refreshing...');
                        this.refreshToken().catch(err => {
                            console.error('👁️ Failed to refresh token on visibility change:', err);
                        });
                    } else {
                        console.log('👁️ Token is still fresh, no refresh needed');
                    }
                }
            };

            // Store the handler so we can remove it later
            (this as any)._visibilityChangeHandler = visibilityChangeHandler;

            // Add the event listener
            document.addEventListener('visibilitychange', visibilityChangeHandler);
            console.log('👁️ Page Visibility listener added');
        },

        /**
         * Remove Page Visibility API listener
         */
        removeVisibilityChangeListener() {
            if ((this as any)._visibilityChangeHandler) {
                document.removeEventListener('visibilitychange', (this as any)._visibilityChangeHandler);
                (this as any)._visibilityChangeHandler = null;
                console.log('👁️ Page Visibility listener removed');
            }
        },

        /**
         * Check if token should be refreshed when tab becomes visible
         * Returns true if last refresh was more than 5 minutes ago
         */
        shouldRefreshOnVisibilityChange(): boolean {
            // Check if we have a token stored with timestamp
            const lastRefreshKey = 'auth_token_last_refresh';
            const lastRefreshStr = localStorage.getItem(lastRefreshKey);

            if (!lastRefreshStr) {
                // No last refresh recorded, should refresh
                return true;
            }

            try {
                const lastRefresh = parseInt(lastRefreshStr, 10);
                const now = Date.now();
                const fiveMinutes = 5 * 60 * 1000; // 5 minutes in milliseconds

                // Refresh if more than 5 minutes since last refresh
                return (now - lastRefresh) > fiveMinutes;
            } catch (e) {
                console.error('Error parsing last refresh timestamp:', e);
                return true; // Refresh on error to be safe
            }
        },

    }, // Ende Actions
}); // Ende defineStore