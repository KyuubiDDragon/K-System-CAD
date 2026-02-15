/**
 * K-Systems Mail Helper Functions
 *
 * Utility functions for mail system operations including:
 * - Date formatting
 * - File size formatting
 * - Text preview extraction
 * - Email validation
 * - HTML sanitization
 * - Thread grouping
 * - Search filtering
 */

import type { Mail, MailWithRecipient } from '@/types/Mail'

/**
 * Format date for mail list display
 * - Today: "15:30"
 * - Yesterday: "Gestern"
 * - This week: "Montag"
 * - This year: "15. März"
 * - Older: "15.03.2023"
 */
export function formatMailDate(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  const mailDate = new Date(date.getFullYear(), date.getMonth(), date.getDate())

  // Today
  if (mailDate.getTime() === today.getTime()) {
    return date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })
  }

  // Yesterday
  if (mailDate.getTime() === yesterday.getTime()) {
    return 'Gestern'
  }

  // This week (last 7 days)
  const weekAgo = new Date(today)
  weekAgo.setDate(weekAgo.getDate() - 6)
  if (mailDate >= weekAgo) {
    return date.toLocaleDateString('de-DE', { weekday: 'long' })
  }

  // This year
  if (date.getFullYear() === now.getFullYear()) {
    return date.toLocaleDateString('de-DE', { day: 'numeric', month: 'long' })
  }

  // Older
  return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

/**
 * Format file size (bytes to human readable)
 */
export function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

/**
 * Extract preview text from HTML body
 */
export function extractPreview(htmlBody: string, maxLength: number = 100): string {
  if (!htmlBody) return ''

  const tmp = document.createElement('div')
  tmp.innerHTML = htmlBody
  const text = tmp.textContent || tmp.innerText || ''

  return text.length > maxLength ? text.substring(0, maxLength).trim() + '...' : text.trim()
}

/**
 * Validate email address
 */
export function isValidEmail(email: string): boolean {
  if (!email || typeof email !== 'string') return false
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return regex.test(email.trim())
}

/**
 * Parse multiple emails from string (comma/semicolon separated)
 */
export function parseEmailList(input: string): string[] {
  if (!input || typeof input !== 'string') return []

  return input
    .split(/[,;]/)
    .map(e => e.trim())
    .filter(e => e.length > 0 && isValidEmail(e))
}

/**
 * Get sender name or email
 */
export function getSenderDisplay(mail: Mail): string {
  // Try to extract name from "Name <email>" format
  const match = mail.from_address.match(/^(.+?)\s*<(.+?)>$/)
  if (match && match[1]) {
    return match[1].trim()
  }
  return mail.from_address
}

/**
 * Get sender email only (without name)
 */
export function getSenderEmail(mail: Mail): string {
  // Try to extract email from "Name <email>" format
  const match = mail.from_address.match(/^.+?<(.+?)>$/)
  if (match && match[1]) {
    return match[1].trim()
  }
  return mail.from_address
}

/**
 * Get priority icon and color
 */
export function getPriorityInfo(priority: string): { icon: string; color: string } {
  switch (priority) {
    case 'high':
      return { icon: 'mdi-alert-circle', color: 'error' }
    case 'low':
      return { icon: 'mdi-arrow-down-circle', color: 'grey' }
    default:
      return { icon: '', color: '' }
  }
}

/**
 * Sanitize HTML for safe display
 * (Basic implementation - consider using DOMPurify in production)
 */
export function sanitizeHTML(html: string): string {
  if (!html) return ''

  const tmp = document.createElement('div')
  tmp.innerHTML = html

  // Remove script tags
  const scripts = tmp.querySelectorAll('script')
  scripts.forEach(script => script.remove())

  // Remove dangerous tags
  const dangerousTags = ['iframe', 'object', 'embed', 'link', 'meta']
  dangerousTags.forEach(tag => {
    const elements = tmp.querySelectorAll(tag)
    elements.forEach(el => el.remove())
  })

  // Remove event handlers and dangerous attributes
  const elements = tmp.querySelectorAll('*')
  elements.forEach(el => {
    Array.from(el.attributes).forEach(attr => {
      if (attr.name.startsWith('on') || attr.name === 'formaction') {
        el.removeAttribute(attr.name)
      }
    })

    // Remove javascript: protocol from href and src
    if (el.hasAttribute('href')) {
      const href = el.getAttribute('href') || ''
      if (href.toLowerCase().startsWith('javascript:')) {
        el.removeAttribute('href')
      }
    }
    if (el.hasAttribute('src')) {
      const src = el.getAttribute('src') || ''
      if (src.toLowerCase().startsWith('javascript:')) {
        el.removeAttribute('src')
      }
    }
  })

  return tmp.innerHTML
}

