<script setup lang="ts">
import { ref, computed, onMounted, reactive, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import { useToast } from 'vue-toastification'; // Import toast
import type { PersonFile } from '@/types/Person';
import { useModulePermission } from '@/composables/useModulePermission';

// Import child components
import AuthorityAddPersonFile from '@/components/PersonFile/Authority/Add.vue'; // Adjust path
import AuthorityEditPersonFile from '@/components/PersonFile/Authority/Edit.vue'; // Adjust path
import AuthorityViewPersonFile from '@/components/PersonFile/Authority/View.vue'; // Adjust path

// --- Store & Route ---
const authStore = useAuthStore();
const route = useRoute();
const { t } = useI18n();

// Access props that may be passed directly from desktop windows
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  id?: string | number
  allPermissions?: boolean
  // Add additional props that might be passed from desktop windows
  [key: string]: any  // Allow any additional props to be passed
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  id: undefined,
  allPermissions: false
});

console.log('Route meta:', route.meta);
console.log('Props passed directly:', {
  canEdit: props.canEdit,
  canDelete: props.canDelete,
  'meta.canEdit': props.meta?.canEdit,
  'meta.canDelete': props.meta?.canDelete,
  allPermissions: props.allPermissions
});

// --- Permissions ---
// Check in multiple places: props directly, props.meta, and route.meta
// Also check for ALL_PERMISSIONS in auth store or props.allPermissions
const { hasAllPermissions } = useModulePermission();
const isAdmin = computed(() =>
  hasAllPermissions.value || props.allPermissions
);

const canEdit = computed(() => 
  isAdmin.value || 
  props.canEdit || 
  props.meta?.canEdit || 
  !!route.meta.canEdit
);

const canDelete = computed(() => 
  isAdmin.value || 
  props.canDelete || 
  props.meta?.canDelete || 
  !!route.meta.canDelete
);

const canCreate = computed(() =>
  isAdmin.value || 
  props.canCreate || 
  props.meta?.canCreate || 
  !!route.meta.canCreate
);

// --- Component State ---
const persons = ref<PersonFile[]>([]); // Holds the typed person data
const search = ref('');
const loadingPersons = ref(false);
const deleting = ref(false);

// --- Dialog States ---
const addPersonDialog = ref(false); // Controls Add component visibility/v-model
const editPersonDialog = ref(false); // Controls Edit component visibility/v-model
const viewPersonDialog = ref(false); // Controls View component visibility/v-model
const deletePersonDialog = ref(false); // Controls Delete confirmation dialog

// --- Data for Child Components & Dialogs ---
const editedPerson = ref<PersonFile | null>(null); // Data passed to Edit component
const selectedPersonToView = ref<PersonFile | null>(null); // Data passed to View component
const personToDelete = ref<PersonFile | null>(null); // Data for Delete dialog

// --- Toast ---
const toast = useToast();

// --- Table Headers ---
const personHeaders = computed(() => [
    { title: t('person.headers.name'), key: 'name', sortable: true },
    { title: t('person.headers.phoneNumber'), key: 'phonenumber', sortable: true },
    { title: t('person.headers.birthdate'), key: 'birthday', sortable: true },
    { title: t('person.headers.email'), key: 'mail', sortable: false },
    { title: t('person.headers.actions'), key: 'actions', sortable: false, align: 'end' },
]);

