/**
 * K-Systems Mail System TypeScript Type Definitions
 *
 * This file contains all TypeScript interfaces and types for the mail system,
 * matching the backend database schema and API responses.
 */

// ============================================================================
// Core Mail Domain & Account Types
// ============================================================================

/**
 * Mail domain configuration
 * Represents domains available for creating mail accounts (e.g., @k-systems.app)
 */
export interface MailDomain {
  id: number;
  domain: string;
  domain_type: 'faction' | 'default';
  authority_id: number | null;
  is_active: boolean;
  verified: boolean;
  max_accounts: number;
  display_name: string | null;
  logo_url: string | null;
  total_accounts: number;
  total_mails_sent: number;
  created_at: string;
  updated_at: string;
}

/**
 * Mail account (mailbox)
 * Represents a complete email address that can send/receive mail
 */
export interface MailAccount {
  id: number;
  email: string;
  current_user_id: number | null;
  authority_id: number;
  account_type: 'personal' | 'company' | 'faction';
  is_active: boolean;
  is_locked: boolean;
  is_searchable: boolean;
  storage_used: number;
  storage_limit: number;
  last_login_at: string | null;
  created_at: string;
  updated_at: string;
}

// ============================================================================
// Mail Message Types
// ============================================================================

/**
 * Core mail message
 * Represents an individual email with all its metadata
 */
export interface Mail {
  id: number;
  message_id: string;
  thread_id: string | null;
  reply_to_id: number | null;
  from_address: string;
  from_user_id: number | null;
  from_name: string | null;
  to_addresses: string[];
  cc_addresses: string[] | null;
  bcc_addresses: string[] | null;
  subject: string;
  body_html: string;
  body_text: string | null;
  has_attachments: boolean;
  is_draft: boolean;
  is_sent: boolean;
  sent_at: string | null;
  priority: 'low' | 'normal' | 'high';
  authority_id: number;
  created_at: string;
  updated_at: string;
}

/**
 * Mail recipient data
 * User-specific data for each mail (read status, flags, labels, etc.)
 * One record per recipient per mail
 */
export interface MailRecipient {
  id: number;
  mail_id: number;
  recipient_address: string;
  recipient_user_id: number | null;
  recipient_type: 'to' | 'cc' | 'bcc';
  is_read: boolean;
  read_at: string | null;
  is_starred: boolean;
  is_important: boolean;
  is_deleted: boolean;
  deleted_at: string | null;
  folder_id: number | null;
  label_ids: number[] | null;
  assigned_to_user_id: number | null;
  assigned_at: string | null;
  assigned_by_user_id: number | null;
  status: 'new' | 'in_progress' | 'done' | 'archived';
  private_note: string | null;
  authority_id: number;
  created_at: string;
}

/**
 * Combined mail with recipient data
 * Used in list views where we need both mail content and user-specific flags
 */
export interface MailWithRecipient extends Mail {
  // User-specific flags from MailRecipient
  is_read: boolean;
  read_at: string | null;
  is_starred: boolean;
  is_important: boolean;
  is_deleted: boolean;
  folder_id: number | null;
  label_ids: number[] | null;
  status: 'new' | 'in_progress' | 'done' | 'archived';
  assigned_to_user_id: number | null;
  private_note: string | null;

  // Additional computed fields
  preview?: string;
  attachments?: MailAttachment[];
}

/**
 * Mail attachment
 * Files attached to emails
 */
export interface MailAttachment {
  id: number;
  mail_id: number;
  filename: string;
  original_filename: string;
  file_path: string;
  file_size: number;
  mime_type: string;
  is_safe: boolean;
  scan_status: 'pending' | 'clean' | 'infected';
  uploaded_at: string;
}

// ============================================================================
// Company Mailbox & Permissions
// ============================================================================

/**
 * Company mailbox
 * Shared mailboxes for teams/departments (e.g., support@, sales@)
 */
export interface CompanyMailbox {
  id: number;
  mail_account_id: number;
  authority_id: number;
  name: string;
  description: string | null;
  department: string | null;
  max_mail_addresses: number;
  current_mail_count: number;
  auto_reply_enabled: boolean;
  auto_reply_message: string | null;
  signature: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;

  // Joined data
  email?: string;
  permissions?: MailboxPermission;
}

/**
 * Mailbox permissions
 * Controls what users/groups can do with a shared mailbox
 */
export interface MailboxPermission {
  id: number;
  mailbox_id: number;
  user_id: number | null;
  group_id: number | null;
  authority_id: number;
  can_read: boolean;
  can_send: boolean;
  can_delete: boolean;
  can_assign: boolean;
  can_manage_members: boolean;
  can_manage_settings: boolean;
  added_at: string;
  added_by_user_id: number | null;
}

// ============================================================================
// Templates & Signatures
// ============================================================================

/**
 * Mail template
 * Reusable email templates with variables
 */
export interface MailTemplate {
  id: number;
  name: string;
  description: string | null;
  owner_user_id: number | null;
  owner_mailbox_id: number | null;
  authority_id: number;
  template_type: 'personal' | 'mailbox' | 'system';
  subject_template: string | null;
  body_template: string;
  available_variables: string[] | null;
  is_active: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string;
}

/**
 * Mail signature
 * Email signatures for accounts/users
 */
export interface MailSignature {
  id: number;
  user_id: number | null;
  mail_account_id: number | null;
  authority_id: number;
  name: string;
  signature_html: string;
  is_default: boolean;
  use_for_new: boolean;
  use_for_reply: boolean;
  created_at: string;
  updated_at: string;
}

// ============================================================================
// Contacts
// ============================================================================

/**
 * Contact
 * Address book entries for quick access and autocomplete
 */
