<template>
  <div class="logs-view">
    <v-container fluid>
      <v-row>
        <v-col cols="12">
          <v-card class="mb-6" elevation="3" rounded="lg">
            <v-img
              height="80"
              src="https://picsum.photos/1920/80?random"
              gradient="to bottom, rgba(0,0,0,.7), rgba(0,0,0,.9)"
              cover
            >
              <v-card-title class="d-flex justify-space-between align-center text-white">
                <div class="d-flex align-center">
                  <v-icon icon="mdi-database-search" class="mr-2" size="large" />
                  <span class="text-h5">System Logs</span>
                  <v-tooltip location="top">
                    <template v-slot:activator="{ props }">
                      <v-icon v-bind="props" icon="mdi-information-outline" class="mx-2" size="small" />
                    </template>
                    <span>Systemweite Änderungen und Aktivitäten</span>
                  </v-tooltip>
                </div>
                <div>
                  <v-btn
                    color="white"
                    variant="outlined"
                    prepend-icon="mdi-refresh"
                    @click="fetchLogs"
                    :loading="logsLoading"
                    class="mr-2"
                  >
                    Aktualisieren
                  </v-btn>
                </div>
              </v-card-title>
            </v-img>
          </v-card>
        </v-col>
      </v-row>

      <!-- Stats Cards -->
      <v-row v-if="showStats">
        <v-col cols="12">
          <v-card class="mb-4" elevation="2" rounded="lg">
            <v-card-title class="d-flex align-center justify-space-between py-3 px-4">
              <div class="d-flex align-center">
                <v-icon icon="mdi-chart-box" class="mr-2" color="primary" />
                <span>System Logs Statistiken</span>
              </div>
              <v-btn icon variant="text" @click="showStats = !showStats">
                <v-icon>mdi-minus</v-icon>
              </v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="pa-4">
              <v-row>
                <!-- Total Logs -->
                <v-col cols="12" md="3">
                  <v-card class="stats-card" color="primary" dark elevation="3" rounded="lg">
                    <v-card-text class="text-center pa-4">
                      <v-icon icon="mdi-database" size="x-large" class="mb-2"></v-icon>
                      <div class="text-h4 font-weight-bold">{{ logStats.totalLogs.toLocaleString() }}</div>
                      <div class="text-subtitle-1">Gesamtanzahl Logs</div>
                    </v-card-text>
                  </v-card>
                </v-col>
                
                <!-- Top Users -->
                <v-col cols="12" md="3">
                  <v-card class="stats-card h-100" elevation="3" rounded="lg">
                    <v-card-title class="d-flex align-center py-3">
                      <v-icon icon="mdi-account-group" class="mr-2" color="info" />
                      <span>Top Benutzer</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-list dense class="py-0">
                      <v-list-item 
                        v-for="user in showAllTopUsers ? logStats.topUsers : logStats.topUsers.slice(0, 5)" 
                        :key="user.user_id" 
                        class="py-1"
                      >
                        <template v-slot:prepend>
                          <v-avatar color="info" size="32" class="mr-2">
                            <span class="text-caption">{{ user.username ? user.username.charAt(0) : '?' }}</span>
                          </v-avatar>
                        </template>
                        <v-list-item-title>{{ user.username || 'Unbekannter Benutzer' }}</v-list-item-title>
                        <template v-slot:append>
                          <v-chip size="small" color="info" variant="outlined">{{ user.action_count }}</v-chip>
                        </template>
                      </v-list-item>
                      <v-list-item v-if="logStats.topUsers.length === 0" class="py-2">
                        <v-list-item-title class="text-center text-body-2 text-disabled">Keine Daten verfügbar</v-list-item-title>
                      </v-list-item>
                      <v-divider v-if="logStats.topUsers.length > 5" class="my-2"></v-divider>
                      <v-list-item v-if="logStats.topUsers.length > 5" @click="showAllTopUsers = !showAllTopUsers" class="py-1">
                        <v-list-item-title class="text-center text-caption">
                          {{ showAllTopUsers ? 'Weniger anzeigen' : `${logStats.topUsers.length - 5} weitere anzeigen` }}
                        </v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-card>
                </v-col>
                
                <!-- Action Types -->
                <v-col cols="12" md="3">
                  <v-card class="stats-card h-100" elevation="3" rounded="lg">
                    <v-card-title class="d-flex align-center py-3">
                      <v-icon icon="mdi-gesture-tap" class="mr-2" color="success" />
                      <span>Aktionstypen</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-list dense class="py-0">
                      <v-list-item 
                        v-for="action in showAllActionTypes ? logStats.actionTypes : logStats.actionTypes.slice(0, 5)" 
                        :key="action.action" 
                        class="py-1"
                      >
                        <v-list-item-title>{{ action.action || 'Unbekannt' }}</v-list-item-title>
                        <template v-slot:append>
                          <v-chip size="small" :color="getActionColor(action.action)" text-color="white">
                            {{ action.count }}
                          </v-chip>
                        </template>
                      </v-list-item>
                      <v-list-item v-if="logStats.actionTypes.length === 0" class="py-2">
                        <v-list-item-title class="text-center text-body-2 text-disabled">Keine Daten verfügbar</v-list-item-title>
                      </v-list-item>
                      <v-divider v-if="logStats.actionTypes.length > 5" class="my-2"></v-divider>
                      <v-list-item v-if="logStats.actionTypes.length > 5" @click="showAllActionTypes = !showAllActionTypes" class="py-1">
                        <v-list-item-title class="text-center text-caption">
                          {{ showAllActionTypes ? 'Weniger anzeigen' : `${logStats.actionTypes.length - 5} weitere anzeigen` }}
                        </v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-card>
                </v-col>
                
                <!-- Top Tables -->
                <v-col cols="12" md="3">
                  <v-card class="stats-card h-100" elevation="3" rounded="lg">
                    <v-card-title class="d-flex align-center py-3">
                      <v-icon icon="mdi-table" class="mr-2" color="warning" />
                      <span>Top Tabellen</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-list dense class="py-0">
                      <v-list-item 
                        v-for="table in showAllTopTables ? logStats.topTables : logStats.topTables.slice(0, 5)" 
                        :key="table.table_name" 
                        class="py-1"
                      >
                        <v-list-item-title class="text-truncate" :title="table.table_name">
                          {{ table.table_name || 'Unbekannt' }}
                        </v-list-item-title>
                        <template v-slot:append>
                          <v-chip size="small" color="warning" variant="tonal">{{ table.count }}</v-chip>
                        </template>
                      </v-list-item>
                      <v-list-item v-if="logStats.topTables.length === 0" class="py-2">
                        <v-list-item-title class="text-center text-body-2 text-disabled">Keine Daten verfügbar</v-list-item-title>
                      </v-list-item>
                      <v-divider v-if="logStats.topTables.length > 5" class="my-2"></v-divider>
                      <v-list-item v-if="logStats.topTables.length > 5" @click="showAllTopTables = !showAllTopTables" class="py-1">
                        <v-list-item-title class="text-center text-caption">
                          {{ showAllTopTables ? 'Weniger anzeigen' : `${logStats.topTables.length - 5} weitere anzeigen` }}
                        </v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
      
      <!-- Filters -->
      <v-row>
        <v-col cols="12">
          <v-card class="mb-4" elevation="2" rounded="lg">
            <v-card-title class="d-flex align-center py-3 px-4">
              <v-icon icon="mdi-filter-variant" class="mr-2" color="primary" />
              <span>Filter</span>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="pa-4">
              <v-row>
                <!-- Date Range -->
                <v-col cols="12" md="6" lg="3">
                  <v-menu
                    v-model="startDateMenu"
                    :close-on-content-click="false"
                    transition="scale-transition"
                    offset-y
                    min-width="auto"
                  >
                    <template #activator="{ on, attrs }">
                      <v-text-field
                        v-model="filter.start_date"
                        label="Start Datum"
                        prepend-icon="mdi-calendar"
                        readonly
                        v-bind="attrs"
                        v-on="on"
                        clearable
                        @click:clear="filter.start_date = ''"
                        density="comfortable"
                        variant="outlined"
                        hide-details
                      ></v-text-field>
                    </template>
                    <v-date-picker
                      v-model="filter.start_date"
                      @input="startDateMenu = false"
                    ></v-date-picker>
                  </v-menu>
                </v-col>
                
                <v-col cols="12" md="6" lg="3">
                  <v-menu
                    v-model="endDateMenu"
                    :close-on-content-click="false"
                    transition="scale-transition"
                    offset-y
                    min-width="auto"
                  >
                    <template #activator="{ on, attrs }">
                      <v-text-field
                        v-model="filter.end_date"
                        label="End Datum"
                        prepend-icon="mdi-calendar"
                        readonly
                        v-bind="attrs"
                        v-on="on"
                        clearable
                        @click:clear="filter.end_date = ''"
                        density="comfortable"
                        variant="outlined"
                        hide-details
                      ></v-text-field>
                    </template>
                    <v-date-picker
                      v-model="filter.end_date"
                      @input="endDateMenu = false"
                    ></v-date-picker>
                  </v-menu>
                </v-col>
                
                <!-- Action Type -->
                <v-col cols="12" md="6" lg="3">
                  <v-select
                    v-model="filter.action_type"
                    :items="actionTypeOptions"
                    label="Aktionstyp"
                    prepend-icon="mdi-gesture-tap"
                    clearable
                    @click:clear="filter.action_type = ''"
                    density="comfortable"
                    variant="outlined"
                    hide-details
                  ></v-select>
                </v-col>
                
                <!-- Table Name -->
                <v-col cols="12" md="6" lg="3">
                  <v-select
                    v-model="filter.table_name"
                    :items="tableNameOptions"
                    label="Tabelle"
                    prepend-icon="mdi-table"
                    clearable
                    density="comfortable"
                    variant="outlined"
                    hide-details
                  ></v-select>
                </v-col>
                
                <!-- Authority - Only visible for SYSTEM_ADMIN -->
                <v-col cols="12" md="6" lg="3" v-if="isSystemAdmin">
                  <v-select
                    v-model="filter.authority_id"
                    :items="authorityOptions"
                    item-title="name"
                    item-value="id"
                    label="Authority (nur für SYSTEM_ADMIN)"
                    prepend-icon="mdi-office-building"
                    clearable
                    density="comfortable"
                    variant="outlined"
                    hide-details
                    :hint="filter.authority_id ? '' : 'Alle Authorities'"
                    persistent-hint
                  ></v-select>
                </v-col>
                
                <!-- Search -->
                <v-col cols="12" md="6" lg="3">
                  <v-text-field
                    v-model="filter.search"
                    label="Suche"
                    prepend-icon="mdi-magnify"
                    clearable
                    density="comfortable"
                    variant="outlined"
                    hide-details
                  ></v-text-field>
                </v-col>
                
                <!-- Actions -->
                <v-col cols="12" md="12" lg="6" class="d-flex justify-end align-center mt-4">
                  <v-btn color="primary" class="mr-2" variant="tonal" @click="fetchLogs">
                    <v-icon left class="mr-1">mdi-refresh</v-icon>
                    Aktualisieren
                  </v-btn>
                  
                  <v-btn color="grey" class="mr-2" variant="tonal" @click="resetFilters">
                    <v-icon left class="mr-1">mdi-filter-remove</v-icon>
                    Filter zurücksetzen
                  </v-btn>
                  
                  <v-btn color="success" class="mr-2" variant="tonal" :href="exportUrl" target="_blank">
                    <v-icon left class="mr-1">mdi-file-export</v-icon>
                    CSV Export
                  </v-btn>
                  
                  <v-btn v-if="isSystemAdmin" color="error" variant="tonal" @click="showClearDialog = true">
                    <v-icon left class="mr-1">mdi-delete-sweep</v-icon>
                    Logs löschen
                  </v-btn>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
      
      <!-- Data Table -->
      <v-row>
        <v-col cols="12">
          <v-card elevation="2" rounded="lg">
            <!-- Filterleiste: Anzahl rechts, daneben die Spaltenauswahl. -->
            <KTableToolbar :columns="kCols" :shown="(logs || []).length" />
            <v-data-table
              :headers="kCols.visible.value"
              :items="logs"
              :loading="logsLoading"
              v-model:options="tableOptions"
              :server-items-length="pagination.totalItems"
              :items-per-page="pagination.itemsPerPage"
              v-model:page="pagination.page"
              :items-per-page-options="[25, 50, 100]"
              item-key="id"
              class="elevation-0"
              @update:options="onTableOptionsChange"
             density="compact">
              <!-- Action Column -->
              <template #[`item.action`]="{ item }">
                <v-chip size="small" :color="getActionColor(item.action)" text-color="white">
                  {{ item.action }}
                </v-chip>
              </template>
              
              <!-- Timestamp Column -->
              <template #[`item.timestamp`]="{ item }">
                {{ formatDateTime(item.timestamp) }}
              </template>
              
              <!-- Column Name (truncated) -->
              <template #[`item.column_name`]="{ item }">
                <span class="text-truncate d-inline-block" style="max-width: 150px;">
                  {{ item.column_name || '–' }}
                </span>
              </template>
              
              <!-- Details Button -->
              <template #[`item.actions`]="{ item }">
                <v-btn
                  icon
                  size="small"
                  variant="text"
                  color="primary"
                  @click="openLogDetails(item)"
                >
                  <v-icon>mdi-eye</v-icon>
                </v-btn>
              </template>
              
              <!-- Loading State -->
              <template #loading>
                <v-skeleton-loader
                  type="table-row@6"
                  class="pa-4"
                ></v-skeleton-loader>
              </template>
              
              <!-- No Data -->
              <template #no-data>
                <div class="d-flex flex-column align-center py-8">
                  <v-icon icon="mdi-database-off" size="64" color="grey" class="mb-4"></v-icon>
                  <div class="text-h6 text-grey">Keine Logs gefunden</div>
                  <div class="text-body-2 text-grey mt-2">Versuchen Sie, Filter zu entfernen oder die Suchkriterien zu ändern.</div>
                  <v-btn
                    color="primary"
                    class="mt-4"
                    variant="outlined"
                    @click="resetFilters"
                  >
                    Filter zurücksetzen
                  </v-btn>
                </div>
              </template>
            </v-data-table>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
    
    <!-- Log Details Dialog -->
    <v-dialog v-model="showLogDetails" max-width="800" scrollable>
      <v-card elevation="24" rounded="lg">
        <v-toolbar color="primary" dark dense flat>
          <v-toolbar-title class="text-h6">
            <v-icon left class="mr-2">mdi-database-search</v-icon>
            Log Details
          </v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn icon @click="showLogDetails = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        
        <v-card-text class="pa-4">
          <!-- Loading Spinner -->
          <div v-if="loadingDetails" class="d-flex justify-center align-center py-12">
            <v-progress-circular
              indeterminate
              color="primary"
              size="64"
            ></v-progress-circular>
          </div>

          <!-- Log Details -->
          <v-row v-else-if="selectedLog">
            <v-col cols="12" md="6">
              <v-list-item>
                <template #prepend>
                  <v-icon color="primary" class="mr-2">mdi-key-variant</v-icon>
                </template>
                <v-list-item-title class="text-subtitle-2 text-grey">ID</v-list-item-title>
                <v-list-item-subtitle class="text-body-1">{{ selectedLog.id }}</v-list-item-subtitle>
              </v-list-item>
              
              <v-list-item>
                <template #prepend>
                  <v-icon color="primary" class="mr-2">mdi-clock-outline</v-icon>
                </template>
                <v-list-item-title class="text-subtitle-2 text-grey">Zeitpunkt</v-list-item-title>
                <v-list-item-subtitle class="text-body-1">{{ formatDateTime(selectedLog.timestamp) }}</v-list-item-subtitle>
              </v-list-item>
              
              <v-list-item>
                <template #prepend>
                  <v-icon color="primary" class="mr-2">mdi-account</v-icon>
                </template>
                <v-list-item-title class="text-subtitle-2 text-grey">Benutzer</v-list-item-title>
                <v-list-item-subtitle class="text-body-1">{{ selectedLog.username }} (ID: {{ selectedLog.user_id }})</v-list-item-subtitle>
              </v-list-item>
              
              <v-list-item>
                <template #prepend>
                  <v-icon color="primary" class="mr-2">mdi-gesture-tap</v-icon>
                </template>
                <v-list-item-title class="text-subtitle-2 text-grey">Aktion</v-list-item-title>
                <v-list-item-subtitle>
                  <v-chip size="small" :color="getActionColor(selectedLog.action)" dark class="mt-1">
                    {{ selectedLog.action }}
                  </v-chip>
                </v-list-item-subtitle>
              </v-list-item>
            </v-col>
            
            <v-col cols="12" md="6">
              <v-list-item>
                <template #prepend>
                  <v-icon color="primary" class="mr-2">mdi-table</v-icon>
                </template>
                <v-list-item-title class="text-subtitle-2 text-grey">Tabelle</v-list-item-title>
                <v-list-item-subtitle class="text-body-1">{{ selectedLog.table_name }}</v-list-item-subtitle>
              </v-list-item>
              
              <v-list-item>
                <template #prepend>
                  <v-icon color="primary" class="mr-2">mdi-database</v-icon>
                </template>
                <v-list-item-title class="text-subtitle-2 text-grey">Datensatz ID</v-list-item-title>
                <v-list-item-subtitle class="text-body-1">{{ selectedLog.record_id }}</v-list-item-subtitle>
              </v-list-item>
              
              <v-list-item v-if="selectedLog.column_name">
                <template #prepend>
                  <v-icon color="primary" class="mr-2">mdi-format-columns</v-icon>
                </template>
                <v-list-item-title class="text-subtitle-2 text-grey">Spalte</v-list-item-title>
                <v-list-item-subtitle class="text-body-1">{{ selectedLog.column_name }}</v-list-item-subtitle>
              </v-list-item>
              
              <v-list-item v-if="selectedLog.authority_id">
                <template #prepend>
                  <v-icon color="primary" class="mr-2">mdi-office-building</v-icon>
                </template>
                <v-list-item-title class="text-subtitle-2 text-grey">Behörde ID</v-list-item-title>
                <v-list-item-subtitle class="text-body-1">{{ selectedLog.authority_id }}</v-list-item-subtitle>
              </v-list-item>
            </v-col>
            
            <v-col cols="12">
              <v-divider class="my-3"></v-divider>

              <!-- BATCH_UPDATE: Zeige als Tabelle -->
              <v-row v-if="selectedLog.column_name === 'BATCH_UPDATE'">
                <v-col cols="12">
                  <v-card outlined class="mb-3">
                    <v-card-title class="text-subtitle-1 py-2 px-4 bg-blue-lighten-4">
                      <v-icon color="blue" class="mr-2">mdi-table-edit</v-icon>
                      Batch-Update ({{ Object.keys(selectedLog.new_value || {}).length }} Felder geändert)
                    </v-card-title>
                    <v-card-text class="px-0 py-0">
                      <v-table>
                        <thead>
                          <tr>
                            <th class="text-left">Feld</th>
                            <th class="text-left">Alter Wert</th>
                            <th class="text-center"><v-icon small>mdi-arrow-right</v-icon></th>
                            <th class="text-left">Neuer Wert</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(newValue, key) in selectedLog.new_value" :key="key">
                            <td class="font-weight-bold text-no-wrap">{{ key }}</td>
                            <td>
                              <v-chip size="small" variant="outlined" color="grey">
                                {{ formatValue(selectedLog.old_value?.[key]) }}
                              </v-chip>
                            </td>
                            <td class="text-center">
                              <v-icon small color="primary">mdi-arrow-right</v-icon>
                            </td>
                            <td>
                              <v-chip size="small" variant="outlined" color="success">
                                {{ formatValue(newValue) }}
                              </v-chip>
                            </td>
                          </tr>
                        </tbody>
                      </v-table>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>

              <!-- Normale Darstellung für einzelne Änderungen -->
              <v-row v-else>
                <v-col cols="12" md="6" v-if="selectedLog.old_value !== null">
                  <v-card outlined class="mb-3">
                    <v-card-title class="text-subtitle-1 py-2 px-4 bg-grey-lighten-4">
                      <v-icon color="grey" class="mr-2">mdi-arrow-left-circle</v-icon>
                      Alter Wert
                    </v-card-title>
                    <v-card-text class="px-4 py-3">
                      <pre>{{ formatValue(selectedLog.old_value) }}</pre>
                    </v-card-text>
                  </v-card>
                </v-col>

                <v-col cols="12" md="6" v-if="selectedLog.new_value !== null">
                  <v-card outlined class="mb-3">
                    <v-card-title class="text-subtitle-1 py-2 px-4 bg-grey-lighten-4">
                      <v-icon color="success" class="mr-2">mdi-arrow-right-circle</v-icon>
                      Neuer Wert
                    </v-card-title>
                    <v-card-text class="px-4 py-3">
                      <pre>{{ formatValue(selectedLog.new_value) }}</pre>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-col>
          </v-row>
        </v-card-text>
        
        <v-divider></v-divider>
        
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="tonal" @click="showLogDetails = false">
            Schließen
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Clear Logs Dialog -->
    <v-dialog v-model="showClearDialog" max-width="500" persistent>
      <v-card elevation="24" rounded="lg">
        <v-toolbar color="error" dark flat>
          <v-toolbar-title class="text-h6">
            <v-icon left class="mr-2">mdi-delete-sweep</v-icon>
            Logs löschen
          </v-toolbar-title>
        </v-toolbar>
        
        <v-card-text class="pa-4 pt-5">
          <p class="text-body-1 mb-4">
            <v-icon color="warning" class="mr-2">mdi-alert</v-icon>
            <strong>Warnung:</strong> Das Löschen der Logs kann nicht rückgängig gemacht werden.
          </p>
          
          <v-radio-group v-model="clearMode" class="mt-4" hide-details>
            <v-radio label="Alle Logs löschen" value="all"></v-radio>
            <v-radio label="Logs vor einem Datum löschen" value="date"></v-radio>
          </v-radio-group>
          
          <v-menu
            v-if="clearMode === 'date'"
            v-model="clearDateMenu"
            :close-on-content-click="false"
            transition="scale-transition"
            offset-y
            min-width="auto"
            class="mt-4"
          >
            <template #activator="{ on, attrs }">
              <v-text-field
                v-model="clearDate"
                label="Datum auswählen"
                prepend-icon="mdi-calendar"
                readonly
                density="comfortable"
                variant="outlined"
                v-bind="attrs"
                v-on="on"
              ></v-text-field>
            </template>
            <v-date-picker
              v-model="clearDate"
              @input="clearDateMenu = false"
            ></v-date-picker>
          </v-menu>
        </v-card-text>
        
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="showClearDialog = false">
            Abbrechen
          </v-btn>
          <v-btn
            color="error"
            :loading="clearingLogs"
            @click="clearLogs"
          >
            Logs löschen
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch, unref } from 'vue';
import { apiClientAuth } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import LogsService from '@/services/LogsService';
import { useModulePermission } from '@/composables/useModulePermission';

