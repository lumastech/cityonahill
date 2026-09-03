export interface LessonPlanOption {
    id: number
    name: string
}

export interface StreamOption extends LessonPlanOption {
    grade_id?: number
    grade?: { id: number; name: string }
    /** Active pupils on the class roll, used to pre-fill the lesson plan pupil stats. */
    boys_count?: number
    girls_count?: number
}

export interface TermOption extends LessonPlanOption {
    is_current?: boolean
}

export interface LessonPlanAttachment {
    id: number
    name: string
    url: string
}

export interface LessonPlanStage {
    stage: string
    teacher_activity: string | null
    learner_activity: string | null
    assessment_criteria: string | null
}

export interface LessonPlan {
    id: number
    school_id: number
    subject_id: number
    stream_id: number
    term_id: number
    topic: string
    sub_topic: string | null
    general_competence: string | null
    specific_competence: string | null
    lesson_goal: string
    reference: string | null
    prior_knowledge: string | null
    learning_material: string | null
    learning_environment: string | null
    stages: LessonPlanStage[] | null
    conclusion: string | null
    evaluation: string | null
    week_number: number | null
    lesson_date: string | null
    duration_minutes: number | null
    boys_count: number | null
    girls_count: number | null
    total_pupils: number | null
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

export interface LessonPlanFormFields {
    subject_id: number | null
    stream_id: number | null
    term_id: number | null
    topic: string
    sub_topic: string
    general_competence: string
    specific_competence: string
    lesson_goal: string
    reference: string
    prior_knowledge: string
    learning_material: string
    learning_environment: string
    stages: LessonPlanStage[]
    conclusion: string
    evaluation: string
    week_number: number | null
    lesson_date: string | null
    duration_minutes: number | null
    boys_count: number | null
    girls_count: number | null
    attachments: File[]
}
