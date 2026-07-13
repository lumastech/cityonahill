import type { GradeLetter } from '@/types/shared'

export type AssessmentType = 'ca_test' | 'assignment' | 'practical' | 'mid_term' | 'end_of_term'

export interface GradeScale {
    min: number
    max: number
    letter: GradeLetter
    label: string
}

export interface Assessment {
    id: number
    school_id: number
    stream_id: number
    subject_id: number
    term_id: number
    name: string
    type: AssessmentType
    max_marks: number
    weight_percent: number
    date: string
    instructions: string | null
    created_by: number
    scores_count?: number
    stream?: { id: number; name: string }
    subject?: { id: number; name: string; code: string }
    term?: { id: number; name: string; number: number }
    scores?: AssessmentScore[]
}

export interface AnswerSheet {
    id: number
    score_id: number
    name: string
    mime_type: string
    size: number
    is_image: boolean
    url: string
}

/** Answer sheets on a report card also carry where they came from. */
export interface ReportAnswerSheet extends Omit<AnswerSheet, 'score_id'> {
    assessment: string | null
    subject: string | null
}

export interface AssessmentScore {
    id: number
    assessment_id: number
    pupil_id: number
    marks_obtained: number
    grade_letter: GradeLetter | null
    remarks: string | null
    entered_by: number
    entered_at: string
    pupil?: { id: number; first_name: string; last_name: string; admission_no: string; full_name?: string }
}

export interface TermResult {
    id: number
    school_id: number
    pupil_id: number
    subject_id: number
    term_id: number
    academic_year_id: number
    stream_id: number
    ca_marks: number | null
    exam_marks: number | null
    total_marks: number | null
    grade_letter: GradeLetter | null
    points: number | null
    position_in_stream: number | null
    teacher_comment: string | null
    published: boolean
    pupil?: { id: number; first_name: string; last_name: string; admission_no: string }
    subject?: { id: number; name: string; code: string }
}

export interface AnnualResult {
    id: number
    school_id: number
    pupil_id: number
    academic_year_id: number
    total_marks: number
    average_marks: number
    position_in_stream: number | null
    grade_stream_id: number
    promoted: boolean
    headteacher_comment: string | null
}

export interface ReportCard {
    id: number
    school_id: number
    pupil_id: number
    term_id: number
    academic_year_id: number
    stream_id: number
    class_teacher_comment: string | null
    headteacher_comment: string | null
    attendance_days: number | null
    attendance_present: number | null
    generated_at: string | null
    published_at: string | null
    generated_by: number | null
    pupil?: { id: number; first_name: string; last_name: string; full_name: string; admission_no: string; sex: string; dob: string }
    term?: { id: number; name: string; number: number; start_date: string; end_date: string }
    stream?: { id: number; name: string; grade?: { id: number; name: string; grade_number: number } }
}

export interface PupilTermReport {
    report_card: ReportCard | null
    results: TermResult[]
    position: number | null
    attendance: { days: number | null; present: number | null }
}