import KTableToolbar from '@/components/table/KTableToolbar.vue';
import { useTableColumns } from '@/composables/useTableColumns';
const authStore = useAuthStore();
const toast = useToast();
const { t } = useI18n();
const { hasModulePermission, hasAllPermissions } = useModulePermission();

// Dialog state
const startDateMenu = ref(false);
const endDateMenu = ref(false);
const clearDateMenu = ref(false);
const tableOptions = ref({
  page: 1,
  itemsPerPage: 25
});

// Define interface to match actual database structure
interface Log {
  id: number;
  action: string;       // Changed from log_type
  table_name: string;
  record_id: number;
  user_id: number;
  column_name: string;
  old_value: string | null;  // Added separate fields for old/new values
  new_value: string | null;
  timestamp: string;
  last_login: string | null;
  authority_id: number;
  username?: string;    // Added from join
}

interface UserStat {
  user_id: number;
  username: string;
  action_count: number;
}

interface ActionType {
  action: string;
  count: number;
}

interface TableStat {
  table_name: string;
  count: number;
}

interface LogStats {
  topUsers: UserStat[];
  actionTypes: ActionType[];
  topTables: TableStat[];
  totalLogs: number;
}

// Computed property for SYSTEM_ADMIN (both authority AND permission required)
// SYSTEM_ADMIN maps to { module: 'system', action: 'admin' }
const isSystemAdmin = computed(() => {
  const authority = authStore.user?.authority || '';
  return authority === 'SYSTEM_ADMIN' && (hasAllPermissions.value || hasModulePermission('system', 'admin'));
});

