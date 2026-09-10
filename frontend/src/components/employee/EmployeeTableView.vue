<script setup lang="ts">
/**
 * Personalübersicht als eine Tabelle.
 *
 * Der Entwurf zeigt hier das Kernstück des Systems: Filter als Chips über der
 * Tabelle, Anzahl und Spaltenauswahl rechts daneben, eine Auswahlspalte vorn
 * und die Massenaktionen in der Fußzeile — sichtbar erst, wenn etwas
 * ausgewählt ist. Bedeutung steht links, Zahlen und Daten rechts.
 *
 * Drei Spalten führt `kdd_employee` zwar, sie gehören aber nicht in eine Liste,
 * die den ganzen Tag offen steht: Bankverbindung, Geburtsdatum und
 * Personalausweis sind Teil der Personalakte. Sie stehen deshalb im
 * Spaltenmenü, aber nicht ab Werk in der Tabelle.
 */
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Rank, Company, Department, License } from '@/types/Members';
import KTableToolbar from '@/components/table/KTableToolbar.vue';
import KBulkBar from '@/components/table/KBulkBar.vue';
import { useTableColumns, type KColumn } from '@/composables/useTableColumns';
import { useTableFilters } from '@/composables/useTableFilters';
import { exportRowsAsCsv } from '@/utils/tableExport';
import KMetricRow, { type KMetric } from '@/components/layout/KMetricRow.vue';
import KContextPanel from '@/components/layout/KContextPanel.vue';

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
    if (!date) return '—';
    const [datePart] = String(date).split(' ');
    const [year, month, day] = datePart.split('-');
    if (!year || !month || !day) return String(date);
    return `${day}.${month}.${year}`;
}

/**
 * Alle Mitarbeiter aus allen Rängen in einer Liste. Der Rang wandert dabei
 * vom Überschrift-Dasein in eine Spalte, damit quer sortiert und gefiltert
 * werden kann.
 */
const alleMitarbeiter = computed(() =>
    (props.ranks ?? []).flatMap((rank: any) =>
        (rank.employees ?? []).map((e: any) => ({
            ...e,
            rankName: rank.name,
            rankImage: rank.rankImage,
            rankOrder: rank.sort_order ?? 999,
            _rank: rank,
        })),
    ),
);

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

/* ------------------------------------------------------------------
   Filter — die Chips über der Tabelle
   ------------------------------------------------------------------ */

/**
 * „Aktiv" steht ab Werk an: ausgeschiedene Mitarbeiter stören in der Liste,
 * die täglich offen ist. Wer sie sucht, schaltet den Filter ab — sie sind
 * dann sofort wieder da, ohne dass man eine zweite Ansicht braucht.
 */
const filters = useTableFilters(
    alleMitarbeiter,
    [
        {
            key: 'active',
            label: t('employeeTable.filterActive'),
            on: true,
            test: (e: any) => !e.is_terminated,
        },
        {
            key: 'absent',
            label: t('employeeTable.filterAbsent'),
            test: (e: any) => !!getCurrentVacation(e),
        },
        {
            key: 'leaving',
            label: t('employeeTable.filterLeaving'),
            test: (e: any) => !!e.leavedate,
        },
    ],
    [
        { field: 'rankName', label: t('employeeTable.rank') },
        {
            field: 'jobrole_name',
            label: t('employeeTable.jobrole'),
            emptyLabel: t('employeeTable.withoutJobrole'),
        },
    ],
);

const sichtbareMitarbeiter = computed(() =>
    [...filters.filtered.value].sort(
        (a: any, b: any) =>
            (a.rankOrder ?? 999) - (b.rankOrder ?? 999) ||
            String(a.name ?? '').localeCompare(String(b.name ?? ''), 'de'),
    ),
);

/* ------------------------------------------------------------------
   Spalten — was ab Werk steht und was sich zuschalten lässt
   ------------------------------------------------------------------ */

