<template>
  <div class="todo-list-widget">
    <div v-if="loading" class="text-center pa-4">
      <v-progress-circular indeterminate size="32" />
    </div>

    <div v-else-if="error" class="error-state pa-4 text-center">
      <v-icon size="48" color="error">mdi-alert-circle-outline</v-icon>
      <p class="text-body-2 mt-2">{{ error }}</p>
    </div>

    <div v-else-if="todos.length === 0" class="no-data pa-4 text-center">
      <v-icon size="48" color="grey">mdi-checkbox-marked-circle-outline</v-icon>
      <p class="text-body-2 mt-2">{{ $t('dashboard.widget.todo.noTasks') }}</p>
      <v-btn size="small" color="primary" variant="tonal" class="mt-3" @click="showAddDialog = true">
        <v-icon start size="small">mdi-plus</v-icon>
        {{ $t('dashboard.widget.todo.addTask') }}
      </v-btn>
    </div>

    <div v-else class="todos-list">
      <div v-for="todo in todos" :key="todo.id" class="todo-item mb-2">
        <v-checkbox
          :model-value="todo.completed"
          @update:model-value="toggleTodo(todo.id, $event)"
          hide-details
          density="compact"
        >
          <template #label>
            <div :class="{ 'text-decoration-line-through text-medium-emphasis': todo.completed }">
              <div class="text-body-2">{{ todo.title }}</div>
              <div v-if="todo.due_date" class="text-caption text-medium-emphasis">
                <v-icon size="x-small">mdi-calendar</v-icon>
                {{ formatDate(todo.due_date) }}
              </div>
            </div>
          </template>
        </v-checkbox>
      </div>

      <v-btn block size="small" variant="tonal" color="primary" class="mt-2" @click="showAddDialog = true">
        <v-icon start size="small">mdi-plus</v-icon>
        {{ $t('dashboard.widget.todo.addTask') }}
      </v-btn>
    </div>

    <v-dialog v-model="showAddDialog" max-width="400">
      <v-card>
        <v-card-title>{{ $t('dashboard.widget.todo.addTask') }}</v-card-title>
        <v-card-text>
          <v-text-field v-model="newTodo.title" :label="$t('dashboard.widget.todo.task')" density="comfortable" />
          <v-text-field v-model="newTodo.due_date" type="date" :label="$t('dashboard.widget.todo.dueDate')" density="comfortable" />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showAddDialog = false">{{ $t('dashboard.widget.todo.cancel') }}</v-btn>
          <v-btn color="primary" @click="addTodo" :loading="adding">{{ $t('dashboard.widget.todo.save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { formatDate } from '@/utils/datetime';
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { apiClientAuth } from '@/api'

interface Props {
  widgetId: string
  config: any
}

defineProps<Props>()

const { t: $t } = useI18n()

interface Todo {
  id: number
  title: string
  completed: boolean
  due_date: string
}

const loading = ref(true)
const error = ref('')
const todos = ref<Todo[]>([])
const showAddDialog = ref(false)
const adding = ref(false)
const newTodo = ref({ title: '', due_date: '' })

async function loadTodos() {
  loading.value = true
  try {
    const response = await apiClientAuth.get('/todo/?action=getAllTodos')
    todos.value = response.data?.todos || []
  } catch (err: any) {
    console.error('Failed to load todos:', err)
    error.value = $t('dashboard.widget.todo.failed')
  } finally {
    loading.value = false
  }
}

async function toggleTodo(id: number, completed: boolean) {
  try {
    await apiClientAuth.post('/todo/?action=updateTodoCompletionStatus', { id, completed })
    await loadTodos()
  } catch (err) {
    console.error('Failed to toggle todo:', err)
  }
}

async function addTodo() {
  if (!newTodo.value.title) return
  adding.value = true
  try {
    await apiClientAuth.post('/todo/?action=addDashboardTodo', newTodo.value)
    showAddDialog.value = false
    newTodo.value = { title: '', due_date: '' }
    await loadTodos()
  } catch (err) {
    console.error('Failed to add todo:', err)
  } finally {
    adding.value = false
  }
}

function formatDate(dateString: string): string {
  return formatDate(dateString)
}

onMounted(() => loadTodos())
defineExpose({ refresh: loadTodos })
</script>

<style scoped lang="scss">
.todo-list-widget {
  height: 100%;
  overflow: hidden;
}

.todos-list {
  padding: 0;
}

.todo-item {
  padding: 4px 0;
}

.no-data,
.error-state {
  color: var(--k-ink-faint);
}
</style>
