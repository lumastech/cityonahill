import { router } from '@inertiajs/vue3'

export function useResults() {
    function fetchStreamResults(streamId: number, termId: number, subjectId?: number) {
        router.get(
            route('term-results.index'),
            { stream_id: streamId, term_id: termId, subject_id: subjectId ?? undefined },
            { preserveState: true, replace: true },
        )
    }

    function computeCA(streamId: number, subjectId: number, termId: number) {
        router.post(route('term-results.compute-ca'), { stream_id: streamId, subject_id: subjectId, term_id: termId })
    }

    function publishResults(streamId: number, termId: number) {
        router.post(route('term-results.publish'), { stream_id: streamId, term_id: termId })
    }

    function computeTotal(ca: number | null, exam: number | null): number | null {
        if (ca !== null && exam !== null) return Math.round(((ca + exam) / 2) * 100) / 100
        if (ca !== null) return ca
        if (exam !== null) return exam
        return null
    }

    return { fetchStreamResults, computeCA, publishResults, computeTotal }
}
