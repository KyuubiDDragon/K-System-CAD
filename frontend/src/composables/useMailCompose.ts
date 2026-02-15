/**
 * K-Systems Mail Compose Composable
 *
 * Handles all compose mail logic including sending, drafts, attachments, and validation
 */

import { ref, computed } from 'vue'
import type {
  ComposeMailForm,
  MailTemplate,
  MailSignature,
  MailAttachment,
  RecipientSuggestion,
  ApiResponse
} from '@/types/Mail'

export function useMailCompose(initialDraftId?: number) {
  // Form state
  const form = ref<ComposeMailForm>({
    from_address: '',
    to_addresses: [],
    cc_addresses: [],
    bcc_addresses: [],
    subject: '',
    body_html: '',
    priority: 'normal',
    attachments: [],
    draft_id: initialDraftId
  })

  // UI state
  const loading = ref(false)
  const sending = ref(false)
  const savingDraft = ref(false)
  const uploadProgress = ref<{ [key: string]: number }>({})
  const uploadedAttachments = ref<MailAttachment[]>([])

  // Computed
  const totalAttachmentSize = computed(() => {
    return uploadedAttachments.value.reduce((sum, att) => sum + att.file_size, 0)
  })

  const hasContent = computed(() => {
    return (
      form.value.to_addresses.length > 0 ||
      form.value.subject.trim() !== '' ||
      form.value.body_html.trim() !== '' ||
      uploadedAttachments.value.length > 0
    )
  })

  /**
   * Validate email address format
   */
  function isValidEmail(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
  }

  /**
   * Validate the compose form
   */
  function validateForm(): { valid: boolean; errors: string[] } {
    const errors: string[] = []

    // Check recipients
    if (form.value.to_addresses.length === 0) {
      errors.push('At least one recipient is required')
    }

    // Validate all recipient emails
    const allRecipients = [
      ...form.value.to_addresses,
      ...(form.value.cc_addresses || []),
      ...(form.value.bcc_addresses || [])
    ]

    for (const email of allRecipients) {
      if (!isValidEmail(email)) {
        errors.push(`Invalid email address: ${email}`)
      }
    }

    // Check subject
    if (!form.value.subject.trim()) {
      errors.push('Subject is required')
    }

    // Check body
    if (!form.value.body_html.trim()) {
      errors.push('Message body cannot be empty')
    }

    // Check attachments
    if (uploadedAttachments.value.length > 5) {
      errors.push('Maximum 5 attachments allowed')
    }

    if (totalAttachmentSize.value > 25 * 1024 * 1024) {
      errors.push('Total attachment size cannot exceed 25MB')
    }

    return {
      valid: errors.length === 0,
      errors
    }
  }

  /**
   * Send the email
   */
  async function sendMail(): Promise<ApiResponse<any>> {
    sending.value = true
    loading.value = true

    try {
      const token = localStorage.getItem('token')
      if (!token) {
        throw new Error('No authentication token found')
      }

      const response = await fetch(`${import.meta.env.VITE_API_URL}/mail/`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'sendMail',
          from_address: form.value.from_address,
          to_addresses: form.value.to_addresses,
          cc_addresses: form.value.cc_addresses,
          bcc_addresses: form.value.bcc_addresses,
          subject: form.value.subject,
          body_html: form.value.body_html,
          priority: form.value.priority,
          attachment_ids: uploadedAttachments.value.map(a => a.id),
          draft_id: form.value.draft_id
        })
      })

      const result = await response.json()

      if (!result.success) {
        throw new Error(result.error || 'Failed to send email')
      }

      // Clear form on success
      resetForm()

      return result
    } catch (error) {
      console.error('Error sending mail:', error)
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Failed to send email'
      }
    } finally {
      sending.value = false
      loading.value = false
    }
  }

  /**
   * Save as draft
   */
  async function saveDraft(): Promise<ApiResponse<any>> {
    savingDraft.value = true

    try {
      const token = localStorage.getItem('token')
      if (!token) {
        throw new Error('No authentication token found')
      }

      const response = await fetch(`${import.meta.env.VITE_API_URL}/mail/`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'saveDraft',
          draft_id: form.value.draft_id,
          from_address: form.value.from_address,
          to_addresses: form.value.to_addresses,
          cc_addresses: form.value.cc_addresses,
          bcc_addresses: form.value.bcc_addresses,
          subject: form.value.subject,
          body_html: form.value.body_html,
          priority: form.value.priority,
          attachment_ids: uploadedAttachments.value.map(a => a.id)
        })
      })

      const result = await response.json()

      if (result.success && result.data?.draft_id) {
        form.value.draft_id = result.data.draft_id
      }

      return result
    } catch (error) {
      console.error('Error saving draft:', error)
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Failed to save draft'
      }
    } finally {
      savingDraft.value = false
    }
  }

  /**
   * Upload attachment
   */
  async function uploadAttachment(file: File): Promise<ApiResponse<MailAttachment>> {
    // Validate file
    const maxSize = 10 * 1024 * 1024 // 10MB
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png']

    if (file.size > maxSize) {
      return {
        success: false,
        error: `File "${file.name}" exceeds 10MB limit`
      }
    }

    if (!allowedTypes.includes(file.type)) {
      return {
        success: false,
        error: `File type not allowed. Only PDF, JPG, and PNG files are supported`
      }
    }

    if (uploadedAttachments.value.length >= 5) {
      return {
        success: false,
        error: 'Maximum 5 attachments allowed'
      }
    }

    const newTotal = totalAttachmentSize.value + file.size
    if (newTotal > 25 * 1024 * 1024) {
      return {
        success: false,
        error: 'Total attachment size cannot exceed 25MB'
      }
    }

    // Upload file
    try {
      const token = localStorage.getItem('token')
      if (!token) {
        throw new Error('No authentication token found')
      }

      const formData = new FormData()
      formData.append('action', 'uploadAttachment')
      formData.append('file', file)
      if (form.value.draft_id) {
        formData.append('draft_id', form.value.draft_id.toString())
      }

      // Track upload progress
      uploadProgress.value[file.name] = 0

      const xhr = new XMLHttpRequest()

      return new Promise<ApiResponse<MailAttachment>>((resolve) => {
        xhr.upload.addEventListener('progress', (e) => {
          if (e.lengthComputable) {
            uploadProgress.value[file.name] = (e.loaded / e.total) * 100
          }
        })

        xhr.addEventListener('load', () => {
          delete uploadProgress.value[file.name]

          if (xhr.status === 200) {
            const result = JSON.parse(xhr.responseText)
            if (result.success && result.data) {
              uploadedAttachments.value.push(result.data)
            }
            resolve(result)
          } else {
            resolve({
              success: false,
              error: 'Failed to upload attachment'
            })
          }
        })

        xhr.addEventListener('error', () => {
          delete uploadProgress.value[file.name]
          resolve({
            success: false,
            error: 'Network error during upload'
          })
        })

        xhr.open('POST', `${import.meta.env.VITE_API_URL}/mail/`)
        xhr.setRequestHeader('Authorization', `Bearer ${token}`)
        xhr.send(formData)
      })
    } catch (error) {
      delete uploadProgress.value[file.name]
      console.error('Error uploading attachment:', error)
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Failed to upload attachment'
      }
    }
  }

  /**
   * Remove attachment
   */
  async function removeAttachment(attachmentId: number): Promise<ApiResponse<any>> {
    try {
      const token = localStorage.getItem('token')
      if (!token) {
        throw new Error('No authentication token found')
      }

      const response = await fetch(`${import.meta.env.VITE_API_URL}/mail/`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'removeAttachment',
          attachment_id: attachmentId
        })
      })

      const result = await response.json()

      if (result.success) {
        uploadedAttachments.value = uploadedAttachments.value.filter(a => a.id !== attachmentId)
      }

      return result
    } catch (error) {
      console.error('Error removing attachment:', error)
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Failed to remove attachment'
      }
    }
  }

  /**
   * Search contacts for autocomplete
   */
  async function searchContacts(query: string): Promise<RecipientSuggestion[]> {
    if (!query || query.trim().length < 2) {
      return []
    }

    try {
      const token = localStorage.getItem('token')
      if (!token) {
        return []
      }

      const response = await fetch(
        `${import.meta.env.VITE_API_URL}/mail/?action=searchContacts&query=${encodeURIComponent(query)}`,
        {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        }
      )

      const result = await response.json()
      return result.success ? (result.data || []) : []
    } catch (error) {
      console.error('Error searching contacts:', error)
      return []
    }
  }

  /**
   * Apply template to compose form
   */
  function applyTemplate(template: MailTemplate, variables?: Record<string, string>) {
    let subject = template.subject_template || ''
    let body = template.body_template

    // Replace variables if provided
    if (variables) {
      Object.entries(variables).forEach(([key, value]) => {
        const placeholder = `{{${key}}}`
        subject = subject.replace(new RegExp(placeholder, 'g'), value)
        body = body.replace(new RegExp(placeholder, 'g'), value)
      })
    }

    form.value.subject = subject
    form.value.body_html = body
  }

  /**
   * Apply signature to body
   */
  function applySignature(signature: MailSignature) {
    // Add signature at the end with proper spacing
    const currentBody = form.value.body_html
    const separator = '<br><br>---<br>'
    form.value.body_html = currentBody + separator + signature.signature_html
  }

  /**
   * Reset form to initial state
   */
  function resetForm() {
    form.value = {
      from_address: '',
      to_addresses: [],
      cc_addresses: [],
      bcc_addresses: [],
      subject: '',
      body_html: '',
      priority: 'normal',
      attachments: []
    }
    uploadedAttachments.value = []
    uploadProgress.value = {}
  }

  /**
   * Load draft data
   */
  async function loadDraft(draftId: number): Promise<void> {
    loading.value = true

    try {
      const token = localStorage.getItem('token')
      if (!token) {
        throw new Error('No authentication token found')
      }

      const response = await fetch(
        `${import.meta.env.VITE_API_URL}/mail/?action=getDraft&draft_id=${draftId}`,
        {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        }
      )

      const result = await response.json()

      if (result.success && result.data) {
        const draft = result.data
        form.value = {
          from_address: draft.from_address,
          to_addresses: draft.to_addresses || [],
          cc_addresses: draft.cc_addresses || [],
          bcc_addresses: draft.bcc_addresses || [],
          subject: draft.subject || '',
          body_html: draft.body_html || '',
          priority: draft.priority || 'normal',
          draft_id: draftId
        }

        if (draft.attachments) {
          uploadedAttachments.value = draft.attachments
        }
      }
    } catch (error) {
      console.error('Error loading draft:', error)
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    form,
    loading,
    sending,
    savingDraft,
    uploadProgress,
    uploadedAttachments,

    // Computed
    totalAttachmentSize,
    hasContent,

    // Methods
    validateForm,
    sendMail,
    saveDraft,
    uploadAttachment,
    removeAttachment,
    searchContacts,
    applyTemplate,
    applySignature,
    resetForm,
    loadDraft,
    isValidEmail
  }
}
