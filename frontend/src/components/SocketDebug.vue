<template>
  <div class="socket-debug" :class="{ 'is-collapsed': collapsed }">
    <div class="socket-debug-header" @click="toggleCollapse">
      <h3>{{ $t('socketDebug.panelTitle') }}</h3>
      <button class="toggle-btn">{{ collapsed ? '▼' : '▲' }}</button>
    </div>
    
    <div v-if="!collapsed" class="socket-debug-content">
      <div class="socket-status">
        <h4>{{ $t('socketDebug.connectionStatus') }}</h4>
        <div v-for="(status, socket) in connectionStatus" :key="socket"
             class="status-item" :class="getStatusClass(status)">
          {{ socket }}: {{ $t('socketDebug.status.' + status) }}
        </div>
      </div>
      
      <div class="socket-actions">
        <button @click="runDiagnostic" class="debug-btn">{{ $t('socketDebug.runDiagnostic') }}</button>
        <button @click="testConnection" class="debug-btn">{{ $t('socketDebug.testConnection') }}</button>
        <button @click="resetConnection" class="debug-btn warning">{{ $t('socketDebug.resetConnections') }}</button>
      </div>
      
      <div class="token-info">
        <h4>{{ $t('socketDebug.authentication') }}</h4>
        <div class="token-status" :class="{ 'has-token': hasToken }">
          {{ $t('socketDebug.token') }}: {{ tokenPreview }}
        </div>
        <div v-if="tokenSource" class="token-source">
          {{ $t('socketDebug.source') }}: {{ tokenSource }}
        </div>
      </div>
      
      <div v-if="Object.keys(testResults).length > 0" class="test-results">
        <h4>{{ $t('socketDebug.testResults') }}</h4>
        <div v-for="(result, socket) in testResults" :key="socket"
             class="result-item" :class="getResultClass(result.status)">
          <div class="result-header">
            {{ socket }}: {{ $t('socketDebug.status.' + result.status) }}
          </div>
          <div class="result-message">{{ result.message }}</div>
        </div>
      </div>
      
      <div v-if="Object.keys(lastErrors).length > 0" class="errors">
        <h4>{{ $t('socketDebug.lastErrors') }}</h4>
        <div v-for="(error, socket) in lastErrors" :key="socket" class="error-item">
          <div class="error-socket">{{ socket }}:</div>
          <div class="error-message">{{ error.message }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import socketDiagnostic from '@/plugins/socket-diagnostic';
import { initializeSockets } from '@/plugins/socket';

export default {
  name: 'SocketDebug',
  
  setup() {
    const { t } = useI18n();
    const collapsed = ref(false);
    const connectionStatus = ref({});
    const lastErrors = ref({});
    const testResults = ref({});
    const tokenInfo = ref({ value: null, source: null });
    const updateInterval = ref(null);
    
    const hasToken = computed(() => !!tokenInfo.value.value);
    const tokenPreview = computed(() => tokenInfo.value.value || t('socketDebug.notFound'));
    const tokenSource = computed(() => tokenInfo.value.source);
    
    // Update status from diagnostic tool
    const updateStatus = () => {
      const data = socketDiagnostic.getDiagnostics();
      connectionStatus.value = data.connectionState || {};
      lastErrors.value = data.lastErrors || {};
      testResults.value = data.testResults || {};
      tokenInfo.value = data.tokenInfo || { value: null, source: null };
    };
    
    // Toggle collapsed state
    const toggleCollapse = () => {
      collapsed.value = !collapsed.value;
    };
    
    // Run diagnostic
    const runDiagnostic = () => {
      socketDiagnostic.runDiagnostic();
      updateStatus();
    };
    
    // Test connection
    const testConnection = () => {
      socketDiagnostic.testAllConnections().then(() => {
        updateStatus();
      });
    };
    
    // Reset connections
    const resetConnection = () => {
      // First disconnect all sockets
      Object.values(socketDiagnostic.sockets).forEach(socket => {
        if (socket && typeof socket.disconnect === 'function') {
          try {
            socket.disconnect();
          } catch (e) {
            console.error('Error disconnecting socket:', e);
          }
        }
      });
      
      // Then reinitialize
      setTimeout(() => {
        initializeSockets();
        updateStatus();
      }, 500);
    };
    
    // Get CSS class based on connection status
    const getStatusClass = (status) => {
      switch(status) {
        case 'connected': return 'status-connected';
        case 'disconnected': return 'status-disconnected';
        case 'reconnecting': return 'status-reconnecting';
        case 'error': 
        case 'failed': return 'status-error';
        default: return 'status-unknown';
      }
    };
    
    // Get CSS class based on test result status
    const getResultClass = (status) => {
      switch(status) {
        case 'success': return 'result-success';
        case 'timeout': return 'result-timeout';
        case 'error':
        case 'failed': return 'result-error';
        default: return 'result-unknown';
      }
    };
    
    // Initialize on component mount
    onMounted(() => {
      updateStatus();
      
      // Update status periodically
      updateInterval.value = setInterval(updateStatus, 5000);
    });
    
    // Clean up on component unmount
    onBeforeUnmount(() => {
      if (updateInterval.value) {
        clearInterval(updateInterval.value);
      }
    });
    
    return {
      collapsed,
      connectionStatus,
      lastErrors,
      testResults,
      hasToken,
      tokenPreview,
      tokenSource,
      toggleCollapse,
      runDiagnostic,
      testConnection,
      resetConnection,
      getStatusClass,
      getResultClass
    };
  }
}
</script>

<style scoped>
.socket-debug {
  position: fixed;
  bottom: 0;
  right: 20px;
  width: 320px;
  background: rgba(33, 33, 33, 0.9);
  color: #fff;
  border-radius: 8px 8px 0 0;
  z-index: 9900;
  box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
  font-family: monospace;
  font-size: 12px;
  transition: all 0.3s ease;
}

.socket-debug-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 15px;
  background: #333;
  border-radius: 8px 8px 0 0;
  cursor: pointer;
}

