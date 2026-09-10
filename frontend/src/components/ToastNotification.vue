<template>
  <div class="toast-notification" :class="typeClass">
    <div class="toast-header">
      <div class="toast-title">{{ computedTitle }}</div>
      <div class="toast-time">{{ formattedTime }}</div>
    </div>
    
    <div class="toast-body">
      <div v-if="sender" class="toast-sender">{{ sender }}</div>
      <div class="toast-message">{{ message }}</div>
      <div v-if="content" class="toast-content">{{ content }}</div>
    </div>
    
    <div v-if="link" class="toast-footer">
      <button @click="handleLink" class="toast-link-button">{{ t('notifications.showMore') }}</button>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';

export default {
  name: 'ToastNotification',
  
  props: {
    title: {
      type: String,
      default: ''
    },
    message: {
      type: String,
      required: true
    },
    content: {
      type: String,
      default: ''
    },
    timestamp: {
      type: String,
      default: () => new Date().toISOString()
    },
    sender: {
      type: String,
      default: ''
    },
    type: {
      type: String,
      default: 'info',
      validator: (value) => ['info', 'success', 'warning', 'error', 'message', 'broadcast'].includes(value)
    },
    link: {
      type: [String, Object],
      default: null
    }
  },
  
  setup(props) {
    const router = useRouter();
    const { t } = useI18n();
    
    const typeClass = computed(() => {
      return {
        'toast-info': props.type === 'info',
        'toast-success': props.type === 'success',
        'toast-warning': props.type === 'warning',
        'toast-error': props.type === 'error',
        'toast-message': props.type === 'message' || props.type === 'room_message',
        'toast-broadcast': props.type === 'broadcast'
      };
    });
    
    const formattedTime = computed(() => {
      try {
        const date = new Date(props.timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      } catch (e) {
        return '';
      }
    });
    
    const handleLink = () => {
      if (typeof props.link === 'string') {
        // Externe URL
        if (props.link.startsWith('http')) {
          window.open(props.link, '_blank');
        } else {
          // Interne Route
          router.push(props.link);
        }
      } else if (props.link && typeof props.link === 'object') {
        // Route-Objekt
        router.push(props.link);
      }
    };

    const computedTitle = computed(() => props.title || t('notifications.defaultTitle'));

    return {
      typeClass,
      formattedTime,
      handleLink,
      computedTitle,
      t
    };
  }
};
</script>

<style scoped>
.toast-notification {
  min-width: 300px;
  max-width: 450px;
  background-color: var(--k-surface);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  font-family: var(--font-family, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif);
}

.toast-notification.toast-info {
  border-left: 4px solid var(--k-accent-line);
}

.toast-notification.toast-success {
  border-left: 4px solid var(--k-success);
}

.toast-notification.toast-warning {
  border-left: 4px solid var(--k-warning);
}

.toast-notification.toast-error {
  border-left: 4px solid var(--k-critical);
}

.toast-notification.toast-message {
  border-left: 4px solid #7E57C2;
}

.toast-notification.toast-broadcast {
  border-left: 4px solid #E91E63;
}

.toast-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  font-weight: 500;
}

.toast-title {
  font-weight: 600;
  color: #333;
}

.toast-time {
  font-size: 0.85em;
  color: #777;
}

.toast-body {
  padding: 16px;
  color: #333;
}

.toast-sender {
  font-weight: 600;
  margin-bottom: 4px;
  color: #555;
}

.toast-message {
  margin-bottom: 8px;
}

.toast-content {
  font-size: 0.9em;
  color: #666;
  background: rgba(0, 0, 0, 0.03);
  padding: 8px;
  border-radius: 4px;
  margin-top: 8px;
}

.toast-footer {
  display: flex;
  justify-content: flex-end;
  padding: 0 16px 16px;
}

.toast-link-button {
  background-color: transparent;
  color: var(--primary-color, var(--k-accent));
  border: none;
  padding: 6px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.toast-link-button:hover {
  background-color: rgba(25, 118, 210, 0.08);
}
</style> 