const allHeaders = computed<KColumn[]>(() => [
    { title: t('employeeTable.employee'), key: 'name', sortable: true, width: '230px', locked: true },
    { title: t('employeeTable.serviceNumber'), key: 'servicenumber', sortable: true, width: '86px' },
    { title: t('employeeTable.rank'), key: 'rankName', sortable: true, width: '150px' },
    { title: t('employeeTable.jobrole'), key: 'jobrole_name', sortable: true, width: '150px', optional: true },
    { title: t('employeeTable.organization'), key: 'organization', sortable: false, width: '180px' },
    { title: t('employeeTable.phone'), key: 'phonenumber', sortable: true, width: '120px' },
    { title: t('employeeTable.mail'), key: 'mail', sortable: true, width: '190px', optional: true },
    { title: t('employeeTable.entrydate'), key: 'entrydate', sortable: true, width: '104px', align: 'end' },
    { title: t('employeeTable.leavedate'), key: 'leavedate', sortable: true, width: '104px', align: 'end', optional: true },
    { title: t('employeeTable.licenses'), key: 'licenses', sortable: false, width: '180px', optional: true },
    // Teil der Personalakte — zuschaltbar, aber nicht ab Werk sichtbar.
    { title: t('employeeTable.birthdate'), key: 'birthdate', sortable: true, width: '104px', align: 'end', optional: true },
    { title: t('employeeTable.personalid'), key: 'personalid', sortable: true, width: '120px', optional: true },
    { title: t('employeeTable.bankaccount'), key: 'bankaccount', sortable: true, width: '150px', optional: true },
    { title: t('employeeTable.status'), key: 'status', sortable: true, width: '134px' },
    { title: t('employeeTable.actions'), key: 'actions', sortable: false, width: '134px', align: 'end', locked: true },
]);

const columns = useTableColumns('employees', allHeaders);

/* ------------------------------------------------------------------
   Kennzahlen über der Liste
   ------------------------------------------------------------------ */

/**
 * Der Entwurf zeigt die Kennzahlenreihe nicht nur auf dem Dashboard, sondern
 * über dem Arbeitsbereich. Der Bestand steht dann da, bevor man in die Liste
 * schaut — die Liste beantwortet danach das „welche", nicht mehr das
 * „wie viele".
 *
 * Gezählt wird auf dem ganzen Bestand, nicht auf der gefilterten Liste: eine
 * Kennzahl, die sich mit jedem Chip ändert, ist keine Kennzahl mehr.
 */
const kMetrics = computed<KMetric[]>(() => {
    const all = alleMitarbeiter.value;
    const leaving = all.filter((e: any) => !!e.leavedate).length;
    const absent = all.filter((e: any) => !!getCurrentVacation(e)).length;
    const ranks = new Set(all.map((e: any) => e.rankName));
    const rankCount = (props.ranks ?? []).length;

    return [
        {
            cap: t('employeeTable.metricTotal'),
            val: all.filter((e: any) => !e.is_terminated).length,
            unit: `/ ${all.length}`,
            sub: t('employeeTable.metricLeavingSub', { n: leaving }),
        },
        {
            cap: t('employeeTable.metricAbsent'),
            val: absent,
            sub: t('employeeTable.metricOnDutySub', { n: all.length - absent }),
        },
        {
            cap: t('employeeTable.metricRanks'),
            val: ranks.size,
            unit: `/ ${rankCount}`,
            sub: t('employeeTable.metricVacantSub', { n: Math.max(0, rankCount - ranks.size) }),
        },
    ];
});

/* ------------------------------------------------------------------
   Auswahl und Massenaktionen
   ------------------------------------------------------------------ */

const selected = ref<any[]>([]);

const selectedEmployees = computed(() =>
    sichtbareMitarbeiter.value.filter((e: any) => selected.value.includes(e.id)),
);

function clearSelection() {
    selected.value = [];
}

// Get status info
const getStatusInfo = (employee: any) => {
    if (employee.is_terminated) {
        return {
            text: t('employeeTable.terminated'),
            color: 'error',
            icon: 'mdi-account-cancel',
        };
    }

    const currentVacation = getCurrentVacation(employee);
    if (currentVacation) {
        const isSick = currentVacation.reason?.toLowerCase().includes('krank');
        return {
            text: isSick ? t('employeeTable.sick') : t('employeeTable.vacation'),
            color: isSick ? 'warning' : 'info',
            icon: isSick ? 'mdi-medical-bag' : 'mdi-beach',
            until: formatDate(currentVacation.end),
        };
    }

    if (employee.leavedate) {
        return {
            text: t('employeeTable.leavingOn', { date: formatDate(employee.leavedate) }),
            color: 'warning',
            icon: 'mdi-calendar-alert',
        };
    }

    return {
        text: t('employeeTable.active'),
        color: 'success',
        icon: 'mdi-check-circle',
    };
};

