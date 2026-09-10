<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Rank, Company, Department, License } from '@/types/Members';

const { t } = useI18n();

interface Props {
    ranks: Rank[];
    companies: Company[];
    departments: Department[];
    licenses: License[];
    canEdit: boolean;
    canDelete: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    edit: [data: any];
    editNotes: [employee: any];
    addVacation: [employee: any];
    stopVacation: [vacationId: number];
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
    if (!date) return '-';
    const [datePart] = date.split(' ');
    const [year, month, day] = datePart.split('-');
    return `${day}.${month}.${year}`;
}

// Table headers
const headers = [
    { title: t('employeeTable.employee'), key: 'name', sortable: true, width: '250px' },
    { title: t('employeeTable.serviceNumber'), key: 'servicenumber', sortable: true, width: '120px' },
    { title: t('employeeTable.rank'), key: 'rank', sortable: true, width: '150px' },
    { title: t('employeeTable.organization'), key: 'organization', sortable: false, width: '200px' },
    { title: t('employeeTable.contact'), key: 'contact', sortable: false, width: '180px' },
    { title: t('employeeTable.licenses'), key: 'licenses', sortable: false, width: '200px' },
    { title: t('employeeTable.status'), key: 'status', sortable: true, width: '140px' },
    { title: t('employeeTable.actions'), key: 'actions', sortable: false, width: '140px', align: 'center' as const },
];

/**
 * Alle Mitarbeiter aus allen Raengen in einer Liste. Der Rang wandert dabei
 * vom Ueberschrift-Dasein in eine Spalte, damit quer sortiert und gefiltert
 * werden kann.
 */
const alleMitarbeiter = computed(() =>
    (props.ranks ?? []).flatMap((rank: any) =>
        (rank.employees ?? []).map((e: any) => ({ ...e, rankName: rank.name, rankImage: rank.rankImage })),
    ),
);

/** Zuschaltbare Filter. Ohne Auswahl sind alle Mitarbeiter sichtbar. */
const filter = ref([
    { key: 'active', label: t('employeeTable.filterActive'), aktiv: false },
    { key: 'absent', label: t('employeeTable.filterAbsent'), aktiv: false },
    { key: 'leaving', label: t('employeeTable.filterLeaving'), aktiv: false },
]);

const sichtbareMitarbeiter = computed(() => {
    const aktiv = filter.value.filter(f => f.aktiv).map(f => f.key);
    if (!aktiv.length) return alleMitarbeiter.value;

    return alleMitarbeiter.value.filter((e: any) => {
        if (aktiv.includes('absent') && !e.is_absent) return false;
        if (aktiv.includes('leaving') && !e.leavedate) return false;
        if (aktiv.includes('active') && (e.is_absent || e.is_terminated)) return false;
        return true;
    });
});

// Get current vacation for employee
const getCurrentVacation = (employee: any) => {
    if (!employee.vacations) return null;
    const today = new Date();
    const vacations = Object.values(employee.vacations);
    return vacations.find((vacation: any) => {
        const startDate = new Date(vacation.start);
        const endDate = new Date(vacation.end);
        return today >= startDate && today <= endDate;
    });
};

// Get status info
const getStatusInfo = (employee: any) => {
    if (employee.is_terminated) {
        return {
            text: t('employeeTable.terminated'),
            color: 'error',
            icon: 'mdi-account-cancel'
        };
    }

    const currentVacation = getCurrentVacation(employee);
    if (currentVacation) {
        const isSick = currentVacation.reason.toLowerCase().includes('krank');
        return {
            text: isSick ? t('employeeTable.sick') : t('employeeTable.vacation'),
            color: isSick ? 'warning' : 'info',
            icon: isSick ? 'mdi-medical-bag' : 'mdi-beach',
            until: formatDate(currentVacation.end)
        };
    }

    return {
        text: t('employeeTable.active'),
        color: 'success',
        icon: 'mdi-check-circle'
    };
};

// Filter licenses by type
const filteredLicenses = (employee: any, filter: string) => {
    if (!employee.licenses) return [];
    const licenses = Object.values(employee.licenses);
    return licenses.filter((license: any) => license && license.type === filter);
};

