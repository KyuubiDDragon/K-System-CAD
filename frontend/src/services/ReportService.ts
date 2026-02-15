import { apiClientAuth } from '@/api';
import { useAuthStore } from '@/stores/auth';
import type { AxiosResponse } from 'axios';

export interface Report {
    id?: number;
    title: string;
    content: string;
    category_id: number;
    status: string;
    priority: string;
    due_date: string | null;
    assigned_to: number | null;
    tags: string[];
    location: string;
    created_at?: string;
    updated_at?: string;
    custom_fields?: {
        id: number;
        name: string;
        value: string;
        type: string;
    }[];
}

export interface SharedReport extends Report {
    authority_id: number;
    authority_name: string;
    access_level: string;
    shared_by: string;
    shared_at: string;
}

export interface SharingInfo {
    report: Report;
    shared_from?: {
        owner_id: number;
        owner_name: string;
        owner_display_name: string;
        access_level: string;
    };
    shared_with: Array<{
        id: number;
        name: string;
        display_name: string;
        access_level: string;
    }>;
    visibility: 'public' | 'private' | 'specific_roles';
    groups: Array<{
        id: number;
        name: string;
        description?: string;
    }>;
}

export interface SharingOptions {
    authorities: Array<{
        id: number;
        name: string;
        display_name: string;
    }>;
    groups: Array<{
        id: number;
        name: string;
        description?: string;
        power?: number;
    }>;
}

class ReportService {
    private endpoint = '/report/index.php';

    /**
     * Get a list of reports
     */
    async getReports(): Promise<Report[]> {
        console.log('ReportService: Fetching reports');
        try {
            const response: AxiosResponse = await apiClientAuth.get('/report/?action=getReports');
            console.log('ReportService: Reports fetched successfully', response.data);
            return response.data.data || [];
        } catch (error) {
            console.error('ReportService: Error fetching reports', error);
            throw error;
        }
    }

    /**
     * Get a single report by ID
     * @param id Report ID
     */
    async getReport(id: number): Promise<Report> {
        console.log(`ReportService: Fetching report with ID ${id}`);
        try {
            const response: AxiosResponse = await apiClientAuth.get('/report/index.php', {
                params: { action: 'getReport', id },
            });
            console.log(`ReportService: Report ${id} fetched successfully`, response.data);
            
            // Process custom fields if they exist
            if (response.data.data && response.data.data.custom_fields) {
                response.data.data.custom_fields = this.processCustomFields(response.data.data.custom_fields);
            }
            
            return response.data.data || response.data;
        } catch (error) {
            console.error(`ReportService: Error fetching report ${id}`, error);
            throw error;
        }
    }

    /**
     * Get a single shared report by ID
     * @param id Report ID
     */
    async getSharedReport(id: number): Promise<Report> {
        console.log(`ReportService: Fetching shared report with ID ${id}`);
        try {
            const response: AxiosResponse = await apiClientAuth.get('/report/share.php', {
                params: { action: 'getSharedReport', id },
            });
            console.log(`ReportService: Shared report ${id} fetched successfully`, response.data);
            
            // Process custom fields if they exist
            if (response.data.data && response.data.data.custom_fields) {
                response.data.data.custom_fields = this.processCustomFields(response.data.data.custom_fields);
            }
            
            return response.data.data || response.data;
        } catch (error) {
            console.error(`ReportService: Error fetching shared report ${id}`, error);
            throw error;
        }
    }

    /**
     * Create a new report
     * @param report Report data
     */
    async createReport(report: Report): Promise<Report> {
        console.log('ReportService: Creating new report', report);
        try {
            const response: AxiosResponse = await apiClientAuth.post('/report/?action=addReport', report);
            console.log('ReportService: Report created successfully', response.data);
            return response.data.data;
        } catch (error) {
            console.error('ReportService: Error creating report', error);
            throw error;
        }
    }

    /**
     * Update an existing report
     * @param report Report data with ID
     */
    async updateReport(report: Report): Promise<Report> {
        console.log(`ReportService: Updating report ${report.id}`, report);
        if (!report.id) {
            const error = new Error('Report ID is required for updates');
            console.error('ReportService:', error);
            throw error;
        }
        
        try {
            const response: AxiosResponse = await apiClientAuth.post('/report/?action=editReport', report);
            console.log(`ReportService: Report ${report.id} updated successfully`, response.data);
            return response.data.data;
        } catch (error) {
            console.error(`ReportService: Error updating report ${report.id}`, error);
            throw error;
        }
    }

