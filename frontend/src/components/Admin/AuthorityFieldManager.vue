<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useModulePermission } from '@/composables/useModulePermission';
import AuthorityFieldsService, {
    type AuthorityField,
    type FieldOption,
} from '@/services/AuthorityFieldsService';
import type { Authority } from '@/types/Authority';
import { authorityApi } from '@/api';
import { useAuthStore } from '@/stores/auth';

const fields = ref<AuthorityField[]>([]);
const authorities = ref<Authority[]>([]);
const loading = ref(true);
const showAddDialog = ref(false);
const showDeleteDialog = ref(false);
const editMode = ref(false);
const selectedAuthority = ref<number | null>(null);

// Get auth store for checking permissions
const authStore = useAuthStore();
const { t } = useI18n();
const userPermissions = computed(() => authStore.user?.permissions || []);
const userAuthority = computed(() => authStore.user?.authority || null);
const userAuthorityId = computed(() => {
    if (authStore.user?.authority_id) return authStore.user.authority_id;
    if (typeof authStore.user?.authority === 'string') {
        const authorityObj = authorities.value.find(a => a.name === authStore.user?.authority);
        return authorityObj?.id || null;
    }
    return null;
});

// Check if user is system admin
const { hasModulePermission } = useModulePermission();
const isSystemAdmin = computed(() => {
    return hasModulePermission('system', 'ADMIN');
});

// Dialog-Formular-Daten
const fieldForm = ref<Partial<AuthorityField>>({
    authority_id: null,
    field_name: '',
    display_name: '',
    field_type: 'text',
    required: false,
    options: null,
    display_order: 0,
});

// Ausgewähltes Feld für Bearbeitung/Löschen
const selectedField = ref<AuthorityField | null>(null);

// Optionen für Field Type Select
const fieldTypeOptions = [
    { title: t('authorityFieldManager.fieldTypes.text'), value: 'text' },
    { title: t('authorityFieldManager.fieldTypes.number'), value: 'number' },
    { title: t('authorityFieldManager.fieldTypes.date'), value: 'date' },
    { title: t('authorityFieldManager.fieldTypes.boolean'), value: 'boolean' },
    { title: t('authorityFieldManager.fieldTypes.select'), value: 'select' },
    { title: t('authorityFieldManager.fieldTypes.multiselect'), value: 'multiselect' },
    { title: t('authorityFieldManager.fieldTypes.textarea'), value: 'textarea' },
];

// Datentabellen Kopfzeilen (Base headers)
const baseHeaders = [
    { title: 'ID', key: 'id', sortable: true },
    { title: t('authorityFieldManager.fieldName'), key: 'field_name', sortable: true },
    { title: t('authorityFieldManager.displayName'), key: 'display_name', sortable: true },
    { title: t('authorityFieldManager.fieldType'), key: 'field_type', sortable: true },
    { title: t('authorityFieldManager.required'), key: 'required', sortable: true },
    { title: t('authorityFieldManager.displayOrder'), key: 'display_order', sortable: true },
    { title: t('authorityFieldManager.actions'), key: 'actions', sortable: false },
];

// Authority column to insert for admins
const authorityColumn = { title: t('authorityFieldManager.authorityColumn'), key: 'authority_name', sortable: true };

// Computed table headers that include or exclude the authority column
const headers = computed(() => {
    if (isSystemAdmin.value) {
        // Insert authority column after ID column for system admins
        return [baseHeaders[0], authorityColumn, ...baseHeaders.slice(1)];
    }
    // For non-admin users, just use base headers without authority column
    return baseHeaders;
});

// Custom sort option to make authority column unsortable for non-admins
const sortOptions = computed(() => {
    if (!isSystemAdmin.value) {
        return { authority_name: false }; // Disable sorting for authority column
    }
    return {}; // Default sorting behavior for admins
});

// Optionen für das Feld (nur für Typ 'select')
const fieldOptions = ref<FieldOption[]>([]);

// Computed props für Dialog-Titel
const dialogTitle = computed(() =>
    editMode.value ? t('authorityFieldManager.editField') : t('authorityFieldManager.addField')
);

// Laden der Daten beim Initialisieren
onMounted(async () => {
    await Promise.all([loadAuthorities(), loadFields()]);
});