// Handle edit employee
const handleEdit = (employee: any, rank: Rank) => {
    emit('edit', {
        employee,
        rank
    });
};
</script>

<template>
    <div class="employee-table-view">
        <!--
            Eine Tabelle statt einer je Rang. Vorher standen neun Tabellen
            untereinander, jede mit eigenem Kopf und eigener Fusszeile - der
            Rang war die Ueberschrift statt eine Spalte, sodass man weder
            sortieren noch quer filtern konnte.
        -->
        <div class="rank-table-section">
            <!-- Filterleiste: Zustaende zuschalten, Anzahl rechts. -->
            <div class="k-toolbar">
                <v-chip
                    v-for="f in filter"
                    :key="f.key"
                    size="small"
                    variant="outlined"
                    class="k-filter-chip"
                    :class="{ 'is-active': f.aktiv }"
                    @click="f.aktiv = !f.aktiv"
                >
                    {{ f.label }}
                </v-chip>

                <span class="k-toolbar__spacer"></span>

                <span class="k-toolbar__count">
                    {{ t('employeeTable.countOf', { n: sichtbareMitarbeiter.length, total: alleMitarbeiter.length }) }}
                </span>
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="sichtbareMitarbeiter"
                class="employee-data-table"
                :items-per-page="25"
                density="compact"
                hover
            >
                <!-- Employee Name Column -->
                <template #item.name="{ item }">
                    <div class="employee-info-cell">
                        <v-avatar size="40" class="employee-avatar" rounded="lg">
                            <v-img :src="getImage(rank.rankImage)" />
                        </v-avatar>
                        <div class="employee-details">
                            <div class="employee-name-table">{{ item.name }}</div>
                            <div class="employee-id-table">#{{ item.servicenumber }}</div>
                        </div>
                    </div>
                </template>

                <!-- Service Number Column -->
                <template #item.servicenumber="{ item }">
                    <span class="text-caption">{{ item.servicenumber }}</span>
                </template>

                <!-- Rank Column -->
                <template #item.rank="{ item }">
                    <span class="text-caption">{{ rank.name }}</span>
                </template>

                <!-- Organization Column -->
                <template #item.organization="{ item }">
                    <div class="organization-cell">
                        <div v-if="item.companies" class="text-caption">
                            <template v-for="(company, index) in Object.values(item.companies)" :key="index">
                                {{ company.name }}<span v-if="index < Object.values(item.companies).length - 1">, </span>
                            </template>
                        </div>
                        <div v-if="item.departments" class="text-caption text-grey mt-1">
                            <template v-for="(dept, index) in Object.values(item.departments)" :key="index">
                                {{ dept.name }}<span v-if="index < Object.values(item.departments).length - 1">, </span>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Contact Column -->
                <template #item.contact="{ item }">
                    <div class="contact-cell">
                        <div class="contact-item">
                            <v-icon size="x-small" color="primary">mdi-phone</v-icon>
                            <span class="text-caption">{{ item.phonenumber }}</span>
                        </div>
                        <div class="contact-item">
                            <v-icon size="x-small" color="primary">mdi-email</v-icon>
                            <span class="text-caption text-truncate" style="max-width: 140px;">{{ item.mail }}</span>
                        </div>
                    </div>
                </template>

                <!-- Licenses Column -->
                <template #item.licenses="{ item }">
                    <div class="license-pills">
                        <v-chip
                            v-for="license in filteredLicenses(item, 'driverlicense').slice(0, 3)"
                            :key="license.id"
                            size="x-small"
                            color="success"
                            variant="tonal"
                            class="mr-1 mb-1"
                        >
                            🚗 {{ license.name || license.license }}
                        </v-chip>
                        <v-chip
                            v-for="license in filteredLicenses(item, 'medic').slice(0, 2)"
                            :key="license.id"
                            size="x-small"
                            color="error"
                            variant="tonal"
                            class="mr-1 mb-1"
                        >
                            🏥 {{ license.name || license.license }}
                        </v-chip>
                        <v-chip
                            v-if="Object.values(item.licenses || {}).length > 5"
                            size="x-small"
                            variant="outlined"
                            class="mr-1 mb-1"
                        >
                            +{{ Object.values(item.licenses).length - 5 }}
                        </v-chip>
                    </div>
                </template>

                <!-- Status Column -->
                <template #item.status="{ item }">
                    <div class="status-cell">
                        <v-chip
                            :color="getStatusInfo(item).color"
                            size="small"
                            variant="tonal"
                            :prepend-icon="getStatusInfo(item).icon"
                        >
                            {{ getStatusInfo(item).text }}
                        </v-chip>
                        <div v-if="getStatusInfo(item).until" class="text-caption text-grey mt-1">
                            {{ t('employeeTable.until') }} {{ getStatusInfo(item).until }}
                        </div>
                    </div>
                </template>

                <!-- Actions Column -->
                <template #item.actions="{ item }">
                    <div class="table-actions">
                        <v-tooltip text="Notizen" location="top">
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-bind="tooltipProps"
                                    icon
                                    size="small"
                                    variant="text"
                                    color="primary"
                                    @click="emit('editNotes', item)"
                                >
                                    <v-icon size="small">mdi-note-edit</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>

                        <v-tooltip
                            :text="getCurrentVacation(item) ? 'Urlaub beenden' : 'Urlaub hinzufügen'"
                            location="top"
                        >
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-if="canEdit"
                                    v-bind="tooltipProps"
                                    icon
                                    size="small"
                                    variant="text"
                                    :color="getCurrentVacation(item) ? 'warning' : 'primary'"
                                    @click="getCurrentVacation(item) ? emit('stopVacation', getCurrentVacation(item).id) : emit('addVacation', item)"
                                >
                                    <v-icon size="small">
                                        {{ getCurrentVacation(item) ? 'mdi-calendar-remove' : 'mdi-calendar-plus' }}
                                    </v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>

                        <v-tooltip text="Bearbeiten" location="top">
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-if="canEdit"
                                    v-bind="tooltipProps"
                                    icon
                                    size="small"
                                    variant="text"
                                    color="primary"
                                    @click="handleEdit(item, rank)"
                                >
                                    <v-icon size="small">mdi-pencil</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>
                    </div>
                </template>
                <!-- Leerzustand innerhalb der Tabelle statt als eigene Karte -->
                <template #no-data>
                    <div class="no-data">
                        <v-icon size="20" color="grey">mdi-account-off</v-icon>
                        <p>{{ t('employeeTable.noEmployees') }}</p>
                    </div>
                </template>
            </v-data-table>
        </div>
    </div>
