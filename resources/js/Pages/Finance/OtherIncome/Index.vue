<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { OtherIncome, IncomeSource } from '@/types/finance'
import { fmtDate } from '@/utils/date'

defineProps<{
    incomes: { data: OtherIncome[]; links: unknown[] }
    filters: { source?: string | null }
}>()

const SOURCES: IncomeSource[] = ['donation', 'grant', 'uniform_sales', 'book_sales', 'feeding', 'rental', 'fundraising', 'other']

const SOURCE_COLORS: Record<string, string> = {
    donation: 'bg-emerald-100 text-emerald-800',
    grant: 'bg-blue-100 text-blue-800',
    uniform_sales: 'bg-indigo-100 text-indigo-800',
    book_sales: 'bg-violet-100 text-violet-800',
    feeding: 'bg-pink-100 text-pink-800',
    rental: 'bg-amber-100 text-amber-800',
    fundraising: 'bg-teal-100 text-teal-800',
    other: 'bg-gray-100 text-gray-700',
}

function label(s: string) {
    return s.replace(/_/g, ' ')
}

const source = ref('')

function applyFilter() {
    router.get(route('other-income.index'), { source: source.value }, { preserveState: true })
}

const form = useForm({ source: 'donation', description: '', amount: '', received_date: '', reference: '' })
function submit() {
    form.post(route('other-income.store'), { onSuccess: () => form.reset() })
}

function remove(id: number) {
    if (confirm('Delete this income record?')) useForm({}).delete(route('other-income.destroy', id))
}
</script>

<template>
    <AppLayout>
    <Head title="Other Income" />
    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Other Income</h1>
                <select v-model="source" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter">
                    <option value="">All Sources</option>
                    <option v-for="s in SOURCES" :key="s" :value="s" class="capitalize">{{ label(s) }}</option>
                </select>
            </div>

            <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Date</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Source</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Description</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Amount (ZMW)</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Receipt</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="i in incomes.data" :key="i.id">
                            <td class="px-4 py-3 text-gray-700">{{ fmtDate(i.received_date) }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium capitalize', SOURCE_COLORS[i.source]]">{{ label(i.source) }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 max-w-xs truncate">{{ i.description }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ Number(i.amount).toFixed(2) }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                <a v-if="i.media?.[0]" :href="i.media[0].original_url" target="_blank" class="text-indigo-600 hover:underline">View</a>
                                <span v-else>{{ i.reference ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button class="text-xs text-red-600 hover:underline" @click="remove(i.id)">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!incomes.data.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">No income recorded.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add income form -->
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-gray-900">Record Income</h2>
                <form class="grid grid-cols-2 gap-4 sm:grid-cols-3" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm text-gray-700">Source</label>
                        <select v-model="form.source" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option v-for="s in SOURCES" :key="s" :value="s" class="capitalize">{{ label(s) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Amount (ZMW)</label>
                        <input v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Date</label>
                        <input v-model="form.received_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.received_date" class="mt-1 text-xs text-red-600">{{ form.errors.received_date }}</p>
                    </div>
                    <div class="col-span-2 sm:col-span-3">
                        <label class="block text-sm text-gray-700">Description</label>
                        <input v-model="form.description" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Reference</label>
                        <input v-model="form.reference" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="flex items-end justify-end col-span-2 sm:col-span-3">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save Income
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
