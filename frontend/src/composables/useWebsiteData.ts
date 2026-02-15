import { ref, computed } from 'vue';
import api from '@/api';

/**
 * Website configuration interface
 */
export interface WebsiteConfig {
  id?: number;
  authority_id: number;
  site_title: string;
  site_description: string;
  theme: string;
  primary_color: string;
  secondary_color: string;
  logo_url?: string;
  favicon_url?: string;
  header_html?: string;
  footer_html?: string;
  custom_css?: string;
  custom_js?: string;
  meta_keywords?: string;
  social_links?: Record<string, string>;
  contact_info?: {
    email?: string;
    phone?: string;
    address?: string;
  };
  created_at?: string;
  updated_at?: string;
}

/**
 * Website page interface
 */
export interface WebsitePage {
  id?: number;
  authority_id: number;
  title: string;
  slug: string;
  content: string;
  excerpt?: string;
  featured_image?: string;
  meta_title?: string;
  meta_description?: string;
  meta_keywords?: string;
  is_published: boolean;
  is_homepage: boolean;
  parent_id?: number;
  order_index: number;
  template?: string;
  created_at?: string;
  updated_at?: string;
  created_by?: number;
  updated_by?: number;
}

/**
 * Website post/news interface
 */
export interface WebsitePost {
  id?: number;
  authority_id: number;
  title: string;
  slug: string;
  content: string;
  excerpt?: string;
  featured_image?: string;
  category_id?: number;
  tags?: string[];
  meta_title?: string;
  meta_description?: string;
  meta_keywords?: string;
  is_published: boolean;
  published_at?: string;
  author_id: number;
  created_at?: string;
  updated_at?: string;
}

/**
 * Website category interface
 */
export interface WebsiteCategory {
  id?: number;
  authority_id: number;
  name: string;
  slug: string;
  description?: string;
  parent_id?: number;
  order_index: number;
  created_at?: string;
  updated_at?: string;
}

/**
 * Composable for fetching and managing website data
 * Centralizes all website-related API calls and state management
 *
 * @returns Object with website data, loading states, and fetch functions
 *
 * @example
 * ```ts
 * const {
 *   config,
 *   pages,
 *   posts,
 *   categories,
 *   isLoading,
 *   fetchAll,
 *   refreshConfig,
 *   refreshPages
 * } = useWebsiteData();
 *
 * onMounted(async () => {
 *   await fetchAll();
 * });
 * ```
 */