/** Zellwert einer Spalte als reiner Text — für die Ausgabe. */
function cellText(item: any, key: string): string {
    switch (key) {
        case 'organization':
            return Object.values(item.companies ?? {})
                .map((c: any) => c.name)
                .join(' / ');
        case 'licenses':
            return Object.values(item.licenses ?? {})
                .map((l: any) => l.name || l.license)
                .join(' / ');
        case 'status':
            return getStatusInfo(item).text;
        case 'entrydate':
        case 'leavedate':
        case 'birthdate':
            return item[key] ? formatDate(item[key]) : '';
        default:
            return item[key] == null ? '' : String(item[key]);
    }
}

/**
 * Die Ausgabe folgt der Ansicht: genau die Spalten, die gerade sichtbar sind,
 * in genau der Reihenfolge. Was man sieht, gibt man auch aus.
 */
function exportSelection() {
    exportRowsAsCsv(
        columns.visible.value,
        selectedEmployees.value.length ? selectedEmployees.value : sichtbareMitarbeiter.value,
        { name: 'mitarbeiter', cell: cellText },
    );
}

// Filter licenses by type
const filteredLicenses = (employee: any, type: string) => {
    if (!employee.licenses) return [];
    const licenses = Object.values(employee.licenses);
    return licenses.filter((license: any) => license && license.type === type);
};

// Handle edit employee
const handleEdit = (employee: any) => {
    emit('edit', {
        employee,
        rank: employee._rank,
    });
};
</script>

