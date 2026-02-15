<template>
    <v-container fluid class="pa-4">
        <v-row>
            <!-- Linke/Mittlere Spalte: Dispatches -->
            <v-col :cols="dispatchColumnSize">
                <v-card class="list-card" :loading="loadingDispatches" elevation="4" theme="dark">
                    <v-toolbar density="compact" color="primary" class="card-toolbar">
                        <v-toolbar-title class="text-subtitle-1">
                            <v-icon start size="18" class="mr-1">mdi-radio-tower</v-icon>
                            Dispatches
                        </v-toolbar-title>
                        <v-spacer></v-spacer>

                        <!-- Buttons zum Öffnen der Mitarbeiter/Fahrzeug Slide-outs -->
                        <v-btn
                            icon
                            size="small"
                            variant="text"
                            @click="toggleEmployeePanel"
                            :class="{ 'active-panel-btn': employeeDrawerOpen }"
                            class="mr-2"
                        >
                            <v-badge
                                :content="availableEmployees.length"
                                color="blue"
                                offset-x="-2"
                                offset-y="-2"
                            >
                                <v-icon>mdi-account-group-outline</v-icon>
                            </v-badge>
                            <v-tooltip activator="parent" location="bottom">
                                {{ t('dispatchView.availableEmployees') }}
                            </v-tooltip>
                        </v-btn>

                        <v-btn
                            icon
                            size="small"
                            variant="text"
                            @click="toggleVehiclePanel"
                            :class="{ 'active-panel-btn': vehicleDrawerOpen }"
                            class="mr-2"
                        >
                            <v-badge
                                :content="availableVehicles.length"
                                color="amber"
                                offset-x="-2"
                                offset-y="-2"
                            >
                                <v-icon>mdi-car-outline</v-icon>
                            </v-badge>
                            <v-tooltip activator="parent" location="bottom">
                                {{ t('dispatchView.availableVehicles') }}
                            </v-tooltip>
                        </v-btn>

                        <v-chip size="small" label color="primary" variant="flat">{{
                            dispatches.length
                        }}</v-chip>
                    </v-toolbar>

                    <v-card-text class="pa-3 list-scroll-area">
                        <v-row dense>
                            <v-col
                                v-for="dispatch in dispatches"
                                :key="dispatch.id"
                                cols="12"
                                sm="4"
                                lg="3"
                                xl="2"
                            >
                                <v-card
                                    :id="`dispatch-${dispatch.id}`"
                                    class="dispatch-card fill-height d-flex flex-column"
                                    :data-id="dispatch.id"
                                    elevation="3"
                                    theme="dark"
                                >
                                    <v-toolbar
                                        density="compact"
                                        :color="dispatch.color || 'primary'"
                                        class="dispatch-toolbar"
                                    >
                                        <v-toolbar-title class="text-subtitle-2">{{
                                            dispatch.name
                                        }}</v-toolbar-title>
                                    </v-toolbar>

                                    <v-card-text class="flex-grow-1 pa-3">
                                        <!-- Mitarbeiter Drop-Zone -->
                                        <div class="drop-zone-header">
                                            <v-icon size="16" class="mr-1"
                                                >mdi-account-group</v-icon
                                            >
                                            <span class="text-body-2 font-weight-medium"
                                                >Mitarbeiter</span
                                            >
                                        </div>

                                        <draggable
                                            v-model="dispatch.employees"
                                            item-key="id"
                                            :group="{ name: 'employees', put: 'employees' }"
                                            class="drop-zone employee-drop-zone pa-2"
                                            @add="evt => onEmployeeDrop(evt, dispatch.id)"
                                            @remove="evt => onEmployeeRemove(evt, dispatch.id)"
                                        >
                                            <template #item="{ element }">
                                                <v-card
                                                    class="mb-1 draggable-card employee-card"
                                                    :class="{ 'employee-absent': element.is_absent }"
                                                    variant="tonal"
                                                    :color="element.is_absent ? 'orange' : 'blue'"
                                                    :data-id="element.id"
                                                    hover
                                                >
                                                    <v-card-text class="py-1 px-2">
                                                        <div class="d-flex align-center">
                                                            <v-avatar
                                                                size="20"
                                                                :color="element.is_absent ? 'orange-darken-2' : 'primary'"
                                                                class="mr-2"
                                                            >
                                                                <span class="text-caption">{{
                                                                    element.name.charAt(0)
                                                                }}</span>
                                                            </v-avatar>
                                                            <div class="text-caption flex-grow-1">
                                                                [{{ element.servicenumber }}]
                                                                {{ element.name }}
                                                                <span class="text-grey"
                                                                    >• {{ element.rank_name }}</span
                                                                >
                                                            </div>
                                                            <v-btn
                                                                icon
                                                                size="x-small"
                                                                variant="text"
                                                                :color="element.is_absent ? 'orange' : 'grey'"
                                                                @click.stop="toggleEmployeeAbsent(element, dispatch.id)"
                                                                class="absence-toggle-btn"
                                                            >
                                                                <v-icon size="14">{{
                                                                    element.is_absent ? 'mdi-account-off' : 'mdi-account-check'
                                                                }}</v-icon>
                                                            </v-btn>
                                                        </div>
                                                    </v-card-text>
                                                </v-card>
                                            </template>

                                            <template #header>
                                                <div
                                                    v-if="
                                                        !dispatch.employees ||
                                                        dispatch.employees.length === 0
                                                    "
                                                    class="drop-placeholder"
                                                >
                                                    <v-icon
                                                        color="grey-darken-1"
                                                        size="20"
                                                        class="mr-2"
                                                        >mdi-drag</v-icon
                                                    >
                                                    <span>Mitarbeiter hierher ziehen</span>
                                                </div>
                                            </template>
                                        </draggable>

                                        <!-- Fahrzeuge Drop-Zone -->
                                        <div class="drop-zone-header mt-3">
                                            <v-icon size="16" class="mr-1">mdi-car-multiple</v-icon>
                                            <span class="text-body-2 font-weight-medium"
                                                >Fahrzeuge</span
                                            >
                                        </div>

                                        <draggable
                                            v-model="dispatch.vehicles"
                                            item-key="id"
                                            :group="{ name: 'vehicles', put: 'vehicles' }"
                                            class="drop-zone vehicle-drop-zone pa-2"
                                            @add="evt => onVehicleDrop(evt, dispatch.id)"
                                            @remove="evt => onVehicleRemove(evt, dispatch.id)"
                                        >
                                            <template #item="{ element }">
                                                <v-card
                                                    class="mb-1 draggable-card vehicle-card"
                                                    variant="tonal"
                                                    color="amber-darken-4"
                                                    :data-id="element.id"
                                                    hover
                                                >
                                                    <v-card-text class="py-1 px-2">
                                                        <div class="d-flex align-center">
                                                            <v-avatar
                                                                size="20"
                                                                color="warning"
                                                                class="mr-2"
                                                            >
                                                                <v-icon size="12" color="white"
                                                                    >mdi-car</v-icon
                                                                >
                                                            </v-avatar>
                                                            <div class="text-caption">
                                                                {{ element.title }}
                                                                <span class="text-grey"
                                                                    >•
                                                                    {{ element.numberplate }}</span
                                                                >
                                                            </div>
                                                        </div>
                                                    </v-card-text>
                                                </v-card>
                                            </template>

                                            <template #header>
                                                <div
                                                    v-if="
                                                        !dispatch.vehicles ||
                                                        dispatch.vehicles.length === 0
                                                    "
                                                    class="drop-placeholder"
                                                >
                                                    <v-icon
                                                        color="grey-darken-1"
                                                        size="20"
                                                        class="mr-2"
                                                        >mdi-drag</v-icon
                                                    >
                                                    <span>Fahrzeug hierher ziehen</span>
                                                </div>
                                            </template>
                                        </draggable>
                                    </v-card-text>

                                    <v-divider></v-divider>

                                    <v-card-actions class="pa-3">
                                        <v-text-field
                                            v-model="dispatch.status"
                                            append-inner-icon="mdi-content-save"
                                            density="compact"
                                            label="Status"
                                            variant="outlined"
                                            color="primary"
                                            hide-details
                                            @click:append-inner="saveDispatch(dispatch)"
                                            @keydown.enter="saveDispatch(dispatch)"
                                            :loading="savingDispatch === dispatch.id"
                                        ></v-text-field>
                                    </v-card-actions>
                                </v-card>
                            </v-col>
                        </v-row>

                        <div
                            v-if="!loadingDispatches && dispatches.length === 0"
                            class="empty-list-message py-10"
                        >
                            <v-icon color="grey-darken-1" size="48" class="mb-2"
                                >mdi-radio-tower-off</v-icon
                            >
                            <span>Keine Dispatches konfiguriert</span>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Rechte Spalte: Mitarbeiter/Fahrzeuge (erscheint nur wenn Button geklickt) -->
            <v-col v-if="sidePanelOpen" cols="12" md="3" lg="3" xl="3" class="side-panel">
                <v-card class="list-card" elevation="4" theme="dark" style="max-width: 350px; margin-left: auto;">
                    <!-- Mitarbeiter Panel -->
                    <template v-if="employeeDrawerOpen">
                        <v-toolbar density="compact" color="primary">
                            <v-toolbar-title class="text-subtitle-1">
                                <v-icon start size="18" class="mr-1">mdi-account-group-outline</v-icon>
                                {{ t('dispatchView.availableEmployees') }}
                            </v-toolbar-title>
                            <v-chip size="small" label color="primary" variant="flat" class="mr-2">
                                {{ availableEmployees.length }}
                            </v-chip>
                            <v-btn icon @click="closePanel" size="small">
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                        </v-toolbar>

                        <v-card-text class="pa-3 list-scroll-area">
                            <v-text-field
                                v-model="employeeSearch"
                                :label="t('dispatchView.searchEmployees')"
                                prepend-inner-icon="mdi-magnify"
                                variant="outlined"
                                density="compact"
                                color="primary"
                                hide-details
                                clearable
                                class="mb-3"
                            ></v-text-field>

                            <draggable
                                v-model="availableEmployees"
                                item-key="id"
                                :group="{ name: 'employees', pull: 'clone', put: true }"
                                :sort="false"
                                :clone="cloneEmployee"
                                class="source-list"
                                @add="onEmployeeReturnToSource"
                            >
                                <template #item="{ element }">
                                    <v-card
                                        v-show="matchesEmployeeSearch(element)"
                                        class="mb-2 draggable-card employee-card"
                                        :data-id="element.id"
                                        variant="outlined"
                                        color="blue-grey-darken-3"
                                        hover
                                    >
                                        <v-card-text class="pa-2">
                                            <div class="d-flex align-center">
                                                <v-avatar size="24" color="primary" class="mr-2">
                                                    <span class="text-caption">{{
                                                        element.name.charAt(0)
                                                    }}</span>
                                                </v-avatar>
                                                <div>
                                                    <div class="text-caption font-weight-bold">
                                                        [{{ element.servicenumber }}]
                                                        {{ element.name }}
                                                    </div>
                                                    <div class="text-caption text-grey">
                                                        {{ element.rank_name }}
                                                    </div>
                                                </div>
                                            </div>
                                        </v-card-text>
                                    </v-card>
                                </template>
                            </draggable>

                            <div
                                v-if="!loadingEmployees && availableEmployees.length === 0"
                                class="empty-list-message"
                            >
                                <v-icon color="grey-darken-1" size="36" class="mb-2">
                                    mdi-account-off-outline
                                </v-icon>
                                <span>Keine Mitarbeiter verfügbar</span>
                            </div>
                        </v-card-text>
                    </template>

                    <!-- Fahrzeuge Panel -->
                    <template v-if="vehicleDrawerOpen">
                        <v-toolbar density="compact" color="primary">
                            <v-toolbar-title class="text-subtitle-1">
                                <v-icon start size="18" class="mr-1">mdi-car-outline</v-icon>
                                {{ t('dispatchView.availableVehicles') }}
                            </v-toolbar-title>
                            <v-chip size="small" label color="primary" variant="flat" class="mr-2">
                                {{ availableVehicles.length }}
                            </v-chip>
                            <v-btn icon @click="closePanel" size="small">
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                        </v-toolbar>

                        <v-card-text class="pa-3 list-scroll-area">
                            <v-text-field
                                v-model="vehicleSearch"
                                :label="t('dispatchView.searchVehicles')"
                                prepend-inner-icon="mdi-magnify"
                                variant="outlined"
                                density="compact"
                                color="primary"
                                hide-details
                                clearable
                                class="mb-3"
                            ></v-text-field>

                            <draggable
                                v-model="availableVehicles"
                                item-key="id"
                                :group="{ name: 'vehicles', pull: 'clone', put: true }"
                                :sort="false"
                                :clone="cloneVehicle"
                                class="source-list"
                                @add="onVehicleReturnToSource"
                            >
                                <template #item="{ element }">
                                    <v-card
                                        v-show="matchesVehicleSearch(element)"
                                        class="mb-2 draggable-card vehicle-card"
                                        :data-id="element.id"
                                        variant="outlined"
                                        color="blue-grey-darken-3"
                                        hover
                                    >
                                        <v-card-text class="pa-2">
                                            <div class="d-flex align-center">
                                                <v-avatar size="24" color="warning" class="mr-2">
                                                    <v-icon size="small" color="white">mdi-car</v-icon>
                                                </v-avatar>
                                                <div>
                                                    <div class="text-caption font-weight-bold">
                                                        {{ element.title }}
                                                    </div>
                                                    <div class="text-caption text-grey">
                                                        {{ element.numberplate }}
                                                        {{ element.rank ? '• ' + element.rank : '' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </v-card-text>
                                    </v-card>
                                </template>
                            </draggable>

                            <div
                                v-if="!loadingVehicles && availableVehicles.length === 0"
                                class="empty-list-message"
                            >
                                <v-icon color="grey-darken-1" size="36" class="mb-2">mdi-car-off</v-icon>
                                <span>Keine Fahrzeuge verfügbar</span>
                            </div>
                        </v-card-text>
                    </template>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<style scoped>
/* Side panel animation */
.side-panel {
    animation: slideInFromRight 0.3s ease-out;
}

@keyframes slideInFromRight {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Active panel button highlight */
.active-panel-btn {
    background-color: rgba(59, 130, 246, 0.2) !important;
}

/* Highlight animation for dispatch cards when navigated to via ID */
.highlight-dispatch {
    animation: highlightPulse 0.5s ease-in-out 3;
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.6) !important;
}

@keyframes highlightPulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.4);
    }
    50% {
        transform: scale(1.02);
        box-shadow: 0 0 20px rgba(59, 130, 246, 0.8);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.4);
    }
}
</style>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, reactive, nextTick, type Ref } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store (for WS only if needed)
import draggable from 'vuedraggable'; // Use vuedraggable
import { useToast } from 'vue-toastification';
const { t } = useI18n();
// @ts-ignore - Ignoriere TypeScript-Probleme mit dem Socket-Modul
import { initializeSockets, sendDispatchUpdate } from '@/plugins/socket'; // WebSocket-Funktionen

