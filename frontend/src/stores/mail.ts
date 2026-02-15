// src/stores/mail.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiClientAuth } from '@/api'
import type { Mail, MailWithRecipient, MailFolder, MailAccount, MailAttachment, BulkMailAction } from '@/types/Mail'

/**
 * Mail Store
 * Manages mail system state including accounts, mails, folders, and all mail operations
 */
export const useMailStore = defineStore('mail', () => {
  // ============================================================================
  // State
  // ============================================================================

  const currentAccount = ref<MailAccount | null>(null)
  const mails = ref<MailWithRecipient[]>([])
  const folders = ref<MailFolder[]>([])
  const currentFolder = ref<string>('inbox') // 'inbox', 'sent', 'drafts', 'starred', 'trash', or folder ID
  const selectedMail = ref<MailWithRecipient | null>(null)
  const unreadCount = ref<number>(0)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // ============================================================================
  // Getters
  // ============================================================================

  // FIX: Clarified inbox logic - only received mails that are not in custom folders
  const inboxMails = computed(() =>
    mails.value.filter(mail =>
      !mail.is_deleted &&
      !mail.is_draft &&
      !mail.is_sent &&
      mail.folder_id === null // Only mails not in custom folders
    )
  )

  const sentMails = computed(() =>
    mails.value.filter(mail => mail.is_sent && !mail.is_deleted)
  )

  const draftMails = computed(() =>
    mails.value.filter(mail => mail.is_draft && !mail.is_deleted)
  )

  const starredMails = computed(() =>
    mails.value.filter(mail => mail.is_starred && !mail.is_deleted)
  )

  const trashedMails = computed(() =>
    mails.value.filter(mail => mail.is_deleted)
  )

  const currentMails = computed(() => {
    if (currentFolder.value === 'inbox') return inboxMails.value
    if (currentFolder.value === 'sent') return sentMails.value
    if (currentFolder.value === 'drafts') return draftMails.value
    if (currentFolder.value === 'starred') return starredMails.value
    if (currentFolder.value === 'trash') return trashedMails.value

    // Custom folder
    const folderId = parseInt(currentFolder.value)
    if (!isNaN(folderId)) {
      return mails.value.filter(mail => mail.folder_id === folderId && !mail.is_deleted)
    }

    return []
  })

  const folderUnreadCount = computed(() => (folderId: number) => {
    return mails.value.filter(mail =>
      mail.folder_id === folderId && !mail.is_read && !mail.is_deleted
    ).length
  })

  // ============================================================================
  // Actions - Fetch Operations
  // ============================================================================

  /**
   * Fetch inbox mails
   * FIX: Changed from REPLACE to UPDATE+ADD strategy for consistency
   */
  async function fetchInbox() {
    loading.value = true
    error.value = null

    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getInbox' }
      })

      // Backend returns array directly, not wrapped in {success, data}
      const inboxMails = Array.isArray(response.data) ? response.data : []
      // UPDATE+ADD strategy: update existing, add new
      inboxMails.forEach((inboxMail: MailWithRecipient) => {
        const index = mails.value.findIndex(m => m.id === inboxMail.id)
        if (index !== -1) {
          mails.value[index] = { ...mails.value[index], ...inboxMail }
        } else {
          mails.value.push(inboxMail)
        }
      })
      updateUnreadCount()
    } catch (err: any) {
      error.value = err.message
      console.error('Error fetching inbox:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch sent mails
   * FIX: Changed to UPDATE+ADD strategy for consistency
   */
  async function fetchSent() {
    loading.value = true
    error.value = null

    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getSent' }
      })

      // Backend returns array directly, not wrapped in {success, data}
      const sentMails = Array.isArray(response.data) ? response.data : []
      // UPDATE+ADD strategy: update existing, add new
      sentMails.forEach((sentMail: MailWithRecipient) => {
        const index = mails.value.findIndex(m => m.id === sentMail.id)
        if (index !== -1) {
          mails.value[index] = { ...mails.value[index], ...sentMail }
        } else {
          mails.value.push(sentMail)
        }
      })
    } catch (err: any) {
      error.value = err.message
      console.error('Error fetching sent mails:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch draft mails
   * FIX: Changed to UPDATE+ADD strategy for consistency
   */
  async function fetchDrafts() {
    loading.value = true
    error.value = null

    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getDrafts' }
      })

      // Backend returns array directly, not wrapped in {success, data}
      const drafts = Array.isArray(response.data) ? response.data : []
      // UPDATE+ADD strategy: update existing, add new
      drafts.forEach((draft: MailWithRecipient) => {
        const index = mails.value.findIndex(m => m.id === draft.id)
        if (index !== -1) {
          mails.value[index] = { ...mails.value[index], ...draft }
        } else {
          mails.value.push(draft)
        }
      })
    } catch (err: any) {
      error.value = err.message
      console.error('Error fetching drafts:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch starred mails
   */
  async function fetchStarred() {
    loading.value = true
    error.value = null

    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getStarred' }
      })

      // Backend returns array directly, not wrapped in {success, data}
      const starredMails = Array.isArray(response.data) ? response.data : []
      starredMails.forEach((starredMail: MailWithRecipient) => {
        const index = mails.value.findIndex(m => m.id === starredMail.id)
        if (index !== -1) {
          mails.value[index] = { ...mails.value[index], ...starredMail }
        } else {
          mails.value.push(starredMail)
        }
      })
    } catch (err: any) {
      error.value = err.message
      console.error('Error fetching starred mails:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch trash mails
   * FIX: Changed to UPDATE+ADD strategy for consistency
   */
  async function fetchTrash() {
    loading.value = true
    error.value = null

    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getTrash' }
      })

      // Backend returns array directly, not wrapped in {success, data}
      const trashedMails = Array.isArray(response.data) ? response.data : []
      // UPDATE+ADD strategy: update existing, add new
      trashedMails.forEach((trashedMail: MailWithRecipient) => {
        const index = mails.value.findIndex(m => m.id === trashedMail.id)
        if (index !== -1) {
          mails.value[index] = { ...mails.value[index], ...trashedMail }
        } else {
          mails.value.push(trashedMail)
        }
      })
    } catch (err: any) {
      error.value = err.message
      console.error('Error fetching trash:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch a single mail by ID
   */
  async function fetchMail(mailId: number) {
    loading.value = true
    error.value = null

    try {
      const response = await apiClientAuth.get('/mail/', {
        params: {
          action: 'getMail',
          mail_id: mailId
        }
      })

      // Backend returns mail object directly, not wrapped in {success, data}
      const mail = response.data
      selectedMail.value = mail

      // Update in mails array if exists
      const index = mails.value.findIndex(m => m.id === mailId)
      if (index !== -1) {
        mails.value[index] = mail
      } else {
        mails.value.push(mail)
      }

      return mail
    } catch (err: any) {
      error.value = err.message
      console.error('Error fetching mail:', err)
      return null
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch all folders
   */
  async function fetchFolders() {
    loading.value = true
    error.value = null

    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getFolders' }
      })

      // Backend returns {folders: [...]}
      folders.value = response.data.folders || []
    } catch (err: any) {
      error.value = err.message
      console.error('Error fetching folders:', err)
    } finally {
      loading.value = false
    }
  }

  // ============================================================================
  // Actions - Mail Operations
  // ============================================================================

  /**
   * Mark mail as read/unread
   * FIX: Now sets read_at to null when marking as unread
   */
  async function markAsRead(mailId: number, isRead: boolean = true) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        mail_id: mailId,
        is_read: isRead
      }, {
        params: { action: 'markAsRead' }
      })

      if (response.data.success) {
        // Update local state
        const mail = mails.value.find(m => m.id === mailId)
        if (mail) {
          mail.is_read = isRead
          if (isRead) {
            mail.read_at = new Date().toISOString()
          } else {
            mail.read_at = null // FIX: Clear read_at when marking as unread
          }
          updateUnreadCount()
        }
        return true
      } else {
        throw new Error(response.data.error || 'Failed to mark as read')
      }
    } catch (err: any) {
      error.value = err.message
      console.error('Error marking as read:', err)
      return false
    }
  }

  /**
   * Mark mail as starred/unstarred
   */
  async function markAsStarred(mailId: number, starred: boolean = true) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        mail_id: mailId,
        is_starred: starred
      }, {
        params: { action: 'markAsStarred' }
      })

      if (response.data.success) {
        // Update local state
        const mail = mails.value.find(m => m.id === mailId)
        if (mail) {
          mail.is_starred = starred
        }
        return true
      } else {
        throw new Error(response.data.error || 'Failed to mark as starred')
      }
    } catch (err: any) {
      error.value = err.message
      console.error('Error marking as starred:', err)
      return false
    }
  }

  /**
   * Delete mail (move to trash)
   */
  async function deleteMail(mailId: number) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        mail_id: mailId
      }, {
        params: { action: 'deleteMail' }
      })

      if (response.data.success) {
        // Update local state
        const mail = mails.value.find(m => m.id === mailId)
        if (mail) {
          mail.is_deleted = true
          mail.deleted_at = new Date().toISOString()
          updateUnreadCount()
        }
        return true
      } else {
        throw new Error(response.data.error || 'Failed to delete mail')
      }
    } catch (err: any) {
      error.value = err.message
      console.error('Error deleting mail:', err)
      return false
    }
  }

  /**
   * Move mail to folder
   */
  async function moveToFolder(mailId: number, folderId: number | null) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        mail_id: mailId,
        folder_id: folderId
      }, {
        params: { action: 'moveToFolder' }
      })

      if (response.data.success) {
        // Update local state
        const mail = mails.value.find(m => m.id === mailId)
        if (mail) {
          mail.folder_id = folderId
        }
        return true
      } else {
        throw new Error(response.data.error || 'Failed to move to folder')
      }
    } catch (err: any) {
      error.value = err.message
      console.error('Error moving to folder:', err)
      return false
    }
  }

  /**
   * Bulk operations on multiple mails
   */
  async function bulkAction(action: BulkMailAction) {
    try {
      const response = await apiClientAuth.post('/mail/', action, {
        params: { action: 'bulkAction' }
      })

      if (response.data.success) {
        // Update local state based on action
        action.mail_ids.forEach(mailId => {
          const mail = mails.value.find(m => m.id === mailId)
          if (!mail) return

          switch (action.action) {
            case 'mark_read':
              mail.is_read = true
              mail.read_at = new Date().toISOString()
              break
            case 'mark_unread':
              mail.is_read = false
              mail.read_at = null
              break
            case 'star':
              mail.is_starred = true
              break
            case 'unstar':
              mail.is_starred = false
              break
            case 'delete':
              mail.is_deleted = true
              mail.deleted_at = new Date().toISOString()
              break
            case 'move':
              if (action.folder_id !== undefined) {
                mail.folder_id = action.folder_id
              }
              break
          }
        })

        updateUnreadCount()
        return true
      } else {
        throw new Error(response.data.error || 'Bulk action failed')
      }
    } catch (err: any) {
      error.value = err.message
      console.error('Error performing bulk action:', err)
      return false
    }
  }

  /**
   * Send a new mail
   * FIX: Removed redundant fetchSent() call to avoid race conditions
   */
  async function sendMail(mailData: any) {
    loading.value = true
    error.value = null

    try {
      const response = await apiClientAuth.post('/mail/', mailData, {
        params: { action: 'sendMail' }
      })

      if (response.data.success) {
        // Add to sent mails (no need to fetch again)
        if (response.data.data) {
          const existingIndex = mails.value.findIndex(m => m.id === response.data.data.id)
          if (existingIndex !== -1) {
            mails.value[existingIndex] = response.data.data
          } else {
            mails.value.push(response.data.data)
          }
        }
        return response.data.data
      } else {
        throw new Error(response.data.error || 'Failed to send mail')
      }
    } catch (err: any) {
      error.value = err.message
      console.error('Error sending mail:', err)
      return null
    } finally {
      loading.value = false
    }
  }

  /**
   * Save draft mail
   * FIX: Removed redundant fetchDrafts() call to avoid race conditions
   */
  async function saveDraft(draftData: any) {
    loading.value = true
    error.value = null

    try {
      const response = await apiClientAuth.post('/mail/', draftData, {
        params: { action: 'saveDraft' }
      })

      if (response.data.success) {
        // Update or add draft in mails array (no need to fetch again)
        if (response.data.data) {
          const existingIndex = mails.value.findIndex(m => m.id === response.data.data.id)
          if (existingIndex !== -1) {
            mails.value[existingIndex] = response.data.data
          } else {
            mails.value.push(response.data.data)
          }
        }
        return response.data.data
      } else {
        throw new Error(response.data.error || 'Failed to save draft')
      }
    } catch (err: any) {
      error.value = err.message
      console.error('Error saving draft:', err)
      return null
    } finally {
      loading.value = false
    }
  }

  // ============================================================================
  // Actions - Folder Operations
  // ============================================================================

  /**
   * Create a new folder
   */
  async function createFolder(name: string, color: string = '#1976d2') {
    try {
      const response = await apiClientAuth.post('/mail/', {
        name,
        color
      }, {
        params: { action: 'createFolder' }
      })

      // Backend returns {success: true, folder_id: number}
      if (response.data.success && response.data.folder_id) {
        // Refresh folders list after creation
        await fetchFolders()
        return response.data.folder_id
      } else {
        throw new Error(response.data.error || 'Failed to create folder')
      }
    } catch (err: any) {
      error.value = err.message
      console.error('Error creating folder:', err)
      return null
    }
  }

  /**
   * Update folder
   */
  async function updateFolder(folderId: number, updates: Partial<MailFolder>) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        folder_id: folderId,
        ...updates
      }, {
        params: { action: 'updateFolder' }
      })

      if (response.data.success) {
        const folder = folders.value.find(f => f.id === folderId)
        if (folder) {
          Object.assign(folder, updates)
        }
        return true
      } else {
        throw new Error(response.data.error || 'Failed to update folder')
      }
    } catch (err: any) {
      error.value = err.message
      console.error('Error updating folder:', err)
      return false
    }
  }

  /**
   * Delete folder
   * FIX: Now updates currentFolder if deleting the active folder
   */
  async function deleteFolder(folderId: number) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        folder_id: folderId
      }, {
        params: { action: 'deleteFolder' }
      })

      if (response.data.success) {
        folders.value = folders.value.filter(f => f.id !== folderId)

        // Clear folder_id from mails in this folder
        mails.value.forEach(mail => {
          if (mail.folder_id === folderId) {
            mail.folder_id = null
          }
        })

        // FIX: If currently viewing this folder, switch to inbox
        if (currentFolder.value === folderId.toString()) {
          currentFolder.value = 'inbox'
        }

        return true
      } else {
        throw new Error(response.data.error || 'Failed to delete folder')
      }
    } catch (err: any) {
      error.value = err.message
      console.error('Error deleting folder:', err)
      return false
    }
  }

  // ============================================================================
  // Actions - Account Management
  // ============================================================================

  /**
   * Fetch my mail accounts
   */
  async function fetchMyAccounts() {
    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getMyMailAccounts' }
      })
      return response.data.success ? response.data.accounts : null
    } catch (err: any) {
      console.error('Error fetching accounts:', err)
      throw err
    }
  }

  /**
   * Fetch available domains
   */
  async function fetchAvailableDomains() {
    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getAvailableDomains' }
      })
      // Backend returns 'type', map it to 'domain_type' for frontend compatibility
      const domains = response.data.success ? response.data.domains : null
      if (domains) {
        return domains.map((d: any) => ({
          ...d,
          domain_type: d.type || d.domain_type  // Map backend 'type' to 'domain_type'
        }))
      }
      return null
    } catch (err: any) {
      console.error('Error fetching domains:', err)
      throw err
    }
  }

  /**
   * Check mail availability
   */
  async function checkMailAvailability(email: string) {
    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'checkMailAvailability', email }
      })
      return response.data
    } catch (err: any) {
      console.error('Error checking availability:', err)
      throw err
    }
  }

  /**
   * Create mail account
   */
  async function createAccount(accountData: any) {
    try {
      const response = await apiClientAuth.post('/mail/', accountData, {
        params: { action: 'createMailAccount' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to create account')
      }
      return response.data.data
    } catch (err: any) {
      console.error('Error creating account:', err)
      throw err
    }
  }

  /**
   * Link mail account
   */
  async function linkAccount(linkData: any) {
    try {
      const response = await apiClientAuth.post('/mail/', linkData, {
        params: { action: 'linkMailAccount' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to link account')
      }
      return response.data.data
    } catch (err: any) {
      console.error('Error linking account:', err)
      throw err
    }
  }

  /**
   * Unlink mail account
   */
  async function unlinkAccount(accountId: number, reason: string) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        account_id: accountId,
        reason
      }, {
        params: { action: 'unlinkMailAccount' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to unlink account')
      }
      return true
    } catch (err: any) {
      console.error('Error unlinking account:', err)
      throw err
    }
  }

  /**
   * Change mail password
   */
  async function changePassword(accountId: number, oldPassword: string, newPassword: string) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        account_id: accountId,
        old_password: oldPassword,
        new_password: newPassword
      }, {
        params: { action: 'changeMailPassword' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to change password')
      }
      return true
    } catch (err: any) {
      console.error('Error changing password:', err)
      throw err
    }
  }

  /**
   * Update mail settings
   */
  async function updateMailSettings(settings: any) {
    try {
      const response = await apiClientAuth.post('/mail/', settings, {
        params: { action: 'updateMailSettings' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to update settings')
      }
      return true
    } catch (err: any) {
      console.error('Error updating settings:', err)
      throw err
    }
  }

  // ============================================================================
  // Actions - Company Mailboxes
  // ============================================================================

  /**
   * Fetch company mailboxes
   */
  async function fetchCompanyMailboxes() {
    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getCompanyMailboxes' }
      })
      return response.data.success ? response.data.data : null
    } catch (err: any) {
      console.error('Error fetching mailboxes:', err)
      throw err
    }
  }

  /**
   * Create company mailbox
   */
  async function createMailbox(mailboxData: any) {
    try {
      const response = await apiClientAuth.post('/mail/', mailboxData, {
        params: { action: 'createCompanyMailbox' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to create mailbox')
      }
      return response.data.data
    } catch (err: any) {
      console.error('Error creating mailbox:', err)
      throw err
    }
  }

  /**
   * Update mailbox settings
   */
  async function updateMailboxSettings(mailboxId: number, settings: any) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        mailbox_id: mailboxId,
        ...settings
      }, {
        params: { action: 'updateMailboxSettings' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to update mailbox')
      }
      return true
    } catch (err: any) {
      console.error('Error updating mailbox:', err)
      throw err
    }
  }

  /**
   * Fetch mailbox members
   */
  async function fetchMailboxMembers(mailboxId: number) {
    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getMailboxMembers', mailbox_id: mailboxId }
      })
      return response.data.success ? response.data.data : null
    } catch (err: any) {
      console.error('Error fetching members:', err)
      throw err
    }
  }

  /**
   * Add mailbox permission
   */
  async function addMailboxPermission(mailboxId: number, userId: number, permissions: any) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        mailbox_id: mailboxId,
        user_id: userId,
        ...permissions
      }, {
        params: { action: 'addMailboxPermission' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to add permission')
      }
      return true
    } catch (err: any) {
      console.error('Error adding permission:', err)
      throw err
    }
  }

  /**
   * Update mailbox permission
   */
  async function updateMailboxPermission(mailboxId: number, userId: number, permissions: any) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        mailbox_id: mailboxId,
        user_id: userId,
        ...permissions
      }, {
        params: { action: 'updateMailboxPermission' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to update permission')
      }
      return true
    } catch (err: any) {
      console.error('Error updating permission:', err)
      throw err
    }
  }

  /**
   * Remove mailbox permission
   */
  async function removeMailboxPermission(mailboxId: number, userId: number) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        mailbox_id: mailboxId,
        user_id: userId
      }, {
        params: { action: 'removeMailboxPermission' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to remove permission')
      }
      return true
    } catch (err: any) {
      console.error('Error removing permission:', err)
      throw err
    }
  }

  // ============================================================================
  // Actions - Contacts
  // ============================================================================

  /**
   * Fetch contacts
   */
  async function fetchContacts() {
    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getContacts' }
      })
      // Backend returns {contacts: [...]}
      return response.data.contacts || []
    } catch (err: any) {
      console.error('Error fetching contacts:', err)
      throw err
    }
  }

  /**
   * Search global directory
   */
  async function searchGlobalDirectory(query: string) {
    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getGlobalDirectory', query }
      })
      // Backend returns array directly, not wrapped in {success, data}
      return Array.isArray(response.data) ? response.data : []
    } catch (err: any) {
      console.error('Error searching directory:', err)
      throw err
    }
  }

  /**
   * Create contact
   */
  async function createContact(contactData: any) {
    try {
      const response = await apiClientAuth.post('/mail/', contactData, {
        params: { action: 'createContact' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to create contact')
      }
      return response.data.data
    } catch (err: any) {
      console.error('Error creating contact:', err)
      throw err
    }
  }

  /**
   * Update contact
   */
  async function updateContact(contactId: number, contactData: any) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        contact_id: contactId,
        ...contactData
      }, {
        params: { action: 'updateContact' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to update contact')
      }
      return true
    } catch (err: any) {
      console.error('Error updating contact:', err)
      throw err
    }
  }

  /**
   * Delete contact
   */
  async function deleteContact(contactId: number) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        contact_id: contactId
      }, {
        params: { action: 'deleteContact' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to delete contact')
      }
      return true
    } catch (err: any) {
      console.error('Error deleting contact:', err)
      throw err
    }
  }

  /**
   * Toggle favorite contact
   */
  async function toggleFavorite(contactId: number) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        contact_id: contactId
      }, {
        params: { action: 'toggleFavorite' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to toggle favorite')
      }
      return true
    } catch (err: any) {
      console.error('Error toggling favorite:', err)
      throw err
    }
  }

  /**
   * Export contacts
   */
  async function exportContacts() {
    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'exportContacts' }
      })
      return response.data.success ? response.data.data : null
    } catch (err: any) {
      console.error('Error exporting contacts:', err)
      throw err
    }
  }

  /**
   * Import contacts
   */
  async function importContacts(file: File) {
    try {
      const formData = new FormData()
      formData.append('file', file)

      const response = await apiClientAuth.post('/mail/', formData, {
        params: { action: 'importContacts' },
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to import contacts')
      }
      return response.data.data
    } catch (err: any) {
      console.error('Error importing contacts:', err)
      throw err
    }
  }

  // ============================================================================
  // Actions - Templates
  // ============================================================================

  /**
   * Fetch templates
   */
  async function fetchTemplates() {
    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getTemplates' }
      })
      return response.data.success ? response.data.data : null
    } catch (err: any) {
      console.error('Error fetching templates:', err)
      throw err
    }
  }

  /**
   * Create template
   */
  async function createTemplate(templateData: any) {
    try {
      const response = await apiClientAuth.post('/mail/', templateData, {
        params: { action: 'createTemplate' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to create template')
      }
      return response.data.data
    } catch (err: any) {
      console.error('Error creating template:', err)
      throw err
    }
  }

  /**
   * Update template
   */
  async function updateTemplate(templateId: number, templateData: any) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        template_id: templateId,
        ...templateData
      }, {
        params: { action: 'updateTemplate' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to update template')
      }
      return true
    } catch (err: any) {
      console.error('Error updating template:', err)
      throw err
    }
  }

  /**
   * Delete template
   */
  async function deleteTemplate(templateId: number) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        template_id: templateId
      }, {
        params: { action: 'deleteTemplate' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to delete template')
      }
      return true
    } catch (err: any) {
      console.error('Error deleting template:', err)
      throw err
    }
  }

  // ============================================================================
  // Actions - Signatures
  // ============================================================================

  /**
   * Fetch signatures
   */
  async function fetchSignatures() {
    try {
      const response = await apiClientAuth.get('/mail/', {
        params: { action: 'getSignatures' }
      })
      return response.data.success ? response.data.data : null
    } catch (err: any) {
      console.error('Error fetching signatures:', err)
      throw err
    }
  }

  /**
   * Create signature
   */
  async function createSignature(signatureData: any) {
    try {
      const response = await apiClientAuth.post('/mail/', signatureData, {
        params: { action: 'createSignature' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to create signature')
      }
      return response.data.data
    } catch (err: any) {
      console.error('Error creating signature:', err)
      throw err
    }
  }

  /**
   * Update signature
   */
  async function updateSignature(signatureId: number, signatureData: any) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        signature_id: signatureId,
        ...signatureData
      }, {
        params: { action: 'updateSignature' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to update signature')
      }
      return true
    } catch (err: any) {
      console.error('Error updating signature:', err)
      throw err
    }
  }

  /**
   * Delete signature
   */
  async function deleteSignature(signatureId: number) {
    try {
      const response = await apiClientAuth.post('/mail/', {
        signature_id: signatureId
      }, {
        params: { action: 'deleteSignature' }
      })
      if (!response.data.success) {
        throw new Error(response.data.error || 'Failed to delete signature')
      }
      return true
    } catch (err: any) {
      console.error('Error deleting signature:', err)
      throw err
    }
  }

  // ============================================================================
  // Utility Functions
  // ============================================================================

  /**
   * Update unread count
   */
  function updateUnreadCount() {
    unreadCount.value = mails.value.filter(m => !m.is_read && !m.is_deleted).length
  }

  /**
   * Set current mail account
   * FIX: Added missing setter function for currentAccount
   */
  function setCurrentAccount(account: MailAccount | null) {
    currentAccount.value = account
  }

  /**
   * Set current folder
   */
  function setCurrentFolder(folder: string) {
    currentFolder.value = folder
  }

  /**
   * Clear error
   */
  function clearError() {
    error.value = null
  }

  /**
   * Reset store
   */
  function resetStore() {
    currentAccount.value = null
    mails.value = []
    folders.value = []
    currentFolder.value = 'inbox'
    selectedMail.value = null
    unreadCount.value = 0
    loading.value = false
    error.value = null
  }

  // ============================================================================
  // Return
  // ============================================================================

  return {
    // State
    currentAccount,
    mails,
    folders,
    currentFolder,
    selectedMail,
    unreadCount,
    loading,
    error,

    // Getters
    inboxMails,
    sentMails,
    draftMails,
    starredMails,
    trashedMails,
    currentMails,
    folderUnreadCount,

    // Actions - Fetch
    fetchInbox,
    fetchSent,
    fetchDrafts,
    fetchStarred,
    fetchTrash,
    fetchMail,
    fetchFolders,

    // Actions - Mail Operations
    markAsRead,
    markAsStarred,
    deleteMail,
    moveToFolder,
    bulkAction,
    sendMail,
    saveDraft,

    // Actions - Folder Operations
    createFolder,
    updateFolder,
    deleteFolder,

    // Actions - Account Management
    fetchMyAccounts,
    fetchAvailableDomains,
    checkMailAvailability,
    createAccount,
    linkAccount,
    unlinkAccount,
    changePassword,
    updateMailSettings,

    // Actions - Company Mailboxes
    fetchCompanyMailboxes,
    createMailbox,
    updateMailboxSettings,
    fetchMailboxMembers,
    addMailboxPermission,
    updateMailboxPermission,
    removeMailboxPermission,

    // Actions - Contacts
    fetchContacts,
    searchGlobalDirectory,
    createContact,
    updateContact,
    deleteContact,
    toggleFavorite,
    exportContacts,
    importContacts,

    // Actions - Templates
    fetchTemplates,
    createTemplate,
    updateTemplate,
    deleteTemplate,

    // Actions - Signatures
    fetchSignatures,
    createSignature,
    updateSignature,
    deleteSignature,

    // Utilities
    setCurrentAccount,
    setCurrentFolder,
    clearError,
    resetStore,
  }
})
