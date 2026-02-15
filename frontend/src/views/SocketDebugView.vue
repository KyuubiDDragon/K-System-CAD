<template>
  <div class="socket-debug-view">
    <div class="debug-header">
      <h1>{{ $t('socketDebug.socketIoDiagnosticTool') }}</h1>
      <p>{{ $t('socketDebug.pageDescription') }}</p>
    </div>
    
    <div class="debug-content">
      <div class="connection-panel">
        <h2>{{ $t('socketDebug.connectionStatus') }}</h2>
        <div class="status-grid">
          <div v-for="(status, socket) in connectionStatus" :key="socket" 
               class="status-card" :class="getStatusClass(status)">
            <div class="status-name">{{ socket }}</div>
            <div class="status-value">{{ $t('socketDebug.status.' + status) }}</div>
            <div class="status-id">{{ getSocketId(socket) }}</div>
          </div>
        </div>
        
        <div class="action-buttons">
          <button @click="initializeConnections" class="action-btn">{{ $t('socketDebug.initializeConnections') }}</button>
          <button @click="testAllConnections" class="action-btn">{{ $t('socketDebug.testConnections') }}</button>
          <button @click="resetConnections" class="action-btn danger">{{ $t('socketDebug.resetAllConnections') }}</button>
        </div>
      </div>
      
      <div class="token-panel">
        <h2>{{ $t('socketDebug.authentication') }}</h2>
        <div class="token-details">
          <div class="detail-row">
            <span class="detail-label">{{ $t('socketDebug.token') }}:</span>
            <span class="detail-value" :class="{ 'text-success': hasToken, 'text-danger': !hasToken }">
              {{ tokenPreview }}
            </span>
          </div>
          <div class="detail-row" v-if="tokenSource">
            <span class="detail-label">{{ $t('socketDebug.source') }}:</span>
            <span class="detail-value">{{ tokenSource }}</span>
          </div>
          <div class="detail-row" v-if="isTokenValid !== null">
            <span class="detail-label">{{ $t('socketDebug.validity') }}:</span>
            <span class="detail-value" :class="{ 'text-success': isTokenValid, 'text-danger': !isTokenValid }">
              {{ isTokenValid ? $t('socketDebug.valid') : $t('socketDebug.invalid') }}
            </span>
          </div>
        </div>
        <div class="cookie-details" v-if="Object.keys(cookieStatus).length > 0">
          <h3>{{ $t('socketDebug.cookies') }}</h3>
          <div class="detail-row" v-for="(status, cookie) in cookieStatus" :key="cookie">
            <span class="detail-label">{{ cookie }}:</span>
            <span class="detail-value" :class="{ 'text-success': status === 'present' }">
              {{ status }}
            </span>
          </div>
        </div>
      </div>
      
      <div class="test-panel" v-if="Object.keys(testResults).length > 0">
        <h2>{{ $t('socketDebug.connectionTests') }}</h2>
        <div v-for="(result, socket) in testResults" :key="socket" 
             class="test-result" :class="getResultClass(result.status)">
          <div class="test-header">
            <span class="test-socket">{{ socket }}</span>
            <span class="test-status">{{ $t('socketDebug.status.' + result.status) }}</span>
          </div>
          <div class="test-message">{{ result.message }}</div>
          <div class="test-timestamp" v-if="result.timestamp">{{ formatTime(result.timestamp) }}</div>
        </div>
      </div>
      
      <div class="error-panel" v-if="Object.keys(lastErrors).length > 0">
        <h2>{{ $t('socketDebug.connectionErrors') }}</h2>
        <div v-for="(error, socket) in lastErrors" :key="socket" class="error-card">
          <div class="error-socket">{{ socket }}</div>
          <div class="error-message">{{ error.message }}</div>
          <div class="error-timestamp">{{ formatTime(error.timestamp) }}</div>
        </div>
      </div>
      
      <div class="network-panel" v-if="networkStatus">
        <h2>{{ $t('socketDebug.networkStatus') }}</h2>
        <div class="detail-row">
          <span class="detail-label">{{ $t('socketDebug.serverReachable') }}:</span>
          <span class="detail-value" :class="{ 'text-success': networkStatus.serverReachable, 'text-danger': !networkStatus.serverReachable }">
            {{ networkStatus.serverReachable ? 'Yes' : 'No' }}
          </span>
        </div>
        <div class="detail-row">
          <span class="detail-label">{{ $t('socketDebug.socketServerUrl') }}:</span>
          <span class="detail-value">{{ networkStatus.socketServerUrl }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">{{ $t('socketDebug.browserUrl') }}:</span>
          <span class="detail-value">{{ networkStatus.browserUrl }}</span>
        </div>
        <div class="action-buttons">
          <button @click="testCORS" class="action-btn">{{ $t('socketDebug.testCORS') }}</button>
          <button @click="checkNetwork" class="action-btn">{{ $t('socketDebug.checkNetwork') }}</button>
        </div>
      </div>
      
      <div class="environment-panel">
        <h2>{{ $t('socketDebug.environment') }}</h2>
        <div class="detail-row" v-for="(value, key) in environment" :key="key">
          <span class="detail-label">{{ key }}:</span>
          <span class="detail-value">{{ value || $t('socketDebug.notSet') }}</span>
        </div>
      </div>
      
      <div class="manual-test-panel">
        <h2>{{ $t('socketDebug.manualTesting') }}</h2>
        <p>{{ $t('socketDebug.manualTestingDescription') }}</p>
        <div class="code-block">
          <pre>// Show diagnostic UI
window.socketHelper.runDiagnostic()

// Test token retrieval
window.socketHelper.testToken()

// Test manual connection
window.socketHelper.testManualConnection()

// Reset and reconnect all sockets
window.socketHelper.resetAndReconnect()</pre>
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
import socketHelper from '@/plugins/socket-helper';

export default {
  name: 'SocketDebugView',
  
  setup() {
    const { t } = useI18n();
    const connectionStatus = ref({});
    const lastErrors = ref({});
    const testResults = ref({});
    const tokenInfo = ref({});
    const cookieStatus = ref({});
    const networkStatus = ref(null);
    const environment = ref({});
    const socketIds = ref({});
    const updateInterval = ref(null);
    const isTokenValid = ref(null);
    
    const hasToken = computed(() => !!tokenInfo.value.value);
    const tokenPreview = computed(() => tokenInfo.value.value || t('socketDebug.notFound'));
    const tokenSource = computed(() => tokenInfo.value.source);
    
    // Get socket ID by type
    const getSocketId = (type) => {
      return socketIds.value[type] || t('socketDebug.notConnected');
    };
    
    // Format timestamp
    const formatTime = (timestamp) => {
      if (!timestamp) return '';
      const date = new Date(timestamp);
      return date.toLocaleTimeString();
    };
    
    // Update status from diagnostic tool
    const updateDiagnostics = () => {
      const data = socketDiagnostic.getDiagnostics();
      connectionStatus.value = data.connectionState || {};
      lastErrors.value = data.lastErrors || {};
      testResults.value = data.testResults || {};
      tokenInfo.value = data.tokenInfo || {};
      cookieStatus.value = data.cookieStatus || {};
      networkStatus.value = data.networkStatus || null;
      environment.value = data.environment || {};
      socketIds.value = data.socketIds || {};
    };
    
    // Initialize connections
    const initializeConnections = () => {
      console.log('Initializing Socket.io connections...');
      initializeSockets();
      setTimeout(updateDiagnostics, 1000);
    };
    
    // Test all connections
    const testAllConnections = () => {
      console.log('Testing all Socket.io connections...');
      socketDiagnostic.testAllConnections().then(() => {
        updateDiagnostics();
      });
    };
    
    // Reset connections
    const resetConnections = () => {
      console.log('Resetting all Socket.io connections...');
      Object.values(socketDiagnostic.sockets).forEach(socket => {
        if (socket && typeof socket.disconnect === 'function') {
          socket.disconnect();
        }
      });
      
      // Clear socket references
      Object.keys(socketDiagnostic.sockets).forEach(key => {
        delete socketDiagnostic.sockets[key];
      });
      
      // Reinitialize after a brief delay
      setTimeout(() => {
        initializeConnections();
      }, 1000);
    };
    
    // Test CORS
    const testCORS = async () => {
      console.log('Testing CORS with Socket.io server...');
      const result = await socketHelper.testCORS();
      if (result.success) {
        console.log('CORS test successful:', result);
      } else {
        console.error('CORS test failed:', result);
      }
    };
    
    // Check network
    const checkNetwork = async () => {
      console.log('Checking network connectivity...');
      await socketDiagnostic.checkNetwork();
      updateDiagnostics();
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
      // Run diagnostic
      socketDiagnostic.runDiagnostic();
      
      // Update status
      updateDiagnostics();
      
      // Update status periodically
      updateInterval.value = setInterval(updateDiagnostics, 3000);
    });
    
    // Clean up on component unmount
    onBeforeUnmount(() => {
      if (updateInterval.value) {
        clearInterval(updateInterval.value);
      }
    });
    
    return {
      connectionStatus,
      lastErrors,
      testResults,
      tokenInfo,
      cookieStatus,
      networkStatus,
      environment,
      hasToken,
      tokenPreview,
      tokenSource,
      isTokenValid,
      initializeConnections,
      testAllConnections,
      resetConnections,
      getStatusClass,
      getResultClass,
      getSocketId,
      formatTime,
      testCORS,
      checkNetwork
    };
  }
}
</script>

