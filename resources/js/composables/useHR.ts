import type { StaffStatus } from '@/types/hr'

export const POSITION_COLORS: Record<string, string> = {
    'headteacher':           'bg-purple-100 text-purple-800',
    'deputy-headteacher':    'bg-indigo-100 text-indigo-800',
    'class-teacher':         'bg-blue-100 text-blue-800',
    'subject-teacher':       'bg-cyan-100 text-cyan-800',
    'finance-officer':       'bg-green-100 text-green-800',
    'librarian':             'bg-yellow-100 text-yellow-800',
    'boarding-master':       'bg-orange-100 text-orange-800',
    'transport-coordinator': 'bg-pink-100 text-pink-800',
    'feeding-coordinator':   'bg-lime-100 text-lime-800',
    'school-admin':          'bg-gray-100 text-gray-800',
}

export const STATUS_COLORS: Record<StaffStatus, string> = {
    active:     'bg-green-100 text-green-800',
    terminated: 'bg-red-100 text-red-800',
    suspended:  'bg-yellow-100 text-yellow-800',
    on_leave:   'bg-blue-100 text-blue-800',
}

export const MONTH_NAMES = [
    '', 'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
]

export function useHR() {
    /** Format a role name as a human-readable position label. */
    function positionLabel(pos: string): string {
        return pos.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
    }

    function positionColor(pos: string): string {
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
