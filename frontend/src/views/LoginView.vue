<template>
    <v-container fluid class="fill-height login-container pa-0">
        <v-row align="center" justify="center" no-gutters>
            <v-col cols="12" sm="10" md="9" lg="8" xl="7">

                <!-- Authority Info Card (for URL-based selection) -->
                <v-card
                    v-if="urlAuthorityMode && urlAuthority"
                    class="authority-info-card rounded-xl mb-4"
                    :style="authorityCardStyle"
                >
                    <v-card-text class="d-flex align-center pa-4">
                        <v-avatar v-if="urlAuthority.logo_url" size="48" class="mr-4">
                            <img :src="getFullImageUrl(urlAuthority.logo_url)" :alt="urlAuthority.display_name">
                        </v-avatar>
                        <v-icon v-else size="48" class="mr-4" color="primary">mdi-shield-account</v-icon>
                        <div class="flex-grow-1">
                            <h3 class="text-h6 mb-1">{{ urlAuthority.app_title || urlAuthority.display_name }}</h3>
                            <p class="text-caption mb-0 text-medium-emphasis">
                                {{ $t('login.autoSelectedForSite', { site: urlAuthority.name }) }}
                            </p>
                        </div>
                        <v-btn
                            variant="text"
                            icon="mdi-close"
                            size="small"
                            @click="clearUrlMode"
                            class="ml-2"
                        ></v-btn>
                    </v-card-text>
                </v-card>

                <v-card class="login-card rounded-xl overflow-hidden" :style="dynamicCardStyle">
                    <!--
                        Linke Spalte: wer hier anmeldet, bei welcher Behoerde.
                        Der Betriebszustand aus dem Entwurf steht bewusst nicht
                        hier - diese Zahlen waeren vor der Anmeldung sichtbar
                        und gehen Unbefugte nichts an.
                    -->
                    <aside class="login-aside">
                        <div class="login-aside__brand">
                            <img
                                v-if="selectedAuthorityBranding?.logo_url"
                                :src="getFullImageUrl(selectedAuthorityBranding.logo_url)"
                                :alt="selectedAuthorityBranding.app_title + ' Logo'"
                                class="login-aside__logo"
                            />
                            <img
                                v-else
                                :src="getDefaultLogo()"
                                :alt="getDefaultLogoAlt()"
                                class="login-aside__logo"
                            />
                            <div class="login-aside__title">
                                {{ selectedAuthorityBranding?.app_title || 'Command &amp; Control' }}
                            </div>
                        </div>

                        <div class="login-aside__foot">
                            <div class="login-aside__authority">
                                {{ selectedAuthorityBranding?.display_name || urlAuthority?.display_name || '' }}
                            </div>
                            <p class="login-aside__note">{{ $t('login.accessNote') }}</p>
                        </div>
                    </aside>

                    <v-card-text class="login-form px-8 py-8 position-relative">
                        <h2 class="text-h5 font-weight-medium mb-8 text-center">
                            {{ $t('login.welcomeBack') }}
                        </h2>

                        <v-form ref="formRef" v-model="isFormValid" @submit.prevent="submitLogin">
                            <v-text-field
                                :label="$t('login.username')"
                                v-model="username"
                                :rules="[requiredRule]"
                                required
                                prepend-inner-icon="mdi-account"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="surface"
                                class="mb-6 login-field"
                                hide-details="auto"
                            ></v-text-field>

                            <v-text-field
                                :label="$t('login.password')"
                                v-model="password"
                                type="password"
                                :rules="[requiredRule]"
                                required
                                prepend-inner-icon="mdi-lock"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="surface"
                                class="mb-6 login-field"
                                hide-details="auto"
                            ></v-text-field>

                            <!-- Authority Selector (hidden in URL mode) -->
                            <v-autocomplete
                                v-if="!urlAuthorityMode"
                                :label="$t('login.system')"
                                v-model="authority"
                                :items="authorityOptions"
                                item-title="text"
                                item-value="value"
                                prepend-inner-icon="mdi-shield-account"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="surface"
                                class="mb-6 login-field"
                                :loading="isLoadingAuthorities"
                                hide-details="auto"
                                return-object
                                clearable
                                @update:model-value="onAuthorityChange"
                            >
                                <template v-slot:prepend-item>
                                    <v-list-subheader class="text-caption text-medium-emphasis">
                                        {{
                                            $t('login.availableSystems', {
                                                count: authorityOptions.length,
                                            })
                                        }}
                                    </v-list-subheader>
                                    <v-divider class="mt-2"></v-divider>
                                </template>

                                <template v-slot:item="{ props, item }">
                                    <v-list-item
                                        v-bind="props"
                                        :title="item.raw.text"
                                        class="authority-item"
                                    >
                                        <template v-slot:prepend>
                                            <v-avatar v-if="item.raw.logo_url" size="32" class="mr-3">
                                                <img :src="getFullImageUrl(item.raw.logo_url)" :alt="item.raw.text">
                                            </v-avatar>
                                            <v-icon v-else :icon="getSystemIcon(item.raw.category)" class="mr-3"></v-icon>
                                        </template>
                                        <template v-slot:subtitle v-if="item.raw.category">
                                            <span class="text-caption">{{
                                                item.raw.category
                                            }}</span>
                                        </template>
                                    </v-list-item>
                                </template>

                                <template v-slot:selection="{ item }">
                                    <div class="d-flex align-center">
                                        <v-avatar v-if="item.raw.logo_url" size="24" class="mr-2">
                                            <img :src="getFullImageUrl(item.raw.logo_url)" :alt="item.raw.text">
                                        </v-avatar>
                                        <v-icon v-else :icon="getSystemIcon(item.raw.category)" size="24" class="mr-2"></v-icon>
                                        <span>{{ item.raw.text }}</span>
                                    </div>
                                </template>

                                <template v-if="recentSystems.length > 0" v-slot:append-item>
                                    <v-divider class="mb-2"></v-divider>
                                    <v-list-subheader class="text-caption text-medium-emphasis">
                                        {{ $t('login.recent') }}
                                    </v-list-subheader>
                                    <v-list-item
                                        v-for="(item, i) in recentSystems"
                                        :key="i"
                                        :title="item.text"
                                        :prepend-icon="'mdi-clock-outline'"
                                        density="compact"
                                        class="recent-item"
                                        @click="selectRecentSystem(item)"
                                    ></v-list-item>
                                </template>
                            </v-autocomplete>

                            <!-- Language Selector Dropdown -->
                            <v-select
                                :label="$t('login.language')"
                                v-model="selectedLanguage"
                                :items="availableLanguages"
                                item-title="name"
                                item-value="code"
                                prepend-inner-icon="mdi-translate"
                                variant="outlined"
                                density="comfortable"
                                color="primary"
                                bg-color="surface"
                                class="mb-6 login-field"
                                hide-details="auto"
                                @update:model-value="changeLanguage"
                            >
                                <template v-slot:item="{ props, item }">
                                    <v-list-item v-bind="props">
                                        <template v-slot:prepend>
                                            <span class="mr-2">{{ item.raw.flag }}</span>
                                        </template>
                                        <template v-slot:title>
                                            {{ item.raw.name }}
                                        </template>
                                    </v-list-item>
                                </template>
                                <template v-slot:selection="{ item }">
                                    <span class="mr-2">{{ item.raw.flag }}</span>
                                    <span>{{ item.raw.name }}</span>
                                </template>
                            </v-select>

                            <!-- Layout Mode Selection -->
                            <div class="mb-6">
                                <label class="text-caption text-medium-emphasis mb-2 d-block">
                                    {{ $t('login.selectLayout', 'Wähle dein Layout') }}
                                </label>
                                <v-btn-toggle
                                    v-model="selectedLayoutPreference"
                                    color="primary"
                                    variant="outlined"
                                    divided
                                    mandatory
                                    class="w-100"
                                >
                                    <v-btn value="sidebar" class="flex-grow-1" size="large">
                                        <v-icon start>mdi-view-sidebar</v-icon>
                                        {{ $t('login.sidebarMode', 'Sidebar') }}
                                    </v-btn>
                                    <v-btn value="desktop" class="flex-grow-1" size="large">
                                        <v-icon start>mdi-desktop-mac</v-icon>
                                        {{ $t('login.desktopMode', 'Desktop') }}
                                    </v-btn>
                                </v-btn-toggle>
                                <p class="text-caption text-medium-emphasis mt-2 text-center">
                                    {{ selectedLayoutPreference === 'sidebar'
                                        ? $t('login.sidebarDesc', 'Modernes Menü mit Sidebar')
                                        : $t('login.desktopDesc', 'Windows-ähnliche Desktop-Oberfläche') }}
                                </p>
                            </div>

                            <div class="d-flex align-center justify-space-between mb-5">
                                <v-checkbox
                                    :label="$t('login.stayLoggedIn')"
                                    v-model="autoLogin"
                                    density="compact"
                                    color="primary"
                                    hide-details
                                ></v-checkbox>

                                <v-btn
                                    variant="text"
                                    density="comfortable"
                                    class="text-caption px-2 forgot-link"
                                    @click="forgotPassword"
                                >
                                    {{ $t('login.forgotPassword') }}
                                </v-btn>
                            </div>

                            <v-btn
                                :loading="isLoggingIn"
                                :disabled="!isFormValid || isLoggingIn || (!authority && !urlAuthorityMode)"
                                type="submit"
                                size="large"
                                color="primary"
                                class="mt-2 py-6 font-weight-medium login-button"
                                :style="dynamicButtonStyle"
                                block
                                rounded
                                elevation="1"
                            >
                                {{ $t('login.loginButton') }}
                            </v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>

                <div class="mt-8 text-center">
                    <p class="text-caption text-medium-emphasis">
                        {{ $t('login.rights', { year: new Date().getFullYear() }) }}
                    </p>
                </div>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useUIStore } from '@/stores/ui';
