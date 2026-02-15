# Mail System Integration Tests

This document contains a comprehensive checklist for testing all mail system functionality.

## Backend API Tests

### Account Management

#### GET action=getMyMailAccounts
- [ ] Returns empty array when user has no accounts
- [ ] Returns user's mail accounts with correct fields
- [ ] Filters by authority_id correctly
- [ ] Shows linked status correctly
- [ ] Includes storage usage and limits

#### POST action=createMailAccount
- [ ] Creates new account with valid data
- [ ] Validates email format
- [ ] Checks domain availability
- [ ] Validates password strength (min 8 chars)
- [ ] Prevents duplicate accounts
- [ ] Returns account ID and email
- [ ] Auto-links to user account
- [ ] Updates domain account count

#### POST action=linkMailAccount
- [ ] Links existing account to user
- [ ] Verifies password before linking
- [ ] Prevents linking already-linked accounts
- [ ] Updates current_user_id field
- [ ] Returns success with account data

#### POST action=unlinkMailAccount
- [ ] Unlinks account from user
- [ ] Sets current_user_id to NULL
- [ ] Preserves mail data
- [ ] Logs reason for unlinking
- [ ] Prevents unlinking if only account

#### POST action=changeMailPassword
- [ ] Validates old password
- [ ] Changes password successfully
- [ ] Validates new password strength
- [ ] Prevents using same password
- [ ] Updates password hash

#### POST action=updateMailSettings
- [ ] Updates display name
- [ ] Updates searchability setting
- [ ] Updates storage limits (admin only)
- [ ] Validates all settings
- [ ] Returns updated account data

### Mail Operations

#### GET action=getInbox
- [ ] Returns inbox mails for current user
- [ ] Excludes drafts, sent, and deleted mails
- [ ] Paginates results (default 50 per page)
- [ ] Orders by sent_at DESC
- [ ] Includes recipient flags (is_read, is_starred, etc.)
- [ ] Filters by authority_id
- [ ] Includes attachment count
- [ ] Shows preview text

#### GET action=getSent
- [ ] Returns sent mails (is_sent=true)
- [ ] Shows mails sent from user's accounts
- [ ] Excludes deleted mails
- [ ] Includes recipient list
- [ ] Orders by sent_at DESC

#### GET action=getDrafts
- [ ] Returns draft mails (is_draft=true)
- [ ] Shows only user's drafts
- [ ] Excludes deleted drafts
- [ ] Includes attachment count
- [ ] Orders by updated_at DESC

#### GET action=getStarred
- [ ] Returns starred mails (is_starred=true)
- [ ] Excludes deleted mails
- [ ] Includes all folders
- [ ] Orders by starred date DESC

#### GET action=getTrash
- [ ] Returns deleted mails (is_deleted=true)
- [ ] Shows deletion timestamp
- [ ] Includes all mail types
- [ ] Orders by deleted_at DESC

#### GET action=getMail
- [ ] Returns single mail by ID
- [ ] Includes all attachments
- [ ] Includes recipient list
- [ ] Validates user access rights
- [ ] Marks as read automatically
- [ ] Returns 404 if not found

#### POST action=sendMail
- [ ] Sends mail to recipients
- [ ] Validates to_addresses (at least one required)
- [ ] Validates email formats
- [ ] Validates subject (required)
- [ ] Validates body (required)
- [ ] Creates mail record
- [ ] Creates recipient records for each recipient
- [ ] Handles CC and BCC correctly
- [ ] Attaches files correctly
- [ ] Sets is_sent=true and sent_at timestamp
- [ ] Generates unique message_id
- [ ] Handles thread_id for replies
- [ ] Removes draft if draft_id provided
- [ ] Updates storage quota
- [ ] Emits socket event for recipients
- [ ] Returns mail_id

#### POST action=saveDraft
- [ ] Creates new draft if no draft_id
- [ ] Updates existing draft if draft_id provided
- [ ] Saves partial data (allows empty subject)
- [ ] Auto-saves every 30 seconds (client-side)
- [ ] Preserves attachments
- [ ] Sets is_draft=true
- [ ] Updates updated_at timestamp
- [ ] Returns draft_id

#### POST action=markAsRead
- [ ] Marks mail as read for current user
- [ ] Updates is_read flag in mail_recipients
- [ ] Sets read_at timestamp
- [ ] Allows marking as unread (is_read=false)
- [ ] Updates unread count
- [ ] Emits socket event for sync

#### POST action=markAsStarred
- [ ] Toggles starred status
- [ ] Updates is_starred flag in mail_recipients
- [ ] Works independently for each user
- [ ] Emits socket event for sync

#### POST action=deleteMail
- [ ] Soft deletes mail (is_deleted=true)
- [ ] Sets deleted_at timestamp
- [ ] Moves to trash folder
- [ ] Preserves mail data
- [ ] Can be restored later
- [ ] Updates unread count if unread

