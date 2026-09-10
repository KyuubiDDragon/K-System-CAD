<template>
    <v-dialog v-model="dialog" persistent max-width="800" class="employee-form-dialog">
        <v-card class="dialog-card">
            <v-toolbar density="compact" color="primary" class="card-toolbar">
                <v-toolbar-title class="text-subtitle-1">
                    <v-icon start size="18" class="mr-2">{{
                        !newEmployer ? 'mdi-account-edit' : 'mdi-account-plus'
                    }}</v-icon>
                    <span class="text-h6">
                        {{
                            !newEmployer
                                ? $t('employeeForm.editTitle')
                                : $t('employeeForm.addTitle')
                        }}
                    </span>
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <add-shortcut-button
                  v-if="!newEmployer && employee.id"
                  type="employee"
                  :resource-id="employee.id"
                  :title="employee.name"
                  :subtitle="employee.servicenumber || undefined"
                  icon="mdi-account-tie"
                  color="blue"
                />
            </v-toolbar>

            <v-card-text class="pa-4">
                <v-container fluid>
                    <!-- Grunddaten -->
                    <div class="form-section mb-4">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-account-details</v-icon>
                            {{ $t('employeeForm.basicData') }}
                        </div>

                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="employee.name"
                                    :label="$t('employeeForm.name') + '*'"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    required
                                    :rules="[requiredRule($t('employeeForm.name') + '*')]"
                                    prepend-inner-icon="mdi-account"
                                    class="field-item"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="employee.personalid"
                                    :label="$t('employeeForm.personalId')"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-card-account-details"
                                    class="field-item"
                                />
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="employee.birthdate"
                                    :label="$t('employeeForm.birthdate')"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    type="date"
                                    required
                                    prepend-inner-icon="mdi-calendar"
                                    class="field-item"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="employee.sidejob"
                                    :label="$t('employeeForm.sidejob')"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    type="text"
                                    prepend-inner-icon="mdi-account-hard-hat"
                                    class="field-item"
                                />
                            </v-col>
                        </v-row>
                    </div>

                    <!-- Kontaktinformationen -->
                    <div class="form-section mb-4">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-phone</v-icon>
                            {{ $t('employeeForm.contactInfo') }}
                        </div>

                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="employee.phonenumber"
                                    :label="$t('employeeForm.phonenumber')"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-cellphone"
                                    class="field-item"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="employee.mail"
                                    :label="$t('employeeForm.mail')"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    prepend-inner-icon="mdi-email"
                                    class="field-item"
                                />
                            </v-col>
                        </v-row>
                    </div>

                    <!-- Dienstinformationen -->
                    <div class="form-section mb-4">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-office-building</v-icon>
                            {{ $t('employeeForm.serviceInfo') }}
                        </div>

                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="employee.servicenumber"
                                    :label="$t('employeeForm.servicenumber') + '*'"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    required
                                    :rules="[requiredRule($t('employeeForm.servicenumber') + '*')]"
                                    prepend-inner-icon="mdi-badge-account-horizontal"
                                    class="field-item"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="employee.bankaccount"
                                    :label="$t('employeeForm.bankaccount')"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    type="text"
                                    prepend-inner-icon="mdi-bank"
                                    class="field-item"
                                />
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-select
                                    v-model="employee.company"
                                    :items="$props.companies"
                                    item-value="id"
                                    item-title="name"
                                    :label="$t('employeeForm.company')"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    multiple
                                    chips
                                    closable-chips
                                    prepend-inner-icon="mdi-domain"
                                    class="field-item"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-select
                                    v-model="employee.department"
                                    :items="$props.departments"
                                    item-value="id"
                                    item-title="name"
                                    :label="$t('employeeForm.department')"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    multiple
                                    chips
                                    closable-chips
                                    prepend-inner-icon="mdi-office-building-outline"
                                    class="field-item"
                                />
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-select
                                    v-model="employee.rankId"
                                    :items="$props.ranks"
                                    item-value="id"
                                    item-title="name"
                                    :label="$t('employeeForm.rank') + '*'"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    :rules="[requiredRule($t('employeeForm.rank') + '*')]"
                                    chips
                                    required
                                    prepend-inner-icon="mdi-medal"
                                    class="field-item"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-select
                                    v-model="employee.license"
                                    :items="$props.licenses"
                                    item-value="id"
                                    item-title="name"
                                    :label="$t('employeeForm.license')"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    multiple
                                    chips
                                    closable-chips
                                    prepend-inner-icon="mdi-license"
                                    class="field-item"
                                />
                            </v-col>
                        </v-row>
                    </div>

                    <!-- Ein-/Austrittsdaten -->
                    <div class="form-section">
                        <div class="section-title">
                            <v-icon size="small" class="mr-1">mdi-calendar-clock</v-icon>
                            {{ $t('employeeForm.entryExit') }}
                        </div>

                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="employee.entrydate"
                                    :label="$t('employeeForm.entrydate') + '*'"
                                    variant="outlined"
                                    density="comfortable"
                                    color="primary"
                                    bg-color="grey-darken-3"
                                    type="date"
                                    required
                                    :rules="[requiredRule($t('employeeForm.entrydate') + '*')]"
                                    prepend-inner-icon="mdi-calendar-plus"
                                    class="field-item"
                                />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <div class="terminated-group">
                                    <v-checkbox
                                        v-model="employee.is_terminated"
                                        :label="$t('employeeForm.terminated')"
                                        color="error"
                                        hide-details
                                        @change="updateLeaveDate"
                                        class="terminated-checkbox mb-2"
                                    ></v-checkbox>

                                    <v-text-field
                                        v-model="employee.leavedate"
                                        :label="$t('employeeForm.leavedate')"
                                        variant="outlined"
                                        density="comfortable"
                                        color="error"
                                        bg-color="grey-darken-3"
                                        type="date"
                                        :disabled="!employee.is_terminated"
                                        prepend-inner-icon="mdi-calendar-remove"
                                        class="field-item"
                                    />
                                </div>
                            </v-col>
                        </v-row>
                    </div>
                </v-container>
            </v-card-text>

            <v-divider></v-divider>

            <v-card-actions class="pa-4">
                <v-spacer></v-spacer>
                <v-btn
                    color="grey-darken-1"
                    variant="text"
                    @click="dialog = false"
                    class="action-button mr-2"
                >
                    {{ $t('employeeForm.cancel') }}
                </v-btn>

                <v-btn
                    color="primary"
                    variant="elevated"
                    @click="submitEmployee"
                    class="save-button"
                >
                    <v-icon class="mr-1">mdi-content-save</v-icon>
                    {{
                        !newEmployer
                            ? $t('employeeForm.saveChanges')
                            : $t('employeeForm.save')
                    }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup lang="ts">
import { defineComponent, ref, onMounted, watch } from 'vue';
import { type Company, type Department, type Rank, type License } from '@/types/Members';
import { apiClientAuth } from '@/api';
import AddShortcutButton from '@/components/shortcuts/AddShortcutButton.vue';

type Employee = {
    id?: number;
    name: string;
    personalid: string;
    phonenumber: string;
    birthdate: string;
    mail: string;
    servicenumber: string;
    entrydate: string;
    bankaccount: string;
    leavedate: string | null;
    sidejob: string;
    company: string[];
    department: string[];
    is_terminated: number;
    rank: string;
    rankId: number;
    license: string[];
    companies?: Record<string, Company>;
    departments?: Record<string, Department>;
    licenses?: Record<string, License>;
};

// Props definieren
const props = defineProps<{
    editMode?: boolean;
    hideEditButton?: boolean;
    initialEmployee?: Employee;
    companies?: any[];
    departments?: any[];
    ranks?: Rank[];
    licenses?: any[];
}>();

// Defaults setzen
const {
    editMode = false,
    hideEditButton = false,
    initialEmployee = {
        name: '',
        personalid: '',
        phonenumber: '',
        birthdate: '',
        mail: '',
        servicenumber: '',
        bankaccount: '',
        entrydate: '',
        leavedate: '',
        sidejob: '',
        company: [],
        companies: [],
        department: [],
        departments: [],
        rank: '',
        rankId: 9,
        license: [],
        licenses: [],
        is_terminated: false,
    },
    companies = [],
    departments = [],
    ranks = [],
    licenses = [],
} = props;

const dialog = ref(false);
const employee = ref({ ...props.initialEmployee });
const newEmployer = ref(false);
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
const emit = defineEmits(['updateEmployeeList']);

onMounted(async () => {
    if (props.editMode) {
        employee.value = { ...props.initialEmployee };
    }
    employee.value.is_terminated = employee.value.leavedate ? 1 : 0;

    initializeLeaveDate();
});

const initializeLeaveDate = () => {
    if (employee.value.is_terminated && employee.value.leavedate) {
        const leaveDate = new Date(employee.value.leavedate);
        employee.value.leavedate = `${leaveDate.getFullYear()}-${(leaveDate.getMonth() + 1).toString().padStart(2, '0')}-${leaveDate.getDate().toString().padStart(2, '0')}`;
    } else if (employee.value.is_terminated) {
        const currentDate = new Date();
        employee.value.leavedate = `${currentDate.getFullYear()}-${(currentDate.getMonth() + 1).toString().padStart(2, '0')}-${currentDate.getDate().toString().padStart(2, '0')}`;
    } else {
        employee.value.leavedate = '';
    }
};

const validateEmployee = () => {
    console.log(employee.value);
    if (
        !employee.value.name ||
        !employee.value.servicenumber ||
        !employee.value.entrydate ||
        !employee.value.rankId
    ) {
        return false;
    }
    return true;
};

const submitEmployee = async () => {
    if (!validateEmployee()) {
        console.error('Required fields are not filled');
        return;
    }

    const employeeData = {
        id: employee.value.id,
        name: employee.value.name,
        personalid: employee.value.personalid,
        phonenumber: employee.value.phonenumber,
        birthdate: employee.value.birthdate,
        mail: employee.value.mail,
        servicenumber: employee.value.servicenumber,
        bankaccount: employee.value.bankaccount,
        entrydate: employee.value.entrydate,
        leavedate: employee.value.leavedate || null,
        sidejob: employee.value.sidejob,
        company: employee.value.company,
        department: employee.value.department,
        rankId: employee.value.rankId,
        license: employee.value.license,
        is_terminated: employee.value.is_terminated,
    };

    console.log('Submitting employee data:', employeeData);

    try {
        if (!newEmployer.value) {
            const response = await apiClientAuth.post(
                '/employee/?action=editEmployee',
                employeeData
            );
            console.log('Edit response:', response.data);
        } else {
            const response = await apiClientAuth.post(
                '/employee/?action=createEmployee',
                employeeData
            );
            console.log('Create response:', response.data);
        }
        emit('updateEmployeeList');
        dialog.value = false;
    } catch (error) {
        console.error('Error adding/updating employee:', error);
    }
};

const updateLeaveDate = () => {
    if (employee.value.is_terminated) {
        if (!employee.value.leavedate) {
            const currentDate = new Date();
            employee.value.leavedate = currentDate.toISOString().slice(0, 10);
        }
    } else {
        employee.value.leavedate = '';
    }
};

const openDialog = (employeeData: any | null, isNew: boolean) => {
    console.log('openDialog called with:', { employeeData, isNew });

    if (isNew) {
        employee.value = {
            name: '',
            personalid: '',
            phonenumber: '',
            birthdate: '',
            mail: '',
            servicenumber: '',
            bankaccount: '',
            entrydate: new Date().toISOString().slice(0, 10),
            leavedate: '',
            sidejob: '',
            company: [],
            department: [],
            rankId: 0,
            license: [],
            is_terminated: 0,
        };

        const rankRecruit = ranks.find(
            rankRecruit =>
                rankRecruit.name == 'Recruit' ||
                rankRecruit.name == 'Aspirant' ||
                rankRecruit.name == 'Trainee'
        );

        if (rankRecruit) {
            employee.value.rankId = rankRecruit.id;
        } else if (ranks.length > 0) {
            employee.value.rankId = ranks[0].id;
        }
    } else if (employeeData) {
        console.log('Setting employee data:', employeeData);

        employee.value = {
            id: employeeData.id,
            name: employeeData.name || '',
            personalid: employeeData.personalid || '',
            phonenumber: employeeData.phonenumber || '',
            birthdate: employeeData.birthdate || '',
            mail: employeeData.mail || '',
            servicenumber: employeeData.servicenumber || '',
            bankaccount: employeeData.bankaccount || '',
            entrydate: employeeData.entrydate || '',
            leavedate: employeeData.leavedate || '',
            sidejob: employeeData.sidejob || '',
            is_terminated: employeeData.is_terminated == 1 ? 1 : 0,
            rank: employeeData.rank || '',
            rankId: employeeData.rankId || 0,
            company: Array.isArray(employeeData.company) ? [...employeeData.company] : [],
            department: Array.isArray(employeeData.department) ? [...employeeData.department] : [],
            license: Array.isArray(employeeData.license) ? [...employeeData.license] : []
        };

        if (!employee.value.company.length && employeeData.companies) {
            const companies = employeeData?.companies || {};
            if (Array.isArray(companies)) {
                employee.value.company = companies.map(c => c.id);
            } else {
                employee.value.company = Object.keys(companies).length > 0
                    ? (props.companies as Company[])
                        .filter(company => company.id in companies)
                        .map(company => company.id)
                    : [];
            }
        }

        if (!employee.value.department.length && employeeData.departments) {
            const departments = employeeData?.departments || {};
            if (Array.isArray(departments)) {
                employee.value.department = departments.map(d => d.id);
            } else {
                employee.value.department = Object.keys(departments).length > 0
                    ? (props.departments as Department[])
                        .filter(department => department.id in departments)
                        .map(department => department.id)
                    : [];
            }
        }

        if (!employee.value.license.length && employeeData.licenses) {
            const licenses = employeeData?.licenses || {};
            if (Array.isArray(licenses)) {
                employee.value.license = licenses.map(l => l.id);
            } else {
                employee.value.license = Object.keys(licenses).length > 0
                    ? (props.licenses as License[])
                        .filter(license => license.id in licenses)
                        .map(license => license.id)
                    : [];
            }
        }

        console.log('After processing arrays:', {
            company: employee.value.company,
            department: employee.value.department,
            license: employee.value.license
        });
    }

    newEmployer.value = isNew;

    initializeLeaveDate();

    if (employee.value.birthdate) {
        try {
            const birthDate = new Date(employee.value.birthdate);
            employee.value.birthdate = birthDate.toISOString().slice(0, 10);
        } catch (e) {
            console.error('Error formatting birthdate:', e);
        }
    }

    if (employee.value.entrydate) {
        try {
            const entryDate = new Date(employee.value.entrydate);
            employee.value.entrydate = entryDate.toISOString().slice(0, 10);
        } catch (e) {
            console.error('Error formatting entrydate:', e);
        }
    }

    dialog.value = true;

    console.log('Final employee object:', JSON.parse(JSON.stringify(employee.value)));
};

defineExpose({
    openDialog,
});

watch(
    () => employee.value.is_terminated,
    (newValue, oldValue) => {
        updateLeaveDate();
    }
);
</script>

<style scoped>

.employee-form-dialog :deep(.v-overlay__content) {
    border-radius: 16px;
    overflow: hidden;
}

.dialog-card {
    background: var(--k-surface) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.card-toolbar {
    border-bottom: 1px solid var(--card-border);
}

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

.field-item {
    border-radius: 8px;
    transition: all var(--transition-timing);
}

.field-item:focus-within {
    transform: var(--button-hover-translate);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.terminated-group {
    display: flex;
    flex-direction: column;
}

.terminated-checkbox {
    margin-bottom: 6px;
}

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
</style>
