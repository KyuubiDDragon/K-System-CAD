<script setup lang="ts">
import { ref, onMounted, reactive, computed, unref } from 'vue';
import { useI18n } from 'vue-i18n';
import KTableToolbar from '@/components/table/KTableToolbar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
import { useRoute } from 'vue-router'; // Import useRoute if permissions are needed
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import type { Group, User, GroupMember } from '@/types/AdminMessage'; // Adjust path if needed
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar

// --- Router & Permissions ---
const route = useRoute(); // If using route meta for permissions
const { t } = useI18n();
const canEdit = computed(() => true); // Replace with route.meta check if needed
const canDelete = computed(() => true); // Replace with route.meta check if needed

// --- Component State ---
const groups = ref<Group[]>([]);
const users = ref<User[]>([]); // List of all users for selection
const loadingGroups = ref(false);
const loadingUsers = ref(false);
const savingGroup = ref(false);
const deletingGroup = ref(false);

// --- Dialog States & Data ---
const addEditDialog = ref(false);
const confirmDeleteDialog = ref(false);
const groupFormRef = ref<any>(null);
const isGroupFormValid = ref(false);
const initialFormData: Group = {
    id: 0,
    name: '',
    created_at: '',
    is_member: false,
    sort_order: 0,
    members: [],
};
const selectedGroup = reactive<Omit<Group, 'id' | 'members'> & { id: number | null }>({
    ...initialFormData,
    id: null,
}); // Use Omit to avoid conflict with selectedMembers and allow null id
const selectedMembers = ref<number[]>([]); // Store only member IDs for v-select model
const groupToDelete = ref<Group | null>(null);
const isEditing = computed(() => !!selectedGroup.id);

// --- Snackbar ---
const errorSnackbar = ref({ visible: false, message: '', color: 'error' });
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    errorSnackbar.value.message = message;
    errorSnackbar.value.color = color;
    errorSnackbar.value.visible = true;
}

// --- Table Headers ---
const groupHeaders = computed(() => [
    { title: t('adminMessages.headers.id'), key: 'id', align: 'start', sortable: true, width: '80px' },
    { title: t('adminMessages.headers.name'), key: 'name', sortable: true },
    { title: t('adminMessages.headers.members'), key: 'members', sortable: false }, // Add column for member count/info
    { title: t('adminMessages.headers.actions'), key: 'actions', sortable: false, align: 'end', width: '120px' },
]);

// --- Validation Rules ---
/**
 * Pflichtfeld-Regel.
 *
 * Der Entwurf verlangt, dass eine Meldung die Ursache benennt: "Diese
 * Dienstnummer ist bereits vergeben" statt "Ungueltige Eingabe". Fuer ein
 * fehlendes Pflichtfeld heisst das, den Namen des Feldes zu nennen - er steht
 * ohnehin als Beschriftung daneben.
 */
const requiredRule = (feld?: string) => (value: any) =>
    (value !== null && value !== undefined && String(value).trim() !== '') ||
    (feld ? `${feld} fehlt.` : 'Dieses Feld muss ausgefüllt werden.');

// --- Data Fetching ---
const fetchGroups = async () => {
    loadingGroups.value = true;
    try {
        const response = await apiClientAuth.get<Group[]>('/admin/messages?action=getGroups'); // Adjust path
        groups.value = (response.data || response.data || []).map(group => ({
            ...group,
            members: group.members || [], // Ensure members array exists
        }));
    } catch (error: any) {
        console.error('Error fetching groups:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Gruppen.', 'error');
        groups.value = [];
    } finally {
        loadingGroups.value = false;
    }
};

const fetchUsers = async () => {
    loadingUsers.value = true;
    try {
        const response = await apiClientAuth.get<User[]>('/admin/user?action=getUsers'); // Adjust path
        // Assuming User type includes 'employee' and 'username'
        users.value = response.data || response.data || [];
    } catch (error: any) {
        console.error('Error fetching users:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Benutzer.', 'error');
        users.value = [];
    } finally {
        loadingUsers.value = false;
    }
};

// --- Methods ---

// Add/Edit Dialog Logic
const openNewGroupDialog = () => {
    Object.assign(selectedGroup, { ...initialFormData, id: null }); // Reset group data
    selectedMembers.value = []; // Clear selected members
    isGroupFormValid.value = false;
    addEditDialog.value = true;
    setTimeout(() => groupFormRef.value?.resetValidation(), 100);
};

const openEditGroupDialog = (group: Group) => {
    Object.assign(selectedGroup, { ...group }); // Load group data (excluding members array)
    selectedMembers.value = group.members?.map((member: GroupMember) => member.user_id) || []; // Populate selected members
    isGroupFormValid.value = false;
    addEditDialog.value = true;
    setTimeout(() => groupFormRef.value?.resetValidation(), 100);
};

