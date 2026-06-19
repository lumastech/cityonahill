<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { FeeInvoice, InvoiceStatus } from '@/types/finance'

interface Props {
    invoices: { data: FeeInvoice[]; links: unknown[] }
    grades: Array<{ id: number; grade_number: number }>
    terms: Array<{ id: number; name: string }>
    filters: { gradeId?: number | null; termId?: number | null; status?: string | null }
}

const props = defineProps<Props>()

const gradeId = ref(props.filters.gradeId ?? '')
const termId = ref(props.filters.termId ?? '')
const status = ref(props.filters.status ?? '')

function applyFilter() {
    router.get(route('fee-invoices.index'), { grade_id: gradeId.value, term_id: termId.value, status: status.value }, { preserveState: true })
}

const STATUS_COLORS: Record<InvoiceStatus, string> = {
    unpaid: 'bg-red-100 text-red-800',
    partial: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    waived: 'bg-gray-100 text-gray-600',
}

const bulkForm = useForm({ fee_structure_id: '', term_id: '', grade_id: '' })
function bulkRaise() {
    bulkForm.post(route('fee-invoices.bulk-raise'), { onSuccess: () => bulkForm.reset() })
}
</script>

<template>
    <Head title="Fee Invoices" />
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-gray-900 mr-4">Fee Invoices</h1>

                <select v-model="gradeId" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter">
                    <option value="">All Grades</option>
                    <option v-for="g in grades" :key="g.id" :value="g.id">Grade {{ g.grade_number }}</option>
                </select>
                <select v-model="termId" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter">
                    <option value="">All Terms</option>
                    <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
                <select v-model="status" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter">
                    <option value="">All Statuses</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                    <option value="waived">Waived</option>
                </select>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Pupil</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Grade</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Fee</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Term</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Balance Due</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="invoice in invoices.data" :key="invoice.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ invoice.pupil?.first_name }} {{ invoice.pupil?.last_name }}
                                <span class="block text-xs text-gray-400">{{ invoice.pupil?.admission_no }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">Grade {{ invoice.pupil?.grade?.grade_number }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ invoice.fee_structure?.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ invoice.term?.name }}</td>
                            <td class="px-4 py-3 text-right font-medium">ZMW {{ Number(invoice.balance_due).toFixed(2) }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', STATUS_COLORS[invoice.status]]">
                                    {{ invoice.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a :href="route('fee-invoices.show', invoice.id)" class="text-xs text-indigo-600 hover:underline">View</a>
                            </td>
                        </tr>
                        <tr v-if="!invoices.data.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">No invoices found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
