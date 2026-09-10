<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { apiClientAuth } from '@/api'; // Verwende konfigurierte Axios-Instanz
import { useAuthStore } from '@/stores/auth'; // Importiere Pinia Auth Store
import type { Event } from '@/types/Calendar';
import VueCal from 'vue-cal';
import 'vue-cal/dist/vuecal.css';
import { useToast } from 'vue-toastification';
import GroupManager from '@/components/calendar/GroupManager.vue';

// Store & Router
const authStore = useAuthStore();
const route = useRoute();
const { t } = useI18n();

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

// Check permissions from both route.meta and props
const canEdit = computed(() => props.allPermissions || props.canEdit || !!route.meta.canEdit);
const canDelete = computed(() => props.allPermissions || props.canDelete || !!route.meta.canDelete);

// Zustandsvariablen (vorher data)
const formRef = ref<any | null>(null);
const users = ref<Array<{ id: number; username: string }>>([]);
const groups = ref<Array<{ id: number; name: string; color: string }>>([]);
const calendar = ref<any | null>(null);
const deleteDialog = ref(false);
const eventDialog = ref(false);
const newEventDialog = ref(false);
const isLoading = ref(false); // Neue Loading-State Variableariable exists
const originalEvents = ref<Event[]>([]);
const showAllEvents = ref(false);
const showMore = ref(false);
const clickShowMore = ref(false);
const eventsDayIndices = ref<{ [date: string]: { [index: string]: number } }>({});
const popupDialog = ref(false);
const popupEvents = ref<
    Array<{
        id: number;
        title: string;
        content: string;
        start: string;
        end: string;
        color: string;
        contentFull: string;
        assigned_to: string[];
    }>
>([]);
const popupDate = ref('');
const popupPosition = ref({ x: 0, y: 0 });
const filterOptions = ref({
    assignedTo: [] as number[],
});
const recurring = computed(() => [
    { text: t('calendarView.recurring.none'), value: '' },
    { text: t('calendarView.recurring.daily'), value: 'day' },
    { text: t('calendarView.recurring.monthly'), value: 'month' },
]);
const selectedEvent = ref({
    id: 0,
    title: '',
    content: '',
    start: '',
    end: '',
    color: '',
    contentFull: '',
    assigned_to: [],
    recurring: '',
    is_private: false,
    group_id: null as number | null,
});
const newEvent = ref({
    title: '',
    content: '',
    start: '',
    end: '',
    color: '',
    contentFull: '',
    assigned_to: [],
    recurring: '',
    is_private: false,
    group_id: null as number | null,
});
const predefinedColors = [['#008000'], ['#FFA500'], ['#FF0000']];
const events = ref<Event[]>([]);

// Sidebar collapse state
const isSidebarCollapsed = ref(true); // Default to collapsed

// Toggle sidebar function
const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

// Validierungsregeln
const requiredRule = (value: string) => !!value || t('validation.required');

// Toastification
const toast = useToast();

// Methoden
async function deleteEvent(idEvent: number) {
    try {
        await apiClientAuth.post('/calendar?action=deleteEvent', {
            id: idEvent,
        });

        deleteDialog.value = false;
        eventDialog.value = false;
        await fetchEvents();
    } catch (error: any) {
        toast.error(error.response?.data?.error || t('calendarView.errors.deleteEvent'));
    }
}

async function fetchUsers() {
    try {
        const response = await apiClientAuth.get('/user?action=getUsersWithEmployee');
        users.value = response.data;
    } catch (error: any) {
        toast.error(error.response?.data?.error || t('calendarView.errors.loadUsers'));
    }
}

async function fetchGroups() {
    try {
        const response = await apiClientAuth.get('/calendar?action=getGroups');
        groups.value = response.data;
    } catch (error: any) {
        toast.error(error.response?.data?.error || t('calendarView.errors.loadGroups'));
    }
}

async function fetchEvents() {
    try {
        const response = await apiClientAuth.get('/calendar?action=getEvents');
        originalEvents.value = response.data; // Speichern Sie die ursprüngliche Ereignisliste in originalEvents
        events.value = [...originalEvents.value]; // Setzen Sie die Ereignisliste auf den ursprünglichen Zustand zurück
        popupEvents.value = events.value.filter(event => {
            return formatDate(event.start, true, true).split('T')[0] === popupDate.value;
        });
    } catch (error: any) {
        toast.error(error.response?.data?.error || t('calendarView.errors.loadEvents'));
    }
}

function closePopup() {
    popupDialog.value = false;
}

function handleShowMore(eventDate: string) {
    popupEvents.value = events.value.filter(event => {
        return formatDate(event.start, true, true).split('T')[0] === eventDate;
    });
    popupDate.value = formatDate(eventDate, false, true);
    popupDialog.value = true;
}

