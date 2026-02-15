import { ref } from 'vue';

interface NotificationState {
  visible: boolean;
  message: string;
  color: 'success' | 'error' | 'info' | 'warning';
  timeout?: number;
}

// Create a shared state for notifications
const notificationState = ref<NotificationState>({
  visible: false,
  message: '',
  color: 'info',
  timeout: 3000
});

export function useNotification() {
  // Show a success notification
  const showSuccess = (message: string, timeout = 3000) => {
    notificationState.value = {
      visible: true,
      message,
      color: 'success',
      timeout
    };
  };

  // Show an error notification
  const showError = (message: string, timeout = 5000) => {
    notificationState.value = {
      visible: true,
      message,
      color: 'error',
      timeout
    };
  };

  // Show a custom notification
  const showNotification = (options: NotificationState) => {
    notificationState.value = {
      ...notificationState.value,
      ...options,
      visible: true
    };
  };

  // Close the notification
  const closeNotification = () => {
    notificationState.value.visible = false;
  };

  return {
    notification: notificationState,
    showSuccess,
    showError,
    showNotification,
    closeNotification
  };
} 