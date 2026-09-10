<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import { useRoute } from 'vue-router'; // Import if permissions needed
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import ErrorSnackbar from '@/components/ErrorSnackbar.vue'; // Import custom snackbar
import { useI18n } from 'vue-i18n';

// --- Define Interfaces ---
interface Weather {
    id: number;
    date: string; // Expect YYYY-MM-DD from API and date picker
    day: string;
    min_temp: number;
    max_temp: number;
    icon: string; // MDI icon name string (e.g., 'mdi-weather-sunny')
    humidity: number;
    warning: string | null; // Allow null for optional warning
    wind_speed: number;
}

interface IconOption {
    name: string; // User-friendly name
    icon: string; // MDI icon name string
}

interface WeatherFormData {
    id?: number | null;
    date: string | null;
    day: string | null;
    min_temp: number | null;
    max_temp: number | null;
    icon: string | null;
    humidity: number | null;
    warning: string | null;
    wind_speed: number | null;
}

// --- Router & Permissions ---
const route = useRoute(); // If using route meta for permissions
const { t } = useI18n();
const canEdit = computed(() => true); // Replace with route.meta check if needed
const canDelete = computed(() => true); // Replace with route.meta check if needed

// --- Component State ---
const weatherData = ref<Weather[]>([]);
const loadingWeather = ref(false);
const savingWeather = ref(false);
const deletingWeather = ref(false);

// --- Dialog States & Data ---
const addEditDialog = ref(false);
const confirmDeleteDialog = ref(false);
const weatherFormRef = ref<any>(null);
const isWeatherFormValid = ref(false);

const initialFormData: WeatherFormData = {
    id: null,
    date: null,
    day: null,
    min_temp: null,
    max_temp: null,
    icon: null,
    humidity: null,
    warning: null,
    wind_speed: null,
};
const selectedWeather = reactive<WeatherFormData>({ ...initialFormData });
const isEditing = computed(() => !!selectedWeather.id);
const itemToDelete = ref<Weather | null>(null);

// --- Static Data ---
const icons: IconOption[] = [
    { name: t('weatherView.sunny'), icon: 'mdi-weather-sunny' },
    { name: t('weatherView.partlyCloudy'), icon: 'mdi-weather-partly-cloudy' },
    { name: t('weatherView.rainy'), icon: 'mdi-weather-rainy' },
    { name: t('weatherView.cloudy'), icon: 'mdi-weather-cloudy' },
    { name: t('weatherView.thunderstorm'), icon: 'mdi-weather-lightning' },
    { name: t('weatherView.thunderstormRain'), icon: 'mdi-weather-lightning-rainy' },
    { name: t('weatherView.windy'), icon: 'mdi-weather-windy' },
    { name: t('weatherView.foggy'), icon: 'mdi-weather-fog' },
    { name: t('weatherView.snow'), icon: 'mdi-weather-snowy' },
    { name: t('weatherView.sleet'), icon: 'mdi-weather-snowy-rainy' },
];
const daysOfWeek: string[] = [
    t('weatherView.monday'),
    t('weatherView.tuesday'),
    t('weatherView.wednesday'),
    t('weatherView.thursday'),
    t('weatherView.friday'),
    t('weatherView.saturday'),
    t('weatherView.sunday'),
];

// Add this function to your script section
const getIconColor = (iconValue: string | null): string => {
    if (!iconValue) return 'grey';

    // Color mapping based on weather conditions
    const colorMap: { [key: string]: string } = {
        'mdi-weather-sunny': 'amber',
        'mdi-weather-partly-cloudy': 'light-blue',
        'mdi-weather-rainy': 'blue',
        'mdi-weather-cloudy': 'grey',
        'mdi-weather-lightning': 'purple',
        'mdi-weather-lightning-rainy': 'deep-purple',
        'mdi-weather-windy': 'blue-grey',
        'mdi-weather-fog': 'grey-lighten-1',
        'mdi-weather-snowy': 'cyan-lighten-4',
        'mdi-weather-snowy-rainy': 'cyan-lighten-2',
    };

    return colorMap[iconValue] || 'grey';
};

