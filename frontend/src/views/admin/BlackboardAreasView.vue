<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiClientAuth } from '@/api';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import type { BlackboardArea, Role } from '@/types';

// Toast-Instanz
const toast = useToast();
const { t } = useI18n();

// State-Variablen
const areas = ref<BlackboardArea[]>([]);
const roles = ref<Role[]>([]);
const loading = ref(false);
const savingArea = ref(false);
const savingPermissions = ref(false);
const editDialog = ref(false);
const deleteDialog = ref(false);
const permissionsDialog = ref(false);

// Form state
const editedArea = ref<BlackboardArea>({
    id: -1,
    authority_id: 0,
    key: '',
    name: '',
    description: '',
    icon: 'mdi-bulletin-board',
    is_active: true,
    sort_order: 0,
    created_at: '',
    updated_at: ''
});

const selectedAreaForPermissions = ref<BlackboardArea | null>(null);
const areaPermissions = ref<any[]>([]);

// Form validation
const formValid = ref(false);
const formRules = {
    required: (v: string) => !!v || t('blackboardAreasView.fieldRequired'),
    keyFormat: (v: string) =>
        /^[a-z0-9_]+$/.test(v) || t('blackboardAreasView.keyFormatError'),
};

// Icons für die Auswahl
const availableIcons = [
    'mdi-bulletin-board',
    'mdi-message-text-outline',
    'mdi-information-outline',
    'mdi-calendar-clock',
    'mdi-wrench',
    'mdi-fire-truck',
    'mdi-police-badge',
    'mdi-ambulance',
    'mdi-school',
    'mdi-office-building',
    'mdi-email-outline',
    'mdi-clipboard-text-outline',
];

// Lifecycle hooks
onMounted(async () => {
    await fetchAreas();
    await fetchRoles();
});

// Methoden
async function fetchAreas() {
    loading.value = true;
    try {
        const response = await apiClientAuth.post('/blackboard/?action=getAreas');
        areas.value = response.data;
    } catch (error: any) {
        console.error('Error fetching areas:', error);
        toast.error(error.response?.data?.error || t('blackboardAreasView.loadError'));
    } finally {
        loading.value = false;
    }
}

async function fetchRoles() {
    try {
        const response = await apiClientAuth.get('/admin/roles?action=getRoles');
        roles.value = response.data;
    } catch (error: any) {
        console.error('Error fetching roles:', error);
        toast.error(t('blackboardAreasView.rolesLoadError'));
    }
}

function openEditDialog(area: BlackboardArea | null = null) {
    if (area) {
        editedArea.value = { ...area };
    } else {
        editedArea.value = {
            id: -1,
            authority_id: 0,
            key: '',
            name: '',
            description: '',
            icon: 'mdi-bulletin-board',
            is_active: true,
            sort_order: areas.value.length + 1,
            created_at: '',
            updated_at: ''
        };
    }
    editDialog.value = true;
}

function openDeleteDialog(area: BlackboardArea) {
    editedArea.value = { ...area };
    deleteDialog.value = true;
}

async function openPermissionsDialog(area: BlackboardArea) {
    selectedAreaForPermissions.value = { ...area };
    permissionsDialog.value = true;

    // Berechtigungen für alle Rollen initialisieren
    areaPermissions.value = roles.value.map(role => {
        return {
            role_id: role.id,
            role_name: role.name,
            can_read: false,
            can_write: false,
            can_delete: false,
        };
    });

    // Bestehende Berechtigungen abrufen
    try {
        const response = await apiClientAuth.post('/blackboard/?action=getAreaPermissions', {
            area_id: area.id,
        });

        // Verarbeitung der Antwort - Die Antwort hat ein "roles"-Feld
        if (response.data && response.data.roles) {
            const rolesWithPermissions = response.data.roles;

            // Bestehende Berechtigungen in die Liste einarbeiten
            rolesWithPermissions.forEach((role: any) => {
                const index = areaPermissions.value.findIndex(p => p.role_id === role.id);
                if (index !== -1 && role.permissions) {
                    areaPermissions.value[index].can_read = !!role.permissions.can_read;
                    areaPermissions.value[index].can_write = !!role.permissions.can_write;
                    areaPermissions.value[index].can_delete = !!role.permissions.can_delete;
                }
            });
        } else {
            console.error('Unerwartetes Antwortformat:', response.data);
            toast.error(t('blackboardAreasView.unexpectedResponse'));
        }
    } catch (error: any) {
        console.error('Error loading permissions:', error);
        toast.error(t('blackboardAreasView.permissionsLoadError'));
    }
}

async function saveArea() {
    if (!formValid.value) return;

    savingArea.value = true;
    try {
        const action = editedArea.value.id === -1 ? 'addArea' : 'updateArea';
        await apiClientAuth.post(`/blackboard/?action=${action}`, editedArea.value);

        toast.success(
            action === 'addArea' ? t('blackboardAreasView.areaCreated') : t('blackboardAreasView.areaUpdated')
        );
        editDialog.value = false;
        await fetchAreas();
    } catch (error: any) {
        console.error('Error saving area:', error);
        toast.error(error.response?.data?.error || t('blackboardAreasView.saveError'));
    } finally {
        savingArea.value = false;
    }
}

