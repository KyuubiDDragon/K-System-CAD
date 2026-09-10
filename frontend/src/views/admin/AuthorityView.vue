<template>
    <v-container fluid class="pa-4">
        <v-row class="mb-4 align-center">
            <v-col cols="auto">
                <div class="d-flex align-center">
                    <v-icon icon="mdi-shield-key" size="24" class="mr-2 text-primary"></v-icon>
                    <h1 class="text-h5 font-weight-medium mb-0">{{ t('authorityView.title') }}</h1>
                </div>
            </v-col>
            <v-col>
                <v-alert
                    border="start"
                    border-color="primary"
                    elevation="2"
                    density="comfortable"
                    icon="mdi-information-outline"
                    variant="tonal"
                    class="mt-0 info-alert"
                >
                    Manage authorities (sites) and their feature permissions. Each authority can
                    have different access to system features.
                </v-alert>
            </v-col>
        </v-row>

        <v-card class="main-card elevation-4">
            <v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                <v-toolbar-title class="text-h6">
                    <v-icon start size="20" class="mr-2">mdi-shield-account</v-icon>
                    Authorities
                </v-toolbar-title>
                <v-spacer></v-spacer>

                <v-text-field
                    v-model="search"
                    append-icon="mdi-magnify"
                    label="Search authorities"
                    single-line
                    hide-details
                    density="compact"
                    class="mr-4"
                    style="max-width: 300px"
                ></v-text-field>

                <v-btn
                    v-if="canEdit"
                    @click="openCreateAuthorityModal"
                    color="primary"
                    variant="elevated"
                    size="small"
                    prepend-icon="mdi-plus"
                    class="action-button"
                >
                    {{ t('authorityView.new') }}
                </v-btn>
            </v-toolbar>

            <v-divider></v-divider>

            <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
            <KTableToolbar :columns="kCols" :shown="(filteredAuthorities || []).length" />
            <v-data-table
                :headers="kCols.visible.value"
                :items="filteredAuthorities"
                item-value="id"
                class="elevation-0"
                :loading="loadingAuthorities"
                density="comfortable"
                hover
            >
                <template v-slot:[`item.id`]="{ item }">
                    <v-chip size="small" label color="blue-grey" variant="tonal" class="id-chip">
                        {{ item.id }}
                    </v-chip>
                </template>

                <template v-slot:[`item.name`]="{ item }">
                    <span class="font-weight-medium">{{ item.name }}</span>
                </template>

                <template v-slot:[`item.display_name`]="{ item }">
                    <span class="font-weight-medium">{{ item.display_name }}</span>
                </template>

                <template v-slot:[`item.active`]="{ item }">
                    <v-chip
                        size="small"
                        :color="item.active ? 'success' : 'error'"
                        :text="item.active ? 'Active' : 'Inactive'"
                        variant="tonal"
                    ></v-chip>
                </template>

                <template v-slot:[`item.actions`]="{ item }">
                    <div class="d-flex gap-1">
                        <v-tooltip text="Edit" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canEdit"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="editAuthority(item)"
                                    v-bind="props"
                                    class="action-icon"
                                >
                                    <v-icon size="small">mdi-pencil</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>

                        <v-tooltip text="Manage Features" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canEdit"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="manageFeatures(item)"
                                    v-bind="props"
                                    color="info"
                                    class="action-icon"
                                >
                                    <v-icon size="small">mdi-cog</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>

                        <v-tooltip text="Delete" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canDelete"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="confirmDeleteAuthority(item)"
                                    v-bind="props"
                                    color="error"
                                    class="action-icon"
                                >
                                    <v-icon size="small">mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>
                    </div>
                </template>

                <template v-slot:no-data>
                    <div class="empty-state">
                        <v-icon size="40" color="grey-darken-1" class="mb-2">mdi-shield-off</v-icon>
                        <span>No authorities found.</span>
                    </div>
                </template>

                <template v-slot:loading>
                    <div class="loading-state">
                        <v-progress-circular
                            indeterminate
                            color="primary"
                            size="24"
                            class="mr-2"
                        ></v-progress-circular>
                        <span>Loading authorities...</span>
                    </div>
                </template>
            </v-data-table>
        </v-card>

        <v-dialog v-model="showCreateAuthorityModal" persistent max-width="600px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-shield-plus" class="mr-2"></v-icon>
                    {{ t('authorityView.createTitle') }}
                </v-card-title>

                <v-card-text class="pa-4">
                    <v-form ref="itemFormRef" v-model="isItemFormValid">
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="newAuthority.name"
                                        label="Name"
                                        required
                                        :rules="[(v) => !!v || 'Name is required']"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-shield-account"
                                        hint="Internal identifier (e.g., 'fireguard')"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12">
                                    <v-text-field
                                        v-model="newAuthority.display_name"
                                        label="Display Name"
                                        required
                                        :rules="[(v) => !!v || 'Display name is required']"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-card-account-details"
                                        hint="Public-facing name (e.g., 'FireGuard Solutions')"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12">
                                    <v-textarea
                                        v-model="newAuthority.description"
                                        label="Description"
                                        variant="outlined"
                                        rows="3"
                                        auto-grow
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-text-box"
                                    ></v-textarea>
                                </v-col>

                                <v-col cols="12">
                                    <v-switch
                                        v-model="newAuthority.active"
                                        label="Active"
                                        color="success"
                                        density="comfortable"
                                        inset
                                    ></v-switch>
                                </v-col>
                                
                                <v-col cols="12">
                                    <v-divider class="mb-3"></v-divider>
                                    <div class="text-subtitle-1 mb-3">
                                        <v-icon icon="mdi-account-key" class="mr-1"></v-icon>
                                        Admin Account
                                    </div>
                                    <v-alert
                                        density="comfortable"
                                        type="info"
                                        variant="tonal"
                                        class="mb-3"
                                    >
                                        Create an administrator account for this authority with full permissions.
                                        This account will have access to all features and functions within the system.
                                    </v-alert>
                                </v-col>

                                <v-col cols="12">
                                    <v-switch
                                        v-model="newAuthority.create_admin_account"
                                        label="Create Admin Account"
                                        color="primary"
                                        density="comfortable"
                                        inset
                                    ></v-switch>
                                </v-col>

                                <template v-if="newAuthority.create_admin_account">
                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="newAuthority.admin_username"
                                            label="Admin Username"
                                            required
                                            :rules="[
                                                (v) => !!v || 'Username is required',
                                                (v) => v.length >= 3 || 'Username must be at least 3 characters',
                                                (v) => /^[a-zA-Z0-9_]+$/.test(v) || 'Username can only contain letters, numbers, and underscores'
                                            ]"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            bg-color="grey-darken-3"
                                            prepend-inner-icon="mdi-account"
                                            hint="Login username for administrator"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="newAuthority.admin_email"
                                            label="Admin Email"
                                            required
                                            :rules="[
                                                (v) => !!v || 'Email is required',
                                                (v) => /.+@.+\..+/.test(v) || 'Email must be valid'
                                            ]"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            bg-color="grey-darken-3"
                                            prepend-inner-icon="mdi-email"
                                            hint="Administrator email address"
                                        ></v-text-field>
                                    </v-col>

                                    <v-col cols="12">
                                        <v-text-field
                                            v-model="newAuthority.admin_password"
                                            label="Admin Password"
                                            required
                                            :rules="[
                                                (v) => !!v || 'Password is required',
                                                (v) => v.length >= 8 || 'Password must be at least 8 characters'
                                            ]"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            bg-color="grey-darken-3"
                                            prepend-inner-icon="mdi-lock"
                                            hint="Minimum 8 characters"
                                            :type="showPassword ? 'text' : 'password'"
                                            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                            @click:append-inner="showPassword = !showPassword"
                                        ></v-text-field>
                                    </v-col>
                                </template>
                            </v-row>
                        </v-container>
                    </v-form>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeCreateAuthorityModal">{{ t('cancel') }}</v-btn>
                    <v-btn
                        color="primary"
                        variant="elevated"
                        @click="createAuthority"
                        :disabled="!isItemFormValid"
                        :loading="savingItem"
                    >
                        {{ t('create') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="showEditAuthorityModal" persistent max-width="600px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-shield-edit" class="mr-2"></v-icon>
                    Edit Authority
                </v-card-title>

                <v-card-text class="pa-4">
                    <v-form ref="itemFormRef" v-model="isItemFormValid">
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="editedAuthority.value.name"
                                        label="Name"
                                        required
                                        :rules="[(v) => !!v || 'Name is required']"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-shield-account"
                                        hint="Internal identifier (e.g., 'fireguard')"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12">
                                    <v-text-field
                                        v-model="editedAuthority.value.display_name"
                                        label="Display Name"
                                        required
                                        :rules="[(v) => !!v || 'Display name is required']"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-card-account-details"
                                        hint="Public-facing name (e.g., 'FireGuard Solutions')"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12">
                                    <v-textarea
                                        v-model="editedAuthority.value.description"
                                        label="Description"
                                        variant="outlined"
                                        rows="3"
                                        auto-grow
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-text-box"
                                    ></v-textarea>
                                </v-col>

                                <v-col cols="12">
                                    <v-switch
                                        v-model="editedAuthority.value.active"
                                        label="Active"
                                        color="success"
                                        density="comfortable"
                                        inset
                                    ></v-switch>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-form>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeEditAuthorityModal">Cancel</v-btn>
                    <v-btn
                        color="primary"
                        variant="elevated"
                        @click="updateAuthority"
                        :disabled="!isItemFormValid"
                        :loading="savingItem"
                    >
                        Update
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="showFeaturesModal" persistent max-width="800px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-cog-transfer" class="mr-2"></v-icon>
                    Manage Features for {{ selectedAuthority.value.name }}
                </v-card-title>

                <v-card-text class="pa-4">
                    <v-text-field
                        v-model="featureSearch"
                        label="Search features"
                        append-icon="mdi-magnify"
                        variant="outlined"
                        density="comfortable"
                        color="primary"
                        bg-color="grey-darken-3"
                        class="mb-4"
                    ></v-text-field>

                    <v-list class="feature-list bg-grey-darken-3 rounded" density="comfortable">
                        <v-list-item v-if="loadingFeatures && features.length === 0">
                            <v-list-item-title class="text-center text-grey">
                                Loading features...
                            </v-list-item-title>
                        </v-list-item>
                        <v-list-item v-else-if="filteredFeatures.length === 0">
                            <v-list-item-title class="text-center text-grey">
                                {{ featureSearch ? 'No features match your search.' : 'No features found.' }}
                            </v-list-item-title>
                        </v-list-item>
                        <v-list-item
                            v-for="feature in filteredFeatures"
                            :key="feature.id"
                            :class="{ 'selected-feature': isFeatureEnabled(feature.id) }"
                        >
                            <template v-slot:prepend>
                                <v-checkbox
                                    :model-value="isFeatureEnabled(feature.id)"
                                    @update:model-value="toggleFeature(feature.id)"
                                    color="primary"
                                    hide-details
                                    density="compact"
                                ></v-checkbox>
                            </template>

                            <v-list-item-title class="font-weight-medium">{{
                                feature.name
                            }}</v-list-item-title>
                            <v-list-item-subtitle>{{ feature.description }}</v-list-item-subtitle>
                            <v-list-item-subtitle class="text-caption text-primary">
                                Code: {{ feature.code }}
                            </v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeFeaturesModal">Cancel</v-btn>
                    <v-btn
                        color="primary"
                        variant="elevated"
                        @click="saveFeatures"
                        :loading="savingItem"
                    >
                        Save Features
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="showDeleteModal" persistent max-width="500px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                    Confirm Delete
                </v-card-title>

                <v-card-text class="pt-4">
                    <p>
                        Are you sure you want to delete the authority
                        <span class="font-weight-bold">"{{ selectedAuthority.value.name }}"</span>?
                    </p>
                    <div class="text-caption text-medium-emphasis mt-2">
                        This action cannot be undone and may affect users with this authority.
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeDeleteModal" class="mr-2">Cancel</v-btn>
                    <v-btn
                        color="error"
                        variant="elevated"
                        @click="deleteAuthority"
                        :loading="deletingItem"
                        class="delete-button"
                    >
                        <v-icon start>mdi-delete</v-icon>
                        Delete
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive, unref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Your Axios instance
import { useToast } from 'vue-toastification';

import KTableToolbar from '@/components/table/KTableToolbar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
// --- Define Interfaces ---
interface Authority {
    id: number | null;
    name: string;
    display_name: string;
    description: string;
    active: boolean;
    created_at?: string;
    updated_at?: string;
    create_admin_account?: boolean;
    admin_username?: string;
    admin_email?: string;
    admin_password?: string;
}

interface Feature {
    id: number;
    name: string;
    code: string;
    description: string;
    created_at?: string;
    updated_at?: string;
}

// --- Router & Permissions ---
const route = useRoute();

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>;
  canEdit?: boolean;
  canDelete?: boolean;
  canCreate?: boolean;
  allPermissions?: boolean;
  desktopWindow?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  desktopWindow: false,
});

