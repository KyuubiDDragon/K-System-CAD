<template>
    <div class="authority-branding-content">
        <!-- Loading Overlay -->
        <v-overlay
            :model-value="loadingBranding || savingBranding"
            class="align-center justify-center"
            contained
        >
            <v-progress-circular
                :indeterminate="loadingBranding"
                :value="savingBranding ? 75 : undefined"
                color="primary"
                size="64"
            >
                <template v-if="savingBranding">75%</template>
            </v-progress-circular>
        </v-overlay>

        <!-- Content Container -->
        <div class="branding-content-wrapper">
            <!-- Header -->
            <div class="branding-header mb-6">
                <div class="d-flex align-center mb-2">
                    <v-icon icon="mdi-palette-outline" class="mr-3" size="28" color="primary" />
                    <h2 class="text-h5 font-weight-medium">{{ t('authorityBranding.title') }}</h2>
                </div>
                <p class="text-body-2 text-medium-emphasis">
                    Anpassen der Erscheinung und Branding-Einstellungen für Ihre Authority
                </p>
            </div>

            <v-form ref="formRef" v-model="isFormValid">
                            <v-expand-panel-group v-model="expandedPanels" multiple variant="accordion">
                                
                                <!-- Logo Upload Section -->
                                <v-expand-panel value="logo" elevation="0" class="branding-panel">
                                    <v-expand-panel-title>
                                        <div class="d-flex align-center">
                                            <v-icon icon="mdi-image-outline" class="mr-2" />
                                            <span class="text-subtitle-1 font-weight-medium">
                                                {{ t('authorityBranding.logoSettings') }}
                                            </span>
                                        </div>
                                    </v-expand-panel-title>
                                    <v-expand-panel-text>
                                        <div class="mb-4">
                                            <v-row align="center">
                                                <v-col cols="12" md="8">
                                                    <v-file-input
                                                        v-model="logoFile"
                                                        :label="t('authorityBranding.uploadLogo')"
                                                        accept="image/png,image/jpeg,image/gif,image/webp"
                                                        prepend-icon="mdi-image-plus"
                                                        variant="outlined"
                                                        density="compact"
                                                        :rules="logoRules"
                                                        show-size
                                                        :clearable="false"
                                                        @change="onLogoFileSelect"
                                                    >
                                                        <template v-slot:selection="{ fileNames }">
                                                            <template v-for="fileName in fileNames" :key="fileName">
                                                                <v-chip
                                                                    size="small"
                                                                    label
                                                                    color="primary"
                                                                    class="me-2"
                                                                >
                                                                    {{ fileName }}
                                                                </v-chip>
                                                            </template>
                                                        </template>
                                                    </v-file-input>
                                                    <v-btn
                                                        v-if="logoFile && logoFile.length > 0"
                                                        @click="uploadLogo"
                                                        :loading="uploadingLogo"
                                                        color="primary"
                                                        variant="tonal"
                                                        class="mt-2"
                                                    >
                                                        <v-icon start>mdi-upload</v-icon>
                                                        {{ t('authorityBranding.uploadLogoButton') }}
                                                    </v-btn>
                                                </v-col>
                                                <v-col cols="12" md="4" class="text-center">
                                                    <div class="logo-preview">
                                                        <v-avatar
                                                            v-if="brandingData.logo_url"
                                                            size="100"
                                                            class="logo-preview-avatar"
                                                        >
                                                            <img
                                                                :src="getFullImageUrl(brandingData.logo_url)"
                                                                :alt="brandingData.authority_display_name"
                                                            >
                                                        </v-avatar>
                                                        <div v-else class="no-logo-placeholder">
                                                            <img
                                                                :src="getDefaultLogo()"
                                                                :alt="t('authorityBranding.defaultLogo')"
                                                                class="default-logo-preview"
                                                            />
                                                            <p class="text-caption mt-2">{{ t('authorityBranding.usingDefaultLogo') }}</p>
                                                        </div>
                                                    </div>
                                                    <v-btn
                                                        v-if="brandingData.logo_url"
                                                        @click="removeLogo"
                                                        color="error"
                                                        variant="text"
                                                        size="small"
                                                        class="mt-2"
                                                    >
                                                        <v-icon start>mdi-delete</v-icon>
                                                        {{ t('authorityBranding.removeLogo') }}
                                                    </v-btn>
                                                </v-col>
                                            </v-row>
                                        </div>
                                    </v-expand-panel-text>
                                </v-expand-panel>

                                <!-- Color Settings Section -->
                                <v-expand-panel value="colors" elevation="0" class="branding-panel">
                                    <v-expand-panel-title>
                                        <div class="d-flex align-center">
                                            <v-icon icon="mdi-palette" class="mr-2" />
                                            <span class="text-subtitle-1 font-weight-medium">
                                                {{ t('authorityBranding.colorSettings') }}
                                            </span>
                                        </div>
                                    </v-expand-panel-title>
                                    <v-expand-panel-text>
                                        <v-row>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="brandingData.primary_color"
                                                    :label="t('authorityBranding.primaryColor')"
                                                    :hint="t('authorityBranding.primaryColorHint')"
                                                    persistent-hint
                                                    variant="outlined"
                                                    density="compact"
                                                    :rules="colorRules"
                                                    class="mb-4"
                                                >
                                                    <template v-slot:prepend-inner>
                                                        <v-menu>
                                                            <template v-slot:activator="{ props }">
                                                                <div
                                                                    v-bind="props"
                                                                    class="color-swatch"
                                                                    :style="{ backgroundColor: brandingData.primary_color }"
                                                                ></div>
                                                            </template>
                                                            <v-color-picker
                                                                v-model="brandingData.primary_color"
                                                                mode="hex"
                                                                @update:model-value="onColorChange"
                                                            ></v-color-picker>
                                                        </v-menu>
                                                    </template>
                                                </v-text-field>
                                            </v-col>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="brandingData.secondary_color"
                                                    :label="t('authorityBranding.secondaryColor')"
                                                    :hint="t('authorityBranding.secondaryColorHint')"
                                                    persistent-hint
                                                    variant="outlined"
                                                    density="compact"
                                                    :rules="colorRules"
                                                    class="mb-4"
                                                >
                                                    <template v-slot:prepend-inner>
                                                        <v-menu>
                                                            <template v-slot:activator="{ props }">
                                                                <div
                                                                    v-bind="props"
                                                                    class="color-swatch"
                                                                    :style="{ backgroundColor: brandingData.secondary_color }"
                                                                ></div>
                                                            </template>
                                                            <v-color-picker
                                                                v-model="brandingData.secondary_color"
                                                                mode="hex"
                                                                @update:model-value="onColorChange"
                                                            ></v-color-picker>
                                                        </v-menu>
                                                    </template>
                                                </v-text-field>
                                            </v-col>
                                        </v-row>
                                        
                                        <!-- Color Preview -->
                                        <v-card class="color-preview-card mb-4" :style="colorPreviewStyle">
                                            <v-card-title class="color-preview-title">
                                                {{ t('authorityBranding.colorPreview') }}
                                            </v-card-title>
                                            <v-card-text>
                                                <v-btn :style="previewButtonStyle" class="mr-2">
                                                    {{ t('authorityBranding.primaryButton') }}
                                                </v-btn>
                                                <v-btn :style="previewSecondaryButtonStyle" variant="outlined">
                                                    {{ t('authorityBranding.secondaryButton') }}
                                                </v-btn>
                                            </v-card-text>
                                        </v-card>
                                    </v-expand-panel-text>
                                </v-expand-panel>

                                <!-- App Title & Background Section -->
                                <v-expand-panel value="app-settings" elevation="0" class="branding-panel">
                                    <v-expand-panel-title>
                                        <div class="d-flex align-center">
                                            <v-icon icon="mdi-application-settings" class="mr-2" />
                                            <span class="text-subtitle-1 font-weight-medium">
                                                {{ t('authorityBranding.appSettings') }}
                                            </span>
                                        </div>
                                    </v-expand-panel-title>
                                    <v-expand-panel-text>
                                        <v-text-field
                                            v-model="brandingData.app_title"
                                            :label="t('authorityBranding.appTitle')"
                                            :hint="t('authorityBranding.appTitleHint')"
                                            persistent-hint
                                            variant="outlined"
                                            density="compact"
                                            :placeholder="brandingData.authority_display_name"
                                            class="mb-4"
                                        ></v-text-field>

                                        <!-- Default Background Upload -->
                                        <div class="mb-4">
                                            <v-row align="center">
                                                <v-col cols="12" md="8">
                                                    <v-file-input
                                                        v-model="backgroundFile"
                                                        :label="t('authorityBranding.uploadBackground')"
                                                        accept="image/png,image/jpeg,image/gif,image/webp"
                                                        prepend-icon="mdi-image-multiple"
                                                        variant="outlined"
                                                        density="compact"
                                                        :rules="backgroundRules"
                                                        show-size
                                                        :clearable="false"
                                                        @change="onBackgroundFileSelect"
                                                    ></v-file-input>
                                                    <v-btn
                                                        v-if="backgroundFile && backgroundFile.length > 0"
                                                        @click="uploadBackground"
                                                        :loading="uploadingBackground"
                                                        color="primary"
                                                        variant="tonal"
                                                        class="mt-2"
                                                    >
                                                        <v-icon start>mdi-upload</v-icon>
                                                        {{ t('authorityBranding.uploadBackgroundButton') }}
                                                    </v-btn>
                                                </v-col>
                                                <v-col cols="12" md="4" class="text-center">
                                                    <div class="background-preview">
                                                        <div
                                                            v-if="brandingData.default_background"
                                                            class="background-preview-container"
                                                            :style="{ backgroundImage: `url(${getFullImageUrl(brandingData.default_background)})` }"
                                                        >
                                                            <div class="background-overlay">
                                                                {{ t('authorityBranding.backgroundPreview') }}
                                                            </div>
                                                        </div>
                                                        <div v-else class="no-background-placeholder">
                                                            <div
                                                                class="background-preview-container default-background"
                                                                :style="{ backgroundImage: `url(${getDefaultBackground()})` }"
                                                            >
                                                                <div class="background-overlay">
                                                                    {{ t('authorityBranding.defaultBackground') }}
                                                                </div>
                                                            </div>
                                                            <p class="text-caption mt-2">{{ t('authorityBranding.usingDefaultBackground') }}</p>
                                                        </div>
                                                    </div>
                                                    <v-btn
                                                        v-if="brandingData.default_background"
                                                        @click="removeBackground"
                                                        color="error"
                                                        variant="text"
                                                        size="small"
                                                        class="mt-2"
                                                    >
                                                        <v-icon start>mdi-delete</v-icon>
                                                        {{ t('authorityBranding.removeBackground') }}
                                                    </v-btn>
                                                </v-col>
                                            </v-row>
                                        </div>
                                    </v-expand-panel-text>
                                </v-expand-panel>

                            </v-expand-panel-group>
            </v-form>

            <!-- Action Buttons -->
            <div class="branding-actions mt-8 pt-4">
                <div class="d-flex justify-end">
                    <v-btn
                        @click="resetChanges"
                        :disabled="!hasChanges"
                        variant="outlined"
                        color="grey"
                        class="mr-3"
                    >
                        <v-icon start>mdi-refresh</v-icon>
                        {{ t('authorityBranding.reset') }}
                    </v-btn>
                    <v-btn
                        @click="saveBrandingData"
                        :loading="savingBranding"
                        :disabled="!isFormValid || !hasChanges"
                        color="primary"
                        variant="elevated"
                    >
                        <v-icon start>mdi-content-save</v-icon>
                        {{ t('authorityBranding.save') }}
                    </v-btn>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vue-toastification';