#### POST action=restoreMail
- [ ] Restores mail from trash
- [ ] Sets is_deleted=false
- [ ] Clears deleted_at timestamp
- [ ] Returns to original folder
- [ ] Updates unread count if unread

#### POST action=permanentDeleteMail
- [ ] Hard deletes mail record
- [ ] Deletes attachments from disk
- [ ] Deletes recipient records
- [ ] Updates storage quota
- [ ] Cannot be undone
- [ ] Requires admin permission or mail older than 30 days

#### POST action=bulkMarkAsRead
- [ ] Accepts array of mail_ids
- [ ] Marks all as read in single query
- [ ] Returns success count
- [ ] Updates unread count
- [ ] Handles invalid IDs gracefully

#### POST action=bulkDelete
- [ ] Accepts array of mail_ids
- [ ] Soft deletes all in single query
- [ ] Returns success count
- [ ] Updates unread count
- [ ] Handles invalid IDs gracefully

#### POST action=bulkMove
- [ ] Accepts array of mail_ids and folder_id
- [ ] Moves all to specified folder
- [ ] Returns success count
- [ ] Validates folder ownership
- [ ] Handles invalid IDs gracefully

### Attachments

#### POST action=uploadAttachment
- [ ] Accepts file upload (multipart/form-data)
- [ ] Validates file type (PDF, JPG, PNG only)
- [ ] Validates file size (max 10MB per file)
- [ ] Checks total mail attachment limit (25MB)
- [ ] Checks storage quota
- [ ] Generates unique filename
- [ ] Stores file in correct directory
- [ ] Creates attachment record
- [ ] Links to draft if draft_id provided
- [ ] Updates storage quota
- [ ] Returns attachment_id and metadata
- [ ] Scans for viruses (status: pending)

#### GET action=downloadAttachment
- [ ] Downloads file by attachment_id
- [ ] Validates user access (must be sender or recipient)
- [ ] Sets correct Content-Type header
- [ ] Sets Content-Disposition for download
- [ ] Streams file content
- [ ] Returns 404 if file not found
- [ ] Returns 403 if no access

#### DELETE action=deleteAttachment
- [ ] Deletes attachment record
- [ ] Removes file from disk
- [ ] Updates mail storage quota
- [ ] Only allows deletion by uploader
- [ ] Prevents deletion if mail sent
- [ ] Returns success status

#### GET action=getAttachments
- [ ] Returns all attachments for a mail
- [ ] Includes file metadata (size, type, name)
- [ ] Shows scan status
- [ ] Orders by uploaded_at ASC
- [ ] Validates user access to mail

### Company Mailboxes

#### GET action=getCompanyMailboxes
- [ ] Returns all company mailboxes
- [ ] Filters by authority_id
- [ ] Shows only mailboxes user has access to
- [ ] Includes permission level for user
- [ ] Shows member count
- [ ] Shows mail count
- [ ] Includes email address

#### POST action=createCompanyMailbox
- [ ] Creates new company mailbox
- [ ] Requires ADMIN_MAIL permission
- [ ] Creates mail account first
- [ ] Links to company mailbox record
- [ ] Sets creator as admin member
- [ ] Validates mailbox name
- [ ] Returns mailbox_id and email

#### PUT action=updateCompanyMailbox
- [ ] Updates mailbox settings
- [ ] Requires can_manage_settings permission
- [ ] Updates name, description, department
- [ ] Updates auto-reply settings
- [ ] Updates signature
- [ ] Validates all fields
- [ ] Returns updated mailbox

#### POST action=addMailboxPermission
- [ ] Adds user to mailbox
- [ ] Requires can_manage_members permission
- [ ] Sets permission flags (can_read, can_send, etc.)
- [ ] Prevents duplicate memberships
- [ ] Logs who added the member
- [ ] Returns permission record

#### PUT action=updateMailboxPermission
- [ ] Updates user permissions
- [ ] Requires can_manage_members permission
- [ ] Updates permission flags
- [ ] Validates at least one admin remains
- [ ] Returns updated permission

#### DELETE action=removeMailboxPermission
- [ ] Removes user from mailbox
- [ ] Requires can_manage_members permission
- [ ] Validates at least one admin remains
- [ ] Deletes permission record
- [ ] Returns success status

### Folders & Labels

#### GET action=getFolders
- [ ] Returns user's custom folders
- [ ] Excludes system folders (inbox, sent, etc.)
- [ ] Includes unread count per folder
- [ ] Includes total count per folder
- [ ] Orders by sort_order ASC

#### POST action=createFolder
- [ ] Creates new custom folder
- [ ] Validates folder name (unique per user)
- [ ] Sets default color if not provided
- [ ] Sets sort_order automatically
- [ ] Returns folder_id and data

#### PUT action=updateFolder
- [ ] Updates folder name
- [ ] Updates folder color
- [ ] Updates folder icon
- [ ] Updates sort_order
- [ ] Validates uniqueness
- [ ] Returns updated folder

