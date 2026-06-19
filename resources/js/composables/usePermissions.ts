import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function usePermissions() {
    const page = usePage()

    const roles = computed(() => page.props.auth?.user?.roles ?? [])
    const permissions = computed(() => page.props.auth?.user?.permissions ?? [])

    function can(permission: string): boolean {
        return permissions.value.includes(permission)
    }

    function hasRole(role: string): boolean {
        return roles.value.includes(role)
    }

    function isSuperAdmin(): boolean {
        return hasRole('super-admin')
    }

    function canAny(perms: string[]): boolean {
        return perms.some(can)
    }

    function canAll(perms: string[]): boolean {
        return perms.every(can)
    }

    return { can, hasRole, isSuperAdmin, canAny, canAll, roles, permissions }
}
