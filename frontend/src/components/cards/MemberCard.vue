<script setup lang="ts">
import { defineComponent, ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { type License, type Employee, type Rank } from '@/types/Members';
import { type TrainingAssign, type Training } from '@/types/Training';
import EmployeeForm from './EmployeeForm.vue';
import DocumentEditor from '@/components/document/DocumentEditor.vue';
import api from '@/api';
import { useRoute } from 'vue-router';
import { useToast } from 'vue-toastification';

const emit = defineEmits(['edit', 'updateNotes']);

interface Vacation {
    id: number;
    reason: string;
    start: string;
    end: string;
    reported: boolean;
    other: string;
}

const props = defineProps<{
    member: any;
    logoURL: string;
    companies: any[]; // ggf. Company[] definieren
    departments: any[]; // ggf. Department[] definieren
    ranks: Rank[];
    licenses: any[]; // ggf. License[] definieren
    showPreview: boolean;
    showIsTerminated: boolean;
    trainings: Training[];
    trainingassigns: TrainingAssign[];
}>();

const expandedIndex = ref(-1);
const editMode = ref(false);
const toast = useToast();
const { t } = useI18n();

const licensesArray = computed(() => {
    if (props.member.licenses && typeof props.member.licenses === 'object') {
        return Object.values(props.member.licenses);
    }
    return [];
});

const route = useRoute();

const canEdit = computed(() => route.meta.canEdit);
const canDelete = computed(() => route.meta.canDelete);

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

const filteredLicenses = (filter: string) => {
    console.log("Filtering licenses for type:", filter, "All licenses:", licensesArray.value);
    return (licensesArray.value as License[]).filter((license: License) => {
        return license && license.type === filter;
    });
};

// Delete vacation functionality removed - vacation should be stopped via stopVacation() instead
// The backend action deleteVacation doesn't exist, vacation management uses user module

const saving = ref(false);
const newVacation = ref({
    type: '',
    start: '',
    end: '',
    reported: false,
    other: '',
});

async function saveVacation() {
	saving.value = true;
    try {
        await api.post('user/?action=addVacation', {
            id: props.member.id,
            start: newVacation.value.start,
            end: newVacation.value.end,
            type: newVacation.value.type,
            reported: newVacation.value.reported,
            other: newVacation.value.other,
        });
        newVacationDialog.value = false;
        newVacation.value = {
            type: '',
            start: '',
            end: '',
            reported: false,
            other: '',
        };
    } catch (err) {
        toast.error(t('memberCard.errorSavingVacation'));
    } finally {
        saving.value = false;
    }
}

async function stopVacation(id: number) {
    try {
        await api.post('user/?action=stopVacation', {
            id: id,
            newdate: new Date(),
        });
    } catch (err) {
        toast.error('Fehler beim Löschen des Urlaubs.');
    }
}

const expandCard = (index: number) => {
    if (expandedIndex.value === index) {
        expandedIndex.value = -1;
    } else {
        expandedIndex.value = index;
    }
};

const editUser = (user: number) => {
    // Find the employee in the list
    editMode.value = true;
    const employee = props.member;
    const { companies, departments, ranks, licenses } = props;
    const editEmployee = true;
    emit('edit', {
        employee,
        companies,
        departments,
        ranks,
        licenses,
        editEmployee,
    });
};

const employeeForm = ref();

const promotionTableHeaders = [
    { title: 'Beförderungsdatum', key: 'promotion_date' },
    { title: 'Von Rang', key: 'old_rank_name' },
    { title: 'Zu Rang', key: 'new_rank_name' },
];

const vacationTableHeaders = [
    { title: 'Grund', key: 'reason' },
    { title: 'Von', key: 'start' },
    { title: 'Bis', key: 'end' },
    { title: 'Gemeldet', key: 'reported' },
    { title: 'Sonstiges', key: 'other' },
    {
        title: 'Actions',
        align: 'start',
        sortable: false,
        key: 'actions',
    },
] as const;

const promotionDialog = ref(false);
const promotionsArray = computed(() => {
    if (props.member.promotions && typeof props.member.promotions === 'object') {
        return Object.values(props.member.promotions);
    }
    return [];
});
const showPromotionsDialog = () => {
    promotionDialog.value = true;
};

const vacationDialog = ref(false);
const vacationsArray = computed<Vacation[]>(() => {
    if (props.member.vacations && typeof props.member.vacations === 'object') {
        return Object.values(props.member.vacations) as Vacation[];
    }
    return [];
});

const currentVacation = computed<Vacation | undefined>(() => {
    const today = new Date();
    return vacationsArray.value.find(vacation => {
        const startDate = new Date(vacation.start);
        const endDate = new Date(vacation.end);
        return today >= startDate && today <= endDate;
    });
});
const showVacationsDialog = () => {
    vacationDialog.value = true;
};

const newVacationDialog = ref(false);

function formatDate(date: any) {
    if (!date) return '';
    const [datePart] = date.split(' ');
    const [year, month, day] = datePart.split('-');
    return `${day}.${month}.${year}`;
}

// Transform departments and companies objects to arrays
const memberDepartments = computed(() => {
    return props.member.departments ? Object.values(props.member.departments) : [];
});

const memberCompanies = computed(() => {
    return props.member.companies ? Object.values(props.member.companies) : [];
});

const createTrainingRows = () => {
    const categoriesMap: {
        [key: number]: {
            catName: string;
            color: string;
            trainings: Training[];
        };
    } = {};

    console.log('Props trainings:', props.trainings);
    console.log('Props trainingassigns:', props.trainingassigns);
    console.log('Member ID:', props.member.id);

    // Group trainings by category ID
    (props.trainings as Training[]).forEach(training => {
        if (!categoriesMap[training.catId]) {
            categoriesMap[training.catId] = {
                catName: training.cat_name,
                color: training.color,
                trainings: [],
            };
        }
        categoriesMap[training.catId].trainings.push(training);
    });

    // Convert categories map to array and sort by the original order of Trainings
    const sortedCategories = Object.values(categoriesMap).sort((a, b) => {
        const aIndex = (props.trainings as Training[]).findIndex(
            t => t.catId === a.trainings[0].catId
        );
        const bIndex = (props.trainings as Training[]).findIndex(
            t => t.catId === b.trainings[0].catId
        );
        return aIndex - bIndex;
    });

    let trainingRows = '';

    sortedCategories.forEach(category => {
        trainingRows += `
            <tr>
                <td colspan="2" style="background-color:#${category.color}; text-align:center"><strong><span style="color:#D3D3D3">${category.catName}</span></strong></td>
            </tr>
        `;
        category.trainings.forEach(training => {
            const assignments = (props.trainingassigns as any[]).filter(assign => {
                const match =
                    assign.training_id === training.id && assign.employeeid == props.member.id;
                if (match) {
                    console.log('Matched assignment:', assign);
                }
                return match;
            });

            if (assignments.length > 0) {
                const latestAssignment = assignments.reduce(
                    (prev, current) => (prev.id > current.id ? prev : current),
                    assignments[0]
                );
                const trainingDate = latestAssignment ? latestAssignment.date : '&nbsp;';
                const trainingInstructor = latestAssignment
                    ? latestAssignment.instructor
                    : '&nbsp;';
                trainingRows += `
                    <tr>
                        <td style="width:200px"><strong>${training.name}</strong></td>
                        <td>${
                            trainingDate === '&nbsp;' ? '' : formatDate(trainingDate)
                        } - ${trainingInstructor}</td>
                    </tr>
                `;
            } else {
                trainingRows += `
                    <tr>
                        <td style="width:200px"><strong>${training.name}</strong></td>
                        <td>&nbsp;</td>
                    </tr>
                `;
            }
        });
    });

    console.log('Generated training rows:', trainingRows);
    return trainingRows;
};

const createCompanies = () => {
    return memberCompanies.value.map((company: any) => company.name).join(', ');
};

const createDepartments = () => {
    return memberDepartments.value.map((department: any) => department.name).join(', ');
};

function openNewVacationDialog() {
    newVacationDialog.value = true;
}

//Preview File System

const content = ref<string | null>(null);
function resetSelectedDocument() {
    content.value = null;
}

//Preview File System

//Employee File Notes
const selectedDocumentEmployeeNotes = ref<Employee | null>(null);
function resetSelectedDocumentEmployeeNotes() {
    selectedDocumentEmployeeNotes.value = null;
}

const updateSelectedDocumentEmployeeNotes = async (updatedEmployee: any) => {
    if (updatedEmployee.id != -1) {
        try {
            const response = await api.post('employee/?action=updateNotes', {
                id: updatedEmployee.id,
                notes: updatedEmployee.notes,
            });
            if (response.data.success) {
                emit('updateNotes', {
                    id: updatedEmployee.id,
                    notes: updatedEmployee.notes,
                });
            }
        } catch (error: any) {
            toast.error(error.response?.data?.error || 'An error occurred');
        }
    }
};

async function editNotes(member: Employee) {
    try {
        selectedDocumentEmployeeNotes.value = member;
    } catch (err) {
        toast.error(t('memberCard.errorEditing'));
    }
}

async function addVacation(member: Employee) {
    try {
        selectedDocumentEmployeeNotes.value = member;
    } catch (err) {
        toast.error(t('memberCard.errorEditing'));
    }
}
</script>

<template>
    <v-col cols="12" sm="6" md="4" lg="4" xl="3">
        <v-card
            class="employee-card elevation-4"
            theme="dark"
            :class="{
                'terminated-employee': member.is_terminated == true && !showIsTerminated,
                'vacation-employee': currentVacation,
            }"
        >
            <!-- Status Banner für Gekündigt oder im Urlaub -->
            <div class="status-banner" v-if="member.is_terminated || currentVacation">
                <div class="status-icon">
                    <v-icon v-if="member.is_terminated" color="error" size="small"
                        >mdi-account-cancel</v-icon
                    >
                    <v-icon
                        v-else-if="
                            currentVacation &&
                            currentVacation.reason.toLowerCase().includes('krank')
                        "
                        color="warning"
                        size="small"
                        >mdi-medical-bag</v-icon
                    >
                    <v-icon v-else color="info" size="small">mdi-beach</v-icon>
                </div>
                <div class="status-text">
                    <template v-if="member.is_terminated"
                        >Ausgetreten am {{ formatDate(member.leavedate) }}</template
                    >
                    <template v-else-if="currentVacation">
                        <span
                            :class="{
                                'status-sick': currentVacation.reason
                                    .toLowerCase()
                                    .includes('krank'),
                            }"
                        >
                            {{ currentVacation.reason }}
                        </span>
                        <span class="status-date">
                            {{ formatDate(currentVacation.start) }} -
                            {{ formatDate(currentVacation.end) }}
                        </span>
                    </template>
                </div>
                <div v-if="currentVacation && canEdit" class="status-action">
                    <v-btn
                        density="comfortable"
                        size="x-small"
                        icon="mdi-calendar-remove"
                        variant="tonal"
                        color="error"
                        @click="stopVacation(currentVacation.id)"
                    ></v-btn>
                </div>
            </div>

            <!-- Header-Bereich mit Logo und Basisdaten -->
            <div class="card-header">
                <div class="employee-info">
                    <div class="employee-name" @click="expandCard(member.id)">
                        <!-- Removed tooltip -->
                        <span class="name-text">
                            <v-icon size="small" class="mr-1">{{
                                expandedIndex === member.id
                                    ? 'mdi-chevron-down'
                                    : 'mdi-chevron-right'
                            }}</v-icon>
                            [{{ member.servicenumber }}] {{ member.name }}
                        </span>
                    </div>

                    <div class="employee-rank">{{ member.rank }}</div>

                    <div class="employee-department">
                        <template v-for="(company, index) in member.companies" :key="index">
                            <span class="department-item">{{ company.name }}</span>
                        </template>
                        <template v-for="(department, index) in member.departments" :key="index">
                            <span class="department-item">| {{ department.name }}</span>
                        </template>
                    </div>
                </div>

                <div class="logo-container">
                    <v-img
                        :src="getImage(logoURL)"
                        width="64"
                        height="64"
                        class="rank-logo"
                    ></v-img>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions px-6 py-2">
                <v-btn
                    variant="text"
                    size="small"
                    color="primary"
                    @click="editNotes(member)"
                    prepend-icon="mdi-note-edit"
                    class="quick-action-btn"
                >
                    {{ $t('memberCard.notes') }}
                </v-btn>

                <v-btn
                    v-if="canEdit"
                    variant="text"
                    size="small"
                    color="primary"
                    @click="openNewVacationDialog()"
                    prepend-icon="mdi-calendar-plus"
                    class="quick-action-btn"
                >
                    {{ $t('memberCard.vacation') }}
                </v-btn>

                <v-btn
                    v-if="canEdit"
                    variant="text"
                    size="small"
                    color="primary"
                    @click="editUser"
                    prepend-icon="mdi-account-edit"
                    class="quick-action-btn"
                >
                    {{ $t('memberCard.edit') }}
                </v-btn>
            </div>

            <!-- Erweiterte Informationen (wenn expandiert) -->
            <v-expand-transition>
                <div v-if="expandedIndex === member.id" class="expanded-content pa-4">
                    <div class="contact-info mb-3">
                        <v-chip
                            size="small"
                            color="primary"
                            variant="tonal"
                            prepend-icon="mdi-phone"
                            class="mr-2 mb-2"
                        >
                            {{ member.phonenumber }}
                        </v-chip>
                        <v-chip
                            size="small"
                            color="primary"
                            variant="tonal"
                            prepend-icon="mdi-email"
                            class="mb-2"
                        >
                            {{ member.mail }}
                        </v-chip>
                    </div>

                    <v-divider class="mb-3"></v-divider>

                    <!-- Persönliche Informationen -->
                    <div class="detail-section mb-4">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-account-details</v-icon>
                            Persönliche Informationen
                        </div>

                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Name</div>
                                <div class="detail-value">{{ member.name }}</div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Geburtsdatum</div>
                                <div class="detail-value">{{ formatDate(member.birthdate) }}</div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Dienstnummer</div>
                                <div class="detail-value">{{ member.servicenumber }}</div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Ausweis ID</div>
                                <div class="detail-value">{{ member.personalid }}</div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Kontonummer</div>
                                <div class="detail-value">{{ member.bankaccount }}</div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Beigetreten am</div>
                                <div class="detail-value">{{ formatDate(member.entrydate) }}</div>
                            </div>

                            <div class="detail-item" v-if="member.is_terminated">
                                <div class="detail-label">Austrittsdatum</div>
                                <div class="detail-value text-error">
                                    {{ formatDate(member.leavedate) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Abteilungen und Companies -->
                    <div class="detail-section mb-4">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-office-building</v-icon>
                            Zugehörigkeiten
                        </div>

                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Companies</div>
                                <div class="detail-value">
                                    <template
                                        v-for="(company, index) in member.companies"
                                        :key="index"
                                    >
                                        <v-chip
                                            size="x-small"
                                            color="primary"
                                            variant="tonal"
                                            class="mr-1 mb-1"
                                            >{{ company.name }}</v-chip
                                        >
                                    </template>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Abteilungen</div>
                                <div class="detail-value">
                                    <template
                                        v-for="(department, index) in member.departments"
                                        :key="index"
                                    >
                                        <v-chip
                                            size="x-small"
                                            color="info"
                                            variant="tonal"
                                            class="mr-1 mb-1"
                                            >{{ department.name }}</v-chip
                                        >
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Beförderungen und Urlaub -->
                    <div class="detail-section mb-4">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-history</v-icon>
                            Verlauf
                        </div>

                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Letzte Beförderung</div>
                                <div class="detail-value">
                                    {{ formatDate(member.last_promotion) }}
                                    <v-btn
                                        variant="text"
                                        size="x-small"
                                        color="primary"
                                        @click="showPromotionsDialog"
                                        class="ml-1 pa-0"
                                    >
                                        (Details)
                                    </v-btn>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Urlaub</div>
                                <div class="detail-value">
                                    {{ formatDate(member.last_vacation) }}
                                    <v-btn
                                        variant="text"
                                        size="x-small"
                                        color="primary"
                                        @click="showVacationsDialog"
                                        class="ml-1 pa-0"
                                    >
                                        (Details)
                                    </v-btn>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lizenzen und Zertifikate -->
                    <div class="detail-section">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-license</v-icon>
                            Lizenzen und Zertifikate
                        </div>

                        <div class="licenses-container">
                            <div class="license-category">
                                <div class="license-category-title">Führerschein</div>
                                <div class="license-items">
                                    <template
                                        v-for="license in filteredLicenses('driverlicense')"
                                        :key="license.id"
                                    >
                                        <v-chip
                                            size="small"
                                            color="success"
                                            variant="tonal"
                                            class="mr-1 mb-1"
                                            >{{ license.name || license.license }}</v-chip
                                        >
                                    </template>
                                    <span
                                        v-if="filteredLicenses('driverlicense').length === 0"
                                        class="no-licenses"
                                        >Keine Lizenzen vorhanden</span
                                    >
                                </div>
                            </div>

                            <div class="license-category">
                                <div class="license-category-title">Tauchschein</div>
                                <div class="license-items">
                                    <template
                                        v-for="license in filteredLicenses('diverlicense')"
                                        :key="license.id"
                                    >
                                        <v-chip
                                            size="small"
                                            color="info"
                                            variant="tonal"
                                            class="mr-1 mb-1"
                                            >{{ license.name || license.license }}</v-chip
                                        >
                                    </template>
                                    <span
                                        v-if="filteredLicenses('diverlicense').length === 0"
                                        class="no-licenses"
                                        >Keine Lizenzen vorhanden</span
                                    >
                                </div>
                            </div>

                            <div class="license-category">
                                <div class="license-category-title">Medizinisch</div>
                                <div class="license-items">
                                    <template
                                        v-for="license in filteredLicenses('medic')"
                                        :key="license.id"
                                    >
                                        <v-chip
                                            size="small"
                                            color="error"
                                            variant="tonal"
                                            class="mr-1 mb-1"
                                            >{{ license.name || license.license }}</v-chip
                                        >
                                    </template>
                                    <span
                                        v-if="filteredLicenses('medic').length === 0"
                                        class="no-licenses"
                                        >Keine Lizenzen vorhanden</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </v-expand-transition>
        </v-card>
    </v-col>

    <!-- Beförderungsverlauf Dialog -->
    <v-dialog v-model="promotionDialog" max-width="700" class="promotion-dialog">
        <v-card theme="dark" class="dialog-card">
            <v-toolbar density="compact" color="primary" class="card-toolbar">
                <v-toolbar-title class="text-subtitle-1">
                    <v-icon start size="18" class="mr-2">mdi-medal</v-icon>
                    {{ $t('memberCard.promotionsTitle', { name: member.name }) }}
                </v-toolbar-title>
            </v-toolbar>

            <v-card-text class="pa-4">
                <v-data-table
                    :headers="promotionTableHeaders"
                    :items="promotionsArray"
                    class="promotion-table"
                    :sort-by="[{ key: 'promotion_date', order: 'desc' }]"
                    hover
                    density="comfortable"
                >
                    <template v-slot:no-data>
                        <div class="empty-state">
                            <v-icon size="40" color="grey-darken-1" class="mb-2"
                                >mdi-medal-outline</v-icon
                            >
                            <span>{{ $t('memberCard.noPromotions') }}</span>
                        </div>
                    </template>
                </v-data-table>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions class="pa-4">
                <v-spacer></v-spacer>
                <v-btn
                    color="primary"
                    variant="elevated"
                    @click="promotionDialog = false"
                    class="close-button"
                >
                    {{ $t('memberCard.close') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Urlaubsverlauf Dialog -->
    <v-dialog v-model="vacationDialog" max-width="800" class="vacation-dialog">
        <v-card theme="dark" class="dialog-card">
            <v-toolbar density="compact" color="primary" class="card-toolbar">
                <v-toolbar-title class="text-subtitle-1">
                    <v-icon start size="18" class="mr-2">mdi-calendar-check</v-icon>
                    {{ $t('memberCard.vacationsTitle', { name: member.name }) }}
                </v-toolbar-title>
            </v-toolbar>

            <v-card-text class="pa-4">
                <v-data-table
                    :headers="vacationTableHeaders"
                    :items="vacationsArray"
                    class="vacation-table"
                    :sort-by="[{ key: 'end', order: 'desc' }]"
                    hover
                    density="comfortable"
                >
                    <template v-slot:[`item.reported`]="{ item }">
                        <v-icon v-if="item.reported" color="success" size="small">mdi-check</v-icon>
                        <v-icon v-else color="error" size="small">mdi-close</v-icon>
                    </template>

                    <!-- Delete vacation button removed - use stopVacation() instead -->
                    <!-- Backend action deleteVacation doesn't exist -->

                    <template v-slot:no-data>
                        <div class="empty-state">
                            <v-icon size="40" color="grey-darken-1" class="mb-2"
                                >mdi-calendar-blank</v-icon
                            >
                            <span>{{ $t('memberCard.noVacations') }}</span>
                        </div>
                    </template>
                </v-data-table>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions class="pa-4">
                <v-spacer></v-spacer>
                <v-btn
                    color="primary"
                    variant="elevated"
                    @click="vacationDialog = false"
                    class="close-button"
                >
                    {{ $t('memberCard.close') }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Neuer Urlaub Dialog -->
    <v-dialog v-model="newVacationDialog" max-width="700" persistent class="new-vacation-dialog">
        <v-card theme="dark" class="dialog-card">
            <v-toolbar density="compact" color="primary" class="card-toolbar">
                <v-toolbar-title class="text-subtitle-1">
                    <v-icon start size="18" class="mr-2">mdi-calendar-plus</v-icon>
                    Urlaub hinzufügen - {{ member.name }}
                </v-toolbar-title>
            </v-toolbar>

            <v-card-text class="pa-6">
                <v-row>
                    <v-col cols="12" sm="4">
                        <v-select
                            :items="['Urlaub', 'Krank', 'Sporadisch im Dienst', 'Sonstiges']"
                            label="Art des Urlaubs"
                            v-model="newVacation.type"
                            required
                            :rules="[v => !!v || 'Bitte Art auswählen']"
                            prepend-inner-icon="mdi-format-list-checks"
                            variant="outlined"
                            density="comfortable"
                            bg-color="grey-darken-3"
                            class="field-item"
                        ></v-select>
                    </v-col>

                    <v-col cols="12" sm="4">
                        <v-text-field
                            v-model="newVacation.start"
                            label="Von"
                            type="date"
                            required
                            :rules="[v => !!v || 'Bitte Startdatum wählen']"
                            prepend-inner-icon="mdi-calendar-start"
                            variant="outlined"
                            density="comfortable"
                            bg-color="grey-darken-3"
                            class="field-item"
                        ></v-text-field>
                    </v-col>

                    <v-col cols="12" sm="4">
                        <v-text-field
                            v-model="newVacation.end"
                            label="Bis"
                            type="date"
                            required
                            :rules="[v => !!v || 'Bitte Enddatum wählen']"
                            prepend-inner-icon="mdi-calendar-end"
                            variant="outlined"
                            density="comfortable"
                            bg-color="grey-darken-3"
                            class="field-item"
                        ></v-text-field>
                    </v-col>

                    <v-col cols="12" sm="4">
                        <v-checkbox
                            v-model="newVacation.reported"
                            label="Gemeldet"
                            color="primary"
                            hide-details
                            class="mt-2"
                        ></v-checkbox>
                    </v-col>

                    <v-col cols="12" sm="8">
                        <v-text-field
                            v-model="newVacation.other"
                            label="Grund / Bemerkung"
                            required
                            :rules="[v => !!v || 'Bitte Grund angeben']"
                            prepend-inner-icon="mdi-text-box"
                            variant="outlined"
                            density="comfortable"
                            bg-color="grey-darken-3"
                            class="field-item"
                        ></v-text-field>
                    </v-col>
                </v-row>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions class="pa-4">
                <v-spacer></v-spacer>
                <v-btn
                    color="grey-darken-1"
                    variant="text"
                    @click="newVacationDialog = false"
                    class="action-button mr-2"
                >
                    Abbrechen
                </v-btn>

                <v-btn
                    color="primary"
                    variant="elevated"
                    @click="saveVacation"
                    :disabled="
                        !newVacation.type ||
                        !newVacation.start ||
                        !newVacation.end ||
                        !newVacation.other
                    "
                    :loading="saving"
                    class="save-button"
                >
                    <v-icon class="mr-1">mdi-content-save</v-icon>
                    Speichern
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Confirm delete dialog removed - vacation deletion not supported -->
    <!-- Use stopVacation() to end active vacations instead -->

    <!-- Document/Notes Editors with fixed z-index -->
    <DocumentEditor
        class="document-editor-container"
        :selectedDocument="null"
        :selectedDocumentPreview="content"
        :selectedDocumentEmployeeDocument="null"
        :categories="[]"
        @close-document="resetSelectedDocument"
        persistent
    />

    <DocumentEditor
        class="document-editor-container"
        :selectedDocument="null"
        :selectedDocumentPreview="null"
        :selectedDocumentEmployeeDocument="selectedDocumentEmployeeNotes"
        :categories="[]"
        @update:selectedDocumentEmployeeDocument="updateSelectedDocumentEmployeeNotes"
        @close-document="resetSelectedDocumentEmployeeNotes"
        persistent
    />

    <!-- EmployeeForm Component (unverändert) -->
    <employee-form ref="employeeForm" :edit-mode="editMode" :hide-edit-button="true" />
</template>

<style scoped>
/* Card Styling */
.employee-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.employee-card:hover {
    box-shadow: var(--shadow-elevation);
}

.terminated-employee {
    background: var(--terminated-bg) !important;
    border: 1px solid rgba(244, 67, 54, 0.3);
    position: relative;
}

.terminated-employee::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ef4444, #b91c1c);
    z-index: 5;
}

.vacation-employee {
    background: var(--vacation-bg) !important;
    border: 1px solid rgba(76, 175, 80, 0.3);
}

/* Status Banner */
.status-banner {
    display: flex;
    align-items: center;
    padding: 6px 12px;
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    z-index: 3;
}

.terminated-employee .status-banner {
    background: rgba(239, 68, 68, 0.2);
}

.vacation-employee .status-banner {
    background: rgba(59, 130, 246, 0.15);
}

.status-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
}

.status-text {
    flex: 1;
    font-size: 0.85rem;
    font-weight: 500;
    display: flex;
    flex-direction: column;
}

.status-date {
    font-size: 0.75rem;
    opacity: 0.7;
    margin-top: 2px;
}

.status-sick {
    color: #f59e0b;
}

.status-action {
    margin-left: 8px;
}

/* Card Header */
.card-header {
    display: flex;
    padding: 16px;
    align-items: flex-start;
    position: relative;
    z-index: 2;
}

.employee-info {
    flex: 1;
}

.employee-name {
    font-size: 1.1rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    margin-bottom: 4px;
}

.name-text {
    transition: color 0.2s ease;
}

.name-text:hover {
    color: var(--v-theme-primary);
}

.employee-rank {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 4px;
}

.employee-department {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.6);
}

.department-item {
    margin-right: 4px;
}

.logo-container {
    margin-left: 12px;
}

.rank-logo {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Quick Actions Area */
.quick-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 12px 16px;
    position: relative;
    z-index: 2;
}

.quick-action-btn {
    text-transform: none;
    letter-spacing: normal;
    transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    border-radius: 8px;
    position: relative;
    overflow: hidden;
}

.quick-action-btn:hover {
    background: rgba(var(--v-theme-primary-rgb), 0.15);
    transform: translateY(-1px);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
}

.quick-action-btn:active {
    transform: translateY(0);
    box-shadow: none;
    transition-duration: 0.1s;
}

/* Vacation Alert - Now replaced by status banner */
.vacation-alert {
    display: none;
}

/* Expanded Content */
.expanded-content {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    z-index: 1;
}

.contact-info {
    display: flex;
    flex-wrap: wrap;
}

/* Detail Sections */
.detail-section {
    border-radius: 8px;
    overflow: hidden;
}

.section-title {
    display: flex;
    align-items: center;
    font-size: 0.95rem;
    font-weight: 500;
    margin-bottom: 12px;
    padding-bottom: 6px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--v-theme-primary);
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.detail-item {
    margin-bottom: 4px;
}

.detail-label {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 4px;
    font-weight: 500;
}

.detail-value {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.9);
}

.text-error {
    color: #ef4444;
}

/* Licenses Section */
.licenses-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.license-category {
    margin-bottom: 8px;
}

.license-category-title {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 6px;
    font-weight: 500;
}

.license-items {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.no-licenses {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.5);
    font-style: italic;
}

/* Dialog Styling */
.dialog-card {
    background: rgba(15, 23, 42, 0.8) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.card-toolbar {
    border-bottom: 1px solid var(--card-border);
}

.dialog-title {
    background: linear-gradient(90deg, #1e3a8a, #2563eb);
    color: white;
    padding: 16px;
}

/* Empty States */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
    color: #64748b;
    text-align: center;
}

/* Form Field Styling */
.field-item {
    border-radius: 8px;
    transition: all 0.2s ease;
}

.field-item:focus-within {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Action Buttons */
.action-button,
.save-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.3s ease;
}

.action-button:hover {
    opacity: 0.9;
}

.save-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(59, 130, 246, 0.2);
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

/* Close Button */
.close-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.3s ease;
}

.close-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(59, 130, 246, 0.2);
}

