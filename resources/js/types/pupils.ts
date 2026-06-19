import type { AcademicYear } from '@/types/calendar'

export type PupilStatus = 'active' | 'transferred' | 'withdrawn' | 'completed' | 'suspended'
export type DisabilityType = 'none' | 'visual' | 'hearing' | 'physical' | 'intellectual' | 'other'
export type GuardianRelationship = 'father' | 'mother' | 'guardian' | 'grandparent' | 'sibling' | 'other'

export interface PupilGuardianPivot {
    is_primary: boolean
    is_emergency: boolean
    can_pickup: boolean
}

export interface Guardian {
    id: number
    school_id: number
    user_id: number | null
    first_name: string
    last_name: string
    full_name: string
    relationship: GuardianRelationship
    phone: string
    phone2: string | null
    email: string | null
    nrc: string | null
    occupation: string | null
    employer: string | null
    address: string | null
    pivot?: PupilGuardianPivot
}

export interface PupilTransfer {
    id: number
    school_id: number
    pupil_id: number
    from_school: string
    to_school: string
    transfer_date: string
    reason: string | null
    approved_by: number
}

export interface Pupil {
    id: number
    school_id: number
    admission_no: string
    first_name: string
    last_name: string
    other_name: string | null
    full_name: string
    age: number
    sex: 'male' | 'female'
    dob: string
    place_of_birth: string | null
    nationality: string
    religion: string | null
    tribe: string | null
    disability: DisabilityType
    disability_details: string | null
    blood_group: string | null
    previous_school: string | null
    date_of_admission: string
    grade_id: number
    stream_id: number | null
    academic_year_id: number
    status: PupilStatus
    transfer_school: string | null
    transfer_date: string | null
    primary_guardian: Guardian | null
    grade?: { id: number; name: string; grade_number: number }
    stream?: { id: number; name: string } | null
    academic_year?: AcademicYear
    guardians?: Guardian[]
    transfers?: PupilTransfer[]
}

export interface SchoolStatistics {
    total_pupils: number
    by_grade: Array<{ grade: string; count: number }>
    by_gender: { male: number; female: number }
    by_status: { active: number; transferred: number; withdrawn: number }
}

export interface AdmissionFormData {
    first_name: string
    last_name: string
    other_name: string
    sex: 'male' | 'female' | ''
    dob: string
    nationality: string
    religion: string
    tribe: string
    disability: DisabilityType
    disability_details: string
    blood_group: string
    previous_school: string
    date_of_admission: string
    grade_id: number | null
    stream_id: number | null
    academic_year_id: number | null
    guardian_first_name: string
    guardian_last_name: string
    guardian_relationship: GuardianRelationship | ''
    guardian_phone: string
    guardian_email: string
    is_primary: boolean
    is_emergency: boolean
    can_pickup: boolean
}