// Check permissions from both route.meta and props
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);
const canCreate = computed(() => props.allPermissions || props.canCreate || !!route.meta.canCreate);

// --- Setup Toast ---
const toast = useToast();
const { t } = useI18n();

// --- Component State ---
const authorities = ref<Authority[]>([]);
const features = ref<Feature[]>([]); // This will hold ALL available features
const search = ref('');
const featureSearch = ref('');
const loadingAuthorities = ref(false);
const loadingFeatures = ref(false); // Loading state for features modal
const savingItem = ref(false);
const deletingItem = ref(false);
const showPassword = ref(false); // For password field visibility toggle

// --- Dialog States & Data ---
const showCreateAuthorityModal = ref(false);
const showEditAuthorityModal = ref(false);
const showFeaturesModal = ref(false);
const showDeleteModal = ref<boolean>(false); // Explicitly define boolean type
const selectedAuthorityFeatures = ref<number[]>([]); // Holds feature IDs for the *currently selected* authority
const itemFormRef = ref<any>(null); // Ref for the v-form component
const isItemFormValid = ref(false); // v-form validation state

const initialAuthorityData: Authority = { // Use interface
    id: null as any, // ID is null for new/initial state
    name: '',
    display_name: '',
    description: '',
    active: true,
    create_admin_account: true,
    admin_username: '',
    admin_email: '',
    admin_password: ''
};

