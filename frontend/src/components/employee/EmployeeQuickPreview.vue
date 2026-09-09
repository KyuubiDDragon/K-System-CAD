<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Employee, Rank, Company, Department, License } from '@/types/Members';
import type { Training, TrainingAssign } from '@/types/Training';

const { t } = useI18n();

interface Props {
    employee: Employee | null;
    rank?: Rank;
    companies: Company[];
    departments: Department[];
    licenses: License[];
    trainings: Training[];
    trainingAssigns: TrainingAssign[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    edit: [employee: Employee];
    editNotes: [employee: Employee];
    addVacation: [employee: Employee];
    stopVacation: [vacationId: number];
    showPromotions: [employee: Employee];
    showVacations: [employee: Employee];
}>();

// Get avatar image
const getImage = (path: string | null) => {
    try {
        if (path) {
            return new URL(`../../assets/ranks/${path}`, import.meta.url).href;
        } else {
            return new URL(`../../assets/logo.png`, import.meta.url).href;
        }
    } catch {
        return new URL(`@/assets/logo.png`, import.meta.url).href;
    }
};

// Format date helper
function formatDate(date: any) {
    if (!date) return '';
    const [datePart] = date.split(' ');
    const [year, month, day] = datePart.split('-');
    return `${day}.${month}.${year}`;
}

// Get employee companies
const employeeCompanies = computed(() => {
    if (!props.employee) return [];
    return props.employee.companies ? Object.values(props.employee.companies) : [];
});

// Get employee departments
const employeeDepartments = computed(() => {
    if (!props.employee) return [];
    return props.employee.departments ? Object.values(props.employee.departments) : [];
});

// Get employee licenses
const employeeLicenses = computed(() => {
    if (!props.employee) return [];
    return props.employee.licenses ? Object.values(props.employee.licenses) : [];
});

// Filter licenses by type
const filteredLicenses = (filter: string) => {
    return employeeLicenses.value.filter((license: any) => license && license.type === filter);
};

// Current vacation
const currentVacation = computed(() => {
    if (!props.employee || !props.employee.vacations) return null;
    const today = new Date();
    const vacations = Object.values(props.employee.vacations);
    return vacations.find((vacation: any) => {
        const startDate = new Date(vacation.start);
        const endDate = new Date(vacation.end);
        return today >= startDate && today <= endDate;
    });
});
</script>

<template>
    <v-navigation-drawer
        :model-value="!!employee"
        location="right"
        temporary
        width="450"
        class="employee-preview-sidebar"
        @update:model-value="!$event && emit('close')"
    >
        <v-card v-if="employee" class="preview-card h-100" flat>
            <!-- Header -->
            <v-toolbar density="compact" color="primary" class="preview-toolbar">
                <v-toolbar-title class="text-subtitle-1">
                    <v-icon start size="18" class="mr-2">mdi-account-details</v-icon>
                    {{ t('employeePreview.title') }}
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn icon size="small" @click="emit('close')">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>

            <!-- Content -->
            <v-card-text class="preview-content pa-6">
                <!-- Avatar & Basic Info -->
                <div class="text-center mb-6">
                    <v-avatar size="120" class="preview-avatar mb-4">
                        <v-img :src="getImage(rank?.rankImage || null)" />
                    </v-avatar>

                    <h2 class="text-h5 font-weight-bold mb-2">{{ employee.name }}</h2>
                    <div class="text-primary text-h6 mb-2">#{{ employee.servicenumber }}</div>
                    <v-chip color="primary" variant="tonal" size="small">
                        {{ employee.rank || rank?.name }}
                    </v-chip>

                    <!-- Vacation/Sick Badge -->
                    <v-alert
                        v-if="currentVacation"
                        :color="currentVacation.reason.toLowerCase().includes('krank') ? 'warning' : 'info'"
                        variant="tonal"
                        density="compact"
                        class="mt-4"
                    >
                        <template #prepend>
                            <v-icon v-if="currentVacation.reason.toLowerCase().includes('krank')">
                                mdi-medical-bag
                            </v-icon>
                            <v-icon v-else>mdi-beach</v-icon>
                        </template>
                        <div class="text-caption">
                            <strong>{{ currentVacation.reason }}</strong><br>
                            {{ formatDate(currentVacation.start) }} - {{ formatDate(currentVacation.end) }}
                        </div>
                    </v-alert>
                </div>

                <v-divider class="mb-4"></v-divider>