#### DELETE action=deleteFolder
- [ ] Deletes folder
- [ ] Moves mails to inbox (not trash)
- [ ] Prevents deleting system folders
- [ ] Returns success status

#### POST action=moveToFolder
- [ ] Moves mail to specified folder
- [ ] Updates folder_id in mail_recipients
- [ ] Allows moving to NULL (inbox)
- [ ] Validates folder ownership
- [ ] Returns success status

#### GET action=getLabels
- [ ] Returns user's labels
- [ ] Orders by sort_order ASC
- [ ] Includes usage count
- [ ] Includes color and icon

#### POST action=createLabel
- [ ] Creates new label
- [ ] Validates label name (unique per user)
- [ ] Sets default color if not provided
- [ ] Returns label_id and data

#### POST action=applyLabel
- [ ] Applies label to mail
- [ ] Adds label_id to mail_recipients.label_ids array
- [ ] Prevents duplicate labels
- [ ] Allows multiple labels per mail
- [ ] Returns success status

#### DELETE action=removeLabel
- [ ] Removes label from mail
- [ ] Removes label_id from array
- [ ] Does not delete label record
- [ ] Returns success status

### Templates & Signatures

#### GET action=getTemplates
- [ ] Returns user's personal templates
- [ ] Returns mailbox templates if has access
- [ ] Returns system templates
- [ ] Filters by authority_id
- [ ] Orders by sort_order ASC

#### POST action=createTemplate
- [ ] Creates new template
- [ ] Sets owner_user_id or owner_mailbox_id
- [ ] Validates template name
- [ ] Stores subject and body templates
- [ ] Stores available_variables array
- [ ] Returns template_id and data

#### POST action=renderTemplate
- [ ] Renders template with variables
- [ ] Replaces {{variable}} placeholders
- [ ] Returns rendered subject and body
- [ ] Validates all required variables provided

#### DELETE action=deleteTemplate
- [ ] Deletes template
- [ ] Only allows deletion by owner
- [ ] Cannot delete system templates
- [ ] Returns success status

#### GET action=getSignatures
- [ ] Returns user's signatures
- [ ] Returns account signatures if linked
- [ ] Filters by authority_id
- [ ] Shows default signature
- [ ] Shows use_for_new and use_for_reply flags

#### POST action=createSignature
- [ ] Creates new signature
- [ ] Validates signature name
- [ ] Stores HTML signature
- [ ] Sets default flag if requested
- [ ] Unsets other defaults if setting new default
- [ ] Returns signature_id and data

#### PUT action=updateSignature
- [ ] Updates signature name
- [ ] Updates signature HTML
- [ ] Updates default flag
- [ ] Updates use_for_new and use_for_reply flags
- [ ] Returns updated signature

#### DELETE action=deleteSignature
- [ ] Deletes signature
- [ ] Only allows deletion by owner
- [ ] Cannot delete if only signature and is default
- [ ] Returns success status

### Contacts

#### GET action=getContacts
- [ ] Returns user's personal contacts
- [ ] Filters by authority_id
- [ ] Orders by display_name ASC
- [ ] Shows favorite status
- [ ] Includes all contact fields

#### GET action=searchContacts
- [ ] Searches by query string
- [ ] Searches in email, display_name, first_name, last_name
- [ ] Returns matching contacts
- [ ] Limits results to 10 by default
- [ ] Orders by frequency if available

#### GET action=getGlobalDirectory
- [ ] Returns searchable users in same authority
- [ ] Filters by is_searchable=true
- [ ] Searches in email, name
- [ ] Excludes current user
- [ ] Returns user email and display name

#### POST action=createContact
- [ ] Creates new contact
- [ ] Validates email format
- [ ] Validates required fields (email, display_name)
- [ ] Prevents duplicate emails per user
- [ ] Stores all contact fields
- [ ] Returns contact_id and data

#### PUT action=updateContact
- [ ] Updates contact fields
- [ ] Validates email format
- [ ] Validates uniqueness
- [ ] Updates updated_at timestamp
- [ ] Returns updated contact

#### DELETE action=deleteContact
- [ ] Deletes contact record
- [ ] Only allows deletion by owner
- [ ] Returns success status

#### POST action=toggleFavorite
- [ ] Toggles is_favorite flag
- [ ] Returns new favorite status

#### GET action=exportContacts
- [ ] Exports contacts to vCard format
- [ ] Includes all contact fields
- [ ] Returns file download
- [ ] Filters by authority_id

#### POST action=importContacts
- [ ] Accepts vCard or CSV file
- [ ] Parses contact data
- [ ] Validates email formats
- [ ] Creates contacts in batch
- [ ] Skips duplicates
- [ ] Returns success count and errors

### Domains

#### GET action=getAvailableDomains
- [ ] Returns active domains for authority
- [ ] Returns default domains (k-systems.app, ls.k-systems.app)
- [ ] Shows max_accounts limit
- [ ] Shows current account count
- [ ] Filters by is_active=true