import { useToast } from 'vue-toastification';
import { apiClientAuth } from '@/api'; // Import API client for direct access
import axios from 'axios';
import type { LayoutPreference } from '@/types/Menu';

// Store & Router
const authStore = useAuthStore();
const uiStore = useUIStore();
const router = useRouter();
const { t, locale } = useI18n();

// Form State
const formRef = ref<any>(null);
const isFormValid = ref(false);
const isLoggingIn = ref(false);
const isLoadingAuthorities = ref(false);
const username = ref('');
const password = ref('');
const authority = ref<any>(null);
const autoLogin = ref(false);

// Layout Selection State - Default to desktop
const selectedLayoutPreference = ref<LayoutPreference>('desktop');

// Authority Branding State
const urlAuthorityMode = ref(false);
const urlAuthority = ref<any>(null);
const selectedAuthorityBranding = ref<any>(null);

// Recent Systems
const recentSystems = ref<{ text: string; value: number; category?: string }[]>([]);
const authorityOptions = ref<{ text: string; value: number; category?: string }[]>([]);

// Toast
const toast = useToast();

// Language selector
const availableLanguages = ref([
    { code: 'de', name: 'Deutsch', flag: '🇩🇪' },
    { code: 'en', name: 'English', flag: '🇬🇧' }
]);

