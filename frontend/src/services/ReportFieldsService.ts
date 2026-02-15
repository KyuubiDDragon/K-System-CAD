import { apiClientAuth } from '@/api';

export interface ReportField {
  id?: number;
  authority_id: number;
  category_id: number | null;
  field_name: string;
  field_label: string;
  field_type: 'text' | 'number' | 'date' | 'boolean' | 'select' | 'textarea' | 'multiselect';
  options?: string[] | null;
  sort_order: number;
  is_required: boolean;
  created_at?: string;
  updated_at?: string;
  category_name?: string;
}

export interface ReportCategory {
  id: number;
  name: string;
  authority_id: number;
  description?: string;
}

class ReportFieldsService {
  private endpoint = '/admin/report_fields.php';

  async getReportFields() {
    try {
      const response = await apiClientAuth.get(this.endpoint, {
        params: { action: 'getReportFields' }
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching report fields:', error);
      throw error;
    }
  }

  async getFieldsByCategory(categoryId: number | null, includeGlobal: boolean = true) {
    try {
      const response = await apiClientAuth.get(this.endpoint, {
        params: {
          action: 'getFieldsByCategory',
          category_id: categoryId,
          include_global: includeGlobal
        }
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching fields by category:', error);
      throw error;
    }
  }

  async addReportField(field: Omit<ReportField, 'id' | 'created_at' | 'updated_at'>) {
    try {
      const response = await apiClientAuth.post(this.endpoint, field, {
        params: { action: 'addReportField' }
      });
      return response.data;
    } catch (error) {
      console.error('Error adding report field:', error);
      throw error;
    }
  }

  async updateReportField(field: Partial<ReportField> & { id: number }) {
    try {
      const response = await apiClientAuth.post(this.endpoint, field, {
        params: { action: 'updateReportField' }
      });
      return response.data;
    } catch (error) {
      console.error('Error updating report field:', error);
      throw error;
    }
  }

  async deleteReportField(id: number) {
    try {
      const response = await apiClientAuth.post(this.endpoint, { id }, {
        params: { action: 'deleteReportField' }
      });
      return response.data;
    } catch (error) {
      console.error('Error deleting report field:', error);
      throw error;
    }
  }

  async getReportCategories() {
    try {
      const response = await apiClientAuth.get(this.endpoint, {
        params: { action: 'getReportCategories' }
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching report categories:', error);
      throw error;
    }
  }
}

export default new ReportFieldsService(); 