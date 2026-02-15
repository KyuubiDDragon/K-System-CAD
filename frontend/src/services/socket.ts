import { io, Socket } from 'socket.io-client';
import { ref } from 'vue';

class SocketService {
  private socket: Socket | null = null;
  private connected = ref(false);
  private connectionError = ref<string | null>(null);

  connect(url: string = import.meta.env.VITE_SOCKET_URL || 'http://localhost:3001') {
    this.socket = io(url, {
      transports: ['websocket'],
      withCredentials: true,
      autoConnect: true
    });

    this.socket.on('connect', () => {
      console.log('Socket.io connected!');
      this.connected.value = true;
      this.connectionError.value = null;
    });

    this.socket.on('disconnect', (reason) => {
      console.log('Socket.io disconnected:', reason);
      this.connected.value = false;
    });

    this.socket.on('connect_error', (error) => {
      console.error('Socket.io connection error:', error);
      this.connectionError.value = error.message;
    });

    return this.socket;
  }

  disconnect() {
    if (this.socket) {
      this.socket.disconnect();
      this.socket = null;
    }
  }

  emit(event: string, data?: any) {
    if (!this.socket) {
      console.warn('Socket not connected, cannot emit event:', event);
      return;
    }
    this.socket.emit(event, data);
  }

  on(event: string, callback: (...args: any[]) => void) {
    if (!this.socket) {
      console.warn('Socket not connected, cannot listen to event:', event);
      return;
    }
    this.socket.on(event, callback);
  }

  off(event: string) {
    if (!this.socket) return;
    this.socket.off(event);
  }

  isConnected() {
    return this.connected.value;
  }

  getConnectionError() {
    return this.connectionError.value;
  }
}

// Singleton-Instanz exportieren
export const socketService = new SocketService();