const currentLocale = computed(() => locale.value);
const selectedLanguage = ref(locale.value);

// Change language function
const changeLanguage = (langCode: string) => {
    locale.value = langCode;
    localStorage.setItem('userLanguage', langCode);
};

// Fetch Authorities
const fetchAuthorities = async () => {
    isLoadingAuthorities.value = true;
    try {
        const response = await apiClientAuth.post('/login/?action=getAuthorities');
        if (response.data && Array.isArray(response.data.authorities)) {
            // Process authorities and add categories if not present
            authorityOptions.value = response.data.authorities.map((auth: any) => ({
                text: auth.text || auth.display_name || auth.name,
                value: auth.id,
                category: auth.category || t('login.otherSystem'),
                logo_url: auth.logo_url,
                primary_color: auth.primary_color,
                secondary_color: auth.secondary_color,
                app_title: auth.app_title,
                default_background: auth.default_background,
                display_name: auth.display_name,
                name: auth.name
            }));

            // Sort by category and name
            authorityOptions.value.sort((a, b) => {
                const categoryA = a.category || t('login.otherSystem');
                const categoryB = b.category || t('login.otherSystem');
                if (categoryA !== categoryB) return categoryA.localeCompare(categoryB);
                return a.text.localeCompare(b.text);
            });

            // Load recent systems
            loadRecentSystems();

            // Set default selection if available
            if (authorityOptions.value.length > 0) {
                // Try to set the last used system if available
                const lastUsed = localStorage.getItem('last_used_authority');
                if (lastUsed) {
                    const lastUsedId = parseInt(lastUsed);
                    const exists = authorityOptions.value.find(auth => auth.value === lastUsedId);
                    if (exists) {
                        authority.value = exists;
                    }
                }
            }
        } else {
            showSnackbar(t('login.errorAuthorities'), 'error');
            // Fallback to some default options
            authorityOptions.value = [
                { text: 'FireGuard Solutions', value: 1, category: 'Standard' },
            ];
            authority.value = authorityOptions.value[0];
        }
    } catch (error: any) {
        console.error('Error fetching authorities:', error);
        showSnackbar(
            t('login.errorAuthorities') + ': ' + (error.message || t('login.unknownError')),
            'error'
        );
        // Fallback to some default options
        authorityOptions.value = [{ text: 'FireGuard Solutions', value: 1, category: 'Standard' }];
        authority.value = authorityOptions.value[0];
    } finally {
        isLoadingAuthorities.value = false;
    }
};

// Load recent systems from localStorage
const loadRecentSystems = () => {
    try {
        const storedRecent = localStorage.getItem('recent_systems');
        if (storedRecent) {
            const recentIds = JSON.parse(storedRecent);
            recentSystems.value = recentIds
                .map((id: number) => authorityOptions.value.find(opt => opt.value === id))
                .filter(Boolean)
                .slice(0, 3); // Limit to 3 recent systems
        }
    } catch (error) {
        console.error('Error loading recent systems:', error);
        recentSystems.value = [];
    }
};

