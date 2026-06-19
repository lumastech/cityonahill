import { router } from '@inertiajs/vue3'
import type { AssessmentType } from '@/types/results'
import type { GradeLetter } from '@/types/shared'

const TYPE_LABELS: Record<AssessmentType, string> = {
    ca_test: 'CA Test',
    assignment: 'Assignment',
    practical: 'Practical',
    mid_term: 'Mid-Term',
    end_of_term: 'End of Term',
}

const TYPE_COLORS: Record<AssessmentType, string> = {
    ca_test: 'bg-blue-100 text-blue-800',
    assignment: 'bg-purple-100 text-purple-800',
    practical: 'bg-green-100 text-green-800',
    mid_term: 'bg-yellow-100 text-yellow-800',
    end_of_term: 'bg-red-100 text-red-800',
}

const GRADE_COLORS: Partial<Record<GradeLetter, string>> = {
    A: 'bg-green-100 text-green-800',
    B: 'bg-blue-100 text-blue-800',
    C: 'bg-yellow-100 text-yellow-800',
    D: 'bg-orange-100 text-orange-800',
    F: 'bg-red-100 text-red-800',
}

export function useAssessments() {
    function typeLabel(type: AssessmentType): string {
        return TYPE_LABELS[type] ?? type
    }

    function typeColor(type: AssessmentType): string {
        return TYPE_COLORS[type] ?? 'bg-gray-100 text-gray-800'
    }

    function gradeColor(letter: GradeLetter | null): string {
        return letter ? (GRADE_COLORS[letter] ?? 'bg-gray-100 text-gray-800') : 'bg-gray-100 text-gray-400'
    }

    function computeGradeLetter(marks: number, maxMarks: number): GradeLetter {
        if (maxMarks === 0) return 'F'
        const pct = (marks / maxMarks) * 100
        if (pct >= 75) return 'A'
        if (pct >= 65) return 'B'
        if (pct >= 50) return 'C'
        if (pct >= 40) return 'D'
        return 'F'
    }

    function goToScoreEntry(assessmentId: number) {
        router.get(route('assessments.show', assessmentId))
    }

    return { typeLabel, typeColor, gradeColor, computeGradeLetter, goToScoreEntry }
}
