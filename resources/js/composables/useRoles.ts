import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useConfirm } from '@/composables/useConfirm'

interface RoleFormData {
    name: string
    level: number
    group: number
    permission_ids: number[]
}

export function useRoles() {
    const { confirm } = useConfirm()
    const processing = ref(false)

    function createRole(data: RoleFormData): void {
        router.post(route('roles.store'), data, {
            onStart: () => { processing.value = true },
            onFinish: () => { processing.value = false },
        })
    }

    function updateRole(id: number, data: RoleFormData): void {
        router.put(route('roles.update', id), data, {
            onStart: () => { processing.value = true },
            onFinish: () => { processing.value = false },
        })
    }

    async function deleteRole(id: number): Promise<void> {
        const confirmed = await confirm('Delete this role? This will remove it from all users assigned to it.', {
            title: 'Delete Role',
            confirmLabel: 'Delete',
            dangerMode: true,
        })
        if (confirmed) {
            router.delete(route('roles.destroy', id))
        }
    }

    return { createRole, updateRole, deleteRole, processing }
}
