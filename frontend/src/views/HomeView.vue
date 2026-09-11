<template>
    <v-container fluid>
        <v-row justify="center" class="mt-2 mb-4">
             <h1 class="text-h4 font-weight-light">{{$t("home.welcome")}}</h1>
        </v-row>
         <v-divider class="border-opacity-50 mb-6" ></v-divider>

        <v-row>
            <v-col cols="12" md="4">
                <v-card class="mb-5 fill-height">
                    <v-img
                        :src="EventsToday.length > 0 ? '/img/dashboard/calendarOverviewTop400.png' : '/img/dashboard/calendarOverviewTop400Free_2.png'"
                        height="150"
                        cover
                        class="text-white align-end"
                        gradient="to bottom, rgba(0,0,0,.1), rgba(0,0,0,.5)"
                    >
                         <v-card-title class="text-h6 pb-2">{{$t("home.todayEvents")}}</v-card-title>
                    </v-img>

                    <v-card-text>
                        <v-timeline density="compact" align="start" v-if="!loadingEvents && EventsToday.length > 0" line-inset="8">
                            <v-timeline-item
                                v-for="event in EventsToday"
                                :key="`${event.start}-${event.id}`" :dot-color="event.color || 'primary'"
                                size="x-small"
                            >
                                <div class="mb-3">
                                    <div class="font-weight-normal text-caption text-medium-emphasis">
                                         {{ formatDateCalPreview(event.start) }} {{ $t("home.clock") }}
                                    </div>
                                    <p class="font-weight-medium mb-1">
                                        {{ event.title }}
                                        <v-chip v-if="event.assigned_to && Object.keys(event.assigned_to).length > 0" size="x-small" class="ml-1" label :color="event.color || 'primary'" variant="tonal">
                                            <v-icon start size="small">mdi-account-multiple-outline</v-icon>
                                            {{ formatAssignedUser(event.assigned_to) }}
                                             <v-tooltip activator="parent" location="top" max-width="300">
                                                 <div v-for="user in event.assigned_to" :key="user.user_id">{{ user.username }}</div>
                                             </v-tooltip>
                                        </v-chip>
                                    </p>
                                    </div>
                            </v-timeline-item>
                        </v-timeline>
                         <div v-else-if="loadingEvents" class="text-center pa-5"><v-progress-circular indeterminate size="24"></v-progress-circular></div>
                         <div v-else class="text-center pa-5 text-grey">{{$t("home.noEventsToday")}}</div>
                    </v-card-text>
                </v-card>

                <v-card class="fill-height">
                     <v-img
                         :src="Events7Days.length > 0 ? '/img/dashboard/calendarOverviewTop400.png' : '/img/dashboard/calendarOverviewTop400Free_2.png'"
                         height="150"
                         cover
                         class="text-white align-end"
                         gradient="to bottom, rgba(0,0,0,.1), rgba(0,0,0,.5)"
                     >
                          <v-card-title class="text-h6 pb-2">{{$t("home.next7Days")}}</v-card-title>
                     </v-img>

                     <v-card-text>
                         <v-timeline density="compact" align="start" v-if="!loadingEvents && Events7Days.length > 0" line-inset="8">
                             <v-timeline-item
                                 v-for="event in Events7Days"
                                 :key="`${event.start}-${event.id}`"
                                 :dot-color="event.color || 'primary'"
                                 size="x-small"
                             >
                                 <div class="mb-3">
                                     <div class="font-weight-normal text-caption text-medium-emphasis">
                                        {{ formatDateToDDMMYYYY(event.start) }} @ {{ formatDateCalPreview(event.start) }} {{ $t("home.clock") }}
                                     </div>
                                     <p class="font-weight-medium mb-1">
                                         {{ event.title }}
                                        <v-chip v-if="event.assigned_to && Object.keys(event.assigned_to).length > 0" size="x-small" class="ml-1" label :color="event.color || 'primary'" variant="tonal">
                                            <v-icon start size="small">mdi-account-multiple-outline</v-icon>
                                            {{ formatAssignedUser(event.assigned_to) }}
                                             <v-tooltip activator="parent" location="top" max-width="300">
                                                 <div v-for="user in event.assigned_to" :key="user.user_id">{{ user.username }}</div>
                                             </v-tooltip>
                                         </v-chip>
                                     </p>
                                 </div>
                             </v-timeline-item>
                         </v-timeline>
                         <div v-else-if="loadingEvents" class="text-center pa-5"><v-progress-circular indeterminate size="24"></v-progress-circular></div>
                          <div v-else class="text-center pa-5 text-grey">{{$t("home.noEvents7Days")}}</div>
                     </v-card-text>
                 </v-card>
            </v-col>

            <v-col cols="12" md="4">
                 <v-card class="mb-5 fill-height">
                      <v-img
                          :src="TodosToday.length > 0 ? '/img/dashboard/todoOverviewTop.png' : '/img/dashboard/calendarOverviewTop400Free_2.png'"
                          height="150"
                          cover
                          class="text-white align-end"
                          gradient="to bottom, rgba(0,0,0,.1), rgba(0,0,0,.5)"
                      >
                           <v-card-title class="text-h6 pb-2">{{$t("home.todayTodos")}}</v-card-title>
                      </v-img>

                      <v-card-text>
                          <v-timeline density="compact" align="start" v-if="!loadingTodos && TodosToday.length > 0" line-inset="8">
                              <v-timeline-item
                                  v-for="todo in TodosToday"
                                  :key="todo.id"
                                  :dot-color="getDotColor(todo.importance)"
                                  size="x-small"
                              >
                                  <div class="mb-3">
                                      <div class="font-weight-normal text-caption text-medium-emphasis">
                                          {{ todo.MainTodo }} @ {{ todo.SubTodo }}
                                      </div>
                                      <p class="font-weight-medium">{{ todo.TodoPoint }}</p>
                                  </div>
                              </v-timeline-item>
                          </v-timeline>
                          <div v-else-if="loadingTodos" class="text-center pa-5"><v-progress-circular indeterminate size="24"></v-progress-circular></div>
                           <div v-else class="text-center pa-5 text-grey">{{$t("home.noTodosToday")}}</div>
                      </v-card-text>
                  </v-card>

                  <v-card class="fill-height">
                       <v-img
                           :src="Todos7Days.length > 0 ? '/img/dashboard/todoOverviewTop.png' : '/img/dashboard/calendarOverviewTop400Free_2.png'"
                           height="150"
                           cover
                           class="text-white align-end"
                           gradient="to bottom, rgba(0,0,0,.1), rgba(0,0,0,.5)"
                       >
                            <v-card-title class="text-h6 pb-2">{{$t("home.next7DaysTodos")}}</v-card-title>
                       </v-img>

                       <v-card-text>
                           <v-timeline density="compact" align="start" v-if="!loadingTodos && Todos7Days.length > 0" line-inset="8">
                               <v-timeline-item
                                   v-for="todo in Todos7Days"
                                   :key="todo.id"
                                   :dot-color="getDotColor(todo.importance)"
                                   size="x-small"
                               >
                                   <div class="mb-3">
                                       <div class="font-weight-normal text-caption text-medium-emphasis">
                                            {{ formatDateToDDMMYYYY(todo.due_date) }} | {{ todo.MainTodo }} @ {{ todo.SubTodo }}
                                       </div>
                                       <p class="font-weight-medium">{{ todo.TodoPoint }}</p>
                                   </div>
                               </v-timeline-item>
                           </v-timeline>
                            <div v-else-if="loadingTodos" class="text-center pa-5"><v-progress-circular indeterminate size="24"></v-progress-circular></div>
                            <div v-else class="text-center pa-5 text-grey">{{$t("home.noTodos7Days")}}</div>
                       </v-card-text>
                   </v-card>
             </v-col>

            <v-col cols="12" md="4">
                  <v-card class="fill-height">
                      <v-img
                          :src="Applicants.length > 0 ? '/img/dashboard/applicationOverview.png' : '/img/dashboard/calendarOverviewTop400Free_2.png'"
                          height="150"
                          cover
                          class="text-white align-end"
                          gradient="to bottom, rgba(0,0,0,.1), rgba(0,0,0,.5)"
                      >
                           <v-card-title class="text-h6 pb-2">{{$t("home.pendingApplications")}}</v-card-title>
                      </v-img>

                      <v-card-text>
                         <v-timeline density="compact" align="start" v-if="!loadingApplicants && Applicants.length > 0" line-inset="8">
                              <v-timeline-item
                                  v-for="applicant in Applicants"
                                  :key="applicant.id"
                                  dot-color="blue-lighten-1"
                                  size="x-small"
                              >
                                  <div class="mb-3">
                                      <div class="font-weight-normal text-caption text-medium-emphasis">
                                           {{$t("home.interview")}} {{ formatDateToDDMMYYYY(applicant.jobinterviewDate) }} @ {{ applicant.jobinterviewTime?.substring(0,5) ?? '?' }} {{ $t("home.clock") }}
                                      </div>
                                      <p class="font-weight-medium">{{ applicant.name }}</p>
                                      <p class="text-body-2 text-medium-emphasis">{{ applicant.email }}, {{ applicant.phonenumber }}</p>
                                  </div>
                              </v-timeline-item>
                          </v-timeline>
                           <div v-else-if="loadingApplicants" class="text-center pa-5"><v-progress-circular indeterminate size="24"></v-progress-circular></div>
                           <div v-else class="text-center pa-5 text-grey">{{$t("home.noPendingApplications")}}</div>
                      </v-card-text>
                  </v-card>
             </v-col>
        </v-row>
    </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, computed } from 'vue';
