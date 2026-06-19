import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { School } from '@/types/shared'

export function useSchool() {
    const page = usePage()

    const currentSchool = computed<School | null>(() => page.props.current_school)

    const schoolName = computed<string>(() => currentSchool.value?.name ?? '')

    const logoUrl = computed<string | null>(() => currentSchool.value?.logo_url ?? null)

    return { currentSchool, schoolName, logoUrl }
}