const selectedAuthority = reactive<{ value: Authority }>({ value: { ...initialAuthorityData } });
const newAuthority = reactive<Authority>({ ...initialAuthorityData }); // Use interface
const editedAuthority = reactive<{ value: Authority }>({ value: { ...initialAuthorityData } });

// --- Table Headers ---
const authorityHeaders = [
    { title: t('adminAuthority.headers.id'), key: 'id', align: 'start' as const, sortable: true, width: '80px' },
    { title: t('adminAuthority.headers.name'), key: 'name', sortable: true },
    { title: t('adminAuthority.headers.displayName'), key: 'display_name', sortable: true },
    { title: t('adminAuthority.headers.description'), key: 'description', sortable: true },
    { title: t('adminAuthority.headers.status'), key: 'active', sortable: true, width: '120px' },
    { title: t('adminAuthority.headers.actions'), key: 'actions', sortable: false, align: 'end' as const, width: '120px' },
];

// --- Validation Rules ---
// Diese Ansicht benutzt keine Pflichtfeld-Regel; die Definition stand hier
// auskommentiert und ist ersatzlos entfallen.

// --- Data Fetching ---

// Fetch list of all authorities
const fetchAuthorities = async () => {
    loadingAuthorities.value = true;
    try {
        const response = await apiClientAuth.get('/admin/authority/index.php?action=getAuthorities');
        authorities.value = response.data || [];
    } catch (error: any) {
        console.error('Error fetching authorities:', error);
        toast.error(error.response?.data?.error || 'Failed to load authorities');
    } finally {
        loadingAuthorities.value = false;
    }
};

