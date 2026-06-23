<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import type { Leave } from '@/types/hr'
import { fmtDate } from '@/utils/date'

interface Paginated { data: Leave[]; links: { url: string | null; label: string; active: boolean }[]; total: number }

const props = defineProps<{
    leaves: Paginated
    filters: { status: string; search: string | null }
}>()

const status = ref(props.filters.status ?? 'pending')
const search = ref(props.filters.search ?? '')

function applyFilters() {
    router.get(route('leaves.index'), {
        status: status.value,
        search: search.value || undefined,
    }, { preserveState: true, replace: true })
}

watch(status, applyFilters)

let debounce: ReturnType<typeof setTimeout>
watch(search, () => {
    clearTimeout(debounce)
    debounce = setTimeout(applyFilters, 350)
})

function handleAction(leave: Leave, action: 'approved' | 'rejected') {
    const comment = action === 'rejected' ? window.prompt('Rejection reason (optional):') ?? '' : ''
    useForm({ status: action, comment }).post(route('leaves.approve', leave.id))
}

const STATUS_COLOR: Record<string, string> = {
    pending:  'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
}
</script>

<template>
    <AppLayout>
    <Head title="Leave Applications" />
    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

            <!-- Header + filters -->
            <div class="mb-5 flex flex-wrap items-center gap-3">
                <h1 class="mr-4 text-2xl font-semibold text-gray-900">Leave Applications</h1>

                <!-- Status filter -->
                <div class="flex rounded-md border border-gray-300 overflow-hidden text-sm">
                    <button v-for="opt in [['pending','Pending'], ['approved','Approved'], ['rejected','Rejected'], ['all','All']]"
                        :key="opt[0]"
                        @click="status = opt[0]"
                        :class="[
                            'px-3 py-1.5 font-medium transition-colors',
                            status === opt[0]
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white text-gray-600 hover:bg-gray-50'
                        ]">
                        {{ opt[1] }}
                    </button>
                </div>

                <!-- Search -->
                <input v-model="search" type="search" placeholder="Search staff name…"
                    class="rounded-md border-gray-300 text-sm shadow-sm w-52" />

                <span class="ml-auto text-sm text-gray-400">{{ leaves.total }} record{{ leaves.total !== 1 ? 's' : '' }}</span>

                <a :href="route('leaves.apply')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Apply for Leave
                </a>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Staff</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Type</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Period</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Days</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Reason</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="leave in leaves.data" :key="leave.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ leave.staff?.user?.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ leave.leave_type?.name }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                {{ fmtDate(leave.start_date) }} → {{ fmtDate(leave.end_date) }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ leave.total_days }}</td>
                            <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ leave.reason }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium capitalize', STATUS_COLOR[leave.status] ?? 'bg-gray-100 text-gray-600']">
                                    {{ leave.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <template v-if="leave.status === 'pending'">
                                    <button class="mr-3 text-xs font-medium text-green-600 hover:text-green-900"
                                        @click="handleAction(leave, 'approved')">Approve</button>
                                    <button class="text-xs font-medium text-red-600 hover:text-red-900"
                                        @click="handleAction(leave, 'rejected')">Reject</button>
                                </template>
                                <span v-else class="text-xs text-gray-300">—</span>
                            </td>
                        </tr>
                        <tr v-if="!leaves.data.length">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                                No {{ status !== 'all' ? status : '' }} leave applications found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="leaves.links.length > 3" class="mt-4 flex justify-center gap-1 text-sm">
                <template v-for="link in leaves.links" :key="link.label">
                    <a v-if="link.url" :href="link.url"
                        class="rounded border px-3 py-1"
                        :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                        v-html="link.label" />
                    <span v-else class="rounded border border-gray-100 px-3 py-1 text-gray-300" v-html="link.label" />
                </template>
            </div>

        </div>
    </div>
    </AppLayout>
</template>
