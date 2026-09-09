<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import if used (assuming generic snackbar)
import ActiveSessions from '@/components/sessions/ActiveSessions.vue'; // Session management
import LoginHistory from '@/components/sessions/LoginHistory.vue'; // Login history

// --- Store & State ---
const authStore = useAuthStore();
const { t } = useI18n();

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false
});

console.log('Profile props from desktop window:', {
  canEdit: props.canEdit,
  canDelete: props.canDelete,
  'meta.canEdit': props.meta?.canEdit,
  'meta.canDelete': props.meta?.canDelete,
  allPermissions: props.allPermissions,
  isDesktopWindow: props.desktopWindow
});

// Reactive refs for form data and settings
const lastLogin = ref<string | null>(null); // Example, load actual data if available
const headerImage = ref('');
const footerImage = ref('');
const headerImageNeutral = ref('');
const footerImageNeutral = ref('');
const signature = ref('');
const selectedDocumentView = ref('');
const passwordChange = reactive({
    password: '',
    passwordRetry: '',
    oldPassword: '',
});

// Neue Eigenschaften für die überarbeitete UI
const activeTab = ref('personal');
const previewDialog = ref(false);
const previewImage = ref('');

// Form state and refs
const passwordFormRef = ref<any>(null); // Type depends on Vuetify's VForm
const isPasswordFormValid = ref(false);

// Feedback messages
const passwordMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);
const templateMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);
const templateMessageNeutral = ref<{ type: 'success' | 'error'; text: string } | null>(null);
const templateMessageSignature = ref<{ type: 'success' | 'error'; text: string } | null>(null);

// Loading states
const changingPassword = ref(false);
const savingTemplateImages = ref(false);
const savingNeutralTemplateImages = ref(false);
const savingSignature = ref(false);
const savingSettings = ref(false);

// Generic Snackbar state
const snackbar = reactive({
    visible: false,
    message: '',
    color: 'info',
});

function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    snackbar.message = message;
    snackbar.color = color;
    snackbar.visible = true;
}

// --- Computed properties for user data ---
const user = computed(() => authStore.user); // Get reactive user object
const username = computed(() => user.value?.username ?? 'Benutzername');
const userRoles = computed(() => user.value?.roles || []);
const lastLoginFormatted = computed(() => {
    // Format last login date if available in user object
    if (user.value?.last_login) {
        try {
            return new Date(user.value.last_login).toLocaleString('de-DE');
        } catch {
            return 'N/A'; // Fallback for invalid date
        }
    }
    return 'N/A'; // Default if not available
});

// --- Static Data ---
const documentTypeOptions = [
    { name: 'Kachel', key: 'tiles' },
    { name: 'Tabelle', key: 'table' },
];

// --- Validation Rules ---
const passwordRules = {
    required: (value: string) => !!value || 'Erforderlich.',
    minLength: (value: string) => (value && value.length >= 8) || 'Mindestens 8 Zeichen.',
    matchPassword: (value: string) =>
        value === passwordChange.password || 'Passwörter stimmen nicht überein.',
};

// --- Methods ---

const changePassword = async () => {
    // Optional: Trigger validation programmatically
    // const { valid } = await passwordFormRef.value?.validate();
    // if (!valid) return;
    if (!isPasswordFormValid.value) return; // Rely on form's v-model validity

    changingPassword.value = true;
    passwordMessage.value = null; // Clear previous message
    try {
        const response = await apiClientAuth.post(
            '/account/?action=changePassword',
            passwordChange
        );
        if (response.data.success) {
            passwordMessage.value = { type: 'success', text: t('toast.passwordChangeSuccess') };
            passwordFormRef.value?.reset(); // Reset form fields
            // Optionally clear the reactive object too if reset doesn't cover it
            passwordChange.oldPassword = '';
            passwordChange.password = '';
            passwordChange.passwordRetry = '';
        } else {
            passwordMessage.value = {
                type: 'error',
                text: response.data.message || t('toast.passwordChangeError'),
            };
        }
    } catch (error: any) {
        console.error('Password change error:', error);
        passwordMessage.value = {
            type: 'error',
            text: error.response?.data?.error || t('toast.networkError'),
        };
    } finally {
        changingPassword.value = false;
    }
};