#### POST action=checkMailAvailability
- [ ] Checks if email is available
- [ ] Returns available=true/false
- [ ] Suggests alternatives if taken
- [ ] Validates email format
- [ ] Checks across all authorities

## Frontend Component Tests

### MailView.vue

#### Layout
- [ ] Three-column layout renders correctly
- [ ] Sidebar shows on left (folders)
- [ ] Mail list shows in center
- [ ] Mail detail shows on right
- [ ] Responsive design works (mobile stacks vertically)

#### Folder Sidebar
- [ ] Shows system folders (Inbox, Sent, Drafts, Starred, Trash)
- [ ] Shows custom folders below system folders
- [ ] Shows unread count badges
- [ ] Highlights current folder
- [ ] Click switches folder and loads mails

#### Mail List
- [ ] Virtual scrolling works with 1000+ mails
- [ ] Shows mail preview cards
- [ ] Shows sender name/email
- [ ] Shows subject
- [ ] Shows preview text (first 100 chars)
- [ ] Shows date/time formatted correctly
- [ ] Shows unread indicator (bold text or dot)
- [ ] Shows attachment icon if has_attachments
- [ ] Shows star icon (filled if starred)
- [ ] Click opens mail in detail view

#### Search
- [ ] Search bar filters mails in real-time
- [ ] Searches in subject, from, body
- [ ] Shows "No results" message
- [ ] Clear button resets filter

#### Compose Button
- [ ] Floating action button visible
- [ ] Click opens compose dialog
- [ ] Icon shows correctly (mdi-pencil)

#### Unread Badge
- [ ] Shows unread count in top bar
- [ ] Updates when mail marked as read
- [ ] Updates when new mail received
- [ ] Shows 0 when no unread mails

### MailList.vue

#### Virtual Scrolling
- [ ] Only renders visible items
- [ ] Smoothly scrolls through 1000+ mails
- [ ] Maintains scroll position on update
- [ ] Shows loading indicator while fetching

#### Multi-Select
- [ ] Checkboxes appear on hover or select
- [ ] Click checkbox selects mail
- [ ] Shift+click selects range
- [ ] Ctrl/Cmd+click toggles individual
- [ ] Select all checkbox works
- [ ] Selection count shown in toolbar

#### Bulk Actions Toolbar
- [ ] Appears when mails selected
- [ ] Shows selection count
- [ ] Mark as read button works
- [ ] Mark as unread button works
- [ ] Star button works
- [ ] Delete button works
- [ ] Move to folder dropdown works
- [ ] Apply label dropdown works
- [ ] Deselect all button works

#### Mail Preview Card
- [ ] Shows sender avatar or initials
- [ ] Shows sender name (bold if unread)
- [ ] Shows subject (bold if unread)
- [ ] Shows preview text
- [ ] Shows date/time
- [ ] Shows attachment icon
- [ ] Shows priority icon (if high/low)
- [ ] Shows star (filled if starred)
- [ ] Hover shows actions (archive, delete, etc.)

#### Context Menu
- [ ] Right-click opens context menu
- [ ] Mark as read/unread option
- [ ] Star/unstar option
- [ ] Move to folder submenu
- [ ] Apply label submenu
- [ ] Delete option
- [ ] Forward option

### MailDetail.vue

#### Header
- [ ] Shows sender name and email
- [ ] Shows recipient list (To, CC, BCC)
- [ ] Shows subject
- [ ] Shows date/time
- [ ] Shows priority badge (if high/low)

#### Body
- [ ] Renders HTML safely (sanitized)
- [ ] Shows images (with load confirmation)
- [ ] Preserves formatting
- [ ] Links are clickable
- [ ] No script execution

#### Attachments
- [ ] Lists all attachments
- [ ] Shows file name and size
- [ ] Shows file type icon
- [ ] Download button works
- [ ] Preview button works (for images/PDFs)

#### Actions
- [ ] Reply button opens compose with pre-filled To
- [ ] Reply All includes all recipients
- [ ] Forward button opens compose with body and attachments
- [ ] Star button toggles starred status
- [ ] Delete button moves to trash
- [ ] Mark as unread button works
- [ ] Move to folder button works
- [ ] Print button works

#### Mark as Read
- [ ] Automatically marks as read when opened
- [ ] Updates unread count
- [ ] Updates mail list (removes bold)

#### Thread View
- [ ] Shows previous messages in thread
- [ ] Collapses old messages by default
- [ ] Click expands message
- [ ] Shows conversation flow

### MailComposeDialog.vue

#### Dialog
- [ ] Opens as modal dialog
- [ ] Full-screen on mobile
- [ ] Close button works
- [ ] ESC key closes (with confirmation if has content)

#### From Field
- [ ] Dropdown shows user's mail accounts
- [ ] Default account pre-selected
- [ ] Can switch accounts