function getEventDayIndex(event: Event) {
    const eventDate = formatDate(event.start, true).split('T')[0];
    if (!eventsDayIndices.value[eventDate]) {
        eventsDayIndices.value[eventDate] = {};
    }

    if (eventsDayIndices.value[eventDate][event.id] === undefined) {
        const visibleEvents = events.value.filter(
            e => formatDate(e.start, true).split('T')[0] === eventDate
        );
        visibleEvents.forEach((e, index) => {
            eventsDayIndices.value[eventDate][e.id] = index;
        });
    }

    return eventsDayIndices.value[eventDate][event.id];
}

function isFormValid(exist: boolean) {
    if (exist) return selectedEvent.value.title !== '';
    else return newEvent.value.title !== '';
}

function getEventIndex(event: Event) {
    return events.value.findIndex(e => e.id === event.id);
}

function handleEventClick(event: any) {
    console.log('Event clicked:', event); // Log the event data for debugging

    // Create a clean object from the event (which might be a Proxy)
    const eventData = {
        id: event.id,
        title: event.title,
        content: event.content || '',
        start: event.start,
        end: event.end,
        color: event.color || '',
        contentFull: event.contentFull || '',
        recurring: event.recurring || '',
        // Ensure is_private is a proper boolean, converting from 0/1 if needed
        is_private: event.is_private == '1' ||  event.is_private === true || false,
        group_id: event.group_id || null,
        assigned_to: [] // Start with empty array
    };
    
    // Extract user IDs from assignedUsers objects (new format)
    if (Array.isArray(event.assignedUsers)) {
        eventData.assigned_to = event.assignedUsers
            .map((user: any) => {
                // Check if user is an object with user_id property (new format)
                if (user && typeof user === 'object' && user.user_id) {
                    return Number(user.user_id);
                }
                return null;
            })
            .filter(Boolean); // Remove any nulls
    } 
    // Or use assigned_to if available (old format)
    else if (Array.isArray(event.assigned_to)) {
        eventData.assigned_to = event.assigned_to
            .filter((id: any) => id !== '')
            .map((id: any) => Number(id));
    }
    
    console.log('Extracted user IDs:', eventData.assigned_to);

    selectedEvent.value = eventData;

    // Format dates for the datetime-local input fields
    const timezoneOffset = new Date().getTimezoneOffset() * 60000;
    selectedEvent.value.start = new Date(new Date(event.start).getTime() - timezoneOffset)
        .toISOString()
        .slice(0, -1);
    selectedEvent.value.end = new Date(new Date(event.end).getTime() - timezoneOffset)
        .toISOString()
        .slice(0, -1);

    eventDialog.value = true;
}

function handleCellClick(cell: any) {
    const startDate = formatDate(cell, true);
    const endDate = formatDate(cell, false);

    newEvent.value = {
        title: '',
        content: '',
        start: startDate,
        end: endDate,
        color: '',
        contentFull: '',
        assigned_to: [],
        recurring: '',
        is_private: false,
        group_id: null,
    };
    newEventDialog.value = true;
}

function applyFilters() {
    if (filterOptions.value.assignedTo.length > 0) {
        events.value = originalEvents.value.filter(event =>
            event.assigned_to.some(user => filterOptions.value.assignedTo.includes(Number(user)))
        );
    } else {
        events.value = [...originalEvents.value]; // Setzen Sie die Ereignisliste auf den ursprünglichen Zustand zurück, wenn keine Filter angewendet sind
    }
}

function formatDate(dateInput: string | Date, isStart: boolean, onlyDate = false) {
    const date = new Date(dateInput);
    if (!isStart) {
        date.setHours(date.getHours() + 1);
    }

    const timezoneOffset = date.getTimezoneOffset() * 60000;
    const localISOTime = new Date(date.getTime() - timezoneOffset).toISOString().slice(0, -1);

    const [datePart, timePart] = localISOTime.split('T');
    let [hour, minute] = timePart.split(':');

    // Runde die Minuten auf die nächsten 15-Minuten-Schritte
    const roundedMinute = Math.ceil(parseInt(minute) / 15) * 15;
    if (roundedMinute === 60) {
        // Wenn die gerundeten Minuten 60 sind, erhöhe die Stunden um 1 und setze die Minuten auf 0
        hour = (parseInt(hour) + 1).toString().padStart(2, '0');
        minute = '00';
    } else {
        minute = roundedMinute.toString().padStart(2, '0');
    }

    return onlyDate ? datePart : `${datePart}T${hour}:${minute}`;
}

function formatDateToDDMMYYYY(datePart: any) {
    const [year, month, day] = datePart.split('-');
    const formattedDate = `${day}.${month}.${year}`;
    return formattedDate;
}

function formatDateCalPreview(dateInput: string | Date) {
    const date = new Date(dateInput);
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    return `${hours}:${minutes}`;
}