// --- Data Fetching & Processing ---
const fetchPersons = async () => {
    loadingPersons.value = true;
    try {
        const response = await apiClientAuth.get<any[]>('/personfile/?action=getPersons'); // Assume raw data in response.data
        const rawPersons = response.data || [];
        
        console.log('Raw persons data from API:', rawPersons);

        // Get the current user's authority from Pinia store
        const authority = authStore.user?.authority || 'default'; // Use 'default' or handle appropriately

        persons.value = rawPersons.map((entry): PersonFile => {
            // Create a base person object with standard fields
            const basePerson: Partial<PersonFile> = {
                id: entry.id,
                name: `${entry.firstname || ''} ${entry.lastname || ''}`.trim(),
                fullname: entry.fullname || '',
                firstname: entry.firstname || '',
                lastname: entry.lastname || '',
                gender: entry.gender || '',
                title: entry.title || '',
                birthplace: entry.birthplace || '',
                birthday: entry.birthday || '',
                phonenumber: entry.phonenumber || '',
                address: entry.address || '',
                idcard: entry.idcard || '',
                bankaccount: entry.bankaccount || '',
                mail: entry.mail || '',
                entry: entry.entry || '',
                licenses: entry.licenses || '',
                wanted: entry.wanted === '1' || entry.wanted === true,
                text: entry.text || '',
                is_deleted: entry.is_deleted === '1' || entry.is_deleted === true,
            };
            
            // Create a copy of the entry object to preserve all fields, including custom fields
            const personWithAllFields = { ...entry };
            
            // Ensure standard fields are properly formatted
            Object.keys(basePerson).forEach(key => {
                personWithAllFields[key] = basePerson[key];
            });
            
            // Log any fields that don't match standard fields (likely custom fields)
            const standardFields = Object.keys(basePerson);
            const allFields = Object.keys(entry);
            const customFields = allFields.filter(field => !standardFields.includes(field));
            
            if (customFields.length > 0) {
                console.log(`Person ${entry.id} has custom fields:`, customFields);
                console.log('Custom field values:', customFields.map(field => `${field}: ${entry[field]}`));
            }
            
            return personWithAllFields as PersonFile;
        });
        
        console.log('Processed persons data:', persons.value);
    } catch (error: any) {
        console.error('Error fetching persons:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Personen', 'error');
        persons.value = []; // Clear data on error
    } finally {
        loadingPersons.value = false;
    }
};

// --- Computed Properties ---
const filteredPersons = computed(() => {
    const searchTerm = search.value.trim().toLowerCase();
    if (!searchTerm) {
        return persons.value; // No search term, return all
    }
    return persons.value.filter(person => {
        // Combine relevant fields for searching
        const searchableContent = `
		${person.name}
		${person.phonenumber}
		${person.mail}
		${person.firstname}
		${person.lastname}
		${person.address}
		${person.idcard}
	  `.toLowerCase();
        return searchableContent.includes(searchTerm);
    });
});

// --- Methods ---

// Add Person Dialog/Component
const openAddPersonDialog = () => {
    addPersonDialog.value = true;
};

const onPersonAdded = () => {
    addPersonDialog.value = false; // Close the Add component
    showSnackbar('Person erfolgreich hinzugefügt.', 'success');
    fetchPersons(); // Refresh the list
};

// Edit Person Dialog/Component
const openEditPersonDialog = (person: PersonFile) => {
    editedPerson.value = { ...person }; // Pass a copy to prevent direct modification
    editPersonDialog.value = true;
};

const onPersonUpdated = () => {
    editPersonDialog.value = false; // Close the Edit component
    editedPerson.value = null;
    showSnackbar('Person erfolgreich aktualisiert.', 'success');
    fetchPersons(); // Refresh the list
};

const closeEditFile = () => {
    editPersonDialog.value = false;
    editedPerson.value = null;
};

// View Person Dialog/Component
const openViewPersonDialog = (person: PersonFile) => {
    console.log('📂 openViewPersonDialog called with:', person);
    selectedPersonToView.value = { ...person }; // Pass data to View component
    console.log('📂 Selected person to view set:', selectedPersonToView.value);
    viewPersonDialog.value = true;
    console.log('📂 viewPersonDialog.value set to:', viewPersonDialog.value);
    
    // Force Vue to update
    nextTick(() => {
        console.log('📂 After nextTick - viewPersonDialog.value:', viewPersonDialog.value);
        console.log('📂 Dialog component should be visible now');
    });
};

const closeViewFile = () => {
    viewPersonDialog.value = false;
    selectedPersonToView.value = null;
};

// Delete Person Dialog
const openDeletePersonDialog = (person: PersonFile) => {
    personToDelete.value = person;
    deletePersonDialog.value = true;
};

const closeDeleteDialog = () => {
    deletePersonDialog.value = false;
    personToDelete.value = null;
};

