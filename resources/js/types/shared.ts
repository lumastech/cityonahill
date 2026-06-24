export interface School {
    id: number
    name: string
    code: string
    type: 'government' | 'private' | 'mission' | 'grant-aided'
    level: 'primary' | 'secondary' | 'basic' | 'combined'
    province: string
    district: string
    phone: string
    moe_registration_no: string | null
    logo_url: string | null
    status: 'active' | 'inactive' | 'suspended'
}

export interface AuthUser {
    id: number
    first_name: string
    last_name: string
    email: string
    phone: string | null
    roles: string[]
    permissions: string[]
    school: School | null
    is_parent: boolean
    status: 'active' | 'inactive' | 'suspended'
}

export interface Term {
    id: number
    name: string
    academic_year_id: number
    start_date: string
    end_date: string
    is_current: boolean
}

export interface PaginatedResponse<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: { url: string | null; label: string; active: boolean }[]
}

export interface FlashMessages {
    success: string | null
    error: string | null
    info: string | null
    link_url: string | null
}

export interface SharedProps {
    auth: { user: AuthUser; staff_profile_url: string | null }
    flash: FlashMessages
    current_school: School | null
    terms: Term[]
    settings: Record<string, string>
    nav: NavGroup[]
}

export type ZambianProvince =
    | 'Central'
    | 'Copperbelt'
    | 'Eastern'
    | 'Luapula'
    | 'Lusaka'
    | 'Muchinga'
    | 'Northern'
    | 'North-Western'
    | 'Southern'
    | 'Western'

export type GradeLetter = 'A' | 'B' | 'C' | 'D' | 'F'

export interface NavItem {
    label: string
    url: string | null
}

export interface NavGroup {
    label: string
    icon: string
    items: NavItem[]
}

declare module '@inertiajs/core' {
    interface PageProps {
        nav: NavGroup[]
    }
}
