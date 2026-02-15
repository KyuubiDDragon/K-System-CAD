import { ref, onMounted, onUnmounted } from 'vue'
import { io, Socket } from 'socket.io-client'
import { useMailStore } from '@/stores/mail'
import { useRouter } from 'vue-router'

let socket: Socket | null = null

/**
 * Mail Socket Composable
 * Manages WebSocket connection for real-time mail notifications
 */
export function useMailSocket() {
  const mailStore = useMailStore()
  const router = useRouter()
  const connected = ref(false)

  /**
   * Connect to mail socket namespace
   */
  function connect() {
    if (socket?.connected) {
      console.log('Mail socket already connected')
      return // Already connected
    }

    const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
    if (!token) {
      console.warn('No token found, cannot connect to mail socket')
      return
    }

    const socketUrl = import.meta.env.VITE_SOCKET_URL || 'http://localhost:3001'

    console.log('Connecting to mail socket:', `${socketUrl}/mail`)

    socket = io(`${socketUrl}/mail`, {
      auth: { token },
      transports: ['websocket', 'polling']
    })

    // Connection events
    socket.on('connect', () => {
      console.log('✅ Mail socket connected:', socket?.id)
      connected.value = true
    })

    socket.on('disconnect', (reason) => {
      console.log('❌ Mail socket disconnected:', reason)
      connected.value = false
    })

    socket.on('connect_error', (error) => {
      console.error('Mail socket connection error:', error.message)
      connected.value = false
    })

    socket.on('mail:connected', (data) => {
      console.log('📧 Mail socket namespace connected:', data)
    })

    // Handle new mail event
    socket.on('new_mail', (data) => {
      console.log('📨 New mail received:', data)

      // Show desktop notification
      showDesktopNotification({
        title: `Neue E-Mail von ${data.from_address}`,
        body: `${data.subject}\n${data.preview}`,
        icon: '/favicon.ico',
        tag: `mail-${data.mail_id}`
      })

      // Play notification sound
      playNotificationSound()

      // Update unread count
      mailStore.unreadCount++

      // Refresh inbox if currently viewing
      if (mailStore.currentFolder === 'inbox') {
        mailStore.fetchInbox()
      }

      // Show toast notification (optional - if you have a toast library)
      // Could integrate with vue-toastification or similar
    })

    // Handle mail read event (sync across tabs)
    socket.on('mail_read', (data) => {
      console.log('📖 Mail marked as read:', data)
      const mail = mailStore.mails.find(m => m.id === data.mail_id)
      if (mail) {
        mail.is_read = true
        mail.read_at = data.timestamp
        mailStore.unreadCount = Math.max(0, mailStore.unreadCount - 1)
      }
    })

    // Handle mail starred event (sync across tabs)
    socket.on('mail_starred', (data) => {
      console.log('⭐ Mail starred status changed:', data)
      const mail = mailStore.mails.find(m => m.id === data.mail_id)
      if (mail) {
        mail.is_starred = data.is_starred
      }
    })

    // Handle mailbox activity
    socket.on('mailbox_activity', (data) => {
      console.log('📬 Mailbox activity:', data)
      // Could show a toast notification for mailbox activity
      // toast.info(`Postfach "${data.mailbox_name}": ${data.message}`)
    })
  }

  /**
   * Disconnect from mail socket
   */
  function disconnect() {
    if (socket) {
      console.log('Disconnecting mail socket')
      socket.disconnect()
      socket = null
      connected.value = false
    }
  }

  /**
   * Emit event to socket server
   */
  function emit(event: string, data: any) {
    if (socket?.connected) {
      socket.emit(event, data)
    } else {
      console.warn('Cannot emit, mail socket not connected')
    }
  }

  /**
   * Notify other tabs that mail was marked as read
   */
  function notifyMailRead(mailId: number) {
    emit('mark_as_read', { mail_id: mailId })
  }

  /**
   * Notify other tabs that mail was starred/unstarred
   */
  function notifyMailStarred(mailId: number, isStarred: boolean) {
    emit('mail_starred', { mail_id: mailId, is_starred: isStarred })
  }

  // Lifecycle hooks
  onMounted(() => {
    connect()
  })

  onUnmounted(() => {
    disconnect()
  })

  return {
    connected,
    connect,
    disconnect,
    emit,
    notifyMailRead,
    notifyMailStarred
  }
}

/**
 * Show desktop notification
 */
function showDesktopNotification(options: {
  title: string
  body: string
  icon?: string
  tag?: string
}) {
  if (!('Notification' in window)) {
    console.warn('Browser does not support notifications')
    return
  }

  if (Notification.permission === 'granted') {
    try {
      new Notification(options.title, {
        body: options.body,
        icon: options.icon || '/favicon.ico',
        tag: options.tag,
        requireInteraction: false,
        badge: options.icon
      })
    } catch (error) {
      console.error('Failed to show notification:', error)
    }
  } else if (Notification.permission !== 'denied') {
    Notification.requestPermission().then(permission => {
      if (permission === 'granted') {
        try {
          new Notification(options.title, options)
        } catch (error) {
          console.error('Failed to show notification:', error)
        }
      }
    })
  }
}

/**
 * Play notification sound
 */
function playNotificationSound() {
  try {
    const audio = new Audio('/sounds/mail-notification.mp3')
    audio.volume = 0.5
    audio.play().catch(err => {
      console.log('Audio play failed (user interaction may be required):', err.message)
    })
  } catch (error) {
    console.error('Failed to play notification sound:', error)
  }
}

/**
 * Request notification permission
 */
export function requestNotificationPermission() {
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission().then(permission => {
      console.log('Notification permission:', permission)
    })
  }
}
