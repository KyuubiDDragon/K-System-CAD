<script setup lang="ts">
import { defineComponent, ref, computed, watch, onMounted, type PropType } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Company, CompanyType } from '@/types/Company';
import { apiClientAuth } from '@/api';

import TiptapEditor from '@/components/TiptapEditor.vue';
interface Props {
  modelValue?: boolean
  companyToEdit?: Record<string, any>
  editCompanyDialog?: boolean
}

const props = defineProps<Props>();

// --- Emits ---
const emit = defineEmits(['update:modelValue', 'companyUpdated', 'close']);

const editedCompany = ref<Company>({ ...(props.companyToEdit || {}) } as Company);
const companyTypes = ref<CompanyType[]>([]);
const currentTab = ref(0);
const { t } = useI18n();

const requiredRule = (value: string) => !!value || t('authorityComponents.common.requiredField');

const fetchCompanyTypes = async () => {
    try {
        const response = await apiClientAuth.get('/company/?action=getCompanyTypes');
        companyTypes.value = response.data;
    } catch (error) {
        console.error(t('authorityComponents.common.loadError'), error);
    }
};

const updateCompany = async () => {
    try {
        await apiClientAuth.post('/company/?action=editCompany', editedCompany.value);
        emit('companyUpdated');
        closeForm();
    } catch (error) {
        console.error(t('authorityComponents.companyFile.errorUpdate'), error);
    }
};

const closeForm = () => {
    emit('update:modelValue', false);
};

watch(
    () => props.companyToEdit,
    newVal => {
        editedCompany.value = { ...(newVal || {}) } as Company;
    },
    { immediate: true }
);

onMounted(async () => {
    await fetchCompanyTypes();
});
</script>

