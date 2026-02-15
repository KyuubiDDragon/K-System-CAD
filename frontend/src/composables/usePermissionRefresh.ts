import { useAuthStore } from '@/stores/auth';
import { ref } from 'vue';

/**
 * Composable for refreshing user permissions from the database
 * This triggers a JWT token refresh which loads fresh permissions
 * Useful when permissions have been changed on the server
 */
export function usePermissionRefresh() {
  const authStore = useAuthStore();
  const isRefreshing = ref(false);

  /**
   * Refresh permissions from the database by refreshing the JWT token
   * The new token will contain updated permissions
   * @returns Promise<boolean> - whether the permissions were successfully refreshed
   */
  const refreshPermissions = async (): Promise<boolean> => {
    if (isRefreshing.value) {
      console.log('🔄 Permission refresh already in progress, waiting...');
      // Wait until current refresh completes
      while (isRefreshing.value) {
        await new Promise(resolve => setTimeout(resolve, 50));
      }
      return true;
    }

    isRefreshing.value = true;
    console.log('🔄 Refreshing permissions via token refresh...');

    try {
      // Call the token refresh which loads fresh permissions from DB
      await authStore.refreshToken();
      console.log('✅ Permissions refreshed successfully via token refresh');
      return true;
    } catch (error) {
      console.error('❌ Error refreshing permissions:', error);
      return false;
    } finally {
      isRefreshing.value = false;
    }
  };

  return {
    refreshPermissions,
    isRefreshing
  };
} 