const closeAddEditDialog = () => {
    addEditDialog.value = false;
};

const saveGroup = async () => {
    if (!isGroupFormValid.value) return;
    savingGroup.value = true;

    const payload = {
        id: selectedGroup.id, // Include id if editing, null/undefined if adding
        name: selectedGroup.name,
        sort_order: selectedGroup.sort_order, // Include if needed by backend
        members: selectedMembers.value, // Send array of selected user IDs
    };

    try {
        await apiClientAuth.post(`/admin/messages?action=saveGroup`, payload); // Adjust path
        closeAddEditDialog();
        await fetchGroups(); // Refresh list
        showSnackbar(
            `Gruppe erfolgreich ${isEditing.value ? 'aktualisiert' : 'hinzugefügt'}.`,
            'success'
        );
    } catch (error: any) {
        console.error('Error saving group:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Speichern der Gruppe.', 'error');
    } finally {
        savingGroup.value = false;
    }
};

// Delete Logic
const openConfirmDeleteDialog = (group: Group) => {
    groupToDelete.value = group;
    confirmDeleteDialog.value = true;
};

const closeConfirmDeleteDialog = () => {
    confirmDeleteDialog.value = false;
    groupToDelete.value = null;
};

const proceedWithDelete = async () => {
    if (!groupToDelete.value) return;
    deletingGroup.value = true;
    try {
        await apiClientAuth.post('/admin/messages?action=deleteGroup', {
            id: groupToDelete.value.id,
        }); // Adjust path
        closeConfirmDeleteDialog();
        await fetchGroups(); // Refresh list
        showSnackbar('Gruppe erfolgreich gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting group:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen der Gruppe.', 'error');
    } finally {
        deletingGroup.value = false;
    }
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchGroups();
    fetchUsers(); // Fetch users needed for the select dropdown
});

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('admin/MessagesView', () => unref(groupHeaders) as any);
</script>