const saveTemplateImages = async (neutral: boolean) => {
    const loadingRef = neutral ? savingNeutralTemplateImages : savingTemplateImages;
    const messageRef = neutral ? templateMessageNeutral : templateMessage;

    loadingRef.value = true;
    messageRef.value = null;
    try {
        // Behalte den Payload wie er ist, um die Daten an das Backend zu senden
        const payload = {
            headerImage: !neutral ? headerImage.value : undefined,
            footerImage: !neutral ? footerImage.value : undefined,
            headerImageNeutral: neutral ? headerImageNeutral.value : undefined,
            footerImageNeutral: neutral ? footerImageNeutral.value : undefined,
            neutral: neutral,
        };
        const response = await apiClientAuth.post('/account/?action=updateTemplateImages', payload);

        if (response.data.success) {
            messageRef.value = {
                type: 'success',
                text: t('toast.templateSaveSuccess'),
            };

            // --- Direkte Aktualisierung des Pinia Stores ---
            // 1. Erstelle ein Objekt nur mit den Feldern, die aktualisiert wurden.
            const updatedFields: Partial<User> = {}; // Partial<User> ist wichtig für Typsicherheit
            if (neutral) {
                updatedFields.mail_header_neutral = headerImageNeutral.value; // Wert aus dem v-model
                updatedFields.mail_footer_neutral = footerImageNeutral.value; // Wert aus dem v-model
            } else {
                updatedFields.mail_header = headerImage.value; // Wert aus dem v-model
                updatedFields.mail_footer = footerImage.value; // Wert aus dem v-model
            }

            // 2. Rufe die neue Action im Store auf, um nur diese Felder zu aktualisieren.
            authStore._updateUserFields(updatedFields);
            // --- Ende der direkten Aktualisierung ---

            // Entferne oder kommentiere den alten fetchUser Aufruf aus:
            // await authStore.fetchUser(); // Nicht mehr nötig für die sofortige UI-Aktualisierung
        } else {
            messageRef.value = {
                type: 'error',
                text: response.data.message || t('toast.templateSaveError'),
            };
        }
    } catch (error: any) {
        console.error('Template image save error:', error);
        messageRef.value = {
            type: 'error',
            text: error.response?.data?.error || t('toast.networkError'),
        };
    } finally {
        loadingRef.value = false;
    }
};

// Ähnliche Anpassung für saveSignature und saveSettings
const saveSignature = async () => {
    savingSignature.value = true;
    templateMessageSignature.value = null;
    try {
        const response = await apiClientAuth.post('/account/?action=updateSignature', {
            signature: signature.value,
        });

        if (response.data.success) {
            templateMessageSignature.value = {
                type: 'success',
                text: t('toast.signatureSaveSuccess')
            };

            // --- Direkte Aktualisierung ---
            authStore._updateUserFields({ signature: signature.value });
            // --- Ende ---

            // await authStore.fetchUser(); // Entfernen
        } else {
            templateMessageSignature.value = {
                type: 'error',
                text: response?.data?.error || t('toast.networkError')
            };
        }
    } catch (error: any) {
        console.error('Signature save error:', error);
        templateMessageSignature.value = {
            type: 'error',
            text: error.response?.data?.error || t('toast.networkError')
        };
    } finally {
        savingSignature.value = false;
    }
};