// Data
const logs = ref<Log[]>([]);
const logStats = ref<LogStats>({
  topUsers: [],
  actionTypes: [],
  topTables: [],
  totalLogs: 0
});
const showStats = ref(true);
const logsLoading = ref(false);
const statsLoading = ref(false);
const actionTypeOptions = ref([]);
const tableNameOptions = ref([]);
const authorityOptions = ref<{ id: number; name: string }[]>([]);

// Filter and Pagination
const filter = reactive({
  page: 1,
  limit: 25,
  start_date: '',
  end_date: '',
  action_type: '', // Keep filter name the same for simplicity
  table_name: '',
  authority_id: undefined as number | undefined,
  search: ''
});

const pagination = reactive({
  page: 1,
  itemsPerPage: 25,
  totalItems: 0,
  totalPages: 0
});

// Table Settings - Updated to match actual database columns
const tableHeaders = computed(() => [
  { title: t('logs.headers.id'), key: 'id', sortable: true, width: '60px', optional: true },
  { title: t('logs.headers.action'), key: 'action', sortable: true, width: '100px' },
  { title: t('logs.headers.table'), key: 'table_name', sortable: true },
  { title: t('logs.headers.user'), key: 'username', sortable: true },
  { title: t('logs.headers.column'), key: 'column_name', sortable: true, optional: true },
  { title: t('logs.headers.recordId'), key: 'record_id', sortable: true, optional: true },
  { title: t('logs.headers.timestamp'), key: 'timestamp', sortable: true, align: 'end' },
  { title: t('logs.headers.details'), key: 'actions', sortable: false, width: '60px' }
]);