#### To/CC/BCC Fields
- [ ] Text input with chips
- [ ] Autocomplete shows contacts
- [ ] Autocomplete searches global directory
- [ ] Enter key adds email chip
- [ ] Shows CC/BCC buttons to reveal fields
- [ ] Validates email format
- [ ] Shows error for invalid emails
- [ ] Can remove chips with X button

#### Subject Field
- [ ] Text input
- [ ] Shows error if empty on send
- [ ] Updates draft on change

#### Rich Text Editor
- [ ] TipTap editor renders
- [ ] Bold, italic, underline work
- [ ] Font size selector works
- [ ] Font color picker works
- [ ] Alignment buttons work
- [ ] Bullet list works
- [ ] Numbered list works
- [ ] Link button works
- [ ] Image upload works
- [ ] Undo/redo works

#### Attachments
- [ ] File picker button works
- [ ] Drag and drop works
- [ ] Shows upload progress bar
- [ ] Shows attached files list
- [ ] Shows file name, size, type
- [ ] Remove button deletes attachment
- [ ] Validates file type (PDF, JPG, PNG)
- [ ] Validates file size (max 10MB)
- [ ] Validates total size (max 25MB)
- [ ] Shows error messages

#### Priority
- [ ] Dropdown shows Low, Normal, High
- [ ] Default is Normal
- [ ] Icon shows in sent mail

#### Template Dropdown
- [ ] Shows user's templates
- [ ] Shows mailbox templates
- [ ] Shows system templates
- [ ] Click applies template to subject/body
- [ ] Confirms before replacing existing content

#### Signature Dropdown
- [ ] Shows user's signatures
- [ ] Click appends signature to body
- [ ] Default signature auto-applied (configurable)

#### Auto-Save
- [ ] Saves draft every 30 seconds
- [ ] Shows "Saving..." indicator
- [ ] Shows "Saved at HH:MM" timestamp
- [ ] Saves on content change (debounced)

#### Send Button
- [ ] Validates form before sending
- [ ] Shows validation errors
- [ ] Disables while sending
- [ ] Shows loading spinner
- [ ] Shows success message
- [ ] Closes dialog on success
- [ ] Keeps open on error

#### Save as Draft Button
- [ ] Saves current state as draft
- [ ] Shows success message
- [ ] Keeps dialog open
- [ ] Updates draft_id for updates

#### Discard Button
- [ ] Confirms before discarding
- [ ] Deletes draft if exists
- [ ] Closes dialog

### Mail Settings Views

#### Accounts Tab
- [ ] Lists user's mail accounts
- [ ] Shows email, storage used, status
- [ ] Create account button opens dialog
- [ ] Link account button opens dialog
- [ ] Unlink button shows confirmation
- [ ] Change password button opens dialog
- [ ] Settings button opens drawer

#### Create Account Dialog
- [ ] Domain dropdown shows available domains
- [ ] Username input validates format (a-z, 0-9, -, _)
- [ ] Password input validates strength
- [ ] Confirm password validates match
- [ ] Availability check shows green/red icon
- [ ] Suggests alternatives if taken
- [ ] Create button sends request
- [ ] Shows success message
- [ ] Auto-links to user

#### Link Account Dialog
- [ ] Email input validates format
- [ ] Password input required
- [ ] Link button verifies password
- [ ] Shows error if password wrong
- [ ] Shows success message
- [ ] Adds to account list

#### Company Mailboxes Tab
- [ ] Lists company mailboxes user has access to
- [ ] Shows mailbox name, email, members
- [ ] Create button requires ADMIN_MAIL permission
- [ ] Click mailbox opens detail view

#### Create Company Mailbox Dialog
- [ ] Name input required
- [ ] Description textarea optional
- [ ] Department input optional
- [ ] Domain dropdown
- [ ] Username input
- [ ] Create button sends request
- [ ] Shows success message
- [ ] Adds creator as admin

#### Mailbox Detail View
- [ ] Shows mailbox info (name, email, description)
- [ ] Shows members list with permissions
- [ ] Add member button opens dialog
- [ ] Edit member button opens dialog
- [ ] Remove member button shows confirmation
- [ ] Settings tab shows auto-reply, signature
- [ ] Save button updates settings

#### Contacts Tab
- [ ] Lists all contacts
- [ ] Search bar filters contacts
- [ ] Shows favorite contacts first
- [ ] Add contact button opens dialog
- [ ] Click contact opens detail/edit
- [ ] Star icon toggles favorite
- [ ] Delete button shows confirmation
- [ ] Export button downloads vCard
- [ ] Import button opens file picker

#### Contact Form
- [ ] Email input required, validates format
- [ ] Display name input required
- [ ] First name, last name optional
- [ ] Company, department, position optional
- [ ] Phone, mobile optional
- [ ] Address, website optional
- [ ] Tags input (chips)
- [ ] Category dropdown
- [ ] Notes textarea
- [ ] Avatar upload
- [ ] Save button creates/updates contact

