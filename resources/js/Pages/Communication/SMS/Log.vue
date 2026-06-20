<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import type { SmsLog } from '@/types/communication'

defineProps<{
    logs: {
        data: SmsLog[]
        links: { url: string | null; label: string; active: boolean }[]
        meta: { total: number; current_page: number; last_page: number }
    }
}>()

const STATUS_COLORS: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700',
    sent: 'bg-blue-100 text-blue-700',
    delivered: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
}

const statusFilter = ref('')

function applyFilter() {
    router.get(route('sms.log'), { status: statusFilter.value || undefined }, { preserveState: true })
}
</script>

<template>
    <AppLayout>
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">SMS Log</h1>
            <a :href="route('sms.compose')" class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Compose SMS
            </a>
        </div>

        <div class="mb-4 flex gap-2">
            <select
                v-model="statusFilter"
                @change="applyFilter"
                class="rounded border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="sent">Sent</option>
                <option value="delivered">Delivered</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Phone</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Message</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Provider</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Sent At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono">{{ log.recipient_phone }}</td>
                        <td class="max-w-xs truncate px-4 py-3 text-gray-600">{{ log.message }}</td>
                        <td class="px-4 py-3">
                            <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', STATUS_COLORS[log.status]]">
                                {{ log.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ log.provider ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ log.sent_at ? new Date(log.sent_at).toLocaleString() : '—' }}
                        </td>
                    </tr>
                    <tr v-if="logs.data.length === 0">
                        <td colspan="5" class="py-12 text-center text-gray-400">No SMS logs found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="logs.meta.last_page > 1" class="mt-4 flex justify-center gap-1">
            <template v-for="link in logs.links" :key="link.label">
                <a
                    v-if="link.url"
                    :href="link.url"
                    :class="[
                        'rounded border px-3 py-1 text-sm',
                        link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 text-gray-600 hover:bg-gray-50'
                    ]"
                    v-html="link.label"
                />
                <span v-else class="rounded border border-gray-200 px-3 py-1 text-sm text-gray-400" v-html="link.label" />
            </template>
        </div>
    </div>
    </AppLayout>
</template>
