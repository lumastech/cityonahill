<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { SharedProps } from '@/types/shared'
import type { FeeInvoice, InvoiceStatus } from '@/types/finance'
import { fmtDate } from '@/utils/date'

const props = defineProps<{
    invoice: FeeInvoice
    amount_paid: number
    outstanding: number
    gateway_active: boolean
    cash_enabled: boolean
    guardian_phone: string | null
}>()

const STATUS_COLORS: Record<InvoiceStatus, string> = {
    unpaid:  'bg-red-100 text-red-800',
    partial: 'bg-yellow-100 text-yellow-800',
    paid:    'bg-green-100 text-green-800',
    waived:  'bg-gray-100 text-gray-600',
}

const isPending = computed(() => props.invoice.status !== 'paid' && props.invoice.status !== 'waived')

// ── Cash / manual payment ────────────────────────────────────────────────────
const MOBILE_METHODS = ['airtel_money', 'mtn_momo']
const cashForm = useForm({
    invoice_id:             props.invoice.id,
    amount:                 props.outstanding,
    payment_method:         'cash',
    reference:              '',
    transaction_id:         '',
    payment_date:           new Date().toISOString().slice(0, 10),
    mobile_money_provider:  '',
})
function submitCash() {
    cashForm.post(route('fee-payments.store'), {
        preserveState: true,
        preserveScroll: true,
    })
}

// ── Gateway pay slide-over ───────────────────────────────────────────────────
const showGatewayPanel = ref(false)
const gatewayForm = useForm({
    phone:  props.guardian_phone ?? '',
    amount: props.outstanding,
})
function submitGateway() {
    gatewayForm.post(route('fee-invoices.initiate-payment', props.invoice.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => { showGatewayPanel.value = false },
    })
}

// ── Send link popover ────────────────────────────────────────────────────────
const showLinkPanel = ref(false)
const linkForm = useForm({
    sent_via:  'sms',
    sent_to:   props.guardian_phone ?? '',
    expires_hours: 48,
})
function sendLink() {
    linkForm.post(route('fee-invoices.payment-link', props.invoice.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => { showLinkPanel.value = false },
    })
}

// ── Copy link ────────────────────────────────────────────────────────────────
const copied = ref(false)
const copyForm = useForm({ sent_via: 'copy', sent_to: '', expires_hours: 48 })
function copyLink() {
    copyForm.post(route('fee-invoices.payment-link', props.invoice.id), {
        onSuccess: (page) => {
            const url = (page.props as unknown as SharedProps).flash?.link_url
            if (url) {
                navigator.clipboard.writeText(url).then(() => {
                    copied.value = true
                    setTimeout(() => { copied.value = false }, 2500)
                })
            }
        },
    })
}
</script>

