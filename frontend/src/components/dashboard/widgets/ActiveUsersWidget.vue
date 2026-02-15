<template>
  <div class="active-users-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>
    <div v-else-if="users.length === 0" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-account-off-outline</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.noActiveUsers') }}</p>
    </div>
    <div v-else class="users-list">
      <div v-for="user in users" :key="user.id" class="user-item d-flex align-center mb-2">
        <v-avatar size="32" color="success" class="mr-2">
          <span class="text-caption">{{ getInitials(user.name) }}</span>
        </v-avatar>
        <div class="flex-grow-1">
          <div class="text-body-2">{{ user.name }}</div>
          <div class="text-caption text-medium-emphasis">{{ user.role }}</div>
        </div>
        <v-icon size="small" color="success">mdi-circle</v-icon>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiClientAuth } from '@/api'

interface Props {
  widgetId: string
  config: any
}

defineProps<Props>()

const loading = ref(true)
const users = ref<any[]>([])

async function loadUsers() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/user/?action=getActiveUsers')
    users.value = response.data || []
  } catch (err) {
    console.error('Failed to load active users:', err)
  } finally {
    loading.value = false
  }
}

function getInitials(name: string): string {
  if (!name) return '?'
  const parts = name.split(' ')
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
  return name.substring(0, 2).toUpperCase()
}

onMounted(() => loadUsers())
defineExpose({ refresh: loadUsers })
</script>

<style scoped lang="scss">
.active-users-widget {
  height: 100%;
  overflow: hidden;
}

.users-list {
  padding: 0;
}

.user-item {
  padding: 4px 0;
}

.no-data {
  color: rgba(255, 255, 255, 0.5);
}
</style>
