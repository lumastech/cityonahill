<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { ReceivablesAging } from '@/types/finance'
import { fmtDate } from '@/utils/date'

const props = defineProps<{
    aging: ReceivablesAging
    filters: { as_of: string | null }
}>()

const asOf = ref(props.filters.as_of ?? props.aging.as_of)

function applyFilter() {
    router.get(route('finance.receivables'), { as_of: asOf.value }, { preserveState: true })
}

function formatZmw(n: number) { return `ZMW ${Number(n).toFixed(2)}` }

const BUCKET_COLORS: Record<string, string> = {
    current: 'border-gray-200 bg-white text-gray-900',
    '1_30': 'border-yellow-100 bg-yellow-50 text-yellow-800',
    '31_60': 'border-orange-100 bg-orange-50 text-orange-800',
    '61_90': 'border-red-100 bg-red-50 text-red-700',
    '90_plus': 'border-red-200 bg-red-100 text-red-800',
}
</script>

<template>
    <AppLayout>
    <Head title="Receivables" />
    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-semibold text-gray-900">Accounts Receivable</h1>
                <div class="flex items-center gap-2 text-sm">
                    <label class="text-gray-600">As of</label>
                    <input v-model="asOf" type="date" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter" />
                </div>
            </div>

            <!-- Aging buckets -->
            <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <div
                    v-for="b in aging.buckets"
                    :key="b.key"
                    :class="['rounded-lg border p-4 text-center shadow-sm', BUCKET_COLORS[b.key]]"
                >
                    <p class="text-lg font-bold">{{ formatZmw(b.amount) }}</p>
                    <p class="text-sm opacity-80">{{ b.label }}</p>
                    <p class="mt-1 text-xs opacity-60">{{ b.count }} invoice{{ b.count === 1 ? '' : 's' }}</p>
                </div>
            </div>

            <div class="mb-6 rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                <span class="text-sm text-indigo-600">Total outstanding</span>
                <span class="ml-3 text-xl font-bold text-indigo-800">{{ formatZmw(aging.total_outstanding) }}</span>
                <span class="ml-2 text-sm text-indigo-500">across {{ aging.total_count }} invoices</span>
            </div>

            <!-- Debtors -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <h2 class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700">Debtors</h2>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Pupil</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Adm. No</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Grade</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Invoices</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Oldest Due</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="d in aging.debtors" :key="d.pupil_id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <Link :href="route('pupils.show', d.pupil_id)" class="text-indigo-600 hover:underline">{{ d.name }}</Link>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ d.admission_no }}</td>
                            <td class="px-4 py-3 text-gray-700">Grade {{ d.grade }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ d.invoice_count }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ fmtDate(d.oldest_due_date) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-red-700">{{ formatZmw(d.outstanding) }}</td>
                        </tr>
                        <tr v-if="!aging.debtors.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">No outstanding receivables. 🎉</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