#### Templates Tab
- [ ] Lists user's templates
- [ ] Add template button opens dialog
- [ ] Click template opens editor
- [ ] Delete button shows confirmation
- [ ] Preview button shows rendered template

#### Template Editor
- [ ] Name input required
- [ ] Description textarea optional
- [ ] Subject template input (supports {{variables}})
- [ ] Body template (rich text editor)
- [ ] Available variables list (chips)
- [ ] Add variable button inserts placeholder
- [ ] Preview section shows rendered result
- [ ] Save button creates/updates template

#### Signatures Tab
- [ ] Lists user's signatures
- [ ] Shows default indicator
- [ ] Add signature button opens editor
- [ ] Click signature opens editor
- [ ] Delete button shows confirmation
- [ ] Set as default button works

#### Signature Editor
- [ ] Name input required
- [ ] Signature editor (rich text)
- [ ] Set as default checkbox
- [ ] Use for new mails checkbox
- [ ] Use for replies checkbox
- [ ] Preview section
- [ ] Save button creates/updates signature

## Socket Integration Tests

### Connection

#### Initial Connection
- [ ] Socket connects on app load
- [ ] JWT token sent in auth parameter
- [ ] Connection established within 5 seconds
- [ ] Retry logic works on failure

#### Authentication
- [ ] Token validated on server
- [ ] Invalid token disconnects socket
- [ ] User ID extracted from token
- [ ] Authority ID extracted from token

#### Reconnection
- [ ] Auto-reconnects on disconnect
- [ ] Exponential backoff (1s, 2s, 4s, 8s, max 30s)
- [ ] Resumes after network recovery
- [ ] Re-authenticates on reconnect

### Events

#### new_mail Event
- [ ] Emitted when user receives new mail
- [ ] Includes mail_id, from, subject, preview
- [ ] Triggers desktop notification
- [ ] Plays notification sound
- [ ] Updates unread count badge
- [ ] Refreshes inbox if currently viewing
- [ ] Shows toast notification

#### mail_read Event
- [ ] Emitted when mail marked as read
- [ ] Syncs across all user's tabs/devices
- [ ] Updates mail list UI
- [ ] Updates unread count

#### mail_starred Event
- [ ] Emitted when mail starred/unstarred
- [ ] Syncs across all user's tabs/devices
- [ ] Updates mail list UI
- [ ] Updates starred folder count

#### mail_deleted Event
- [ ] Emitted when mail deleted
- [ ] Removes from current view
- [ ] Updates folder counts
- [ ] Syncs across tabs

#### mailbox_activity Event
- [ ] Emitted on company mailbox activity
- [ ] Shows toast notification
- [ ] Includes actor name and action
- [ ] Only sent to mailbox members

#### draft_saved Event
- [ ] Emitted when draft auto-saved
- [ ] Syncs across tabs
- [ ] Updates draft list
- [ ] Shows "saved" indicator

### Notifications

#### Desktop Notifications
- [ ] Permission requested on first load
- [ ] Notification shows sender and subject
- [ ] Notification shows preview text
- [ ] Notification shows app icon
- [ ] Click notification opens mail
- [ ] Works when app in background
- [ ] Respects browser notification settings

#### Sound Notifications
- [ ] Sound plays on new mail
- [ ] Sound file loads correctly (/sounds/notification.mp3)
- [ ] Volume appropriate (not too loud)
- [ ] Can be muted in settings
- [ ] Different sound for high priority (optional)

#### Badge Updates
- [ ] Unread count updates in real-time
- [ ] Badge shown in sidebar folders
- [ ] Badge shown in browser tab title
- [ ] Badge shown in app icon (PWA)

#### Toast Notifications
- [ ] Shows on new mail
- [ ] Shows on mailbox activity
- [ ] Shows on errors
- [ ] Auto-dismisses after 5 seconds
- [ ] Can be manually dismissed
- [ ] Shows action button (e.g., "View")

## Multi-Tenant Tests

### Data Isolation

#### Authority Filtering
- [ ] User only sees mails from their authority
- [ ] SQL queries include WHERE authority_id = :authority_id
- [ ] No cross-authority data leaks
- [ ] Admin cannot see other authorities (unless super admin)

#### Company Mailboxes
- [ ] Filtered by authority_id
- [ ] Users only see mailboxes they have access to
- [ ] Permissions checked per authority

#### Contacts
- [ ] Separated by authority_id
- [ ] User cannot see other authority's contacts
- [ ] Global directory limited to same authority

#### Templates
- [ ] Personal templates scoped to user
- [ ] Mailbox templates scoped to mailbox authority
- [ ] System templates visible to all (if authority_id NULL)

#### Global Directory
- [ ] Shows only users in same authority
- [ ] Filters by is_searchable=true
- [ ] Excludes disabled users

### Permissions

#### READ_MAIL Permission
- [ ] Required to view inbox
- [ ] Required to read mails
- [ ] Required to download attachments
- [ ] Checked on all GET endpoints