<style scoped>
.socket-debug-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
}

.debug-header {
  margin-bottom: 30px;
  text-align: center;
}

.debug-header h1 {
  margin-bottom: 10px;
  font-size: 28px;
}

.debug-content {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.connection-panel, .token-panel, .test-panel, .error-panel, 
.network-panel, .environment-panel, .manual-test-panel {
  background: #f5f5f5;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

h2 {
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 18px;
  color: #333;
  border-bottom: 1px solid #ddd;
  padding-bottom: 8px;
}

h3 {
  margin-top: 15px;
  margin-bottom: 10px;
  font-size: 16px;
  color: #555;
}

.status-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 10px;
  margin-bottom: 20px;
}

.status-card {
  padding: 12px;
  border-radius: 6px;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.status-name {
  font-weight: bold;
  margin-bottom: 5px;
}

.status-value {
  font-size: 14px;
  margin-bottom: 5px;
}

.status-id {
  font-size: 12px;
  color: #666;
  word-break: break-all;
}

.status-connected {
  border-left: 4px solid #4caf50;
}

.status-connected .status-value {
  color: #4caf50;
}

.status-disconnected {
  border-left: 4px solid #9e9e9e;
}

.status-disconnected .status-value {
  color: #9e9e9e;
}

.status-reconnecting {
  border-left: 4px solid #ff9800;
}

.status-reconnecting .status-value {
  color: #ff9800;
}

.status-error {
  border-left: 4px solid #f44336;
}

.status-error .status-value {
  color: #f44336;
}

.status-unknown {
  border-left: 4px solid #9e9e9e;
}

.action-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.action-btn {
  padding: 8px 15px;
  background: #2196f3;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  flex: 1;
}

.action-btn:hover {
  background: #1976d2;
}

.action-btn.danger {
  background: #f44336;
}

.action-btn.danger:hover {
  background: #d32f2f;
}

.token-details, .cookie-details {
  background: white;
  padding: 15px;
  border-radius: 6px;
  margin-bottom: 15px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.detail-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.detail-label {
  font-weight: bold;
  color: #555;
}

.detail-value {
  word-break: break-all;
}

.text-success {
  color: #4caf50;
}

.text-danger {
  color: #f44336;
}

.test-result {
  background: white;
  padding: 15px;
  border-radius: 6px;
  margin-bottom: 10px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.result-success {
  border-left: 4px solid #4caf50;
}

.result-timeout {
  border-left: 4px solid #ff9800;
}

.result-error {
  border-left: 4px solid #f44336;
}

.test-header {
  display: flex;
  justify-content: space-between;
  font-weight: bold;
  margin-bottom: 5px;
}

.test-socket {
  color: #333;
}

.result-success .test-status {
  color: #4caf50;
}

.result-timeout .test-status {
  color: #ff9800;
}

.result-error .test-status {
  color: #f44336;
}

.test-message {
  margin-bottom: 5px;
  color: #555;
}

.test-timestamp, .error-timestamp {
  font-size: 12px;
  color: #777;
}

.error-card {
  background: white;
  padding: 15px;
  border-radius: 6px;
  margin-bottom: 10px;
  border-left: 4px solid #f44336;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.error-socket {
  font-weight: bold;
  color: #f44336;
  margin-bottom: 5px;
}

.error-message {
  margin-bottom: 5px;
  color: #333;
  word-break: break-all;
}

.code-block {
  background: #272822;
  color: #f8f8f2;
  padding: 15px;
  border-radius: 6px;
  overflow-x: auto;
  font-family: 'Consolas', 'Monaco', monospace;
  font-size: 14px;
  line-height: 1.5;
}

.code-block pre {
  margin: 0;
}

@media (max-width: 768px) {
  .debug-content {
    grid-template-columns: 1fr;
  }
  
  .status-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  }
}
</style> 