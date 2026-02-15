export interface Authority {
    id: number | null;
    name: string;
    display_name: string;
    description: string;
    active: boolean;
    created_at?: string;
    updated_at?: string;
    create_admin_account?: boolean;
    admin_username?: string;
    admin_email?: string;
    admin_password?: string;
}

export interface Feature {
    id: number;
    name: string;
    code: string;
    description: string;
    created_at?: string;
    updated_at?: string;
}

export interface AuthorityFeature {
    authority_id: number;
    feature_id: number;
    enabled: boolean;
    config?: Record<string, any>;
}