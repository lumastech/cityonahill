export interface LessonPlanOption {
    id: number
    name: string
}

export interface StreamOption extends LessonPlanOption {
    grade_id?: number
}

export interface TermOption extends LessonPlanOption {
    is_current?: boolean
}

export interface LessonPlanAttachment {
    id: number
    name: string
    url: string
}

export interface LessonPlan {
    id: number
    school_id: number
    subject_id: number
    stream_id: number
    term_id: number
    title: string
    week_number: number | null
    lesson_date: string | null
    objectives: string
    content: string
    activities: string | null
    materials: string | null
    status: 'draft' | 'submitted' | 'approved' | 'rejected'
    comment: string | null
    submitted_at: string | null
    reviewed_at: string | null
    media_count?: number
    subject?: LessonPlanOption
    stream?: LessonPlanOption
    term?: LessonPlanOption
    submitted_by?: number
    submittedBy?: { id: number; name: string }
    reviewedBy?: { id: number; name: string }
    attachments?: LessonPlanAttachment[]
}

export const LESSON_PLAN_STATUS_COLOR: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700',
    submitted: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
}

export const LESSON_PLAN_STATUS_LABEL: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Pending review',
    approved: 'Approved',
    rejected: 'Rejected',
}
