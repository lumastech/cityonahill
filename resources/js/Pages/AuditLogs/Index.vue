<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { PaginatedResponse } from '@/types/shared'

interface AuditLog {
    id: number
    action: string
    auditable_type: string
    auditable_id: number
    ip_address: string | null
    created_at: string
    user?: { id: number; name: string } | null
}

const props = defineProps<{
    logs: PaginatedResponse<AuditLog>
    filters: { action?: string; model?: string }
}>()

const selectedAction = ref(props.filters.action ?? '')
const modelSearch = ref(props.filters.model ?? '')

function applyFilters() {
    router.get(route('audit-logs.index'), {
        action: selectedAction.value || undefined,
        model: modelSearch.value || undefined,
    }, { preserveState: true, replace: true })
}

function modelLabel(type: string): string {
    return type.split('\\').pop() ?? type
}

function formatDate(dt: string): string {
    return new Date(dt).toLocaleString('en-ZM', { dateStyle: 'medium', timeStyle: 'short' })
}

const ACTION_COLORS: Record<string, string> = {
    created: 'bg-green-100 text-green-700',
    updated: 'bg-blue-100 text-blue-700',
    deleted: 'bg-red-100 text-red-700',
}
</script>

<template>
    <AppLayout title="Audit Log">
        <Head title="Audit Log" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <h1 class="mb-6 text-2xl font-bold text-gray-900">Audit Log</h1>

            <!-- Filters -->
            <div class="mb-4 flex flex-wrap gap-3">
                <select v-model="selectedAction" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilters">
                    <option value="">All Actions</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                </select>
                <input
                    v-model="modelSearch"
                    type="text"
                    placeholder="Filter by model…"
                    class="rounded-md border-gray-300 text-sm shadow-sm"
                    @keyup.enter="applyFilters"
                />
                <button class="rounded-md border px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50" @click="applyFilters">
                    Filter
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">When</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">User</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Action</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Model</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">ID</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!logs.data.length">
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">No audit log entries found.</td>
                        </tr>
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2.5 text-xs text-gray-500 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                            <td class="px-4 py-2.5 text-gray-700">{{ log.user?.name ?? 'System' }}</td>
                            <td class="px-4 py-2.5">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="ACTION_COLORS[log.action] ?? 'bg-gray-100 text-gray-600'">
                                    {{ log.action }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-gray-700">{{ modelLabel(log.auditable_type) }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs text-gray-500">{{ log.auditable_id }}</td>
                            <td class="px-4 py-2.5 text-xs text-gray-400">{{ log.ip_address ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <Pagination :meta="logs" />
            </div>
        </div>
    </AppLayout>
</template>
