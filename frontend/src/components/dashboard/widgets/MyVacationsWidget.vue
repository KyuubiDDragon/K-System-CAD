<template>
  <div class="my-vacations-widget">
    <!-- Loading State -->
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state pa-4 text-center">
      <v-icon size="48" color="error">mdi-alert-circle-outline</v-icon>
      <p class="text-body-2 mt-2">{{ error }}</p>
    </div>

    <!-- No Vacations -->
    <div v-else-if="vacations.length === 0" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-beach</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.noCurrentVacation') }}</p>
      <v-btn
        size="small"
        color="primary"
        variant="tonal"
        class="mt-3"
        @click="showAddDialog = true"
      >
        <v-icon start size="small">mdi-plus</v-icon>
        {{ $t('dashboard.addVacation') }}
      </v-btn>
    </div>

    <!-- Vacations List -->
    <div v-else class="vacations-list">
      <div
        v-for="vacation in vacations"
        :key="vacation.id"
        class="vacation-item mb-3 pa-3"
        :class="{ 'active-vacation': isActive(vacation) }"
      >
        <div class="d-flex align-center mb-2">
          <v-icon :color="getVacationTypeColor(vacation.reason)" class="mr-2">
            {{ getVacationTypeIcon(vacation.reason) }}
          </v-icon>
          <span class="text-subtitle-2 font-weight-bold">
            {{ getVacationTypeLabel(vacation.reason) }}
          </span>
          <v-spacer />
          <v-chip
            v-if="isActive(vacation)"
            size="x-small"
            color="success"
            variant="flat"
          >
            Active
          </v-chip>
        </div>

        <div class="vacation-dates text-body-2 mb-1">
          <v-icon size="small" class="mr-1">mdi-calendar</v-icon>
          {{ formatDate(vacation.start) }} - {{ formatDate(vacation.end) }}
          <span class="text-caption text-medium-emphasis ml-2">
            ({{ getDuration(vacation.start, vacation.end) }} days)
          </span>
        </div>

        <div v-if="vacation.other" class="vacation-note text-caption text-medium-emphasis">
          <v-icon size="x-small" class="mr-1">mdi-note-text</v-icon>
          {{ vacation.other }}
        </div>

        <div v-if="isActive(vacation)" class="mt-2">
          <v-btn
            size="x-small"
            variant="text"
            color="warning"
            @click="endVacation(vacation.id)"
            :loading="ending === vacation.id"
          >
            <v-icon start size="small">mdi-stop</v-icon>
            End Now
          </v-btn>
        </div>
      </div>

      <!-- Add Button -->
      <v-btn
        block
        size="small"
        variant="tonal"
        color="primary"
        class="mt-2"
        @click="showAddDialog = true"
      >
        <v-icon start size="small">mdi-plus</v-icon>
        {{ $t('dashboard.addVacation') }}
      </v-btn>
    </div>

    <!-- Add Vacation Dialog -->
    <v-dialog v-model="showAddDialog" max-width="500">
      <v-card>
        <v-card-title>{{ $t('dashboard.addVacation') }}</v-card-title>
        <v-card-text>
          <v-select
            v-model="newVacation.reason"
            :items="vacationTypes"
            :label="$t('dashboard.vacationType')"
            item-title="label"
            item-value="value"
            density="comfortable"
          />

          <v-text-field
            v-model="newVacation.start"
            type="date"
            :label="$t('dashboard.from')"
            density="comfortable"
          />

          <v-text-field
            v-model="newVacation.end"
            type="date"
            :label="$t('dashboard.to')"
            density="comfortable"
          />

          <v-textarea
            v-model="newVacation.other"
            :label="$t('dashboard.reasonNote')"
            :hint="$t('dashboard.reasonHint')"
            rows="2"
            density="comfortable"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showAddDialog = false">
            {{ $t('cancel') }}
          </v-btn>
          <v-btn
            color="primary"
            variant="flat"
            @click="addVacation"
            :loading="adding"
          >
            {{ $t('save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { apiClientAuth } from '@/api'
import { useToast } from 'vue-toastification'

interface Props {
  widgetId: string
  config: any
}

defineProps<Props>()

interface Vacation {
  id: number
  reason: string
  start: string
  end: string
  employee: number
  reported: string
  other: string
}

const { t } = useI18n()
const toast = useToast()

const loading = ref(true)
const error = ref('')
const vacations = ref<Vacation[]>([])
const showAddDialog = ref(false)
const adding = ref(false)
const ending = ref<number | null>(null)

const newVacation = ref({
  reason: 'vacation',
  start: '',
  end: '',
  other: ''
})

const vacationTypes = computed(() => [
  { value: 'vacation', label: t('dashboard.vacationTypes.vacation') },
  { value: 'sick', label: t('dashboard.vacationTypes.sick') },
  { value: 'training', label: t('dashboard.vacationTypes.training') },
  { value: 'other', label: t('dashboard.vacationTypes.other') }
])

/**
 * Load vacations
 */
async function loadVacations() {
  loading.value = true
  error.value = ''

  try {
    const response = await apiClientAuth.get('/user/?action=getOwnVacations')
    vacations.value = response.data || []
  } catch (err: any) {
    console.error('Failed to load vacations:', err)
    error.value = err.response?.data?.error || 'Failed to load vacations'
  } finally {
    loading.value = false
  }
}

/**
 * Add new vacation
 */
async function addVacation() {
  if (!newVacation.value.start || !newVacation.value.end) {
    toast.error('Please select start and end dates')
    return
  }

  adding.value = true

  try {
    await apiClientAuth.post('/user/?action=addVacation', newVacation.value)
    toast.success(t('dashboard.vacationAddedSuccess'))
    showAddDialog.value = false

    // Reset form
    newVacation.value = {
      reason: 'vacation',
      start: '',
      end: '',
      other: ''
    }

    // Reload vacations
    await loadVacations()
  } catch (err: any) {
    console.error('Failed to add vacation:', err)
    toast.error(err.response?.data?.error || t('dashboard.vacationSaveError'))
  } finally {
    adding.value = false
  }
}

/**
 * End vacation early
 */
async function endVacation(vacationId: number) {
  ending.value = vacationId

  try {
    await apiClientAuth.post('/user/?action=stopVacation', { id: vacationId })
    toast.success(t('dashboard.vacationEndedSuccess'))
    await loadVacations()
  } catch (err: any) {
    console.error('Failed to end vacation:', err)
    toast.error(err.response?.data?.error || t('dashboard.vacationEndError'))
  } finally {
    ending.value = null
  }
}

/**
 * Check if vacation is currently active
 */
function isActive(vacation: Vacation): boolean {
  const now = new Date()
  const start = new Date(vacation.start)
  const end = new Date(vacation.end)
  return now >= start && now <= end
}

/**
 * Format date
 */
function formatDate(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleDateString()
}

/**
 * Get duration in days
 */
function getDuration(start: string, end: string): number {
  const startDate = new Date(start)
  const endDate = new Date(end)
  const diffTime = Math.abs(endDate.getTime() - startDate.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays + 1 // Include both start and end day
}

/**
 * Get vacation type icon
 */
function getVacationTypeIcon(reason: string): string {
  switch (reason) {
    case 'vacation': return 'mdi-beach'
    case 'sick': return 'mdi-medical-bag'
    case 'training': return 'mdi-school'
    case 'other': return 'mdi-calendar-question'
    default: return 'mdi-calendar'
  }
}

/**
 * Get vacation type color
 */
function getVacationTypeColor(reason: string): string {
  switch (reason) {
    case 'vacation': return 'success'
    case 'sick': return 'error'
    case 'training': return 'info'
    case 'other': return 'warning'
    default: return 'grey'
  }
}

/**
 * Get vacation type label
 */
function getVacationTypeLabel(reason: string): string {
  return t(`dashboard.vacationTypes.${reason}`)
}

// Load on mount
onMounted(() => {
  loadVacations()
})

// Expose refresh method
defineExpose({
  refresh: loadVacations
})
</script>

<style scoped lang="scss">
.my-vacations-widget {
  height: 100%;
  overflow: hidden;
}

.vacations-list {
  padding: 0;
}

.vacation-item {
  border-left: 3px solid rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.03);
  transition: all 0.2s;

  &.active-vacation {
    border-left-color: rgb(var(--v-theme-success));
    background: rgba(var(--v-theme-success), 0.1);
  }

  &:hover {
    background: rgba(255, 255, 255, 0.05);
  }
}

.vacation-dates {
  color: rgba(255, 255, 255, 0.8);
}

.vacation-note {
  font-style: italic;
}

.no-data,
.error-state {
  color: rgba(255, 255, 255, 0.5);
}
</style>
