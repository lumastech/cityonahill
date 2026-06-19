export type RegistrationStatus = 'pending' | 'submitted' | 'confirmed' | 'withdrawn'
export type EntryMethod = 'manual' | 'bulk_upload'

export interface EczSubjectEntry {
    id: number
    candidate_id: number
    subject_id: number
    entered_by: number
    predicted_grade: string | null
    actual_grade: string | null
    actual_points: number | null
    subject?: { id: number; name: string; code: string }
}

export interface EczCandidate {
    id: number
    school_id: number
    pupil_id: number
    exam_year: number
    grade_level: 7 | 9 | 12
    index_number: string | null
    centre_number: string | null
    registration_status: RegistrationStatus
    division: string | null
    total_points: number | null
    predicted_division?: string
    pupil?: { id: number; first_name: string; last_name: string; admission_no: string; sex?: string }
    subject_entries?: EczSubjectEntry[]
    result?: EczResult | null
}

export interface EczResult {
    id: number
    school_id: number
    candidate_id: number
    published_at: string | null
    raw_result_file: string | null
    entry_method: EntryMethod
}

export interface EczPassRateAnalytics {
    registered: number
    passed: number
    failed: number
    div1: number
    div2: number
    div3: number
    div4: number
    pass_rate_pct: number
}
