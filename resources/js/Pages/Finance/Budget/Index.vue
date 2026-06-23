<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { Budget, BudgetVsActualItem, ExpenseCategory } from '@/types/finance'

const CATEGORIES: ExpenseCategory[] = ['salaries', 'utilities', 'maintenance', 'supplies', 'transport', 'feeding', 'library', 'other']

const props = defineProps<{
    budgets: Budget[]
    academic_years: Array<{ id: number; name: string }>
    budget_vs_actual: { by_category: BudgetVsActualItem[] } | null
    filters: { academicYearId?: number | null }
}>()

const academicYearId = ref(props.filters.academicYearId ?? '')

function applyFilter() {
    router.get(route('budgets.index'), { academic_year_id: academicYearId.value }, { preserveState: true })
}

const form = useForm({ academic_year_id: '', term_id: '', category: '', amount: '' })
function submit() {
    form.post(route('budgets.store'), { onSuccess: () => form.reset() })
}

function formatZmw(n: number) { return `ZMW ${Number(n).toFixed(2)}` }

const maxBudget = computed(() => Math.max(...(props.budget_vs_actual?.by_category ?? []).map(i => Math.max(i.budget, i.actual)), 1))
</script>

<template>
    <AppLayout>
    <Head title="Budget vs Actual" />
    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center gap-4">
                <h1 class="text-2xl font-semibold text-gray-900">Budget vs Actual</h1>
                <select v-model="academicYearId" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter">
                    <option value="">Select Academic Year</option>
                    <option v-for="ay in academic_years" :key="ay.id" :value="ay.id">{{ ay.name }}</option>
                </select>
            </div>

            <!-- Horizontal bar chart -->
            <div v-if="budget_vs_actual" class="mb-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="space-y-4">
                    <div v-for="item in budget_vs_actual.by_category" :key="item.category">
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="font-medium capitalize text-gray-800">{{ item.category }}</span>
                            <span :class="item.variance >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs">
                                {{ item.variance >= 0 ? 'Under' : 'Over' }} by {{ formatZmw(Math.abs(item.variance)) }}
                            </span>
                        </div>
                        <div class="relative h-5 rounded-full bg-gray-100">
                            <div class="absolute left-0 top-0 h-5 rounded-full bg-indigo-200 opacity-80 transition-all"
                                :style="{ width: `${(item.budget / maxBudget) * 100}%` }" />
                            <div class="absolute left-0 top-0 h-5 rounded-full bg-indigo-600 opacity-90 transition-all"
                                :style="{ width: `${(item.actual / maxBudget) * 100}%` }" />
                        </div>
                        <div class="mt-0.5 flex gap-4 text-xs text-gray-500">
                            <span>Budget: {{ formatZmw(item.budget) }}</span>
                            <span>Actual: {{ formatZmw(item.actual) }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex gap-4 text-xs">
                    <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-indigo-200"></span> Budget</span>
                    <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-indigo-600"></span> Actual</span>
                </div>
            </div>

            <!-- Add budget form -->
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-gray-900">Set Budget</h2>
                <form class="grid grid-cols-2 gap-4 sm:grid-cols-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm text-gray-700">Academic Year</label>
                        <select v-model="form.academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option v-for="ay in academic_years" :key="ay.id" :value="ay.id">{{ ay.name }}</option>
                        </select>
                        <p v-if="form.errors.academic_year_id" class="mt-1 text-xs text-red-600">{{ form.errors.academic_year_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Category</label>
                        <select v-model="form.category" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">Select category…</option>
                            <option v-for="c in CATEGORIES" :key="c" :value="c" class="capitalize">{{ c }}</option>
                        </select>
                        <p v-if="form.errors.category" class="mt-1 text-xs text-red-600">{{ form.errors.category }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Amount (ZMW)</label>
                        <input v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" :disabled="form.processing" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
