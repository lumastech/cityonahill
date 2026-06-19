export type RouteStatus = 'active' | 'inactive'
export type TransportDirection = 'to_school' | 'from_school' | 'both'
export type AssignmentStatus = 'active' | 'suspended'

export interface TransportRoute {
    id: number
    school_id: number
    name: string
    description?: string | null
    pickup_points: string[]
    vehicle_registration?: string | null
    vehicle_type?: string | null
    capacity: number
    driver_name?: string | null
    driver_phone?: string | null
    driver_user_id?: number | null
    status: RouteStatus
    occupancy?: number
    created_at: string
    updated_at: string
}

export interface PupilTransport {
    id: number
    school_id: number
    pupil_id: number
    route_id: number
    pickup_point: string
    direction: TransportDirection
    term_id: number
    fee_amount: number
    status: AssignmentStatus
    pupil?: {
        id: number
        first_name: string
        last_name: string
        admission_no: string
        stream?: { id: number; name: string; grade?: { id: number; name: string } }
    }
    route?: TransportRoute
    created_at: string
    updated_at: string
}

export interface RouteManifest {
    route: TransportRoute
    assignments: PupilTransport[]
    term_id: number | null
}