// Erweitere Window-Interface für TypeScript
declare global {
    interface Window {
        socketMain: any;
    }
}

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
  id?: number | string
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  id: undefined
});

// Router
const route = useRoute();

// --- Interfaces --- (Define based on expected API response structure)
interface Employee {
    id: number;
    name: string;
    servicenumber: string;
    rank_name: string;
    is_absent?: number; // 0 = present, 1 = absent
}
interface Vehicle {
    id: number;
    title: string;
    numberplate: string;
    rank: string | null; // Minimum rank?
    // Add other relevant properties
}
interface Dispatch {
    id: number;
    name: string;
    status: string | null;
    sort_order?: number; // Sort order for dispatches
    color?: string; // Optional color for the header
    employees: Employee[];
    vehicles: Vehicle[];
}

// WebSocket Interfaces
interface WebSocketUpdateData {
    type: string;
    data: {
        itemType?: 'employee' | 'vehicle';
        itemId?: number;
        dispatchId?: number | null;
        status?: string;
        [key: string]: any;
    };
    timestamp: number;
    sender?: number;              // User ID (für Logging)
    senderSocketId?: string;      // Socket ID (für Tab-Unterscheidung)
}

// --- Store ---
const authStore = useAuthStore(); // Jetzt nötig für WebSocket-Verbindung

