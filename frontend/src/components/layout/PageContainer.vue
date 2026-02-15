<template>
  <div class="page-container">
    <v-container fluid :class="containerClass" :style="containerStyle">
      <!-- Header Section -->
      <div class="section-header" v-if="title || showHeader">
        <div class="d-flex align-center">
          <v-icon v-if="icon" :icon="icon" size="24" class="mr-2 text-primary"></v-icon>
          <h1 class="text-h5 font-weight-medium mb-0">{{ title }}</h1>
          <slot name="header-title-after"></slot>
        </div>
        <slot name="header-actions">
          <v-btn
            v-if="showAddButton"
            @click="$emit('add')"
            color="primary"
            variant="elevated"
            :prepend-icon="addButtonIcon"
            size="small"
            class="ml-auto"
          >
            {{ addButtonText }}
          </v-btn>
        </slot>
      </div>
      
      <!-- Main Content -->
      <slot></slot>
      
      <!-- Footer -->
      <slot name="footer"></slot>
    </v-container>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  title?: string
  icon?: string
  showHeader?: boolean
  showAddButton?: boolean
  addButtonText?: string
  addButtonIcon?: string
  containerClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  icon: '',
  showHeader: true,
  showAddButton: false,
  addButtonText: 'Hinzufügen',
  addButtonIcon: 'mdi-plus',
  containerClass: ''
});

const containerStyle = computed(() => {
  const styles: Record<string, string> = {};
  
  if (typeof props.padding === 'number') {
    styles.padding = `${props.padding * 4}px`;
  } else if (props.padding) {
    styles.padding = props.padding;
  }
  
  if (props.customBackground) {
    styles.background = props.customBackground;
  }
  
  return styles;
});

defineEmits(['add']);
</script>

<style scoped>
.page-container {
  min-height: 90vh;
  background-color: var(--background);
  background-image: 
    radial-gradient(circle at 20% 30%, rgba(var(--primary), 0.05) 0%, transparent 25%),
    radial-gradient(circle at 85% 85%, rgba(var(--primary), 0.05) 0%, transparent 35%);
  position: relative;
  color: var(--on-background);
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 16px;
  margin-bottom: 24px;
  border-bottom: 1px solid var(--card-border);
}
</style> 