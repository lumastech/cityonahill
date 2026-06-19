import { router } from '@inertiajs/vue3'
import type { GuardianRelationship } from '@/types/pupils'

const RELATIONSHIP_LABELS: Record<GuardianRelationship, string> = {
    father: 'Father',
    mother: 'Mother',
    guardian: 'Guardian',
    grandparent: 'Grandparent',
    sibling: 'Sibling',
    other: 'Other',
}

export function useGuardians() {
    function removeGuardian(pupilId: number, guardianId: number): void {
        router.delete(route('pupils.guardians.destroy', [pupilId, guardianId]), {
            preserveScroll: true,
        })
    }

    function relationshipLabel(rel: GuardianRelationship | string): string {
        return RELATIONSHIP_LABELS[rel as GuardianRelationship] ?? rel
    }

    return { removeGuardian, relationshipLabel }
}