// Fetch list of ALL available system features
const fetchFeatures = async () => {
    loadingFeatures.value = true; // Set loading state for features
    try {
        console.log('Fetching all system features...');
        // NEUE API: GET mit action=getFeatures
        const response = await apiClientAuth.get('/admin/authority/index.php?action=getFeatures');
        console.log('All features response:', response.data);
        features.value = response.data; // Populate the list of ALL available features
    } catch (error: any) {
        console.error('Error fetching all features:', error);
        toast.error(error.response?.data?.error || 'Failed to load all features');
        features.value = []; // Ensure features is an empty array on error
    } finally {
        loadingFeatures.value = false; // Unset loading state
    }
};

// Fetch features assigned to a specific authority
const fetchAuthorityFeatures = async (authorityId: number) => {
    // Set loading state *specifically for the features list within the modal*
    loadingFeatures.value = true;
    try {
        console.log('Fetching assigned features for authority ID:', authorityId);

        // Zuerst den Array leeren, um sicherzustellen, dass keine alten Daten übrig bleiben
        selectedAuthorityFeatures.value = [];

        // NEUE API: GET mit action=getAuthorityFeatures und ID als Query-Parameter
        const response = await apiClientAuth.get(`/admin/authority/index.php?action=getAuthorityFeatures&id=${authorityId}`);
        console.log('Assigned authority features response:', response.data);

        // Das Backend gibt hier ein Array von IDs zurück, keine Objekte.
        // Stelle sicher, dass die IDs als Zahlen gespeichert werden
        // Es ist wichtig, dass die response.data ein Array von IDs ist.
        if (Array.isArray(response.data)) {
            selectedAuthorityFeatures.value = response.data.map((id: any) => Number(id));
        } else {
             console.error('API did not return an array of feature IDs:', response.data);
             selectedAuthorityFeatures.value = [];
             toast.error('Failed to load assigned features: Invalid data format.');
        }

        console.log('Selected feature IDs:', selectedAuthorityFeatures.value);
    } catch (error: any) {
        console.error('Error fetching assigned authority features:', error);
        toast.error(error.response?.data?.error || 'Failed to load assigned authority features');
         selectedAuthorityFeatures.value = []; // Ensure array is empty on error
    } finally {
        loadingFeatures.value = false; // Unset loading state
    }
};