<template>
    <div class="employee-table-view">
        <!-- Bestand zuerst, dann die Liste. -->
        <KMetricRow :metrics="kMetrics" />

        <!--
            Eine Tabelle statt einer je Rang. Vorher standen neun Tabellen
            untereinander, jede mit eigenem Kopf und eigener Fusszeile - der
            Rang war die Ueberschrift statt eine Spalte, sodass man weder
            sortieren noch quer filtern konnte.
        -->
        <div class="rank-table-section">
            <KTableToolbar
                :filters="filters"
                :columns="columns"
                :shown="sichtbareMitarbeiter.length"
                :total="alleMitarbeiter.length"
                :noun="t('employeeTable.noun')"
            />

            <v-data-table
                v-model="selected"
                :headers="columns.visible.value"
                :items="sichtbareMitarbeiter"
                item-value="id"
                show-select
                class="employee-data-table"
                :items-per-page="25"
                density="compact"
                hover
                hide-default-footer
            >
                <!-- Employee Name Column -->
                <template #item.name="{ item }">
                    <div class="employee-info-cell">
                        <v-avatar size="26" class="employee-avatar" rounded="sm">
                            <v-img :src="getImage(item.rankImage)" />
                        </v-avatar>
                        <span class="employee-name-table">{{ item.name }}</span>
                    </div>
                </template>

                <!-- Kennungen stehen in Festbreite: #05 und #29 sind untereinander
                     sonst schwer zu unterscheiden. -->
                <template #item.servicenumber="{ item }">
                    <span class="k-mono">#{{ item.servicenumber }}</span>
                </template>

                <template #item.rankName="{ item }">
                    <span class="text-muted-cell">{{ item.rankName }}</span>
                </template>

                <template #item.jobrole_name="{ item }">
                    <span class="text-muted-cell">{{ item.jobrole_name || '—' }}</span>
                </template>

                <template #item.phonenumber="{ item }">
                    <span class="k-mono">{{ item.phonenumber || '—' }}</span>
                </template>

                <template #item.mail="{ item }">
                    <span class="text-muted-cell text-truncate d-inline-block" style="max-width: 180px">
                        {{ item.mail || '—' }}
                    </span>
                </template>

                <template #item.entrydate="{ item }">
                    <span class="k-mono">{{ formatDate(item.entrydate) }}</span>
                </template>

                <template #item.leavedate="{ item }">
                    <span class="k-mono">{{ item.leavedate ? formatDate(item.leavedate) : '—' }}</span>
                </template>

                <template #item.birthdate="{ item }">
                    <span class="k-mono">{{ item.birthdate ? formatDate(item.birthdate) : '—' }}</span>
                </template>

                <template #item.personalid="{ item }">
                    <span class="k-mono">{{ item.personalid || '—' }}</span>
                </template>

                <template #item.bankaccount="{ item }">
                    <span class="k-mono">{{ item.bankaccount || '—' }}</span>
                </template>

                <!-- Organization Column -->
                <template #item.organization="{ item }">
                    <div class="organization-cell">
                        <div v-if="item.companies" class="text-muted-cell">
                            <template v-for="(company, index) in Object.values(item.companies)" :key="index">
                                {{ company.name
                                }}<span v-if="index < Object.values(item.companies).length - 1">, </span>
                            </template>
                        </div>
                        <div v-if="item.departments" class="text-faint-cell">
                            <template v-for="(dept, index) in Object.values(item.departments)" :key="index">
                                {{ dept.name
                                }}<span v-if="index < Object.values(item.departments).length - 1">, </span>
                            </template>
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
                        >
                            {{ license.name || license.license }}
                        </v-chip>
                        <v-chip
                            v-for="license in filteredLicenses(item, 'medic').slice(0, 2)"
                            :key="license.id"
                            size="x-small"
                            color="error"
                            variant="tonal"
                        >
                            {{ license.name || license.license }}
                        </v-chip>
                        <v-chip
                            v-if="Object.values(item.licenses || {}).length > 5"
                            size="x-small"
                            variant="outlined"
                        >
                            +{{ Object.values(item.licenses).length - 5 }}
                        </v-chip>
                    </div>
                </template>

                <!-- Status Column -->
                <template #item.status="{ item }">
                    <div class="status-cell">
                        <v-chip :color="getStatusInfo(item).color" size="small" variant="tonal">
                            {{ getStatusInfo(item).text }}
                        </v-chip>
                        <span v-if="getStatusInfo(item).until" class="text-faint-cell">
                            {{ t('employeeTable.until') }} {{ getStatusInfo(item).until }}
                        </span>
                    </div>
                </template>

                <!-- Actions Column -->
                <template #item.actions="{ item }">
                    <div class="table-actions">
                        <v-tooltip :text="t('employeeTable.notes')" location="top">
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-bind="tooltipProps"
                                    icon
                                    size="x-small"
                                    variant="text"
                                    @click="emit('editNotes', item)"
                                >
                                    <v-icon size="small">mdi-note-edit</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>

                        <v-tooltip
                            :text="
                                getCurrentVacation(item)
                                    ? t('employeeTable.stopVacation')
                                    : t('employeeTable.addVacation')
                            "
                            location="top"
                        >
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-if="canEdit"
                                    v-bind="tooltipProps"
                                    icon
                                    size="x-small"
                                    variant="text"
                                    :color="getCurrentVacation(item) ? 'warning' : undefined"
                                    @click="
                                        getCurrentVacation(item)
                                            ? emit('stopVacation', getCurrentVacation(item).id)
                                            : emit('addVacation', item)
                                    "
                                >
                                    <v-icon size="small">
                                        {{
                                            getCurrentVacation(item)
                                                ? 'mdi-calendar-remove'
                                                : 'mdi-calendar-plus'
                                        }}
                                    </v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>

                        <v-tooltip :text="t('employeeTable.edit')" location="top">
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-if="canEdit"
                                    v-bind="tooltipProps"
                                    icon
                                    size="x-small"
                                    variant="text"
                                    @click="handleEdit(item)"
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
                        <v-icon size="20">mdi-account-off</v-icon>
                        <p>{{ t('employeeTable.noEmployees') }}</p>
                    </div>
                </template>
            </v-data-table>

            <!-- Massenaktionen: erst sichtbar, wenn sie etwas zu tun haben. -->
            <KBulkBar
                :count="selectedEmployees.length"
                :shown="sichtbareMitarbeiter.length"
                :total="alleMitarbeiter.length"
                @clear="clearSelection"
            >
                <template #actions>
                    <v-btn variant="outlined" size="small" @click="exportSelection">
                        {{ t('employeeTable.exportSelection') }}
                    </v-btn>
                </template>
            </KBulkBar>
        </div>

        <!--
            Dritte Zone: der Zusammenhang zum ausgewaehlten Mitarbeiter.
            Ohne Auswahl gibt es nichts zu zeigen, dann bleibt die Spalte weg.
        -->
        <template v-if="selectedEmployees.length">
            <KContextPanel v-if="selectedEmployees.length === 1" :label="selectedEmployees[0].name">
                <dl class="k-kv-list">
                    <div class="k-kv">
                        <dt class="id">#{{ selectedEmployees[0].servicenumber }}</dt>
                        <dd>{{ selectedEmployees[0].rankName }}</dd>
                    </div>
                    <div class="k-kv">
                        <dt>{{ t('employeeTable.phone') }}</dt>
                        <dd class="k-mono">{{ selectedEmployees[0].phonenumber || '—' }}</dd>
                    </div>
                    <div class="k-kv">
                        <dt>{{ t('employeeTable.entrydate') }}</dt>
                        <dd class="k-mono">{{ formatDate(selectedEmployees[0].entrydate) }}</dd>
                    </div>
                    <div v-if="selectedEmployees[0].leavedate" class="k-kv">
                        <dt>{{ t('employeeTable.leavedate') }}</dt>
                        <dd class="k-mono">{{ formatDate(selectedEmployees[0].leavedate) }}</dd>
                    </div>
                    <div class="k-kv">
                        <dt>{{ t('employeeTable.status') }}</dt>
                        <dd>
                            <v-chip size="x-small" variant="tonal" :color="getStatusInfo(selectedEmployees[0]).color">
                                {{ getStatusInfo(selectedEmployees[0]).text }}
                            </v-chip>
                        </dd>
                    </div>
                </dl>
                <p v-if="!selectedEmployees[0].phonenumber" class="k-context-note">
                    {{ t('employeeTable.noPhone') }}
                </p>
            </KContextPanel>

            <KContextPanel v-else :label="t('employeeTable.contextSelection')">
                <dl class="k-kv-list">
                    <div class="k-kv">
                        <dt>{{ t('employeeTable.contextSelected') }}</dt>
                        <dd>{{ selectedEmployees.length }}</dd>
                    </div>
                    <div class="k-kv">
                        <dt>{{ t('employeeTable.filterLeaving') }}</dt>
                        <dd>{{ selectedEmployees.filter((e: any) => !!e.leavedate).length }}</dd>
                    </div>
                </dl>
            </KContextPanel>
        </template>
    </div>