import { apiClientAuth } from '@/api';

// Setup
const { t } = useI18n();
const toast = useToast();

// Form & Loading State
const formRef = ref<any>(null);
const isFormValid = ref(false);
const loadingBranding = ref(false);
const savingBranding = ref(false);
const uploadingLogo = ref(false);
const uploadingBackground = ref(false);
const expandedPanels = ref(['logo', 'colors', 'app-settings']);

// File Upload State
const logoFile = ref<File[]>([]);
const backgroundFile = ref<File[]>([]);

// Branding Data
const brandingData = reactive({
    logo_url: '',
    primary_color: 'var(--k-accent)',
    secondary_color: '#6B7280',
    app_title: '',
    default_background: '',
    authority_name: '',
    authority_display_name: ''
});

const originalBrandingData = ref<any>({});

// Computed Properties
const hasChanges = computed(() => {
    return JSON.stringify(brandingData) !== JSON.stringify(originalBrandingData.value);
});

const colorPreviewStyle = computed(() => ({
    background: `linear-gradient(135deg, ${brandingData.primary_color}20, ${brandingData.secondary_color}15)`,
    border: `1px solid ${brandingData.primary_color}40`
}));

const previewButtonStyle = computed(() => ({
    backgroundColor: brandingData.primary_color,
    color: '#FFFFFF'
}));

