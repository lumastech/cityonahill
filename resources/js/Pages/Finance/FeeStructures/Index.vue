<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import type { FeeStructure } from '@/types/finance'

defineProps<{ fee_structures: FeeStructure[] }>()

const APPLIES_TO_LABELS: Record<string, string> = {
    all: 'All Pupils',
    day_scholars: 'Day Scholars',
    boarders: 'Boarders',
    new_pupils: 'New Pupils',
}

const form = useForm({
    grade_id: '',
    term_id: '',
    academic_year_id: '',
    name: '',
    description: '',
    amount: '',
    applies_to: 'all',
    is_mandatory: true,
})

function submit() {
    form.post(route('fee-structures.store'), { onSuccess: () => form.reset() })
}

function remove(id: number) {
    if (confirm('Delete this fee structure?')) {
        useForm({}).delete(route('fee-structures.destroy', id))
    }
}
</script>

<template>
    <Head title="Fee Structures" />
    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-semibold text-gray-900">Fee Schedule</h1>

            <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Grade</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Term</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Amount (ZMW)</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Applies To</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Mandatory</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="fs in fee_structures" :key="fs.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ fs.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ fs.grade ? `Grade ${fs.grade.grade_number}` : 'All Grades' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ fs.term?.name }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ Number(fs.amount).toFixed(2) }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-800">
                                    {{ APPLIES_TO_LABELS[fs.applies_to] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="fs.is_mandatory ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'" class="rounded-full px-2 py-0.5 text-xs">
                                    {{ fs.is_mandatory ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button class="text-xs text-red-600 hover:underline" @click="remove(fs.id)">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!fee_structures.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">No fee structures defined yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add form -->
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-gray-900">Add Fee Structure</h2>
                <form class="grid grid-cols-2 gap-4 sm:grid-cols-3" @submit.prevent="submit">
                    <div class="col-span-2 sm:col-span-3">
                        <label class="block text-sm text-gray-700">Name</label>
                        <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Amount (ZMW)</label>
                        <input v-model="form.amount" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Applies To</label>
                        <select v-model="form.applies_to" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option v-for="(label, val) in APPLIES_TO_LABELS" :key="val" :value="val">{{ label }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pt-5">
                        <input v-model="form.is_mandatory" type="checkbox" class="rounded border-gray-300" />
                        <label class="text-sm text-gray-700">Mandatory</label>
                    </div>
                    <div class="col-span-2 sm:col-span-3 flex justify-end">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save Fee Structure
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
