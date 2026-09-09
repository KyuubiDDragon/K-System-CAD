<template>
  <div class="whiteboard-manager">
    <!-- Debug-Controls wurden entfernt -->
    
    <!-- Verbindungsstatus-Banner -->
    <v-alert v-if="showConnectionAlert" type="warning" closable class="connection-alert"
      @click:close="showConnectionAlert = false">
      {{ t('whiteboard.connectionLost') }}
      <v-btn size="small" color="primary" class="ml-2" @click="reconnectSocket">
        {{ t('whiteboard.reconnect') }}
      </v-btn>
    </v-alert>
    
    <v-card class="manager-card" :class="{ 'dark-mode': darkMode }">
      <v-tabs v-model="activeTab" background-color="primary">
        <v-tab value="my-boards">{{ t('whiteboard.myBoards') }}</v-tab>
        <v-tab value="public-boards">{{ t('whiteboard.publicBoards') }}</v-tab>
        <v-tab value="join-code">{{ t('whiteboard.joinCodeTab') }}</v-tab>
      </v-tabs>

      <v-card-text>
        <!-- Meine Whiteboards Tab -->
        <v-window v-model="activeTab">
          <v-window-item value="my-boards">
            <div class="tab-content">
              <div class="tab-header">
                <h3>{{ t('whiteboard.myBoards') }}</h3>
                <v-btn color="primary" @click="openCreateDialog">
                  <v-icon>mdi-plus</v-icon> {{ t('whiteboard.newWhiteboard') }}
                </v-btn>
              </div>

              <v-progress-circular v-if="loading" indeterminate color="primary" class="loader"></v-progress-circular>

              <div v-else-if="myWhiteboards.length === 0" class="empty-state">
                <v-icon color="grey" size="64">mdi-drawing</v-icon>
                <p>{{ t('whiteboard.noBoards') }}</p>
                <v-btn color="primary" @click="openCreateDialog">{{ t('whiteboard.createWhiteboard') }}</v-btn>
              </div>

              <v-row v-else>
                <v-col v-for="board in myWhiteboards" :key="board.id" cols="12" sm="6" md="4">
                  <v-card class="board-card" @click="openWhiteboard(board.id)">
                    <v-card-title class="board-title">
                      <span>{{ board.name }}</span>
                      <v-chip v-if="board.accessType === 'private'" color="error" size="small" class="ml-2">{{ t('whiteboard.private') }}</v-chip>
                      <v-chip v-else-if="board.accessType === 'public'" color="success" size="small" class="ml-2">{{ t('whiteboard.public') }}</v-chip>
                      <v-chip v-else-if="board.accessType === 'code'" color="info" size="small" class="ml-2">{{ t('whiteboard.code') }}</v-chip>
                    </v-card-title>
                    
                    <v-card-text>
                      <div class="board-info">
                        <div class="info-item">
                          <v-icon size="small">mdi-calendar</v-icon>
                          <span>{{ formatDate(board.createdAt) }}</span>
                        </div>
                        <div class="info-item">
                          <v-icon size="small">mdi-account-multiple</v-icon>
                          <span>{{ board.participants }} Teilnehmer</span>
                        </div>
                        <div v-if="board.accessType === 'code' && board.joinCode" class="info-item">
                          <v-icon size="small">mdi-key</v-icon>
                          <span>Code: {{ board.joinCode }}</span>
                        </div>
                      </div>
                    </v-card-text>
                    
                    <v-card-actions>
                      <v-spacer></v-spacer>
                      <v-btn
                        size="small"
                        variant="text"
                        color="primary"
                        @click.stop="openWhiteboard(board.id)"
                      >
                        {{ t('whiteboard.open') }}
                      </v-btn>
                      <v-btn
                        v-if="board.isOwner"
                        size="small"
                        variant="text"
                        color="error"
                        @click.stop="confirmDeleteWhiteboard(board)"
                      >
                        {{ t('whiteboard.delete') }}
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-col>
              </v-row>
            </div>
          </v-window-item>

          <!-- Öffentliche Whiteboards Tab -->
          <v-window-item value="public-boards">
            <div class="tab-content">
              <div class="tab-header">
                <h3>{{ t('whiteboard.publicBoards') }}</h3>
                <v-btn color="primary" @click="refreshPublicWhiteboards">
                  <v-icon>mdi-refresh</v-icon> {{ t('whiteboard.refresh') }}
                </v-btn>
              </div>

              <v-progress-circular v-if="loading" indeterminate color="primary" class="loader"></v-progress-circular>

              <div v-else-if="publicWhiteboards.length === 0" class="empty-state">
                <v-icon color="grey" size="64">mdi-drawing-box</v-icon>
                <p>{{ t('whiteboard.noPublicBoards') }}</p>
              </div>

              <v-row v-else>
                <v-col v-for="board in publicWhiteboards" :key="board.id" cols="12" sm="6" md="4">
                  <v-card class="board-card" @click="openWhiteboard(board.id)">
                    <v-card-title class="board-title">
                      {{ board.name }}
                    </v-card-title>
                    
                    <v-card-text>
                      <div class="board-info">
                        <div class="info-item">
                          <v-icon size="small">mdi-calendar</v-icon>
                          <span>{{ formatDate(board.createdAt) }}</span>
                        </div>
                        <div class="info-item">
                          <v-icon size="small">mdi-account-multiple</v-icon>
                          <span>{{ board.participants }} Teilnehmer</span>
                        </div>
                      </div>
                    </v-card-text>
                    
                    <v-card-actions>
                      <v-spacer></v-spacer>
                      <v-btn
                        size="small"
                        variant="text"
                        color="primary"
                        @click.stop="openWhiteboard(board.id)"
                      >
                        {{ t('whiteboard.join') }}
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-col>
              </v-row>
            </div>
          </v-window-item>

          <!-- Mit Code beitreten Tab -->
          <v-window-item value="join-code">
            <div class="tab-content">
              <div class="tab-header">
                <h3>{{ t('whiteboard.joinCodeTab') }}</h3>
              </div>

              <div class="join-code-container">
                <v-card>
                  <v-card-text>
                    <p>{{ t('whiteboard.enterCode') }}</p>
                    <v-form @submit.prevent="joinWithCode">
                      <v-text-field
                        v-model="joinCodeInput"
                        :label="t('whiteboard.codeLabel')"
                        :placeholder="t('whiteboard.codeExample')"
                        class="my-3"
                        variant="outlined"
                        :error-messages="joinCodeError"
                        @input="joinCodeError = ''"
                      ></v-text-field>
                      <v-btn
                        type="submit"
                        color="primary"
                        block
                        :loading="joinLoading"
                      >
                        {{ t('whiteboard.join') }}
                      </v-btn>
                    </v-form>
                  </v-card-text>
                </v-card>
              </div>
            </div>
          </v-window-item>
        </v-window>
      </v-card-text>
    </v-card>

    <!-- Dialog zum Erstellen eines neuen Whiteboards -->
    <v-dialog v-model="showCreateDialog" max-width="500px">
      <v-card>
        <v-card-title>{{ t('whiteboard.createDialogTitle') }}</v-card-title>
        <v-card-text>
          <v-form ref="createForm" @submit.prevent>
            <v-text-field
              v-model="newWhiteboard.name"
              :label="t('whiteboard.name')"
              required
              :rules="[v => !!v || t('whiteboard.nameRequired')]"
              class="mb-3"
            ></v-text-field>
            
            <v-select
              v-model="newWhiteboard.accessType"
              :label="t('whiteboard.accessType')"
              :items="[
                { title: 'Privat (nur ich)', value: 'private' },
                { title: 'Öffentlich (für alle sichtbar)', value: 'public' },
                { title: 'Mit Code (nur mit Beitrittscode)', value: 'code' }
              ]"
              item-title="title"
              item-value="value"
              class="mb-3"
            ></v-select>
            
            <v-text-field
              v-if="newWhiteboard.accessType === 'code'"
              v-model="newWhiteboard.joinCode"
              :label="t('whiteboard.codeOptional')"
              :hint="t('whiteboard.leaveBlank')"
              class="mb-3"
            ></v-text-field>

            <v-color-picker
              v-model="newWhiteboard.backgroundColor"
              hide-inputs
              hide-canvas
              show-swatches
              swatches-max-height="150px"
            ></v-color-picker>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="showCreateDialog = false">
            {{ t('whiteboard.cancel') }}
          </v-btn>
          <v-btn
            color="primary"
            variant="text"
            @click="() => { console.log('Erstellen-Button geklickt'); createWhiteboard(); }"
            :loading="creatingWhiteboard"
            type="button"
          >
            {{ t('whiteboard.create') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog zum Löschen eines Whiteboards bestätigen -->
    <v-dialog v-model="showDeleteDialog" max-width="400px">
      <v-card>
        <v-card-title>{{ t('whiteboard.deleteTitle') }}</v-card-title>
        <v-card-text>
          {{ t('whiteboard.deleteConfirm', { name: whiteboardToDelete?.name }) }}
          {{ t('whiteboard.irreversible') }}
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="showDeleteDialog = false">
            {{ t('whiteboard.cancel') }}
          </v-btn>
          <v-btn
            color="error"
            variant="text"
            @click="deleteWhiteboard"
            :loading="deletingWhiteboard"
          >
            {{ t('whiteboard.delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Whiteboard-Canvas anzeigen, wenn ein Whiteboard ausgewählt ist -->
    <div v-if="selectedWhiteboardId" class="canvas-container">
      <whiteboard-canvas
        :whiteboard-id="selectedWhiteboardId"
        :whiteboard-name="selectedWhiteboardName"
        :participants="selectedWhiteboardParticipants"
        :initial-content="selectedWhiteboardContent"
        :history="selectedWhiteboardHistory"
        @back="handleBackFromCanvas"
        @error="handleCanvasError"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';
import WhiteboardCanvas from './WhiteboardCanvas.vue';
import { useSocketIO } from '@/composables/useSocketIO';
import { useAuthStore } from '@/stores/auth';
import { useTheme } from 'vuetify';
import { apiClientAuth } from '@/api';
import { useToast } from 'vue-toastification';

// Toast initialisieren
const toast = useToast();
const { t } = useI18n();

// Socket.io-Setup mit Statusverfolgung
const { socket: mainSocket, connected: socketConnected, initSocket } = useSocketIO();
const socketId = computed(() => mainSocket.value?.id || 'nicht verbunden');
const showConnectionAlert = ref(false);

// Auth und Theme
const authStore = useAuthStore();
const user = computed(() => authStore.user);
const theme = useTheme();
const darkMode = computed(() => theme.global.current.value.dark);

// Zustandsvariablen
const loading = ref(false);
const activeTab = ref('my-boards');
const myWhiteboards = ref([]);
const publicWhiteboards = ref([]);
const showCreateDialog = ref(false);
const showDeleteDialog = ref(false);
const creatingWhiteboard = ref(false);
const deletingWhiteboard = ref(false);
const joinLoading = ref(false);
const joinCodeInput = ref('');
const joinCodeError = ref('');
const whiteboardToDelete = ref(null);

// Whiteboard-Auswahl
const selectedWhiteboardId = ref(null);
const selectedWhiteboardName = ref('');
const selectedWhiteboardParticipants = ref(1);
const selectedWhiteboardContent = ref({});
const selectedWhiteboardHistory = ref([]);

// Neues Whiteboard
const newWhiteboard = ref({
  name: 'Neues Whiteboard',  // Standardname
  accessType: 'private',
  joinCode: '',
  backgroundColor: '#FFFFFF'
});

// Formulare
const createForm = ref(null);

// Debug-Optionen
const showDebugControls = ref(false); // Auf false gesetzt für Produktion

// ====== DUALER ANSATZ: SOCKET.IO UND API ======

// Socket.io Event-Handler
const handleMyWhiteboards = (data) => {
  myWhiteboards.value = data.whiteboards;
  loading.value = false;
};

const handlePublicWhiteboards = (data) => {
  publicWhiteboards.value = data.whiteboards;
  loading.value = false;
};

const handleWhiteboardCreated = (data) => {
  creatingWhiteboard.value = false;
  showCreateDialog.value = false;
  
  // Aktualisiere Whiteboard-Listen
  fetchMyWhiteboards();
  
  // Whiteboard direkt öffnen
  if (data.whiteboard && data.whiteboard.id) {
    openWhiteboard(data.whiteboard.id);
  }
};

const handleWhiteboardJoined = (data) => {
  joinLoading.value = false;
  
  if (data.whiteboard) {
    selectedWhiteboardId.value = data.whiteboard.id;
    selectedWhiteboardName.value = data.whiteboard.name;
    selectedWhiteboardParticipants.value = data.whiteboard.participants;
    selectedWhiteboardContent.value = data.whiteboard.content || {};
    selectedWhiteboardHistory.value = data.whiteboard.history || [];
  }
};

const handleWhiteboardError = (data) => {
  creatingWhiteboard.value = false;
  deletingWhiteboard.value = false;
  joinLoading.value = false;
  
  console.error('Socket.io Whiteboard-Fehler:', data);
  
  // Fehlermeldung anzeigen
  if (data.message) {
    toast.error(data.message);
  }
};

const handleWhiteboardDeleted = (data) => {
  deletingWhiteboard.value = false;
  showDeleteDialog.value = false;
  
  // Aktualisiere Whiteboard-Listen
  fetchMyWhiteboards();
};

// ====== API-FUNKTIONEN FÜR DIREKTEN ZUGRIFF ======

// API-Funktionen für den dualen Ansatz
const fetchMyWhiteboardsViaApi = async () => {
  loading.value = true;
  try {
    const response = await apiClientAuth.get('/whiteboard/?action=getMyWhiteboards');
    if (response.data && response.data.whiteboards) {
      myWhiteboards.value = response.data.whiteboards;
    }
  } catch (error) {
    console.error('Fehler beim Laden der Whiteboards über API:', error);
    // Fallback auf Socket.io, falls API fehlschlägt
    mainSocket.value.emit('whiteboard:my-boards');
  } finally {
    loading.value = false;
  }
};

const fetchPublicWhiteboardsViaApi = async () => {
  loading.value = true;
  try {
    const response = await apiClientAuth.get('/whiteboard/?action=getPublicWhiteboards');
    if (response.data && response.data.whiteboards) {
      publicWhiteboards.value = response.data.whiteboards;
    }
  } catch (error) {
    console.error('Fehler beim Laden der öffentlichen Whiteboards über API:', error);
    // Fallback auf Socket.io, falls API fehlschlägt
    mainSocket.value.emit('whiteboard:list');
  } finally {
    loading.value = false;
  }
};

const createWhiteboardViaApi = async () => {
  console.log('createWhiteboard-Funktion aufgerufen');
  
  try {
    // Überprüfe, ob das Formular existiert
    if (!createForm.value) {
      console.error('Formular-Referenz existiert nicht');
      return;
    }
    
    // Prüfe die einzelnen Felder manuell
    console.log('Formularwerte:', {
      name: newWhiteboard.value.name,
      accessType: newWhiteboard.value.accessType,
      joinCode: newWhiteboard.value.joinCode,
      backgroundColor: newWhiteboard.value.backgroundColor
    });
    
    // Validiere nur den Namen, die anderen Felder haben Standardwerte
    if (!newWhiteboard.value.name || newWhiteboard.value.name.trim() === '') {
      console.error('Name darf nicht leer sein');
      return;
    }
    
    // Bypass Formularvalidierung - erstelle direkt
    console.log('Erstelle Whiteboard mit Daten:', newWhiteboard.value);
    creatingWhiteboard.value = true;
    
    // Direkt über API erstellen
    console.log('Sende API-Anfrage zum Erstellen des Whiteboards');
    const response = await apiClientAuth.post('/whiteboard/?action=createWhiteboard', {
      name: newWhiteboard.value.name,
      accessType: newWhiteboard.value.accessType,
      joinCode: newWhiteboard.value.joinCode,
      backgroundColor: newWhiteboard.value.backgroundColor
    });
    
    console.log('API-Antwort erhalten:', response.data);
    
    // Whiteboard erfolgreich erstellt
    creatingWhiteboard.value = false;
    showCreateDialog.value = false;
    
    // Formular zurücksetzen
    resetCreateForm();
    
    // Listen aktualisieren
    fetchMyWhiteboards();
    
    console.log('Whiteboard erstellt, Antwort:', response.data);
    
    // Prüfe die ID-Struktur und verarbeite sie entsprechend
    if (response.data.whiteboard && response.data.whiteboard.id) {
      // Stelle sicher, dass wir die numerische ID verwenden
      // Wenn das Backend die ID als Zahl zurückgibt, verwenden wir sie direkt
      const whiteboardId = response.data.whiteboard.id;
      console.log('Öffne Whiteboard mit ID:', whiteboardId);
      
      // Warte kurz, bevor das Whiteboard geöffnet wird
      setTimeout(() => {
        openWhiteboard(whiteboardId);
      }, 500);
    }
  } catch (error) {
    console.error('Fehler beim Erstellen des Whiteboards:', error);
    toast.error(t('toast.createErrorPrefix') + (error.response?.data?.error || error.message || 'Ein unerwarteter Fehler ist aufgetreten'));
    creatingWhiteboard.value = false;
    
    // Fallback auf Socket.io, falls API fehlschlägt
    console.log('Versuche Fallback über Socket.io');
    mainSocket.value.emit('whiteboard:create', {
      name: newWhiteboard.value.name,
      accessType: newWhiteboard.value.accessType,
      joinCode: newWhiteboard.value.joinCode,
      backgroundColor: newWhiteboard.value.backgroundColor
    });
  }
};

const deleteWhiteboardViaApi = async () => {
  if (!whiteboardToDelete.value) return;
  
  deletingWhiteboard.value = true;
  
  try {
    // Stelle sicher, dass die ID im richtigen Format ist
    const whiteboardId = whiteboardToDelete.value.id;
    console.log('Lösche Whiteboard mit ID:', whiteboardId);
    
    // Direkt über API löschen
    await apiClientAuth.post('/whiteboard/?action=deleteWhiteboard', {
      whiteboardId: whiteboardId
    });
    
    // Erfolgreich gelöscht
    deletingWhiteboard.value = false;
    showDeleteDialog.value = false;
    
    // Listen aktualisieren
    fetchMyWhiteboards();
    
    // Auch über Socket.io senden für Echtzeit-Updates
    mainSocket.value.emit('whiteboard:delete', {
      whiteboardId: whiteboardId
    });
  } catch (error) {
    console.error('Fehler beim Löschen des Whiteboards über API:', error);
    toast.error(t('toast.deleteErrorPrefix') + (error.response?.data?.error || error.message || 'Unbekannter Fehler'));
    deletingWhiteboard.value = false;
    
    // Fallback auf Socket.io, falls API fehlschlägt
    mainSocket.value.emit('whiteboard:delete', {
      whiteboardId: whiteboardId
    });
  }
};

const joinWithCodeViaApi = async () => {
  if (!joinCodeInput.value) {
    joinCodeError.value = 'Bitte gib einen Beitrittscode ein';
    return;
  }
  
  joinLoading.value = true;
  
  try {
    // Direkt über API beitreten
    const response = await apiClientAuth.post('/whiteboard/?action=joinWithCode', {
      joinCode: joinCodeInput.value
    });
    
    if (response.data.success && response.data.whiteboardId) {
      // Erfolgreich beigetreten
      joinLoading.value = false;
      joinCodeInput.value = '';
      joinCodeError.value = '';
      
      // Whiteboard öffnen
      openWhiteboard(response.data.whiteboardId);
    }
  } catch (error) {
    console.error('Fehler beim Beitreten mit Code über API:', error);
    joinCodeError.value = error.response?.data?.error || 'Fehler beim Beitreten';
    joinLoading.value = false;
    
    // Fallback auf Socket.io, falls API fehlschlägt
    mainSocket.value.emit('whiteboard:join-with-code', {
      joinCode: joinCodeInput.value
    });
  }
};

const openWhiteboardViaApi = async (boardId) => {
  joinLoading.value = true;
  
  try {
    console.log('Versuche Whiteboard zu öffnen mit ID:', boardId);
    
    // Stelle sicher, dass die ID im richtigen Format ist
    // Wenn die ID bereits mit einem Präfix kommt, extrahiere die ID oder verwende direkt die numerische ID
    const numericBoardId = typeof boardId === 'string' && boardId.includes('wb_') 
      ? boardId.split('_')[2] // Beispiel: "wb_1745912542004_5chrl0ews" => "5chrl0ews"
      : boardId;
    
    console.log('Verwende numerische ID für API-Aufruf:', numericBoardId);
    
    // Whiteboard-Daten direkt über API laden
    const response = await apiClientAuth.get(`/whiteboard/?action=getWhiteboard&id=${numericBoardId}`);
    
    console.log('Whiteboard-Daten erhalten:', response.data);
    
    if (response.data.whiteboard) {
      const whiteboardData = response.data.whiteboard;
      console.log('Whiteboard gefunden:', whiteboardData);
      
      // DEBUG: Vorher-Status
      console.log('VOR UPDATE - selectedWhiteboardId:', selectedWhiteboardId.value);
      
      // Speichere Daten im lokalen State
      selectedWhiteboardId.value = whiteboardData.id;
      selectedWhiteboardName.value = whiteboardData.name;
      selectedWhiteboardParticipants.value = whiteboardData.participants || 1;
      selectedWhiteboardContent.value = whiteboardData.content || {};
      selectedWhiteboardHistory.value = whiteboardData.history || [];
      joinLoading.value = false;
      
      // DEBUG: Nachher-Status
      console.log('NACH UPDATE - selectedWhiteboardId:', selectedWhiteboardId.value);
      console.log('NACH UPDATE - selectedWhiteboardId Typ:', typeof selectedWhiteboardId.value);
      console.log('Ist Canvas anzeige-Bedingung erfüllt?', !!selectedWhiteboardId.value);
      
      // DEBUG: State dump für Templates
      setTimeout(() => {
        console.log('NACH TIMEOUT - Alle Whiteboard States:', {
          selectedWhiteboardId: selectedWhiteboardId.value,
          type: typeof selectedWhiteboardId.value,
          selectedWhiteboardName: selectedWhiteboardName.value,
          selectedWhiteboardParticipants: selectedWhiteboardParticipants.value,
          contentLength: Array.isArray(selectedWhiteboardContent.value) ? 
            selectedWhiteboardContent.value.length : 
            Object.keys(selectedWhiteboardContent.value || {}).length,
          historyLength: selectedWhiteboardHistory.value.length
        });
      }, 1000);
      
      // Auch über Socket.io beitreten - WICHTIG: Verwende die exakte ID vom Server
      console.log('Joining whiteboard via Socket.io with ID:', whiteboardData.id);
      mainSocket.value.emit('whiteboard:join', {
        whiteboardId: whiteboardData.id
      });
      
      // Socket.io kann numerische oder String-IDs unterschiedlich behandeln
      // Probiere beide Formate, falls eines nicht funktioniert
      if (typeof whiteboardData.id === 'number') {
        console.log('Zusätzlich mit String-ID versuchen:', String(whiteboardData.id));
        setTimeout(() => {
          mainSocket.value.emit('whiteboard:join', {
            whiteboardId: String(whiteboardData.id)
          });
        }, 500);
      }
    } else {
      console.error('Whiteboard-Daten wurden zurückgegeben, aber das whiteboard-Objekt fehlt');
      toast.error(t('toast.whiteboardLoadError'));
      joinLoading.value = false;
    }
  } catch (error) {
    console.error('Fehler beim Öffnen des Whiteboards über API:', error);
    toast.error(t('toast.errorPrefix') + (error.response?.data?.error || error.message || 'Whiteboard nicht gefunden'));
    joinLoading.value = false;
    
    // Fallback auf Socket.io, falls API fehlschlägt
    console.log('Versuche Fallback mit Socket.io für ID:', boardId);
    mainSocket.value.emit('whiteboard:join', {
      whiteboardId: boardId
    });
  }
};

// ====== GEMEINSAME FUNKTIONEN ======

// API-Funktionen
const fetchMyWhiteboards = () => {
  fetchMyWhiteboardsViaApi();
  // Als Backup auch über Socket.io anfragen für Fallback
  mainSocket.value.emit('whiteboard:my-boards');
};

const fetchPublicWhiteboards = () => {
  fetchPublicWhiteboardsViaApi();
  // Als Backup auch über Socket.io anfragen für Fallback
  mainSocket.value.emit('whiteboard:list');
};

const refreshPublicWhiteboards = () => {
  fetchPublicWhiteboards();
};

const createWhiteboard = () => {
  console.log("Hauptfunktion createWhiteboard wird aufgerufen");
  createWhiteboardViaApi();
};

const openWhiteboard = (boardId) => {
  if (!mainSocket.value) {
    console.warn('Socket ist null, versuche Neuverbindung vor dem Öffnen...');
    initSocket();
    setTimeout(() => {
      if (mainSocket.value && socketConnected.value) {
        openWhiteboardViaApi(boardId);
      } else {
        toast.error(t('toast.socketConnectionError'));
      }
    }, 1000);
  } else {
    openWhiteboardViaApi(boardId);
  }
};

const confirmDeleteWhiteboard = (board) => {
  whiteboardToDelete.value = board;
  showDeleteDialog.value = true;
};

const deleteWhiteboard = () => {
  deleteWhiteboardViaApi();
};

const joinWithCode = () => {
  joinWithCodeViaApi();
};

const handleCanvasError = (err) => {
  console.error('Whiteboard-Fehler:', err);
  toast.error(t('toast.errorPrefix') + (err.message || 'Unbekannter Fehler'));
};

// Formatierungshilfen
const formatDate = (dateString) => {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  return date.toLocaleDateString('de-DE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

// Tab wechseln
watch(activeTab, (newTab) => {
  if (newTab === 'my-boards') {
    fetchMyWhiteboards();
  } else if (newTab === 'public-boards') {
    fetchPublicWhiteboards();
  }
});

// Funktion zum Zurücksetzen des Formulars
const resetCreateForm = () => {
  newWhiteboard.value = {
    name: 'Neues Whiteboard',
    accessType: 'private',
    joinCode: '',
    backgroundColor: '#FFFFFF'
  };
};

// Öffnen des Erstellungsdialogs
const openCreateDialog = () => {
  resetCreateForm();
  showCreateDialog.value = true;
};

// Debug-Aktionen
const debugForceShowCanvas = () => {
  // Debug-Funktion deaktiviert
};

const debugResetState = () => {
  // Debug-Funktion deaktiviert
};

// Neuer Handler für Zurück-Button vom Canvas
const handleBackFromCanvas = () => {
  console.log('Zurück vom Canvas-View');
  selectedWhiteboardId.value = null;
  
  // Optional: Bei Rückkehr UI aktualisieren
  fetchMyWhiteboards();
};

// Reconnect-Funktion
const reconnectSocket = () => {
  console.log('Versuche Socket neu zu verbinden...');
  initSocket();
  
  // Nach kurzer Verzögerung prüfen, ob es funktioniert hat
  setTimeout(() => {
    if (mainSocket.value && socketConnected.value) {
      console.log('Socket erfolgreich neu verbunden:', mainSocket.value.id);
      showConnectionAlert.value = false;
      
      // Events neu registrieren
      registerSocketEvents();
      
      // Optional: Daten aktualisieren
      fetchMyWhiteboards();
    } else {
      console.error('Socket-Neuverbindung fehlgeschlagen');
      showConnectionAlert.value = true;
    }
  }, 1000);
};

// Für bessere Wartbarkeit, Events in eigener Funktion registrieren
const registerSocketEvents = () => {
  if (!mainSocket.value) {
    console.error('Cannot register events, socket is null');
    return;
  }
  
  console.log('Registriere Socket-Events');
  
  // Socket.io-Events registrieren  
  mainSocket.value.on('whiteboard:my-boards', handleMyWhiteboards);
  mainSocket.value.on('whiteboard:list', handlePublicWhiteboards);
  mainSocket.value.on('whiteboard:created', handleWhiteboardCreated);
  mainSocket.value.on('whiteboard:joined', handleWhiteboardJoined);
  mainSocket.value.on('whiteboard:error', handleWhiteboardError);
  mainSocket.value.on('whiteboard:deleted', handleWhiteboardDeleted);
};

// Funktion zum Überprüfen der Socket-Verbindung
const ensureSocketConnection = () => {
  if (!mainSocket.value || !socketConnected.value) {
    console.warn('Socket ist nicht verbunden, versuche Neuverbindung...');
    reconnectSocket();
    
    // Direktes Feedback geben
    if (!mainSocket.value || !socketConnected.value) {
      showConnectionAlert.value = true;
      return false;
    }
  }
  return true;
};

// Lifecycle hooks
onMounted(() => {
  // Socket-Verbindung initialisieren
  console.log('WhiteboardManager mounted, prüfe Socket-Verbindung');
  
  if (!mainSocket.value) {
    console.log('Socket ist null, initialisiere...');
    initSocket();
  }
  
  // Verbindungs-Events
  if (mainSocket.value) {
    console.log('Socket ist vorhanden:', mainSocket.value.id);
    
    mainSocket.value.on('connect', () => {
      console.log('Socket connect-Event: Manager mit Whiteboard-Namespace verbunden!');
      showConnectionAlert.value = false;
      // Nach erfolgreicher Verbindung Daten laden
      fetchMyWhiteboards();
    });
    
    mainSocket.value.on('disconnect', (reason) => {
      console.log('Socket disconnect-Event:', reason);
      showConnectionAlert.value = true;
    });
    
    mainSocket.value.on('connect_error', (error) => {
      console.error('Manager: Fehler bei der Verbindung zum Whiteboard-Namespace:', error);
      showConnectionAlert.value = true;
    });
    
    // Socket-Events registrieren
    registerSocketEvents();
  } else {
    console.error('Socket konnte nicht initialisiert werden.');
    showConnectionAlert.value = true;
  }
  
  // Initialen Zustand laden
  fetchMyWhiteboards();
});

onBeforeUnmount(() => {
  // Socket.io-Events deregistrieren
  if (mainSocket.value) {
    console.log('WhiteboardManager unmounted, deregistriere Events');
    mainSocket.value.off('whiteboard:my-boards');
    mainSocket.value.off('whiteboard:list');
    mainSocket.value.off('whiteboard:created');
    mainSocket.value.off('whiteboard:joined');
    mainSocket.value.off('whiteboard:error');
    mainSocket.value.off('whiteboard:deleted');
  }
});
</script>

<style scoped>
.whiteboard-manager {
  width: 100%;
  height: 100%;
  position: relative;
}

.manager-card {
  min-height: 500px;
  height: 100%;
  border-radius: 8px;
  overflow: hidden;
}

.manager-card.dark-mode {
  background-color: var(--k-surface);
  color: var(--k-ink);
}

.tab-content {
  padding: 16px 0;
}

.tab-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  color: #888;
  text-align: center;
}

.board-card {
  transition: transform 0.2s;
  height: 100%;
  cursor: pointer;
}

.board-card:hover {
  transform: translateY(-5px);
}

.board-title {
  display: flex;
  align-items: center;
  word-break: break-word;
}

.board-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #666;
}

.dark-mode .info-item {
  color: #aaa;
}

.join-code-container {
  max-width: 400px;
  margin: 20px auto;
}

.loader {
  display: block;
  margin: 40px auto;
}

/* Debug Controls */
.debug-controls {
  display: none; /* Ausgeblendet statt komplett entfernt */
}

.debug-info {
  display: none;
}

.debug-info pre {
  display: none;
}

.debug-actions {
  display: none;
}

.canvas-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 100;
}

.connection-alert {
  position: fixed;
  top: 5px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1000;
  width: auto;
  margin: 0 auto;
}
</style> 