// --- Component State ---
const employees = ref<Employee[]>([]); // Available employees pool
const vehicles = ref<Vehicle[]>([]); // Available vehicles pool
const dispatches = ref<Dispatch[]>([]); // Dispatch units with assigned items
const loadingEmployees = ref(false);
const loadingVehicles = ref(false);
const loadingDispatches = ref(false);
const savingDispatch = ref<number | null>(null); // ID of dispatch being saved

// Search / Filter
const employeeSearch = ref('');
const vehicleSearch = ref('');

// Drawer state for employee and vehicle slide-outs
const employeeDrawerOpen = ref(false);
const vehicleDrawerOpen = ref(false);

// Socket-Referenz für Verbindungsstatus
const socketMain = ref<any>(null);

// --- Snackbar ---
const toast = useToast();
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// --- Computed Properties ---
// Available employees (not assigned to any dispatch) - NO search filter
const availableEmployees = computed(() => {
    const assignedEmployeeIds = new Set();
    dispatches.value.forEach(dispatch => {
        dispatch.employees?.forEach(emp => assignedEmployeeIds.add(emp.id));
    });

    return employees.value.filter(emp => !assignedEmployeeIds.has(emp.id));
});

// Available vehicles (not assigned to any dispatch) - NO search filter
const availableVehicles = computed(() => {
    const assignedVehicleIds = new Set();
    dispatches.value.forEach(dispatch => {
        dispatch.vehicles?.forEach(veh => assignedVehicleIds.add(veh.id));
    });

    return vehicles.value.filter(veh => !assignedVehicleIds.has(veh.id));
});

// Search filter functions (used with v-show)
function matchesEmployeeSearch(employee: Employee): boolean {
    const searchTermLower = employeeSearch.value.toLowerCase();
    if (!searchTermLower) return true;

    return (
        employee.name.toLowerCase().includes(searchTermLower) ||
        employee.servicenumber.toLowerCase().includes(searchTermLower) ||
        employee.rank_name.toLowerCase().includes(searchTermLower)
    );
}