async function deleteArea() {
    try {
        await apiClientAuth.post('/blackboard/?action=deleteArea', {
            id: editedArea.value.id,
        });

        toast.success(t('blackboardAreasView.areaDeleted'));
        deleteDialog.value = false;
        await fetchAreas();
    } catch (error: any) {
        console.error('Error deleting area:', error);
        toast.error(error.response?.data?.error || t('blackboardAreasView.deleteError'));
    }
}

async function savePermissions() {
    savingPermissions.value = true;
    try {
        await apiClientAuth.post('/blackboard/?action=saveAreaPermissions', {
            area_id: selectedAreaForPermissions.value?.id,
            permissions: areaPermissions.value,
        });

        toast.success(t('blackboardAreasView.permissionsUpdated'));
        permissionsDialog.value = false;
    } catch (error: any) {
        console.error('Error saving permissions:', error);
        toast.error(error.response?.data?.error || t('blackboardAreasView.permissionsSaveError'));
    } finally {
        savingPermissions.value = false;
    }
}
</script>

<template>
    <div class="blackboard-areas-admin">
        <v-container fluid>
            <v-card class="mb-4">
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>{{ t('blackboardAreasView.title') }}</span>
                    <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        @click="openEditDialog()"
                        size="small"
                    >
                        {{ t('blackboardAreasView.newAreaButton') }}
                    </v-btn>
                </v-card-title>

                <v-card-text>
                    <p class="text-body-2 mb-4">
                        {{ t('blackboardAreasView.description') }}
                    </p>

                    <v-progress-linear
                        v-if="loading"
                        indeterminate
                        color="primary"
                    ></v-progress-linear>

                    <!-- Bereiche-Tabelle -->
                    <v-card variant="outlined" class="mb-4">
                        <v-card-title>
                            <div class="d-flex align-center">
                                <v-icon icon="mdi-bulletin-board" class="mr-2"></v-icon>
                                {{ t('blackboardAreasView.customAreas', { count: areas.length }) }}
                            </div>
                        </v-card-title>
                        <v-card-text>
                            <v-table v-if="areas.length > 0">
                                <thead>
                                    <tr>
                                        <th>{{ t('blackboardAreasView.icon') }}</th>
                                        <th>{{ t('blackboardAreasView.name') }}</th>
                                        <th>{{ t('blackboardAreasView.key') }}</th>
                                        <th>{{ t('blackboardAreasView.description') }}</th>
                                        <th>{{ t('blackboardAreasView.status') }}</th>
                                        <th>{{ t('blackboardAreasView.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="area in areas" :key="area.id">
                                        <td><v-icon :icon="area.icon"></v-icon></td>
                                        <td>{{ area.name }}</td>
                                        <td>
                                            <code>{{ area.key }}</code>
                                        </td>
                                        <td>{{ area.description || '-' }}</td>
                                        <td>
                                            <v-chip
                                                :color="area.is_active ? 'success' : 'error'"
                                                size="small"
                                                variant="outlined"
                                            >
                                                {{ area.is_active ? t('blackboardAreasView.active') : t('blackboardAreasView.inactive') }}
                                            </v-chip>
                                        </td>
                                        <td>
                                            <v-btn
                                                icon
                                                size="small"
                                                color="primary"
                                                variant="text"
                                                @click="openEditDialog(area)"
                                                class="mr-1"
                                            >
                                                <v-icon icon="mdi-pencil-outline"></v-icon>
                                            </v-btn>
                                            <v-btn
                                                icon
                                                size="small"
                                                color="info"
                                                variant="text"
                                                @click="openPermissionsDialog(area)"
                                                class="mr-1"
                                            >
                                                <v-icon icon="mdi-shield-account"></v-icon>
                                            </v-btn>
                                            <v-btn
                                                icon
                                                size="small"
                                                color="error"
                                                variant="text"
                                                @click="openDeleteDialog(area)"
                                            >
                                                <v-icon icon="mdi-delete-outline"></v-icon>
                                            </v-btn>
                                        </td>
                                    </tr>
                                </tbody>
                            </v-table>

                            <div v-else class="text-center py-4">
                                <v-icon
                                    icon="mdi-bulletin-board"
                                    size="48"
                                    color="grey"
                                ></v-icon>
                                <p class="mt-2 text-grey">
                                    {{ t('blackboardAreasView.noCustomAreas') }}
                                </p>
                                <v-btn
                                    color="primary"
                                    prepend-icon="mdi-plus"
                                    @click="openEditDialog()"
                                    size="small"
                                    class="mt-2"
                                >
                                    {{ t('blackboardAreasView.createArea') }}
                                </v-btn>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-card-text>
            </v-card>
        </v-container>

        <!-- Bearbeiten/Erstellen Dialog -->
        <v-dialog v-model="editDialog" max-width="600px" persistent>
            <v-card>
                <v-card-title>
                    <span class="text-h5">{{
                        editedArea.id === -1 ? t('blackboardAreasView.newArea') : t('blackboardAreasView.editArea')
                    }}</span>
                </v-card-title>
                <v-form v-model="formValid">
                    <v-card-text>
                        <v-row>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="editedArea.name"
                                    :label="t('blackboardAreasView.name')"
                                    :rules="[formRules.required]"
                                    required
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" v-if="editedArea.id === -1">
                                <v-text-field
                                    v-model="editedArea.key"
                                    :label="t('blackboardAreasView.keyLabel')"
                                    :rules="[formRules.required, formRules.keyFormat]"
                                    required
                                    :hint="t('blackboardAreasView.keyHint')"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12">
                                <v-textarea
                                    v-model="editedArea.description"
                                    :label="t('blackboardAreasView.description')"
                                    rows="3"
                                ></v-textarea>
                            </v-col>

                            <v-col cols="12">
                                <v-select
                                    v-model="editedArea.icon"
                                    :label="t('blackboardAreasView.icon')"
                                    :items="availableIcons"
                                    item-title="icon"
                                    item-value="value"
                                >
                                    <template v-slot:selection="{ item }">
                                        <v-icon :icon="editedArea.icon" class="mr-2"></v-icon>
                                        {{ editedArea.icon }}
                                    </template>
                                    <template v-slot:item="{ item, props }">
                                        <v-list-item v-bind="props">
                                            <template v-slot:prepend>
                                                <v-icon :icon="item.raw" class="mr-2"></v-icon>
                                            </template>
                                            {{ item.raw }}
                                        </v-list-item>
                                    </template>
                                </v-select>
                            </v-col>

                            <v-col cols="12">
                                <v-switch
                                    v-model="editedArea.is_active"
                                    :label="t('blackboardAreasView.active')"
                                    color="success"
                                ></v-switch>
                            </v-col>
                        </v-row>
                    </v-card-text>

                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn color="error" text @click="editDialog = false"> {{ t('cancel') }} </v-btn>
                        <v-btn
                            color="success"
                            text
                            @click="saveArea"
                            :loading="savingArea"
                            :disabled="!formValid"
                        >
                            {{ t('save') }}
                        </v-btn>
                    </v-card-actions>
                </v-form>
            </v-card>
        </v-dialog>

        <!-- Löschen Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400px">
            <v-card>
                <v-card-title class="text-h5"> {{ t('blackboardAreasView.deleteTitle') }} </v-card-title>
                <v-card-text>
                    {{ t('blackboardAreasView.deleteConfirm', { name: editedArea.name }) }}
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" text @click="deleteDialog = false"> {{ t('cancel') }} </v-btn>
                    <v-btn color="error" text @click="deleteArea">
                        {{ t('delete') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Berechtigungen Dialog -->
        <v-dialog v-model="permissionsDialog" max-width="800px" persistent>
            <v-card>
                <v-card-title>
                    <span class="text-h5"
                        >{{ t('blackboardAreasView.permissionsTitle', { name: selectedAreaForPermissions?.name }) }}</span
                    >
                </v-card-title>
                <v-card-text>
                    <p class="text-body-2 mb-4">
                        {{ t('blackboardAreasView.permissionsDescription') }}
                    </p>

                    <v-table>
                        <thead>
                            <tr>
                                <th>{{ t('blackboardAreasView.role') }}</th>
                                <th class="text-center">{{ t('blackboardAreasView.read') }}</th>
                                <th class="text-center">{{ t('blackboardAreasView.write') }}</th>
                                <th class="text-center">{{ t('blackboardAreasView.delete') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(permission, index) in areaPermissions" :key="index">
                                <td>{{ permission.role_name }}</td>
                                <td class="text-center">
                                    <v-checkbox
                                        v-model="permission.can_read"
                                        hide-details
                                        density="compact"
                                    ></v-checkbox>
                                </td>
                                <td class="text-center">
                                    <v-checkbox
                                        v-model="permission.can_write"
                                        hide-details
                                        density="compact"
                                        :disabled="!permission.can_read"
                                    ></v-checkbox>
                                </td>
                                <td class="text-center">
                                    <v-checkbox
                                        v-model="permission.can_delete"
                                        hide-details
                                        density="compact"
                                        :disabled="!permission.can_read || !permission.can_write"
                                    ></v-checkbox>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="error" text @click="permissionsDialog = false"> {{ t('cancel') }} </v-btn>
                    <v-btn
                        color="success"
                        text
                        @click="savePermissions"
                        :loading="savingPermissions"
                    >
                        {{ t('save') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<style scoped>
.blackboard-areas-admin {
    min-height: 80vh;
}
</style>
