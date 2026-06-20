<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import type { Leave } from '@/types/hr'

defineProps<{ pending_leaves: Leave[] }>()

function approve(leave: Leave, status: 'approved' | 'rejected') {
    const form = useForm({ status, comment: '' })
    form.post(route('leaves.approve', leave.id))
}

function handleAction(leave: Leave, status: 'approved' | 'rejected') {
    const comment = status === 'rejected' ? window.prompt('Rejection reason (optional):') ?? '' : ''
    const form = useForm({ status, comment })
    form.post(route('leaves.approve', leave.id))
}

const leaveStatusColor: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
}
</script>

<template>
    <AppLayout>
    <Head title="Leave Approvals" />
    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Pending Leave Applications</h1>
                <a :href="route('leaves.apply')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Apply for Leave
                </a>
            </div>

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
                        <tr v-for="leave in pending_leaves" :key="leave.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ leave.staff?.user?.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ leave.leave_type?.name }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ leave.start_date }} → {{ leave.end_date }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ leave.total_days }}</td>
                            <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ leave.reason }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', leaveStatusColor[leave.status] ?? 'bg-gray-100']">
                                    {{ leave.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button class="mr-2 text-green-600 hover:text-green-900 text-xs font-medium" @click="handleAction(leave, 'approved')">Approve</button>
                                <button class="text-red-600 hover:text-red-900 text-xs font-medium" @click="handleAction(leave, 'rejected')">Reject</button>
                            </td>
                        </tr>
                        <tr v-if="!pending_leaves.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">No pending leave applications.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
