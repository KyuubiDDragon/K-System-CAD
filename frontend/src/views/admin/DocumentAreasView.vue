<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { apiClientAuth } from '@/api';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import type { DocArea, Role } from '@/types';

// Toast-Instanz
const toast = useToast();
const { t } = useI18n();

// State-Variablen
const areas = ref<DocArea[]>([]);
const roles = ref<Role[]>([]);
const loading = ref(false);
const savingArea = ref(false);
const savingPermissions = ref(false);
const editDialog = ref(false);
const deleteDialog = ref(false);
const permissionsDialog = ref(false);

// Form state
const editedArea = ref<DocArea>({
    id: -1,
    key: '',
    name: '',
    description: '',
    icon: 'mdi-file-document-outline',
    is_system: false,
    is_active: true,
    sort_order: 0,
});

const selectedAreaForPermissions = ref<DocArea | null>(null);
const areaPermissions = ref<any[]>([]);

// Form validation
const formValid = ref(false);
const formRules = {
    required: (v: string) => !!v || t('documentAreasView.fieldRequired'),
    keyFormat: (v: string) =>
        /^[a-z0-9_]+$/.test(v) || t('documentAreasView.keyFormatError'),
};

// Icons für die Auswahl
const availableIcons = [
    'mdi-file-document-outline',
    'mdi-folder-outline',
    'mdi-account-group',
    'mdi-school',
    'mdi-domain',
    'mdi-office-building',
    'mdi-car',
    'mdi-medical-bag',
    'mdi-fire-truck',
    'mdi-police-badge',
    'mdi-book-open-page-variant',
    'mdi-clipboard-text-outline',
];

// Computed
const systemAreas = computed(() => areas.value.filter(area => area.is_system));
const customAreas = computed(() => areas.value.filter(area => !area.is_system));

// Lifecycle hooks
onMounted(async () => {
    await fetchAreas();
    await fetchRoles();
});

// Methoden
async function fetchAreas() {
    loading.value = true;
    try {
        const response = await apiClientAuth.post('/document/?action=getAreas');
        areas.value = response.data;
    } catch (error: any) {
        console.error('Error fetching areas:', error);
        toast.error(error.response?.data?.error || t('documentAreasView.loadError'));
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
        toast.error(t('documentAreasView.rolesLoadError'));
    }
}

function openEditDialog(area: DocArea | null = null) {
    if (area) {
        editedArea.value = { ...area };
    } else {
        editedArea.value = {
            id: -1,
            key: '',
            name: '',
            description: '',
            icon: 'mdi-file-document-outline',
            is_system: false,
            is_active: true,
            sort_order: areas.value.length + 1,
        };
    }
    editDialog.value = true;
}

function openDeleteDialog(area: DocArea) {
    editedArea.value = { ...area };
    deleteDialog.value = true;
}

async function openPermissionsDialog(area: DocArea) {
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
        const response = await apiClientAuth.post('/document/?action=getAreaPermissions', {
            area_id: area.id,
        });

        // Korrigierte Verarbeitung der Antwort - Die Antwort hat ein "roles"-Feld
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
            toast.error(t('documentAreasView.unexpectedResponse'));
        }
    } catch (error: any) {
        console.error('Error loading permissions:', error);
        toast.error(t('documentAreasView.permissionsLoadError'));
    }
}

async function saveArea() {
    if (!formValid.value) return;

    savingArea.value = true;
    try {
        const action = editedArea.value.id === -1 ? 'addArea' : 'updateArea';
        await apiClientAuth.post(`/document/?action=${action}`, editedArea.value);

        toast.success(
            action === 'addArea' ? t('documentAreasView.areaCreated') : t('documentAreasView.areaUpdated')
        );
        editDialog.value = false;
        await fetchAreas();
    } catch (error: any) {
        console.error('Error saving area:', error);
        toast.error(error.response?.data?.error || t('documentAreasView.saveError'));
    } finally {
        savingArea.value = false;
    }
}

async function deleteArea() {
    try {
        await apiClientAuth.post('/document/?action=deleteArea', {
            id: editedArea.value.id,
        });

        toast.success(t('documentAreasView.areaDeleted'));
        deleteDialog.value = false;
        await fetchAreas();
    } catch (error: any) {
        console.error('Error deleting area:', error);
        toast.error(error.response?.data?.error || t('documentAreasView.deleteError'));
    }
}

async function savePermissions() {
    savingPermissions.value = true;
    try {
        await apiClientAuth.post('/document/?action=saveAreaPermissions', {
            area_id: selectedAreaForPermissions.value?.id,
            permissions: areaPermissions.value,
        });

        toast.success(t('documentAreasView.permissionsUpdated'));
        permissionsDialog.value = false;
    } catch (error: any) {
        console.error('Error saving permissions:', error);
        toast.error(error.response?.data?.error || t('documentAreasView.permissionsSaveError'));
    } finally {
        savingPermissions.value = false;
    }
}
</script>

