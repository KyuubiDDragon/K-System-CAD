import { ref, onMounted, onUnmounted } from 'vue';
import { io, Socket } from 'socket.io-client';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';

const socket = ref<Socket | null>(null);
let connectionAttempts = 0;
const MAX_RECONNECT_ATTEMPTS = 5;
const RECONNECT_INTERVAL = 3000; // 3 Sekunden

export function useSocketIO() {
  const toast = useToast();
  const { t } = useI18n();
  const connected = ref(false);
  const connecting = ref(false);
  
  const initSocket = () => {
    if (socket.value) return;
    
    connecting.value = true;
    
    // Socket.io-Verbindung initialisieren
    const socketUrl = import.meta.env.VITE_SOCKET_SERVER_URL || 'http://localhost:3001';
    
    // Token aus dem Session Storage holen (angenommen, es wird dort bei der Anmeldung gespeichert)
    const token = sessionStorage.getItem('user_token') || localStorage.getItem('user_token');
    
    socket.value = io(socketUrl, {
      auth: {
        token
      },
      reconnection: true,
      reconnectionAttempts: MAX_RECONNECT_ATTEMPTS,
      reconnectionDelay: RECONNECT_INTERVAL,
      timeout: 10000
    });
    
    // Connection events
    socket.value.on('connect', () => {
      console.log('Socket.io connected');
      connected.value = true;
      connecting.value = false;
      connectionAttempts = 0;
    });
    
    socket.value.on('connect_error', (error) => {
      console.error('Socket.io connection error:', error);
      connecting.value = false;
      connected.value = false;
      
      connectionAttempts++;
      
      if (connectionAttempts >= MAX_RECONNECT_ATTEMPTS) {
        toast.error(t('toast.serverConnectionFailed'));
      }
    });
    
    socket.value.on('disconnect', (reason) => {
      console.log('Socket.io disconnected:', reason);
      connected.value = false;
      
      if (reason === 'io server disconnect') {
        // Der Server hat die Verbindung absichtlich getrennt
        toast.error(t('toast.serverDisconnected'));
      }
    });
    
    // Authentifizierungsbestätigung
    socket.value.on('user:authenticated', (data) => {
      console.log('Socket.io authenticated:', data);
    });
    
    // Verbindung bestätigt
    socket.value.on('connection:established', (data) => {
      console.log('Socket.io connection established:', data);
    });

    // ✅ Permission Refresh Event - triggered when admin changes user roles/permissions
    socket.value.on('permissions:refresh', async (data) => {
      console.log('🔄 [Socket.io] Permission refresh event received:', data);

      const authStore = useAuthStore();

      // Show toast notification to user
      toast.info(t('toast.permissionsUpdated') || 'Your permissions have been updated', {
        timeout: 5000,
        icon: '🔐'
      });

      // Trigger token refresh to get fresh permissions from database
      try {
        await authStore.refreshToken();
        console.log('✅ [Socket.io] Permissions refreshed successfully');
      } catch (error) {
        console.error('❌ [Socket.io] Failed to refresh permissions:', error);
        toast.error(t('toast.permissionsRefreshFailed') || 'Failed to update permissions. Please reload the page.');
      }
    });
  };
  
  const closeSocket = () => {
    if (socket.value) {
      socket.value.disconnect();
      socket.value = null;
      connected.value = false;
    }
  };
  
  // Lifecycle hooks
  onMounted(() => {
    initSocket();
  });
  
  onUnmounted(() => {
    closeSocket();
  });
  
  return {
    socket,
    connected,
    connecting,
    initSocket,
    closeSocket
  };
}