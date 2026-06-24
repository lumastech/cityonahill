<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useHR, MONTH_NAMES } from '@/composables/useHR'
import type { Payroll } from '@/types/hr'

interface Adjustment {
    id: number
    type: 'bonus' | 'deduction'
    description: string
    amount: number
}

interface PayrollWithAdjustments extends Payroll {
    adjustments: Adjustment[]
    staff: { id: number; employee_no: string; position: string; department: string | null; user: { id: number; name: string; email: string } }
    approved_by_user?: { id: number; name: string } | null
}

const props = defineProps<{
    payroll: PayrollWithAdjustments
    can_edit: boolean
}>()

const { formatZmw, monthName } = useHR()

const addForm = useForm({ type: 'bonus' as 'bonus' | 'deduction', description: '', amount: '' })
const showAddForm = ref(false)

function print() { window.print() }

function submitAdjustment() {
    addForm.post(route('payroll.adjustments.store', props.payroll.id), {
        onSuccess: () => { addForm.reset(); showAddForm.value = false },
    })
}

function removeAdjustment(id: number) {
    router.delete(route('payroll.adjustments.destroy', id), { preserveScroll: true })
}

function approve() {
    router.post(route('payroll.approve', props.payroll.id))
}

const bonuses    = computed(() => props.payroll.adjustments.filter(a => a.type === 'bonus'))
const deductions = computed(() => props.payroll.adjustments.filter(a => a.type === 'deduction'))
const gross      = computed(() => Number(props.payroll.basic_salary) + Number(props.payroll.allowances))
const totalDeductions = computed(() =>
    Number(props.payroll.napsa_employee) + Number(props.payroll.paye) + Number(props.payroll.deductions)
)
</script>

