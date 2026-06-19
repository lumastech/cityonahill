export type AttendanceStatus = 'present' | 'absent' | 'late' | 'excused' | 'sick'
export type SessionType = 'morning' | 'afternoon' | 'full_day'

export interface AttendanceRecord {
    id: number
    session_id: number
    pupil_id: number
    status: AttendanceStatus
    remarks: string | null
    record_id?: number
    pupil_name?: string
    admission_no?: string
}

export interface AttendanceSession {
    id: number
    school_id: number
    stream_id: number
    term_id: number
    date: string
    session_type: SessionType
    recorded_by: number | null
    finalized: boolean
    records?: AttendanceRecord[]
}

export interface ClassRegister {
    session: AttendanceSession | null
    records: AttendanceRecord[]
    pupils: Array<{ id: number; first_name: string; last_name: string; full_name: string; admission_no: string }>
}

export interface TermAttendanceSummary {
    total_days: number
    present: number
    absent: number
    late: number
    excused: number
    sick: number
    percentage: number
    daily: Array<{ date: string; status: AttendanceStatus }>
}

export interface StreamDailySummary {
    stream_id: number
    stream_name: string
    grade_name: string | null
    session: {
        id: number
        present: number
        absent: number
        late: number
        total: number
        finalized: boolean
    } | null
}
