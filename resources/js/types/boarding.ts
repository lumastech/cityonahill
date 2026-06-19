export type DormitoryGender = 'male' | 'female'
export type BedStatus = 'available' | 'occupied' | 'maintenance'
export type AllocationStatus = 'active' | 'vacated' | 'suspended'

export interface Dormitory {
    id: number
    school_id: number
    name: string
    gender: DormitoryGender
    capacity: number
    warden_id: number | null
    description: string | null
    occupancy?: number
    available_beds?: number
    total_beds?: number
    occupied_count?: number
    available_count?: number
    beds?: Bed[]
    created_at: string
    updated_at: string
}

export interface Bed {
    id: number
    dormitory_id: number
    bed_number: string
    status: BedStatus
    dormitory?: Dormitory
    active_allocation?: BoardingAllocation | null
    created_at: string
    updated_at: string
}

export interface BoardingAllocation {
    id: number
    school_id: number
    pupil_id: number
    bed_id: number
    term_id: number
    allocated_date: string
    vacated_date: string | null
    fee_amount: number
    status: AllocationStatus
    pupil?: {
        id: number
        first_name: string
        last_name: string
        admission_no: string
        stream?: { id: number; name: string; grade?: { id: number; name: string } }
        guardians?: { id: number; first_name: string; last_name: string; phone?: string }[]
    }
    bed?: Bed
    created_at: string
    updated_at: string
}
