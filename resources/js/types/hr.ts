export type StaffPosition =
    | 'headteacher' | 'deputy_headteacher' | 'class_teacher' | 'subject_teacher'
    | 'bursar' | 'librarian' | 'boarding_master' | 'transport_coordinator'
    | 'feeding_coordinator' | 'admin' | 'support' | 'counsellor'

export type EmploymentType = 'permanent' | 'contract' | 'temporary' | 'volunteer'
export type StaffStatus = 'active' | 'terminated' | 'suspended' | 'on_leave'
export type LeaveStatus = 'pending' | 'approved' | 'rejected' | 'cancelled'

export interface Staff {
    id: number
    user_id: number
    school_id: number
    employee_no: string
    position: StaffPosition
    department: string | null
    subjects_taught: number[] | null
    employment_type: EmploymentType
    employment_date: string
    end_date: string | null
    basic_salary: number
    napsa_no: string | null
    tpin: string | null
    status: StaffStatus
    full_name?: string
    user?: { id: number; name: string; email: string; profile_photo_path?: string | null }
    leaves?: Leave[]
    payrolls?: Payroll[]
}

export interface LeaveType {
    id: number
    school_id: number
    name: string
    days_per_year: number
    accrues: boolean
}

export interface Leave {
    id: number
    school_id: number
    staff_id: number
    leave_type_id: number
    start_date: string
    end_date: string
    total_days: number
    reason: string
    status: LeaveStatus
    approved_by: number | null
    comment: string | null
    staff?: Pick<Staff, 'id' | 'user'>
    leave_type?: Pick<LeaveType, 'id' | 'name'>
}

export interface Payroll {
    id: number
    school_id: number
    staff_id: number
    month: number
    year: number
    basic_salary: number
    allowances: number
    deductions: number
    napsa_employee: number
    napsa_employer: number
    paye: number
    net_pay: number
    paid_at: string | null
    approved_by: number | null
    staff?: Pick<Staff, 'id' | 'user'>
}

export interface PayrollSummary {
    total_gross: number
    total_napsa_employee: number
    total_napsa_employer: number
    total_paye: number
    total_net: number
    count: number
}