const previewSecondaryButtonStyle = computed(() => ({
    borderColor: brandingData.secondary_color,
    color: brandingData.secondary_color
}));

// Validation Rules
const colorRules = [
    (v: string) => !!v || t('authorityBranding.colorRequired'),
    (v: string) => /^#[0-9A-Fa-f]{6}$/.test(v) || t('authorityBranding.colorInvalid')
];

const logoRules = [
    (files: File[]) => !files || files.length === 0 || files[0].size <= 5 * 1024 * 1024 || t('authorityBranding.logoSizeError')
];

const backgroundRules = [
    (files: File[]) => !files || files.length === 0 || files[0].size <= 10 * 1024 * 1024 || t('authorityBranding.backgroundSizeError')
];

// Methods
const getFullImageUrl = (path: string) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    return `${import.meta.env.VITE_API_URL}${path}`;
};

// Get default logo based on authority name
const getDefaultLogo = () => {
    const authorityName = brandingData.authority_name?.toLowerCase() || '';
    
    // Authority-specific logos
    if (authorityName.includes('lsfd') || authorityName.includes('fire')) {
        return new URL('@/assets/lsfdschriftzug.png', import.meta.url).href;
    }
    
    // Default fallback
    return new URL('@/assets/logo.png', import.meta.url).href;
};

