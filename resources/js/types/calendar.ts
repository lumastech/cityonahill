export type HolidayType = 'public_holiday' | 'school_holiday' | 'event'

export interface SchoolHoliday {
    id: number
    school_id: number
    term_id: number | null
    name: string
    start_date: string
    end_date: string
    type: HolidayType
}

export interface Term {
    id: number
    school_id: number
    academic_year_id: number
    name: string
    number: 1 | 2 | 3
    start_date: string
    end_date: string
    is_current: boolean
    ca_deadline: string | null
    exam_start: string | null
    exam_end: string | null
    holidays?: SchoolHoliday[]
    weeks?: number
}

export interface AcademicYear {
    id: number
    school_id: number
    name: string
    start_date: string
    end_date: string
    is_current: boolean
    terms?: Term[]
    terms_count?: number
}
