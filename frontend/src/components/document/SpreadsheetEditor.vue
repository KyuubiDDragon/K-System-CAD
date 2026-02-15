<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { createUniver, LocaleType, mergeLocales, defaultTheme } from '@univerjs/presets';

// Import all preset packages
import { UniverSheetsCorePreset } from '@univerjs/preset-sheets-core';
import { UniverSheetsFilterPreset } from '@univerjs/preset-sheets-filter';
import { UniverSheetsSortPreset } from '@univerjs/preset-sheets-sort';
import { UniverSheetsDataValidationPreset } from '@univerjs/preset-sheets-data-validation';
import { UniverSheetsConditionalFormattingPreset } from '@univerjs/preset-sheets-conditional-formatting';
import { UniverSheetsFindReplacePreset } from '@univerjs/preset-sheets-find-replace';
import { UniverSheetsDrawingPreset } from '@univerjs/preset-sheets-drawing';
import { UniverSheetsThreadCommentPreset } from '@univerjs/preset-sheets-thread-comment';

// Import locale files
import UniverPresetSheetsCoreEnUS from '@univerjs/preset-sheets-core/locales/en-US';
import UniverPresetSheetsFilterEnUS from '@univerjs/preset-sheets-filter/locales/en-US';
import UniverPresetSheetsSortEnUS from '@univerjs/preset-sheets-sort/locales/en-US';
import UniverPresetSheetsDataValidationEnUS from '@univerjs/preset-sheets-data-validation/locales/en-US';
import UniverPresetSheetsConditionalFormattingEnUS from '@univerjs/preset-sheets-conditional-formatting/locales/en-US';
import UniverPresetSheetsFindReplaceEnUS from '@univerjs/preset-sheets-find-replace/locales/en-US';
import UniverPresetSheetsDrawingEnUS from '@univerjs/preset-sheets-drawing/locales/en-US';
import UniverPresetSheetsThreadCommentEnUS from '@univerjs/preset-sheets-thread-comment/locales/en-US';

// Import preset styles
import '@univerjs/preset-sheets-core/lib/index.css';
import '@univerjs/preset-sheets-filter/lib/index.css';
import '@univerjs/preset-sheets-sort/lib/index.css';
import '@univerjs/preset-sheets-data-validation/lib/index.css';
import '@univerjs/preset-sheets-conditional-formatting/lib/index.css';
import '@univerjs/preset-sheets-find-replace/lib/index.css';
import '@univerjs/preset-sheets-drawing/lib/index.css';
import '@univerjs/preset-sheets-thread-comment/lib/index.css';

const props = defineProps<{
  modelValue: any
  editable?: boolean
  title?: string
  darkMode?: boolean
  fullscreen?: boolean
}>();

const emit = defineEmits<{
  'update:modelValue': [value: any]
  'update:darkMode': [value: boolean]
  'update:fullscreen': [value: boolean]
}>();

const container = ref<HTMLElement>();
const isDarkMode = ref(props.darkMode ?? true);
const isFullscreen = ref(props.fullscreen ?? false);
let univer: any = null;
let univerAPI: any = null;

// Watch for dark mode changes
watch(() => props.darkMode, (newVal) => {
  if (newVal !== undefined) {
    isDarkMode.value = newVal;
    if (univerAPI) {
      univerAPI.toggleDarkMode(newVal);
    }
  }
});

watch(() => props.fullscreen, (newVal) => {
  if (newVal !== undefined) {
    isFullscreen.value = newVal;
  }
});

// Watch for modelValue changes (e.g., when importing XLSX)
// Note: We don't use { deep: true } here to avoid triggering on internal Univer changes
watch(() => props.modelValue, async (newVal, oldVal) => {
  // Only reload if the entire object reference changed (e.g., XLSX import)
  // and Univer is already initialized
  if (newVal && univerAPI && newVal !== oldVal) {
    // Reload the Univer instance with new data
    destroyUniver();
    // Wait for next tick to ensure cleanup is complete
    await nextTick();
    await initializeUniver();
  }
});

const initializeUniver = async () => {
  if (!container.value) {
    console.error('Univer: Container not available');
    return;
  }

  try {
    // Create Univer instance using the preset approach with all advanced features
    const result = createUniver({
      locale: LocaleType.EN_US,
      locales: {
        [LocaleType.EN_US]: mergeLocales(
          UniverPresetSheetsCoreEnUS,
          UniverPresetSheetsFilterEnUS,
          UniverPresetSheetsSortEnUS,
          UniverPresetSheetsDataValidationEnUS,
          UniverPresetSheetsConditionalFormattingEnUS,
          UniverPresetSheetsFindReplaceEnUS,
          UniverPresetSheetsDrawingEnUS,
          UniverPresetSheetsThreadCommentEnUS
        ),
      },
      theme: defaultTheme,
      darkMode: isDarkMode.value,
      presets: [
        UniverSheetsCorePreset({
          container: container.value,
        }),
        UniverSheetsFilterPreset(),
        UniverSheetsSortPreset(),
        UniverSheetsDataValidationPreset(),
        UniverSheetsConditionalFormattingPreset(),
        UniverSheetsFindReplacePreset(),
        UniverSheetsDrawingPreset(),
        UniverSheetsThreadCommentPreset(),
      ],
    });

    univer = result.univer;
    univerAPI = result.univerAPI;

    // Create workbook with initial data
    const workbookData = props.modelValue || {
      id: 'workbook-01',
      locale: LocaleType.EN_US,
      name: props.title || 'Spreadsheet',
      sheetOrder: ['sheet-01'],
      appVersion: '3.0.0-alpha',
      sheets: {
        'sheet-01': {
          id: 'sheet-01',
          name: 'Sheet1',
          cellData: {},
        },
      },
    };

    univerAPI.createUniverSheet(workbookData);
  } catch (error) {
    console.error('Univer initialization error:', error);
  }
};

const destroyUniver = () => {
  if (univer) {
    univer.dispose();
    univer = null;
    univerAPI = null;
  }
};

onMounted(() => {
  initializeUniver();
});

onBeforeUnmount(() => {
  destroyUniver();
});

// Export current data
const exportData = () => {
  if (univerAPI) {
    const workbook = univerAPI.getActiveWorkbook();
    return workbook?.save() || null;
  }
  return null;
};

// Expose exportData for parent components
defineExpose({
  exportData,
});
</script>

<template>
  <div :class="['spreadsheet-editor-wrapper', { 'fullscreen': isFullscreen }]">
    <div ref="container" class="spreadsheet-container"></div>
  </div>
</template>

<style scoped>
.spreadsheet-editor-wrapper {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  position: relative;
  border-radius: 8px;
}

.spreadsheet-editor-wrapper.fullscreen {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 9999;
  border-radius: 0;
  height: 100vh !important;
}

.spreadsheet-container {
  width: 100%;
  height: 100%;
  min-height: 500px;
  flex: 1;
  overflow: hidden;
  position: relative;
}
</style>