// Get default background based on authority name
const getDefaultBackground = () => {
    const authorityName = brandingData.authority_name?.toLowerCase() || '';
    
    // Authority-specific backgrounds
    if (authorityName.includes('lsfd') || authorityName.includes('fire')) {
        return '/img/bg2.png'; // Fire department background
    } else if (authorityName.includes('lspd') || authorityName.includes('police')) {
        return '/img/bg3.png'; // Police background  
    } else if (authorityName.includes('ems') || authorityName.includes('medical')) {
        return '/img/bg.jpg'; // Medical/EMS background
    }
    
    // Default fallback
    return '/img/bg.jpg';
};

const loadBrandingData = async () => {
    loadingBranding.value = true;
    try {
        const response = await apiClientAuth.get('/admin/authority/branding.php?action=getBranding');
        if (response.data?.success && response.data?.branding) {
            const data = response.data.branding;
            Object.assign(brandingData, data);
            originalBrandingData.value = { ...data };
        }
    } catch (error: any) {
        console.error('Error loading branding data:', error);
        toast.error(t('authorityBranding.loadError'));
    } finally {
        loadingBranding.value = false;
    }
};

const saveBrandingData = async () => {
    if (!isFormValid.value) return;
    
    savingBranding.value = true;
    try {
        const response = await apiClientAuth.post('/admin/authority/branding.php?action=updateBranding', {
            primary_color: brandingData.primary_color,
            secondary_color: brandingData.secondary_color,
            app_title: brandingData.app_title
        });
        
        if (response.data?.success) {
            toast.success(t('authorityBranding.saveSuccess'));
            originalBrandingData.value = { ...brandingData };
        } else {
            toast.error(response.data?.error || t('authorityBranding.saveError'));
        }
    } catch (error: any) {
        console.error('Error saving branding data:', error);
        toast.error(t('authorityBranding.saveError'));
    } finally {
        savingBranding.value = false;
    }
};