// Log Details Dialog
const showLogDetails = ref(false);
const selectedLog = ref<Log | null>(null);

// Clear Logs Dialog
const showClearDialog = ref(false);
const clearMode = ref('all');
const clearDate = ref('');
const clearingLogs = ref(false);

// Add these new ref variables to control the expanded state
const showAllTopUsers = ref(false);
const showAllActionTypes = ref(false);
const showAllTopTables = ref(false);

// Export URL
const exportUrl = computed(() => {
  const params = new URLSearchParams();
  params.append('action', 'getLogEntries');
  params.append('format', 'csv');
  
  if (filter.search) params.append('searchTerm', filter.search);
  if (filter.start_date) params.append('startDate', filter.start_date);
  if (filter.end_date) params.append('endDate', filter.end_date);
  if (filter.action_type) params.append('logType', filter.action_type);
  if (filter.authority_id) params.append('authorityId', filter.authority_id.toString());
  
  return `/admin/logs/?${params.toString()}`;
});

// Methods
const fetchLogs = async () => {
  logsLoading.value = true;
  try {
    // Build query parameters from filter options
    const params = new URLSearchParams();
    params.append('action', 'getLogEntries');

    for (const [key, value] of Object.entries(filter)) {
      if (value !== undefined && value !== null && value !== '') {
        // Map action_type to action since that's the actual column name
        if (key === 'action_type') {
          params.append('action', String(value));
        } else if (key === 'authority_id') {
          // Only add authorityId if SYSTEM_ADMIN and a specific authority is selected
          if (isSystemAdmin.value) {
            params.append('authorityId', String(value));
          }
        } else {
          params.append(key, String(value));
        }
      }
    }

    const response = await apiClientAuth.get(`/admin/logs/?${params.toString()}`);
    logs.value = response.data.logs || [];
    pagination.totalItems = response.data.total || 0;
    pagination.totalPages = Math.ceil(response.data.total / pagination.itemsPerPage) || 0;

    // After fetching paginated logs, refresh statistics with ALL logs
    await fetchStatsOnly();
  } catch (error: any) {
    console.error('Error fetching logs:', error);
    toast.error(error.response?.data?.error || 'Fehler beim Laden der Logs.');
  } finally {
    logsLoading.value = false;
  }
};