// --- Methods ---
const openCreateAuthorityModal = () => {
    Object.assign(newAuthority, initialAuthorityData);
    isItemFormValid.value = false; // Reset form validity
    showCreateAuthorityModal.value = true;
    showPassword.value = false; // Reset password visibility
    // Reset validation state after the dialog is shown
    setTimeout(() => itemFormRef.value?.resetValidation(), 100);
};

const closeCreateAuthorityModal = () => {
    showCreateAuthorityModal.value = false;
    // Optional: Reset form data and validation on close
    Object.assign(newAuthority, initialAuthorityData);
    showPassword.value = false; // Reset password visibility
    if (itemFormRef.value) { // Check if ref is assigned
        itemFormRef.value.resetValidation();
    }
};

const createAuthority = async () => {
    // Manual form validation
    const { valid } = await itemFormRef.value.validate();
    if (!valid) {
        isItemFormValid.value = false; // Update form validity state
        toast.warning('Please fill out all required fields correctly.');
        return;
    }

    // Additional validation for admin account when enabled
    if (newAuthority.create_admin_account) {
        if (!newAuthority.admin_username || newAuthority.admin_username.length < 3) {
            toast.warning('Please enter a valid admin username (min 3 characters).');
            return;
        }
        
        if (!newAuthority.admin_email || !/.+@.+\..+/.test(newAuthority.admin_email)) {
            toast.warning('Please enter a valid admin email address.');
            return;
        }
        
        if (!newAuthority.admin_password || newAuthority.admin_password.length < 8) {
            toast.warning('Admin password must be at least 8 characters.');
            return;
        }
    }

    isItemFormValid.value = true; // Form is valid
    savingItem.value = true;

    try {
        // Prepare the data object
        const authorityData = {
            ...newAuthority,
            // Only include admin fields if creating an admin account
            ...(newAuthority.create_admin_account 
                ? { 
                    create_admin_account: true,
                    admin_username: newAuthority.admin_username,
                    admin_email: newAuthority.admin_email,
                    admin_password: newAuthority.admin_password
                } 
                : { create_admin_account: false })
        };

        // NEUE API: POST mit action=createAuthority, Daten im Body
        const response = await apiClientAuth.post('/admin/authority/index.php?action=createAuthority', authorityData);
        
        const adminAccountCreated = response.data.admin_account ? true : false;
        let successMessage = response.data.message || 'Authority created successfully.';
        
        if (adminAccountCreated) {
            successMessage += ` Administrator account created with username: ${response.data.admin_account.username}. This account has full system permissions.`;
        }
        
        toast.success(successMessage);
        closeCreateAuthorityModal();
        fetchAuthorities(); // Refresh the list
    } catch (error: any) {
        console.error('Error creating authority:', error);
        // Display specific backend error if available
        toast.error(error.response?.data?.error || 'Failed to create authority');
    } finally {
        savingItem.value = false;
    }
};