function handleEventDragEnd(event: any) {
    const updatedEvent = { ...event.event }; // Use event.event instead of event

    if (updatedEvent.assigned_to != undefined) {
        // Extrahieren Sie die Uhrzeit aus dem ursprünglichen Start- und Enddatum
        const originalStart = new Date(event.originalEvent.start);
        const originalEnd = new Date(event.originalEvent.end);

        // Kombinieren Sie das neue Datum mit der ursprünglichen Uhrzeit
        const newStartDate = new Date(event.event.start);
        const newEndDate = new Date(event.event.end);

        newStartDate.setHours(originalStart.getHours(), originalStart.getMinutes());
        newEndDate.setHours(originalEnd.getHours(), originalEnd.getMinutes());

        const timezoneOffset = new Date().getTimezoneOffset() * 60000;
        updatedEvent.start = new Date(new Date(newStartDate).getTime() - timezoneOffset)
            .toISOString()
            .slice(0, -1);
        updatedEvent.end = new Date(new Date(newEndDate).getTime() - timezoneOffset)
            .toISOString()
            .slice(0, -1);

        updatedEvent.assigned_to = updatedEvent.assigned_to.filter((user: any) => user !== ''); // Convert Proxy object to array
        updateEventAfterDrag(updatedEvent);
    }
}

async function updateEventAfterDrag(updatedEvent: Event) {
    try {
        console.log('Update event after drag request data:', updatedEvent); // Log the request data for updateEventAfterDrag()

        const response = await apiClientAuth.put('calendar?action=updateEvent', updatedEvent);

        if (response.status === 200) {
            fetchEvents();
        }
    } catch (error: any) {
        toast.error(error.response?.data?.error || t('calendarView.errors.updateEvent'));
    }
}

async function updateEvent() {
    try {
        isLoading.value = true; // Setze Loading-State

        const requestData = {
            ...selectedEvent.value,
            assigned_to: selectedEvent.value.assigned_to.filter((user: any) => user !== ''),
        };

        console.log('Update event request data:', requestData);

        const response = await apiClientAuth.put('calendar?action=updateEvent', requestData);

        if (response.status === 200) {
            await fetchEvents(); // Warte auf das Neuladen der Events
            eventDialog.value = false; // Schließe Dialog erst nachdem Events geladen wurden
        }
    } catch (error: any) {
        toast.error(error.response?.data?.error || t('calendarView.errors.updateEvent'));
    } finally {
        isLoading.value = false; // Setze Loading-State zurück
    }
}

async function createEvent() {
    try {
        isLoading.value = true; // Setze Loading-State

        const response = await apiClientAuth.post('/calendar?action=createEvent', {
            ...newEvent.value,
            assigned_to: newEvent.value.assigned_to.filter((user: any) => user !== ''),
        });
        
        // Akzeptiere alle erfolgreichen HTTP-Statuscodes (2xx)
        if (response.status >= 200 && response.status < 300) {
            await fetchEvents(); // Warte auf das Neuladen der Events
            newEventDialog.value = false; // Schließe Dialog erst nachdem Events geladen wurden
        }
    } catch (error: any) {
        toast.error(error.response?.data?.error || t('calendarView.errors.createEvent'));
    } finally {
        isLoading.value = false; // Setze Loading-State zurück
    }
}

// Computed Property
const eventsPerDay = computed(() => {
    const eventsByDate: { [date: string]: number } = {};

    events.value.forEach(event => {
        const date = formatDate(new Date(event.start), true).split('T')[0];

        if (eventsByDate[date]) {
            eventsByDate[date]++;
        } else {
            eventsByDate[date] = 1;
        }
    });

    return eventsByDate;
});

// Lifecycle Hooks
onMounted(async () => {
    await fetchUsers();
    await fetchGroups();
    await fetchEvents();
    applyFilters();
    
    // Check for ID from route query or props
    const eventId = route.query.id || props.id || props.meta?.id;
    
    if (eventId) {
        // Find the event with the specified ID
        const event = events.value.find(evt => evt.id === Number(eventId));
        if (event) {
            // Open the event dialog for this event
            handleEventClick(event);
        }
    }
});

// Watch für automatische Privatsphäre-Einstellung bei neuen Terminen
watch(() => newEvent.value.assigned_to, (newVal) => {
    if (newVal && newVal.length > 0) {
        if (!newEvent.value.is_private) {
            newEvent.value.is_private = true;
            toast.info(t('calendarView.autoPrivateMessage'));
        }
    }
});

watch(() => newEvent.value.group_id, (newVal) => {
    if (newVal) {
        if (!newEvent.value.is_private) {
            newEvent.value.is_private = true;
            toast.info(t('calendarView.autoPrivateMessage'));
        }
    }
});

// Watch für automatische Privatsphäre-Einstellung bei Bearbeitung
watch(() => selectedEvent.value.assigned_to, (newVal) => {
    if (newVal && newVal.length > 0) {
        if (!selectedEvent.value.is_private) {
            selectedEvent.value.is_private = true;
            toast.info(t('calendarView.autoPrivateMessage'));
        }
    }
});

watch(() => selectedEvent.value.group_id, (newVal) => {
    if (newVal) {
        if (!selectedEvent.value.is_private) {
            selectedEvent.value.is_private = true;
            toast.info(t('calendarView.autoPrivateMessage'));
        }
    }
});
</script>