<template>
    <AppLayout>
    <Head title="Invoice" />
    <div class="py-6">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-start gap-4">
                <div class="flex-1">
                    <a :href="route('fee-invoices.index')" class="mb-1 block text-sm text-indigo-600 hover:underline">← Invoices</a>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        <Link v-if="invoice.pupil" :href="route('pupils.show', invoice.pupil.id)" class="hover:underline">
                            {{ invoice.pupil.first_name }} {{ invoice.pupil.last_name }}
                        </Link>
                    </h1>
                    <p class="text-sm text-gray-500">{{ invoice.pupil?.admission_no }} · {{ invoice.fee_structure?.name }} · {{ invoice.term?.name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span :class="['rounded-full px-3 py-1 text-sm font-semibold', STATUS_COLORS[invoice.status]]">
                        {{ invoice.status }}
                    </span>
                    <!-- Action buttons (pending invoices only) -->
                    <template v-if="isPending">
                        <button v-if="gateway_active"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            @click="showGatewayPanel = true; showLinkPanel = false">
                            Pay via Mobile Money
                        </button>
                        <button
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            @click="showLinkPanel = true; showGatewayPanel = false">
                            Send Link
                        </button>
                        <button :disabled="copyForm.processing"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 transition-colors"
                            :class="copied ? 'border-green-400 text-green-700 bg-green-50' : ''"
                            @click="copyLink">
                            {{ copied ? '✓ Copied!' : copyForm.processing ? 'Generating…' : 'Copy Link' }}
                        </button>
                    </template>
                </div>
            </div>

            <!-- Gateway pay slide-over -->
            <div v-if="showGatewayPanel && isPending"
                class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-indigo-900">Mobile Money Payment</h2>
                    <button class="text-indigo-400 hover:text-indigo-600" @click="showGatewayPanel = false">✕</button>
                </div>
                <form class="flex flex-wrap gap-4" @submit.prevent="submitGateway">
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input v-model="gatewayForm.phone" type="tel" placeholder="e.g. 0977123456"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                            :class="gatewayForm.errors.phone ? 'border-red-400' : ''" />
                        <p v-if="gatewayForm.errors.phone" class="mt-1 text-xs text-red-600">{{ gatewayForm.errors.phone }}</p>
                    </div>
                    <div class="w-44">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount (ZMW)</label>
                        <input v-model="gatewayForm.amount" type="number" step="0.01" :max="outstanding"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="gatewayForm.errors.amount" class="mt-1 text-xs text-red-600">{{ gatewayForm.errors.amount }}</p>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" :disabled="gatewayForm.processing"
                            class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            {{ gatewayForm.processing ? 'Processing…' : 'Charge Phone' }}
                        </button>
                        <button type="button" class="rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                            @click="showGatewayPanel = false">Cancel</button>
                    </div>
                </form>
                <p class="mt-3 text-xs text-indigo-600">The customer will receive a mobile money prompt on their phone to approve the payment.</p>
            </div>

            <!-- Send link popover -->
            <div v-if="showLinkPanel && isPending"
                class="mb-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Send Payment Link</h2>
                    <button class="text-gray-400 hover:text-gray-600" @click="showLinkPanel = false">✕</button>
                </div>
                <form class="flex flex-wrap gap-4" @submit.prevent="sendLink">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Send via</label>
                        <select v-model="linkForm.sent_via" class="rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="sms">SMS</option>
                            <option value="email">Email</option>
                        </select>
                        <p v-if="linkForm.errors.sent_via" class="mt-1 text-xs text-red-600">{{ linkForm.errors.sent_via }}</p>
                    </div>
                    <div class="flex-1 min-w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ linkForm.sent_via === 'sms' ? 'Phone Number' : 'Email Address' }}
                        </label>
                        <input v-model="linkForm.sent_to"
                            :type="linkForm.sent_via === 'sms' ? 'tel' : 'email'"
                            :placeholder="linkForm.sent_via === 'sms' ? '0977123456' : 'parent@email.com'"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                            :class="linkForm.errors.sent_to ? 'border-red-400' : ''" />
                        <p v-if="linkForm.errors.sent_to" class="mt-1 text-xs text-red-600">{{ linkForm.errors.sent_to }}</p>
                    </div>
                    <div class="w-36">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Link expires in</label>
                        <select v-model="linkForm.expires_hours" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option :value="24">24 hours</option>
                            <option :value="48">48 hours</option>
                            <option :value="72">72 hours</option>
                            <option :value="168">7 days</option>
                        </select>
                        <p v-if="linkForm.errors.expires_hours" class="mt-1 text-xs text-red-600">{{ linkForm.errors.expires_hours }}</p>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" :disabled="linkForm.processing"
                            class="rounded-md bg-gray-800 px-5 py-2 text-sm font-medium text-white hover:bg-gray-900 disabled:opacity-50">
                            {{ linkForm.processing ? 'Sending…' : 'Send Link' }}
                        </button>
                        <button type="button" class="rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                            @click="showLinkPanel = false">Cancel</button>
                    </div>
                </form>
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

            <!-- Payment history -->
            <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <h2 class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700">Payment History</h2>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-600">Date</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-600">Amount</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-600">Method</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-600">Gateway</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-600">Reference</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-600">Received By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in invoice.payments" :key="p.id">
                            <td class="px-4 py-2 text-gray-700">{{ fmtDate(p.payment_date) }}</td>
                            <td class="px-4 py-2 text-right font-medium">ZMW {{ Number(p.amount).toFixed(2) }}</td>
                            <td class="px-4 py-2 text-gray-600 capitalize">{{ p.payment_method?.replace('_', ' ') }}</td>
                            <td class="px-4 py-2">
                                <span v-if="p.gateway"
                                    :class="p.gateway_status === 'completed' ? 'bg-green-100 text-green-700'
                                          : p.gateway_status === 'pending'   ? 'bg-yellow-100 text-yellow-700'
                                          : 'bg-red-100 text-red-700'"
                                    class="rounded-full px-2 py-0.5 text-xs font-medium capitalize">
                                    {{ p.gateway }} · {{ p.gateway_status }}
                                </span>
                                <span v-else class="text-gray-400 text-xs">—</span>
                            </td>
                            <td class="px-4 py-2 text-gray-600 font-mono text-xs">{{ p.reference ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ p.received_by_user?.name ?? '—' }}</td>
                        </tr>
                        <tr v-if="!invoice.payments?.length">
                            <td colspan="6" class="px-4 py-4 text-center text-gray-400">No payments yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Cash / manual record payment -->
            <div v-if="isPending && cash_enabled" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-gray-900">Record Cash / Manual Payment</h2>
                <form class="grid grid-cols-2 gap-4" @submit.prevent="submitCash">
                    <div>
                        <label class="block text-sm text-gray-700">Amount (ZMW)</label>
                        <input v-model="cashForm.amount" type="number" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"
                            :class="cashForm.errors.amount ? 'border-red-400' : ''" />
                        <p v-if="cashForm.errors.amount" class="mt-1 text-xs text-red-600">{{ cashForm.errors.amount }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Payment Date</label>
                        <input v-model="cashForm.payment_date" type="date"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"
                            :class="cashForm.errors.payment_date ? 'border-red-400' : ''" />
                        <p v-if="cashForm.errors.payment_date" class="mt-1 text-xs text-red-600">{{ cashForm.errors.payment_date }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Method</label>
                        <select v-model="cashForm.payment_method"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                        <p v-if="cashForm.errors.payment_method" class="mt-1 text-xs text-red-600">{{ cashForm.errors.payment_method }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Reference</label>
                        <input v-model="cashForm.reference" type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"
                            :class="cashForm.errors.reference ? 'border-red-400' : ''" />
                        <p v-if="cashForm.errors.reference" class="mt-1 text-xs text-red-600">{{ cashForm.errors.reference }}</p>
                    </div>
                    <div class="col-span-2 flex justify-end">
                        <button type="submit" :disabled="cashForm.processing"
                            class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">
                            Record Payment
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
    </AppLayout>
</template>