function matchesVehicleSearch(vehicle: Vehicle): boolean {
    const searchTermLower = vehicleSearch.value.toLowerCase();
    if (!searchTermLower) return true;

    return (
        vehicle.title.toLowerCase().includes(searchTermLower) ||
        vehicle.numberplate.toLowerCase().includes(searchTermLower)
    );
}

// Filtered lists for badge display
const filteredEmployees = computed(() => {
    return availableEmployees.value.filter(emp => matchesEmployeeSearch(emp));
});

const filteredVehicles = computed(() => {
    return availableVehicles.value.filter(veh => matchesVehicleSearch(veh));
});

// Check if any side panel is open
const sidePanelOpen = computed(() => employeeDrawerOpen.value || vehicleDrawerOpen.value);

// Dynamic column size for dispatch cards
const dispatchColumnSize = computed(() => {
    return sidePanelOpen.value ? 9 : 12; // 9 cols when panel open (leaves 3 for side panel), 12 when closed
});

// Toggle functions
function toggleEmployeePanel() {
    if (employeeDrawerOpen.value) {
        // If already open, close it
        employeeDrawerOpen.value = false;
    } else {
        // Close vehicle panel if open, then open employee panel
        vehicleDrawerOpen.value = false;
        employeeDrawerOpen.value = true;
    }
}

function toggleVehiclePanel() {
    if (vehicleDrawerOpen.value) {
        // If already open, close it
        vehicleDrawerOpen.value = false;
    } else {
        // Close employee panel if open, then open vehicle panel
        employeeDrawerOpen.value = false;
        vehicleDrawerOpen.value = true;
    }
}

function closePanel() {
    employeeDrawerOpen.value = false;
    vehicleDrawerOpen.value = false;
}

// Clone functions for drag and drop
function cloneEmployee(employee: Employee) {
    return { ...employee };
}

function cloneVehicle(vehicle: Vehicle) {
    return { ...vehicle };
}

// --- Data Fetching ---
const fetchData = async (
    action: string,
    targetRef: Ref<any[]>,
    loadingRef: Ref<boolean>,
    errorMessage: string,
    useGet: boolean = false
) => {
    loadingRef.value = true;
    try {
        let response;
        if (useGet) {
            // Use GET request
            response = await apiClientAuth.get<{ data: any[] }>(`dispatch?action=${action}`);
        } else {
            // Use POST request (default)
            response = await apiClientAuth.post<{ data: any[] }>(`dispatch?action=${action}`);
        }

        // Debug-Ausgabe für die API-Antwort
        console.log(`Fetched data for ${action}:`, response.data);

        // Achte auf verschiedene Antwortstrukturen
        if (response.data && response.data.data) {
            targetRef.value = response.data.data;
        } else if (Array.isArray(response.data)) {
            targetRef.value = response.data;
        } else {
            targetRef.value = [];
            console.warn(`Unexpected response format for ${action}:`, response.data);
        }
    } catch (error: any) {
        console.error(`Error fetching ${action}:`, error);
        showSnackbar(error.response?.data?.error || errorMessage, 'error');
        targetRef.value = [];
    } finally {
        loadingRef.value = false;
    }
};

// Hilfsfunktion zum Sortieren der Mitarbeiter nach Dienstnummer
const sortEmployees = () => {
    employees.value.sort((a, b) => {
        const numA = parseInt(a.servicenumber) || 0;
        const numB = parseInt(b.servicenumber) || 0;
        return numA - numB;
    });
};

const fetchEmployees = async () => {
    loadingEmployees.value = true;
    try {
        const response = await apiClientAuth.get<any>('dispatch?action=getEmployees');

        // Debug-Information zur API-Antwort
        console.log('Employee API response:', response.data);

        // Ermittle die tatsächliche Datenquelle
        let employeeData;
        if (Array.isArray(response.data)) {
            employeeData = response.data;
        } else if (response.data && Array.isArray(response.data.data)) {
            employeeData = response.data.data;
        } else {
            console.warn('Unexpected employee data format:', response.data);
            employeeData = [];
        }

        // Setze die Mitarbeiter-Daten
        if (Array.isArray(employeeData)) {
            employees.value = employeeData;
            // Sortiere nach Dienstnummer
            sortEmployees();
        } else {
            employees.value = [];
        }

        console.log('Processed employees:', employees.value);
    } catch (error: any) {
        console.error('Error fetching employees:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Mitarbeiter.', 'error');
        employees.value = [];
    } finally {
        loadingEmployees.value = false;
    }
};

const fetchVehicles = async () => {
    loadingVehicles.value = true;
    try {
        const response = await apiClientAuth.get<any>('vehicle/?action=getVehicles');

        // Debug-Information zur API-Antwort
        console.log('Vehicle API response:', response.data);

        // Ermittle die tatsächliche Datenquelle (ob response.data oder response.data.data)
        let vehicleData;
        if (Array.isArray(response.data)) {
            // API gibt direkt ein Array zurück
            vehicleData = response.data;
        } else if (response.data && Array.isArray(response.data.data)) {
            // API gibt { data: [...] } zurück
            vehicleData = response.data.data;
        } else {
            // Unerwartetes Format
            console.warn('Unexpected vehicle data format:', response.data);
            vehicleData = [];
        }

        // Stelle sicher, dass vehicleData ein Array ist, bevor sortiert wird
        if (Array.isArray(vehicleData)) {
            // Sortiere nach sort_order, dann title
            vehicles.value = vehicleData.sort((a, b) => {
                // Prüfe, ob die Eigenschaften existieren
                if (a.sort_order !== undefined && b.sort_order !== undefined) {
                    const sortOrderDiff = (a.sort_order ?? 999) - (b.sort_order ?? 999);
                    if (sortOrderDiff !== 0) return sortOrderDiff;
                }
                // Fallback auf title, falls vorhanden
                return (a.title || '').localeCompare(b.title || '');
            });
        } else {
            vehicles.value = [];
        }

        console.log('Processed vehicles:', vehicles.value);
    } catch (error: any) {
        console.error('Error fetching vehicles:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Fahrzeuge.', 'error');
        vehicles.value = [];
    } finally {
        loadingVehicles.value = false;
    }
};