// --- Snackbar ---
const errorSnackbar = ref({ visible: false, message: '', color: 'error' });
function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    errorSnackbar.value.message = message;
    errorSnackbar.value.color = color;
    errorSnackbar.value.visible = true;
}

// --- Table Headers ---
const weatherHeaders = ref([
    { title: t('weatherView.date'), key: 'date', sortable: true },
    { title: t('weatherView.day'), key: 'day', sortable: true },
    { title: t('weatherView.minTemp'), key: 'min_temp', sortable: true, align: 'end' },
    { title: t('weatherView.maxTemp'), key: 'max_temp', sortable: true, align: 'end' },
    { title: t('weatherView.icon'), key: 'icon', sortable: false, align: 'center' },
    { title: t('weatherView.humidity'), key: 'humidity', sortable: true, align: 'end' },
    { title: t('weatherView.wind'), key: 'wind_speed', sortable: true, align: 'end' },
    { title: t('weatherView.warning'), key: 'warning', sortable: false },
    { title: t('weatherView.actions'), key: 'actions', sortable: false, align: 'end', width: '120px' },
] as const);

// --- Validation Rules ---
const requiredRule = (value: any) => !!value || t('weatherView.fieldRequired');
// Add more specific rules if needed (e.g., temperature range)

// --- Data Fetching ---
const fetchWeatherData = async () => {
    loadingWeather.value = true;
    try {
        const response = await apiClientAuth.get<Weather[]>('/admin/weather?action=getWeather'); // Adjust path
        // Ensure date is in YYYY-MM-DD format if needed for date picker, sort by date descending
        weatherData.value = (response.data || response.data || [])
            .map(w => ({
                ...w,
                date: w.date ? w.date.split(' ')[0] : '', // Extract date part
            }))
            .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
    } catch (error: any) {
        console.error('Error fetching weather data:', error);
        showSnackbar(error.response?.data?.error || t('weatherView.loadError'), 'error');
        weatherData.value = [];
    } finally {
        loadingWeather.value = false;
    }
};

// --- Methods ---

// Dialog Openers
const openNewWeatherDialog = () => {
    Object.assign(selectedWeather, { ...initialFormData }); // Reset form
    isWeatherFormValid.value = false;
    addEditDialog.value = true;
    setTimeout(() => weatherFormRef.value?.resetValidation(), 100);
};

const openEditWeatherDialog = (weather: Weather) => {
    Object.assign(selectedWeather, { ...weather }); // Load data
    isWeatherFormValid.value = false;
    addEditDialog.value = true;
    setTimeout(() => weatherFormRef.value?.resetValidation(), 100);
};

// Dialog Closer
const closeAddEditDialog = () => {
    addEditDialog.value = false;
};

// Save Weather (Add/Edit)
const saveWeather = async () => {
    if (!isWeatherFormValid.value) return;
    savingWeather.value = true;

    const action = isEditing.value ? 'updateWeather' : 'insertWeather';
    const payload = { ...selectedWeather }; // Send reactive object directly

    try {
        await apiClientAuth.post(`/admin/weather?action=${action}`, payload); // Adjust path
        closeAddEditDialog();
        await fetchWeatherData(); // Refresh list
        showSnackbar(
            isEditing.value ? t('weatherView.updateSuccess') : t('weatherView.addSuccess'),
            'success'
        );
    } catch (error: any) {
        console.error(`Error saving weather (Action: ${action}):`, error);
        showSnackbar(
            error.response?.data?.error || t('weatherView.saveError'),
            'error'
        );
    } finally {
        savingWeather.value = false;
    }
};

// Delete Logic
const openConfirmDeleteDialog = (item: Weather) => {
    itemToDelete.value = item;
    confirmDeleteDialog.value = true;
};

const closeConfirmDeleteDialog = () => {
    confirmDeleteDialog.value = false;
    itemToDelete.value = null;
};

