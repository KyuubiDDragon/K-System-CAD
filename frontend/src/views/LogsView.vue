<template>
  <div class="logs-view">
    <v-container fluid>
      <v-card>
        <v-card-title>
          <h1>{{ t('logsView.title') }}</h1>
          <v-spacer></v-spacer>
          <v-text-field
            v-model="searchTerm"
            append-icon="mdi-magnify"
            :label="t('logsView.search')"
            single-line
            hide-details
            @input="debounceSearch"
          ></v-text-field>
        </v-card-title>

        <v-card-text>
          <v-row>
            <v-col cols="12" md="3">
              <v-menu
                ref="startMenu"
                v-model="startDateMenu"
                :close-on-content-click="false"
                transition="scale-transition"
                offset-y
                min-width="auto"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field
                    v-model="startDate"
                    :label="t('logsView.startDate')"
                    prepend-icon="mdi-calendar"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                    clearable
                    @click:clear="startDate = null"
                  ></v-text-field>
                </template>
                <v-date-picker
                  v-model="startDate"
                  @input="startDateMenu = false"
                ></v-date-picker>
              </v-menu>
            </v-col>

            <v-col cols="12" md="3">
              <v-menu
                ref="endMenu"
                v-model="endDateMenu"
                :close-on-content-click="false"
                transition="scale-transition"
                offset-y
                min-width="auto"
              >
                <template v-slot:activator="{ on, attrs }">
                  <v-text-field
                    v-model="endDate"
                    :label="t('logsView.endDate')"
                    prepend-icon="mdi-calendar"
                    readonly
                    v-bind="attrs"
                    v-on="on"
                    clearable
                    @click:clear="endDate = null"
                  ></v-text-field>
                </template>
                <v-date-picker
                  v-model="endDate"
                  @input="endDateMenu = false"
                ></v-date-picker>
              </v-menu>
            </v-col>

            <v-col cols="12" md="3">
              <v-select
                v-model="selectedUser"
                :items="users"
                item-text="username"
                item-value="id"
                :label="t('logsView.user')"
                clearable
                prepend-icon="mdi-account"
              ></v-select>
            </v-col>

            <v-col cols="12" md="3">
              <v-select
                v-model="selectedLogType"
                :items="logTypes"
                :label="t('logsView.logType')"
                clearable
                prepend-icon="mdi-tag"
              ></v-select>
            </v-col>
          </v-row>

          <v-row>
            <v-col cols="12" class="d-flex justify-end">
              <v-btn color="primary" @click="loadLogs" class="mr-2">
                <v-icon left>mdi-refresh</v-icon>
                {{ t('logsView.refresh') }}
              </v-btn>
              <v-btn 
                color="error" 
                @click="showClearDialog = true"
                v-if="hasSystemAdminPermission"
              >
                <v-icon left>mdi-delete</v-icon>
                {{ t('logsView.clearLogs') }}
              </v-btn>
            </v-col>
          </v-row>

          <!-- Filterleiste: Anzahl der Eintraege, wie im Entwurf. -->
          <div class="k-toolbar">
              <span class="k-toolbar__spacer"></span>
              <span class="k-toolbar__count">{{ $t("common.entries", { n: (logs || []).length }) }}</span>
          </div>
          <v-data-table
            :headers="headers"
            :items="logs"
            :loading="loading"
            class="elevation-1"
            :server-items-length="totalLogs"
            v-model:options="options"
            @update:options="onOptionsChange"
           density="compact">
            <template #[`item.timestamp`]="{ item }">
              {{ formatDateTime(item.timestamp) }}
            </template>
            
            <template #[`item.log_type`]="{ item }">
              <v-chip 
                :color="getLogTypeColor(item.log_type)" 
                dark
                small
              >
                {{ item.log_type }}
              </v-chip>
            </template>

            <template #[`item.changed_data`]="{ item }">
              <v-btn
                small
                text
                color="primary"
                @click="showDetails(item)"
                v-if="item.changed_data"
              >
                {{ t('logsView.viewDetails') }}
              </v-btn>
              <span v-else>{{ t('logsView.noDetails') }}</span>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>
    </v-container>

    <!-- Log Details Dialog -->
    <v-dialog v-model="detailsDialog" max-width="800px">
      <v-card>
        <v-card-title>
          {{ t('logsView.dialogDetails') }}
          <v-spacer></v-spacer>
          <v-btn icon @click="detailsDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-simple-table v-if="selectedLog && selectedLog.changed_data">
            <template #default>
              <thead>
                <tr>
                  <th>{{ t('logsView.field') }}</th>
                  <th>{{ t('logsView.oldValue') }}</th>
                  <th>{{ t('logsView.newValue') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(change, index) in selectedLog.changed_data" :key="index">
                  <td>{{ change.column_name }}</td>
                  <td>{{ formatValue(change.old_value) }}</td>
                  <td>{{ formatValue(change.new_value) }}</td>
                </tr>
              </tbody>
            </template>
          </v-simple-table>
          <div v-else-if="selectedLog">
            <p>{{ t('logsView.table') }}: {{ selectedLog.table_name }}</p>
            <p>{{ t('logsView.recordId') }}: {{ selectedLog.record_id }}</p>
            <p>{{ t('logsView.action') }}: {{ selectedLog.log_type }}</p>
            <p>{{ t('logsView.message') }}: {{ selectedLog.message }}</p>
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Clear Logs Dialog -->
    <v-dialog v-model="showClearDialog" max-width="500px">
      <v-card>
        <v-card-title>{{ t('logsView.clearDialogTitle') }}</v-card-title>
        <v-card-text>
          <p>{{ t('logsView.clearDialogText') }}</p>
          
          <v-menu
            ref="clearDateMenu"
            v-model="clearDateMenu"
            :close-on-content-click="false"
            transition="scale-transition"
            offset-y
            min-width="auto"
          >
            <template v-slot:activator="{ on, attrs }">
              <v-text-field
                v-model="clearBeforeDate"
                :label="t('logsView.clearBeforeDate')"
                prepend-icon="mdi-calendar"
                readonly
                v-bind="attrs"
                v-on="on"
                class="mt-4"
              ></v-text-field>
            </template>
            <v-date-picker
              v-model="clearBeforeDate"
              @input="clearDateMenu = false"
            ></v-date-picker>
          </v-menu>

          <v-select
            v-model="clearLogType"
            :items="logTypes"
            :label="t('logsView.clearOnlyType')"
            clearable
            prepend-icon="mdi-tag"
            class="mt-4"
          ></v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey darken-1" text @click="showClearDialog = false">
            {{ t('cancel') }}
          </v-btn>
          <v-btn
            color="error"
            @click="clearLogs"
            :disabled="!clearBeforeDate && !clearLogType"
          >
            {{ t('logsView.clearLogs') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useStore } from 'vuex';
import axios from 'axios';
import { debounce } from 'lodash';
import { formatDate } from '@/utils/dateFormatter';
import { useI18n } from 'vue-i18n';
import { useModulePermission } from '@/composables/useModulePermission';

export default {
  name: 'LogsView',
  setup() {
    const store = useStore();
    const { t } = useI18n();
    const loading = ref(false);
    const logs = ref([]);
    const totalLogs = ref(0);
    const logTypes = ref([]);
    const users = ref([]);
    const selectedLog = ref(null);
    const detailsDialog = ref(false);
    const searchTerm = ref('');
    const startDate = ref(null);
    const endDate = ref(null);
    const selectedUser = ref(null);
    const selectedLogType = ref(null);
    const startDateMenu = ref(false);
    const endDateMenu = ref(false);
    const showClearDialog = ref(false);
    const clearBeforeDate = ref(null);
    const clearLogType = ref(null);
    const clearDateMenu = ref(false);
    
    const options = reactive({
      page: 1,
      itemsPerPage: 25,
      sortBy: ['timestamp'],
      sortDesc: [true]
    });

    const headers = [
      { text: t('logsView.timestamp'), value: 'timestamp', width: '15%' },
      { text: t('logsView.user'), value: 'username', width: '10%' },
      { text: t('logsView.logType'), value: 'log_type', width: '10%' },
      { text: t('logsView.message'), value: 'message', width: '30%' },
      { text: t('logsView.table'), value: 'table_name', width: '15%' },
      { text: t('logsView.recordId'), value: 'record_id', width: '10%' },
      { text: t('logsView.details'), value: 'changed_data', width: '10%' }
    ];

    const { hasModulePermission } = useModulePermission();

    const hasSystemAdminPermission = computed(() => {
      return hasModulePermission('system', 'ADMIN');
    });

    const logTypeColors = {
      'INSERT': 'green',
      'UPDATE': 'blue',
      'DELETE': 'red',
      'ERROR': 'deep-orange',
      'LOGIN': 'indigo',
      'LOGOUT': 'purple',
      'ADMIN_ACTION': 'amber darken-3'
    };

    function getLogTypeColor(type) {
      return logTypeColors[type] || 'grey';
    }

    const debounceSearch = debounce(() => {
      loadLogs();
    }, 500);

    function formatDateTime(dateTimeStr) {
      if (!dateTimeStr) return '';
      const date = new Date(dateTimeStr);
      return formatDate(date, 'dd.MM.yyyy HH:mm:ss');
    }

    function formatValue(value) {
      if (value === null || value === undefined) return '-';
      if (typeof value === 'object') return JSON.stringify(value);
      if (value === true) return 'Yes';
      if (value === false) return 'No';
      return value.toString();
    }

    function showDetails(log) {
      selectedLog.value = log;
      detailsDialog.value = true;
    }

    async function loadLogs() {
      try {
        loading.value = true;
        
        const params = {
          limit: options.itemsPerPage,
          offset: (options.page - 1) * options.itemsPerPage
        };

        if (searchTerm.value) params.searchTerm = searchTerm.value;
        if (startDate.value) params.startDate = startDate.value;
        if (endDate.value) params.endDate = endDate.value;
        if (selectedUser.value) params.userId = selectedUser.value;
        if (selectedLogType.value) params.logType = selectedLogType.value;

        const response = await axios.get('/admin/logs/?action=getLogEntries', { params });
        
        logs.value = response.data.logs || [];
        totalLogs.value = response.data.total || 0;
      } catch (error) {
        console.error('Error loading logs:', error);
        store.dispatch('errorSnackbar/showError', {
          error: t('logsView.failedLoad'),
          details: error.response?.data?.error || error.message
        });
      } finally {
        loading.value = false;
      }
    }

    async function loadLogTypes() {
      try {
        const response = await axios.get('/admin/logs/', { 
          params: { action: 'getLogTypes' } 
        });
        logTypes.value = response.data || [];
      } catch (error) {
        console.error('Error loading log types:', error);
      }
    }

    async function loadLogUsers() {
      try {
        const response = await axios.get('/admin/logs/', { 
          params: { action: 'getLogUsers' } 
        });
        users.value = response.data || [];
      } catch (error) {
        console.error('Error loading log users:', error);
      }
    }

    async function clearLogs() {
      if (!clearBeforeDate.value && !clearLogType.value) {
        store.dispatch('errorSnackbar/showError', {
          error: t('logsView.noFilterWarning')
        });
        return;
      }

      try {
        loading.value = true;
        
        const data = {};
        if (clearBeforeDate.value) data.beforeDate = clearBeforeDate.value;
        if (clearLogType.value) data.logType = clearLogType.value;

        const response = await axios.post('/admin/logs/', {
          action: 'clearLogs',
          ...data
        });
        
        store.dispatch('snackbar/showSuccess', {
          text: response.data.message,
          timeout: 5000
        });
        
        showClearDialog.value = false;
        clearBeforeDate.value = null;
        clearLogType.value = null;
        
        // Reload logs to reflect changes
        loadLogs();
        
      } catch (error) {
        console.error('Error clearing logs:', error);
        store.dispatch('errorSnackbar/showError', {
          error: t('logsView.failedClear'),
          details: error.response?.data?.error || error.message
        });
      } finally {
        loading.value = false;
      }
    }

    function onOptionsChange() {
      loadLogs();
    }

    watch([startDate, endDate, selectedUser, selectedLogType], () => {
      loadLogs();
    });

    onMounted(() => {
      loadLogs();
      loadLogTypes();
      loadLogUsers();
    });

    return {
      loading,
      logs,
      totalLogs,
      logTypes,
      users,
      headers,
      options,
      searchTerm,
      startDate,
      endDate,
      selectedUser,
      selectedLogType,
      startDateMenu,
      endDateMenu,
      selectedLog,
      detailsDialog,
      showClearDialog,
      clearBeforeDate,
      clearLogType,
      clearDateMenu,
      hasSystemAdminPermission,
      formatDateTime,
      formatValue,
      getLogTypeColor,
      showDetails,
      loadLogs,
      debounceSearch,
      onOptionsChange,
      clearLogs,
      t
    };
  }
};
</script>

<style scoped>
.logs-view {
  padding: 16px;
}
</style> 