const fetchAllData = () => {
    // Lade Mitarbeiter mit Sortierung nach Dienstnummer
    fetchEmployees();
    // Lade Fahrzeuge mit Sortierung
    fetchVehicles();
    // Lade Dispatches
    fetchData(
        'getDispatches',
        dispatches,
        loadingDispatches,
        'Fehler beim Laden der Dispatches.',
        true
    );

    // Debug-Ausgabe nach dem Laden
    setTimeout(() => {
        console.log('Loaded employees:', employees.value);
        console.log('Loaded vehicles:', vehicles.value);
        console.log('Filtered vehicles:', filteredVehicles.value);
    }, 1000);
};

// --- Dispatch Actions ---
async function saveDispatch(dispatch: Dispatch) {
    savingDispatch.value = dispatch.id;
    try {
        // Use editDispatch to update status without clearing employees/vehicles
        await apiClientAuth.post('/dispatch?action=editDispatch', {
            id: dispatch.id,
            name: dispatch.name,
            status: dispatch.status,
            sort_order: dispatch.sort_order ?? 0 // Use existing sort order or default to 0
        });
        showSnackbar(`Status für ${dispatch.name} gespeichert.`, 'success');
        sendWebSocketUpdate('status', { dispatchId: dispatch.id, status: dispatch.status });
    } catch (error: any) {
        console.error('Error saving dispatch status:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Speichern des Status.', 'error');
        // Optional: Revert local change on error by refetching
        // await fetchData('getDispatches', dispatches, loadingDispatches, "Fehler beim Laden der Dispatches.");
    } finally {
        savingDispatch.value = null;
    }
}

// --- Toggle Employee Absent Status ---
async function toggleEmployeeAbsent(employee: Employee, dispatchId: number) {
    try {
        const response = await apiClientAuth.post('/dispatch?action=toggleAbsent', {
            employee_id: employee.id
        });

        if (response.data.success) {
            // Update local state
            const newAbsentStatus = response.data.is_absent;

            // Find and update employee in the dispatch
            const dispatch = dispatches.value.find(d => d.id === dispatchId);
            if (dispatch) {
                const emp = dispatch.employees.find(e => e.id === employee.id);
                if (emp) {
                    emp.is_absent = newAbsentStatus;
                }
            }

            // Send WebSocket update
            sendWebSocketUpdate('absent', {
                employeeId: employee.id,
                dispatchId: dispatchId,
                isAbsent: newAbsentStatus
            });
        }
    } catch (error: any) {
        console.error('Error toggling employee absence:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Ändern des Abwesenheitsstatus.', 'error');
    }
}

// --- Drag and Drop Assignment Logic ---
async function assignItemToDispatch(
    itemId: number,
    newDispatchId: number | null,
    itemType: 'employee' | 'vehicle'
) {
    const action = itemType === 'employee' ? 'assignEmployee' : 'assignVehicle';
    const payload =
        itemType === 'employee'
            ? { employee_id: itemId, dispatch_id: newDispatchId }
            : { vehicle_id: itemId, dispatch_id: newDispatchId };

    try {
        console.log(`Assigning ${itemType} ${itemId} to dispatch ${newDispatchId}`);
        await apiClientAuth.post(`dispatch?action=${action}`, payload);
        
        // If we're removing an item from a dispatch (setting to null)
        // We need to make sure it's added back to the master list
        if (newDispatchId === null) {
            if (itemType === 'employee') {
                // Find the employee in the dispatches
                let foundEmployee: Employee | undefined;
                dispatches.value.forEach(dispatch => {
                    const index = dispatch.employees.findIndex(emp => emp.id === itemId);
                    if (index !== -1) {
                        foundEmployee = {...dispatch.employees[index]};  // Clone the object
                        // Remove from this dispatch
                        dispatch.employees.splice(index, 1);
                    }
                });
                
                // Add to master list if not there already
                if (foundEmployee && !employees.value.some(emp => emp.id === itemId)) {
                    console.log("Adding employee back to master list:", foundEmployee);
                    employees.value.push(foundEmployee);
                    sortEmployees(); // Sortierung beibehalten
                }
            } else {  // vehicle
                // Find the vehicle in the dispatches
                let foundVehicle: Vehicle | undefined;
                dispatches.value.forEach(dispatch => {
                    const index = dispatch.vehicles.findIndex(veh => veh.id === itemId);
                    if (index !== -1) {
                        foundVehicle = {...dispatch.vehicles[index]};  // Clone the object
                        // Remove from this dispatch
                        dispatch.vehicles.splice(index, 1);
                    }
                });
                
                // Add to master list if not there already
                if (foundVehicle && !vehicles.value.some(veh => veh.id === itemId)) {
                    console.log("Adding vehicle back to master list:", foundVehicle);
                    vehicles.value.push(foundVehicle);
                }
            }
        }
        
    } catch (error: any) {
        console.error(`Error assigning ${itemType}:`, error);
        showSnackbar(error.response?.data?.error || `Fehler beim Zuweisen (${itemType}).`, 'error');
        // Revert optimistic update by refetching data
        await fetchAllData();
    }
}

function onEmployeeDrop(evt: any, targetDispatchId: number | null = null) {
    const employeeId = Number(evt.item?.dataset?.id);
    const targetId = targetDispatchId;

    console.log(`Employee ${employeeId} dropped onto dispatch ${targetId}`);

    if (!employeeId || targetId === null) return;

    // Reset is_absent when dropping into a dispatch (local update)
    const targetDispatch = dispatches.value.find(d => d.id === targetId);
    if (targetDispatch) {
        const emp = targetDispatch.employees.find(e => e.id === employeeId);
        if (emp) {
            emp.is_absent = 0;
        }
    }

    assignItemToDispatch(employeeId, targetId, 'employee');

    // WebSocket-Event senden für Echtzeit-Updates bei anderen Clients
    sendWebSocketUpdate('assign', {
        itemType: 'employee',
        itemId: employeeId,
        dispatchId: targetId
    });
}