<template>
    <!-- Firma bearbeiten Formular -->
    <v-card v-if="editCompanyDialog" class="company-edit-card">
        <v-toolbar density="compact" color="primary" class="card-toolbar">
            <v-toolbar-title class="text-subtitle-1">
                <v-icon start size="18" class="mr-2">mdi-domain-edit</v-icon>
                {{ $t('authorityComponents.companyFile.editTitle') }}
            </v-toolbar-title>
        </v-toolbar>

        <!-- Tabs für verschiedene Formularinformationen -->
        <v-tabs
            v-model="currentTab"
            show-arrows
            color="primary"
            class="edit-tabs mb-2"
            slider-color="primary"
            density="comfortable"
        >
            <v-tab value="1" class="tab-item">
                <v-icon start size="16">mdi-information-outline</v-icon>
                {{ $t('authorityComponents.tabs.info') }}
            </v-tab>
            <v-tab value="2" class="tab-item">
                <v-icon start size="16">mdi-text-box-edit-outline</v-icon>
                {{ $t('authorityComponents.tabs.description') }}
            </v-tab>
        </v-tabs>

        <!-- Inhalt der Tabs -->
        <v-window v-model="currentTab" class="tab-content">
            <!-- Tab 1: Firmendetails -->
            <v-window-item value="1">
                <v-card-text class="pt-2">
                    <v-form ref="editCompanyForm">
                        <div class="form-section mb-4">
                            <div class="section-title">
                                <v-icon size="small" class="mr-1">mdi-domain</v-icon>
                                {{ $t('companyForm.basicInfoSection') }}
                            </div>

                            <v-row>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('companyForm.name') + '*'"
                                        v-model="editedCompany.name"
                                        required
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-domain"
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('companyForm.location') + '*'"
                                        v-model="editedCompany.location"
                                        required
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-map-marker"
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-select
                                        :label="$t('companyForm.type') + '*'"
                                        v-model="editedCompany.type_id"
                                        :items="companyTypes"
                                        item-title="name"
                                        item-value="id"
                                        required
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-shape-outline"
                                        class="field-item"
                                    ></v-select>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-checkbox
                                        v-model="editedCompany.contract_available"
                                        :label="$t('companyForm.contractAvailable')"
                                        color="primary"
                                        hide-details
                                        class="mt-3"
                                    ></v-checkbox>
                                </v-col>
                            </v-row>
                        </div>

                        <div class="form-section mb-4">
                            <div class="section-title">
                                <v-icon size="small" class="mr-1">mdi-account-tie</v-icon>
                                {{ $t('companyForm.contactInfoSection') }}
                            </div>

                            <v-row>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('companyForm.phone') + '*'"
                                        v-model="editedCompany.phonenumber"
                                        required
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-phone"
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('companyForm.email')"
                                        v-model="editedCompany.email"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-email"
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('companyForm.ceo') + '*'"
                                        v-model="editedCompany.ceo"
                                        required
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-account-tie"
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('companyForm.coCeo')"
                                        v-model="editedCompany.co_ceo"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-account-tie-outline"
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        :label="$t('companyForm.contactPerson') + '*'"
                                        v-model="editedCompany.contact_person"
                                        required
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-account-voice"
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </div>

                        <div class="form-section">
                            <div class="section-title">
                                <v-icon size="small" class="mr-1">mdi-shield-fire</v-icon>
                                {{ $t('companyForm.fireProtectionSection') }}
                            </div>

                            <v-row>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('companyForm.lastFireInspection') + '*'"
                                        v-model="editedCompany.last_fire_protection_inspection"
                                        type="date"
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-calendar-check"
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('companyForm.fireInspectionValidUntil')"
                                        v-model="
                                            editedCompany.fire_protection_inspection_valid_until
                                        "
                                        type="date"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-calendar-clock"
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </div>
                    </v-form>
                </v-card-text>
            </v-window-item>

            <!-- Tab 2: Zusätzliche Informationen -->
            <v-window-item value="2">
                <v-card-text class="pt-2">
                    <div class="section-title mb-4">
                        <v-icon size="small" class="mr-1">mdi-text-box-edit</v-icon>
                        {{ $t('companyForm.descriptionSection') }}
                    </div>

                    <div class="editor-container">
                        <TiptapEditor
                            v-model="editedCompany.description"
                            :placeholder="$t('companyForm.descriptionPlaceholder')"
                            :show-character-count="false"
                            :show-source-button="true"
                            :editable="true"
                        />
                    </div>
                </v-card-text>
            </v-window-item>
        </v-window>

        <!-- Aktionsbuttons -->
        <v-divider></v-divider>

        <v-card-actions class="pa-4">
            <v-spacer></v-spacer>
            <v-btn variant="text" @click="closeForm" class="action-button mr-2">
                {{ $t('authorityComponents.common.cancel') }}
            </v-btn>

            <v-btn color="primary" variant="elevated" @click="updateCompany" class="save-button">
                <v-icon start>mdi-content-save</v-icon>
                {{ $t('authorityComponents.common.save') }}
            </v-btn>
        </v-card-actions>
    </v-card>
</template>
<style scoped>

/* Card Styling */
.company-edit-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.card-toolbar {
    border-bottom: 1px solid var(--card-border);
}

/* Tabs Styling */
.edit-tabs {
    background-color: var(--k-sunken) !important;
    border-bottom: 1px solid var(--k-line);
}

.tab-item {
    min-width: 140px;
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
}

.tab-content {
    min-height: 400px;
}

/* Form Sections */
.form-section {
    margin-bottom: 24px;
}

.section-title {
    display: flex;
    align-items: center;
    font-size: 0.95rem;
    font-weight: 500;
    margin-bottom: 16px;
    padding-bottom: 6px;
    border-bottom: 1px solid var(--k-line);
    color: var(--v-theme-primary);
}

/* Form Fields */
.field-item {
    border-radius: 8px;
    transition: all var(--transition-timing);
}

.field-item:focus-within {
    transform: var(--button-hover-translate);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Editor Container */
.editor-container {
    border: 1px solid var(--k-line);
    border-radius: 8px;
    overflow: hidden;
    min-height: 300px;
}

/* Buttons */
.action-button,
.save-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all var(--transition-timing);
}

.action-button:hover {
    opacity: 0.9;
}

.save-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 6px 12px var(--k-accent-weak);
}

/* Responsive Adjustments */
@media (max-width: 600px) {
    .tab-item {
        min-width: auto;
        padding: 0 8px;
    }

    .section-title {
        font-size: 0.9rem;
    }

    .edit-tabs :deep(.v-tab__content) {
        justify-content: center;
    }
}
</style>