const saveSettings = async () => {
    savingSettings.value = true;
    try {
        const response = await apiClientAuth.post('/account/?action=updateSettings', {
            documentView: selectedDocumentView.value,
            // Add other general settings here if needed
        });

        if (response.data.success) {
            showSnackbar(t('toast.settingsSaveSuccess'), 'success');

            // --- Direkte Aktualisierung ---
            authStore._updateUserFields({ documentView: selectedDocumentView.value });
            // --- Ende ---

            // await authStore.fetchUser(); // Entfernen
        } else {
            showSnackbar(t('toast.settingsSaveError'), 'error');
        }
    } catch (error: any) {
        console.error('Settings save error:', error);
        showSnackbar(t('toast.settingsSaveError'), 'error');
    } finally {
        savingSettings.value = false;
    }
};

// --- (fetchAllUserData - für den Refresh Button) ---
// Stelle sicher, dass diese Funktion existiert und die fetchUser Action im Store aufruft
const refreshingData = ref(false);
const fetchAllUserData = async () => {
    refreshingData.value = true;
    try {
        await authStore.fetchUser(); // Ruft die Store Action zum Neuladen auf
        // Aktualisiere die lokalen Refs NACH dem Fetch, falls sich etwas geändert hat
        const currentUser = authStore.user;
        if (currentUser) {
            headerImage.value = currentUser.mail_header || '';
            footerImage.value = currentUser.mail_footer || '';
            headerImageNeutral.value = currentUser.mail_header_neutral || '';
            footerImageNeutral.value = currentUser.mail_footer_neutral || '';
            signature.value = currentUser.signature || '';
            selectedDocumentView.value = currentUser.documentView || 'tiles';
        }
        showSnackbar(t('toast.userDataRefreshSuccess'), 'success');
    } catch (error) {
        console.error('Error refreshing user data:', error);
        showSnackbar(t('toast.userDataRefreshError'), 'error');
    } finally {
        refreshingData.value = false;
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    // Initialize form values from the Pinia store state
    const currentUser = authStore.user;
    if (currentUser) {
        headerImage.value = currentUser.mail_header || '';
        footerImage.value = currentUser.mail_footer || '';
        headerImageNeutral.value = currentUser.mail_header_neutral || '';
        footerImageNeutral.value = currentUser.mail_footer_neutral || '';
        signature.value = currentUser.signature || '';
        selectedDocumentView.value = currentUser.documentView || 'tiles'; // Default to 'tiles' if not set
        // Load lastLogin if available, otherwise handled by computed property
        // lastLogin.value = currentUser.last_login;
    } else {
        // Optionally fetch user data if not already loaded
        // authStore.fetchUser();
        console.warn('User data not available on mount.');
    }
});
</script>