    /**
     * Delete a report
     * @param id Report ID
     */
    async deleteReport(id: number): Promise<void> {
        console.log(`ReportService: Deleting report ${id}`);
        try {
            await apiClientAuth.post('/report/?action=deleteReport', { id });
            console.log(`ReportService: Report ${id} deleted successfully`);
        } catch (error) {
            console.error(`ReportService: Error deleting report ${id}`, error);
            throw error;
        }
    }

    /**
     * Get a list of shared reports
     */
    async getSharedReports(): Promise<SharedReport[]> {
        console.log('ReportService: Fetching shared reports');
        try {
            const response: AxiosResponse = await apiClientAuth.get('/report/share.php?action=getSharedReports');
            console.log('ReportService: Shared reports fetched successfully', response.data);
            
            let reports: any[] = [];
            
            // Überprüfe, ob die Antwort ein Array oder in data.data enthalten ist
            if (Array.isArray(response.data)) {
                reports = response.data;
            } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
                reports = response.data.data;
            } else {
                console.warn('ReportService: Unexpected response format', response.data);
                return [];
            }
            
            // Ensure all reports are read-only regardless of backend setting
            const readOnlyReports = reports.map(report => ({
                ...report,
                access_level: 'read'
            }));
            
            return readOnlyReports;
        } catch (error) {
            console.error('ReportService: Error fetching shared reports', error);
            throw error;
        }
    }

    /**
     * Get sharing options for reports (authorities and groups)
     */
    async getSharingOptions(): Promise<SharingOptions> {
        console.log('ReportService: Fetching sharing options');
        try {
            const response: AxiosResponse = await apiClientAuth.get('/report/share.php?action=getSharingOptions');
            console.log('ReportService: Raw API response:', response);
            
            // Access the data directly from the response
            if (response.data && typeof response.data === 'object') {
                // Check if data is directly in response.data or in response.data.data
                const responseData = response.data.data || response.data;
                console.log('ReportService: Extracted data:', responseData);
                
                // Create a properly structured return object
                const result: SharingOptions = {
                    authorities: Array.isArray(responseData.authorities) ? responseData.authorities : [],
                    groups: Array.isArray(responseData.groups) ? responseData.groups : []
                };
                
                console.log('ReportService: Returning sharing options:', result);
                return result;
            }
            
            // Fallback if response structure is unexpected
            console.warn('ReportService: Unexpected response structure:', response.data);
            return { authorities: [], groups: [] };
        } catch (error) {
            console.error('ReportService: Error fetching sharing options', error);
            // Return empty arrays on error to prevent UI crashes
            return { authorities: [], groups: [] };
        }
    }

    /**
     * Get sharing information for a specific report
     * @param reportId Report ID
     */
    async getReportSharing(reportId: number): Promise<SharingInfo> {
        console.log(`ReportService: Fetching sharing info for report ${reportId}`);
        try {
            const response: AxiosResponse = await apiClientAuth.get(`/report/share.php?action=getReportSharing&report_id=${reportId}`);
            console.log(`ReportService: Raw API response for sharing info:`, response);
            
            // Log the raw data received from the API
            if (response.data) {
                console.log(`ReportService: Sharing info raw data:`, response.data);
            }
            
            // Access the data directly from the response
            const responseData = response.data || {};
            
            // Ensure we return a valid sharing info object even if API fails
            const defaultSharingInfo: SharingInfo = {
                report: {
                    id: reportId,
                    title: '',
                    content: '',
                    category_id: 0,
                    status: '',
                    priority: '',
                    due_date: null,
                    assigned_to: null,
                    tags: [],
                    location: ''
                },
                shared_with: [],
                visibility: 'private',
                groups: []
            };
            
            // If data is null/undefined, return default
            if (!responseData) {
                console.warn('ReportService: No data received from API');
                return defaultSharingInfo;
            }
            
            // Log what we're extracting from the response
            console.log(`ReportService: Extracted shared_with:`, responseData.shared_with);
            console.log(`ReportService: Extracted visibility:`, responseData.visibility);
            console.log(`ReportService: Extracted groups:`, responseData.groups);
            
            // Preserve the original shared_with array
            const sharedWith = Array.isArray(responseData.shared_with) ? responseData.shared_with : [];
            
            // Create the result object with proper typing
            const result: SharingInfo = {
                report: responseData.report || defaultSharingInfo.report,
                shared_from: responseData.shared_from,
                shared_with: sharedWith,
                visibility: responseData.visibility || 'private',
                groups: Array.isArray(responseData.groups) ? responseData.groups : []
            };
            
            console.log(`ReportService: Returning sharing info:`, result);
            return result;
        } catch (error) {
            console.error(`ReportService: Error fetching sharing info for report ${reportId}`, error);
            // Return a default object to prevent UI crashes
            return {
                report: {
                    id: reportId,
                    title: '',
                    content: '',
                    category_id: 0,
                    status: '',
                    priority: '',
                    due_date: null,
                    assigned_to: null,
                    tags: [],
                    location: ''
                },
                shared_with: [],
                visibility: 'private',
                groups: []
            };
        }
    }

    /**
     * Update sharing settings for a report
     * @param reportId Report ID
     * @param settings Sharing settings
     */
    async updateSharingSettings(reportId: number, settings: any) {
        console.log(`ReportService: Updating sharing settings for report ${reportId}`, settings);
        
        // Map visibility to visibility_type for backend
        const mappedSettings = { ...settings };
        if (settings.visibility) {
            mappedSettings.visibility_type = settings.visibility;
            delete mappedSettings.visibility;
        }
        
        // Log the data being sent to the backend
        const requestData = {
            report_id: reportId,
            ...mappedSettings
        };
        console.log('ReportService: Sending update with data:', requestData);
        
        try {
            const response: AxiosResponse = await apiClientAuth.post(
                '/report/share.php?action=updateSharingSettings', 
                requestData
            );
            
            console.log(`ReportService: Update response received:`, response.data);
            
            if (response.data && response.data.error) {
                console.error('ReportService: Backend returned error:', response.data.error);
                throw new Error(response.data.error);
            }
            
            return response.data;
        } catch (error) {
            console.error(`ReportService: Error updating sharing settings for report ${reportId}`, error);
            throw error;
        }
    }

    /**
     * Process custom fields from the API response
     * @param customFields Custom fields from API
     */
    private processCustomFields(customFields: any) {
        if (typeof customFields === 'string') {
            try {
                return JSON.parse(customFields);
            } catch (e) {
                console.error('ReportService: Error parsing custom fields', e);
                return [];
            }
        }
        return customFields;
    }

    // --- Report Sharing Methods ---

    /**
     * Share a report with another authority
     */
    async shareReport(reportId: number, authorityId: number, accessLevel: 'read' | 'edit') {
        try {
            const response = await apiClientAuth.post(
                '/report/share.php?action=shareReport',
                {
                    report_id: reportId,
                    authority_id: authorityId,
                    access_level: accessLevel,
                }
            );
            return response.data;
        } catch (error) {
            console.error('Error sharing report:', error);
            throw error;
        }
    }

    /**
     * Remove report sharing with an authority
     */
    async removeSharing(reportId: number, authorityId: number) {
        try {
            const response = await apiClientAuth.post(
                '/report/share.php?action=removeSharing',
                {
                    report_id: reportId,
                    authority_id: authorityId,
                }
            );
            return response.data;
        } catch (error) {
            console.error('Error removing report sharing:', error);
            throw error;
        }
    }

    /**
     * Set report visibility (public, private, specific_roles)
     */
    async setReportVisibility(
        reportId: number,
        visibility: 'public' | 'private' | 'specific_roles'
    ) {
        try {
            const response = await apiClientAuth.post(
                '/report/share.php?action=setReportVisibility',
                {
                    report_id: reportId,
                    visibility: visibility,
                }
            );
            return response.data;
        } catch (error) {
            console.error('Error setting report visibility:', error);
            throw error;
        }
    }

    /**
     * Add group access to a report
     */
    async addGroupAccess(reportId: number, groupIds: number[]) {
        try {
            const response = await apiClientAuth.post(
                '/report/share.php?action=addGroupAccess',
                {
                    report_id: reportId,
                    group_ids: groupIds,
                }
            );
            return response.data;
        } catch (error) {
            console.error('Error adding group access:', error);
            throw error;
        }
    }

    /**
     * Remove group access from a report
     */
    async removeGroupAccess(reportId: number, groupId: number) {
        try {
            const response = await apiClientAuth.post(
                '/report/share.php?action=removeGroupAccess',
                {
                    report_id: reportId,
                    group_id: groupId,
                }
            );
            return response.data;
        } catch (error) {
            console.error('Error removing group access:', error);
            throw error;
        }
    }
}

export default new ReportService();