/**
 * Group mails by thread
 */
export function groupByThread(mails: MailWithRecipient[]): Map<string, MailWithRecipient[]> {
  const threads = new Map<string, MailWithRecipient[]>()

  mails.forEach(mail => {
    const threadId = mail.thread_id || mail.message_id
    if (!threads.has(threadId)) {
      threads.set(threadId, [])
    }
    threads.get(threadId)!.push(mail)
  })

  // Sort each thread by sent_at
  threads.forEach(thread => {
    thread.sort((a, b) => {
      const dateA = new Date(a.sent_at || a.created_at).getTime()
      const dateB = new Date(b.sent_at || b.created_at).getTime()
      return dateA - dateB
    })
  })

  return threads
}

/**
 * Check if mail matches search query
 */
export function matchesSearch(mail: MailWithRecipient, query: string): boolean {
  if (!query || !query.trim()) return true

  const lowerQuery = query.toLowerCase().trim()

  // Search in subject
  if (mail.subject && mail.subject.toLowerCase().includes(lowerQuery)) {
    return true
  }

  // Search in from address
  if (mail.from_address && mail.from_address.toLowerCase().includes(lowerQuery)) {
    return true
  }

  // Search in body
  if (mail.body_html && mail.body_html.toLowerCase().includes(lowerQuery)) {
    return true
  }

  if (mail.body_text && mail.body_text.toLowerCase().includes(lowerQuery)) {
    return true
  }

  // Search in to addresses
  if (mail.to_addresses && Array.isArray(mail.to_addresses)) {
    const found = mail.to_addresses.some(addr =>
      addr && addr.toLowerCase().includes(lowerQuery)
    )
    if (found) return true
  }

  // Search in CC addresses
  if (mail.cc_addresses && Array.isArray(mail.cc_addresses)) {
    const found = mail.cc_addresses.some(addr =>
      addr && addr.toLowerCase().includes(lowerQuery)
    )
    if (found) return true
  }

  return false
}

/**
 * Get mail age in human-readable format
 */
export function getMailAge(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMinutes = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMinutes < 1) return 'gerade eben'
  if (diffMinutes < 60) return `vor ${diffMinutes} Minute${diffMinutes > 1 ? 'n' : ''}`
  if (diffHours < 24) return `vor ${diffHours} Stunde${diffHours > 1 ? 'n' : ''}`
  if (diffDays < 7) return `vor ${diffDays} Tag${diffDays > 1 ? 'en' : ''}`
  if (diffDays < 30) return `vor ${Math.floor(diffDays / 7)} Woche${Math.floor(diffDays / 7) > 1 ? 'n' : ''}`

  return formatMailDate(dateString)
}

/**
 * Generate initials from sender name
 */
export function getSenderInitials(mail: Mail): string {
  const displayName = getSenderDisplay(mail)

  // If it looks like an email, use first two letters
  if (displayName.includes('@')) {
    return displayName.substring(0, 2).toUpperCase()
  }

  // Extract initials from name
  const parts = displayName.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }

  return displayName.substring(0, 2).toUpperCase()
}

/**
 * Format recipient list for display
 */
export function formatRecipients(addresses: string[], maxDisplay: number = 3): string {
  if (!addresses || addresses.length === 0) return ''

  const displayAddresses = addresses.slice(0, maxDisplay)
  const remaining = addresses.length - maxDisplay

  const formatted = displayAddresses.map(addr => {
    const match = addr.match(/^(.+?)\s*<(.+?)>$/)
    return match && match[1] ? match[1].trim() : addr
  }).join(', ')

  if (remaining > 0) {
    return `${formatted} (+${remaining})`
  }

  return formatted
}