</template>

<style scoped>
.employee-table-view {
    width: 100%;
}

/*
   Vorher stand hier ein fest verdrahtetes Dunkelblau (rgba(15,23,42,.6)) samt
   Weichzeichner - im hellen Modus eine dunkle Platte mitten auf hellem Grund.
   Jetzt tragen Flaeche und Linie dieselben Merker wie alles andere.
*/
.rank-table-section {
    background: var(--k-surface, #fff);
    border: 1px solid var(--k-line, #e2e5ea);
    border-radius: 6px;
    overflow: hidden;
}

.employee-data-table {
    background: transparent !important;
}

.employee-info-cell {
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
}

.employee-avatar {
    flex-shrink: 0;
    border-radius: 4px !important;
}

.employee-name-table {
    font-weight: 550;
    font-size: 13px;
    color: var(--k-ink);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.text-muted-cell {
    font-size: 12.5px;
    color: var(--k-ink-muted);
}

.text-faint-cell {
    font-size: 11.5px;
    color: var(--k-ink-faint);
}

.organization-cell {
    display: flex;
    flex-direction: column;
    line-height: 1.35;
}

.license-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.status-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
    align-items: flex-start;
}

.table-actions {
    display: flex;
    gap: 2px;
    justify-content: flex-end;
}
</style>
