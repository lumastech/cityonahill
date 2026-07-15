<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useHR } from '@/composables/useHR'
import type { Staff } from '@/types/hr'

defineProps<{ staff: Staff[]; can_export: boolean }>()

const { positionLabel, positionColor, statusColor } = useHR()
const search = ref('')

function formatLastLogin(value?: string | null): string {
    if (!value) return 'Never'
    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    })
}
</script>

<template>
    <AppLayout title="Staff Directory">
    <Head title="Staff Directory" />
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Staff Directory</h1>
                <div class="flex items-center gap-3">
                    <!-- Plain anchor, not Inertia Link: this is a file download, not a page visit. -->
                    <a
                        v-if="can_export"
                        :href="route('staff.export')"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Export CSV
                    </a>
                    <Link :href="route('staff.create')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Add Staff
                    </Link>
                </div>
            </div>

            <div class="mb-4">
                <input v-model="search" type="text" placeholder="Search name, position, employee no…" class="w-64 rounded-md border-gray-300 text-sm shadow-sm" />
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Position</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Department</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Employee No</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Last Login</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="member in staff.filter(s => !search || s.user?.name.toLowerCase().includes(search.toLowerCase()) || s.position.includes(search.toLowerCase()) || s.employee_no.toLowerCase().includes(search.toLowerCase()))"
                            :key="member.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-semibold text-indigo-600">
                                        {{ member.user?.name?.charAt(0) }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ member.user?.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', positionColor(member.position)]">
                                    {{ positionLabel(member.position) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ member.department ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ member.employee_no }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', statusColor(member.status)]">
                                    {{ member.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600" :class="{ 'text-gray-400 italic': !member.user?.last_login_at }">
                                {{ formatLastLogin(member.user?.last_login_at) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('staff.show', member.id)" class="text-indigo-600 hover:text-indigo-900 text-sm">View</Link>
                            </td>
                        </tr>
                        <tr v-if="!staff.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">No staff records found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