// Save recent system to localStorage
const saveRecentSystem = (system: any) => {
    if (!system || !system.value) return;

    try {
        const id = system.value;

        // Save as last used authority
        localStorage.setItem('last_used_authority', id.toString());

        // Update recent systems
        const storedRecent = localStorage.getItem('recent_systems');
        let recentIds: number[] = storedRecent ? JSON.parse(storedRecent) : [];

        // Remove if already exists and add to front
        recentIds = recentIds.filter(existingId => existingId !== id);
        recentIds.unshift(id);

        // Limit to 5 recent systems
        recentIds = recentIds.slice(0, 5);

        localStorage.setItem('recent_systems', JSON.stringify(recentIds));

        // Update the recent systems list
        loadRecentSystems();
    } catch (error) {
        console.error('Error saving recent system:', error);
    }
};

// Select a recent system
const selectRecentSystem = (system: any) => {
    authority.value = system;
};

// Get appropriate icon for system category
const getSystemIcon = (category?: string) => {
    switch (category) {
        case 'Standard':
            return 'mdi-shield-check';
        case 'Polizei':
            return 'mdi-police-badge';
        case 'Feuerwehr':
            return 'mdi-fire-truck';
        case 'Rettungsdienst':
            return 'mdi-ambulance';
        case 'Verwaltung':
            return 'mdi-office-building';
        case 'GTA RP Server':
            return 'mdi-gamepad-variant';
        default:
            return 'mdi-shield-account';
    }
};

// Get full image URL for authority logos
const getFullImageUrl = (path: string) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    return `${import.meta.env.VITE_API_URL}${path}`;
};

// Get default logo based on authority category/name
const getDefaultLogo = () => {
    const selectedAuth = getSelectedAuthority();
    const authorityName = selectedAuth?.name?.toLowerCase() || '';
    const category = selectedAuth?.category || '';
    
    // Authority-specific logos
    if (authorityName.includes('lsfd') || authorityName.includes('fire')) {
        return new URL('@/assets/lsfdschriftzug.png', import.meta.url).href;
    }
    
    // Category-specific defaults could be added here
    // if (category === 'GTA RP Server') {
    //     return new URL('@/assets/gta-rp-default.png', import.meta.url).href;
    // }
    
    // Default fallback
    return new URL('@/assets/logo.png', import.meta.url).href;
};

// Get default logo alt text
const getDefaultLogoAlt = () => {
    const selectedAuth = getSelectedAuthority();
    const authorityName = selectedAuth?.name?.toLowerCase() || '';
    
    if (authorityName.includes('lsfd') || authorityName.includes('fire')) {
        return 'LSFD Logo';
    }
    
    return 'CAD Command & Control Logo';
};

// Validate URL site parameter
const validateUrlSite = async (siteName: string) => {
    try {
        const response = await apiClientAuth.post('/login/?action=validateSite', {
            site: siteName
        });
        
        if (response.data?.valid && response.data?.authority) {
            const authorityData = response.data.authority;
            urlAuthority.value = authorityData;
            urlAuthorityMode.value = true;
            
            // Set authority branding
            selectedAuthorityBranding.value = {
                logo_url: authorityData.logo_url,
                primary_color: authorityData.primary_color || 'var(--k-accent)',
                secondary_color: authorityData.secondary_color || '#6B7280',
                app_title: authorityData.app_title || authorityData.display_name,
                default_background: authorityData.default_background
            };
            
            // Apply authority branding styles
            applyAuthorityBranding();
            
            return true;
        }
        return false;
    } catch (error) {
        console.error('Error validating site:', error);
        return false;
    }
};

// Check URL parameters on mount
const checkUrlParameters = async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const siteParam = urlParams.get('site');
    
    if (siteParam) {
        const isValid = await validateUrlSite(siteParam);
        if (!isValid) {
            showSnackbar(t('login.invalidSite', { site: siteParam }), 'error');
            // Remove invalid site parameter from URL
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
        }
    }
};

// Clear URL mode
const clearUrlMode = () => {
    urlAuthorityMode.value = false;
    urlAuthority.value = null;
    selectedAuthorityBranding.value = null;
    
    // Remove site parameter from URL
    const newUrl = window.location.pathname;
    window.history.replaceState({}, document.title, newUrl);
    
    // Reset branding styles
    resetBrandingStyles();
};

// Get selected authority (either from URL or dropdown)
const getSelectedAuthority = () => {
    return urlAuthorityMode.value ? urlAuthority.value : authority.value;
};