<template>
    <div class="calendar-view">
        <v-container fluid>
            <v-row>
                <!-- Calendar column - expands to 12 cols when sidebar is collapsed -->
                <v-col :cols="12" :md="isSidebarCollapsed ? 12 : 8" class="transition-col">
                    <!-- Header mit Titel und Filter -->
                    <v-row class="mb-4">
                        <v-col cols="12">
                            <div class="page-header d-flex align-center justify-space-between flex-wrap">
                                <div>
                                    <h1 class="text-h4 font-weight-medium mb-2">
                                        <v-icon size="36" class="mr-2">mdi-calendar-month</v-icon>
                                        {{ t('calendarView.title') }}
                                    </h1>
                                    <p class="text-body-1 text-medium-emphasis">
                                        {{ t('calendarView.subtitle') }}
                                    </p>
                                </div>

                                <div class="d-flex align-center">
                                    <!-- Toggle sidebar button - moved to header -->
                                    <v-btn
                                        icon
                                        variant="tonal"
                                        color="primary"
                                        size="small"
                                        class="mr-3 toggle-btn"
                                        @click="toggleSidebar"
                                    >
                                        <v-icon>{{ isSidebarCollapsed ? 'mdi-dock-right' : 'mdi-dock-left' }}</v-icon>
                                    </v-btn>

                                </div>
                            </div>
                        </v-col>
                    </v-row>

                    <!-- Kalender Container -->
                    <v-card class="calendar-card" elevation="4">
                        <!-- Kalender -->
                        <vue-cal
                            ref="calendar"
                            :events="events"
                            @event-dblclick="handleEventClick"
                            @event-click="handleEventClick"
                            @cell-click="handleCellClick"
                            @event-drop="handleEventDragEnd"
                            active-view="month"
                            events-on-month-view="short"
                            show-all-day-events="short"
                            :disable-views="['years', 'year', 'day', 'week']"
                            locale="de"
                            :editable-events="{
                                title: true,
                                drag: true,
                                resize: false,
                                delete: true,
                                create: false,
                            }"
                            :draggable="true"
                            :resizable="false"
                            hide-view-selector
                            class="calendar-component"
                        >
                            <template #event="{ event, view }">
                                <div class="event-container">
                                    <div class="event" v-if="getEventDayIndex(event) < 5">
                                        <v-tooltip activator="parent" location="top">
                                            {{ event.title }}
                                            <template v-if="event.group">
                                                <br>
                                                <small>Gruppe: {{ event.group.name }}</small>
                                            </template>
                                        </v-tooltip>
                                        <div class="event-title" style="float: left" v-if="view === 'month'">
                                            <span :style="{ color: event.color }" class="event-dot"
                                                >&#9679;</span
                                            >
                                            <span v-if="event.is_private" class="event-private-icon">🔒</span>
                                            {{ formatDateCalPreview(event.start) }} {{ event.title }}
                                        </div>
                                    </div>
                                    <div
                                        class="show-more"
                                        v-if="
                                            eventsPerDay[formatDate(event.start, true).split('T')[0]] > 4 &&
                                            getEventDayIndex(event) === 5
                                        "
                                        @click.stop="
                                            handleShowMore(formatDate(event.start, true).split('T')[0])
                                        "
                                        data-show-more
                                    >
                                        {{ t('notifications.showMore') }}
                                    </div>
                                </div>
                            </template>
                        </vue-cal>
                    </v-card>

                    <!-- Event Details Dialog -->
                    <v-dialog v-model="eventDialog" max-width="550" class="dialog-container">
                        <v-card class="dialog-card">
                            <v-card-title class="dialog-title">
                                <v-icon color="primary" class="mr-2">mdi-calendar-edit</v-icon>
                                {{ t('calendarView.eventDetails') }}
                                <v-spacer></v-spacer>
                                <v-btn
                                    v-if="canDelete"
                                    color="error"
                                    variant="tonal"
                                    size="small"
                                    @click="deleteDialog = true"
                                    prepend-icon="mdi-delete"
                                    class="delete-button"
                                >
                                    {{ t('delete') }}
                                </v-btn>
                            </v-card-title>

                            <v-card-text class="pt-4">
                                <v-form>
                                    <div class="form-section mb-4">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1">mdi-information-outline</v-icon>
                                            {{ t('calendarView.generalInfo') }}
                                        </div>

                                        <v-row>
                                            <v-col cols="12">
                                                <v-text-field
                                                    v-model.lazy="selectedEvent.title"
                                                    :rules="[requiredRule]"
                                                    :label="t('calendarView.fields.title')"
                                                    required
                                                    variant="outlined"
                                                    density="comfortable"
                                                    color="primary"
                                                    prepend-inner-icon="mdi-format-title"
                                                ></v-text-field>
                                            </v-col>

                                            <v-col cols="12">
                                                <v-text-field
                                                    v-model="selectedEvent.content"
                                                    :label="t('calendarView.fields.subtitle')"
                                                    variant="outlined"
                                                    density="comfortable"
                                                    color="primary"
                                                    prepend-inner-icon="mdi-text-short"
                                                ></v-text-field>
                                            </v-col>
                                        </v-row>
                                    </div>

                                    <div class="form-section mb-4">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1"
                                                >mdi-clock-time-four-outline</v-icon
                                            >
                                            {{ t('calendarView.period') }}
                                        </div>

                                        <v-row>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="selectedEvent.start"
                                                    :label="t('calendarView.fields.startDate')"
                                                    type="datetime-local"
                                                    required
                                                    variant="outlined"
                                                    density="comfortable"
                                                    color="primary"
                                                    prepend-inner-icon="mdi-calendar-start"
                                                ></v-text-field>
                                            </v-col>

                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="selectedEvent.end"
                                                    :label="t('calendarView.fields.endDate')"
                                                    type="datetime-local"
                                                    required
                                                    variant="outlined"
                                                    density="comfortable"
                                                    color="primary"
                                                    prepend-inner-icon="mdi-calendar-end"
                                                ></v-text-field>
                                            </v-col>

                                            <v-col cols="12">
                                                <v-select
                                                    v-model="selectedEvent.recurring"
                                                    :items="recurring"
                                                    item-title="text"
                                                    item-value="value"
                                                    :label="t('calendarView.fields.repeat')"
                                                    variant="outlined"
                                                    density="comfortable"
                                                    color="primary"
                                                    prepend-inner-icon="mdi-repeat"
                                                    chips
                                                ></v-select>
                                            </v-col>
                                        </v-row>
                                    </div>

                                    <div class="form-section mb-4">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1">mdi-text-box-outline</v-icon>
                                            {{ t('calendarView.description') }}
                                        </div>

                                        <v-textarea
                                            v-model="selectedEvent.contentFull"
                                            :label="t('calendarView.fields.description')"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            prepend-inner-icon="mdi-file-document-outline"
                                            auto-grow
                                            rows="3"
                                        ></v-textarea>
                                    </div>

                                    <div class="form-section mb-4">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1">mdi-palette</v-icon>
                                            {{ t('calendarView.appearance') }}
                                        </div>

                                        <v-color-picker
                                            v-model="selectedEvent.color"
                                            :swatches="predefinedColors"
                                            :swatches-label="t('calendarView.predefinedColors')"
                                            hide-inputs
                                            show-swatches
                                            hide-canvas
                                            hide-sliders
                                            mode="hexa"
                                            width="100%"
                                            class="color-picker"
                                        ></v-color-picker>
                                    </div>

                                    <div class="form-section">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1">mdi-account-multiple</v-icon>
                                            {{ t('calendarView.assignedPersons') }}
                                        </div>

                                        <v-select
                                            v-model="selectedEvent.assigned_to"
                                            :items="users"
                                            item-title="username"
                                            item-value="id"
                                            :label="t('calendarView.fields.assignedTo')"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            prepend-inner-icon="mdi-account-multiple-check"
                                            chips
                                            multiple
                                        >
                                            <template v-slot:item="{ props, item }">
                                                <v-list-item v-bind="props">
                                                    <template v-slot:prepend>
                                                        <v-icon :color="item.raw.color">mdi-circle</v-icon>
                                                    </template>
                                                </v-list-item>
                                            </template>
                                        </v-select>
                                    </div>

                                    <div class="form-section">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1">mdi-account-group</v-icon>
                                            {{ t('calendarView.group') }}
                                        </div>

                                        <v-select
                                            v-model="selectedEvent.group_id"
                                            :items="groups"
                                            item-title="name"
                                            item-value="id"
                                            :label="t('calendarView.fields.group')"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            prepend-inner-icon="mdi-account-group"
                                            :clearable="true"
                                        >
                                            <template v-slot:item="{ props, item }">
                                                <v-list-item v-bind="props">
                                                    <template v-slot:prepend>
                                                        <v-icon :color="item.raw.color">mdi-circle</v-icon>
                                                    </template>
                                                </v-list-item>
                                            </template>
                                        </v-select>
                                    </div>

                                    <div class="form-section">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1">mdi-eye</v-icon>
                                            {{ t('calendarView.visibility') }}
                                        </div>

                                        <v-switch
                                            v-model="selectedEvent.is_private"
                                            color="primary"
                                            :label="t('calendarView.fields.privateEvent')"
                                            hide-details
                                            class="mt-2"
                                        ></v-switch>
                                        <div class="text-caption text-medium-emphasis mt-1">
                                            {{ t('calendarView.privateEventInfo') }}
                                        </div>
                                    </div>
                                </v-form>
                            </v-card-text>

                            <v-divider></v-divider>

                            <v-card-actions class="pa-4">
                                <v-spacer></v-spacer>
                                <v-btn variant="text" @click="eventDialog = false" class="mr-2">
                                    {{ t('cancel') }}
                                </v-btn>
                                <v-btn
                                    color="primary"
                                    variant="elevated"
                                    :disabled="!isFormValid(true) || isLoading"
                                    :loading="isLoading"
                                    @click="updateEvent"
                                    class="action-button"
                                >
                                    <v-icon start>mdi-content-save</v-icon>
                                    {{ t('update') }}
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <!-- New Event Dialog -->
                    <v-dialog v-model="newEventDialog" max-width="550" class="dialog-container">
                        <v-card class="dialog-card">
                            <v-card-title class="dialog-title">
                                <v-icon color="primary" class="mr-2">mdi-calendar-plus</v-icon>
                                {{ t('calendarView.newEvent') }}
                            </v-card-title>

                            <v-card-text class="pt-4">
                                <v-form>
                                    <div class="form-section mb-4">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1">mdi-information-outline</v-icon>
                                            {{ t('calendarView.generalInfo') }}
                                        </div>

                                        <v-row>
                                            <v-col cols="12">
                                                <v-text-field
                                                    v-model="newEvent.title"
                                                    :rules="[requiredRule]"
                                                    :label="t('calendarView.fields.title')"
                                                    required
                                                    variant="outlined"
                                                    density="comfortable"
                                                    color="primary"
                                                    prepend-inner-icon="mdi-format-title"
                                                ></v-text-field>
                                            </v-col>

                                            <v-col cols="12">
                                                <v-text-field
                                                    v-model="newEvent.content"
                                                    :label="t('calendarView.fields.subtitle')"
                                                    variant="outlined"
                                                    density="comfortable"
                                                    color="primary"
                                                    prepend-inner-icon="mdi-text-short"
                                                ></v-text-field>
                                            </v-col>
                                        </v-row>
                                    </div>

                                    <div class="form-section mb-4">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1"
                                                >mdi-clock-time-four-outline</v-icon
                                            >
                                            {{ t('calendarView.period') }}
                                        </div>

                                        <v-row>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="newEvent.start"
                                                    :label="t('calendarView.fields.startDate')"
                                                    type="datetime-local"
                                                    required
                                                    variant="outlined"
                                                    density="comfortable"
                                                    color="primary"
                                                    prepend-inner-icon="mdi-calendar-start"
                                                ></v-text-field>
                                            </v-col>

                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="newEvent.end"
                                                    :label="t('calendarView.fields.endDate')"
                                                    type="datetime-local"
                                                    required
                                                    variant="outlined"
                                                    density="comfortable"
                                                    color="primary"
                                                    prepend-inner-icon="mdi-calendar-end"
                                                ></v-text-field>
                                            </v-col>

                                            <v-col cols="12">
                                                <v-select
                                                    v-model="newEvent.recurring"
                                                    :items="recurring"
                                                    item-title="text"
                                                    item-value="value"
                                                    :label="t('calendarView.fields.recurring')"
                                                    variant="outlined"
                                                    density="comfortable"
                                                    color="primary"
                                                    prepend-inner-icon="mdi-repeat"
                                                    chips
                                                    required
                                                ></v-select>
                                            </v-col>
                                        </v-row>
                                    </div>

                                    <div class="form-section mb-4">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1">mdi-text-box-outline</v-icon>
                                            {{ t('calendarView.description') }}
                                        </div>

                                        <v-textarea
                                            v-model="newEvent.contentFull"
                                            :label="t('calendarView.fields.description')"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            prepend-inner-icon="mdi-file-document-outline"
                                            auto-grow
                                            rows="3"
                                        ></v-textarea>
                                    </div>

                                    <div class="form-section mb-4">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1">mdi-palette</v-icon>
                                            {{ t('calendarView.appearance') }}
                                        </div>

                                        <v-color-picker
                                            v-model="newEvent.color"
                                            :swatches="predefinedColors"
                                            :swatches-label="t('calendarView.predefinedColors')"
                                            hide-inputs
                                            show-swatches
                                            hide-canvas
                                            hide-sliders
                                            mode="hexa"
                                            width="100%"
                                            class="color-picker"
                                        ></v-color-picker>
                                    </div>

                                    <div class="form-section">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1">mdi-account-multiple</v-icon>
                                            {{ t('calendarView.assignedPersons') }}
                                        </div>

                                        <v-select
                                            v-model="newEvent.assigned_to"
                                            :items="users"
                                            item-title="username"
                                            item-value="id"
                                            :label="t('calendarView.fields.assignedTo')"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            prepend-inner-icon="mdi-account-multiple-check"
                                            chips
                                            multiple
                                            required
                                        >
                                            <template v-slot:item="{ props, item }">
                                                <v-list-item v-bind="props">
                                                    <template v-slot:prepend>
                                                        <v-icon :color="item.raw.color">mdi-circle</v-icon>
                                                    </template>
                                                </v-list-item>
                                            </template>
                                        </v-select>
                                    </div>

                                    <div class="form-section">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1">mdi-account-group</v-icon>
                                            {{ t('calendarView.group') }}
                                        </div>

                                        <v-select
                                            v-model="newEvent.group_id"
                                            :items="groups"
                                            item-title="name"
                                            item-value="id"
                                            :label="t('calendarView.fields.group')"
                                            variant="outlined"
                                            density="comfortable"
                                            color="primary"
                                            prepend-inner-icon="mdi-account-group"
                                            :clearable="true"
                                        >
                                            <template v-slot:item="{ props, item }">
                                                <v-list-item v-bind="props">
                                                    <template v-slot:prepend>
                                                        <v-icon :color="item.raw.color">mdi-circle</v-icon>
                                                    </template>
                                                </v-list-item>
                                            </template>
                                        </v-select>
                                    </div>

                                    <div class="form-section">
                                        <div class="section-title">
                                            <v-icon size="small" class="mr-1">mdi-eye</v-icon>
                                            {{ t('calendarView.visibility') }}
                                        </div>

                                        <v-switch
                                            v-model="newEvent.is_private"
                                            color="primary"
                                            :label="t('calendarView.fields.privateEvent')"
                                            hide-details
                                            class="mt-2"
                                        ></v-switch>
                                        <div class="text-caption text-medium-emphasis mt-1">
                                            {{ t('calendarView.privateEventInfo') }}
                                        </div>
                                    </div>
                                </v-form>
                            </v-card-text>

                            <v-divider></v-divider>

                            <v-card-actions class="pa-4">
                                <v-spacer></v-spacer>
                                <v-btn variant="text" @click="newEventDialog = false" class="mr-2">
                                    {{ t('cancel') }}
                                </v-btn>
                                <v-btn
                                    color="primary"
                                    variant="elevated"
                                    :disabled="!isFormValid(false) || isLoading"
                                    :loading="isLoading"
                                    @click="createEvent"
                                    class="action-button"
                                >
                                    <v-icon start>mdi-plus</v-icon>
                                    {{ t('create') }}
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <!-- Delete Confirmation Dialog -->
                    <v-dialog v-model="deleteDialog" max-width="500" class="delete-dialog">
                        <v-card class="dialog-card">
                            <v-card-title class="text-h5 dialog-title">
                                <v-icon color="error" class="mr-2">mdi-delete-alert</v-icon>
                                {{ t('calendarView.confirmDelete') }}
                            </v-card-title>

                            <v-card-text class="pt-4">
                                <p>{{ t('calendarView.deleteEventConfirm') }}</p>
                                <div class="text-caption text-medium-emphasis mt-2">
                                    {{ t('calendarView.deleteWarning') }}
                                </div>
                            </v-card-text>

                            <v-divider></v-divider>

                            <v-card-actions class="pa-4">
                                <v-spacer></v-spacer>
                                <v-btn variant="text" @click="deleteDialog = false" class="mr-2">
                                    {{ t('cancel') }}
                                </v-btn>
                                <v-btn
                                    color="error"
                                    variant="elevated"
                                    @click="deleteEvent(selectedEvent.id)"
                                    class="delete-button"
                                >
                                    <v-icon start>mdi-delete</v-icon>
                                    {{ t('delete') }}
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <!-- Popup Dialog für "Mehr anzeigen" -->
                    <v-dialog v-model="popupDialog" max-width="450" class="popup-dialog">
                        <v-card class="dialog-card">
                            <v-card-title class="dialog-title">
                                <v-icon color="primary" class="mr-2">mdi-calendar-month-outline</v-icon>
                                {{ t('calendarView.eventsOn', { date: formatDateToDDMMYYYY(popupDate) }) }}
                            </v-card-title>

                            <v-card-text class="pt-4">
                                <v-list class="event-list">
                                    <v-list-item
                                        v-for="event in popupEvents"
                                        :key="event.id"
                                        @click="handleEventClick(event)"
                                        class="event-list-item"
                                    >
                                        <div class="event">
                                            <v-tooltip activator="parent" location="top">
                                                {{ event.title }}
                                            </v-tooltip>
                                            <div class="event-title">
                                                <span :style="{ color: event.color }" class="event-dot"
                                                    >&#9679;</span
                                                >
                                                {{ formatDateCalPreview(event.start) }} {{ event.title }}
                                            </div>
                                        </div>
                                    </v-list-item>
                                </v-list>
                            </v-card-text>

                            <v-divider></v-divider>

                            <v-card-actions class="pa-4">
                                <v-spacer></v-spacer>
                                <v-btn color="primary" variant="text" @click="popupDialog = false">
                                    {{ t('close') }}
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                </v-col>
                <!-- Right sidebar with collapsible behavior -->
                <v-col 
                    cols="12" 
                    :md="isSidebarCollapsed ? 0 : 4" 
                    class="sidebar-container transition-col" 
                    :class="{ 'collapsed': isSidebarCollapsed }"
                >
                    <!-- Group Manager -->
                    <div class="sidebar-content">
                        <GroupManager />
                    </div>
                </v-col>
            </v-row>
        </v-container>
    </div>
