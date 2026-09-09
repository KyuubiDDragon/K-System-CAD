<template>
  <div class="shared-report-view">
    <!-- Nur der Header mit Badges, ohne Toolbar -->
    <div class="report-header mb-6">
      <div class="d-flex align-center mb-4">
        <h1 class="text-h4 mr-4">{{ report.title || t('sharedReport.noTitle') }}</h1>
        
        <div class="ml-auto d-flex align-center">
          <!-- Sharing info badge -->
          <v-chip
            v-if="report.source_authority_display"
            size="small"
            color="primary"
            variant="outlined"
            class="mx-1"
            prepend-icon="mdi-share-variant"
          >
            {{ t('sharedReport.sharedBy') }} {{ report.source_authority_display }}
          </v-chip>
          
          <!-- Access level badge -->
          <v-chip
            size="small"
            color="info"
            variant="outlined"
            class="mx-1"
          >
            {{ t('sharedReport.readAccess') }}
          </v-chip>
        </div>
      </div>
      
      <div class="d-flex flex-wrap gap-2 align-center">
        <v-chip color="primary" size="small" class="mr-2">
          <v-icon start size="small">mdi-folder-outline</v-icon>
          {{ report.category_name || t('sharedReport.noCategory') }}
        </v-chip>
        <v-chip color="info" size="small" class="mr-2">
          <v-icon start size="small">mdi-tag</v-icon>
          {{ report.status_name || t('sharedReport.noStatus') }}
        </v-chip>
        <v-chip color="secondary" size="small" class="mr-2">
          <v-icon start size="small">mdi-calendar</v-icon>
          {{ formatDate(report.report_date) }}
        </v-chip>
        <v-chip v-if="report.location" color="success" size="small" class="mr-2">
          <v-icon start size="small">mdi-map-marker</v-icon>
          {{ report.location }}
        </v-chip>
        <v-chip v-if="report.report_code_name" size="small" class="mr-2">
          <v-icon start size="small">mdi-barcode</v-icon>
          {{ report.report_code_name }}
        </v-chip>
      </div>
    </div>
    
    <!-- Loading indicator -->
    <v-progress-linear
      v-if="isLoading"
      indeterminate
      color="primary"
      class="mb-4"
    ></v-progress-linear>
    
    <!-- Main content area with shadow -->
    <div class="content-section">
      <!-- Erstellerinformationen über dem Inhalt -->
      <div class="mb-4 pa-4 creator-info-bar">
        <div class="d-flex align-center">
          <v-avatar size="36" color="grey-lighten-2" class="mr-2">
            <v-icon>mdi-account</v-icon>
          </v-avatar>
          <div>
            <div class="font-weight-medium">
              {{ report.creator_firstname && report.creator_lastname 
                 ? `${report.creator_firstname} ${report.creator_lastname}` 
                 : (report.creator_name || t('sharedReport.unknownCreator')) }}
            </div>
            <div class="text-caption text-grey d-flex flex-wrap">
              <span class="mr-4">
                <v-icon size="small" class="mr-1">mdi-clock-outline</v-icon>
                {{ t('sharedReport.created') }} {{ formatDate(report.created_at) }}
              </span>
              <span class="mr-4">
                <v-icon size="small" class="mr-1">mdi-update</v-icon>
                {{ t('sharedReport.lastChanged') }} {{ formatDate(report.updated_at) }}
              </span>
              <span>
                <v-icon size="small" class="mr-1">mdi-share-variant</v-icon>
                {{ t('sharedReport.sharedOn') }} {{ formatDate(report.shared_at) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <v-tabs v-model="activeTab" bg-color="transparent">
        <v-tab value="content">{{ t('sharedReport.content') }}</v-tab>
        <v-tab v-if="hasCustomFields" value="custom">{{ t('sharedReport.customFields') }}</v-tab>
        <v-tab v-if="report.additionals && report.additionals.length > 0" value="additionals">{{ t('sharedReport.additionals') }}</v-tab>
      </v-tabs>
      
      <v-window v-model="activeTab" class="mt-4">
        <!-- Content Tab -->
        <v-window-item value="content">
          <!-- Inhalt zuerst -->
          <h3 class="text-subtitle-1 font-weight-medium mb-2">{{ t('sharedReport.content') }}</h3>
          <div class="formatted-content content-main pa-4 mb-6" v-html="report.text || t('sharedReport.noContent')"></div>
          
          <!-- Beschreibung ganz unten -->
          <h3 class="text-subtitle-1 font-weight-medium mb-2">{{ t('sharedReport.description') }}</h3>
          <div class="formatted-content description-area mb-4 pa-4" v-html="report.description || t('sharedReport.noDescription')"></div>
        </v-window-item>
        
        <!-- Custom Fields Tab -->
        <v-window-item v-if="hasCustomFields" value="custom">
          <h3 class="text-subtitle-1 font-weight-medium mb-2">
            <v-icon start color="primary" size="small">mdi-form-select</v-icon>
            {{ t('sharedReport.customFields') }}
          </h3>
          <v-row>
            <v-col v-for="(value, key) in customFields" :key="key" cols="12" sm="6" md="4">
              <v-card variant="outlined" class="pa-3">
                <div class="text-caption text-uppercase font-weight-medium">{{ key }}</div>
                <div class="text-body-1">{{ formatCustomFieldValue(value) }}</div>
              </v-card>
            </v-col>
          </v-row>
        </v-window-item>
        
        <!-- Additional Items Tab -->
        <v-window-item v-if="report.additionals && report.additionals.length > 0" value="additionals">
          <h3 class="text-subtitle-1 font-weight-medium mb-2">
            <v-icon start color="primary" size="small">mdi-package-variant</v-icon>
            {{ t('sharedReport.additionals') }}
          </h3>
          <v-table density="comfortable" class="rounded-lg">
            <thead>
              <tr>
                <th>{{ t('sharedReport.title') }}</th>
                <th>{{ t('sharedReport.price') }}</th>
                <th>{{ t('sharedReport.units') }}</th>
                <th>{{ t('sharedReport.amount') }}</th>
                <th>{{ t('sharedReport.totalPrice') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in report.additionals" :key="item.id">
                <td>{{ item.title || item.name }}</td>
                <td>{{ formatCurrency(item.price) }}</td>
                <td>{{ item.units }}</td>
                <td>{{ item.amount }}</td>
                <td class="font-weight-medium">{{ formatCurrency(item.price * item.units * item.amount) }}</td>
              </tr>
            </tbody>
          </v-table>
        </v-window-item>
      </v-window>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
// Der Import wird auskommentiert, um Linter-Fehler zu vermeiden, die Funktionalität bleibt aber erhalten
// import { useAuthStore } from '@/stores/auth';

interface Person {
  id: number;
  name?: string;
  fullname?: string;
}

interface Company {
  id: number;
  name?: string;
}

interface Employee {
  id: number;
  name?: string;
}

interface ReportCategory {
  id: number;
  name: string;
}

interface ReportItem {
  id: number;
  title?: string;
  name?: string;
  price: number;
  units: number;
  amount: number;
}

interface Report {
  id: number;
  title: string;
  description?: string;
  text?: string;
  report_date?: string;
  report_code_id?: number;
  report_code_name?: string;
  creator_name?: string;
  creator_firstname?: string;
  creator_lastname?: string;
  creator_id?: number;
  status_id?: number;
  status_name?: string;
  category_id?: number;
  category_name?: string;
  location?: string;
  linked_persons?: number[];
  linked_companies?: number[];
  missing_employees?: number[];
  additionals?: ReportItem[];
  custom_fields?: Record<string, any> | string;
  access_level: string;
  source_authority?: string;
  source_authority_display?: string;
  source_authority_id?: number;
  shared_at?: string;
  created_at?: string;
  updated_at?: string;
}

interface Props {
  report?: Record<string, any>
  persons?: any[]
  companies?: any[]
  employees?: any[]
}

const props = withDefaults(defineProps<Props>(), {
  persons: () => [],
  companies: () => [],
  employees: () => []
});

const emit = defineEmits(['close', 'report-updated']);

const { t } = useI18n();

// State
// Der authStore wird auskommentiert, da wir ihn derzeit nicht aktiv verwenden
// const authStore = useAuthStore();
const isLoading = ref(false);
// const authority = computed(() => authStore.user?.authority || '');
const activeTab = ref('content');

// Computed properties
const linkedPersonsData = computed(() => {
  if (!props.report.linked_persons || !Array.isArray(props.report.linked_persons)) return [];
  
  return props.report.linked_persons.map(personId => {
    const personData = props.persons.find(p => Number(p.id) === Number(personId)) || { id: personId };
    return personData;
  });
});

const linkedCompaniesData = computed(() => {
  if (!props.report.linked_companies || !Array.isArray(props.report.linked_companies)) return [];
  
  return props.report.linked_companies.map(companyId => {
    const companyData = props.companies.find(c => Number(c.id) === Number(companyId)) || { id: companyId };
    return companyData;
  });
});

const missingEmployeesData = computed(() => {
  if (!props.report.missing_employees || !Array.isArray(props.report.missing_employees)) return [];
  
  return props.report.missing_employees.map(employeeId => {
    const employeeData = props.employees.find(e => Number(e.id) === Number(employeeId)) || { id: employeeId };
    return employeeData;
  });
});

const customFields = computed(() => {
  if (!props.report.custom_fields) return {};
  
  if (typeof props.report.custom_fields === 'string') {
    try {
      return JSON.parse(props.report.custom_fields);
    } catch (e) {
      console.error(t('sharedReport.errorParsingCustomFields'), e);
      return {};
    }
  }
  
  return props.report.custom_fields;
});

const hasCustomFields = computed(() => {
  return Object.keys(customFields.value).length > 0;
});

// Helper methods
const formatDate = (dateString: string | undefined): string => {
  if (!dateString) return '-';
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return t('sharedReport.invalidDate');
    return date.toLocaleString('de-DE', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (e) {
    return t('sharedReport.errorDate');
  }
};

const formatCurrency = (value: number | undefined | null): string => {
  if (value === null || value === undefined) return '-';
  return new Intl.NumberFormat('de-DE', { 
    style: 'currency', 
    currency: 'EUR' 
  }).format(value);
};

const formatCustomFieldValue = (value: any): string => {
  if (value === null || value === undefined) return '-';
  
  if (typeof value === 'boolean') {
    return value ? t('sharedReport.yes') : t('sharedReport.no');
  }
  
  if (Array.isArray(value)) {
    return value.join(', ');
  }
  
  return value.toString();
};
</script>

<style scoped>
.shared-report-view {
  max-width: 100%;
}

.content-section {
  border-radius: 8px;
}

.formatted-content {
  min-height: 60px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  background-color: rgba(0, 0, 0, 0.02);
}

.description-area {
  max-height: 300px;
  overflow-y: auto;
}

.content-main {
  min-height: 250px;
  overflow-y: visible;
}

.creator-info-bar {
  background-color: rgba(0, 0, 0, 0.02);
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.gap-2 {
  gap: 8px;
}

/* For dark theme compatibility */
:deep(.v-theme--dark) .creator-info-bar {
  background-color: rgba(30, 41, 59, 0.4);
  border-color: var(--k-ink);
}

:deep(.v-theme--dark) .formatted-content {
  background-color: rgba(15, 23, 42, 0.3);
  border-color: var(--k-ink);
}

/* Sorge dafür, dass Bilder im Inhalt nicht die Container-Breite überschreiten */
:deep(.formatted-content img) {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 1em auto;
  border-radius: 4px;
}

:deep(.formatted-content figure) {
  max-width: 100%;
  margin: 1em 0;
}
</style> 