// Handle authority change in dropdown
const onAuthorityChange = (selectedAuth: any) => {
    if (selectedAuth) {
        selectedAuthorityBranding.value = {
            logo_url: selectedAuth.logo_url,
            primary_color: selectedAuth.primary_color || 'var(--k-accent)',
            secondary_color: selectedAuth.secondary_color || '#6B7280',
            app_title: selectedAuth.app_title || selectedAuth.display_name,
            default_background: selectedAuth.default_background
        };
        applyAuthorityBranding();
    } else {
        selectedAuthorityBranding.value = null;
        resetBrandingStyles();
    }
};

// Apply authority branding styles
const applyAuthorityBranding = () => {
    if (!selectedAuthorityBranding.value) return;
    
    const root = document.documentElement;
    root.style.setProperty('--authority-primary', selectedAuthorityBranding.value.primary_color);
    root.style.setProperty('--authority-secondary', selectedAuthorityBranding.value.secondary_color);
};

// Reset branding styles
const resetBrandingStyles = () => {
    const root = document.documentElement;
    root.style.removeProperty('--authority-primary');
    root.style.removeProperty('--authority-secondary');
};

// Dynamic styles for authority branding
const authorityCardStyle = computed(() => {
    if (!urlAuthority.value) return {};
    return {
        borderColor: urlAuthority.value.primary_color || 'var(--k-accent)',
        background: `linear-gradient(135deg, ${urlAuthority.value.primary_color || 'var(--k-accent)'}15, ${urlAuthority.value.secondary_color || '#6B7280'}10)`
    };
});

const dynamicCardStyle = computed(() => {
    if (!selectedAuthorityBranding.value) return {};
    return {
        border: `1px solid ${selectedAuthorityBranding.value.primary_color}40`
    };
});

const dynamicButtonStyle = computed(() => {
    if (!selectedAuthorityBranding.value) return {};
    return {
        backgroundColor: selectedAuthorityBranding.value.primary_color,
        color: '#FFFFFF'
    };
});

// Load authorities when component mounts
onMounted(async () => {
    // NOTE: Don't clear tokens on mount - they might still be valid
    // Tokens are only cleared right before new login attempt
    // This prevents losing valid tokens during navigation

    // Check URL parameters first
    await checkUrlParameters();
    
    // Then fetch authorities
    fetchAuthorities();
    
    // Load saved language preference
    const savedLanguage = localStorage.getItem('userLanguage');
    if (savedLanguage && ['de', 'en'].includes(savedLanguage)) {
        locale.value = savedLanguage;
        selectedLanguage.value = savedLanguage;
    }
});

// Validation Rules
const requiredRule = (value: string) => !!value || t('login.requiredField');

// Helper function to clear all stored tokens
const clearAllTokens = () => {
    try {
        // Clear all possible token storage locations
        localStorage.removeItem('authToken');
        localStorage.removeItem('token');
        localStorage.removeItem('jwt');
        localStorage.removeItem('auth_token');
        console.log('Cleared all tokens from localStorage');
    } catch (e) {
        console.error('Error clearing localStorage tokens:', e);
    }
};

/**
 * Handle post-login navigation based on selected layout preference
 */
async function handlePostLoginNavigation() {
    // Save the selected layout preference to backend
    await uiStore.saveLayoutPreference(selectedLayoutPreference.value);

    // Navigate to selected layout
    navigateToLayout(selectedLayoutPreference.value);
}

/**
 * Navigate to the selected layout
 */
function navigateToLayout(layout: LayoutPreference) {
    if (layout === 'desktop') {
        uiStore.setDesktopMode(true);
        router.push({ name: 'desktop' });
    } else {
        // Default to sidebar (includes 'sidebar' and null values)
        uiStore.setDesktopMode(false);
        router.push({ name: 'dashboard' });
    }
}