const editAuthority = (authority: Authority) => {
    // Erstelle eine Kopie, um das Original (in der Tabelle) nicht direkt zu ändern.
    // Das 'authority'-Objekt hier enthält die Daten vom Backend.
    const authorityCopy = { ...authority };

    // Explizite Umwandlung des 'active' Werts zu einem Boolean.
    // Das Backend könnte 'active' als 1, '1', 0, '0', true oder false senden.
    // Wir prüfen, ob der Wert als "aktiv" interpretiert werden soll.
    // Eine sichere Methode ist, explizit auf die Werte zu prüfen, die 'true' bedeuten.
    // Hier gehen wir davon aus, dass 1, '1' und true als aktiv gelten.
    authorityCopy.active = Boolean(authority.active);

    // Weise die bearbeitete (und korrigierte) Kopie dem reaktiven Zustand zu.
    editedAuthority.value = authorityCopy;

    isItemFormValid.value = false; // Formularvalidierung zurücksetzen
    showEditAuthorityModal.value = true; // Modal öffnen

    // Validation des Formulars zurücksetzen, nachdem das Modal sichtbar ist.
    // Dies stellt sicher, dass anfängliche Validierungsfehler (z.B. leere Felder)
    // nicht sofort angezeigt werden, wenn das Modal geöffnet wird.
    setTimeout(() => {
        if (itemFormRef.value) { // Prüfen, ob die Ref gesetzt ist
            itemFormRef.value.resetValidation();
        }
    }, 100);
};

const closeEditAuthorityModal = () => {
    showEditAuthorityModal.value = false;
    // Optional: Reset form data and validation on close
    editedAuthority.value = { ...initialAuthorityData };
     if (itemFormRef.value) { // Check if ref is assigned
         itemFormRef.value.resetValidation();
     }
};

