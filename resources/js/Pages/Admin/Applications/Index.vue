<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Applicant {
    id: number
    name: string
    email: string
}

interface Application {
    id: number
    school_name: string
    subdomain: string
    status: string
    province: string
    level: string
    submitted_at: string | null
    applicant: Applicant
}

interface PaginatedApplications {
    data: Application[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
    applications: PaginatedApplications
    counts: Record<string, number>
}>()

const STATUS_CONFIG: Record<string, { label: string; color: string; bg: string }> = {
    pending:    { label: 'Pending',    color: 'text-yellow-700', bg: 'bg-yellow-100' },
    needs_info: { label: 'Needs Info', color: 'text-orange-700', bg: 'bg-orange-100' },
    approved:   { label: 'Approved',   color: 'text-green-700',  bg: 'bg-green-100'  },
    rejected:   { label: 'Rejected',   color: 'text-red-700',    bg: 'bg-red-100'    },
}

const activeFilter = ref<string>('all')

function filter(status: string) {
    activeFilter.value = status
    router.get(route('admin.applications.index'), status !== 'all' ? { status } : {}, { preserveState: true })
}

function formatDate(iso: string | null): string {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-ZM', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<template>
    <AppLayout title="School Applications">
        <Head title="School Applications" />

        <div class="py-8 px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">School Applications</h1>
            </div>

            <!-- Summary counts -->
            <div class="mb-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <button
                    v-for="(count, key) in counts"
                    :key="key"
                    class="rounded-lg border bg-white p-4 text-left shadow-sm hover:shadow-md transition"
                    :class="activeFilter === key ? 'ring-2 ring-indigo-500' : ''"
                    @click="filter(key)"
                >
                    <p class="text-xs font-medium text-gray-500 uppercase">{{ STATUS_CONFIG[key]?.label ?? key }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ count }}</p>
                </button>
            </div>

            <!-- Table -->
            <div class="rounded-lg border bg-white shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">School</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Applicant</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Province</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Submitted</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="applications.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">No applications found.</td>
                        </tr>
                        <tr v-for="app in applications.data" :key="app.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ app.school_name }}</p>
                                <p class="text-xs text-gray-400 font-mono">{{ app.subdomain }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-gray-900">{{ app.applicant.name }}</p>
                                <p class="text-xs text-gray-400">{{ app.applicant.email }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ app.province }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ formatDate(app.submitted_at) }}</td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        STATUS_CONFIG[app.status]?.bg ?? 'bg-gray-100',
                                        STATUS_CONFIG[app.status]?.color ?? 'text-gray-700',
                                        'rounded-full px-2 py-0.5 text-xs font-medium'
                                    ]"
                                >
                                    {{ STATUS_CONFIG[app.status]?.label ?? app.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="route('admin.applications.show', app.id)"
                                    class="text-indigo-600 hover:text-indigo-800 font-medium text-xs"
                                >
                                    Review →
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="applications.last_page > 1" class="flex items-center justify-between border-t px-4 py-3">
                    <p class="text-xs text-gray-500">
                        Showing {{ applications.data.length }} of {{ applications.total }}
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in applications.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="rounded px-2 py-1 text-xs"
                                :class="link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                                v-html="link.label"
                            />
                            <span v-else class="rounded px-2 py-1 text-xs text-gray-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