#### WRITE_MAIL Permission
- [ ] Required to send mails
- [ ] Required to create drafts
- [ ] Required to upload attachments
- [ ] Checked on POST/PUT endpoints

#### ADMIN_MAIL Permission
- [ ] Required to create company mailboxes
- [ ] Required to manage mailbox members
- [ ] Required to view all authority mails (optional)
- [ ] Required to permanent delete mails

#### Mailbox Permissions
- [ ] can_read - View mailbox mails
- [ ] can_send - Send from mailbox
- [ ] can_delete - Delete mailbox mails
- [ ] can_assign - Assign mails to users
- [ ] can_manage_members - Add/remove members
- [ ] can_manage_settings - Update mailbox settings

## Performance Tests

### Load Times
- [ ] Inbox loads <500ms with 100 mails
- [ ] Inbox loads <2s with 1000 mails
- [ ] Single mail loads <200ms
- [ ] Attachments list loads <100ms

### Search Performance
- [ ] Search filters <100ms for 1000 mails (client-side)
- [ ] Server search <500ms for 10,000 mails
- [ ] Search with multiple criteria <1s

### Virtual Scrolling
- [ ] Handles 10,000 mails without lag
- [ ] Smooth scrolling at 60fps
- [ ] Memory usage stable (<100MB for list)

### File Upload
- [ ] Shows progress bar during upload
- [ ] Large files (10MB) upload <10s
- [ ] Multiple file uploads work in parallel
- [ ] Upload can be cancelled

### Auto-Save
- [ ] Debounced to avoid excessive requests
- [ ] Saves every 30 seconds if changed
- [ ] Doesn't block UI while saving
- [ ] Handles concurrent edits gracefully

### Real-time Updates
- [ ] Socket events processed <100ms
- [ ] UI updates immediately on event
- [ ] No UI flicker during updates
- [ ] Batch updates for multiple events

## Security Tests

### HTML Sanitization
- [ ] Removes <script> tags
- [ ] Removes javascript: URLs
- [ ] Removes event handlers (onclick, onerror, etc.)
- [ ] Removes <iframe> tags
- [ ] Removes <object> and <embed> tags
- [ ] Preserves safe HTML (p, div, span, etc.)
- [ ] Preserves styles (with CSS sanitization)

### SQL Injection Prevention
- [ ] All queries use PDO prepared statements
- [ ] Parameters properly bound
- [ ] No raw SQL concatenation
- [ ] Tested with malicious inputs (' OR '1'='1, etc.)

### File Upload Security
- [ ] Rejects executable files (.exe, .sh, .php, etc.)
- [ ] Validates MIME type (not just extension)
- [ ] Generates unique filenames (prevents overwrite)
- [ ] Stores outside webroot (prevents direct access)
- [ ] Checks file content signature
- [ ] Limits file size (10MB per file)
- [ ] Limits total attachments (25MB per mail)

### JWT Token Validation
- [ ] Token validated on all API requests
- [ ] Invalid token returns 401 Unauthorized
- [ ] Expired token returns 401 Unauthorized
- [ ] Token signature verified
- [ ] Token payload extracted correctly
- [ ] User ID and authority ID verified

### Access Control
- [ ] Attachment download checks ownership
- [ ] Mail detail checks recipient status
- [ ] Company mailbox checks permissions
- [ ] Contact access checks ownership
- [ ] Template access checks ownership or mailbox permission
- [ ] Folder access checks ownership

### XSS Prevention
- [ ] All user input sanitized
- [ ] HTML encoded in templates
- [ ] Vue templates auto-escape {{ }}
- [ ] v-html only used with sanitized content
- [ ] No eval() or Function() calls

### CSRF Protection
- [ ] JWT token acts as CSRF token
- [ ] All mutations require POST/PUT/DELETE
- [ ] GET requests are read-only
- [ ] Token required in Authorization header

## Edge Cases & Error Handling

### Empty States
- [ ] Empty inbox shows friendly message
- [ ] No contacts shows "Add your first contact"
- [ ] No templates shows suggestion to create
- [ ] No company mailboxes shows create button

### Network Errors
- [ ] Shows error message on API failure
- [ ] Retry button appears
- [ ] Offline indicator shows when no connection
- [ ] Queues actions for when online (optional)

### Validation Errors
- [ ] Required field errors shown inline
- [ ] Email format errors shown
- [ ] File size errors shown
- [ ] Password strength errors shown

### Quota Limits
- [ ] Shows warning at 80% storage
- [ ] Prevents upload at 100% storage
- [ ] Shows current usage in settings
- [ ] Admin can increase limits

### Concurrent Edits
- [ ] Draft auto-save handles concurrent edits
- [ ] Last save wins (no conflict resolution needed)
- [ ] Shows warning if edited in another tab

### Large Attachments
- [ ] Progress bar shows during upload
- [ ] Can cancel upload mid-way
- [ ] Timeout after 60 seconds
- [ ] Retry on network error