const fetchStatsOnly = async () => {
  statsLoading.value = true;
  try {
    // Build filter parameters
    const statsParams = new URLSearchParams();
    statsParams.append('action', 'getLogStats');

    if (filter.start_date) {
      statsParams.append('startDate', filter.start_date);
    }

    if (filter.end_date) {
      statsParams.append('endDate', filter.end_date);
    }

    if (filter.search) {
      statsParams.append('searchTerm', filter.search);
    }

    // Only add authorityId if SYSTEM_ADMIN and a specific authority is selected
    if (isSystemAdmin.value && filter.authority_id) {
      statsParams.append('authorityId', String(filter.authority_id));
    }
    
    // Call the server-side stats API
    const response = await apiClientAuth.get(`/admin/logs?${statsParams.toString()}`);
    
    if (response.data && response.data.success) {
      // Update statistics from server response
      logStats.value = {
        totalLogs: response.data.stats.totalLogs || 0,
        topUsers: response.data.stats.topUsers || [],
        actionTypes: response.data.stats.actionTypes || [],
        topTables: response.data.stats.topTables || [],
      };
      
      // Update filter dropdowns directly from server response
      actionTypeOptions.value = response.data.actionTypes || [];
      tableNameOptions.value = response.data.tableNames || [];
      
      console.log(`Loaded statistics with ${logStats.value.totalLogs} total logs.`);
    }
  } catch (error) {
    console.error('Error fetching log stats:', error);
    toast.error('Fehler beim Laden der Statistiken.');
  } finally {
    statsLoading.value = false;
  }
};