const updateAuthority = async () => {
     // Manual form validation
    const { valid } = await itemFormRef.value.validate();
    if (!valid) {
        isItemFormValid.value = false; // Update form validity state
        toast.warning('Please fill out all required fields correctly.');
        return;
    }

    isItemFormValid.value = true; // Form is valid
    savingItem.value = true;

    try {
        // NEUE API: POST mit action=updateAuthority, ID und Daten im Body
        // Das Backend erwartet die ID im Body jetzt, nicht in der URL path segment
        const response = await apiClientAuth.post('/admin/authority/index.php?action=updateAuthority', editedAuthority.value);
        toast.success(response.data.message || 'Authority updated successfully');
        closeEditAuthorityModal();
        fetchAuthorities(); // Refresh the list
    } catch (error: any) {
        console.error('Error updating authority:', error);
         // Display specific backend error if available
        toast.error(error.response?.data?.error || 'Failed to update authority');
    } finally {
        savingItem.value = false;
    }
};

const confirmDeleteAuthority = (authority: Authority) => {
    selectedAuthority.value = authority;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedAuthority.value = { ...initialAuthorityData }; // Reset selected authority
};

const deleteAuthority = async () => {
    deletingItem.value = true;

    try {
        // NEUE API: POST mit action=deleteAuthority, ID im Body (gemäß Employee Beispiel)
        const response = await apiClientAuth.post('/admin/authority/index.php?action=deleteAuthority', { id: selectedAuthority.value.id });
        toast.success(response.data.message || 'Authority deleted successfully');
        closeDeleteModal();
        fetchAuthorities(); // Refresh the list
    } catch (error: any) {
        console.error('Error deleting authority:', error);
        // Display specific backend error if available
        toast.error(error.response?.data?.error || 'Failed to delete authority');
    } finally {
        deletingItem.value = false;
    }
};

const manageFeatures = async (authority: Authority) => {
    // Ensure authority has a valid ID
    if (!authority.id) {
        toast.error('Authority ID is required');
        return;
    }

    selectedAuthority.value = authority;
    featureSearch.value = ''; // Reset feature search

    // **Debug Step:** Log the authority ID being passed
    console.log("Opening Manage Features modal for authority ID:", authority.id);
    console.log("Calling fetchAuthorityFeatures with ID:", authority.id);

    // Fetch features assigned to *this specific* authority
    await fetchAuthorityFeatures(authority.id);

    // After fetching assigned features (and assuming fetchFeatures onMounted worked), open modal
    showFeaturesModal.value = true;
};

const closeFeaturesModal = () => {
    showFeaturesModal.value = false;
    selectedAuthority.value = { ...initialAuthorityData }; // Reset selected authority
    selectedAuthorityFeatures.value = []; // Clear selected features
    featureSearch.value = ''; // Clear feature search
};

const isFeatureEnabled = (featureId: number): boolean => {
    // Stelle sicher, dass Zahlen mit Zahlen verglichen werden
    const featureIdNum = Number(featureId);
    // **Debug Step:** Check if the ID is being looked up and against what array
    // console.log(`Checking if feature ID ${featureIdNum} is in selectedAuthorityFeatures:`, selectedAuthorityFeatures.value.includes(featureIdNum));
    return selectedAuthorityFeatures.value.includes(featureIdNum);
};

const toggleFeature = (featureId: number): void => {
    // Konvertiere zu Nummer für konsistenten Vergleich
    const featureIdNum = Number(featureId);

    if (isFeatureEnabled(featureIdNum)) {
        selectedAuthorityFeatures.value = selectedAuthorityFeatures.value.filter(
            id => id !== featureIdNum
        );
    } else {
        selectedAuthorityFeatures.value.push(featureIdNum);
    }

    console.log('Updated selected features:', selectedAuthorityFeatures.value);
};

