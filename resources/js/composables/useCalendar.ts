import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { Term, AcademicYear } from '@/types/calendar'

export function useCalendar() {
    const page = usePage()

    const currentTerm = computed<Term | null>(
        () => (page.props.terms as Term[] | undefined)?.[0] ?? null
    )

    const currentAcademicYear = computed<AcademicYear | null>(
        () => (page.props.current_school as { current_year?: AcademicYear } | null)?.current_year ?? null
    )

    function termLabel(term: Term): string {
        return `${term.name}${term.is_current ? ' (Current)' : ''}`
    }

    function formatDateRange(start: string, end: string): string {
        const fmt = (d: string) =>
            new Date(d).toLocaleDateString('en-ZM', { day: 'numeric', month: 'short', year: 'numeric' })
        return `${fmt(start)} – ${fmt(end)}`
    }

    function isTermActive(term: Term): boolean {
        const today = new Date().toISOString().slice(0, 10)
        return today >= term.start_date && today <= term.end_date
    }

    return {
        currentTerm,
        currentAcademicYear,
        termLabel,
        formatDateRange,
        isTermActive,
    }
}