<template>
    <div class="account-settings-container">
        <error-snackbar
            v-model="snackbar.visible"
            :message="snackbar.message"
            :color="snackbar.color"
        />
        <v-container fluid class="pa-4">
            <!-- Header mit Benutzerprofil und Titel in einer Zeile -->
            <v-row class="align-center">
                <v-col cols="12" md="9">
                    <div class="d-flex align-center mb-4">
                        <v-avatar size="64" color="primary" class="mr-4 profile-avatar">
                            <v-icon size="36" color="white">mdi-account-circle</v-icon>
                        </v-avatar>
                        <div>
                            <h1 class="text-h5 font-weight-medium mb-1">{{ username }}</h1>
                            <div class="text-body-2 d-flex align-center">
                                <v-icon size="small" class="mr-1">mdi-clock-outline</v-icon>
                                Letzter Login: {{ lastLoginFormatted }}
                            </div>
                            <div class="mt-2">
                                <v-chip
                                    v-for="(role, index) in userRoles"
                                    :key="index"
                                    size="small"
                                    class="mr-1"
                                    color="primary"
                                    variant="flat"
                                    >{{ role }}</v-chip
                                >
                                <span
                                    v-if="!userRoles || userRoles.length === 0"
                                    class="text-caption text-medium-emphasis"
                                >
                                    Keine Rollen zugewiesen
                                </span>
                            </div>
                        </div>
                    </div>
                </v-col>
                <v-col cols="12" md="3" class="d-flex justify-end">
                    <v-btn
                        color="primary"
                        variant="tonal"
                        prepend-icon="mdi-refresh"
                        @click="fetchAllUserData"
                        :loading="refreshingData"
                        size="small"
                    >
                        {{ t('profileView.refresh') }}
                    </v-btn>
                </v-col>
            </v-row>

            <!-- Tabs für bessere Organisation -->
            <v-card class="mb-6" variant="outlined">
                <v-tabs
                    v-model="activeTab"
                    color="primary"
                    align-tabs="center"
                    grow
                    class="settings-tabs"
                >
                    <v-tab value="personal">
                        <v-icon start>mdi-account-cog</v-icon>
                        Konto
                    </v-tab>
                    <v-tab value="templates">
                        <v-icon start>mdi-file-document-outline</v-icon>
                        Vorlagen
                    </v-tab>
                    <v-tab value="security">
                        <v-icon start>mdi-lock-outline</v-icon>
                        Sicherheit
                    </v-tab>
                </v-tabs>
            </v-card>

            <!-- Tab Inhalte -->
            <v-window v-model="activeTab" class="mt-2">
                <!-- Persönliche Einstellungen Tab -->
                <v-window-item value="personal">
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-card class="mb-4" variant="outlined">
                                <v-card-title class="d-flex align-center">
                                    <v-icon start class="mr-2">mdi-tune</v-icon>
                                    Allgemeine Einstellungen
                                </v-card-title>
                                <v-divider></v-divider>
                                <v-card-text>
                                    <v-select
                                        :label="t('profileView.documentView')"
                                        :items="documentTypeOptions"
                                        item-value="key"
                                        item-title="name"
                                        v-model="selectedDocumentView"
                                        variant="outlined"
                                        density="compact"
                                        bg-color="grey-darken-3"
                                        color="primary"
                                        hide-details
                                    ></v-select>
                                </v-card-text>
                                <v-divider></v-divider>
                                <v-card-actions>
                                    <v-spacer></v-spacer>
                                    <v-btn
                                        color="primary"
                                        variant="tonal"
                                        @click="saveSettings()"
                                        :loading="savingSettings"
                                        size="small"
                                        >{{ t('save') }}</v-btn
                                    >
                                </v-card-actions>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-window-item>

                <!-- Vorlagen Tab -->
                <v-window-item value="templates">
                    <v-row>
                        <!-- Normale Template Bilder -->
                        <v-col cols="12" md="6">
                            <v-card class="mb-4" variant="outlined">
                                <v-card-title class="d-flex align-center">
                                    <v-icon start class="mr-2">mdi-image-outline</v-icon>
                                    Organisationsbranding
                                </v-card-title>
                                <v-divider></v-divider>
                                <v-card-text>
                                    <div class="py-2">
                                        <div class="d-flex justify-space-between align-center mb-2">
                                            <div class="text-subtitle-2">Header</div>
                                            <v-btn
                                                density="compact"
                                                variant="text"
                                                icon="mdi-eye"
                                                size="small"
                                                @click="
                                                    previewDialog = true;
                                                    previewImage = headerImage;
                                                "
                                                :disabled="!headerImage"
                                            ></v-btn>
                                        </div>
                                        <v-text-field
                                            v-model="headerImage"
                                            :label="t('profileView.headerImageUrl')"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            color="primary"
                                            class="mb-3"
                                            hide-details
                                        ></v-text-field>
                                    </div>

                                    <div class="py-2">
                                        <div class="d-flex justify-space-between align-center mb-2">
                                            <div class="text-subtitle-2">Footer</div>
                                            <v-btn
                                                density="compact"
                                                variant="text"
                                                icon="mdi-eye"
                                                size="small"
                                                @click="
                                                    previewDialog = true;
                                                    previewImage = footerImage;
                                                "
                                                :disabled="!footerImage"
                                            ></v-btn>
                                        </div>
                                        <v-text-field
                                            v-model="footerImage"
                                            :label="t('profileView.footerImageUrl')"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            color="primary"
                                            hide-details
                                        ></v-text-field>
                                    </div>

                                    <v-alert
                                        v-if="templateMessage"
                                        :type="templateMessage.type"
                                        density="compact"
                                        class="mt-3"
                                        variant="tonal"
                                    >
                                        {{ templateMessage.text }}
                                    </v-alert>
                                </v-card-text>
                                <v-divider></v-divider>
                                <v-card-actions>
                                    <v-spacer></v-spacer>
                                    <v-btn
                                        color="primary"
                                        variant="tonal"
                                        @click="saveTemplateImages(false)"
                                        :loading="savingTemplateImages"
                                        size="small"
                                        >{{ t('save') }}</v-btn
                                    >
                                </v-card-actions>
                            </v-card>
                        </v-col>

                        <!-- Neutrale Template Bilder -->
                        <v-col cols="12" md="6">
                            <v-card class="mb-4" variant="outlined">
                                <v-card-title class="d-flex align-center">
                                    <v-icon start class="mr-2">mdi-image-filter</v-icon>
                                    Neutrale Vorlagen
                                </v-card-title>
                                <v-divider></v-divider>
                                <v-card-text>
                                    <div class="py-2">
                                        <div class="d-flex justify-space-between align-center mb-2">
                                            <div class="text-subtitle-2">Neutraler Header</div>
                                            <v-btn
                                                density="compact"
                                                variant="text"
                                                icon="mdi-eye"
                                                size="small"
                                                @click="
                                                    previewDialog = true;
                                                    previewImage = headerImageNeutral;
                                                "
                                                :disabled="!headerImageNeutral"
                                            ></v-btn>
                                        </div>
                                        <v-text-field
                                            v-model="headerImageNeutral"
                                            :label="t('profileView.headerImageUrl')"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            color="primary"
                                            class="mb-3"
                                            hide-details
                                        ></v-text-field>
                                    </div>

                                    <div class="py-2">
                                        <div class="d-flex justify-space-between align-center mb-2">
                                            <div class="text-subtitle-2">Neutraler Footer</div>
                                            <v-btn
                                                density="compact"
                                                variant="text"
                                                icon="mdi-eye"
                                                size="small"
                                                @click="
                                                    previewDialog = true;
                                                    previewImage = footerImageNeutral;
                                                "
                                                :disabled="!footerImageNeutral"
                                            ></v-btn>
                                        </div>
                                        <v-text-field
                                            v-model="footerImageNeutral"
                                            :label="t('profileView.footerImageUrl')"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            color="primary"
                                            hide-details
                                        ></v-text-field>
                                    </div>

                                    <v-alert
                                        v-if="templateMessageNeutral"
                                        :type="templateMessageNeutral.type"
                                        density="compact"
                                        class="mt-3"
                                        variant="tonal"
                                    >
                                        {{ templateMessageNeutral.text }}
                                    </v-alert>
                                </v-card-text>
                                <v-divider></v-divider>
                                <v-card-actions>
                                    <v-spacer></v-spacer>
                                    <v-btn
                                        color="primary"
                                        variant="tonal"
                                        @click="saveTemplateImages(true)"
                                        :loading="savingNeutralTemplateImages"
                                        size="small"
                                        >{{ t('save') }}</v-btn
                                    >
                                </v-card-actions>
                            </v-card>
                        </v-col>

                        <!-- Unterschrift -->
                        <v-col cols="12" md="6">
                            <v-card class="mb-4" variant="outlined">
                                <v-card-title class="d-flex align-center">
                                    <v-icon start class="mr-2">mdi-draw</v-icon>
                                    Unterschrift
                                </v-card-title>
                                <v-divider></v-divider>
                                <v-card-text>
                                    <div class="py-2">
                                        <div class="d-flex justify-space-between align-center mb-2">
                                            <div class="text-subtitle-2">Unterschriftsbild</div>
                                            <v-btn
                                                density="compact"
                                                variant="text"
                                                icon="mdi-eye"
                                                size="small"
                                                @click="
                                                    previewDialog = true;
                                                    previewImage = signature;
                                                "
                                                :disabled="!signature"
                                            ></v-btn>
                                        </div>
                                        <v-text-field
                                            v-model="signature"
                                            :label="t('profileView.signatureImageUrl')"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            color="primary"
                                            hide-details
                                        ></v-text-field>
                                    </div>

                                    <v-alert
                                        v-if="templateMessageSignature"
                                        :type="templateMessageSignature.type"
                                        density="compact"
                                        class="mt-3"
                                        variant="tonal"
                                    >
                                        {{ templateMessageSignature.text }}
                                    </v-alert>
                                </v-card-text>
                                <v-divider></v-divider>
                                <v-card-actions>
                                    <v-spacer></v-spacer>
                                    <v-btn
                                        color="primary"
                                        variant="tonal"
                                        @click="saveSignature()"
                                        :loading="savingSignature"
                                        size="small"
                                        >{{ t('save') }}</v-btn
                                    >
                                </v-card-actions>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-window-item>

                <!-- Sicherheit Tab -->
                <v-window-item value="security">
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-card class="mb-4" variant="outlined">
                                <v-card-title class="d-flex align-center">
                                    <v-icon start class="mr-2">mdi-lock</v-icon>
                                    Passwort ändern
                                </v-card-title>
                                <v-divider></v-divider>
                                <v-card-text>
                                    <v-form ref="passwordFormRef" v-model="isPasswordFormValid">
                                        <v-text-field
                                            v-model="passwordChange.oldPassword"
                                            :label="t('profileView.oldPassword')"
                                            required
                                            :rules="[passwordRules.required]"
                                            type="password"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            color="primary"
                                            class="mb-3"
                                            hide-details
                                        ></v-text-field>
                                        <v-text-field
                                            v-model="passwordChange.password"
                                            :label="t('profileView.newPassword')"
                                            :rules="[
                                                passwordRules.required,
                                                passwordRules.minLength,
                                            ]"
                                            required
                                            type="password"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            color="primary"
                                            class="my-3"
                                            hide-details
                                        ></v-text-field>
                                        <v-text-field
                                            v-model="passwordChange.passwordRetry"
                                            :label="t('profileView.repeatNewPassword')"
                                            :rules="[
                                                passwordRules.required,
                                                passwordRules.matchPassword,
                                                passwordRules.minLength,
                                            ]"
                                            required
                                            type="password"
                                            variant="outlined"
                                            density="compact"
                                            bg-color="grey-darken-3"
                                            color="primary"
                                            hide-details
                                        ></v-text-field>
                                        <v-alert
                                            v-if="passwordMessage"
                                            :type="passwordMessage.type"
                                            density="compact"
                                            class="mt-3"
                                            variant="tonal"
                                        >
                                            {{ passwordMessage.text }}
                                        </v-alert>
                                    </v-form>
                                </v-card-text>
                                <v-divider></v-divider>
                                <v-card-actions>
                                    <v-spacer></v-spacer>
                                    <v-btn
                                        color="primary"
                                        variant="tonal"
                                        :disabled="!isPasswordFormValid"
                                        :loading="changingPassword"
                                        @click="changePassword"
                                        size="small"
                                        >Passwort ändern</v-btn
                                    >
                                </v-card-actions>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="6">
                            <v-card class="mb-4 security-tips" variant="outlined">
                                <v-card-title class="d-flex align-center">
                                    <v-icon start class="mr-2">mdi-shield-check</v-icon>
                                    Sicherheitshinweise
                                </v-card-title>
                                <v-divider></v-divider>
                                <v-card-text>
                                    <div class="d-flex align-center mb-3">
                                        <v-icon color="info" class="mr-2">mdi-information</v-icon>
                                        <div>
                                            <div class="text-subtitle-2">Sicheres Passwort</div>
                                            <div class="text-caption">
                                                Mindestens 8 Zeichen, mit Groß-/Kleinbuchstaben,
                                                Zahlen und Sonderzeichen.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-center mb-3">
                                        <v-icon color="warning" class="mr-2"
                                            >mdi-shield-alert</v-icon
                                        >
                                        <div>
                                            <div class="text-subtitle-2">
                                                Passwort regelmäßig ändern
                                            </div>
                                            <div class="text-caption">
                                                Eine regelmäßige Änderung Ihres Passworts erhöht die
                                                Sicherheit Ihres Kontos.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-center">
                                        <v-icon color="success" class="mr-2"
                                            >mdi-account-key</v-icon
                                        >
                                        <div>
                                            <div class="text-subtitle-2">
                                                Keine Passwort-Weitergabe
                                            </div>
                                            <div class="text-caption">
                                                Teilen Sie Ihr Passwort niemals mit anderen
                                                Personen.
                                            </div>
                                        </div>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <!-- Session Management -->
                        <v-col cols="12">
                            <ActiveSessions />
                        </v-col>

                        <!-- Login History -->
                        <v-col cols="12">
                            <LoginHistory />
                        </v-col>
                    </v-row>
                </v-window-item>
            </v-window>

            <!-- Bild Vorschau Dialog -->
            <v-dialog v-model="previewDialog" max-width="800">
                <v-card class="preview-dialog-card">
                    <v-card-title class="d-flex justify-space-between">
                        <span>Bildvorschau</span>
                        <v-btn
                            icon="mdi-close"
                            variant="text"
                            @click="previewDialog = false"
                        ></v-btn>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text class="pa-0">
                        <v-img
                            :src="previewImage"
                            max-height="500"
                            contain
                            class="preview-dialog-image"
                        >
                            <template v-slot:error>
                                <div
                                    class="d-flex align-center justify-center h-100 bg-grey-darken-3"
                                >
                                    <v-icon size="64" color="grey-darken-1">mdi-image-off</v-icon>
                                    <div class="ml-3 text-body-1">
                                        Bild konnte nicht geladen werden
                                    </div>
                                </div>
                            </template>
                        </v-img>
                    </v-card-text>
                </v-card>
            </v-dialog>
        </v-container>
    </div>