/**
 * Check if mail is recent (last 24 hours)
 */
export function isRecentMail(dateString: string): boolean {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  return diffMs < 86400000 // 24 hours in milliseconds
}

/**
 * Get storage usage percentage
 */
export function getStoragePercentage(used: number, limit: number): number {
  if (limit === 0) return 0
  return Math.round((used / limit) * 100)
}

/**
 * Format storage display
 */
export function formatStorageDisplay(used: number, limit: number): string {
  return `${formatFileSize(used)} von ${formatFileSize(limit)}`
}

/**
 * Generate unique message ID
 */
export function generateMessageId(domain: string = 'k-systems.app'): string {
  const timestamp = Date.now()
  const random = Math.random().toString(36).substring(2, 15)
  return `<${timestamp}.${random}@${domain}>`
}

/**
 * Parse email components (name and address)
 */
export function parseEmailAddress(email: string): { name: string | null; address: string } {
  const match = email.match(/^(.+?)\s*<(.+?)>$/)
  if (match) {
    return {
      name: match[1].trim(),
      address: match[2].trim()
    }
  }
  return {
    name: null,
    address: email.trim()
  }
}

/**
 * Format email with name
 */
export function formatEmailWithName(name: string | null, address: string): string {
  if (name && name.trim()) {
    return `${name.trim()} <${address.trim()}>`
  }
  return address.trim()
}

/**
 * Check if email is from internal domain
 */
export function isInternalEmail(email: string, internalDomains: string[] = ['k-systems.app', 'ls.k-systems.app']): boolean {
  const { address } = parseEmailAddress(email)
  const domain = address.split('@')[1]?.toLowerCase()
  return domain ? internalDomains.includes(domain) : false
}

/**
 * Get thread participant count (unique senders/recipients)
 */
export function getThreadParticipants(mails: MailWithRecipient[]): number {
  const participants = new Set<string>()

  mails.forEach(mail => {
    const { address } = parseEmailAddress(mail.from_address)
    participants.add(address.toLowerCase())

    mail.to_addresses?.forEach(to => {
      const { address: toAddr } = parseEmailAddress(to)
      participants.add(toAddr.toLowerCase())
    })

    mail.cc_addresses?.forEach(cc => {
      const { address: ccAddr } = parseEmailAddress(cc)
      participants.add(ccAddr.toLowerCase())
    })
  })

  return participants.size
}

/**
 * Sort mails by date (newest first by default)
 */
export function sortMailsByDate(mails: MailWithRecipient[], ascending: boolean = false): MailWithRecipient[] {
  return [...mails].sort((a, b) => {
    const dateA = new Date(a.sent_at || a.created_at).getTime()
    const dateB = new Date(b.sent_at || b.created_at).getTime()
    return ascending ? dateA - dateB : dateB - dateA
  })
}

/**
 * Filter mails by date range
 */
export function filterMailsByDateRange(mails: MailWithRecipient[], startDate: Date | null, endDate: Date | null): MailWithRecipient[] {
  return mails.filter(mail => {
    const mailDate = new Date(mail.sent_at || mail.created_at)

    if (startDate && mailDate < startDate) return false
    if (endDate && mailDate > endDate) return false

    return true
  })
}

/**
 * Get mail status label
 */
export function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    'new': 'Neu',
    'in_progress': 'In Bearbeitung',
    'done': 'Erledigt',
    'archived': 'Archiviert'
  }
  return labels[status] || status
}

/**
 * Get mail status color
 */
export function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    'new': 'primary',
    'in_progress': 'warning',
    'done': 'success',
    'archived': 'grey'
  }
  return colors[status] || 'default'
}

/**
 * Truncate text with ellipsis
 */
export function truncateText(text: string, maxLength: number, addEllipsis: boolean = true): string {
  if (!text || text.length <= maxLength) return text
  const truncated = text.substring(0, maxLength).trim()
  return addEllipsis ? truncated + '...' : truncated
}