<template>
    <ErrorSnackbar v-model="errorSnackbar" />
    <v-container fluid class="pa-4">
        <v-row class="mb-4 align-center">
            <v-col cols="auto">
                <div class="d-flex align-center">
                    <v-icon icon="mdi-account-group" size="24" class="mr-2 text-primary"></v-icon>
                    <h1 class="text-h5 font-weight-medium mb-0">{{ t('messageView.manageGroups') }}</h1>
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
                    Hier können Sie Benutzergruppen erstellen und verwalten, um Nachrichten gezielt
                    an bestimmte Teams zu senden.
                </v-alert>
            </v-col>
        </v-row>

        <v-card class="main-card elevation-4">
            <v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                <v-toolbar-title class="text-h6">
                    <v-icon start size="20" class="mr-2">mdi-account-group</v-icon>
                    {{ t('messageView.manageGroups') }}
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn
                    v-if="canEdit"
                    @click="openNewGroupDialog"
                    color="primary"
                    variant="elevated"
                    prepend-icon="mdi-plus"
                    size="small"
                    class="action-button"
                >
                    {{ t('messageView.newGroup') }}
                </v-btn>
            </v-toolbar>

            <v-divider></v-divider>

            <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
            <KTableToolbar :columns="kCols" :shown="(groups || []).length" />
            <v-data-table
                :headers="kCols.visible.value"
                :items="groups"
                item-value="id"
                :loading="loadingGroups"
                hover
                density="comfortable"
            >
                <template v-slot:[`item.id`]="{ item }">
                    <v-chip size="small" label color="blue-grey" variant="tonal" class="id-chip">
                        {{ item.id }}
                    </v-chip>
                </template>

                <template v-slot:[`item.name`]="{ item }">
                    <span class="font-weight-medium">{{ item.name }}</span>
                </template>

                <template v-slot:[`item.members`]="{ item }">
                    <v-chip
                        v-if="item.members && item.members.length > 0"
                        size="small"
                        label
                        color="success"
                        variant="tonal"
                        class="member-chip"
                    >
                        <v-icon start size="x-small">mdi-account-multiple</v-icon>
                        {{ item.members.length }} Mitglied{{
                            item.members.length !== 1 ? 'er' : ''
                        }}
                    </v-chip>
                    <span v-else class="text-grey text-caption">{{ t('messageView.noMembers') }}</span>
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
                                    @click="openEditGroupDialog(item)"
                                    v-bind="props"
                                    class="action-icon"
                                >
                                    <v-icon size="small">mdi-pencil</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>

                        <v-tooltip :text="t('delete')" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canDelete"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="openConfirmDeleteDialog(item)"
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
                        <v-icon size="40" color="grey-darken-1" class="mb-2"
                            >mdi-account-group-outline</v-icon
                        >
                        <span>{{ t('messageView.noGroups') }}</span>
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
                        <span>{{ t('messageView.loadingGroups') }}</span>
                    </div>
                </template>
            </v-data-table>
        </v-card>

        <!-- Add/Edit Group Dialog -->
        <v-dialog v-model="addEditDialog" persistent max-width="700px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon
                        :icon="isEditing ? 'mdi-account-group-edit' : 'mdi-account-group-plus'"
                        class="mr-2"
                    ></v-icon>
                    {{ isEditing ? t('messageView.editGroup') : t('messageView.addGroup') }}
                </v-card-title>

                <v-form ref="groupFormRef" v-model="isGroupFormValid">
                    <v-card-text class="pa-4">
                        <v-container>
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="selectedGroup.name"
                                        label="Gruppenname"
                                        required
                                        :rules="[requiredRule('Gruppenname')]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-account-group"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-autocomplete
                                        v-model="selectedMembers"
                                        :items="users"
                                        item-title="username"
                                        item-value="id"
                                        label="Mitglieder auswählen"
                                        multiple
                                        chips
                                        closable-chips
                                        clearable
                                        variant="outlined"
                                        density="comfortable"
                                        :loading="loadingUsers"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-account-multiple-check"
                                        hint="Wählen Sie Benutzer aus, die dieser Gruppe angehören sollen"
                                        persistent-hint
                                    >
                                        <template v-slot:chip="{ props, item }">
                                            <v-chip
                                                v-bind="props"
                                                :text="item.raw.username"
                                                color="primary"
                                                variant="tonal"
                                                size="small"
                                                class="member-selection-chip"
                                            ></v-chip>
                                        </template>
                                        <template v-slot:item="{ props, item }">
                                            <v-list-item v-bind="props ">
                                                <template v-slot:prepend>
                                                    <v-avatar
                                                        size="32"
                                                        color="grey-darken-3"
                                                        class="member-avatar"
                                                    >
                                                        <span class="text-caption">{{
                                                            item.raw.username
                                                                .charAt(0)
                                                                .toUpperCase()
                                                        }}</span>
                                                    </v-avatar>
                                                </template>
                                                <v-list-item-title>{{
                                                    item.raw.username
                                                }}</v-list-item-title>
                                            </v-list-item>
                                        </template>
                                        <template v-slot:no-data>
                                            <div class="pa-4 text-center">
                                                <v-icon
                                                    icon="mdi-account-search"
                                                    size="36"
                                                    class="mb-2 text-grey"
                                                ></v-icon>
                                                <div>{{ t('messageView.noUsers') }}</div>
                                            </div>
                                        </template>
                                    </v-autocomplete>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeAddEditDialog">{{ t('cancel') }}</v-btn>
                        <v-btn
                            color="primary"
                            variant="elevated"
                            @click="saveGroup"
                            :disabled="!isGroupFormValid"
                            :loading="savingGroup"
                        >
                            {{ t('save') }}
                        </v-btn>
                    </v-card-actions>
                </v-form>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="confirmDeleteDialog" persistent max-width="500px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-delete-alert" color="error" class="mr-2"></v-icon>
                    {{ t('confirmDeleteTitle') }}
                </v-card-title>

                <v-card-text class="pt-4">
                    <p>
                        {{ t('messageView.confirmDeleteGroup', { name: groupToDelete?.name }) }}
                    </p>

                    <v-alert
                        v-if="groupToDelete?.members?.length"
                        class="mt-4"
                        border="start"
                        border-color="warning"
                        elevation="2"
                        density="compact"
                        icon="mdi-alert"
                        variant="tonal"
                        color="warning"
                    >
                        Diese Gruppe hat {{ groupToDelete?.members?.length }} Mitglied{{
                            groupToDelete?.members?.length !== 1 ? 'er' : ''
                        }}, die ihre Gruppenzugehörigkeit verlieren werden.
                    </v-alert>

                    <div class="text-caption text-medium-emphasis mt-2">
                        Diese Aktion kann nicht rückgängig gemacht werden.
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeConfirmDeleteDialog" class="mr-2"
                        >{{ t('cancel') }}</v-btn
                    >
                    <v-btn
                        color="error"
                        variant="elevated"
                        @click="proceedWithDelete"
                        :loading="deletingGroup"
                        class="delete-button"
                        prepend-icon="mdi-delete"
                    >
                        {{ t('delete') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style scoped>

/* Main Container */
.group-container {
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
}

/* Card Toolbar */
.card-toolbar {
    background-color: rgba(30, 41, 59, 0.3) !important;
    border-bottom: 1px solid var(--card-border);
}

/* Chips */
.id-chip,
.member-chip {
    min-width: 36px;
    justify-content: center;
}

.member-selection-chip {
    font-size: 0.75rem;
}

.member-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
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
</style>