function onEmployeeRemove(evt: any, sourceDispatchId: number) {
    const employeeId = Number(evt.item?.dataset?.id);
    // Check if dropped into the source list (left side)
    const targetIsSourceList = evt.to?.classList?.contains('source-list');
    
    console.log(`Employee ${employeeId} removed from dispatch ${sourceDispatchId}, targetIsSourceList: ${targetIsSourceList}`);
    
    // Only call API if dropping to source list, otherwise another dispatch will handle it
    if (employeeId && targetIsSourceList) {
        // Find the employee in dispatches to add it to the available list
        let foundEmployee: Employee | undefined;
        dispatches.value.forEach(dispatch => {
            if (dispatch.id === sourceDispatchId) {
                const index = dispatch.employees.findIndex(emp => emp.id === employeeId);
                if (index !== -1) {
                    // Deep clone the employee object
                    foundEmployee = JSON.parse(JSON.stringify(dispatch.employees[index]));
                    
                    // Remove from this dispatch (no need as vuedraggable already did this)
                    // But ensure it's actually removed
                    if (dispatch.employees.some(emp => emp.id === employeeId)) {
                        dispatch.employees = dispatch.employees.filter(emp => emp.id !== employeeId);
                    }
                }
            }
        });
        
        // Make sure the employee is not already in the available list
        const alreadyInList = employees.value.some(emp => emp.id === employeeId);
        if (foundEmployee && !alreadyInList) {
            console.log("Adding employee back to available list:", foundEmployee);
            employees.value.push(foundEmployee);
            sortEmployees(); // Sortierung beibehalten
        }
        
        assignItemToDispatch(employeeId, null, 'employee');
        
        // WebSocket-Event senden für Entfernung
        sendWebSocketUpdate('assign', { 
            itemType: 'employee', 
            itemId: employeeId, 
            dispatchId: null 
        });
    }
}

function onVehicleDrop(evt: any, targetDispatchId: number | null = null) {
    const vehicleId = Number(evt.item?.dataset?.id);
    const targetId = targetDispatchId;

    console.log(`Vehicle ${vehicleId} dropped onto dispatch ${targetId}`);

    if (!vehicleId || targetId === null) return;
    assignItemToDispatch(vehicleId, targetId, 'vehicle');
    
    // WebSocket-Event senden
    sendWebSocketUpdate('assign', { 
        itemType: 'vehicle', 
        itemId: vehicleId, 
        dispatchId: targetId 
    });
}

function onVehicleRemove(evt: any, sourceDispatchId: number) {
    const vehicleId = Number(evt.item?.dataset?.id);
    // Check if dropped into the source list (left side)
    const targetIsSourceList = evt.to?.classList?.contains('source-list');
    
    console.log(`Vehicle ${vehicleId} removed from dispatch ${sourceDispatchId}, targetIsSourceList: ${targetIsSourceList}`);
    
    // Only call API if dropping to source list, otherwise another dispatch will handle it
    if (vehicleId && targetIsSourceList) {
        // Find the vehicle in dispatches to add it to the available list
        let foundVehicle: Vehicle | undefined;
        dispatches.value.forEach(dispatch => {
            if (dispatch.id === sourceDispatchId) {
                const index = dispatch.vehicles.findIndex(veh => veh.id === vehicleId);
                if (index !== -1) {
                    // Deep clone the vehicle object
                    foundVehicle = JSON.parse(JSON.stringify(dispatch.vehicles[index]));
                    
                    // Remove from this dispatch (no need as vuedraggable already did this)
                    // But ensure it's actually removed
                    if (dispatch.vehicles.some(veh => veh.id === vehicleId)) {
                        dispatch.vehicles = dispatch.vehicles.filter(veh => veh.id !== vehicleId);
                    }
                }
            }
        });
        
        // Make sure the vehicle is not already in the available list
        const alreadyInList = vehicles.value.some(veh => veh.id === vehicleId);
        if (foundVehicle && !alreadyInList) {
            console.log("Adding vehicle back to available list:", foundVehicle);
            vehicles.value.push(foundVehicle);
        }
        
        assignItemToDispatch(vehicleId, null, 'vehicle');
        
        // WebSocket-Event senden
        sendWebSocketUpdate('assign', { 
            itemType: 'vehicle', 
            itemId: vehicleId, 
            dispatchId: null 
        });
    }
}

function onEmployeeReturnToSource(evt: any) {
    const employeeId = Number(evt.item?.dataset?.id);
    if (!employeeId) return;
    
    console.log(`Employee ${employeeId} returned to source list`);
    
    // Find the employee in dispatches
    let foundEmployee: Employee | undefined;
    dispatches.value.forEach(dispatch => {
        const index = dispatch.employees.findIndex(emp => emp.id === employeeId);
        if (index !== -1) {
            foundEmployee = dispatch.employees[index];
        }
    });
    
    // Remove duplicated employee from the source list
    // We need to do this because vuedraggable clones the item
    setTimeout(() => {
        const index = filteredEmployees.value.findIndex(emp => emp.id === employeeId);
        if (index > -1) {
            filteredEmployees.value.splice(index, 1);
        }
        
        // If we found the employee in a dispatch, add it to the employees array
        if (foundEmployee) {
            // Check if this employee exists in the master employees list
            const existsInMaster = employees.value.some(emp => emp.id === employeeId);
            if (!existsInMaster) {
                employees.value.push(foundEmployee);
                sortEmployees(); // Sortierung beibehalten
            }
        }
    }, 50);
    
    assignItemToDispatch(employeeId, null, 'employee');
}

function onVehicleReturnToSource(evt: any) {
    const vehicleId = Number(evt.item?.dataset?.id);
    if (!vehicleId) return;
    
    console.log(`Vehicle ${vehicleId} returned to source list`);
    
    // Find the vehicle in dispatches
    let foundVehicle: Vehicle | undefined;
    dispatches.value.forEach(dispatch => {
        const index = dispatch.vehicles.findIndex(veh => veh.id === vehicleId);
        if (index !== -1) {
            foundVehicle = dispatch.vehicles[index];
        }
    });
    
    // Remove duplicated vehicle from the source list
    setTimeout(() => {
        const index = filteredVehicles.value.findIndex(veh => veh.id === vehicleId);
        if (index > -1) {
            filteredVehicles.value.splice(index, 1);
        }
        
        // If we found the vehicle in a dispatch, add it to the vehicles array
        if (foundVehicle) {
            // Check if this vehicle exists in the master vehicles list
            const existsInMaster = vehicles.value.some(veh => veh.id === vehicleId);
            if (!existsInMaster) {
                vehicles.value.push(foundVehicle);
            }
        }
    }, 50);
    
    assignItemToDispatch(vehicleId, null, 'vehicle');
}