const fetchStats = async () => {
  try {
    // Call the separate function to fetch stats
    await fetchStatsOnly();
  } catch (error) {
    console.error('Error in fetchStats:', error);
  }
};

const fetchAuthorities = async () => {
  // Only fetch authorities if user is SYSTEM_ADMIN
  if (!isSystemAdmin.value) {
    return;
  }

  try {
    const response = await apiClientAuth.get('/admin/logs/?action=getAuthorities');
    authorityOptions.value = response.data || [];
  } catch (error: any) {
    console.error('Error fetching authorities:', error);
    toast.error(error.response?.data?.error || 'Fehler beim Laden der Authorities.');
  }
};

const onFilterChange = () => {
  pagination.page = 1; // Reset to first page when filter changes
  fetchLogs();
};

const onTableOptionsChange = (options: any) => {
  pagination.page = options.page;
  pagination.itemsPerPage = options.itemsPerPage;
  filter.limit = options.itemsPerPage;
  filter.page = options.page;
  fetchLogs();
};

const resetFilters = () => {
  // Reset all filters except pagination
  filter.start_date = '';
  filter.end_date = '';
  filter.action_type = '';
  filter.table_name = '';
  filter.authority_id = undefined;
  filter.search = '';
  
  // Reset pagination
  pagination.page = 1;
  filter.page = 1;
  
  // Re-fetch data
  fetchLogs();
};