const proceedWithDelete = async () => {
    if (!itemToDelete.value) return;
    deletingWeather.value = true;
    try {
        await apiClientAuth.post('/admin/weather?action=deleteWeather', {
            id: itemToDelete.value.id,
        }); // Adjust path
        closeConfirmDeleteDialog();
        await fetchWeatherData(); // Refresh list
        showSnackbar(t('weatherView.deleteSuccess'), 'success');
    } catch (error: any) {
        console.error('Error deleting weather:', error);
        showSnackbar(
            error.response?.data?.error || t('weatherView.deleteError'),
            'error'
        );
    } finally {
        deletingWeather.value = false;
    }
};

// --- Utility ---
const formatDate = (dateString?: string | null): string => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString; // Return original if invalid
        return date.toLocaleDateString('de-DE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    } catch (e) {
        return t('weatherView.error');
    }
};

const getIconName = (iconValue: string | null): string => {
    return icons.find(i => i.icon === iconValue)?.name ?? t('weatherView.unknown');
};

// --- Lifecycle Hooks ---
onMounted(() => {
    fetchWeatherData();
});
</script>

<template>
    <ErrorSnackbar v-model="errorSnackbar" />
    <v-container fluid class="pa-4">
        <v-row class="mb-4 align-center">
            <v-col cols="auto">
                <div class="d-flex align-center">
                    <v-icon
                        icon="mdi-weather-partly-cloudy"
                        size="24"
                        class="mr-2 text-primary"
                    ></v-icon>
                    <h1 class="text-h5 font-weight-medium mb-0">{{ t('weatherView.title') }}</h1>
                </div>
            </v-col>
            <v-col>
                <v-alert
                    border="start"
                    border-color="primary"
                    elevation="2"
                    density="comfortable"
                    icon="mdi-information-outline"
                    variant="tonal"
                    class="mt-0 info-alert"
                >
                    {{ t('weatherView.description') }}
                </v-alert>
            </v-col>
        </v-row>

        <v-card class="main-card elevation-4 weather-card">
            <v-toolbar flat density="compact" color="transparent" class="card-toolbar px-4 py-2">
                <v-toolbar-title class="text-h6">
                    <v-icon start size="20" class="mr-2">mdi-calendar-clock</v-icon>
                    {{ t('weatherView.forecast') }}
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn
                    v-if="canEdit"
                    @click="openNewWeatherDialog"
                    color="primary"
                    variant="elevated"
                    prepend-icon="mdi-plus"
                    size="small"
                    class="action-button"
                >
                    {{ t('weatherView.newEntry') }}
                </v-btn>
            </v-toolbar>

            <v-divider></v-divider>

            <!-- Filterleiste: Anzahl der Eintraege, wie im Entwurf. -->
            <div class="k-toolbar">
                <span class="k-toolbar__spacer"></span>
                <span class="k-toolbar__count">{{ $t("common.entries", { n: (weatherData || []).length }) }}</span>
            </div>
            <v-data-table
                :headers="weatherHeaders"
                :items="weatherData"
                item-value="id"
                :loading="loadingWeather"
                hover
                density="comfortable"
            >
                <template v-slot:[`item.date`]="{ item }">
                    <div class="date-cell">
                        <v-icon size="small" icon="mdi-calendar" class="mr-2 text-primary"></v-icon>
                        {{ formatDate(item.date) }}
                    </div>
                </template>

                <template v-slot:[`item.day`]="{ item }">
                    <v-chip size="small" variant="tonal" color="blue-grey" class="day-chip">
                        {{ item.day }}
                    </v-chip>
                </template>

                <template v-slot:[`item.min_temp`]="{ item }">
                    <span
                        :class="
                            item.min_temp < 0
                                ? 'text-blue-lighten-3'
                                : item.min_temp > 25
                                  ? 'text-orange-lighten-1'
                                  : ''
                        "
                    >
                        {{ item.min_temp }} °C
                    </span>
                </template>

                <template v-slot:[`item.max_temp`]="{ item }">
                    <span
                        :class="
                            item.max_temp < 0
                                ? 'text-blue-lighten-3'
                                : item.max_temp > 30
                                  ? 'text-deep-orange'
                                  : item.max_temp > 25
                                    ? 'text-orange'
                                    : ''
                        "
                    >
                        {{ item.max_temp }} °C
                    </span>
                </template>

                <template v-slot:[`item.humidity`]="{ item }">
                    <div class="d-flex align-center">
                        <v-icon
                            size="small"
                            icon="mdi-water-percent"
                            :color="
                                item.humidity > 80 ? 'blue' : item.humidity < 30 ? 'amber' : 'grey'
                            "
                            class="mr-1"
                        ></v-icon>
                        {{ item.humidity }} %
                    </div>
                </template>

                <template v-slot:[`item.wind_speed`]="{ item }">
                    <div class="d-flex align-center">
                        <v-icon
                            size="small"
                            icon="mdi-weather-windy"
                            :color="
                                item.wind_speed > 40
                                    ? 'red'
                                    : item.wind_speed > 25
                                      ? 'amber'
                                      : 'grey'
                            "
                            class="mr-1"
                        ></v-icon>
                        {{ item.wind_speed }} km/h
                    </div>
                </template>

                <template v-slot:[`item.icon`]="{ item }">
                    <div class="weather-icon-cell">
                        <v-tooltip location="top">
                            <template v-slot:activator="{ props }">
                                <v-icon
                                    v-bind="props"
                                    :icon="item.icon || 'mdi-help-circle-outline'"
                                    size="24"
                                    :color="getIconColor(item.icon || null)"
                                ></v-icon>
                            </template>
                            <span>{{ getIconName(item.icon || null) }}</span>
                        </v-tooltip>
                    </div>
                </template>

                <template v-slot:[`item.warning`]="{ item }">
                    <v-chip
                        v-if="item.warning"
                        size="small"
                        color="warning"
                        variant="flat"
                        class="warning-chip"
                        prepend-icon="mdi-alert"
                    >
                        {{ item.warning }}
                    </v-chip>
                    <span v-else class="text-grey text-caption">-</span>
                </template>

                <template v-slot:[`item.actions`]="{ item }">
                    <div class="d-flex gap-1">
                        <v-tooltip :text="t('edit')" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canEdit"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="openEditWeatherDialog(item)"
                                    v-bind="props"
                                    class="action-icon"
                                >
                                    <v-icon size="small">mdi-pencil</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>

                        <v-tooltip :text="t('delete')" location="top">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    v-if="canDelete"
                                    icon
                                    variant="text"
                                    size="small"
                                    @click="openConfirmDeleteDialog(item)"
                                    v-bind="props"
                                    color="error"
                                    class="action-icon"
                                >
                                    <v-icon size="small">mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>
                    </div>
                </template>

                <template v-slot:no-data>
                    <div class="empty-state">
                        <v-icon size="40" color="grey-darken-1" class="mb-2"
                            >mdi-weather-cloudy-alert</v-icon
                        >
                        <span>{{ t('weatherView.noData') }}</span>
                    </div>
                </template>

                <template v-slot:loading>
                    <div class="loading-state">
                        <v-progress-circular
                            indeterminate
                            color="primary"
                            size="24"
                            class="mr-2"
                        ></v-progress-circular>
                        <span>{{ t('weatherView.loading') }}</span>
                    </div>
                </template>
            </v-data-table>
        </v-card>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="addEditDialog" persistent max-width="700px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon
                        :icon="isEditing ? 'mdi-weather-cloudy-clock' : 'mdi-weather-cloudy-plus'"
                        class="mr-2"
                    ></v-icon>
                    {{ isEditing ? t('weatherView.editEntry') : t('weatherView.newEntryTitle') }}
                </v-card-title>

                <v-form ref="weatherFormRef" v-model="isWeatherFormValid">
                    <v-card-text class="pa-4">
                        <v-container>
                            <v-row>
                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model="selectedWeather.date"
                                        :label="t('weatherView.date')"
                                        required
                                        type="date"
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-calendar"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-select
                                        v-model="selectedWeather.day"
                                        :items="daysOfWeek"
                                        :label="t('weatherView.weekday')"
                                        required
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-calendar-week"
                                    ></v-select>
                                </v-col>

                                <v-col cols="12">
                                    <div class="weather-icon-selector mb-4">
                                        <div class="weather-icon-label">
                                            <v-icon
                                                icon="mdi-weather-partly-cloudy"
                                                size="small"
                                                class="mr-2"
                                            ></v-icon>
                                            <span class="text-subtitle-2">{{ t('weatherView.weatherIcon') }}</span>
                                        </div>

                                        <div class="weather-icons-grid mt-2">
                                            <v-btn
                                                v-for="icon in icons"
                                                :key="icon.icon"
                                                :variant="
                                                    selectedWeather.icon === icon.icon
                                                        ? 'elevated'
                                                        : 'tonal'
                                                "
                                                :color="
                                                    selectedWeather.icon === icon.icon
                                                        ? 'primary'
                                                        : undefined
                                                "
                                                class="weather-icon-btn"
                                                @click="selectedWeather.icon = icon.icon"
                                            >
                                                <v-icon
                                                    :icon="icon.icon"
                                                    size="24"
                                                    :color="getIconColor(icon.icon || null)"
                                                ></v-icon>
                                            </v-btn>
                                        </div>
                                    </div>
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model.number="selectedWeather.min_temp"
                                        :label="t('weatherView.minTemp')"
                                        required
                                        type="number"
                                        suffix="°C"
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-thermometer-low"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model.number="selectedWeather.max_temp"
                                        :label="t('weatherView.maxTemp')"
                                        required
                                        type="number"
                                        suffix="°C"
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-thermometer-high"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model.number="selectedWeather.humidity"
                                        :label="t('weatherView.humidity')"
                                        required
                                        type="number"
                                        suffix="%"
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-water-percent"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12" sm="6">
                                    <v-text-field
                                        v-model.number="selectedWeather.wind_speed"
                                        :label="t('weatherView.windSpeed')"
                                        required
                                        type="number"
                                        suffix="km/h"
                                        :rules="[requiredRule]"
                                        variant="outlined"
                                        density="comfortable"
                                        color="primary"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-weather-windy"
                                    ></v-text-field>
                                </v-col>

                                <v-col cols="12">
                                    <v-text-field
                                        v-model="selectedWeather.warning"
                                        :label="t('weatherView.weatherWarning')"
                                        variant="outlined"
                                        density="comfortable"
                                        color="warning"
                                        bg-color="grey-darken-3"
                                        prepend-inner-icon="mdi-alert-outline"
                                        :hint="t('weatherView.warningHint')"
                                        persistent-hint
                                    ></v-text-field>
                                </v-col>
                            </v-row>
                        </v-container>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeAddEditDialog">{{ t('cancel') }}</v-btn>
                        <v-btn
                            color="primary"
                            variant="elevated"
                            @click="saveWeather"
                            :disabled="!isWeatherFormValid"
                            :loading="savingWeather"
                        >
                            {{ t('save') }}
                        </v-btn>
                    </v-card-actions>
                </v-form>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="confirmDeleteDialog" persistent max-width="500px">
            <v-card class="dialog-card">
                <v-card-title class="dialog-title">
                    <v-icon icon="mdi-delete-alert" color="error" class="mr-2"></v-icon>
                    {{ t('weatherView.confirmDelete') }}
                </v-card-title>

                <v-card-text class="pt-4">
                    <p>
                        {{ t('weatherView.deleteConfirm', { date: formatDate(itemToDelete?.date || null) }) }}
                    </p>

                    <div class="delete-weather-preview mt-4 pa-3 rounded">
                        <div class="d-flex align-center mb-2">
                            <v-icon
                                :icon="itemToDelete?.icon || 'mdi-help-circle-outline'"
                                :color="getIconColor(itemToDelete?.icon || null)"
                                size="20"
                                class="mr-2"
                            ></v-icon>
                            <span>{{ getIconName(itemToDelete?.icon || null) }}</span>
                        </div>

                        <div class="d-flex justify-space-between">
                            <span>
                                <v-icon icon="mdi-thermometer" size="small" class="mr-1"></v-icon>
                                {{ itemToDelete?.min_temp }}°C - {{ itemToDelete?.max_temp }}°C
                            </span>

                            <span>
                                <v-icon icon="mdi-water-percent" size="small" class="mr-1"></v-icon>
                                {{ itemToDelete?.humidity }}%
                            </span>

                            <span>
                                <v-icon icon="mdi-weather-windy" size="small" class="mr-1"></v-icon>
                                {{ itemToDelete?.wind_speed }} km/h
                            </span>
                        </div>
                    </div>

                    <div class="text-caption text-medium-emphasis mt-2">
                        {{ t('weatherView.deleteWarning') }}
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-4">
                    <v-spacer></v-spacer>
                    <v-btn variant="text" @click="closeConfirmDeleteDialog" class="mr-2"
                        >{{ t('cancel') }}</v-btn
                    >
                    <v-btn
                        color="error"
                        variant="elevated"
                        @click="proceedWithDelete"
                        :loading="deletingWeather"
                        class="delete-button"
                        prepend-icon="mdi-delete"
                    >
                        {{ t('delete') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style scoped>

/* Main Container */
.weather-container {
    min-height: 90vh;
    background-color: var(--k-ink);
    background-image:
        radial-gradient(circle at 20% 30%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
}

/* Action Button */
.action-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.3s ease;
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

/* Info Alert */
.info-alert {
    background-color: rgba(var(--v-theme-primary-rgb), 0.08) !important;
    border-left-width: 4px !important;
}

/* Main Card */
.main-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
}

.weather-card {
    background: linear-gradient(
        to bottom right,
        rgba(15, 23, 42, 0.8),
        rgba(30, 64, 175, 0.1)
    ) !important;
}

/* Card Toolbar */
.card-toolbar {
    background-color: rgba(30, 41, 59, 0.3) !important;
    border-bottom: 1px solid var(--card-border);
}

/* Day Chip */
.day-chip {
    min-width: 80px;
    justify-content: center;
}

/* Weather Icon Cell */
.weather-icon-cell {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 32px;
}

.date-cell {
    display: flex;
    align-items: center;
}

.warning-chip {
    font-size: 0.75rem;
}

/* Weather Icon Selector */
.weather-icon-selector {
    background: rgba(30, 41, 59, 0.4);
    border-radius: 8px;
    padding: 16px;
    border: 1px solid var(--k-line);
}

.weather-icon-label {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.weather-icons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
    gap: 8px;
}

.weather-icon-btn {
    aspect-ratio: 1;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.weather-icon-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Delete Preview */
.delete-weather-preview {
    background: rgba(30, 41, 59, 0.4);
    border: 1px solid var(--k-line);
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

/* Empty and Loading States */
.empty-state,
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
    color: var(--v-theme-text-secondary);
    text-align: center;
}

.loading-state {
    flex-direction: row;
    padding: 20px;
}

/* Dialog Styling */
.dialog-card {
    background-color: var(--k-ink) !important;
    border: 1px solid var(--k-line);
    border-radius: 12px;
    overflow: hidden;
}

.dialog-title {
    background: linear-gradient(90deg, #1e3a8a, var(--k-accent-hover));
    color: var(--k-ink);
    padding: 16px;
}

.delete-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all 0.2s ease;
}

.delete-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(244, 67, 54, 0.3);
}

/* Animation effects */
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

/* Responsive adjustments */
@media (max-width: 600px) {
    .weather-icons-grid {
        grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
    }
}
</style>
