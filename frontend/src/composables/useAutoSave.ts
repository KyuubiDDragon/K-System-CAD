import { ref, watch, type Ref } from 'vue';

/**
 * Options for the auto-save composable
 */
export interface AutoSaveOptions {
  /**
   * Delay in milliseconds before auto-saving (default: 30000 = 30 seconds)
   */
  delay?: number;
  /**
   * Whether auto-save is enabled (default: true)
   */
  enabled?: boolean;
}

/**
 * Composable for auto-saving data with debouncing
 * Provides visual feedback for save status
 *
 * @param data - Reactive data to watch for changes
 * @param saveFunction - Async function to call when saving
 * @param options - Configuration options
 * @returns Object with auto-save controls and state
 *
 * @example
 * ```ts
 * const pageData = ref({ title: '', content: '' });
 * const { isSaving, lastSaved, triggerSave } = useAutoSave(
 *   pageData,
 *   async () => {
 *     await api.savePage(pageData.value);
 *   },
 *   { delay: 30000 }
 * );
 * ```
 */
export function useAutoSave<T>(
  data: Ref<T>,
  saveFunction: () => Promise<void>,
  options: AutoSaveOptions = {}
) {
  const { delay = 30000, enabled = true } = options;

  const isSaving = ref(false);
  const lastSaved = ref<Date | null>(null);
  const saveError = ref<string | null>(null);
  let saveTimeout: ReturnType<typeof setTimeout> | null = null;

  /**
   * Manually trigger a save operation
   */
  const triggerSave = async () => {
    if (isSaving.value) return;

    try {
      isSaving.value = true;
      saveError.value = null;
      await saveFunction();
      lastSaved.value = new Date();
    } catch (error) {
      console.error('Auto-save error:', error);
      saveError.value = error instanceof Error ? error.message : 'Failed to save';
      throw error;
    } finally {
      isSaving.value = false;
    }
  };

  /**
   * Schedule an auto-save with debouncing
   */
  const scheduleAutoSave = () => {
    if (!enabled) return;

    // Clear existing timeout
    if (saveTimeout) {
      clearTimeout(saveTimeout);
    }

    // Schedule new save
    saveTimeout = setTimeout(() => {
      triggerSave();
    }, delay);
  };

  /**
   * Cancel any pending auto-save
   */
  const cancelAutoSave = () => {
    if (saveTimeout) {
      clearTimeout(saveTimeout);
      saveTimeout = null;
    }
  };

  // Watch for changes in data
  watch(
    data,
    () => {
      scheduleAutoSave();
    },
    { deep: true }
  );

  // Cleanup on unmount
  if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', () => {
      cancelAutoSave();
    });
  }

  return {
    /**
     * Whether a save operation is currently in progress
     */
    isSaving,
    /**
     * Timestamp of the last successful save
     */
    lastSaved,
    /**
     * Error message from the last save attempt, if any
     */
    saveError,
    /**
     * Manually trigger a save operation
     */
    triggerSave,
    /**
     * Cancel any pending auto-save
     */
    cancelAutoSave,
  };
}

export default useAutoSave;