export function useWebsiteData() {
  // Data references
  const config = ref<WebsiteConfig | null>(null);
  const pages = ref<WebsitePage[]>([]);
  const posts = ref<WebsitePost[]>([]);
  const categories = ref<WebsiteCategory[]>([]);

  // Loading states
  const isLoadingConfig = ref(false);
  const isLoadingPages = ref(false);
  const isLoadingPosts = ref(false);
  const isLoadingCategories = ref(false);

  // Error states
  const configError = ref<string | null>(null);
  const pagesError = ref<string | null>(null);
  const postsError = ref<string | null>(null);
  const categoriesError = ref<string | null>(null);

  // Computed overall loading state
  const isLoading = computed(() =>
    isLoadingConfig.value ||
    isLoadingPages.value ||
    isLoadingPosts.value ||
    isLoadingCategories.value
  );

  // Computed overall error state
  const hasError = computed(() =>
    !!configError.value ||
    !!pagesError.value ||
    !!postsError.value ||
    !!categoriesError.value
  );

  /**
   * Fetch website configuration
   */
  const fetchConfig = async (): Promise<void> => {
    try {
      isLoadingConfig.value = true;
      configError.value = null;
      const token = localStorage.getItem('token');
      const response = await fetch(`${import.meta.env.VITE_API_URL}/website/config.php`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });

      if (!response.ok) throw new Error('Failed to fetch website config');

      const data = await response.json();
      config.value = data.config || null;
    } catch (error) {
      console.error('Error fetching website config:', error);
      configError.value = error instanceof Error ? error.message : 'Failed to fetch config';
      throw error;
    } finally {
      isLoadingConfig.value = false;
    }
  };

  /**
   * Fetch all website pages
   */
  const fetchPages = async (): Promise<void> => {
    try {
      isLoadingPages.value = true;
      pagesError.value = null;
      const token = localStorage.getItem('token');
      const response = await fetch(`${import.meta.env.VITE_API_URL}/website/pages.php`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });

      if (!response.ok) throw new Error('Failed to fetch pages');

      const data = await response.json();
      pages.value = data.pages || [];
    } catch (error) {
      console.error('Error fetching pages:', error);
      pagesError.value = error instanceof Error ? error.message : 'Failed to fetch pages';
      throw error;
    } finally {
      isLoadingPages.value = false;
    }
  };

  /**
   * Fetch all website posts
   */
  const fetchPosts = async (): Promise<void> => {
    try {
      isLoadingPosts.value = true;
      postsError.value = null;
      const token = localStorage.getItem('token');
      const response = await fetch(`${import.meta.env.VITE_API_URL}/website/posts.php`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });

      if (!response.ok) throw new Error('Failed to fetch posts');

      const data = await response.json();
      posts.value = data.posts || [];
    } catch (error) {
      console.error('Error fetching posts:', error);
      postsError.value = error instanceof Error ? error.message : 'Failed to fetch posts';
      throw error;
    } finally {
      isLoadingPosts.value = false;
    }
  };

  /**
   * Fetch all website categories
   */
  const fetchCategories = async (): Promise<void> => {
    try {
      isLoadingCategories.value = true;
      categoriesError.value = null;
      const token = localStorage.getItem('token');
      const response = await fetch(`${import.meta.env.VITE_API_URL}/website/categories.php`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });

      if (!response.ok) throw new Error('Failed to fetch categories');

      const data = await response.json();
      categories.value = data.categories || [];
    } catch (error) {
      console.error('Error fetching categories:', error);
      categoriesError.value = error instanceof Error ? error.message : 'Failed to fetch categories';
      throw error;
    } finally {
      isLoadingCategories.value = false;
    }
  };

  /**
   * Fetch all website data (config, pages, posts, categories)
   */
  const fetchAll = async (): Promise<void> => {
    await Promise.all([
      fetchConfig(),
      fetchPages(),
      fetchPosts(),
      fetchCategories(),
    ]);
  };

  /**
   * Refresh specific data type
   */
  const refreshConfig = fetchConfig;
  const refreshPages = fetchPages;
  const refreshPosts = fetchPosts;
  const refreshCategories = fetchCategories;

  /**
   * Get published pages only
   */
  const publishedPages = computed(() =>
    pages.value.filter(page => page.is_published)
  );

  /**
   * Get published posts only
   */
  const publishedPosts = computed(() =>
    posts.value.filter(post => post.is_published)
  );

  /**
   * Get homepage page
   */
  const homepage = computed(() =>
    pages.value.find(page => page.is_homepage)
  );

  /**
   * Get pages by parent ID
   */
  const getPagesByParent = (parentId: number | null) =>
    pages.value.filter(page => page.parent_id === parentId);

  /**
   * Get posts by category
   */
  const getPostsByCategory = (categoryId: number) =>
    posts.value.filter(post => post.category_id === categoryId);

  return {
    // Data
    config,
    pages,
    posts,
    categories,

    // Loading states
    isLoading,
    isLoadingConfig,
    isLoadingPages,
    isLoadingPosts,
    isLoadingCategories,

    // Error states
    hasError,
    configError,
    pagesError,
    postsError,
    categoriesError,

    // Fetch functions
    fetchAll,
    fetchConfig,
    fetchPages,
    fetchPosts,
    fetchCategories,

    // Refresh functions
    refreshConfig,
    refreshPages,
    refreshPosts,
    refreshCategories,

    // Computed helpers
    publishedPages,
    publishedPosts,
    homepage,
    getPagesByParent,
    getPostsByCategory,
  };
}

export default useWebsiteData;