import type { Ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { apiClientAuth } from "@/api"; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store
import type { EventOverview, AssignedUser } from "@/types/Calendar"; // Adjust path
import type { TodoOverview } from "@/types/Todo"; // Adjust path
import type { ApplicantPending } from "@/types/Application"; // Adjust path
import { useToast } from 'vue-toastification'; // Import toast
// Import 'vue-cal' if used elsewhere, not needed just for types displayed here
// import "vue-cal/dist/vuecal.css";

// --- Store ---
const authStore = useAuthStore();
const currentUserId = computed(() => authStore.user?.id ?? null); // Get reactive user ID

// --- Component State ---
const loadingEvents = ref(false);
const loadingTodos = ref(false);
const loadingApplicants = ref(false);
const Events7Days = ref<EventOverview[]>([]);
const EventsToday = ref<EventOverview[]>([]);
const Todos7Days = ref<TodoOverview[]>([]);
const TodosToday = ref<TodoOverview[]>([]);
const Applicants = ref<ApplicantPending[]>([]);

// --- Snackbar ---
const toast = useToast();
const { t } = useI18n();

function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// --- Data Fetching ---
const fetchData = async <T>(action: string, targetRef: Ref<T[]>, loadingRef: Ref<boolean>, errorMessage: string, baseEndpoint: string) => {
    loadingRef.value = true;
    try {
        const response = await apiClientAuth.get<{ data: T[] }>(`${baseEndpoint}?action=${action}`);
        targetRef.value = response.data.data || response.data || [];
    } catch (error: any) {
        console.error(`Error fetching ${action}:`, error);
        showSnackbar(error.response?.data?.error || errorMessage, "error");
        targetRef.value = [];
    } finally {
        loadingRef.value = false;
    }
};

const fetchAllData = () => {
    Promise.all([
        fetchData<EventOverview>('getEvents7Days', Events7Days, loadingEvents, t('home.errorLoadEvents7Days'), 'calendar/'),
        fetchData<EventOverview>('getEventsToday', EventsToday, loadingEvents, t('home.errorLoadEventsToday'), 'calendar/'),
        fetchData<TodoOverview>('getTodos7Days', Todos7Days, loadingTodos, t('home.errorLoadTodos7Days'), 'todo/'),
        fetchData<TodoOverview>('getTodosToday', TodosToday, loadingTodos, t('home.errorLoadTodosToday'), 'todo/'),
        fetchData<ApplicantPending>('getPendingApplicant', Applicants, loadingApplicants, t('home.errorLoadApplicants'), 'application/')
    ]);
};

// --- Methods ---

// Formatting and Display Logic
const formatAssignedUser = (users: AssignedUser[] | null | undefined): string => {
    if (!users || Object.keys(users).length === 0) return t('home.all');
    // API might return object instead of array? Check structure. Assuming array:
     const userArray = Array.isArray(users) ? users : Object.values(users); // Handle both cases

    if (userArray.length === 0) return t('home.all');

     const userIdNum = currentUserId.value ? Number(currentUserId.value) : null;

     const isCurrentUserAssigned = userIdNum !== null && userArray.some(user => Number(user.user_id) === userIdNum);

    if (isCurrentUserAssigned) {
         if (userArray.length === 1) return t('home.assignedToYou');
         else return t('home.assignedToYouAndOthers', { count: userArray.length - 1 });
    } else {
         // Show first assigned user's name + count
         const firstUser = userArray[0]?.username || t('home.unknown');
         if (userArray.length === 1) return firstUser;
         else return t('home.firstUserAndOthers', { first: firstUser, count: userArray.length - 1 });
     }
};

const formatDate = (dateInput: string | Date | null, onlyDate = false): string => {
     // Simplified formatter, assuming input is valid Date string or Date object
     if (!dateInput) return '-';
     try {
         const date = new Date(dateInput);
         if (isNaN(date.getTime())) return '?';

         if (onlyDate) {
             return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
         } else {
              return date.toLocaleString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
          }
      } catch {
          return '?';
      }
  };

  const formatDateToDDMMYYYY = (dateInput: string | Date | null): string => {
       return formatDate(dateInput, true); // Use the simplified formatter
   };

const formatDateCalPreview = (dateInput: string | Date | null): string => {
    if (!dateInput) return '--:--';
    try {
        const date = new Date(dateInput);
         if (isNaN(date.getTime())) return '--:--';
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${hours}:${minutes}`;
    } catch {
         return '--:--';
    }
};

const getDotColor = (importance: string | undefined): string => {
    switch (importance?.toLowerCase()) {
        case 'low': return 'green';
        case 'medium': return 'orange';
        case 'high': return 'red';
        default: return 'grey'; // Default color
    }
};

// --- Lifecycle Hooks ---
onMounted(fetchAllData);

</script>

<style scoped>
.fill-height {
    min-height: 300px; /* Ensure cards have a minimum height */
}
/* Optional: Adjust timeline item spacing */
:deep(.v-timeline-item .v-timeline-item__body) {
    padding-block-start: 6px;
     padding-block-end: 6px;
}
:deep(.v-timeline-item .v-timeline-item__opposite){
     display: none; /* Hide opposite side if not needed */
}
:deep(.v-timeline--vertical.v-timeline.v-timeline--align-start) {
    grid-template-columns: min-content min-content auto; /* Adjust grid for closer alignment */
}
</style>