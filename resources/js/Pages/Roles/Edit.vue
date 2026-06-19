<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { useRoles } from '@/composables/useRoles'
import { reactive } from 'vue'

interface Permission {
    id: number
    name: string
}

interface Role {
    id: number
    name: string
    level: number
    group: number
    permissions: Permission[]
}

const props = defineProps<{
    role: Role
    permissions: Record<string, Permission[]>
}>()

const page = usePage()
const { updateRole, deleteRole, processing } = useRoles()

const form = reactive({
    name: props.role.name,
    level: props.role.level,
    group: props.role.group,
    permission_ids: props.role.permissions.map((p) => p.id),
})

function togglePermission(id: number, checked: boolean): void {
    if (checked) {
        if (!form.permission_ids.includes(id)) form.permission_ids.push(id)
    } else {
        form.permission_ids = form.permission_ids.filter((p) => p !== id)
    }
}

function toggleGroup(perms: Permission[], checked: boolean): void {
    if (checked) {
        perms.forEach((p) => {
            if (!form.permission_ids.includes(p.id)) form.permission_ids.push(p.id)
        })
    } else {
        const ids = perms.map((p) => p.id)
        form.permission_ids = form.permission_ids.filter((id) => !ids.includes(id))
    }
}

function isGroupAllSelected(perms: Permission[]): boolean {
    return perms.length > 0 && perms.every((p) => form.permission_ids.includes(p.id))
}
</script>

<template>
    <AppLayout :title="`Edit Role: ${role.name}`">
        <Head :title="`Edit Role: ${role.name}`" />

        <div class="space-y-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Edit Role</h2>
                    <p class="text-sm text-gray-500">Update permissions for <strong>{{ role.name }}</strong></p>
                </div>
                <button
                    v-if="role.name !== 'super-admin'"
                    class="rounded-lg border border-red-200 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50"
                    @click="deleteRole(role.id)"
                >
                    Delete Role
                </button>
            </div>

            <form class="space-y-6" @submit.prevent="updateRole(role.id, { ...form })">
                <!-- Basic fields -->
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-1">
                        <label for="name" class="text-sm font-medium text-gray-700">Name</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            :disabled="role.name === 'super-admin'"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:bg-gray-50 disabled:text-gray-400"
                        />
                        <p v-if="page.props.errors?.name" class="text-xs text-red-600">{{ page.props.errors.name }}</p>
                    </div>

                    <div class="space-y-1">
                        <label for="level" class="text-sm font-medium text-gray-700">Level</label>
                        <input
                            id="level"
                            v-model.number="form.level"
                            type="number"
                            min="1"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>

                    <div class="space-y-1">
                        <label for="group" class="text-sm font-medium text-gray-700">Group</label>
                        <input
                            id="group"
                            v-model.number="form.group"
                            type="number"
                            min="1"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <!-- Permissions -->
                <div class="space-y-2">
                    <p class="text-sm font-medium text-gray-700">Permissions</p>

                    <details
                        v-for="(perms, groupName) in permissions"
                        :key="groupName"
                        class="rounded-lg border border-gray-200"
                    >
                        <summary class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-50">
                            <input
                                type="checkbox"
                                :checked="isGroupAllSelected(perms)"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                @click.stop
                                @change="toggleGroup(perms, ($event.target as HTMLInputElement).checked)"
                            />
                            <span class="font-medium capitalize text-gray-700">{{ groupName }}</span>
                            <span class="ml-auto text-xs text-gray-400">
                                {{ perms.filter((p) => form.permission_ids.includes(p.id)).length }} / {{ perms.length }}
                            </span>
                        </summary>

                        <div class="grid gap-3 border-t border-gray-100 p-4 sm:grid-cols-2 lg:grid-cols-3">
                            <label
                                v-for="perm in perms"
                                :key="perm.id"
                                class="flex cursor-pointer items-center gap-2"
                            >
                                <input
                                    type="checkbox"
                                    :checked="form.permission_ids.includes(perm.id)"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    @change="togglePermission(perm.id, ($event.target as HTMLInputElement).checked)"
                                />
                                <span class="text-sm text-gray-600">{{ perm.name }}</span>
                            </label>
                        </div>
                    </details>
                </div>

                <div class="flex gap-3">
                    <button
                        type="submit"
                        :disabled="processing || role.name === 'super-admin'"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60"
                    >
                        {{ processing ? 'Saving…' : 'Save Changes' }}
                    </button>
                    <a
                        :href="route('roles.index')"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
