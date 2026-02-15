import { ref, onBeforeUnmount } from 'vue';
import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router';

/**
 * Options for the unsaved changes composable
 */
export interface UnsavedChangesOptions {
  /**
   * Custom message to show in the browser confirmation dialog
   */
  message?: string;
  /**
   * Whether to warn about unsaved changes (default: true)
   */
  enabled?: boolean;
}

/**
 * Composable for detecting and warning about unsaved changes
 * Prevents accidental data loss when navigating away
 *
 * @param options - Configuration options
 * @returns Object with dirty state tracking and navigation guard
 *
 * @example
 * ```ts
 * // In a Vue component
 * const { isDirty, markDirty, markClean, confirmLeave } = useUnsavedChanges({
 *   message: 'You have unsaved changes. Are you sure you want to leave?'
 * });
 *
 * // Mark as dirty when user makes changes
 * watch(formData, () => markDirty(), { deep: true });
 *
 * // Mark as clean after successful save
 * const save = async () => {
 *   await api.save(formData);
 *   markClean();
 * };
 *
 * // Use in route guard
 * onBeforeRouteLeave((to, from, next) => {
 *   confirmLeave(to, from, next);
 * });
 * ```
 */
export function useUnsavedChanges(options: UnsavedChangesOptions = {}) {
  const {
    message = 'You have unsaved changes. Are you sure you want to leave?',
    enabled = true,
  } = options;

  const isDirty = ref(false);

  /**
   * Mark the form/data as having unsaved changes
   */
  const markDirty = () => {
    isDirty.value = true;
  };

  /**
   * Mark the form/data as saved (no pending changes)
   */
  const markClean = () => {
    isDirty.value = false;
  };

  /**
   * Reset the dirty state (alias for markClean)
   */
  const reset = () => {
    markClean();
  };

  /**
   * Browser beforeunload event handler
   * Shows native browser confirmation dialog when trying to close/reload page
   */
  const handleBeforeUnload = (event: BeforeUnloadEvent) => {
    if (!enabled || !isDirty.value) return;

    event.preventDefault();
    // Modern browsers ignore custom messages and show their own
    event.returnValue = message;
    return message;
  };

  /**
   * Vue Router navigation guard
   * Shows confirmation dialog before navigating to another route
   *
   * @param to - Target route
   * @param from - Current route
   * @param next - Navigation callback
   */
  const confirmLeave = (
    to: RouteLocationNormalized,
    from: RouteLocationNormalized,
    next: NavigationGuardNext
  ) => {
    if (!enabled || !isDirty.value) {
      next();
      return;
    }

    const answer = window.confirm(message);
    if (answer) {
      markClean();
      next();
    } else {
      next(false);
    }
  };

  /**
   * Alternative navigation guard that returns a boolean
   * Useful for Vue Router 4's beforeRouteLeave
   */
  const beforeRouteLeave = async (
    to: RouteLocationNormalized,
    from: RouteLocationNormalized
  ): Promise<boolean> => {
    if (!enabled || !isDirty.value) {
      return true;
    }

    const answer = window.confirm(message);
    if (answer) {
      markClean();
      return true;
    }
    return false;
  };

  // Add browser beforeunload listener
  if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', handleBeforeUnload);
  }

  // Cleanup on component unmount
  onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('beforeunload', handleBeforeUnload);
    }
  });

  return {
    /**
     * Whether there are unsaved changes
     */
    isDirty,
    /**
     * Mark the data as having unsaved changes
     */
    markDirty,
    /**
     * Mark the data as saved (clear dirty state)
     */
    markClean,
    /**
     * Reset the dirty state (alias for markClean)
     */
    reset,
    /**
     * Navigation guard for Vue Router (callback style)
     */
    confirmLeave,
    /**
     * Navigation guard for Vue Router (async/boolean style)
     */
    beforeRouteLeave,
  };
}

export default useUnsavedChanges;
