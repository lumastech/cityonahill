<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { FeeInvoice, InvoiceStatus } from '@/types/finance'
import { fmtDate } from '@/utils/date'

interface FeeStructure { id: number; name: string; amount: string; grade_id: number | null; term_id: number; grade?: { id: number; name: string; grade_number: number }; term?: { id: number; name: string } }

interface Props {
    invoices: { data: FeeInvoice[]; links: unknown[] }
    grades: Array<{ id: number; grade_number: number; name: string }>
    terms: Array<{ id: number; name: string }>
    fee_structures: FeeStructure[]
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

const bulkForm = useForm({ fee_structure_id: '', term_id: '', grade_id: '', due_date: '' })
function bulkRaise() {
    if (!confirm(`Raise invoices for all active pupils${bulkForm.grade_id ? ' in selected grade' : ''}? Duplicates will be skipped.`)) return
    bulkForm.post(route('fee-invoices.bulk-raise'), { onSuccess: () => { bulkForm.reset(); applyFilter() } })
}
</script>

<template>
    <AppLayout>
    <Head title="Fee Invoices" />
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-semibold text-gray-900">Fee Invoices</h1>

            <!-- Bulk invoice panel -->
            <div class="mb-6 rounded-lg border border-indigo-100 bg-indigo-50 p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-indigo-800">Bulk Invoice Pupils</h2>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs text-indigo-700 mb-1">Fee Structure</label>
                        <select v-model="bulkForm.fee_structure_id" class="rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">Select fee…</option>
                            <option v-for="fs in fee_structures" :key="fs.id" :value="fs.id">
                                {{ fs.name }} — ZMW {{ Number(fs.amount).toFixed(2) }}
                                <template v-if="fs.grade"> ({{ fs.grade.name }})</template>
                            </option>
                        </select>
                        <p v-if="bulkForm.errors.fee_structure_id" class="mt-1 text-xs text-red-600">{{ bulkForm.errors.fee_structure_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-indigo-700 mb-1">Term</label>
                        <select v-model="bulkForm.term_id" class="rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">Select term…</option>
                            <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <p v-if="bulkForm.errors.term_id" class="mt-1 text-xs text-red-600">{{ bulkForm.errors.term_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-indigo-700 mb-1">Grade <span class="text-indigo-400">(optional — blank = all)</span></label>
                        <select v-model="bulkForm.grade_id" class="rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">All Grades</option>
                            <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-indigo-700 mb-1">Due Date <span class="text-indigo-400">(optional)</span></label>
                        <input v-model="bulkForm.due_date" type="date" class="rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <button
                        :disabled="!bulkForm.fee_structure_id || !bulkForm.term_id || bulkForm.processing"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-40"
                        @click="bulkRaise"
                    >
                        {{ bulkForm.processing ? 'Raising…' : 'Raise Invoices' }}
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <select v-model="gradeId" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter">
                    <option value="">All Grades</option>
                    <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
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

            <!-- Invoice table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Pupil</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Grade</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Fee</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Term</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Due Date</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Balance Due</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="invoice in invoices.data" :key="invoice.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <Link v-if="invoice.pupil" :href="route('pupils.show', invoice.pupil.id)" class="hover:underline text-indigo-700">
                                    {{ invoice.pupil.first_name }} {{ invoice.pupil.last_name }}
                                </Link>
                                <span class="block text-xs text-gray-400">{{ invoice.pupil?.admission_no }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">Grade {{ invoice.pupil?.grade?.grade_number }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ invoice.fee_structure?.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ invoice.term?.name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ fmtDate(invoice.due_date) }}</td>
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
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400">No invoices found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