</template>

<style scoped>
.account-settings-container {
    min-height: 90vh;
    background-color: var(--k-ink);
    background-image:
        radial-gradient(circle at 10% 20%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 90% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
}

/* Profil Styles */
.profile-avatar {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    border: 2px solid rgba(59, 130, 246, 0.3);
    transition: all 0.3s ease;
}

.profile-avatar:hover {
    transform: scale(1.05);
    border-color: rgba(59, 130, 246, 0.5);
}

/* Tab Styles */
.settings-tabs {
    background-color: rgba(30, 41, 59, 0.3) !important;
    border-bottom: 1px solid var(--k-line);
}

.settings-tabs :deep(.v-tab) {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
}

.settings-tabs :deep(.v-tab--selected) {
    background-color: rgba(59, 130, 246, 0.1);
}

/* Card Styles */
.v-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.v-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.v-card-title {
    font-size: 1rem;
    font-weight: 600;
    padding: 12px 16px;
    background-color: rgba(30, 41, 59, 0.3);
}

/* Bildvorschau Dialog */
.preview-dialog-card {
    background-color: #1a2233 !important;
    border-radius: 8px;
    overflow: hidden;
}

.preview-dialog-image {
    background-color: rgba(15, 23, 42, 0.3);
    min-height: 300px;
}

/* Sicherheitshinweise Card */
.security-tips {
    border-left: 3px solid rgba(var(--v-theme-info-rgb), 0.7) !important;
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Adjustments */
@media (max-width: 960px) {
    .v-card {
        height: auto;
    }
}
</style>
