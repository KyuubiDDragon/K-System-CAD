export interface Session {
  id: number
  device_name: string
  device_type: 'desktop' | 'mobile' | 'tablet'
  ip_address: string
  last_activity: string
  created_at: string
  expires_at: string
  is_current: boolean
}

export interface SessionsResponse {
  sessions: Session[]
  count: number
}
