import type { StaffPosition, StaffStatus } from '@/types/hr'

export const POSITION_LABELS: Record<StaffPosition, string> = {
    headteacher: 'Headteacher',
    deputy_headteacher: 'Deputy Headteacher',
    class_teacher: 'Class Teacher',
    subject_teacher: 'Subject Teacher',
    bursar: 'Bursar',
    librarian: 'Librarian',
    boarding_master: 'Boarding Master',
    transport_coordinator: 'Transport Coordinator',
    feeding_coordinator: 'Feeding Coordinator',
    admin: 'Admin',
    support: 'Support',
    counsellor: 'Counsellor',
}

export const POSITION_COLORS: Record<StaffPosition, string> = {
    headteacher: 'bg-purple-100 text-purple-800',
    deputy_headteacher: 'bg-indigo-100 text-indigo-800',
    class_teacher: 'bg-blue-100 text-blue-800',
    subject_teacher: 'bg-cyan-100 text-cyan-800',
    bursar: 'bg-green-100 text-green-800',
    librarian: 'bg-yellow-100 text-yellow-800',
    boarding_master: 'bg-orange-100 text-orange-800',
    transport_coordinator: 'bg-pink-100 text-pink-800',
    feeding_coordinator: 'bg-lime-100 text-lime-800',
    admin: 'bg-gray-100 text-gray-800',
    support: 'bg-gray-100 text-gray-600',
    counsellor: 'bg-teal-100 text-teal-800',
}

export const STATUS_COLORS: Record<StaffStatus, string> = {
    active: 'bg-green-100 text-green-800',
    terminated: 'bg-red-100 text-red-800',
    suspended: 'bg-yellow-100 text-yellow-800',
    on_leave: 'bg-blue-100 text-blue-800',
}

export const MONTH_NAMES = [
    '', 'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
]

export function useHR() {
    function positionLabel(pos: StaffPosition): string {
        return POSITION_LABELS[pos] ?? pos
    }

    function positionColor(pos: StaffPosition): string {
        return POSITION_COLORS[pos] ?? 'bg-gray-100 text-gray-700'
    }

    function statusColor(status: StaffStatus): string {
        return STATUS_COLORS[status] ?? 'bg-gray-100 text-gray-700'
    }

    function monthName(month: number): string {
        return MONTH_NAMES[month] ?? ''
    }

    function formatZmw(amount: number): string {
        return `ZMW ${Number(amount).toFixed(2)}`
    }

    return { positionLabel, positionColor, statusColor, monthName, formatZmw }
}