const confirmDeletePerson = async () => {
    if (!personToDelete.value) return;

    deleting.value = true;
    try {
        await apiClientAuth.post('/personfile/?action=deletePerson', {
            id: personToDelete.value.id,
        });
        await fetchPersons(); // Refresh list
        closeDeleteDialog(); // Close dialog
        showSnackbar('Person erfolgreich gelöscht', 'success');
    } catch (error: any) {
        console.error('Error deleting person:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen der Person', 'error');
        // Keep dialog open on error? Or close? Closing for now.
        // closeDeleteDialog();
    } finally {
        deleting.value = false;
    }
};

// --- Lifecycle Hooks ---
onMounted(async () => {
    console.log('PersonView mounted, query params:', route.query);
    console.log('PersonView all props:', props);
    console.log('PersonView props keys:', Object.keys(props));
    console.log('PersonView individual props:', {
        id: props.id,
        meta: props.meta,
        canEdit: props.canEdit,
        canDelete: props.canDelete,
        canCreate: props.canCreate,
        allPermissions: props.allPermissions
    });
    
    await fetchPersons(); // Fetch data when component mounts
    
    // Check if we need to open a specific person from query parameter or props
    const personId = route.query.id || props.id || props.meta?.id || props.meta?.personId;
    console.log('[PersonView] Looking for person ID:', personId);
    console.log('[PersonView] ID sources - route.query.id:', route.query.id, 'props.id:', props.id, 'props.meta?.id:', props.meta?.id);
    
    if (personId) {
        console.log('🔍 Found person ID:', personId);
        
        // Simple approach: try to find person immediately, if not found wait for data
        const tryOpenDialog = () => {
            const person = persons.value.find(p => p.id === Number(personId));
            if (person) {
                console.log('✅ Opening view dialog for person:', person.firstname, person.lastname);
                selectedPersonToView.value = person;
                // Force dialog to open with nextTick
                nextTick(() => {
                    viewPersonDialog.value = true;
                    console.log('📂 Dialog opened - viewPersonDialog:', viewPersonDialog.value);
                });
            } else if (!loadingPersons.value) {
                console.warn('⚠️ Person not found with ID:', personId);
                toast.warning(`Person mit ID ${personId} nicht gefunden`);
            }
        };
        
        // Try immediately
        tryOpenDialog();
        
        // If not found and still loading, wait for data
        if (!persons.value.find(p => p.id === Number(personId)) && loadingPersons.value) {
            const unwatch = watch(loadingPersons, (newValue) => {
                if (!newValue && persons.value.length > 0) {
                    unwatch(); // Stop watching
                    tryOpenDialog();
                }
            });
        }
    }
});

// Update the showSnackbar function
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}
</script>

