export interface User {
    id: string;
    username: string;
    email?: string;
    avatar?: string;
    roles: string[];
    // Permissions can be either:
    // - Bitmask format: {"employee": 7, "calendar": 23, ...} (new format from JWT)
    // - Legacy format: ["READ_EMPLOYEE", "WRITE_EMPLOYEE", ...] (old format)
    permissions: Record<string, number> | string[];
    active_features?: string[];
    mail_header: string;
    mail_footer: string;
    mail_header_neutral: string;
    mail_footer_neutral: string;
    signature: string;
    documentView: string;
    linked_employee: number;
    message_groups: number[];
    unreadMessagesCount: number;
    todayEventsCount: number;
    last_login: string;
    name: string;
    authority: string;
    authority_id?: number;
    authority_branding?: AuthorityBranding;
    // Legacy fields for backward compatibility
    groups?: string;
    allGroups?: Groups[];
    banned?: number;
    servicenumber?: number;
}

export interface Groups {
    id: number;
    name: string;
    description: string;
    sort_order: number;
}

export interface AuthorityBranding {
    logo_url?: string;
    primary_color: string;
    secondary_color: string;
    app_title?: string;
    default_background?: string;
}