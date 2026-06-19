export type NoticeAudience = 'all' | 'parents' | 'staff' | 'pupils' | 'grade'

export type SmsStatus = 'pending' | 'sent' | 'failed' | 'delivered'

export type SmsProvider = 'airtel' | 'mtn'

export interface Notice {
    id: number
    school_id: number
    title: string
    content: string
    target_audience: NoticeAudience
    target_grade_id: number | null
    published_at: string | null
    expires_at: string | null
    created_by: number
    created_at: string
    updated_at: string
    creator?: { id: number; name: string }
    grade?: { id: number; name: string }
}

export interface SmsLog {
    id: number
    school_id: number
    recipient_phone: string
    recipient_name: string | null
    message: string
    status: SmsStatus
    provider: SmsProvider | null
    external_message_id: string | null
    sent_at: string | null
    created_at: string
    updated_at: string
}

export interface SchoolMessage {
    id: number
    school_id: number
    sender_id: number
    recipient_id: number
    pupil_id: number | null
    message: string
    read_at: string | null
    created_at: string
    updated_at: string
    sender?: { id: number; name: string }
    recipient?: { id: number; name: string }
    pupil?: { id: number; first_name: string; last_name: string }
}