</template>

<style scoped>
.employee-table-view {
    width: 100%;
}

.rank-table-section {
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid var(--k-line);
    border-radius: 12px;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.rank-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: rgba(30, 41, 59, 0.6);
    border-bottom: 1px solid rgba(59, 130, 246, 0.2);
}

.rank-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #e2e8f0;
    display: flex;
    align-items: center;
}

.employee-data-table {
    background: transparent !important;
}

.employee-data-table :deep(th) {
    background-color: rgba(30, 41, 59, 0.5) !important;
    color: var(--k-ink) !important;
    font-weight: 600 !important;
    font-size: 0.8rem !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.employee-data-table :deep(tr:hover) {
    background-color: rgba(59, 130, 246, 0.08) !important;
}

.employee-data-table :deep(td) {
    border-bottom: 1px solid var(--k-line) !important;
    padding: 18px 12px !important;
}

.employee-data-table :deep(th) {
    padding: 18px 12px !important;
}

.employee-info-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.employee-avatar {
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
    border-radius: 8px !important;
    background: linear-gradient(135deg, #1e3a8a, var(--k-accent));
}

.employee-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.employee-name-table {
    font-weight: 600;
    font-size: 0.875rem;
    color: #e2e8f0;
}

.employee-id-table {
    font-size: 0.75rem;
    color: rgb(var(--v-theme-primary));
    font-weight: 500;
}

.organization-cell {
    line-height: 1.4;
}

.contact-cell {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.license-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.status-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.table-actions {
    display: flex;
    gap: 4px;
    justify-content: center;
}

/* Responsive */
@media (max-width: 1200px) {
    .employee-data-table :deep(th),
    .employee-data-table :deep(td) {
        font-size: 0.75rem !important;
        padding: 8px !important;
    }
}
</style>