// Login Submit Handler
const submitLogin = async () => {
    // In URL mode, we don't need authority.value to be set since we use urlAuthority
    // In dropdown mode, we need authority.value to be set
    const hasValidAuthority = urlAuthorityMode.value ? !!urlAuthority.value : !!authority.value;
    
    if (!isFormValid.value || !hasValidAuthority) return;

    isLoggingIn.value = true;
    
    // Clear any existing tokens before attempting login
    clearAllTokens();
    
    try {
        const selectedAuth = getSelectedAuthority();
        // Determine the authority_id based on whether we're in URL mode or dropdown mode
        const authorityId = urlAuthorityMode.value 
            ? selectedAuth.id  // URL mode: urlAuthority has 'id' property
            : selectedAuth.value;  // Dropdown mode: authority object has 'value' property
            
        await authStore.login({
            username: username.value,
            password: password.value,
            rememberMe: autoLogin.value,
            authority_id: authorityId,
        });

        // Save as recent system upon successful login (only if not in URL mode)
        if (!urlAuthorityMode.value) {
            saveRecentSystem(authority.value);
        }

        if (authStore.isLoggedIn) {
            // Direct approach to load theme settings immediately after login
            try {
                console.log('LoginView: Directly loading theme settings from API after login...');

                // Directly call the settings API endpoint
                const response = await apiClientAuth.get(
                    '/admin/settings/index.php?action=getGlobalSettings'
                );

                if (response.data && response.data.settings) {
                    console.log(
                        'LoginView: Theme settings successfully loaded from API',
                        response.data.settings
                    );

                    // Dynamically import themeLoader
                    const { applyThemeToDOM } = await import('@/utils/themeLoader');

                    // Process the theme settings directly
                    const themeSettings = response.data.settings;
                    interface ProcessedSettings {
                        primaryColor: string;
                        secondaryColor: string;
                        accentColor: string;
                        backgroundColor: string;
                        surfaceColor: string;
                        tertiaryColor: string;
                        infoColor: string;
                        successColor: string;
                        warningColor: string;
                        errorColor: string;
                        onPrimaryColor: string;
                        onSecondaryColor: string;
                        onAccentColor: string;
                        onBackgroundColor: string;
                        onSurfaceColor: string;
                        onSuccessColor: string;
                        onInfoColor: string;
                        onWarningColor: string;
                        onErrorColor: string;
                        borderRadius: number;
                        darkMode: boolean;
                        enableGradients: boolean;
                        desktopBackgroundImage?: string;
                        lastUpdated: number;
                    }

                    const processedSettings: ProcessedSettings = {
                        primaryColor: themeSettings.primaryColor || 'var(--k-accent)',
                        secondaryColor: themeSettings.secondaryColor || '#343541',
                        accentColor: themeSettings.accentColor || '#10B981',
                        backgroundColor: themeSettings.backgroundColor || '#111723',
                        surfaceColor: themeSettings.surfaceColor || '#111827',
                        tertiaryColor: themeSettings.tertiaryColor || '#444654',
                        infoColor: themeSettings.infoColor || '#007bff',
                        successColor: themeSettings.successColor || '#138D75',
                        warningColor: themeSettings.warningColor || '#FFC107',
                        errorColor: themeSettings.errorColor || '#dc3545',
                        onPrimaryColor: themeSettings.onPrimaryColor || '#FFFFFF',
                        onSecondaryColor: themeSettings.onSecondaryColor || '#FFFFFF',
                        onAccentColor: themeSettings.onAccentColor || '#121212',
                        onBackgroundColor: themeSettings.onBackgroundColor || '#FFFFFF',
                        onSurfaceColor: themeSettings.onSurfaceColor || '#FFFFFF',
                        onSuccessColor: themeSettings.onSuccessColor || '#FFFFFF',
                        onInfoColor: themeSettings.onInfoColor || '#FFFFFF',
                        onWarningColor: themeSettings.onWarningColor || '#121212',
                        onErrorColor: themeSettings.onErrorColor || '#FFFFFF',
                        borderRadius: parseInt(themeSettings.borderRadius || '8'),
                        darkMode:
                            themeSettings.darkMode === true || themeSettings.darkMode === 'true',
                        enableGradients:
                            themeSettings.enableGradients === true ||
                            themeSettings.enableGradients === 'true',
                        lastUpdated: new Date().getTime(),
                    };

                    // Check for desktop background image
                    if (themeSettings.desktopBackgroundImage) {
                        processedSettings.desktopBackgroundImage =
                            themeSettings.desktopBackgroundImage;
                    }

                    // Force clear any existing theme data in localStorage
                    localStorage.removeItem('theme-settings');

                    // Apply theme directly and forcefully
                    applyThemeToDOM(processedSettings);

                    // Save processed settings to localStorage
                    localStorage.setItem('theme-settings', JSON.stringify(processedSettings));

                    console.log('LoginView: Theme directly applied and saved to localStorage');

                    // Check for saved layout preference
                    await handlePostLoginNavigation();
                    return;
                } else {
                    console.warn('LoginView: Theme API call returned no settings data');
                }
            } catch (themeError) {
                console.error('LoginView: Error directly loading theme:', themeError);
            }

            // Fallback navigation if theme loading fails
            await handlePostLoginNavigation();
        } else {
            showSnackbar('Login fehlgeschlagen. Überprüfen Sie Ihre Zugangsdaten.', 'error');
        }
    } catch (error: any) {
        console.error('Login error:', error);

        // Translate specific error messages
        let errorMessage = error.message || 'Login fehlgeschlagen. Überprüfen Sie Ihre Zugangsdaten.';

        // Check for specific error messages and translate them
        if (errorMessage.includes('suspended')) {
            errorMessage = t('login.accountSuspended');
        } else if (errorMessage.includes('banned')) {
            errorMessage = t('login.accountBanned');
        }

        showSnackbar(errorMessage, 'error');
    } finally {
        isLoggingIn.value = false;
    }
};