// Felder laden basierend auf Benutzerberechtigungen
async function loadFields() {
    try {
        loading.value = true;

        if (isSystemAdmin.value) {
            // System admins can see all fields from all authorities
            fields.value = await AuthorityFieldsService.getAllFields();
        } else {
            // Regular users only see fields for their own authority
            const authorityId = userAuthorityId.value;
            if (authorityId) {
                fields.value = await AuthorityFieldsService.getFieldsForAuthority(authorityId);
                selectedAuthority.value = authorityId; // Set selected authority to user's authority
            } else {
                fields.value = await AuthorityFieldsService.getFieldsForCurrentAuthority();
            }
        }
    } catch (error) {
        console.error('Fehler beim Laden der Felder:', error);
    } finally {
        loading.value = false;
    }
}

// Alle verfügbaren Behörden laden (für Admins) oder nur die eigene Behörde für normale Benutzer
async function loadAuthorities() {
    try {
        if (isSystemAdmin.value) {
            // Load all authorities for system admins
            const response = await authorityApi.getAuthorities();
            authorities.value = response.data;
        } else {
            // For regular users, just get their own authority
            const response = await authorityApi.getAuthority(userAuthorityId.value as number);
            const currentAuthority = response.data;
            if (currentAuthority) {
                authorities.value = [currentAuthority];
                selectedAuthority.value = currentAuthority.id;
            }
        }
    } catch (error) {
        console.error('Fehler beim Laden der Behörden:', error);
    }
}

// Felder für eine bestimmte Behörde laden
async function loadFieldsForAuthority() {
    if (!selectedAuthority.value) return;

    try {
        loading.value = true;
        fields.value = await AuthorityFieldsService.getFieldsForAuthority(selectedAuthority.value);
    } catch (error) {
        console.error('Fehler beim Laden der Felder für Behörde:', error);
    } finally {
        loading.value = false;
    }
}

// Dialog zum Erstellen eines neuen Feldes öffnen
function openAddDialog() {
    editMode.value = false;

    // Create field form with basic data
    fieldForm.value = {
        field_name: '',
        display_name: '',
        field_type: 'text',
        required: false,
        options: null,
        display_order: 0,
    };

    // Only add authority_id for system admins
    if (isSystemAdmin.value) {
        fieldForm.value.authority_id = selectedAuthority.value;
    }

    fieldOptions.value = [];
    showAddDialog.value = true;
}

// Dialog zum Bearbeiten eines Feldes öffnen
function openEditDialog(field: AuthorityField) {
    editMode.value = true;
    selectedField.value = { ...field };
    fieldForm.value = { ...field };

    // Options verarbeiten, wenn vorhanden
    if (field.options) {
        fieldOptions.value = [...field.options];
    } else {
        fieldOptions.value = [];
    }

    showAddDialog.value = true;
}

// Dialog zum Löschen eines Feldes öffnen
function openDeleteDialog(field: AuthorityField) {
    selectedField.value = field;
    showDeleteDialog.value = true;
}

// Feld hinzufügen oder aktualisieren
async function saveField() {
    try {
        // Optionen zum Formular hinzufügen, wenn es sich um ein Select-Feld oder Multiselect-Feld handelt
        if (
            (fieldForm.value.field_type === 'select' ||
                fieldForm.value.field_type === 'multiselect') &&
            fieldOptions.value.length > 0
        ) {
            fieldForm.value.options = fieldOptions.value;
        } else {
            fieldForm.value.options = null;
        }

        if (editMode.value && selectedField.value) {
            // When editing, only include authority_id if the user is system admin
            const fieldData: any = { ...fieldForm.value };
            if (!isSystemAdmin.value) {
                delete fieldData.authority_id;
            }

            // Feld aktualisieren
            await AuthorityFieldsService.updateField({
                id: selectedField.value.id,
                ...fieldData,
            });
        } else {
            // When creating, only include authority_id if the user is system admin
            const fieldData: any = { ...fieldForm.value };
            if (!isSystemAdmin.value) {
                delete fieldData.authority_id;
            }

            // Neues Feld erstellen
            await AuthorityFieldsService.addField(fieldData);
        }

        // Dialog schließen und Daten neu laden
        showAddDialog.value = false;
        await loadFields();
    } catch (error) {
        console.error('Fehler beim Speichern des Feldes:', error);
    }
}

