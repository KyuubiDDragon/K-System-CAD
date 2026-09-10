<script setup lang="ts">
import { ref, computed, onMounted, reactive, watch, type Ref } from 'vue';
import { apiClientAuth } from "@/api"; // Use configured Axios instance
import VacationCard from "@/components/cards/VacationCard.vue"; // Adjust path if needed
import type { Rank, Company, Department, License, JobTypes as JobType } from "@/types/Members"; // Adjust path and ensure types exist
import { useToast } from 'vue-toastification'; // Import Toastification
import { useI18n } from 'vue-i18n';

// --- Component State ---
const search = ref("");
const employeeData = ref<Rank[]>([]); // Holds raw data structured by rank from fetchEmployee
const companies = ref<Company[]>([]);
const departments = ref<Department[]>([]);
const ranks = ref<Rank[]>([]);
const licenses = ref<License[]>([]);
const jobTypes = ref<JobType[]>([]); // Renamed from JobTypes
const loadingData = ref(false);

// Filter state refs
const activeFilter = ref<'current' | 'return_7' | 'return_30' | 'vacation_7' | 'vacation_30'>('current');
const filterdays = ref<number>(0); // Days for return/vacation filter

// Props for VacationCard (potentially unused based on card implementation)
const showPreview = ref(false);
const showIsTerminated = ref(false);

// --- Toastification ---
const toast = useToast();
const { t } = useI18n();

// --- Data Fetching ---
const fetchData = async <T>(action: string, targetRef: Ref<T[]>, errorMessage: string, mapFn?: (item: any) => T, baseEndpoint = '/employee/') => {
    try {
        // Assume index_fetch.php is handled by base URL or needs to be part of action string if required
        const response = await apiClientAuth.get<{ data: any[] }>(`${baseEndpoint}?action=${action}`);
        const data = response.data.data || response.data || []; // Handle variations
        targetRef.value = mapFn ? data.map(mapFn) : data;
    } catch (error: any) {
        toast.error(error.response?.data?.error || errorMessage); // Use toast for error handling
        targetRef.value = [];
    }
};

const fetchAllInitialData = async () => {
    loadingData.value = true;
    await Promise.all([
        fetchData<JobType>('getJobTypes', jobTypes, t("vacationView.errorLoadJobTypes"), undefined, 'employee'), // Example with specific path if needed
        fetchData<Rank>('getEmployee', employeeData, t("vacationView.errorLoadEmployees")),
        fetchData<Company>('getCompanies', companies, t("vacationView.errorLoadCompanies")),
        fetchData<Department>('getDepartments', departments, t("vacationView.errorLoadDepartments")),
        fetchData<Rank>('getRanks', ranks, t("vacationView.errorLoadRanks")), // Also fetched in getEmployee? Check for redundancy.
        fetchData<License>('getLicenses', licenses, t("vacationView.errorLoadLicenses")),
    ]);
    loadingData.value = false;
};

// --- Computed Properties ---

// Filter employees based on search and termination status (if needed, currently not used)
const filteredRanksWithEmployees = computed(() => {
    const searchTermLower = search.value.toLowerCase();
    const currentDate = new Date();
    currentDate.setHours(0, 0, 0, 0);

    return employeeData.value
        .map(rank => ({
            ...rank,
            // Filter employees within each rank
            employees: rank.employees.filter(employee => {
                // Basic termination filter (show active or those leaving in the future)
                const isActive = !employee.is_terminated || (employee.leavedate && new Date(employee.leavedate) >= currentDate);
                // Search filter
                const matchesSearch = !searchTermLower || employee.name.toLowerCase().includes(searchTermLower);

                return isActive && matchesSearch;
            })
        }))
        // Remove ranks that have no employees after filtering
        .filter(rank => rank.employees.length > 0);
});

// --- Watchers ---
// Update filterdays when activeFilter changes
watch(activeFilter, (newFilter) => {
    switch (newFilter) {
        case 'return_7':
        case 'vacation_7':
            filterdays.value = 7;
            break;
        case 'return_30':
        case 'vacation_30':
            filterdays.value = 30;
            break;
        default: // 'current'
            filterdays.value = 0; // Or specific logic for 'current' in VacationCard
            break;
    }
});

// --- Lifecycle Hooks ---
onMounted(() => {
  fetchAllInitialData();
});

</script>