const uploadLogo = async () => {
    if (!logoFile.value || logoFile.value.length === 0) return;
    
    uploadingLogo.value = true;
    try {
        const formData = new FormData();
        formData.append('logo', logoFile.value[0]);
        
        const response = await apiClientAuth.post('/admin/authority/branding.php?action=uploadLogo', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        if (response.data?.success) {
            brandingData.logo_url = response.data.logo_url;
            logoFile.value = [];
            toast.success(t('authorityBranding.logoUploadSuccess'));
        } else {
            toast.error(response.data?.error || t('authorityBranding.logoUploadError'));
        }
    } catch (error: any) {
        console.error('Error uploading logo:', error);
        toast.error(t('authorityBranding.logoUploadError'));
    } finally {
        uploadingLogo.value = false;
    }
};

const uploadBackground = async () => {
    if (!backgroundFile.value || backgroundFile.value.length === 0) return;
    
    uploadingBackground.value = true;
    try {
        const formData = new FormData();
        formData.append('background', backgroundFile.value[0]);
        
        const response = await apiClientAuth.post('/admin/authority/branding.php?action=uploadBackground', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        if (response.data?.success) {
            brandingData.default_background = response.data.background_url;
            backgroundFile.value = [];
            toast.success(t('authorityBranding.backgroundUploadSuccess'));
        } else {
            toast.error(response.data?.error || t('authorityBranding.backgroundUploadError'));
        }
    } catch (error: any) {
        console.error('Error uploading background:', error);
        toast.error(t('authorityBranding.backgroundUploadError'));
    } finally {
        uploadingBackground.value = false;
    }
};

const removeLogo = () => {
    brandingData.logo_url = '';
};

const removeBackground = () => {
    brandingData.default_background = '';
};

const resetChanges = () => {
    Object.assign(brandingData, originalBrandingData.value);
    logoFile.value = [];
    backgroundFile.value = [];
};

const onLogoFileSelect = () => {
    // File validation is handled by rules
};

const onBackgroundFileSelect = () => {
    // File validation is handled by rules  
};

const onColorChange = () => {
    // Color preview updates automatically via computed
};

// Lifecycle
onMounted(() => {
    loadBrandingData();
});
</script>

<style scoped>
.authority-branding-content {
    position: relative;
    padding: 2rem;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
    background: linear-gradient(135deg, rgba(var(--v-theme-surface), 0.05) 0%, rgba(var(--v-theme-primary), 0.02) 100%);
}

.branding-content-wrapper {
    max-width: 900px;
    margin: 0 auto;
    background: rgba(var(--v-theme-surface), 0.7);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    border: 1px solid rgba(var(--v-border-color), 0.12);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    padding: 2rem;
    transition: all 0.3s ease;
}

.branding-content-wrapper:hover {
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.branding-header {
    border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding-bottom: 1.5rem;
    margin-bottom: 2rem;
}

.branding-actions {
    border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    background: rgba(var(--v-theme-surface), 0.3);
    backdrop-filter: blur(8px);
    border-radius: 12px;
    margin: 2rem -2rem -2rem;
    padding: 1.5rem 2rem;
}

.branding-panel {
    margin-bottom: 1.5rem;
    border: 1px solid rgba(var(--v-border-color), 0.08) !important;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    background: rgba(var(--v-theme-surface), 0.4);
}

.branding-panel:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border-color: rgba(var(--v-theme-primary), 0.2) !important;
}

/* Remove default Vuetify expansion panel styling */
.branding-panel .v-expansion-panel-title {
    border-bottom: none !important;
}

.branding-panel .v-expansion-panel__shadow {
    display: none !important;
}

/* Custom styling for expansion panel group */
:deep(.v-expansion-panel-group) {
    border: none !important;
    box-shadow: none !important;
}

:deep(.v-expansion-panel) {
    border: none !important;
    box-shadow: none !important;
}

:deep(.v-expansion-panel-title) {
    border-bottom: none !important;
    background: transparent !important;
}

:deep(.v-expansion-panel-text__wrapper) {
    padding: 1rem 1.5rem !important;
}

.color-swatch {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    border: 2px solid rgba(0, 0, 0, 0.12);
    cursor: pointer;
    transition: all 0.3s ease;
}

.color-swatch:hover {
    transform: scale(1.1);
    border-color: rgba(0, 0, 0, 0.24);
}

.color-preview-card {
    border: 2px dashed rgba(0, 0, 0, 0.12);
    transition: all 0.3s ease;
}

.color-preview-title {
    font-weight: 500;
    opacity: 0.8;
}

.logo-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 140px;
}

.logo-preview-avatar {
    border: 3px solid rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.logo-preview-avatar:hover {
    transform: scale(1.05);
}

.no-logo-placeholder,
.no-background-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    border: 2px dashed rgba(0, 0, 0, 0.12);
    border-radius: 8px;
    background: rgba(0, 0, 0, 0.02);
}

.default-logo-preview {
    width: 80px;
    height: 80px;
    object-fit: contain;
    border-radius: 8px;
    border: 2px dashed rgba(0, 0, 0, 0.2);
    padding: 8px;
    background: var(--k-row-hover);
    opacity: 0.7;
    transition: all 0.3s ease;
}

.default-logo-preview:hover {
    opacity: 0.9;
    transform: scale(1.05);
}

.default-background {
    opacity: 0.7;
    border: 2px dashed rgba(255, 193, 7, 0.5) !important;
    background-blend-mode: overlay;
}

.default-background:hover {
    opacity: 0.9;
    transform: scale(1.02);
}

.default-background .background-overlay {
    background: linear-gradient(transparent, rgba(255, 193, 7, 0.8));
    color: #000;
    font-weight: 600;
}

.background-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 120px;
}

.background-preview-container {
    width: 120px;
    height: 80px;
    background-size: cover;
    background-position: center;
    border-radius: 8px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.background-preview-container:hover {
    transform: scale(1.05);
}

.background-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
    color: var(--k-ink);
    text-align: center;
    padding: 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Animation for expansion panels */
.branding-panel {
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.branding-panel:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>