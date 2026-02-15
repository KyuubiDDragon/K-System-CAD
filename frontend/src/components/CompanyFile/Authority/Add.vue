<script setup lang="ts">
import { defineComponent, ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Company, CompanyType } from '@/types/Company';
import apiAuthClient from '@/api';
import TiptapEditor from '@/components/TiptapEditor.vue';

const props = defineProps<{
    modelValue: boolean;
    newCompanyDialog: boolean;
}>();

const emit = defineEmits(['companyAdded', 'close', 'update:modelValue']);

const newCompany = ref<Company>({
    id: 0,
    name: '',
    location: '',
    email: '',
    ceo: '',
    co_ceo: '',
    type_id: 0,
    contact_person: '',
    phonenumber: '',
    last_fire_protection_inspection: '',
    fire_protection_inspection_valid_until: '',
    contract_available: false,
    description: '',
});
const companyTypes = ref<CompanyType[]>([]);
const currentTab = ref(0);
const { t } = useI18n();

const requiredRule = (value: string) => !!value || t('authorityComponents.common.requiredField');

const fetchCompanyTypes = async () => {
    try {
        const response = await apiAuthClient.get('company/?action=getCompanyTypes');
        companyTypes.value = response.data;
    } catch (error) {
        console.error(t('authorityComponents.common.loadError'), error);
    }
};

const addCompany = async () => {
    try {
        await apiAuthClient.post('company/?action=addCompany', newCompany.value);
        emit('companyAdded');
        closeForm();
    } catch (error) {
        console.error(t('authorityComponents.companyFile.errorAdd'), error);
    }
};

const closeForm = () => {
    emit('update:modelValue', false);
};

onMounted(async () => {
    await fetchCompanyTypes();
});
</script>

<template>
    <!-- Neue Firma hinzufügen Formular -->
    <v-card v-if="newCompanyDialog" class="company-add-card" theme="dark">
        <v-toolbar density="compact" color="success" class="card-toolbar">
            <v-toolbar-title class="text-subtitle-1">
                <v-icon start size="18" class="mr-2">mdi-domain-plus</v-icon>
                {{ $t('authorityComponents.companyFile.addTitle') }}
            </v-toolbar-title>
        </v-toolbar>

        <!-- Tabs für verschiedene Formularinformationen -->
        <v-tabs
            v-model="currentTab"
            show-arrows
            color="success"
            class="add-tabs mb-2"
            slider-color="success"
            density="comfortable"
        >
            <v-tab value="1" class="tab-item">
                <v-icon start size="16">mdi-information-outline</v-icon>
                {{ $t('authorityComponents.tabs.info') }}
            </v-tab>
            <v-tab value="2" class="tab-item">
                <v-icon start size="16">mdi-text-box-plus-outline</v-icon>
                {{ $t('authorityComponents.tabs.description') }}
            </v-tab>
        </v-tabs>

        <!-- Inhalt der Tabs -->
        <v-window v-model="currentTab" class="tab-content">
            <!-- Tab 1: Firmendetails -->
            <v-window-item value="1">
                <v-card-text class="pt-2">
                    <v-form ref="form">
                        <div class="form-section mb-4">
                            <div class="section-title">
                                <v-icon size="small" class="mr-1">mdi-domain</v-icon>
                                {{ $t('companyForm.basicInfoSection') }}
                            </div>

                            <v-row>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('companyForm.name') + '*'"
                                        v-model="newCompany.name"
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
                                        v-model="newCompany.location"
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
                                        v-model="newCompany.type_id"
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
                                        v-model="newCompany.contract_available"
                                        :label="$t('companyForm.contractAvailable')"
                                        color="success"
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
                                        v-model="newCompany.phonenumber"
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
                                        v-model="newCompany.email"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-email"
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        :label="$t('companyForm.ceo') + '*'"
                                        v-model="newCompany.ceo"
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
                                        v-model="newCompany.co_ceo"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        prepend-inner-icon="mdi-account-tie-outline"
                                        class="field-item"
                                    ></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        :label="$t('companyForm.contactPerson') + '*'"
                                        v-model="newCompany.contact_person"
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
                                        v-model="newCompany.last_fire_protection_inspection"
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
                                        v-model="newCompany.fire_protection_inspection_valid_until"
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
                        <v-icon size="small" class="mr-1">mdi-text-box-plus</v-icon>
                        {{ $t('companyForm.descriptionSection') }}
                    </div>

                    <div class="editor-container">
                        <TiptapEditor
                            v-model="newCompany.description"
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

            <v-btn color="success" variant="elevated" @click="addCompany" class="add-button">
                <v-icon start>mdi-plus</v-icon>
                {{ $t('authorityComponents.common.add') }}
            </v-btn>
        </v-card-actions>
    </v-card>
</template>
<style scoped>

/* Card Styling */
.company-add-card {
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
.add-tabs {
    background-color: rgba(30, 41, 59, 0.3) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--v-theme-success);
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
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    overflow: hidden;
    min-height: 300px;
}

/* Buttons */
.action-button,
.add-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all var(--transition-timing);
}

.action-button:hover {
    opacity: 0.9;
}

.add-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 6px 12px rgba(76, 175, 80, 0.2);
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

    .add-tabs :deep(.v-tab__content) {
        justify-content: center;
    }
}
</style>
