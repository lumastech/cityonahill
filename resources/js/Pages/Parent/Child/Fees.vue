<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import type { ChildSummaryPupil } from '@/types/portal'

defineProps<{
    pupil: ChildSummaryPupil
    invoices: { id: number; description: string; amount: number; paid: number; due_date: string; status: string }[]
    balance: number
}>()

const statusColor: Record<string, string> = {
    paid: 'bg-green-100 text-green-800',
    partial: 'bg-yellow-100 text-yellow-800',
    unpaid: 'bg-red-100 text-red-800',
    overdue: 'bg-red-200 text-red-900',
}
</script>

<template>
    <Head :title="`${pupil.first_name} — Fees`" />
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center gap-3">
                <a :href="route('portal.dashboard')" class="text-sm text-indigo-600 hover:underline">← Back</a>
                <h1 class="text-xl font-semibold text-gray-900">{{ pupil.first_name }} — Fees</h1>
            </div>

            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-sm text-gray-600">Outstanding Balance</p>
                <p :class="['text-2xl font-bold', balance > 0 ? 'text-red-600' : 'text-green-600']">
                    ZMW {{ balance.toFixed(2) }}
                </p>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Description</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Amount</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Paid</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Due</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="inv in invoices" :key="inv.id">
                            <td class="px-4 py-3 text-gray-900">{{ inv.description }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ inv.amount.toFixed(2) }}</td>
                            <td class="px-4 py-3 text-green-700">{{ inv.paid.toFixed(2) }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ inv.due_date }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', statusColor[inv.status] ?? 'bg-gray-100']">
                                    {{ inv.status }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!invoices.length">
                            <td colspan="5" class="px-4 py-6 text-center text-gray-400">No fee invoices available.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
