<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { useRoles } from '@/composables/useRoles'

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
    permissions_count: number
}

defineProps<{ roles: Role[] }>()

const { deleteRole } = useRoles()

function levelBadgeClass(level: number): string {
    if (level === 1) return 'bg-red-100 text-red-700'
    if (level === 2) return 'bg-blue-100 text-blue-700'
    return 'bg-gray-100 text-gray-600'
}
</script>

<template>
    <AppLayout title="Roles">
        <Head title="Roles" />

        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Roles</h2>
                    <p class="text-sm text-gray-500">Manage roles and their permissions</p>
                </div>
                <Link
                    :href="route('roles.create')"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Role
                </Link>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50">
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Level</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Group</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Permissions</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="role in roles" :key="role.id" class="border-b border-gray-100 last:border-0">
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="levelBadgeClass(role.level)"
                                >
                                    {{ role.name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ role.level }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ role.group }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ role.permissions_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="route('roles.edit', role.id)"
                                        class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                                        title="Edit"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button
                                        :disabled="role.name === 'super-admin'"
                                        class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-30"
                                        title="Delete"
                                        @click="deleteRole(role.id)"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="roles.length === 0">
                            <td colspan="5" class="py-10 text-center text-gray-400">No roles found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