### Thread Handling
- [ ] Reply includes original message_id
- [ ] Thread_id groups related mails
- [ ] Thread view shows all messages
- [ ] Handles long threads (100+ messages)

### Internationalization
- [ ] German translations complete
- [ ] Date formats use locale (de-DE)
- [ ] Number formats use locale
- [ ] Error messages translated

## Browser Compatibility

### Desktop Browsers
- [ ] Chrome 90+ works correctly
- [ ] Firefox 88+ works correctly
- [ ] Safari 14+ works correctly
- [ ] Edge 90+ works correctly

### Mobile Browsers
- [ ] Chrome Android works correctly
- [ ] Safari iOS works correctly
- [ ] Responsive design adapts to screen size

### Features by Browser
- [ ] Desktop notifications (supported in all modern)
- [ ] File upload (supported in all)
- [ ] Rich text editor (supported in all)
- [ ] WebSockets (supported in all modern)

## Deployment & Configuration

### Environment Variables
- [ ] VITE_API_URL configured correctly
- [ ] VITE_SOCKET_URL configured correctly
- [ ] Backend JWT_SECRET configured
- [ ] Socket server JWT_SECRET matches backend

### Database Setup
- [ ] All tables created
- [ ] Indexes applied
- [ ] Foreign keys set up
- [ ] Triggers working

### File Storage
- [ ] Upload directory exists and writable
- [ ] Correct permissions (755 for dirs, 644 for files)
- [ ] Sufficient disk space
- [ ] Backup strategy in place

### Socket Server
- [ ] Running on correct port (3001)
- [ ] JWT_SECRET matches backend
- [ ] Handles reconnections
- [ ] Logs errors

### Feature Toggle
- [ ] 'mail' feature enabled for authority
- [ ] Feature check works in frontend
- [ ] Disables mail if feature not enabled

## Final Integration Test Scenarios

### Scenario 1: New User Sends First Mail
1. [ ] User creates mail account
2. [ ] Account auto-links to user
3. [ ] User opens mail view
4. [ ] Clicks compose button
5. [ ] Selects from address
6. [ ] Enters recipient (with autocomplete)
7. [ ] Enters subject and body
8. [ ] Attaches file
9. [ ] Clicks send
10. [ ] Mail appears in sent folder
11. [ ] Recipient receives notification
12. [ ] Recipient sees mail in inbox

### Scenario 2: User Manages Company Mailbox
1. [ ] Admin creates company mailbox
2. [ ] Admin adds team member
3. [ ] Sets permissions (read, send)
4. [ ] Team member sees mailbox in sidebar
5. [ ] Team member sends mail from mailbox
6. [ ] Mail appears in mailbox sent folder
7. [ ] All members see the sent mail
8. [ ] Admin removes team member
9. [ ] Team member no longer sees mailbox

### Scenario 3: Real-time Synchronization
1. [ ] User A opens mail app in two tabs
2. [ ] Tab 1: Mark mail as read
3. [ ] Tab 2: Mail updates to read immediately
4. [ ] User B sends mail to User A
5. [ ] User A gets desktop notification
6. [ ] User A's inbox updates without refresh
7. [ ] Unread count badge updates

### Scenario 4: Draft Auto-Save & Recovery
1. [ ] User clicks compose
2. [ ] Starts typing subject and body
3. [ ] Auto-save triggers after 30 seconds
4. [ ] User closes dialog
5. [ ] User reopens mail view
6. [ ] Draft appears in drafts folder
7. [ ] User clicks draft
8. [ ] Content restored in compose dialog
9. [ ] User completes and sends
10. [ ] Draft deleted automatically

### Scenario 5: File Attachment Workflow
1. [ ] User composes mail
2. [ ] Attaches PDF (3MB)
3. [ ] Attaches image (5MB)
4. [ ] Attaches second image (8MB)
5. [ ] Upload progress shows for each
6. [ ] All three appear in attachment list
7. [ ] User removes PDF
8. [ ] Sends mail
9. [ ] Recipient downloads attachments
10. [ ] Files download correctly

## Test Completion Checklist

- [ ] All backend endpoints tested
- [ ] All frontend components tested
- [ ] Socket events tested
- [ ] Multi-tenant isolation verified
- [ ] Permissions enforced
- [ ] Performance acceptable
- [ ] Security measures in place
- [ ] Edge cases handled
- [ ] Error messages clear
- [ ] Browser compatibility verified
- [ ] Environment configured
- [ ] Documentation complete

---

## Notes for Testers

- Use developer tools network tab to verify API calls
- Use Redux DevTools or Vue DevTools to inspect state
- Use socket.io client debugger to monitor events
- Test with different user roles and permissions
- Test with large datasets (import test data)
- Test with slow network (throttle in DevTools)
- Test with various file types and sizes
- Clear cache and test fresh installation
- Test upgrade path if updating existing system