// LAZY LOADING: Details erst beim Öffnen laden
const loadingDetails = ref(false);

const openLogDetails = async (log: Log) => {
  loadingDetails.value = true;
  showLogDetails.value = true;

  try {
    // Lade vollständige Details nur für diesen Log
    const fullLog = await LogsService.getLogDetails(log.id);
    selectedLog.value = fullLog;
  } catch (error: any) {
    console.error('Error loading log details:', error);
    toast.error('Fehler beim Laden der Log-Details');
    showLogDetails.value = false;
  } finally {
    loadingDetails.value = false;
  }
};

const clearLogs = async () => {
  clearingLogs.value = true;
  try {
    const requestBody: Record<string, any> = {
      action: 'clearLogs',
      mode: clearMode.value
    };
    
    // Add date filter if using date mode
    if (clearMode.value === 'date' && clearDate.value) {
      requestBody.beforeDate = clearDate.value;
    }
    
    // Send request to clear logs
    const response = await apiClientAuth.post('/admin/logs', requestBody);
    
    if (response.data?.success) {
      // Close dialog and refresh data
      showClearDialog.value = false;
      fetchLogs();
      fetchStats();
      toast.success('Logs erfolgreich gelöscht.');
    } else {
      throw new Error(response.data?.error || 'Failed to clear logs');
    }
  } catch (error: any) {
    console.error('Error clearing logs:', error);
    toast.error(error.response?.data?.error || 'Fehler beim Löschen der Logs.');
  } finally {
    clearingLogs.value = false;
  }
};

