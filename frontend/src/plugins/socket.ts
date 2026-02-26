/**
 * Socket.io Plugin
 * Provides socket functionality for real-time updates
 */

import { socketService } from '@/services/socket';

// Socket instances cache
let mainSocket: any = null;

/**
 * Initialize socket connections
 */
export function initializeSockets() {
    if (mainSocket) {
        return { main: mainSocket };
    }

    mainSocket = socketService.connect();

    // Make socket available globally for debugging
    if (typeof window !== 'undefined') {
        (window as any).socketMain = mainSocket;
    }

    return { main: mainSocket };
}

/**
 * Disconnect all sockets
 */
export function disconnectSockets() {
    socketService.disconnect();
    mainSocket = null;

    if (typeof window !== 'undefined') {
        (window as any).socketMain = null;
    }
}

/**
 * Get current socket connection status
 */
export async function getSocketStatus() {
    return {
        connected: socketService.isConnected(),
        error: socketService.getConnectionError(),
        rooms: [] // Room tracking would need to be implemented in socketService
    };
}

/**
 * Reconnect sockets
 */
export async function reconnectSockets() {
    disconnectSockets();
    await new Promise(resolve => setTimeout(resolve, 500));
    return initializeSockets();
}

/**
 * Send dispatch update via socket
 */
export function sendDispatchUpdate(updateType: string, data: Record<string, any>): boolean {
    if (!mainSocket || !socketService.isConnected()) {
        console.warn('Socket not connected, cannot send dispatch update');
        return false;
    }

    socketService.emit('dispatch:update', {
        type: updateType,
        data,
        timestamp: Date.now(),
        senderSocketId: mainSocket.id
    });

    return true;
}

export default {
    initializeSockets,
    disconnectSockets,
    getSocketStatus,
    reconnectSockets,
    sendDispatchUpdate
};