<template>
    <AppLayout>
    <Head :title="`Payslip — ${payroll.staff?.user?.name}`" />
    <div class="py-6">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

            <!-- Toolbar (hidden when printing) -->
            <div class="mb-5 flex flex-wrap items-center gap-3 print:hidden">
                <a :href="route('payroll.index')" class="text-sm text-indigo-600 hover:underline">← Payroll</a>
                <span class="flex-1" />
                <button v-if="can_edit && payroll.paid_at === null"
                    class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                    @click="approve">
                    Mark as Paid
                </button>
                <button
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    @click="print()">
                    Print / Save PDF
                </button>
            </div>

            <!-- Payslip card -->
            <div id="payslip" class="rounded-lg border border-gray-200 bg-white shadow-sm print:shadow-none print:border-none">

                <!-- Header -->
                <div class="border-b border-gray-100 bg-gray-50 px-8 py-6 print:bg-white">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">PAYSLIP</h1>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ monthName(payroll.month) }} {{ payroll.year }}
                            </p>
                        </div>
                        <div class="text-right text-sm text-gray-600">
                            <p class="font-semibold text-gray-900">{{ payroll.staff?.user?.name }}</p>
                            <p>{{ payroll.staff?.employee_no }}</p>
                            <p class="capitalize">{{ payroll.staff?.position?.replace('_', ' ') }}</p>
                            <p v-if="payroll.staff?.department">{{ payroll.staff.department }}</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span v-if="payroll.paid_at"
                            class="inline-flex items-center rounded-full bg-green-100 px-3 py-0.5 text-xs font-medium text-green-800">
                            Paid {{ new Date(payroll.paid_at).toLocaleDateString() }}
                            <span v-if="payroll.approved_by_user"> · Approved by {{ payroll.approved_by_user.name }}</span>
                        </span>
                        <span v-else class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-0.5 text-xs font-medium text-yellow-800">
                            Pending
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 divide-y divide-gray-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0 px-0">

                    <!-- Earnings -->
                    <div class="px-8 py-6">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Earnings</h2>
                        <table class="w-full text-sm">
                            <tbody>
                                <tr>
                                    <td class="py-1.5 text-gray-700">Basic Salary</td>
                                    <td class="py-1.5 text-right font-medium text-gray-900">{{ formatZmw(payroll.basic_salary) }}</td>
                                </tr>
                                <tr v-for="b in bonuses" :key="b.id">
                                    <td class="py-1.5 text-gray-700">{{ b.description }}</td>
                                    <td class="py-1.5 text-right font-medium text-green-700">+ {{ formatZmw(b.amount) }}</td>
                                </tr>
                                <tr class="border-t border-gray-200 font-semibold">
                                    <td class="pt-2 text-gray-900">Gross Pay</td>
                                    <td class="pt-2 text-right text-gray-900">{{ formatZmw(gross) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Deductions -->
                    <div class="px-8 py-6">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">Deductions</h2>
                        <table class="w-full text-sm">
                            <tbody>
                                <tr>
                                    <td class="py-1.5 text-gray-700">NAPSA (Employee 5%)</td>
                                    <td class="py-1.5 text-right font-medium text-red-700">{{ formatZmw(payroll.napsa_employee) }}</td>
                                </tr>
                                <tr>
                                    <td class="py-1.5 text-gray-700">PAYE</td>
                                    <td class="py-1.5 text-right font-medium text-red-700">{{ formatZmw(payroll.paye) }}</td>
                                </tr>
                                <tr v-for="d in deductions" :key="d.id">
                                    <td class="py-1.5 text-gray-700">{{ d.description }}</td>
                                    <td class="py-1.5 text-right font-medium text-red-700">{{ formatZmw(d.amount) }}</td>
                                </tr>
                                <tr class="border-t border-gray-200 font-semibold">
                                    <td class="pt-2 text-gray-900">Total Deductions</td>
                                    <td class="pt-2 text-right text-gray-900">{{ formatZmw(totalDeductions) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Net pay footer -->
                <div class="border-t border-gray-200 bg-indigo-50 px-8 py-5 print:bg-white">
                    <div class="flex items-center justify-between">
                        <span class="text-base font-semibold text-gray-700">NET PAY</span>
                        <span class="text-2xl font-bold text-indigo-700">{{ formatZmw(payroll.net_pay) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        Employer NAPSA contribution: {{ formatZmw(payroll.napsa_employer) }}
                    </p>
                </div>

                <!-- Adjustments panel (pending only, hidden on print) -->
                <div v-if="can_edit && payroll.paid_at === null" class="border-t border-gray-200 px-8 py-6 print:hidden">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700">Adjustments</h2>
                        <button class="rounded-md border border-indigo-300 px-3 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-50"
                            @click="showAddForm = !showAddForm">
                            + Add
                        </button>
                    </div>

                    <!-- Add form -->
                    <form v-if="showAddForm" class="mb-4 flex flex-wrap gap-3 rounded-lg border border-dashed border-gray-300 p-4"
                        @submit.prevent="submitAdjustment">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Type</label>
                            <select v-model="addForm.type" class="rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="bonus">Bonus</option>
                                <option value="deduction">Deduction</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-40">
                            <label class="block text-xs text-gray-600 mb-1">Description</label>
                            <input v-model="addForm.description" type="text" placeholder="e.g. Transport allowance"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="addForm.errors.description" class="mt-1 text-xs text-red-600">{{ addForm.errors.description }}</p>
                        </div>
                        <div class="w-36">
                            <label class="block text-xs text-gray-600 mb-1">Amount (ZMW)</label>
                            <input v-model="addForm.amount" type="number" step="0.01" min="0.01"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="addForm.errors.amount" class="mt-1 text-xs text-red-600">{{ addForm.errors.amount }}</p>
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" :disabled="addForm.processing"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                Save
                            </button>
                            <button type="button" class="rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                                @click="showAddForm = false">
                                Cancel
                            </button>
                        </div>
                    </form>

                    <!-- Existing adjustments list -->
                    <div v-if="payroll.adjustments.length" class="divide-y divide-gray-100 rounded-lg border border-gray-100">
                        <div v-for="adj in payroll.adjustments" :key="adj.id"
                            class="flex items-center justify-between px-4 py-2.5 text-sm">
                            <div class="flex items-center gap-3">
                                <span :class="adj.type === 'bonus'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'"
                                    class="rounded-full px-2 py-0.5 text-xs font-medium capitalize">
                                    {{ adj.type }}
                                </span>
                                <span class="text-gray-700">{{ adj.description }}</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span :class="adj.type === 'bonus' ? 'text-green-700' : 'text-red-700'" class="font-medium">
                                    {{ adj.type === 'bonus' ? '+' : '-' }}{{ formatZmw(adj.amount) }}
                                </span>
                                <button class="text-xs text-red-500 hover:text-red-700"
                                    @click="removeAdjustment(adj.id)">Remove</button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No adjustments yet.</p>
                </div>

            </div>
        </div>
    </div>
    </AppLayout>
</template>

<style>
@media print {
    body * { visibility: hidden; }
    #payslip, #payslip * { visibility: visible; }
    #payslip { position: absolute; inset: 0; }
}
</style>
