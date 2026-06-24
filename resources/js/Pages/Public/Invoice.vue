<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { FeeInvoice } from '@/types/finance'
import { fmtDate } from '@/utils/date'

const props = defineProps<{
    expired: boolean
    token?: string
    invoice?: FeeInvoice
    amount_paid?: number
    outstanding?: number
    gateway_active?: boolean
    payer_phone?: string | null
}>()

const page = usePage()
const flash = computed(() => page.props.flash as { success?: string; error?: string; info?: string })

const form = useForm({
    phone:  props.payer_phone ?? '',
    amount: props.outstanding ?? 0,
})

const paid = computed(() => props.invoice?.status === 'paid')

function submit() {
    form.post(route('invoices.pay.post', props.token!))
}

function fmt(n: number | string) {
    return 'ZMW ' + Number(n).toFixed(2)
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="Pay Invoice" />

        <!-- Header bar -->
        <div class="bg-white border-b border-gray-200 px-4 py-4 flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600 text-white font-bold text-sm">Z</div>
            <span class="font-semibold text-gray-800">School Payment Portal</span>
        </div>

        <div class="mx-auto max-w-lg px-4 py-10">

            <!-- Expired -->
            <div v-if="expired" class="rounded-xl border border-red-200 bg-red-50 p-8 text-center">
                <div class="mb-3 text-4xl">⏰</div>
                <h1 class="text-xl font-bold text-red-800 mb-2">Link Expired</h1>
                <p class="text-red-600 text-sm">This payment link is no longer valid. Please contact the school for a new link.</p>
            </div>

            <template v-else-if="invoice">

                <!-- Flash messages -->
                <div v-if="flash.success" class="mb-5 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    ✓ {{ flash.success }}
                </div>
                <div v-if="flash.error" class="mb-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ flash.error }}
                </div>

                <!-- Invoice card -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">

                    <!-- Invoice header -->
                    <div class="bg-indigo-600 px-6 py-5 text-white">
                        <p class="text-xs uppercase tracking-wide opacity-75 mb-1">Fee Invoice</p>
                        <h1 class="text-xl font-bold">
                            {{ invoice.pupil?.first_name }} {{ invoice.pupil?.last_name }}
                        </h1>
                        <p class="text-sm opacity-90 mt-0.5">{{ invoice.fee_structure?.name }} · {{ invoice.term?.name }}</p>
                    </div>

                    <!-- Amounts -->
                    <div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-gray-100">
                        <div class="px-4 py-4 text-center">
                            <p class="text-base font-bold text-gray-900">{{ fmt(invoice.balance_due) }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">Invoice Total</p>
                        </div>
                        <div class="px-4 py-4 text-center">
                            <p class="text-base font-bold text-green-700">{{ fmt(amount_paid ?? 0) }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">Paid</p>
                        </div>
                        <div class="px-4 py-4 text-center">
                            <p class="text-base font-bold text-red-700">{{ fmt(outstanding ?? 0) }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">Outstanding</p>
                        </div>
                    </div>

                    <!-- Already paid -->
                    <div v-if="paid" class="px-6 py-8 text-center">
                        <div class="text-4xl mb-3">✅</div>
                        <p class="font-semibold text-green-700">This invoice is fully paid. Thank you!</p>
                    </div>

                    <!-- Pay form -->
                    <div v-else-if="gateway_active" class="px-6 py-6">
                        <h2 class="text-sm font-semibold text-gray-800 mb-4">Pay via Mobile Money</h2>
                        <form class="space-y-4" @submit.prevent="submit">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Your Phone Number</label>
                                <input v-model="form.phone" type="tel" placeholder="e.g. 0977123456"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Amount to Pay (ZMW)</label>
                                <input v-model="form.amount" type="number" step="0.01" :max="outstanding" min="1"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                <p class="mt-1 text-xs text-gray-400">You can make a partial payment.</p>
                                <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                            </div>
                            <button type="submit" :disabled="form.processing"
                                class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                                {{ form.processing ? 'Sending request…' : `Pay ${fmt(form.amount)}` }}
                            </button>
                        </form>
                        <p class="mt-3 text-xs text-center text-gray-400">
                            You will receive a mobile money approval prompt on your phone.
                        </p>
                    </div>

                    <div v-else class="px-6 py-6 text-center text-sm text-gray-500">
                        Online payment is not available for this school at this time.
                        <br />Please contact the school to arrange payment.
                    </div>

                    <!-- Payment history -->
                    <div v-if="invoice.payments?.length" class="border-t border-gray-100 px-6 py-4">
                        <p class="text-xs font-medium text-gray-500 mb-2 uppercase tracking-wide">Payment History</p>
                        <div class="space-y-2">
                            <div v-for="p in invoice.payments" :key="p.id"
                                class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">{{ fmtDate(p.payment_date) }}</span>
                                <span class="font-medium text-gray-800">{{ fmt(p.amount) }}</span>
                                <!-- Status icon -->
                                <span v-if="p.gateway_status === 'completed' || !p.gateway_status"
                                    class="flex items-center gap-1 text-green-600 text-xs font-medium">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Paid
                                </span>
                                <span v-else-if="p.gateway_status === 'pending'"
                                    class="flex items-center gap-1 text-yellow-600 text-xs font-medium">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                    </svg>
                                    Pending
                                </span>
                                <span v-else
                                    class="flex items-center gap-1 text-red-600 text-xs font-medium">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    Failed
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </template>
        </div>
    </div>
</template>
