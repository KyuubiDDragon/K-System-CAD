import { apiClientAuth } from "@/api";
import { useAuthStore } from '@/stores/auth';
import { useModulePermission } from '@/composables/useModulePermission';

export interface AuthorityField {
  id: number;
  authority_id: number;
  field_name: string;
  display_name: string;
  field_type: 'text' | 'number' | 'date' | 'boolean' | 'select' | 'textarea' | 'multiselect';
  required: boolean;
  options: FieldOption[] | null;
  display_order: number;
}

export interface FieldOption {
  text: string;
  value: string;
}

class AuthorityFieldsService {
  // Cache for authority fields to improve performance
  private fieldsCache: { [key: string]: { data: AuthorityField[], timestamp: number } } = {};
  
  // Cache expiration time (5 minutes in milliseconds)
  private cacheExpirationMs = 5 * 60 * 1000;
  
  // Clear the cache whenever fields are modified
  private clearCache() {
    this.fieldsCache = {};
    console.log('Authority fields cache cleared');
  }
  
  // Check if cache is valid for the given key
  private isCacheValid(key: string): boolean {
    const cache = this.fieldsCache[key];
    if (!cache) return false;
    
    const now = Date.now();
    return (now - cache.timestamp) < this.cacheExpirationMs;
  }

  /**
   * Get fields for the current user's authority
   * This is the method used by PersonFile components
   */
  async getFields(): Promise<AuthorityField[]> {
    try {
      const { hasModulePermission } = useModulePermission();
      const isSystemAdmin = hasModulePermission('system', 'ADMIN');

      if (isSystemAdmin) {
        // System admins see all fields
        return this.getAllFields();
      } else {
        // Regular users see only their authority fields
        return this.getFieldsForCurrentAuthority();
      }
    } catch (error) {
      console.error('Fehler beim Laden der Feldkonfiguration:', error);
      return [];
    }
  }

  /**
   * Get all fields across all authorities (for system admins)
   */
  async getAllFields(): Promise<AuthorityField[]> {
    const cacheKey = 'all_fields';
    
    // Return from cache if valid
    if (this.isCacheValid(cacheKey)) {
      console.log('Returning authority fields from cache');
      return this.fieldsCache[cacheKey].data;
    }
    
    try {
      const response = await apiClientAuth.get('/admin/authority_fields.php?action=getFields');
      
      // Update cache
      this.fieldsCache[cacheKey] = {
        data: response.data,
        timestamp: Date.now()
      };
      
      return response.data;
    } catch (error) {
      console.error('Error fetching authority fields:', error);
      throw error;
    }
  }

  /**
   * Get fields for a specific authority ID
   */
  async getFieldsForAuthority(authorityId: number): Promise<AuthorityField[]> {
    const cacheKey = `authority_${authorityId}`;
    
    // Return from cache if valid
    if (this.isCacheValid(cacheKey)) {
      console.log(`Returning fields for authority ${authorityId} from cache`);
      return this.fieldsCache[cacheKey].data;
    }
    
    try {
      const response = await apiClientAuth.get(`/admin/authority_fields.php?action=getFieldsByAuth&authority_id=${authorityId}`);
      
      // Update cache
      this.fieldsCache[cacheKey] = {
        data: response.data,
        timestamp: Date.now()
      };
      
      return response.data;
    } catch (error) {
      console.error(`Error fetching fields for authority ${authorityId}:`, error);
      throw error;
    }
  }

  /**
   * Get fields for the current user's authority
   */
  async getFieldsForCurrentAuthority(): Promise<AuthorityField[]> {
    try {
      const authStore = useAuthStore();
      // Try to get authority_id from user object, fallback to authority property
      const authorityId = authStore.user?.authority_id || (authStore.user?.authority ? parseInt(authStore.user.authority, 10) : null);
      
      if (!authorityId) {
        throw new Error('No authority context available');
      }
      
      return this.getFieldsForAuthority(authorityId);
    } catch (error) {
      console.error('Error fetching fields for current authority:', error);
      throw error;
    }
  }

  /**
   * Add a new field
   */
  async addField(field: Omit<AuthorityField, 'id'>): Promise<any> {
    try {
      const response = await apiClientAuth.post('/admin/authority_fields.php?action=addField', field);
      // Clear cache after successful update
      this.clearCache();
      return response.data;
    } catch (error) {
      console.error('Error adding authority field:', error);
      throw error;
    }
  }

  /**
   * Update an existing field
   */
  async updateField(field: AuthorityField): Promise<any> {
    try {
      const response = await apiClientAuth.post('/admin/authority_fields.php?action=updateField', field);
      // Clear cache after successful update
      this.clearCache();
      return response.data;
    } catch (error) {
      console.error(`Error updating authority field ${field.id}:`, error);
      throw error;
    }
  }

  /**
   * Delete a field
   */
  async deleteField(fieldId: number): Promise<any> {
    try {
      const response = await apiClientAuth.post('/admin/authority_fields.php?action=deleteField', { id: fieldId });
      // Clear cache after successful deletion
      this.clearCache();
      return response.data;
    } catch (error) {
      console.error(`Error deleting authority field ${fieldId}:`, error);
      throw error;
    }
  }
}

export default new AuthorityFieldsService(); 