// Forgot Password Handler
const forgotPassword = () => {
    showSnackbar(t('login.forgotNotImplemented'), 'info');
};

function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}
</script>

<style scoped>
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    background: var(--k-canvas, #0f1216);
}



.logo-container {
    margin-bottom: 3rem;
    animation: fadeIn 1s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-30px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.logo-image {
    max-height: 120px;
    width: auto;
    animation: fadeIn 1s cubic-bezier(0.34, 1.56, 0.64, 1);
    object-fit: contain;
    max-width: 120px;
    filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.5));
    transition: transform 0.3s ease;
}

.logo-image:hover {
    transform: scale(1.05);
}

.logo-text {
    font-size: 1.3rem;
    font-weight: 400;
    color: var(--k-ink, rgba(255, 255, 255, 0.85));
    letter-spacing: 0.25em;
    text-transform: uppercase;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    margin-top: 1rem;
}

.login-card {
    backdrop-filter: blur(30px) saturate(180%);
    -webkit-backdrop-filter: blur(30px) saturate(180%);
    background: var(--k-surface, #161a20);
    border: 1px solid var(--k-line, rgba(255, 255, 255, 0.12));
    box-shadow:
        0 20px 60px rgba(0, 0, 0, 0.5),
        0 8px 24px rgba(0, 0, 0, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    transform: translateY(0);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: cardAppear 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
    animation-delay: 0.2s;
}

@keyframes cardAppear {
    from {
        opacity: 0;
        transform: translateY(40px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.login-card:hover {
    transform: translateY(-8px);
    box-shadow:
        0 25px 70px rgba(0, 0, 0, 0.6),
        0 12px 30px rgba(0, 0, 0, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

.login-field {
    position: relative;
    z-index: 5;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 12px;
    animation: fieldAppear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
}

.login-field:nth-child(1) {
    animation-delay: 0.3s;
}
.login-field:nth-child(2) {
    animation-delay: 0.4s;
}
.login-field:nth-child(3) {
    animation-delay: 0.5s;
}
.login-field:nth-child(4) {
    animation-delay: 0.6s;
}

@keyframes fieldAppear {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.login-field:focus-within {
    transform: translateY(-3px);
    filter: brightness(1.05);
}

.login-field :deep(.v-field) {
    background: var(--k-surface, rgba(255, 255, 255, 0.05));
    border: 1px solid var(--k-line-strong, rgba(255, 255, 255, 0.1));
    transition: all 0.3s ease;
}

.login-field :deep(.v-field:hover) {
    background: var(--k-row-hover, rgba(255, 255, 255, 0.08));
    border-color: var(--k-accent-line, rgba(255, 255, 255, 0.15));
}

.login-field :deep(.v-field--focused) {
    background: var(--k-row-hover);
    border-color: var(--authority-primary, var(--k-accent));
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.login-field :deep(.v-input__prepend-inner) {
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.login-field:focus-within :deep(.v-input__prepend-inner) {
    opacity: 1;
}

.login-button {
    letter-spacing: 1.5px;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation: buttonAppear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
    animation-delay: 0.7s;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
}

@keyframes buttonAppear {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.login-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(59, 130, 246, 0.4);
}

.login-button:active {
    transform: translateY(0);
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
}

.login-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    transition: left 0.5s ease;
}

.login-button:hover::before {
    left: 100%;
}

.login-button::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.1),
        transparent,
        rgba(255, 255, 255, 0.05)
    );
    pointer-events: none;
}

.forgot-link {
    position: relative;
    color: var(--k-ink-faint, rgba(255, 255, 255, 0.65));
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.75rem;
    font-weight: 500;
}

.forgot-link:hover {
    color: var(--authority-primary, var(--k-accent));
    transform: translateX(2px);
}

.forgot-link::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: -2px;
    left: 0;
    background: linear-gradient(90deg, var(--authority-primary, var(--k-accent)), transparent);
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.forgot-link:hover::after {
    width: 100%;
}

/* Checkbox Styling */
.login-field :deep(.v-checkbox) {
    animation: fieldAppear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) backwards;
    animation-delay: 0.65s;
}

.login-field :deep(.v-checkbox .v-label) {
    color: var(--k-ink-muted, rgba(255, 255, 255, 0.75));
    font-weight: 500;
    font-size: 0.875rem;
}

.system-selector {
    background-color: rgba(26, 32, 44, 0.5);
    border: 1px solid var(--k-line);
    border-radius: 8px;
    padding: 12px;
    position: relative;
}

.system-search {
    margin-bottom: 0;
}

.system-options {
    max-height: 200px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--k-line-strong) transparent;
}

.system-options::-webkit-scrollbar {
    width: 6px;
}

.system-options::-webkit-scrollbar-track {
    background: transparent;
}

.system-options::-webkit-scrollbar-thumb {
    background-color: var(--k-row-hover);
    border-radius: 3px;
}

.system-options.is-searching {
    max-height: 150px;
}

.system-chip {
    cursor: pointer;
    transition: all 0.2s ease;
}

.system-chip:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.recent-system-chip {
    cursor: pointer;
    transition: all 0.2s ease;
    background-color: var(--k-row-hover);
}

.recent-system-chip:hover {
    transform: translateY(-2px);
    background-color: var(--k-ink);
}

.gap-2 {
    gap: 8px;
}

.selected-system {
    color: var(--k-ink-muted, rgba(255, 255, 255, 0.7));
}

.recent-item {
    cursor: pointer;
    transition: all 0.2s ease;
}

.recent-item:hover {
    background-color: var(--k-row-hover);
}

/* Authority Branding Styles */
.authority-logo {
    transition: all 0.3s ease;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.authority-info-card {
    backdrop-filter: blur(25px) saturate(180%);
    -webkit-backdrop-filter: blur(25px) saturate(180%);
    background: linear-gradient(145deg, rgba(30, 41, 59, 0.92), rgba(51, 65, 85, 0.88));
    border: 2px solid rgba(255, 255, 255, 0.15);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation: cardAppear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow:
        0 12px 40px rgba(0, 0, 0, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.authority-info-card:hover {
    transform: translateY(-4px);
    box-shadow:
        0 16px 50px rgba(0, 0, 0, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

.authority-item {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 8px;
    margin: 2px 0;
}

.authority-item:hover {
    background-color: var(--k-row-hover);
    transform: translateX(4px);
}

/* Autocomplete Dropdown Styling */
.login-field :deep(.v-autocomplete .v-menu) {
    backdrop-filter: blur(20px);
}

.login-field :deep(.v-list) {
    background: rgba(30, 41, 59, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid var(--k-line);
    border-radius: 12px;
}

.login-field :deep(.v-list-item) {
    transition: all 0.2s ease;
    border-radius: 8px;
    margin: 2px 8px;
}

.login-field :deep(.v-list-item:hover) {
    background: var(--k-row-hover);
    transform: translateX(4px);
}

.login-field :deep(.v-list-subheader) {
    color: var(--k-ink-faint);
    font-weight: 700;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Dynamic Authority Branding Variables */
:root {
    --authority-primary: var(--k-accent);
    --authority-secondary: #6B7280;
}

/* Authority-specific button styling */
.login-button[style*="backgroundColor"] {
    background: linear-gradient(135deg, var(--authority-primary), var(--authority-secondary)) !important;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.login-button[style*="backgroundColor"]:hover {
    filter: brightness(1.15);
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
}

/* Authority card border styling */
.login-card[style*="border"] {
    box-shadow:
        0 20px 60px rgba(0, 0, 0, 0.5),
        0 8px 24px rgba(0, 0, 0, 0.3),
        0 0 0 1px var(--authority-primary),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.authority-info-card[style*="borderColor"] {
    box-shadow:
        0 12px 40px rgba(0, 0, 0, 0.4),
        0 0 0 2px var(--authority-primary),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

/* Footer Text Styling */
.text-caption.text-medium-emphasis {
    color: var(--k-ink-faint);
    font-size: 0.75rem;
    font-weight: 500;
    animation: fadeIn 1s ease-out;
    animation-delay: 0.8s;
    animation-fill-mode: backwards;
}

/* Welcome Back Title */
.login-card .text-h5 {
    color: var(--k-ink, rgba(255, 255, 255, 0.95));
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Zweispaltige Anmeldung: links die Behoerde, rechts das Formular.
   Unter 760 px stapeln beide Spalten. */
.login-card {
    display: grid;
    grid-template-columns: 1fr;
}

@media (min-width: 760px) {
    .login-card {
        grid-template-columns: 0.85fr 1fr;
    }
}

.login-aside {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 24px;
    padding: 28px 30px;
    background: var(--k-sunken, #12161b);
    border-right: 1px solid var(--k-line, #262c35);
}

@media (max-width: 759px) {
    .login-aside {
        border-right: 0;
        border-bottom: 1px solid var(--k-line, #262c35);
        padding: 20px 24px;
    }
}

.login-aside__brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.login-aside__logo {
    width: 40px;
    height: 40px;
    object-fit: contain;
    flex: none;
}

.login-aside__title {
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: var(--k-ink, #e4e7ec);
}

.login-aside__authority {
    font-size: 14px;
    font-weight: 620;
    margin-bottom: 4px;
    color: var(--k-ink, #e4e7ec);
}

.login-aside__note {
    font-size: 12.5px;
    line-height: 1.45;
    margin: 0;
    max-width: 34ch;
    color: var(--k-ink-muted, #9aa4b2);
}
</style>