// Feld löschen
async function deleteField() {
    if (!selectedField.value) return;

    try {
        await AuthorityFieldsService.deleteField(selectedField.value.id);
        showDeleteDialog.value = false;
        await loadFields();
    } catch (error) {
        console.error('Fehler beim Löschen des Feldes:', error);
    }
}

// Option zum Formular hinzufügen
function addOption() {
    fieldOptions.value.push({ text: '', value: '' });
}

// Option aus dem Formular entfernen
function removeOption(index: number) {
    fieldOptions.value.splice(index, 1);
}

// Feldtyp-Namen für die Anzeige in der Tabelle
function getFieldTypeName(type: string): string {
    const option = fieldTypeOptions.find(option => option.value === type);
    return option ? option.title : type;
}
</script>

<template>
    <v-card class="authority-field-manager">
        <v-card-title class="d-flex align-center">
            <span>{{ t('authorityFieldManager.manageTitle') }}</span>
            <v-spacer></v-spacer>

            <!-- Filter für Behörden - nur für System Admins sichtbar -->
            <v-select
                v-if="isSystemAdmin"
                v-model="selectedAuthority"
                :items="authorities"
                item-title="name"
                item-value="id"
                :label="t('authorityFieldManager.filterByAuthority')"
                hide-details
                variant="outlined"
                density="compact"
                class="ml-4 authority-filter"
                style="max-width: 200px"
                @update:model-value="loadFieldsForAuthority"
                clearable
            >
                <template v-slot:prepend>
                    <v-icon size="small">mdi-filter</v-icon>
                </template>
            </v-select>

            <v-btn color="primary" class="ml-4" prepend-icon="mdi-plus" @click="openAddDialog">
                {{ t('authorityFieldManager.newField') }}
            </v-btn>
        </v-card-title>

        <v-card-text>
            <!-- Felder-Tabelle -->
            <v-data-table
                :headers="headers"
                :items="fields"
                :loading="loading"
                :sort-options="sortOptions"
                class="elevation-1"
            >
                <template v-slot:item.field_type="{ item }">
                    {{ getFieldTypeName(item.raw?.field_type || item.field_type) }}
                </template>

                <template v-slot:item.required="{ item }">
                    <v-icon
                        :color="item.raw?.required || item.required ? 'success' : 'grey'"
                        :icon="
                            item.raw?.required || item.required ? 'mdi-check-circle' : 'mdi-cancel'
                        "
                    ></v-icon>
                </template>

                <template v-slot:item.actions="{ item }">
                    <div class="d-flex justify-end">
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            color="primary"
                            @click="openEditDialog(item.raw || item)"
                        >
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>

                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            color="error"
                            @click="openDeleteDialog(item.raw || item)"
                        >
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </div>
                </template>
            </v-data-table>
        </v-card-text>

        <!-- Dialog: Feld hinzufügen/bearbeiten -->
        <v-dialog v-model="showAddDialog" max-width="700px">
            <v-card>
                <v-card-title>
                    {{ dialogTitle }}
                </v-card-title>

                <v-card-text>
                    <v-form ref="form">
                        <v-row>
                            <!-- Authority selection for admins -->
                            <v-col cols="12" md="6" v-if="isSystemAdmin">
                                <v-select
                                    v-model="fieldForm.authority_id"
                                    :items="authorities"
                                    item-title="name"
                                    item-value="id"
                                    :label="t('authorityFieldManager.authority')"
                                    required
                                    :rules="[v => !!v || t('authorityFieldManager.required')]"
                                    variant="outlined"
                                    density="comfortable"
                                ></v-select>
                            </v-col>

                            <!-- Field type selection - full width when authority not shown -->
                            <v-col cols="12" :md="isSystemAdmin ? 6 : 12">
                                <v-select
                                    v-model="fieldForm.field_type"
                                    :items="fieldTypeOptions"
                                    item-title="title"
                                    item-value="value"
                                    :label="t('authorityFieldManager.fieldType')"
                                    required
                                    :rules="[v => !!v || t('authorityFieldManager.required')]"
                                    variant="outlined"
                                    density="comfortable"
                                ></v-select>
                            </v-col>

                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="fieldForm.field_name"
                                    :label="t('authorityFieldManager.fieldName')"
                                    required
                                    :rules="[
                                        v => !!v || t('authorityFieldManager.required'),
                                        v =>
                                            /^[a-zA-Z0-9_]+$/.test(v) ||
                                            t('authorityFieldManager.fieldNameHint'),
                                    ]"
                                    variant="outlined"
                                    density="comfortable"
                                    :hint="t('authorityFieldManager.fieldNameHint')"
                                    persistent-hint
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="fieldForm.display_name"
                                    :label="t('authorityFieldManager.displayName')"
                                    required
                                    :rules="[v => !!v || t('authorityFieldManager.required')]"
                                    variant="outlined"
                                    density="comfortable"
                                    :hint="t('authorityFieldManager.displayNameHint')"
                                    persistent-hint
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12" md="6">
                                <v-checkbox
                                    v-model="fieldForm.required"
                                    :label="t('authorityFieldManager.required')"
                                    hide-details
                                ></v-checkbox>
                            </v-col>

                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="fieldForm.display_order"
                                    type="number"
                                    :label="t('authorityFieldManager.displayOrder')"
                                    :hint="t('authorityFieldManager.displayOrderHint')"
                                    persistent-hint
                                    variant="outlined"
                                    density="comfortable"
                                ></v-text-field>
                            </v-col>

                            <!-- Optionen für Dropdown-Felder -->
                            <v-col
                                cols="12"
                                v-if="
                                    fieldForm.field_type === 'select' ||
                                    fieldForm.field_type === 'multiselect'
                                "
                            >
                                <div class="d-flex align-center mb-2">
                                    <h3 class="text-subtitle-1">
                                        {{ t('authorityFieldManager.dropdownOptions') }}
                                    </h3>
                                    <v-spacer></v-spacer>
                                    <v-btn
                                        color="primary"
                                        size="small"
                                        prepend-icon="mdi-plus"
                                        @click="addOption"
                                    >
                                        {{ t('authorityFieldManager.addOption') }}
                                    </v-btn>
                                </div>

                                <v-alert
                                    v-if="fieldForm.field_type === 'multiselect'"
                                    type="info"
                                    variant="tonal"
                                    density="compact"
                                    class="mb-3"
                                >
                                    {{ t('authorityFieldManager.multiSelectInfo') }}
                                </v-alert>

                                <v-card
                                    variant="outlined"
                                    class="pa-2 mb-2"
                                    v-for="(option, index) in fieldOptions"
                                    :key="index"
                                >
                                    <div class="d-flex align-center">
                                        <v-text-field
                                            v-model="option.text"
                                            :label="t('authorityFieldManager.displayName')"
                                            variant="outlined"
                                            density="comfortable"
                                            class="mr-2"
                                        ></v-text-field>

                                        <v-text-field
                                            v-model="option.value"
                                            :label="t('authorityFieldManager.value')"
                                            variant="outlined"
                                            density="comfortable"
                                        ></v-text-field>

                                        <v-btn
                                            icon
                                            variant="text"
                                            color="error"
                                            class="ml-2"
                                            @click="removeOption(index)"
                                        >
                                            <v-icon>mdi-delete</v-icon>
                                        </v-btn>
                                    </div>
                                </v-card>

                                <v-alert
                                    v-if="fieldOptions.length === 0"
                                    type="info"
                                    variant="tonal"
                                    density="compact"
                                >
                                    {{ t('authorityFieldManager.noOptionsDefined') }}
                                </v-alert>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey-darken-1" variant="text" @click="showAddDialog = false">
                        {{ t('authorityFieldManager.cancel') }}
                    </v-btn>
                    <v-btn color="primary" @click="saveField">
                        {{ t('authorityFieldManager.save') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialog: Feld löschen -->
        <v-dialog v-model="showDeleteDialog" max-width="500px">
            <v-card>
                <v-card-title class="text-h5">
                    {{ t('authorityFieldManager.deleteField') }}
                </v-card-title>

                <v-card-text>
                    {{
                        t('authorityFieldManager.deleteFieldConfirm', {
                            name: selectedField?.display_name,
                        })
                    }}
                    <v-alert type="warning" variant="tonal" class="mt-3">
                        {{ t('authorityFieldManager.deleteFieldWarning') }}
                    </v-alert>
                </v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey-darken-1" variant="text" @click="showDeleteDialog = false">
                        {{ t('authorityFieldManager.cancel') }}
                    </v-btn>
                    <v-btn color="error" @click="deleteField">
                        {{ t('authorityFieldManager.delete') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-card>
</template>

<style scoped>
.authority-field-manager {
    border-radius: 8px;
}
</style>