<template>
    <v-container fluid class="pa-4">
        <v-row class="mb-4 align-center">
            <v-col cols="auto">
                <v-btn
                    v-if="!addPersonDialog && !editPersonDialog && !viewPersonDialog && canEdit"
                    @click="openAddPersonDialog"
                    color="primary"
                    variant="elevated"
                    prepend-icon="mdi-account-plus-outline"
                    class="action-button"
                    elevation="2"
                >
                    {{ t('personView.newPerson') }}
                </v-btn>
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
                    Hier werden Personen verwaltet. Sie können neue Personen hinzufügen, bestehende
                    bearbeiten oder löschen.
                </v-alert>
            </v-col>
        </v-row>

        <v-card
            v-if="!addPersonDialog && !editPersonDialog && !viewPersonDialog"
            class="main-card elevation-4"
        >
            <v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                <v-toolbar-title class="text-h6">
                    <v-icon start size="20" class="mr-2">mdi-account-group</v-icon>
                    Personenverwaltung
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-text-field
                    v-model="search"
                    label="Suche (Name, Telefon, E-Mail)"
                    prepend-inner-icon="mdi-magnify"
                    hide-details
                    density="compact"
                    variant="solo-filled"
                    flat
                    class="max-w-300 search-field"
                />
            </v-toolbar>

            <v-divider></v-divider>

            <v-data-table
                :headers="personHeaders"
                :items="filteredPersons"
                class="elevation-0"
                :search="search"
                :items-per-page="25"
                item-value="id"
                :loading="loadingPersons"
                hover
                density="comfortable"
            >
                <template v-slot:[`item.name`]="{ item }">
                    <span @click="openViewPersonDialog(item)" class="person-name-link">
                        {{ item.name }}
                    </span>
                </template>

                <template v-slot:[`item.actions`]="{ item }">
                    <div class="d-flex gap-1">
                        <v-tooltip text="Bearbeiten" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canEdit"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="openEditPersonDialog(item)"
                                    v-bind="props"
                                    class="action-icon"
                                >
                                    <v-icon size="small">mdi-pencil</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>

                        <v-tooltip text="Löschen" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canDelete"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="openDeletePersonDialog(item)"
                                    v-bind="props"
                                    class="action-icon"
                                    color="error"
                                >
                                    <v-icon size="small">mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>
                    </div>
                </template>

                <template v-slot:no-data>
                    <div class="empty-state">
                        <v-icon size="40" color="grey-darken-1" class="mb-2"
                            >mdi-account-off-outline</v-icon
                        >
                        <span>Keine Personen gefunden.</span>
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
                        <span>Lade Personendaten...</span>
                    </div>
                </template>
            </v-data-table>
        </v-card>

        <!-- Add Person Dialog Component -->
        <AuthorityAddPersonFile
            v-if="addPersonDialog"
            v-model="addPersonDialog"
            :new-person-dialog="addPersonDialog"
            :add-person-dialog="addPersonDialog"
            @person-added="onPersonAdded"
            @close="addPersonDialog = false"
        />

        <!-- Edit Person Component -->
        <AuthorityEditPersonFile
            v-if="editPersonDialog"
            v-model="editPersonDialog"
            :edit-person-dialog="editPersonDialog"
            :person-to-edit="editedPerson"
            @person-updated="onPersonUpdated"
            @close="closeEditFile"
        />

        <!-- View Person Component -->
        <AuthorityViewPersonFile
            v-if="viewPersonDialog"
            v-model="viewPersonDialog"
            :view-person-dialog="viewPersonDialog"
            :person-to-view="selectedPersonToView"
            @close="closeViewFile"
        />

        <!-- Delete Confirmation Dialog -->
        <v-dialog
            v-model="deletePersonDialog"
            max-width="500"
            persistent
            class="confirmation-dialog"
        >
            <v-card>
                <v-card-title class="text-h5 dialog-title">
                    <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                    Person löschen
                </v-card-title>

                <v-card-text class="pt-4">
                    <p>
                        Möchten Sie die Person
                        <span class="font-weight-bold"
                            >"{{ personToDelete?.firstname }} {{ personToDelete?.lastname }}"</span
                        >
                        wirklich löschen?
                    </p>
                    <div class="text-caption text-medium-emphasis mt-2">
                        Diese Aktion kann nicht rückgängig gemacht werden.
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeDeleteDialog" class="mr-2">Abbrechen</v-btn>
                    <v-btn
                        color="error"
                        variant="elevated"
                        @click="confirmDeletePerson"
                        :loading="deleting"
                        class="delete-button"
                    >
                        Löschen
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style scoped>

/* Main Container Styles */
.main-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
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

/* Table Styles */
.card-toolbar {
    background-color: rgba(30, 41, 59, 0.3) !important;
    border-bottom: 1px solid var(--card-border);
}

.max-w-300 {
    max-width: 300px;
}

.search-field {
    transition: all 0.2s ease;
}

.search-field:focus-within {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.person-name-link {
    cursor: pointer;
    color: #1976d2;
    transition: all 0.2s ease;
}

.person-name-link:hover {
    text-decoration: underline;
    color: #2196f3;
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
.confirmation-dialog :deep(.v-overlay__content) {
    border-radius: 16px;
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

/* Responsive adjustments */
@media (max-width: 600px) {
    .max-w-300 {
        max-width: 100%;
    }
}
</style>
