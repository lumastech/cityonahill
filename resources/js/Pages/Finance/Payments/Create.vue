<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { FeeInvoice } from '@/types/finance'

defineProps<{
    invoices: FeeInvoice[]
}>()

const search = ref('')
const form = useForm({
    invoice_id: '',
    amount: '',
    payment_method: 'cash',
    reference: '',
    transaction_id: '',
    payment_date: new Date().toISOString().slice(0, 10),
    mobile_money_provider: '',
})

function submit() {
    form.post(route('fee-payments.store'))
}

const MOBILE_METHODS = ['airtel_money', 'mtn_momo']
</script>

<template>
    <AppLayout>
    <Head title="Record Payment" />
    <div class="py-6">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-semibold text-gray-900">Record Payment</h1>
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm text-gray-700">Invoice</label>
                        <select v-model="form.invoice_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">Select invoice…</option>
                            <option v-for="inv in invoices" :key="inv.id" :value="inv.id">
                                {{ inv.pupil?.admission_no }} — {{ inv.pupil?.first_name }} {{ inv.pupil?.last_name }} — {{ inv.fee_structure?.name }} (ZMW {{ Number(inv.balance_due).toFixed(2) }})
                            </option>
                        </select>
                        <p v-if="form.errors.invoice_id" class="mt-1 text-xs text-red-600">{{ form.errors.invoice_id }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-700">Amount (ZMW)</label>
                            <input v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700">Payment Date</label>
                            <input v-model="form.payment_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="form.errors.payment_date" class="mt-1 text-xs text-red-600">{{ form.errors.payment_date }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700">Method</label>
                        <select v-model="form.payment_method" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="cash">Cash</option>
                            <option value="airtel_money">Airtel Money</option>
                            <option value="mtn_momo">MTN MoMo</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    <div v-if="MOBILE_METHODS.includes(form.payment_method)" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-700">Transaction ID</label>
                            <input v-model="form.transaction_id" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700">Provider</label>
                            <input v-model="form.mobile_money_provider" type="text" placeholder="e.g. Airtel Zambia" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700">Reference / Cheque No</label>
                        <input v-model="form.reference" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a :href="route('fee-invoices.index')" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">
                            Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