</template>

<style scoped>
.calendar-view {
  padding: 16px;
}

/* Transition for column width changes */
.transition-col {
  transition: all 0.3s ease;
}

/* Sidebar collapsible functionality */
.sidebar-container {
  position: relative;
  transition: all 0.3s ease;
  overflow: hidden;
}

.sidebar-container.collapsed {
  max-width: 0 !important;
  width: 0 !important;
  padding: 0 !important;
  margin: 0 !important;
  opacity: 0;
}

/* Toggle button styling */
.toggle-btn {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
}

.sidebar-content {
  transition: opacity 0.3s ease, transform 0.3s ease;
  padding-right: 16px;
}

/* Main container */
.main-container {
  min-height: 89vh;
  background-color: var(--k-canvas);
  color: #e2e8f0;
}

/* Page Header */
.page-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--k-line);
}

/* Action Button */
.action-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all var(--transition-timing);
}

.action-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 6px 12px var(--k-accent-weak);
}

/* Calendar Card */
.calendar-card {
    background: var(--card-bg) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 24px;
}

/* Filter Menu */
.filter-menu {
    margin-right: 8px;
}

.filter-card {
    background: var(--k-surface) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 8px;
    overflow: hidden;
}

.filter-list-item {
    padding: 4px 8px;
    min-height: 0;
}

