<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { Expense, ExpenseCategory } from '@/types/finance'
import { fmtDate } from '@/utils/date'

defineProps<{
    expenses: { data: Expense[]; links: unknown[] }
    filters: { category?: string | null }
}>()

const CATEGORIES: ExpenseCategory[] = ['salaries', 'utilities', 'maintenance', 'supplies', 'transport', 'feeding', 'library', 'other']

const CATEGORY_COLORS: Record<string, string> = {
    salaries: 'bg-purple-100 text-purple-800',
    utilities: 'bg-blue-100 text-blue-800',
    maintenance: 'bg-yellow-100 text-yellow-800',
    supplies: 'bg-green-100 text-green-800',
    transport: 'bg-orange-100 text-orange-800',
    feeding: 'bg-pink-100 text-pink-800',
    library: 'bg-teal-100 text-teal-800',
    other: 'bg-gray-100 text-gray-700',
}

const category = ref('')

function applyFilter() {
    router.get(route('expenses.index'), { category: category.value }, { preserveState: true })
}

const form = useForm({ category: 'other', description: '', amount: '', expense_date: '', receipt_no: '' })
function submit() {
    form.post(route('expenses.store'), { onSuccess: () => form.reset() })
}

function remove(id: number) {
    if (confirm('Delete this expense?')) useForm({}).delete(route('expenses.destroy', id))
}
</script>

<template>
    <AppLayout>
    <Head title="Expenses" />
    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Expense Log</h1>
                <select v-model="category" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter">
                    <option value="">All Categories</option>
                    <option v-for="c in CATEGORIES" :key="c" :value="c" class="capitalize">{{ c }}</option>
                </select>
            </div>

            <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Date</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Category</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Description</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Amount (ZMW)</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Receipt</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="e in expenses.data" :key="e.id">
                            <td class="px-4 py-3 text-gray-700">{{ fmtDate(e.expense_date) }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium capitalize', CATEGORY_COLORS[e.category]]">{{ e.category }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 max-w-xs truncate">{{ e.description }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ Number(e.amount).toFixed(2) }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                <a v-if="e.media?.[0]" :href="e.media[0].original_url" target="_blank" class="text-indigo-600 hover:underline">View</a>
                                <span v-else>{{ e.receipt_no ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button class="text-xs text-red-600 hover:underline" @click="remove(e.id)">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!expenses.data.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">No expenses recorded.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add expense form -->
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-gray-900">Record Expense</h2>
                <form class="grid grid-cols-2 gap-4 sm:grid-cols-3" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm text-gray-700">Category</label>
                        <select v-model="form.category" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option v-for="c in CATEGORIES" :key="c" :value="c" class="capitalize">{{ c }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Amount (ZMW)</label>
                        <input v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Date</label>
                        <input v-model="form.expense_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.expense_date" class="mt-1 text-xs text-red-600">{{ form.errors.expense_date }}</p>
                    </div>
                    <div class="col-span-2 sm:col-span-3">
                        <label class="block text-sm text-gray-700">Description</label>
                        <input v-model="form.description" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Receipt No</label>
                        <input v-model="form.receipt_no" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="flex items-end justify-end col-span-2 sm:col-span-3">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save Expense
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
