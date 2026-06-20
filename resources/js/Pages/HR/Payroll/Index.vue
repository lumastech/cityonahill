<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useHR, MONTH_NAMES } from '@/composables/useHR'
import type { Payroll, PayrollSummary } from '@/types/hr'

const props = defineProps<{
    payrolls: Payroll[]
    filters: { month: number; year: number }
}>()

const { formatZmw, monthName } = useHR()
const month = ref(props.filters.month)
const year = ref(props.filters.year)

const generateForm = useForm({ month: month.value, year: year.value, include_all_staff: true })

function applyFilter() {
    router.get(route('payroll.index'), { month: month.value, year: year.value }, { preserveState: true })
}

function generate() {
    generateForm.month = month.value
    generateForm.year = year.value
    generateForm.post(route('payroll.generate'))
}

function approvePayroll(id: number) {
    router.post(route('payroll.approve', id))
}

const summary = computed<PayrollSummary>(() => ({
    count: props.payrolls.length,
    total_gross: props.payrolls.reduce((s, p) => s + Number(p.basic_salary), 0),
    total_napsa_employee: props.payrolls.reduce((s, p) => s + Number(p.napsa_employee), 0),
    total_napsa_employer: props.payrolls.reduce((s, p) => s + Number(p.napsa_employer), 0),
    total_paye: props.payrolls.reduce((s, p) => s + Number(p.paye), 0),
    total_net: props.payrolls.reduce((s, p) => s + Number(p.net_pay), 0),
}))
</script>

<template>
    <AppLayout>
    <Head title="Payroll" />
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-gray-900 mr-4">Payroll</h1>
                <select v-model="month" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter">
                    <option v-for="(name, i) in MONTH_NAMES.slice(1)" :key="i + 1" :value="i + 1">{{ name }}</option>
                </select>
                <input v-model="year" type="number" class="w-24 rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter" />
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700" @click="generate">
                    Generate Payroll
                </button>
            </div>

            <!-- Summary cards -->
            <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm text-center">
                    <p class="text-xl font-bold text-gray-900">{{ summary.count }}</p>
                    <p class="text-sm text-gray-500">Staff</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm text-center">
                    <p class="text-xl font-bold text-gray-900">{{ formatZmw(summary.total_gross) }}</p>
                    <p class="text-sm text-gray-500">Total Gross</p>
                </div>
                <div class="rounded-lg border border-red-100 bg-red-50 p-4 shadow-sm text-center">
                    <p class="text-xl font-bold text-red-700">{{ formatZmw(summary.total_paye) }}</p>
                    <p class="text-sm text-red-500">Total PAYE</p>
                </div>
                <div class="rounded-lg border border-green-100 bg-green-50 p-4 shadow-sm text-center">
                    <p class="text-xl font-bold text-green-700">{{ formatZmw(summary.total_net) }}</p>
                    <p class="text-sm text-green-500">Total Net Pay</p>
                </div>
            </div>

            <!-- Payroll table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Staff</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Gross</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">NAPSA (Emp)</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">PAYE</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Net Pay</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in payrolls" :key="p.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ p.staff?.user?.name }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ formatZmw(p.basic_salary) }}</td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ formatZmw(p.napsa_employee) }}</td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ formatZmw(p.paye) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ formatZmw(p.net_pay) }}</td>
                            <td class="px-4 py-3">
                                <span v-if="p.paid_at" class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Paid</span>
                                <span v-else class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">Pending</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="!p.paid_at" class="text-xs text-indigo-600 hover:underline" @click="approvePayroll(p.id)">Approve</button>
                            </td>
                        </tr>
                        <tr v-if="!payrolls.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                No payroll for {{ monthName(month) }} {{ year }}. Click "Generate Payroll" to create.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
