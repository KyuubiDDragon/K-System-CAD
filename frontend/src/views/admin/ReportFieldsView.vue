<template>
  <div class="report-fields-view">
    <h1 class="text-h4 mb-4">{{t('admin.manageReportFields')}}</h1>
    <p class="mb-6">
      {{t('admin.reportFieldsIntro')}}
    </p>
    
    <ReportFieldManager />
  </div>
</template>

<script setup lang="ts">
import ReportFieldManager from '@/components/Admin/ReportFieldManager.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<style scoped>
.report-fields-view {
  padding: 16px;
}
</style> 