/* Calendar Styling */
:deep(.vuecal--month-view .vuecal__cell) {
    height: 175px;
}

:deep(.vuecal--month-view .vuecal__cell-content) {
    justify-content: flex-start;
    height: 100%;
    align-items: flex-end;
}

:deep(.vuecal--month-view .vuecal__cell-date) {
    padding: 4px;
}

:deep(.vuecal--month-view .vuecal__no-event) {
    display: none;
}

.event-title {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    display: inline-block;
}

.event-dot {
    margin-right: 4px;
    font-size: 14px;
}

:deep(.vuecal__event) {
    color: var(--k-ink);
    background-color: rgba(30, 30, 30, 0.8);
    position: relative;
    box-sizing: border-box;
    left: 0;
    width: 100%;
    z-index: 1;
    transition:
        box-shadow 0.3s,
        left 0.3s,
        width 0.3s;
    overflow: hidden;
    border-radius: 4px;
    margin-bottom: 2px;
    padding: 4px 6px;
    border-left: 3px solid currentColor;
}

:deep(.vuecal__cell--today),
:deep(.vuecal__cell--current) {
    background-color: rgba(0, 0, 0, 0.4);
    z-index: 1;
}

:deep(.vuecal__cell--selected) {
    background-color: rgba(102, 102, 102, 0.4);
    z-index: 2;
}

