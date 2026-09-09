<template>
  <div class="my-crew-widget">
    <!-- Loading State -->
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state pa-4 text-center">
      <v-icon size="48" color="error">mdi-alert-circle-outline</v-icon>
      <p class="text-body-2 mt-2">{{ error }}</p>
    </div>

    <!-- No Crew Assignment -->
    <div v-else-if="!crewInfo" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-account-group-outline</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.widget.crew.notAssigned') }}</p>
    </div>

    <!-- Crew Info -->
    <div v-else class="crew-info">
      <!-- Crew Header (Compact) -->
      <div class="crew-header-compact mb-3">
        <div class="d-flex align-center justify-space-between mb-2">
          <h4 class="crew-name text-subtitle-1 mb-0">
            {{ crewInfo.name }}
          </h4>
          <v-chip
            :color="getStatusColor(crewInfo.status)"
            size="x-small"
            variant="flat"
          >
            <v-icon start size="x-small">mdi-circle</v-icon>
            {{ getStatusLabel(crewInfo.status) }}
          </v-chip>
        </div>
        <div class="text-caption text-medium-emphasis">
          {{ crewInfo.memberCount || crewInfo.members.length }} {{ $t('dashboard.widget.crew.members') }}
        </div>
      </div>

      <v-divider class="my-2" />

      <!-- Crew Members (Information Tiles) -->
      <div class="crew-members-tiles">
        <div
          v-for="member in crewInfo.members"
          :key="member.id"
          class="member-tile"
          :class="{ 'member-online': member.isOnline }"
        >
          <div class="member-tile-content">
            <v-avatar
              size="40"
              :color="member.isOnline ? 'success' : 'grey-darken-2'"
              class="member-avatar"
            >
              <span class="text-caption font-weight-bold">{{ member.servicenumber || '?' }}</span>
              <v-icon
                v-if="member.isLead"
                class="leader-badge"
                size="x-small"
                color="warning"
              >
                mdi-star
              </v-icon>
            </v-avatar>
            <div class="member-info">
              <div class="member-name text-body-2 font-weight-medium">
                {{ member.name }}
              </div>
              <div class="member-rank text-caption text-medium-emphasis">
                {{ member.role || '-' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="crew-actions mt-3">
        <v-btn
          block
          variant="tonal"
          color="primary"
          size="small"
          @click="goToDispatch"
        >
          <v-icon start size="small">mdi-view-dashboard-variant</v-icon>
          {{ $t('dashboard.widget.crew.viewDispatch') }}
        </v-btn>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { apiClientAuth } from '@/api'

interface CrewMember {
  id: number
  name: string
  servicenumber: string
  rank_id: number
  role: string
  isLead: boolean
  isOnline: boolean
}

interface CrewInfo {
  id: number
  name: string
  status: string
  members: CrewMember[]
}

const router = useRouter()
const { t } = useI18n()

const loading = ref(true)
const error = ref('')
const crewInfo = ref<CrewInfo | null>(null)

/**
 * Load crew information
 */
async function loadCrewInfo() {
  loading.value = true
  error.value = ''

  try {
    // Try to get user's crew assignment
    // This endpoint might need to be created or adjusted
    const response = await apiClientAuth.get('/user/?action=getOwnCrew')

    if (response.data && response.data.crew) {
      crewInfo.value = response.data.crew
    }
  } catch (err: any) {
    console.error('Failed to load crew info:', err)

    // If endpoint doesn't exist yet, show placeholder data for testing
    if (err.response?.status === 404 || err.response?.data?.error?.includes('ACTION_NOT_DEFINED')) {
      // Show placeholder - user has no crew assigned
      crewInfo.value = null
    } else {
      error.value = err.response?.data?.error || t('dashboard.widget.crew.loadError')
    }
  } finally {
    loading.value = false
  }
}

/**
 * Get status color
 */
function getStatusColor(status: string): string {
  switch (status?.toLowerCase()) {
    case 'active':
    case 'on_duty':
    case 'available':
      return 'success'
    case 'busy':
    case 'in_action':
      return 'warning'
    case 'offline':
    case 'unavailable':
      return 'error'
    default:
      return 'grey'
  }
}

/**
 * Get status label - try to translate, fallback to original value
 */
function getStatusLabel(status: string): string {
  if (!status) return ''

  // Try to get translation
  const key = `dashboard.widget.crew.status.${status}`
  const translated = t(key)

  // If translation key not found, return original status
  if (translated === key) {
    return status
  }

  return translated
}

/**
 * Navigate to dispatch view
 */
function goToDispatch() {
  router.push('/dispatch')
}

// Load on mount
onMounted(() => {
  loadCrewInfo()
})

// Expose refresh method
defineExpose({
  refresh: loadCrewInfo
})
</script>

<style scoped lang="scss">
.my-crew-widget {
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.crew-info {
  padding: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.crew-header-compact {
  .crew-name {
    font-weight: 600;
    line-height: 1.2;
  }
}

// Crew member tiles layout - Grid for multiple tiles per row
.crew-members-tiles {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 8px;
  flex: 1;
  overflow-y: auto;
  padding-right: 4px;

  &::-webkit-scrollbar {
    width: 4px;
  }

  &::-webkit-scrollbar-track {
    background: var(--k-row-hover);
  }

  &::-webkit-scrollbar-thumb {
    background: var(--k-row-hover);
    border-radius: 2px;
  }
}

.member-tile {
  display: flex;
  padding: 8px;
  border-radius: 8px;
  background: var(--k-row-hover);
  border: 1px solid var(--k-line);
  transition: all 0.2s ease;
  cursor: pointer;

  &:hover {
    background: var(--k-row-hover);
    border-color: var(--k-line);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  }

  &.member-online {
    border-left: 3px solid rgb(76, 175, 80);
    background: rgba(76, 175, 80, 0.05);
  }
}

.member-tile-content {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
}

.member-avatar {
  position: relative;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  flex-shrink: 0;
}

.leader-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: rgba(0, 0, 0, 0.8);
  border-radius: 50%;
  padding: 2px;
}

.member-info {
  flex: 1;
  min-width: 0;
}

.member-name {
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.member-rank {
  line-height: 1.2;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.crew-actions {
  margin-top: auto;
  padding-top: 8px;
}

.no-data,
.error-state {
  color: var(--k-ink-faint);
}
</style>
