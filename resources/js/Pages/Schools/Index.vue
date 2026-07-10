<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { usePermissions } from '@/composables/usePermissions'
import { Head, Link } from '@inertiajs/vue3'

interface Branch {
    id: number
    name: string
    code: string
    type: string
    level: string
    province: string
    district: string
    status: string
    pupils_count: number
    staff_count: number
    headteacher?: { id: number; name: string } | null
}

defineProps<{ schools: Branch[]; currentSchoolId: number | null }>()

const { can } = usePermissions()

const LEVEL_LABELS: Record<string, string> = {
    primary: 'Primary',
    secondary: 'Secondary',
    basic: 'Basic',
    combined: 'Combined',
}

const STATUS_CLASSES: Record<string, string> = {
    active: 'bg-green-50 text-green-700 ring-green-600/20',
    inactive: 'bg-gray-50 text-gray-600 ring-gray-500/20',
    suspended: 'bg-red-50 text-red-700 ring-red-600/20',
}
</script>

<template>
    <AppLayout title="Branches">
        <Head title="Branches" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Branches</h1>
                <Link v-if="can('school.create')" :href="route('schools.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Add Branch
                </Link>
            </div>

            <!-- Empty state -->
            <div v-if="schools.length === 0" class="rounded-lg border border-dashed bg-white p-12 text-center shadow-sm">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
                <h2 class="mt-4 text-sm font-semibold text-gray-900">No branches yet</h2>
                <p class="mt-1 text-sm text-gray-500">Create your first branch to get started.</p>
                <Link v-if="can('school.create')" :href="route('schools.create')"
                    class="mt-6 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Create a Branch
                </Link>
            </div>

            <!-- Branch list -->
            <div v-else class="overflow-x-auto rounded-lg border bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">District</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Headteacher</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pupils</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Staff</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="school in schools" :key="school.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <Link :href="route('schools.show', school.id)" class="font-medium text-indigo-600 hover:text-indigo-800">
                                    {{ school.name }}
                                </Link>
                                <span v-if="school.id === currentSchoolId"
                                    class="ml-2 inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                    Current
                                </span>
                                <p class="font-mono text-xs text-gray-500">{{ school.code }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ LEVEL_LABELS[school.level] ?? school.level }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ school.district }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ school.headteacher?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ school.pupils_count }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ school.staff_count }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="STATUS_CLASSES[school.status] ?? STATUS_CLASSES.inactive">
                                    {{ school.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