.socket-debug-header h3 {
  margin: 0;
  font-size: 14px;
  font-weight: normal;
}

.toggle-btn {
  background: none;
  border: none;
  color: #fff;
  cursor: pointer;
  font-size: 16px;
}

.socket-debug-content {
  padding: 15px;
  max-height: 400px;
  overflow-y: auto;
}

.socket-status, .token-info, .test-results, .errors {
  margin-bottom: 15px;
}

h4 {
  margin: 0 0 8px 0;
  font-size: 13px;
  color: #aaa;
  border-bottom: 1px solid #444;
  padding-bottom: 4px;
}

.status-item, .result-item, .error-item, .token-status {
  margin-bottom: 3px;
  padding: 3px 6px;
  border-radius: 3px;
  background: rgba(255, 255, 255, 0.1);
}

.status-connected {
  color: #5F5;
}

.status-reconnecting {
  color: orange;
}

.status-disconnected {
  color: #aaa;
}

.status-error {
  color: #F55;
}

.status-unknown {
  color: #aaa;
}

.token-status {
  color: #F55;
}

.token-status.has-token {
  color: #5F5;
}

.token-source {
  font-size: 11px;
  color: #aaa;
  margin-top: 3px;
}

.socket-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin: 15px 0;
}

.debug-btn {
  background: #444;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
  flex: 1;
  min-width: 80px;
}

.debug-btn:hover {
  background: #555;
}

.debug-btn.warning {
  background: #5a3434;
}

.debug-btn.warning:hover {
  background: #6a4040;
}

.result-header {
  display: flex;
  justify-content: space-between;
  font-weight: bold;
}

.result-success {
  color: #5F5;
}

.result-timeout {
  color: orange;
}

.result-error {
  color: #F55;
}

.result-message {
  font-size: 11px;
  margin-top: 3px;
  color: #ddd;
}

.error-socket {
  color: #F55;
  margin-bottom: 2px;
}

.error-message {
  font-size: 11px;
  color: #FCC;
  word-break: break-all;
}

/* Collapsed state */
.socket-debug.is-collapsed {
  width: 180px;
}
</style> 