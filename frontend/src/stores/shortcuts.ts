/**
 * Shortcuts Pinia Store
 *
 * Features:
 * - localStorage cache with TTL
 * - BroadcastChannel for multi-tab sync
 * - Map-based indexing for O(1) lookups
 * - Performance metrics
 * - Stale-while-revalidate pattern
 *
 * Version: 2.2 (K-Systems Integration)
 * Date: 2025-01-26
 */

import { defineStore } from 'pinia';
import { apiClientAuth } from '@/api';
import type { Shortcut, ShortcutType, ShortcutCreate } from '@/types/Shortcut';
import { validateShortcutsArray } from '@/types/Shortcut';

// =============================================================================
// CACHE CONFIGURATION
// =============================================================================

const CACHE_KEY = 'user_shortcuts';
const CACHE_TIME_KEY = 'shortcuts_cache_time';
const CACHE_VERSION_KEY = 'shortcuts_cache_version';
const CACHE_VERSION = 2; // Increment to invalidate old cache
const CACHE_TTL = 1000 * 60 * 60; // 1 hour

// BroadcastChannel for multi-tab synchronization
const cacheChannel =
  typeof window !== 'undefined' ? new BroadcastChannel('shortcuts-cache') : null;

// =============================================================================
// STORE DEFINITION
// =============================================================================

