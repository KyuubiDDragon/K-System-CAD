<template>
  <v-card class="data-table-card" elevation="3">
    <v-card-title v-if="title" class="px-4 py-3 table-card-title">
      <v-icon v-if="icon" :icon="icon" size="20" class="mr-2"></v-icon>
      {{ title }}
      <v-spacer></v-spacer>
      <slot name="title-actions"></slot>
    </v-card-title>
    
    <v-data-table
      :headers="headers"
      :items="items"
      :item-value="itemValue"
      :loading="loading"
      :hover="hover"
      :density="density"
      :items-per-page="itemsPerPage"
      :items-per-page-options="itemsPerPageOptions"
      class="data-table"
      v-bind="$attrs"
    >
      <template v-for="(_, slotName) in $slots" #[slotName]="slotData">
        <slot :name="slotName" v-bind="slotData" />
      </template>
      
      <template v-slot:loader>
        <div class="d-flex align-center justify-center pa-4">
          <v-progress-circular indeterminate color="primary" size="32"></v-progress-circular>
          <span class="ml-4">{{ loadingText }}</span>
        </div>
      </template>
      
      <template v-slot:no-data>
        <div class="empty-state pa-6">
          <v-icon :icon="emptyIcon" size="48" color="grey-darken-1" class="mb-4"></v-icon>
          <span>{{ emptyText }}</span>
          <v-btn
            v-if="showEmptyAction"
            color="primary"
            variant="tonal"
            class="mt-4"
            :prepend-icon="emptyActionIcon"
            @click="$emit('empty-action')"
          >
            {{ emptyActionText }}
          </v-btn>
        </div>
      </template>
    </v-data-table>
  </v-card>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
interface Props {
  title?: string
  icon?: string
  headers?: any[]
  items?: any[]
  itemValue?: string
  loading?: boolean
  loadingText?: string
  hover?: boolean
  density?: string
  emptyText?: string
  emptyIcon?: string
  showEmptyAction?: boolean
  emptyActionText?: string
  emptyActionIcon?: string
  itemsPerPage?: number
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  icon: '',
  itemValue: 'id',
  loading: false,
  loadingText: () => t('dataTableCard.loading'),
  hover: true,
  density: 'comfortable',
  emptyText: () => t('dataTableCard.empty'),
  emptyIcon: 'mdi-alert-circle-outline',
  showEmptyAction: false,
  emptyActionText: () => t('dataTableCard.add'),
  emptyActionIcon: 'mdi-plus',
  itemsPerPage: 10
});

defineEmits(['empty-action']);
</script>

<style scoped>
.data-table-card {
  background: var(--card-bg) !important;
  border: 1px solid var(--card-border);
  backdrop-filter: blur(var(--glass-blur));
  border-radius: var(--border-radius-md);
  overflow: hidden;
}

.table-card-title {
  border-bottom: 1px solid var(--card-border);
  font-weight: 500;
}

.data-table {
  width: 100%;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 16px;
  color: var(--desktop-text-tertiary);
  text-align: center;
}
</style> 