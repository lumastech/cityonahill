<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import type { FeeInvoice, InvoiceStatus } from '@/types/finance'

const props = defineProps<{
    invoice: FeeInvoice
    amount_paid: number
    outstanding: number
}>()

const STATUS_COLORS: Record<InvoiceStatus, string> = {
    unpaid: 'bg-red-100 text-red-800',
    partial: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    waived: 'bg-gray-100 text-gray-600',
}

const payForm = useForm({
    invoice_id: props.invoice.id,
    amount: props.outstanding,
    payment_method: 'cash',
    reference: '',
    transaction_id: '',
    payment_date: new Date().toISOString().slice(0, 10),
    mobile_money_provider: '',
})

function submitPayment() {
    payForm.post(route('fee-payments.store'))
}

const MOBILE_METHODS = ['airtel_money', 'mtn_momo']
</script>

<template>
    <Head title="Invoice" />
    <div class="py-6">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <a :href="route('fee-invoices.index')" class="mb-1 block text-sm text-indigo-600 hover:underline">← Invoices</a>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        {{ invoice.pupil?.first_name }} {{ invoice.pupil?.last_name }}
                    </h1>
                    <p class="text-sm text-gray-500">{{ invoice.pupil?.admission_no }} · {{ invoice.fee_structure?.name }} · {{ invoice.term?.name }}</p>
                </div>
                <span :class="['rounded-full px-3 py-1 text-sm font-semibold', STATUS_COLORS[invoice.status]]">
                    {{ invoice.status }}
                </span>
            </div>

            <!-- Fee summary -->
            <div class="mb-6 grid grid-cols-3 gap-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 text-center shadow-sm">
                    <p class="text-lg font-bold">ZMW {{ Number(invoice.balance_due).toFixed(2) }}</p>
                    <p class="text-sm text-gray-500">Total Due</p>
                </div>
                <div class="rounded-lg border border-green-100 bg-green-50 p-4 text-center shadow-sm">
                    <p class="text-lg font-bold text-green-700">ZMW {{ Number(amount_paid).toFixed(2) }}</p>
                    <p class="text-sm text-green-600">Paid</p>
                </div>
                <div class="rounded-lg border border-red-100 bg-red-50 p-4 text-center shadow-sm">
                    <p class="text-lg font-bold text-red-700">ZMW {{ Number(outstanding).toFixed(2) }}</p>
                    <p class="text-sm text-red-600">Outstanding</p>
                </div>
            </div>

            <!-- Payments history -->
            <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <h2 class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700">Payment History</h2>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-600">Date</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-600">Amount</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-600">Method</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-600">Reference</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-600">Received By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in invoice.payments" :key="p.id">
                            <td class="px-4 py-2 text-gray-700">{{ p.payment_date }}</td>
                            <td class="px-4 py-2 text-right font-medium">ZMW {{ Number(p.amount).toFixed(2) }}</td>
                            <td class="px-4 py-2 text-gray-600 capitalize">{{ p.payment_method.replace('_', ' ') }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ p.reference ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ p.received_by_user?.name ?? '—' }}</td>
                        </tr>
                        <tr v-if="!invoice.payments?.length">
                            <td colspan="5" class="px-4 py-4 text-center text-gray-400">No payments yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Record payment form -->
            <div v-if="invoice.status !== 'paid' && invoice.status !== 'waived'" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-gray-900">Record Payment</h2>
                <form class="grid grid-cols-2 gap-4" @submit.prevent="submitPayment">
                    <div>
                        <label class="block text-sm text-gray-700">Amount (ZMW)</label>
                        <input v-model="payForm.amount" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Payment Date</label>
                        <input v-model="payForm.payment_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Method</label>
                        <select v-model="payForm.payment_method" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="cash">Cash</option>
                            <option value="airtel_money">Airtel Money</option>
                            <option value="mtn_momo">MTN MoMo</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Reference</label>
                        <input v-model="payForm.reference" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div v-if="MOBILE_METHODS.includes(payForm.payment_method)">
                        <label class="block text-sm text-gray-700">Transaction ID</label>
                        <input v-model="payForm.transaction_id" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="col-span-2 flex justify-end">
                        <button type="submit" :disabled="payForm.processing" class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">
                            Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
