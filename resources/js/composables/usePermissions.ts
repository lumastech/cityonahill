import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function usePermissions() {
    const page = usePage()

    const roleNames = computed<string[]>(() =>
        (page.props.auth?.user?.roles ?? []).map((r: string | { name: string }) =>
            typeof r === 'string' ? r : r.name
        )
    )
    const permissionNames = computed<string[]>(() =>
        (page.props.auth?.user?.permissions ?? []).map((p: string | { name: string }) =>
            typeof p === 'string' ? p : p.name
        )
    )

    const roles = roleNames
    const permissions = permissionNames

    function can(permission: string): boolean {
        return roleNames.value.includes('super-admin') || permissionNames.value.includes(permission)
    }

    function hasRole(role: string): boolean {
        return roleNames.value.includes(role)
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