<template>
    <v-container fluid class="vacation-container pa-4">
      <!-- Kopfzeile mit Titel und Info -->
      <v-row class="mb-4">
        <v-col cols="12">
          <div class="page-header">
            <h1 class="text-h4 font-weight-medium">
              <v-icon size="36" class="mr-2">mdi-calendar-account</v-icon>
              {{ t("vacationView.title") }}
            </h1>
            <p class="text-body-1 text-medium-emphasis">
              {{ t("vacationView.description") }}
            </p>
          </div>
        </v-col>
      </v-row>
      
      <!-- Filter-Leiste -->
      <v-row class="filter-section mb-5">
        <v-col cols="12" md="8" lg="9">
          <v-card class="filter-card elevation-2">
            <v-card-text class="py-2">
              <v-btn-toggle 
                v-model="activeFilter" 
                mandatory 
                density="comfortable" 
                color="primary" 
                variant="outlined" 
                divided
                class="filter-toggle"
              >
                <v-btn value="current" prepend-icon="mdi-beach">{{ t("vacationView.currentAbsent") }}</v-btn>
                <v-btn value="return_7" prepend-icon="mdi-account-arrow-left">{{ t("vacationView.return7") }}</v-btn>
                <v-btn value="return_30" prepend-icon="mdi-account-arrow-left-outline">{{ t("vacationView.return30") }}</v-btn>
                <v-btn value="vacation_7" prepend-icon="mdi-calendar-clock">{{ t("vacationView.upcoming7") }}</v-btn>
                <v-btn value="vacation_30" prepend-icon="mdi-calendar-range">{{ t("vacationView.upcoming30") }}</v-btn>
              </v-btn-toggle>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="4" lg="3">
          <v-text-field
            v-model="search"
            :label="t('vacationView.search')"
            variant="outlined"
            density="comfortable"
            prepend-inner-icon="mdi-magnify"
            hide-details
            clearable
            class="search-field"
          />
        </v-col>
      </v-row>
      
      <!-- Lade-Anzeige -->
      <v-row v-if="loadingData" justify="center">
        <v-col cols="auto" class="text-center pa-10">
          <div class="loading-container">
            <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
            <p class="mt-4 text-body-1">{{ t("vacationView.loading") }}</p>
          </div>
        </v-col>
      </v-row>
      
      <!-- Keine Ergebnisse -->
      <v-row v-else-if="filteredRanksWithEmployees.length === 0" justify="center">
        <v-col cols="auto" class="text-center pa-10">
          <div class="empty-results">
            <v-icon color="grey-darken-1" size="64" class="mb-3">mdi-calendar-remove</v-icon>
            <p class="text-h6 text-grey">{{ t("vacationView.noResults") }}</p>
            <p class="text-body-2 text-grey mt-2">{{ t("vacationView.noResultsHint") }}</p>
          </div>
        </v-col>
      </v-row>
      
      <!-- Mitarbeiter-Listen nach Rang -->
      <template v-for="rank in filteredRanksWithEmployees" :key="rank.id">
        <v-row v-if="rank.employees.length > 0" class="mt-4">
          <v-col cols="12">
            <div class="rank-header">
              <v-divider class="rank-divider"></v-divider>
              <h2 class="rank-title">
                <v-icon size="22" class="mr-2">mdi-medal</v-icon>
                {{ rank.name }}
                <v-chip size="small" color="primary" variant="tonal" class="ml-2">{{ rank.employees.length }}</v-chip>
              </h2>
              <v-divider class="rank-divider"></v-divider>
            </div>
          </v-col>
        </v-row>
        
        <v-row class="employee-grid">
          <VacationCard
            v-for="employee in rank.employees"
            :key="employee.id"
            :member="employee"
            :logoURL="rank.rankImage"
            :companies="companies"
            :departments="departments"
            :ranks="ranks"
            :licenses="licenses"
            :showPreview="showPreview" 
            :showIsTerminated="showIsTerminated" 
            :showRecentReturns="activeFilter.startsWith('return_')"
            :showRecentVacations="activeFilter.startsWith('vacation_')"
            :filterdays="filterdays"
          />
        </v-row>
      </template>
    </v-container>
  </template>

  <style scoped>

  .vacation-container {
    min-height: 89vh;
    background-color: var(--k-canvas);
    background-image:
      radial-gradient(circle at 15% 20%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
      radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 30%);
  }
  
  .page-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--k-line);
  }
  
  .filter-section {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: rgba(17, 23, 35, 0.8);
    backdrop-filter: blur(10px);
    border-radius: 8px;
    padding: 8px 0;
    margin: 0 -8px;
  }
  
  .filter-card {
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--card-border);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
  }
  
  .filter-toggle {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .search-field {
    transition: all var(--transition-timing);
    background: rgba(15, 23, 42, 0.6);
    border-radius: 12px;
  }
  
  .search-field:focus-within {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }
  
  .rank-header {
    display: flex;
    align-items: center;
    margin: 24px 0 8px;
  }
  
  .rank-divider {
    flex-grow: 1;
    opacity: 0.1;
  }
  
  .rank-title {
    display: flex;
    align-items: center;
    font-size: 1.25rem;
    font-weight: 500;
    color: #e2e8f0;
    margin: 0 16px;
    white-space: nowrap;
  }
  
  .employee-grid {
    margin: 0 -8px;
  }
  
  .loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 300px;
  }
  
  .empty-results {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    max-width: 500px;
    text-align: center;
  }
  
  @media (max-width: 600px) {
    .page-header {
      text-align: center;
    }
    
    .rank-header {
      flex-direction: column;
      gap: 8px;
    }
    
    .rank-divider {
      width: 100%;
    }
    
    .rank-title {
      margin: 8px 0;
    }
    
    .filter-toggle :deep(.v-btn) {
      flex-grow: 1;
      min-width: 0;
      padding: 0 8px;
    }
    
    .filter-toggle :deep(.v-btn__prepend) {
      margin-right: 4px;
    }
  }
  </style>