// --- Lifecycle Hooks ---
onMounted(async () => {
    await fetchAllData();
    setupWebSocketConnection();
    
    // Check for ID from route query or props
    const dispatchId = route.query.id || props.id || props.meta?.id;
    
    if (dispatchId) {
        // Find the dispatch with the specified ID
        const dispatch = dispatches.value.find(d => d.id === Number(dispatchId));
        if (dispatch) {
            // Focus on this dispatch - expand it if needed
            // Since dispatches are shown as cards, we might want to scroll to it
            // or highlight it in some way
            await nextTick();
            const dispatchElement = document.getElementById(`dispatch-${dispatch.id}`);
            if (dispatchElement) {
                dispatchElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Add a temporary highlight effect
                dispatchElement.classList.add('highlight-dispatch');
                setTimeout(() => {
                    dispatchElement.classList.remove('highlight-dispatch');
                }, 3000);
            }
        }
    }
});

// Funktion zum Senden von WebSocket-Updates
function sendWebSocketUpdate(updateType: string, data: Record<string, any>): boolean {
    return sendDispatchUpdate(updateType, data);
}

// WebSocket-Verbindung einrichten und Event-Handler registrieren
function setupWebSocketConnection() {
    if (!window.socketMain) {
        console.log('Initializing sockets...');
        const sockets = initializeSockets();
        if (sockets) {
            socketMain.value = sockets.main;
            console.log('Socket connection established', socketMain.value.id);
        }
    } else {
        socketMain.value = window.socketMain;
        console.log('Reusing existing socket connection', socketMain.value.id);
    }

    // Event-Handler für Dispatch-Updates
    if (socketMain.value) {
        // Vorhandene Handler entfernen, um Duplikate zu verhindern
        socketMain.value.off('dispatch:update');
        
        // Neuen Handler registrieren
        socketMain.value.on('dispatch:update', (updateData: WebSocketUpdateData) => {
            console.log('📣 Received dispatch update:', updateData);

            // Prüfen, ob das Update von diesem Socket stammt (nicht von diesem Tab)
            // Verwende Socket ID statt User ID, damit Updates zwischen Tabs desselben Users funktionieren
            const currentSocketId = socketMain.value?.id;
            if (updateData.senderSocketId && updateData.senderSocketId === currentSocketId) {
                console.log('Ignoring update from same socket/tab');
                return;
            }

            // Update basierend auf dem Typ verarbeiten
            handleDispatchUpdate(updateData);
        });
    }
}

// Verarbeitung der empfangenen Updates
function handleDispatchUpdate(updateData: WebSocketUpdateData): void {
    const { type, data } = updateData;
    
    // Hilfsfunktion zum Erzwingen reaktiver Updates
    function forceTrigger(reactiveArray: any[]) {
        console.log('🔄 Forcing reactive update for array of length:', reactiveArray.length);
        // Erstelle tiefe Kopie und weise sie neu zu, um Vue's Reaktivität zu triggern
        const tempCopy = JSON.parse(JSON.stringify(reactiveArray));
        reactiveArray.splice(0, reactiveArray.length, ...tempCopy);
        console.log('✅ Reactive update forced successfully');
    }
    
    // Automatische Aktualisierung der UI ohne erneutes API-Laden
    if (type === 'assign') {
        const { itemType, itemId, dispatchId } = data;
        
        if (itemType === 'employee') {
            // Mitarbeiter-Objekt finden und speichern, bevor wir es entfernen
            let foundEmployee: Employee | undefined;
            
            // Suche in allen Dispatches nach dem Mitarbeiter
            dispatches.value.forEach(dispatch => {
                if (dispatch.id !== dispatchId) {
                    const index = dispatch.employees.findIndex(emp => emp.id === itemId);
                    if (index !== -1) {
                        // Mitarbeiter gefunden - speichern (Kopie erstellen) und aus Dispatch entfernen
                        foundEmployee = {...dispatch.employees[index]};
                        console.log(`Entferne Mitarbeiter ${itemId} aus Dispatch ${dispatch.id}`);
                        dispatch.employees.splice(index, 1);
                    }
                }
            });
            
            // Wenn einem Dispatch zugewiesen wird
            if (dispatchId) {
                // Wenn nicht in einem anderen Dispatch, dann in der Hauptliste suchen
                if (!foundEmployee) {
                    const employeeIndex = employees.value.findIndex(emp => emp.id === itemId);
                    if (employeeIndex !== -1) {
                        foundEmployee = {...employees.value[employeeIndex]};
                        console.log(`Entferne Mitarbeiter ${itemId} aus Hauptliste`);
                        employees.value.splice(employeeIndex, 1);
                    }
                }
                
                // Zum Ziel-Dispatch hinzufügen
                if (foundEmployee) {
                    // Reset is_absent when reassigning to a dispatch
                    foundEmployee.is_absent = 0;
                    const targetDispatch = dispatches.value.find(d => d.id === dispatchId);
                    if (targetDispatch && !targetDispatch.employees.some(e => e.id === itemId)) {
                        console.log(`Füge Mitarbeiter ${itemId} zu Dispatch ${dispatchId} hinzu`);
                        targetDispatch.employees.push(foundEmployee);
                    }
                }
            } else {
                // Wenn der Mitarbeiter zur Hauptliste zurückkehren soll
                // Prüfe, ob wir ihn gefunden haben (in einem anderen Dispatch)
                if (foundEmployee) {
                    if (!employees.value.some(emp => emp.id === itemId)) {
                        console.log(`Füge Mitarbeiter ${itemId} zur Hauptliste hinzu`);
                        employees.value.push(foundEmployee);
                        sortEmployees(); // Sortierung beibehalten
                    }
                }
            }
        } else if (itemType === 'vehicle') {
            // Fahrzeug-Objekt finden und speichern, bevor wir es entfernen
            let foundVehicle: Vehicle | undefined;
            
            // Suche in allen Dispatches nach dem Fahrzeug
            dispatches.value.forEach(dispatch => {
                if (dispatch.id !== dispatchId) {
                    const index = dispatch.vehicles.findIndex(veh => veh.id === itemId);
                    if (index !== -1) {
                        // Fahrzeug gefunden - speichern (Kopie erstellen) und aus Dispatch entfernen
                        foundVehicle = {...dispatch.vehicles[index]};
                        console.log(`Entferne Fahrzeug ${itemId} aus Dispatch ${dispatch.id}`);
                        dispatch.vehicles.splice(index, 1);
                    }
                }
            });
            
            // Wenn einem Dispatch zugewiesen wird
            if (dispatchId) {
                // Wenn nicht in einem anderen Dispatch, dann in der Hauptliste suchen
                if (!foundVehicle) {
                    const vehicleIndex = vehicles.value.findIndex(veh => veh.id === itemId);
                    if (vehicleIndex !== -1) {
                        foundVehicle = {...vehicles.value[vehicleIndex]};
                        console.log(`Entferne Fahrzeug ${itemId} aus Hauptliste`);
                        vehicles.value.splice(vehicleIndex, 1);
                    }
                }
                
                // Zum Ziel-Dispatch hinzufügen
                if (foundVehicle) {
                    const targetDispatch = dispatches.value.find(d => d.id === dispatchId);
                    if (targetDispatch && !targetDispatch.vehicles.some(v => v.id === itemId)) {
                        console.log(`Füge Fahrzeug ${itemId} zu Dispatch ${dispatchId} hinzu`);
                        targetDispatch.vehicles.push(foundVehicle);
                    }
                }
            } else {
                // Wenn das Fahrzeug zur Hauptliste zurückkehren soll
                // Prüfe, ob wir es gefunden haben (in einem anderen Dispatch)
                if (foundVehicle) {
                    if (!vehicles.value.some(veh => veh.id === itemId)) {
                        console.log(`Füge Fahrzeug ${itemId} zur Hauptliste hinzu`);
                        vehicles.value.push(foundVehicle);
                    }
                }
            }
        }
        
        // Erzwinge reaktive Aktualisierung aller Elemente
        forceTrigger(dispatches.value);
        forceTrigger(employees.value);
        forceTrigger(vehicles.value);
    } else if (type === 'status') {
        // Status-Update verarbeiten
        const { dispatchId, status } = data;
        const targetDispatch = dispatches.value.find(d => d.id === dispatchId);
        if (targetDispatch) {
            targetDispatch.status = status || null;
        }
    } else if (type === 'absent') {
        // Abwesenheits-Update verarbeiten
        const { employeeId, dispatchId, isAbsent } = data;
        const targetDispatch = dispatches.value.find(d => d.id === dispatchId);
        if (targetDispatch) {
            const emp = targetDispatch.employees.find(e => e.id === employeeId);
            if (emp) {
                emp.is_absent = isAbsent;
                console.log(`Mitarbeiter ${employeeId} Abwesenheitsstatus auf ${isAbsent} gesetzt`);
            }
        }
        // Erzwinge reaktive Aktualisierung
        forceTrigger(dispatches.value);
    }
}

