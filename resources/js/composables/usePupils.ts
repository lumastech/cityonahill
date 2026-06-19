import type { Pupil, PupilStatus } from '@/types/pupils'

export function usePupils() {
    function exportToCsv(pupils: Pupil[]): void {
        const headers = ['Admission No', 'First Name', 'Last Name', 'Grade', 'Stream', 'Sex', 'Status', 'Age']
        const rows = pupils.map((p) => [
            p.admission_no,
            p.first_name,
            p.last_name,
            p.grade?.name ?? '',
            p.stream?.name ?? '',
            p.sex,
            p.status,
            p.age,
        ])

        const csv = [headers, ...rows].map((row) => row.join(',')).join('\n')
        const blob = new Blob([csv], { type: 'text/csv' })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = 'pupils.csv'
        a.click()
        URL.revokeObjectURL(url)
    }

    function statusClass(status: PupilStatus): string {
        const map: Record<PupilStatus, string> = {
            active: 'bg-green-100 text-green-800',
            transferred: 'bg-blue-100 text-blue-800',
            withdrawn: 'bg-gray-100 text-gray-600',
            completed: 'bg-purple-100 text-purple-800',
            suspended: 'bg-red-100 text-red-800',
        }
        return map[status] ?? 'bg-gray-100 text-gray-600'
    }

    function sexClass(sex: 'male' | 'female'): string {
        return sex === 'male' ? 'bg-blue-50 text-blue-700' : 'bg-pink-50 text-pink-700'
    }

    function admissionNoPreview(schoolCode: string, year?: number): string {
        const y = year ?? new Date().getFullYear()
        return `${schoolCode.toUpperCase()}/${y}/####`
    }

    return { exportToCsv, statusClass, sexClass, admissionNoPreview }
}