:deep(.vuecal:not(.vuecal--dragging-event) .vuecal__event:hover) {
    z-index: 2;
    background-color: rgba(104, 104, 104, 0.8);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

:deep(.vuecal__cell-events) {
    width: 100%;
    margin-top: -6px;
}

.show-more {
    cursor: pointer;
    padding: 4px 8px;
    margin-top: 4px;
    background: var(--k-accent-weak);
    color: var(--k-accent);
    border-radius: 4px;
    font-size: 0.85rem;
    text-align: center;
    transition: all var(--transition-timing);
}

.show-more:hover {
    background: var(--k-accent-weak);
}

/* Event List in Popup */
.event-list {
    max-height: 300px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--v-theme-primary) transparent;
    background: var(--k-sunken);
    border-radius: 8px;
}

.event-list::-webkit-scrollbar {
    width: 6px;
}

.event-list::-webkit-scrollbar-thumb {
    background-color: var(--k-accent-line);
    border-radius: 3px;
}

.event-list::-webkit-scrollbar-track {
    background: transparent;
}

.event-list-item {
    cursor: pointer;
    transition: background 0.2s;
    border-radius: 4px;
    margin: 4px;
}

.event-list-item:hover {
    background: var(--k-accent-weak);
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

/* Color Picker */
.color-picker {
    background: var(--k-sunken) !important;
    border-radius: 8px;
    overflow: hidden;
    padding: 8px;
}

/* Dialog Styling */
.dialog-container :deep(.v-overlay__content),
.delete-dialog :deep(.v-overlay__content),
.popup-dialog :deep(.v-overlay__content) {
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

.dialog-title {
    background: linear-gradient(90deg, var(--k-accent-hover), var(--k-accent));
    color: var(--k-ink);
    padding: 16px;
}

.delete-dialog .dialog-title {
    background: linear-gradient(90deg, #991b1b, #dc2626);
}

.delete-button {
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    transition: all var(--transition-timing);
}

.delete-button:hover {
    transform: var(--button-hover-translate);
    box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
}

/* Responsive Adjustments */
@media (max-width: 600px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .page-header .action-button {
        align-self: stretch;
    }
}

.event-private-icon {
    margin-right: 4px;
    font-size: 12px;
}
</style>
