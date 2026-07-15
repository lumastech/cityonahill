<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { ProfitLoss } from '@/types/finance'
import { fmtDate } from '@/utils/date'

const props = defineProps<{
    statement: ProfitLoss
    terms: Array<{ id: number; name: string; start_date: string | null; end_date: string | null }>
    filters: { from: string; to: string }
}>()

const from = ref(props.filters.from)
const to = ref(props.filters.to)

function apply() {
    router.get(route('finance.profit-loss'), { from: from.value, to: to.value }, { preserveState: true })
}

function applyTerm(termId: string) {
    const term = props.terms.find((t) => String(t.id) === termId)
    if (term?.start_date && term?.end_date) {
        from.value = term.start_date
        to.value = term.end_date
        apply()
    }
}

function formatZmw(n: number) { return `ZMW ${Number(n).toFixed(2)}` }
function label(s: string) { return s.replace(/_/g, ' ') }
</script>

<template>
    <AppLayout>
    <Head title="Profit & Loss" />
    <div class="py-6">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-1 text-2xl font-semibold text-gray-900">Profit &amp; Loss</h1>
            <p class="mb-6 text-sm text-gray-500">Cash basis · {{ fmtDate(statement.from) }} — {{ fmtDate(statement.to) }}</p>

            <!-- Period controls -->
            <div class="mb-6 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 text-sm shadow-sm">
                <div>
                    <label class="block text-gray-600">From</label>
                    <input v-model="from" type="date" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm" />
                </div>
                <div>
                    <label class="block text-gray-600">To</label>
                    <input v-model="to" type="date" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm" />
                </div>
                <button class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700" @click="apply">Apply</button>
                <div class="ml-auto">
                    <label class="block text-gray-600">Quick term</label>
                    <select class="mt-1 rounded-md border-gray-300 text-sm shadow-sm" @change="applyTerm(($event.target as HTMLSelectElement).value)">
                        <option value="">Select term…</option>
                        <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Statement -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <!-- Income -->
                <div class="border-b border-gray-100 px-5 py-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Income</h2>
                </div>
                <dl class="divide-y divide-gray-50 px-5 text-sm">
                    <div class="flex justify-between py-2.5">
                        <dt class="text-gray-700">Fees collected</dt>
                        <dd class="font-medium text-gray-900">{{ formatZmw(statement.fees_collected) }}</dd>
                    </div>
                    <div v-for="row in statement.other_income_by_source" :key="row.source" class="flex justify-between py-2.5">
                        <dt class="capitalize text-gray-700">{{ label(row.source) }}</dt>
                        <dd class="font-medium text-gray-900">{{ formatZmw(row.amount) }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5 font-semibold">
                        <dt class="text-gray-900">Total income</dt>
                        <dd class="text-green-700">{{ formatZmw(statement.total_income) }}</dd>
                    </div>
                </dl>

                <!-- Expenses -->
                <div class="border-y border-gray-100 bg-gray-50 px-5 py-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Expenses</h2>
                </div>
                <dl class="divide-y divide-gray-50 px-5 text-sm">
                    <div v-for="row in statement.expenses_by_category" :key="row.category" class="flex justify-between py-2.5">
                        <dt class="capitalize text-gray-700">{{ label(row.category) }}</dt>
                        <dd class="font-medium text-gray-900">{{ formatZmw(row.amount) }}</dd>
                    </div>
                    <div v-if="!statement.expenses_by_category.length" class="py-2.5 text-gray-400">No expenses in this period.</div>
                    <div class="flex justify-between py-2.5 font-semibold">
                        <dt class="text-gray-900">Total expenses</dt>
                        <dd class="text-red-700">{{ formatZmw(statement.total_expenses) }}</dd>
                    </div>
                </dl>

                <!-- Net -->
                <div
                    class="flex items-center justify-between px-5 py-4"
                    :class="statement.net >= 0 ? 'bg-green-50' : 'bg-red-50'"
                >
                    <span class="text-base font-semibold" :class="statement.net >= 0 ? 'text-green-800' : 'text-red-800'">
                        Net {{ statement.net >= 0 ? 'Surplus' : 'Deficit' }}
                    </span>
                    <span class="text-xl font-bold" :class="statement.net >= 0 ? 'text-green-700' : 'text-red-700'">
                        {{ formatZmw(Math.abs(statement.net)) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
