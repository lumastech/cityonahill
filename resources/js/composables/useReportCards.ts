import { router } from '@inertiajs/vue3'

export function useReportCards() {
    function generateForStream(streamId: number, termId: number) {
        router.post(route('report-cards.store'), { stream_id: streamId, term_id: termId })
    }

    function publishForStream(streamId: number, termId: number) {
        router.post(route('report-cards.publish'), { stream_id: streamId, term_id: termId })
    }

    function printCard() {
        window.print()
    }

    function attendancePct(present: number | null, days: number | null): string {
        if (!days || !present) return '—'
        return ((present / days) * 100).toFixed(0) + '%'
    }

    return { generateForStream, publishForStream, printCard, attendancePct }
}