// Helper functions
const getActionColor = (action: string): string => {
  switch (action) {
    case 'INSERT': return 'success';
    case 'UPDATE': return 'info';
    case 'DELETE': return 'error';
    case 'LOGIN': return 'warning';
    default: return 'grey';
  }
};

const formatDateTime = (dateString: string): string => {
  if (!dateString) return '–';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  }).format(date);
};

const formatValue = (value: any): string => {
  if (value === null || value === undefined) return '–';
  
  // If it's already an object (parsed JSON), stringify it
  if (typeof value === 'object') {
    return JSON.stringify(value, null, 2);
  }
  
  // If it's a string, try to parse as JSON
  if (typeof value === 'string') {
    try {
      const parsed = JSON.parse(value);
      return JSON.stringify(parsed, null, 2);
    } catch (e) {
      // Not JSON, return as is
      return value;
    }
  }
  
  // For other types, convert to string
  return String(value);
};

// Lifecycle hooks
onMounted(() => {
  fetchLogs(); // This will also call fetchStatsOnly
  fetchAuthorities(); // Load authorities for SYSTEM_ADMIN
});

// Watch filter changes
watch(
  [
    () => filter.start_date,
    () => filter.end_date,
    () => filter.action_type,
    () => filter.table_name,
    () => filter.authority_id,
    () => filter.search
  ],
  () => {
    onFilterChange();
  }
);

/**
 * Spaltenauswahl: Was man sieht, sollte man auch ausgeben koennen.
 * Die Wahl liegt je Ansicht im localStorage und ueberlebt den
 * Seitenwechsel.
 */
const kCols = useTableColumns('admin/LogsView', () => unref(tableHeaders) as any);
</script>

<style scoped>
.logs-view {
  padding-bottom: 24px;
}

pre {
  white-space: pre-wrap;
  word-wrap: break-word;
  max-height: 300px;
  overflow-y: auto;
  font-family: monospace;
  font-size: 0.85em;
  padding: 8px;
  margin: 0;
  background-color: rgba(0, 0, 0, 0.03);
  border-radius: 4px;
}

.stats-card {
  transition: transform 0.2s, box-shadow 0.2s;
}

.stats-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

/* Animation for the loading icon */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.v-btn.v-btn--loading .v-btn__loader {
  animation: spin 1s linear infinite;
}

/* Responsive adjustments */
@media (max-width: 600px) {
  .v-data-table__wrapper {
    overflow-x: auto;
  }
  
  .v-toolbar-title {
    font-size: 1.1rem !important;
  }
}
</style> 