/* Promotion and Vacation Tables */
.promotion-table,
.vacation-table {
    border-radius: 8px;
    overflow: hidden;
}

.promotion-table :deep(th),
.vacation-table :deep(th) {
    background-color: rgba(30, 41, 59, 0.5) !important;
    color: rgba(255, 255, 255, 0.9) !important;
    font-weight: 500;
}

.promotion-table :deep(tr:hover),
.vacation-table :deep(tr:hover) {
    background-color: rgba(59, 130, 246, 0.08) !important;
}

/* Document Editor Container - Fixed z-index */
.document-editor-container {
    max-height: 90vh;
    position: relative; /* Ensure position property is set */
}

/* Make all document editor related overlays appear on top of everything */
:deep(.v-overlay) {
    z-index: 10000 !important; /* Higher than anything else */
}

:deep(.v-dialog) {
    z-index: 10001 !important; /* Even higher */
    position: relative;
}

:deep(.v-overlay__content) {
    z-index: 10001 !important; /* Match dialog z-index */
    position: relative;
}

:deep(.v-overlay__scrim) {
    z-index: 9999 !important;
}

/* Force the document editor and its contents above all other elements */
:deep(.document-editor) {
    position: relative;
    z-index: 10002 !important; /* Higher than dialog */
}

:deep(.v-card.editor-card) {
    z-index: 10003 !important; /* Higher than document-editor */
}

/* Style for document editor when it's open */
:global(.v-overlay-container) {
    z-index: 10004 !important; /* Top level container */
}

/* Fix any potential stacking context issues */
:deep(.quill-editor) {
    z-index: 10005 !important; /* Make editor components visible */
}

/* Override any Vuetify defaults that might be causing issues */
:deep(.v-application__overlay) {
    z-index: 10000 !important;
}

/* Responsive Adjustments */
@media (max-width: 600px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }

    .quick-actions {
        justify-content: center;
    }

    .card-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .logo-container {
        margin: 12px 0 0 0;
    }

    .employee-department {
        margin-bottom: 8px;
    }

    .status-banner {
        flex-direction: column;
        text-align: center;
        padding: 10px;
    }

    .status-icon {
        margin-right: 0;
        margin-bottom: 4px;
    }

    .status-text {
        margin-bottom: 8px;
    }

    .status-action {
        margin-left: 0;
    }
}

.editor-overlay{
	background-color: rgba(0, 0, 0, 0) !important;
}

.document-editor-container {
  max-height: unset !important;
  position: relative !important;
}

.editor-overlay {
  padding: 0px !important;
}
</style>
