import { ref, type Ref } from 'vue'
import api from '@/api'
import type { Session, SessionsResponse } from '@/types/Session'

export function useSessions() {
  const sessions: Ref<Session[]> = ref([])
  const loading = ref(false)
  const error: Ref<string | null> = ref(null)

  /**
   * Load current user's active sessions
   */
  const loadSessions = async (): Promise<void> => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get<SessionsResponse>('/session/?action=getSessions')
      sessions.value = response.data.sessions
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Failed to load sessions'
      console.error('Error loading sessions:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Logout a specific session
   */
  const logoutSession = async (sessionId: number): Promise<boolean> => {
    loading.value = true
    error.value = null

    try {
      await api.post(`/session/?action=logoutSession&sessionId=${sessionId}`)
      // Remove from local list
      sessions.value = sessions.value.filter(s => s.id !== sessionId)
      return true
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Failed to logout session'
      console.error('Error logging out session:', err)
      return false
    } finally {
      loading.value = false
    }
  }

  /**
   * Logout all other sessions (keep current)
   */
  const logoutOtherSessions = async (): Promise<boolean> => {
    loading.value = true
    error.value = null

    try {
      await api.post('/session/?action=logoutOther')
      // Reload sessions to get updated list
      await loadSessions()
      return true
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Failed to logout other sessions'
      console.error('Error logging out other sessions:', err)
      return false
    } finally {
      loading.value = false
    }
  }

  /**
   * Load sessions for a specific user (Admin only)
   */
  const loadUserSessions = async (userId: number): Promise<Session[]> => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get<SessionsResponse>(`/session/?action=getUserSessions&userId=${userId}`)
      return response.data.sessions
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Failed to load user sessions'
      console.error('Error loading user sessions:', err)
      return []
    } finally {
      loading.value = false
    }
  }

  /**
   * Logout all sessions for a user (Admin only)
   */
  const logoutAllUserSessions = async (userId: number): Promise<boolean> => {
    loading.value = true
    error.value = null

    try {
      await api.post(`/session/?action=logoutUser&userId=${userId}`)
      return true
    } catch (err: any) {
      error.value = err.response?.data?.error || 'Failed to logout user'
      console.error('Error logging out user:', err)
      return false
    } finally {
      loading.value = false
    }
  }

  return {
    sessions,
    loading,
    error,
    loadSessions,
    logoutSession,
    logoutOtherSessions,
    loadUserSessions,
    logoutAllUserSessions
  }
}
