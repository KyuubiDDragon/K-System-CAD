<template>
  <div class="whiteboard-app">
    <div v-if="visible" class="whiteboard-container">
      <whiteboard-manager />
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';
import WhiteboardManager from '@/components/whiteboard/WhiteboardManager.vue';

export default {
  name: 'WhiteboardApp',
  components: {
    WhiteboardManager
  },
  props: {
    visible: {
      type: Boolean,
      default: false
    }
  },
  
  setup(props) {
    onMounted(() => {
      if (props.visible) {
        console.log('Whiteboard App gestartet');
      }
    });

    onUnmounted(() => {
      console.log('Whiteboard App geschlossen');
    });
  }
};
</script>

<style scoped>
.whiteboard-app {
  width: 100%;
  height: 100%;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.whiteboard-container {
  flex: 1;
  width: 100%;
  height: 100%;
}
</style> 