const saveFeatures = async () => {
    savingItem.value = true;
    try {
        // Check that authority_id is not null before making the request
        if (!selectedAuthority.value.id) {
            toast.error('Authority ID is required');
            return;
        }

        const authorityId = Number(selectedAuthority.value.id);
        console.log('Saving features for authority ID:', authorityId);
        console.log('Selected feature IDs to save:', selectedAuthorityFeatures.value);

        // NEUE API: POST mit action=updateAuthorityFeatures, Authority ID und Features im Body
        const payload = {
            authority_id: authorityId,
            features: selectedAuthorityFeatures.value // Send the array of feature IDs
        };
        
        const response = await apiClientAuth.post('/admin/authority/index.php?action=updateAuthorityFeatures', payload);

        toast.success(response.data.message || 'Features updated successfully');
        closeFeaturesModal();
        // No need to refetch authorities unless feature assignment affects the main list view
    } catch (error: any) {
        console.error('Error updating features:', error);
        // Display specific backend error if available
        toast.error(error.response?.data?.error || 'Failed to update features');
    } finally {
        savingItem.value = false;
    }
};

// --- Computed ---
const filteredAuthorities = computed(() => {
    if (!search.value) return authorities.value;
    const searchTerm = search.value.toLowerCase();
    return authorities.value.filter(
        authority =>
            authority.name.toLowerCase().includes(searchTerm) ||
            authority.display_name.toLowerCase().includes(searchTerm) ||
            (authority.description?.toLowerCase().includes(searchTerm) ?? false) // Handle potential null description
    );
});

const filteredFeatures = computed(() => {
     // Only show the list if features have been loaded (features.value is not empty)
     // and either not loading OR loading but features.length > 0 (showing partial list while loading?)
     // Let's simplify: if features.value is empty and we're not actively loading, show "No features found".
     // If we are loading, the template handles the "Loading..." state.
     if (!loadingFeatures.value && features.value.length === 0) return [];

    if (!featureSearch.value) return features.value;
    const searchTerm = featureSearch.value.toLowerCase();
    return features.value.filter(
        feature =>
            feature.name.toLowerCase().includes(searchTerm) ||
            feature.code.toLowerCase().includes(searchTerm) ||
            (feature.description?.toLowerCase().includes(searchTerm) ?? false) // Handle potential null description
    );
});

// --- Lifecycle hooks ---
onMounted(async () => {
    await fetchAuthorities();
    await fetchFeatures();
    console.log('Data loading completed.');
});

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('admin/AuthorityView', () => unref(authorityHeaders) as any);
</script>

<style scoped>
/* Main Container */
.authority-admin-container {
    min-height: 90vh;
    background-color: var(--k-canvas);
    background-image:
        radial-gradient(circle at 20% 30%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
}

/* Action Button */
.action-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.3s ease;
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Info Alert */
.info-alert {
    background-color: rgba(var(--v-theme-primary-rgb), 0.08) !important;
    border-left-width: 4px !important;
}

/* Main Card */
.main-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
}

/* Card Toolbar */
.card-toolbar {
    background-color: rgba(30, 41, 59, 0.3) !important;
    border-bottom: 1px solid var(--card-border);
}

/* ID Chip */
.id-chip {
    min-width: 36px;
    justify-content: center;
}

/* Action Icons */
.action-icon {
    opacity: 0.7;
    transition: all 0.2s;
}

.action-icon:hover {
    opacity: 1;
    transform: scale(1.1);
}

/* Empty and Loading States */
.empty-state,
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
    color: var(--v-theme-text-secondary);
    text-align: center;
}

.loading-state {
    flex-direction: row;
    padding: 20px;
}

/* Dialog Styling */
.dialog-card {
    background-color: var(--k-canvas) !important;
    border: 1px solid var(--k-line);
    border-radius: 12px;
    overflow: hidden;
}

.dialog-title {
    background: linear-gradient(90deg, #1e3a8a, var(--k-accent-hover));
    color: var(--k-ink);
    padding: 16px;
}

.delete-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.2s ease;
}

.delete-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
}

/* Feature List */
.feature-list {
    max-height: 400px;
    overflow-y: auto;
}

.selected-feature {
    background-color: rgba(var(--v-theme-primary-rgb), 0.1);
}

/* Animation effects */
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

/* Responsive adjustments */
@media (max-width: 600px) {
    .main-card {
        margin-bottom: 16px;
    }
}
</style>