</script>

<style scoped>

/* Custom padding for employee and vehicle columns */
.custom-col-padding {
    padding: 12px 5px !important;
}

.list-card {
    height: calc(92vh - 70px);
    display: flex;
    flex-direction: column;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
}

.list-scroll-area {
    flex-grow: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--v-theme-primary) transparent;
}

.list-scroll-area::-webkit-scrollbar {
    width: 6px;
}

.list-scroll-area::-webkit-scrollbar-thumb {
    background-color: rgba(59, 130, 246, 0.5);
    border-radius: 3px;
}

.list-scroll-area::-webkit-scrollbar-track {
    background: transparent;
}

.sticky-search {
    position: sticky;
    top: 0;
    z-index: 2;
    backdrop-filter: blur(10px);
    border-radius: 8px !important;
}

.source-list {
    min-height: 200px;
}

.draggable-card {
    transition: all 0.2s ease;
    border: 1px solid var(--card-border);
    cursor: grab;
}

.draggable-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.draggable-card:active {
    cursor: grabbing;
    transform: scale(0.98);
}

.dispatch-card {
    background-color: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    overflow: hidden;
}

.dispatch-toolbar {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.drop-zone-header {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    color: var(--v-theme-text);
}

.drop-zone {
    border: 1px dashed var(--drop-zone-border);
    border-radius: 8px;
    background-color: var(--drop-zone-bg);
    transition: all 0.2s ease-in-out;
    position: relative;
}

.drop-zone.employee-drop-zone {
    min-height: 160px;
}

.drop-zone.vehicle-drop-zone {
    min-height: 80px;
}

.drop-zone:hover {
    border-color: var(--v-theme-primary);
    background-color: rgba(59, 130, 246, 0.05);
}

.drop-zone.sortable-ghost {
    background-color: rgba(59, 130, 246, 0.1);
    border: 1px dashed var(--v-theme-primary);
}

.drop-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--placeholder-color);
    font-size: 0.8rem;
    height: 100%;
    min-height: 60px;
}

.empty-list-message {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--placeholder-color);
    font-size: 0.8rem;
    padding: 20px 0;
    text-align: center;
}

.card-toolbar {
    border-bottom: 1px solid var(--card-border);
}

@media (max-width: 600px) {
    .list-card {
        height: auto;
        max-height: 60vh;
    }
}

/* Animation für Drop-Zonen */
.drop-zone.employee-drop-zone {
    position: relative;
    overflow: hidden;
}

/* Remove the animation effect that causes lag */
.drop-zone.employee-drop-zone::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
    /* Remove animation */
    /* animation: shimmer 3s infinite; */
    pointer-events: none;
    /* Move it off screen permanently */
    left: -200%;
}

/* Remove animation keyframes */
/* @keyframes shimmer {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
} */

/* Absence toggle button styling */
.absence-toggle-btn {
    opacity: 0.6;
    transition: opacity 0.2s ease;
    margin-left: 4px;
}

.absence-toggle-btn:hover {
    opacity: 1;
}

/* Absent employee card styling */
.employee-card.employee-absent {
    border-left: 3px solid rgb(var(--v-theme-warning)) !important;
}
</style>
