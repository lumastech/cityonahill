import type { GradeLetter } from '@/types/shared'

const GRADING_SCALE = [
    { min: 75, max: 100, letter: 'A' as GradeLetter, label: 'Distinction' },
    { min: 65, max: 74, letter: 'B' as GradeLetter, label: 'Merit' },
    { min: 50, max: 64, letter: 'C' as GradeLetter, label: 'Credit' },
    { min: 40, max: 49, letter: 'D' as GradeLetter, label: 'Pass' },
    { min: 0, max: 39, letter: 'F' as GradeLetter, label: 'Fail' },
] as const

const ECZ_POINTS: Record<GradeLetter, number> = {
    A: 1,
    B: 2,
    C: 3,
    D: 4,
    F: 9,
}

export function useGrading() {
    function getGrade(marks: number) {
        return GRADING_SCALE.find((g) => marks >= g.min && marks <= g.max) ?? GRADING_SCALE[4]
    }

    function getGradeLetter(marks: number): GradeLetter {
        return getGrade(marks).letter
    }

    function getGradeLabel(marks: number): string {
        return getGrade(marks).label
    }

    function getEczPoints(letter: GradeLetter): number {
        return ECZ_POINTS[letter]
    }

    /**
     * Calculate ECZ division based on aggregate points from best subjects.
     * Follows Zambia ECZ Grade 9 / Grade 12 division criteria.
     *
     * Division I  : 6–24 points (best 6 subjects)
     * Division II : 25–36 points
     * Division III: 37–48 points
     * Division IV : 49–54 points (conditional pass)
     * Fail        : > 54 or any F in core subject (simplified here to points only)
     */
    function getDivision(totalPoints: number, subjectCount: number): string {
        const best6 = subjectCount >= 6 ? totalPoints : null

        if (best6 === null) return 'Insufficient subjects'

        if (best6 <= 24) return 'Division I'
        if (best6 <= 36) return 'Division II'
        if (best6 <= 48) return 'Division III'
        if (best6 <= 54) return 'Division IV'

        return 'Fail'
    }

    return { getGradeLetter, getGradeLabel, getEczPoints, getDivision }
}
