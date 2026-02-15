import { apiClientAuth } from '@/api';

export interface Log {
  id: number;
  action: string;
  table_name: string;
  record_id: number;
  user_id: number;
  column_name: string | null;
  old_value: string | null;
  new_value: string | null;
  timestamp: string;
  last_login: string | null;
  authority_id: number;
  username?: string; // Added by backend enrichment
}

export interface LogPagination {
  total: number;
  page: number;
  limit: number;
  totalPages: number;
  totalItems?: number; // Optional, das Frontend kann es verwenden
}

export interface LogsResponse {
  logs: Log[];
  pagination: LogPagination;
}

export interface LogFilter {
  page?: number;
  limit?: number;
  start_date?: string;
  end_date?: string;
  action_type?: string;
  table_name?: string;
  user_id?: number;
  authority_id?: number;
  search?: string;
}

export interface UserStat {
  user_id: number;
  username: string;
  action_count: number;
}

export interface ActionStat {
  action: string;
  count: number;
}

export interface TableStat {
  table_name: string;
  count: number;
}

export interface ActivityPoint {
  date: string;
  count: number;
}

export interface LogStats {
  topUsers: UserStat[];
  actionTypes: ActionStat[];
  topTables: TableStat[];
  activityTrend: ActivityPoint[];
  recentLogins: Log[];
  totalLogs: number;
}

class LogsService {
  /**
   * Get logs with optional filtering and pagination
   */
  async getLogs(filter: LogFilter = {}): Promise<LogsResponse> {
    // Build query parameters
    const params = new URLSearchParams();
    
    // Add all filter parameters that are defined
    Object.entries(filter).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params.append(key, String(value));
      }
    });
    
    // Debug-Ausgabe
    const url = `admin/logs.php?action=getLogs&${params.toString()}`;
    console.log('LogsService.getLogs - URL:', url);
    
    try {
      // Make the API call
      const response = await apiClientAuth.get(url);
      
      // Safety check for expected response format
      if (!response.data || typeof response.data !== 'object') {
        console.error('LogsService.getLogs - Invalid response format:', response.data);
        return {
          logs: [],
          pagination: {
            total: 0,
            page: 1,
            limit: filter.limit || 25,
            totalPages: 1,
            totalItems: 0
          }
        };
      }
      
      // Ensure expected structure exists
      const result = response.data;
      
      if (!result.logs) {
        result.logs = [];
      }
      
      if (!result.pagination) {
        result.pagination = {
          total: 0,
          page: filter.page || 1,
          limit: filter.limit || 25,
          totalPages: 1,
          totalItems: 0
        };
      } else {
        // Ensure all pagination properties exist
        result.pagination.total = result.pagination.total || 0;
        result.pagination.totalItems = result.pagination.total || result.pagination.totalItems || 0;
        result.pagination.page = result.pagination.page || filter.page || 1;
        result.pagination.limit = result.pagination.limit || filter.limit || 25;
        result.pagination.totalPages = result.pagination.totalPages || 1;
      }
      
      return result;
    } catch (error) {
      console.error('LogsService.getLogs - Error:', error);
      
      // Return default safe values on error
      return {
        logs: [],
        pagination: {
          total: 0,
          page: filter.page || 1,
          limit: filter.limit || 25,
          totalPages: 1,
          totalItems: 0
        }
      };
    }
  }
  
  /**
   * Get log statistics
   */
  async getLogStats(): Promise<LogStats> {
    console.log('LogsService.getLogStats - URL: admin/logs.php?action=getLogStats');
    
    try {
      const response = await apiClientAuth.get('/admin/logs.php?action=getLogStats');
      
      // Safety check for expected response format
      if (!response.data || typeof response.data !== 'object') {
        console.error('LogsService.getLogStats - Invalid response format:', response.data);
        return this.getDefaultStats();
      }
      
      // Ensure all required properties exist with fallbacks
      const result = response.data;
      
      return {
        topUsers: Array.isArray(result.topUsers) ? result.topUsers : [],
        actionTypes: Array.isArray(result.actionTypes) ? result.actionTypes : [],
        topTables: Array.isArray(result.topTables) ? result.topTables : [],
        activityTrend: Array.isArray(result.activityTrend) ? result.activityTrend : [],
        recentLogins: Array.isArray(result.recentLogins) ? result.recentLogins : [],
        totalLogs: typeof result.totalLogs === 'number' ? result.totalLogs : 0
      };
    } catch (error) {
      console.error('LogsService.getLogStats - Error:', error);
      return this.getDefaultStats();
    }
  }
  
  /**
   * Get default stats object for error handling
   */
  private getDefaultStats(): LogStats {
    return {
      topUsers: [],
      actionTypes: [],
      topTables: [],
      activityTrend: [],
      recentLogins: [],
      totalLogs: 0
    };
  }
  
  /**
   * Clear logs (SYSTEM_ADMIN only)
   */
  async clearLogs(olderThan?: string): Promise<{ success: boolean; message: string }> {
    const data = new FormData();
    data.append('confirm', 'yes');
    
    if (olderThan) {
      data.append('older_than', olderThan);
    }
    
    const response = await apiClientAuth.post('/admin/logs.php?action=clearLogs', data);
    return response.data;
  }
  
  /**
   * Get download URL for logs export
   */
  getExportUrl(filter: LogFilter = {}): string {
    // Build query parameters
    const params = new URLSearchParams();
    params.append('action', 'exportLogs');
    
    // Add all filter parameters that are defined
    Object.entries(filter).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params.append(key, String(value));
      }
    });
    
    // Return the full URL that can be used in a download link
    return `${apiClientAuth.defaults.baseURL}/admin/logs.php?${params.toString()}`;
  }
  
  /**
   * Get unique list of action types for filtering
   */
  async getActionTypes(): Promise<string[]> {
    const stats = await this.getLogStats();
    return stats.actionTypes.map(action => action.action);
  }
  
  /**
   * Get unique list of table names for filtering
   */
  async getTableNames(): Promise<string[]> {
    const stats = await this.getLogStats();
    return stats.topTables.map(table => table.table_name);
  }

  /**
   * Get single log entry with full details (LAZY LOADING)
   * Optimiert: Lädt nur 1 Log statt alle
   */
  async getLogDetails(logId: number): Promise<Log> {
    try {
      const response = await apiClientAuth.get(`/admin/logs/?action=getLogDetails&id=${logId}`);

      if (!response.data || typeof response.data !== 'object') {
        throw new Error('Invalid response format');
      }

      return response.data;
    } catch (error) {
      console.error('LogsService.getLogDetails - Error:', error);
      throw error;
    }
  }
}

export default new LogsService(); 