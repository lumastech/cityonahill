export interface LessonPlanOption {
    id: number
    name: string
}

export interface StreamOption extends LessonPlanOption {
    grade_id?: number
    grade?: LessonPlanOption | null
    /** Active pupils on the class roll, used to pre-fill the lesson plan pupil stats. */
    boys_count?: number
    girls_count?: number
}

export interface TermOption extends LessonPlanOption {
    is_current?: boolean
}

export type LessonPlanStatus = 'draft' | 'submitted' | 'approved' | 'rejected' | 'reverted'

export interface LessonPlanAttachment {
    id: number
    name: string
    url: string
    size?: number
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
    status: LessonPlanStatus
    /** The reviewer's optional note when approving. */
    comment: string | null
    reject_reason: string | null
    revert_reason: string | null
    submitted_at: string | null
    reviewed_at: string | null
    reverted_at: string | null
    media_count?: number
    subject?: LessonPlanOption
    stream?: StreamOption
    term?: LessonPlanOption
    submitted_by?: number
    reviewed_by?: number | null
    reverted_by?: number | null
    teacher?: LessonPlanOption | null
    reviewer?: LessonPlanOption | null
    reverter?: LessonPlanOption | null
    attachments?: LessonPlanAttachment[]
}

export const LESSON_PLAN_STATUS_COLOR: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700',
    submitted: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    reverted: 'bg-orange-100 text-orange-800',
}

export const LESSON_PLAN_STATUS_LABEL: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Pending review',
    approved: 'Approved',
    rejected: 'Rejected',
    reverted: 'Returned',
}

/** Statuses that hand the plan back to its author to work on. */
export const LESSON_PLAN_EDITABLE_STATUSES: LessonPlanStatus[] = ['draft', 'rejected', 'reverted']

/** A decision can only be withdrawn once one has been made. */
export function isRevertable(plan: LessonPlan): boolean {
    return plan.status === 'approved' || plan.status === 'rejected'
}

export type LessonPlanDecision = 'approved' | 'rejected' | 'reverted'

interface DecisionUi {
    /** Modal heading. */
    title: string
    /** Label on the button that commits the decision. */
    action: string
    /** Colour classes for that button, shared with the row/card buttons. */
    button: string
    /** Label above the single textarea the modal collects. */
    label: string
    hint: string
}

export const LESSON_PLAN_DECISION_UI: Record<LessonPlanDecision, DecisionUi> = {
    approved: {
        title: 'Approve lesson plan',
        action: 'Approve',
        button: 'bg-green-600 hover:bg-green-700',
        label: 'Comment (optional)',
        hint: 'Optional note to the teacher…',
    },
    rejected: {
        title: 'Reject lesson plan',
        action: 'Reject',
        button: 'bg-red-600 hover:bg-red-700',
        label: 'Reason for rejection (required)',
        hint: 'Explain what needs to change…',
    },
    reverted: {
        title: 'Return lesson plan to the teacher',
        action: 'Return plan',
        button: 'bg-orange-600 hover:bg-orange-700',
        label: 'Reason for returning (required)',
        hint: 'Explain why this decision is being withdrawn…',
    },
}

/** "Grade 8 - A" where the grade is known, otherwise just the stream name. */
export function streamLabel(stream?: StreamOption | null): string {
    if (!stream) return '—'

    return stream.grade ? `${stream.grade.name} - ${stream.name}` : stream.name
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