<template>
    <div class="document-areas-admin">
        <v-container fluid>
            <v-card class="mb-4">
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>{{ t('documentAreasView.title') }}</span>
                    <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        @click="openEditDialog()"
                        size="small"
                    >
                        {{ t('documentAreasView.newAreaButton') }}
                    </v-btn>
                </v-card-title>

                <v-card-text>
                    <p class="text-body-2 mb-4">
                        {{ t('documentAreasView.description') }}
                    </p>

                    <v-progress-linear
                        v-if="loading"
                        indeterminate
                        color="primary"
                    ></v-progress-linear>

                    <!-- Systembereiche -->
                    <v-expansion-panels variant="accordion" class="mb-4">
                        <v-expansion-panel>
                            <v-expansion-panel-title>
                                <div class="d-flex align-center">
                                    <v-icon icon="mdi-lock" class="mr-2" color="warning"></v-icon>
                                    {{ t('documentAreasView.systemAreas', { count: systemAreas.length }) }}
                                </div>
                            </v-expansion-panel-title>
                            <v-expansion-panel-text>
                                <v-table>
                                    <thead>
                                        <tr>
                                            <th>{{ t('documentAreasView.sortOrder') }}</th>
                                            <th>{{ t('documentAreasView.icon') }}</th>
                                            <th>{{ t('documentAreasView.name') }}</th>
                                            <th>{{ t('documentAreasView.key') }}</th>
                                            <th>{{ t('documentAreasView.description') }}</th>
                                            <th>{{ t('documentAreasView.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="area in systemAreas" :key="area.id">
                                            <td>
                                                <v-chip size="small" color="primary" variant="outlined">
                                                    {{ area.sort_order }}
                                                </v-chip>
                                            </td>
                                            <td><v-icon :icon="area.icon"></v-icon></td>
                                            <td>{{ area.name }}</td>
                                            <td>
                                                <code>{{ area.key }}</code>
                                            </td>
                                            <td>{{ area.description || '-' }}</td>
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
                                                >
                                                    <v-icon icon="mdi-shield-account"></v-icon>
                                                </v-btn>
                                            </td>
                                        </tr>
                                    </tbody>
                                </v-table>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>

                    <!-- Benutzerdefinierte Bereiche -->
                    <v-card variant="outlined" class="mb-4">
                        <v-card-title>
                            <div class="d-flex align-center">
                                <v-icon icon="mdi-folder-multiple-outline" class="mr-2"></v-icon>
                                {{ t('documentAreasView.customAreas', { count: customAreas.length }) }}
                            </div>
                        </v-card-title>
                        <v-card-text>
                            <v-table v-if="customAreas.length > 0">
                                <thead>
                                    <tr>
                                        <th>{{ t('documentAreasView.sortOrder') }}</th>
                                        <th>Icon</th>
                                        <th>Name</th>
                                        <th>Schlüssel</th>
                                        <th>Beschreibung</th>
                                        <th>{{ t('documentAreasView.status') }}</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="area in customAreas" :key="area.id">
                                        <td>
                                            <v-chip size="small" color="primary" variant="outlined">
                                                {{ area.sort_order }}
                                            </v-chip>
                                        </td>
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
                                                {{ area.is_active ? t('documentAreasView.active') : t('documentAreasView.inactive') }}
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
                                    icon="mdi-folder-off-outline"
                                    size="48"
                                    color="grey"
                                ></v-icon>
                                <p class="mt-2 text-grey">
                                    {{ t('documentAreasView.noCustomAreas') }}
                                </p>
                                <v-btn
                                    color="primary"
                                    prepend-icon="mdi-plus"
                                    @click="openEditDialog()"
                                    size="small"
                                    class="mt-2"
                                >
                                    {{ t('documentAreasView.createArea') }}
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
                        editedArea.id === -1 ? t('documentAreasView.newArea') : t('documentAreasView.editArea')
                    }}</span>
                </v-card-title>
                <v-form v-model="formValid">
                    <v-card-text>
                        <v-row>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="editedArea.name"
                                    :label="t('documentAreasView.name')"
                                    :rules="[formRules.required]"
                                    required
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" v-if="editedArea.id === -1">
                                <v-text-field
                                    v-model="editedArea.key"
                                    :label="t('documentAreasView.keyLabel')"
                                    :rules="[formRules.required, formRules.keyFormat]"
                                    required
                                    :hint="t('documentAreasView.keyHint')"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12">
                                <v-textarea
                                    v-model="editedArea.description"
                                    :label="t('documentAreasView.description')"
                                    rows="3"
                                ></v-textarea>
                            </v-col>

                            <v-col cols="12">
                                <v-select
                                    v-model="editedArea.icon"
                                    :label="t('documentAreasView.icon')"
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
                                <v-text-field
                                    v-model.number="editedArea.sort_order"
                                    :label="t('documentAreasView.sortOrder')"
                                    type="number"
                                    :hint="t('documentAreasView.sortOrderHint')"
                                    persistent-hint
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" v-if="!editedArea.is_system">
                                <v-switch
                                    v-model="editedArea.is_active"
                                    :label="t('documentAreasView.active')"
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
                <v-card-title class="text-h5"> {{ t('documentAreasView.deleteTitle') }} </v-card-title>
                <v-card-text>
                    {{ t('documentAreasView.deleteConfirm', { name: editedArea.name }) }}
                    <p class="text-red mt-2" v-if="editedArea.is_system">
                        {{ t('documentAreasView.systemAreasCannotDelete') }}
                    </p>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="primary" text @click="deleteDialog = false"> {{ t('cancel') }} </v-btn>
                    <v-btn color="error" text @click="deleteArea" :disabled="editedArea.is_system">
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
                        >{{ t('documentAreasView.permissionsTitle', { name: selectedAreaForPermissions?.name }) }}</span
                    >
                </v-card-title>
                <v-card-text>
                    <p class="text-body-2 mb-4">
                        {{ t('documentAreasView.permissionsDescription') }}
                    </p>

                    <v-table>
                        <thead>
                            <tr>
                                <th>{{ t('documentAreasView.role') }}</th>
                                <th class="text-center">{{ t('documentAreasView.read') }}</th>
                                <th class="text-center">{{ t('documentAreasView.write') }}</th>
                                <th class="text-center">{{ t('documentAreasView.delete') }}</th>
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
.document-areas-admin {
    min-height: 80vh;
}
</style>