export interface Contact {
  id: number;
  owner_user_id: number;
  authority_id: number;
  contact_type: 'personal' | 'company';
  email: string;
  secondary_emails: string[] | null;
  display_name: string;
  first_name: string | null;
  last_name: string | null;
  company: string | null;
  department: string | null;
  position: string | null;
  phone: string | null;
  mobile: string | null;
  address: string | null;
  website: string | null;
  tags: string[] | null;
  category: string | null;
  is_favorite: boolean;
  notes: string | null;
  avatar_url: string | null;
  created_at: string;
  updated_at: string;
}

// ============================================================================
// Folders & Labels
// ============================================================================

/**
 * Mail folder
 * Custom folders for organizing mail (e.g., Projects, Clients, Archive)
 */
export interface MailFolder {
  id: number;
  user_id: number;
  authority_id: number;
  name: string;
  color: string;
  icon: string | null;
  sort_order: number;
  is_system: boolean;
  created_at: string;

  // Computed
  unread_count?: number;
  total_count?: number;
}

/**
 * Mail label
 * Tags that can be applied to emails (multiple labels per mail)
 */
export interface MailLabel {
  id: number;
  user_id: number;
  authority_id: number;
  name: string;
  color: string;
  icon: string | null;
  sort_order: number;
  created_at: string;
}

// ============================================================================
// Form Types
// ============================================================================

/**
 * Compose mail form
 * Data structure for creating/sending new emails
 */
export interface ComposeMailForm {
  from_address: string;
  to_addresses: string[];
  cc_addresses?: string[];
  bcc_addresses?: string[];
  subject: string;
  body_html: string;
  priority?: 'low' | 'normal' | 'high';
  attachments?: File[];
  attachment_ids?: number[];
  draft_id?: number;
  in_reply_to?: number;
  reply_to_id?: number;
}

/**
 * Create mail account form
 * Data for creating a new mail account in the system
 */
export interface CreateMailAccountForm {
  email: string;
  password: string;
  account_type: 'personal' | 'company' | 'faction';
}

/**
 * Link mail account form
 * Data for linking an existing external mail account
 */
export interface LinkMailAccountForm {
  email: string;
  password: string;
}

/**
 * Update mail settings form
 * Data for updating mail account settings
 */
export interface UpdateMailSettingsForm {
  mail_account_id: number;
  is_searchable: boolean;
}

// ============================================================================
// API Response Types
// ============================================================================

/**
 * Generic API response wrapper
 * Standard response format from backend API
 */
export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  error?: string;
  message?: string;
}

/**
 * Paginated API response
 * For endpoints that return lists with pagination
 */
export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  per_page: number;
}

// ============================================================================
// Utility Types
// ============================================================================

/**
 * Mail priority levels
 */
export type MailPriority = 'low' | 'normal' | 'high';

/**
 * Mail recipient types
 */
export type RecipientType = 'to' | 'cc' | 'bcc';

/**
 * Mail status for task management
 */
export type MailStatus = 'new' | 'in_progress' | 'done' | 'archived';

/**
 * Account types
 */
export type AccountType = 'personal' | 'company' | 'faction';

/**
 * Template types
 */
export type TemplateType = 'personal' | 'mailbox' | 'system';

/**
 * Domain types
 */
export type DomainType = 'faction' | 'default';

/**
 * Contact types
 */
export type ContactType = 'personal' | 'company';

/**
 * Attachment scan status
 */
export type AttachmentScanStatus = 'pending' | 'clean' | 'infected';

/**
 * Partial mail for updates
 * Utility type for PATCH operations
 */
export type PartialMail = Partial<Mail>;

/**
 * Mail filters for list views
 * Used for filtering mail lists in the UI
 */
export interface MailFilters {
  folder?: string | number;
  labels?: number[];
  is_read?: boolean;
  is_starred?: boolean;
  is_important?: boolean;
  has_attachments?: boolean;
  status?: MailStatus;
  from_date?: string;
  to_date?: string;
  search?: string;
  assigned_to?: number;
}

/**
 * Mail sort options
 */
export interface MailSortOptions {
  field: 'created_at' | 'sent_at' | 'subject' | 'from_name' | 'priority';
  order: 'asc' | 'desc';
}

/**
 * Mail statistics
 * Overview stats for dashboards
 */
export interface MailStats {
  total: number;
  unread: number;
  starred: number;
  drafts: number;
  sent_today: number;
  storage_used: number;
  storage_limit: number;
}

/**
 * Mail thread
 * Group of related emails (conversation)
 */
export interface MailThread {
  thread_id: string;
  subject: string;
  participant_count: number;
  message_count: number;
  last_message_at: string;
  has_unread: boolean;
  is_starred: boolean;
  messages: MailWithRecipient[];
}

/**
 * Mail search result
 * Enhanced result with highlighting
 */
export interface MailSearchResult extends MailWithRecipient {
  relevance_score?: number;
  matched_fields?: string[];
  highlighted_subject?: string;
  highlighted_body?: string;
}

/**
 * Bulk action request
 * For performing actions on multiple mails
 */
export interface BulkMailAction {
  mail_ids: number[];
  action: 'mark_read' | 'mark_unread' | 'star' | 'unstar' | 'delete' | 'archive' | 'move' | 'label';
  folder_id?: number;
  label_ids?: number[];
}

/**
 * Mail quota information
 */
export interface MailQuota {
  storage_used: number;
  storage_limit: number;
  storage_percentage: number;
  message_count: number;
  attachment_count: number;
  attachment_size: number;
}

/**
 * Auto-complete suggestion
 * For recipient autocomplete in compose view
 */
export interface RecipientSuggestion {
  email: string;
  display_name: string;
  type: 'contact' | 'recent' | 'user';
  avatar_url?: string;
  frequency?: number;
}