                <!-- Personal Information -->
                <div class="preview-section mb-5">
                    <div class="section-title mb-3">
                        <v-icon size="small" class="mr-2">mdi-account-details</v-icon>
                        {{ t('employeePreview.personalInfo') }}
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">{{ t('employeePreview.serviceNumber') }}</div>
                            <div class="info-value">{{ employee.servicenumber }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">{{ t('employeePreview.personalId') }}</div>
                            <div class="info-value">{{ employee.personalid || '-' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">{{ t('employeePreview.birthdate') }}</div>
                            <div class="info-value">{{ formatDate(employee.birthdate) }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">{{ t('employeePreview.entryDate') }}</div>
                            <div class="info-value">{{ formatDate(employee.entrydate) }}</div>
                        </div>
                        <div v-if="employee.is_terminated" class="info-item">
                            <div class="info-label">{{ t('employeePreview.leaveDate') }}</div>
                            <div class="info-value text-error">{{ formatDate(employee.leavedate) }}</div>
                        </div>
                    </div>
                </div>

                <v-divider class="mb-4"></v-divider>

                <!-- Contact Information -->
                <div class="preview-section mb-5">
                    <div class="section-title mb-3">
                        <v-icon size="small" class="mr-2">mdi-phone</v-icon>
                        {{ t('employeePreview.contact') }}
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">{{ t('employeePreview.phone') }}</div>
                            <div class="info-value">
                                <a :href="`tel:${employee.phonenumber}`" class="text-primary text-decoration-none">
                                    {{ employee.phonenumber }}
                                </a>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">{{ t('employeePreview.email') }}</div>
                            <div class="info-value">
                                <a :href="`mailto:${employee.mail}`" class="text-primary text-decoration-none">
                                    {{ employee.mail }}
                                </a>
                            </div>
                        </div>
                        <div v-if="employee.bankaccount" class="info-item">
                            <div class="info-label">{{ t('employeePreview.bankAccount') }}</div>
                            <div class="info-value">{{ employee.bankaccount }}</div>
                        </div>
                    </div>
                </div>

                <v-divider class="mb-4"></v-divider>

                <!-- Organization -->
                <div class="preview-section mb-5">
                    <div class="section-title mb-3">
                        <v-icon size="small" class="mr-2">mdi-office-building</v-icon>
                        {{ t('employeePreview.organization') }}
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">{{ t('employeePreview.companies') }}</div>
                            <div class="info-value">
                                <v-chip-group>
                                    <v-chip
                                        v-for="company in employeeCompanies"
                                        :key="company.id"
                                        size="x-small"
                                        color="primary"
                                        variant="tonal"
                                    >
                                        {{ company.name }}
                                    </v-chip>
                                </v-chip-group>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">{{ t('employeePreview.departments') }}</div>
                            <div class="info-value">
                                <v-chip-group>
                                    <v-chip
                                        v-for="department in employeeDepartments"
                                        :key="department.id"
                                        size="x-small"
                                        color="info"
                                        variant="tonal"
                                    >
                                        {{ department.name }}
                                    </v-chip>
                                </v-chip-group>
                            </div>
                        </div>
                    </div>
                </div>

                <v-divider class="mb-4"></v-divider>

                <!-- Licenses -->
                <div class="preview-section mb-5">
                    <div class="section-title mb-3">
                        <v-icon size="small" class="mr-2">mdi-license</v-icon>
                        {{ t('employeePreview.licenses') }}
                    </div>

                    <div v-if="employeeLicenses.length > 0">
                        <div v-if="filteredLicenses('driverlicense').length > 0" class="license-category mb-3">
                            <div class="license-category-title">{{ t('employeePreview.driverLicenses') }}</div>
                            <v-chip-group>
                                <v-chip
                                    v-for="license in filteredLicenses('driverlicense')"
                                    :key="license.id"
                                    size="small"
                                    color="success"
                                    variant="tonal"
                                >
                                    🚗 {{ license.name || license.license }}
                                </v-chip>
                            </v-chip-group>
                        </div>

                        <div v-if="filteredLicenses('diverlicense').length > 0" class="license-category mb-3">
                            <div class="license-category-title">{{ t('employeePreview.divingLicenses') }}</div>
                            <v-chip-group>
                                <v-chip
                                    v-for="license in filteredLicenses('diverlicense')"
                                    :key="license.id"
                                    size="small"
                                    color="info"
                                    variant="tonal"
                                >
                                    🤿 {{ license.name || license.license }}
                                </v-chip>
                            </v-chip-group>
                        </div>

                        <div v-if="filteredLicenses('medic').length > 0" class="license-category">
                            <div class="license-category-title">{{ t('employeePreview.medicalLicenses') }}</div>
                            <v-chip-group>
                                <v-chip
                                    v-for="license in filteredLicenses('medic')"
                                    :key="license.id"
                                    size="small"
                                    color="error"
                                    variant="tonal"
                                >
                                    🏥 {{ license.name || license.license }}
                                </v-chip>
                            </v-chip-group>
                        </div>
                    </div>
                    <div v-else class="text-caption text-grey">
                        {{ t('employeePreview.noLicenses') }}
                    </div>
                </div>

                <v-divider class="mb-4"></v-divider>

                <!-- History -->
                <div class="preview-section mb-5">
                    <div class="section-title mb-3">
                        <v-icon size="small" class="mr-2">mdi-history</v-icon>
                        {{ t('employeePreview.history') }}
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">{{ t('employeePreview.lastPromotion') }}</div>
                            <div class="info-value">
                                {{ formatDate(employee.last_promotion) || '-' }}
                                <v-btn
                                    v-if="employee.last_promotion"
                                    variant="text"
                                    size="x-small"
                                    color="primary"
                                    @click="emit('showPromotions', employee)"
                                    class="ml-1 pa-0"
                                >
                                    ({{ t('employeePreview.details') }})
                                </v-btn>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">{{ t('employeePreview.lastVacation') }}</div>
                            <div class="info-value">
                                {{ formatDate(employee.last_vacation) || '-' }}
                                <v-btn
                                    v-if="employee.last_vacation"
                                    variant="text"
                                    size="x-small"
                                    color="primary"
                                    @click="emit('showVacations', employee)"
                                    class="ml-1 pa-0"
                                >
                                    ({{ t('employeePreview.details') }})
                                </v-btn>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <v-btn
                        block
                        color="primary"
                        variant="elevated"
                        prepend-icon="mdi-account-edit"
                        class="mb-2"
                        @click="emit('edit', employee)"
                    >
                        {{ t('employeePreview.editEmployee') }}
                    </v-btn>
                    <v-btn
                        block
                        color="primary"
                        variant="tonal"
                        prepend-icon="mdi-note-edit"
                        class="mb-2"
                        @click="emit('editNotes', employee)"
                    >
                        {{ t('employeePreview.editNotes') }}
                    </v-btn>
                    <v-btn
                        v-if="currentVacation"
                        block
                        color="warning"
                        variant="tonal"
                        prepend-icon="mdi-calendar-remove"
                        class="mb-2"
                        @click="emit('stopVacation', currentVacation.id)"
                    >
                        {{ t('employeePreview.endVacation') }}
                    </v-btn>
                    <v-btn
                        v-else
                        block
                        color="primary"
                        variant="tonal"
                        prepend-icon="mdi-calendar-plus"
                        class="mb-2"
                        @click="emit('addVacation', employee)"
                    >
                        {{ t('employeePreview.addVacation') }}
                    </v-btn>
                </div>
            </v-card-text>
        </v-card>

        <!-- Empty State -->
        <div v-else class="preview-empty">
            <v-icon size="80" color="grey">mdi-account-outline</v-icon>
            <p class="text-h6 text-grey mt-4">{{ t('employeePreview.selectEmployee') }}</p>
        </div>
    </v-navigation-drawer>
</template>

<style scoped>
.employee-preview-sidebar {
    z-index: 2000 !important;
}

.preview-card {
    background: rgba(15, 23, 42, 0.95) !important;
}

.preview-toolbar {
    border-bottom: 1px solid var(--k-line);
}

.preview-content {
    overflow-y: auto;
    height: calc(100vh - 48px);
}

.preview-avatar {
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
    border: 3px solid rgba(59, 130, 246, 0.2);
}

.preview-section {
    animation: fadeIn 0.3s ease-out;
}

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

.section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: rgb(var(--v-theme-primary));
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
}

.info-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-label {
    font-size: 0.75rem;
    color: var(--k-ink-muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 0.875rem;
    color: var(--k-ink);
    font-weight: 500;
}

.license-category-title {
    font-size: 0.8rem;
    color: var(--k-ink-muted);
    margin-bottom: 8px;
    font-weight: 500;
}

.action-buttons {
    margin-top: 24px;
}

.preview-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 40px;
    text-align: center;
}

/* Scrollbar styling */
.preview-content::-webkit-scrollbar {
    width: 8px;
}

.preview-content::-webkit-scrollbar-track {
    background: var(--k-row-hover);
}

.preview-content::-webkit-scrollbar-thumb {
    background: var(--k-row-hover);
    border-radius: 4px;
}

.preview-content::-webkit-scrollbar-thumb:hover {
    background: var(--k-row-hover);
}
</style>
