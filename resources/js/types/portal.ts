import type { TermResult } from '@/types/results'

export interface ChildSummaryPupil {
    id: number
    first_name: string
    last_name: string
    admission_no: string
    sex: string
    grade?: { id: number; name: string; grade_number: number }
    stream?: { id: number; name: string }
}

export interface AttendanceSummary {
    total: number
    present: number
    percentage: number
}

export interface ChildSummary {
    pupil: ChildSummaryPupil
    current_term: { id: number; name: string; number: number } | null
    attendance_summary: AttendanceSummary | null
    latest_results: TermResult[]
    fee_balance: number | null
    notices: unknown[]
}

export interface ParentMessage {
    id: number
    school_id: number
    sender_id: number
    recipient_id: number
    pupil_id: number | null
    message: string
    read_at: string | null
    created_at: string
    sender?: { id: number; name: string }
    recipient?: { id: number; name: string }
    pupil?: { id: number; first_name: string; last_name: string } | null
}

export interface PortalNotification {
    id: number
    user_id: number
    school_id: number
    title: string
    message: string
    type: string
    related_type: string | null
    related_id: number | null
    read_at: string | null
    created_at: string
}