export const useShortcutsStore = defineStore('shortcuts', {
  // ===========================================================================
  // STATE
  // ===========================================================================
  state: () => ({
    /** All user shortcuts */
    shortcuts: [] as Shortcut[],

    /** Map-based index for O(1) lookups */
    shortcutIndex: new Map<string, number>() as Map<string, number>,

    /** Loading state */
    loading: false,

    /** Error message */
    error: null as string | null,

    /** Performance metrics */
    metrics: {
      lastLoadTime: 0,
      avgLoadTime: 0,
      cacheHits: 0,
      cacheMisses: 0,
    },
  }),

  // ===========================================================================
  // GETTERS
  // ===========================================================================
  getters: {
    /**
     * Group shortcuts by type
     */
    groupedShortcuts: (state): Record<ShortcutType, Shortcut[]> => {
      const grouped: Record<string, Shortcut[]> = {};

      state.shortcuts.forEach((shortcut) => {
        if (!grouped[shortcut.type]) {
          grouped[shortcut.type] = [];
        }
        grouped[shortcut.type].push(shortcut);
      });

      return grouped as Record<ShortcutType, Shortcut[]>;
    },

    /**
     * Get shortcuts count
     */
    count: (state): number => state.shortcuts.length,

    /**
     * Check if user has a specific shortcut
     */
    hasShortcut:
      (state) =>
      (type: ShortcutType, resourceId: number): boolean => {
        const key = `${type}:${resourceId}`;
        return state.shortcutIndex.has(key);
      },

    /**
     * Get shortcut ID by type and resource
     */
    getShortcutId:
      (state) =>
      (type: ShortcutType, resourceId: number): number | undefined => {
        const key = `${type}:${resourceId}`;
        return state.shortcutIndex.get(key);
      },

    /**
     * Get shortcut by ID
     */
    getShortcutById:
      (state) =>
      (id: number): Shortcut | undefined => {
        return state.shortcuts.find((s) => s.id === id);
      },

    /**
     * Check if cache is fresh (within TTL)
     */
    isCacheFresh: (): boolean => {
      try {
        const cachedTime = localStorage.getItem(CACHE_TIME_KEY);
        const cachedVersion = localStorage.getItem(CACHE_VERSION_KEY);

        if (!cachedTime || !cachedVersion) return false;

        const cacheAge = Date.now() - parseInt(cachedTime, 10);
        const isVersionMatch = parseInt(cachedVersion, 10) === CACHE_VERSION;

        return cacheAge < CACHE_TTL && isVersionMatch;
      } catch {
        return false;
      }
    },
  },

  // ===========================================================================
  // ACTIONS
  // ===========================================================================
  actions: {
    /**
     * Load shortcuts from API with cache support
     *
     * Strategy: Stale-While-Revalidate
     * 1. Return cached data immediately if available
     * 2. Fetch fresh data in background
     * 3. Update cache
     */
    async loadShortcuts(forceRefresh = false): Promise<void> {
      const startTime = performance.now();

      try {
        // Check cache first (unless force refresh)
        if (!forceRefresh && this.isCacheFresh) {
          const cached = this.loadFromCache();
          if (cached) {
            this.metrics.cacheHits++;
            console.log('✅ Shortcuts loaded from cache');

            // Fetch fresh data in background
            this.fetchAndUpdateCache().catch((error) => {
              console.error('Background fetch failed:', error);
            });

            return;
          }
        }

        // Cache miss - fetch from API
        this.metrics.cacheMisses++;
        this.loading = true;
        this.error = null;

        await this.fetchAndUpdateCache();

        // Update metrics
        const loadTime = performance.now() - startTime;
        this.metrics.lastLoadTime = loadTime;
        this.metrics.avgLoadTime =
          (this.metrics.avgLoadTime * 0.8 + loadTime * 0.2); // Moving average

        console.log(`✅ Shortcuts loaded from API (${loadTime.toFixed(2)}ms)`);
      } catch (error: any) {
        console.error('❌ Failed to load shortcuts:', error);
        this.error = error.response?.data?.error || 'Failed to load shortcuts';

        // Try to use cached data on error
        const cached = this.loadFromCache();
        if (cached) {
          console.log('⚠️ Using stale cache due to API error');
        }
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch fresh data from API and update cache
     */
    async fetchAndUpdateCache(): Promise<void> {
      const response = await apiClientAuth.get('/shortcuts/', {
        params: { action: 'getShortcuts', _t: Date.now() },
      });

      // Validate response
      const shortcuts = response.data?.shortcuts;
      if (!Array.isArray(shortcuts)) {
        throw new Error('Invalid shortcuts response from API');
      }

      // Validate shortcuts array
      if (!validateShortcutsArray(shortcuts)) {
        throw new Error('Invalid shortcuts data structure');
      }

      // Update state
      this.shortcuts = shortcuts;
      this.rebuildIndex();

      // Update cache
      this.saveToCache();

      // Broadcast to other tabs
      cacheChannel?.postMessage({ type: 'update', shortcuts: this.shortcuts });
    },

    /**
     * Add a new shortcut
     */
    async addShortcut(shortcut: ShortcutCreate): Promise<number> {
      try {
        // Optimistic update
        const tempId = Date.now();
        const optimisticShortcut: Shortcut = {
          ...shortcut,
          id: tempId,
          sort_order: 0,
        };

        this.shortcuts.push(optimisticShortcut);
        this.rebuildIndex();

        // API call
        const response = await apiClientAuth.post(
          '/shortcuts/',
          shortcut,
          { params: { action: 'addShortcut' } }
        );

        const { shortcut_id } = response.data;

        // Replace optimistic with real data
        const index = this.shortcuts.findIndex((s) => s.id === tempId);
        if (index !== -1) {
          this.shortcuts[index].id = shortcut_id;
        }

        this.rebuildIndex();
        this.saveToCache();

        console.log('✅ Shortcut added:', shortcut_id);
        return shortcut_id;
      } catch (error: any) {
        // Rollback optimistic update
        await this.loadShortcuts(true);

        console.error('❌ Failed to add shortcut:', error);
        throw new Error(error.response?.data?.error || 'Failed to add shortcut');
      }
    },

    /**
     * Remove a shortcut (soft delete)
     */
    async removeShortcut(shortcutId: number): Promise<void> {
      try {
        // Optimistic update
        const originalShortcuts = [...this.shortcuts];
        this.shortcuts = this.shortcuts.filter((s) => s.id !== shortcutId);
        this.rebuildIndex();

        // API call
        await apiClientAuth.post(
          '/shortcuts/',
          { shortcut_id: shortcutId },
          { params: { action: 'removeShortcut' } }
        );

        this.saveToCache();

        console.log('✅ Shortcut removed:', shortcutId);
      } catch (error: any) {
        // Rollback optimistic update
        this.shortcuts = originalShortcuts;
        this.rebuildIndex();

        console.error('❌ Failed to remove shortcut:', error);
        throw new Error(error.response?.data?.error || 'Failed to remove shortcut');
      }
    },

    /**
     * Reorder shortcuts
     */
    async reorderShortcuts(shortcuts: Array<{ id: number; sort_order: number }>): Promise<void> {
      try {
        // Optimistic update
        const originalShortcuts = [...this.shortcuts];

        shortcuts.forEach((item) => {
          const shortcut = this.shortcuts.find((s) => s.id === item.id);
          if (shortcut) {
            shortcut.sort_order = item.sort_order;
          }
        });

        // API call
        await apiClientAuth.post(
          '/shortcuts/',
          { shortcuts },
          { params: { action: 'reorderShortcuts' } }
        );

        this.saveToCache();

        console.log('✅ Shortcuts reordered');
      } catch (error: any) {
        // Rollback optimistic update
        this.shortcuts = originalShortcuts;

        console.error('❌ Failed to reorder shortcuts:', error);
        throw new Error(error.response?.data?.error || 'Failed to reorder shortcuts');
      }
    },

    /**
     * Check if a resource is already a shortcut
     */
    async checkShortcut(type: ShortcutType, resourceId: number): Promise<boolean> {
      // Check local cache first (O(1))
      if (this.hasShortcut(type, resourceId)) {
        return true;
      }

      // Fallback to API if cache might be stale
      try {
        const response = await apiClientAuth.get('/shortcuts/', {
          params: {
            action: 'hasShortcut',
            type,
            resource_id: resourceId,
          },
        });

        return response.data.has_shortcut;
      } catch (error) {
        console.error('Failed to check shortcut:', error);
        return false;
      }
    },

    /**
     * Rebuild the index map for O(1) lookups
     */
    rebuildIndex(): void {
      this.shortcutIndex.clear();
      this.shortcuts.forEach((shortcut) => {
        const key = `${shortcut.type}:${shortcut.resource_id}`;
        this.shortcutIndex.set(key, shortcut.id);
      });
    },

    /**
     * Save shortcuts to localStorage
     */
    saveToCache(): void {
      try {
        localStorage.setItem(CACHE_KEY, JSON.stringify(this.shortcuts));
        localStorage.setItem(CACHE_TIME_KEY, Date.now().toString());
        localStorage.setItem(CACHE_VERSION_KEY, CACHE_VERSION.toString());
      } catch (error) {
        console.warn('Failed to save shortcuts to cache:', error);
      }
    },

    /**
     * Load shortcuts from localStorage
     */
    loadFromCache(): boolean {
      try {
        const cached = localStorage.getItem(CACHE_KEY);
        if (!cached) return false;

        const parsed = JSON.parse(cached);

        // Validate cached data
        if (!validateShortcutsArray(parsed)) {
          console.warn('Invalid shortcuts in cache, clearing');
          this.clearCache();
          return false;
        }

        this.shortcuts = parsed;
        this.rebuildIndex();

        return true;
      } catch (error) {
        console.warn('Failed to load shortcuts from cache:', error);
        return false;
      }
    },

    /**
     * Clear cache
     */
    clearCache(): void {
      try {
        localStorage.removeItem(CACHE_KEY);
        localStorage.removeItem(CACHE_TIME_KEY);
        localStorage.removeItem(CACHE_VERSION_KEY);
        console.log('🗑️ Shortcuts cache cleared');
      } catch (error) {
        console.warn('Failed to clear cache:', error);
      }
    },

    /**
     * Reset store state
     */
    $reset(): void {
      this.shortcuts = [];
      this.shortcutIndex.clear();
      this.loading = false;
      this.error = null;
      this.metrics = {
        lastLoadTime: 0,
        avgLoadTime: 0,
        cacheHits: 0,
        cacheMisses: 0,
      };
    },
  },
});

// =============================================================================
// MULTI-TAB SYNC
// =============================================================================

// Listen for cache updates from other tabs
if (cacheChannel) {
  cacheChannel.onmessage = (event) => {
    if (event.data.type === 'update') {
      const store = useShortcutsStore();
      store.shortcuts = event.data.shortcuts;
      store.rebuildIndex();
      console.log('🔄 Shortcuts synced from another tab');
    }
  };
}
