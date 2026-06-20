<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import type { FeeStructure } from '@/types/finance'

interface Term         { id: number; name: string; number: number }
interface AcademicYear { id: number; name: string }
interface Grade        { id: number; name: string; grade_number: number }

defineProps<{
    fee_structures: FeeStructure[]
    terms: Term[]
    academic_years: AcademicYear[]
    grades: Grade[]
}>()

const APPLIES_TO_LABELS: Record<string, string> = {
    all:          'All Pupils',
    day_scholars: 'Day Scholars',
    boarders:     'Boarders',
    new_pupils:   'New Pupils',
}

const form = useForm({
    academic_year_id: null as number | null,
    term_id:          null as number | null,
    grade_id:         null as number | null,
    name:             '',
    description:      '',
    amount:           '',
    applies_to:       'all',
    is_mandatory:     true,
})

function submit() {
    form.transform(data => ({
        ...data,
        academic_year_id: data.academic_year_id ? Number(data.academic_year_id) : null,
        term_id:          data.term_id          ? Number(data.term_id)          : null,
        grade_id:         data.grade_id         ? Number(data.grade_id)         : null,
        amount:           Number(data.amount),
    })).post(route('fee-structures.store'), { onSuccess: () => form.reset() })
}

function remove(id: number) {
    if (confirm('Delete this fee structure?')) {
        useForm({}).delete(route('fee-structures.destroy', id))
    }
}
</script>

<template>
    <AppLayout title="Fee Structures">
        <Head title="Fee Structures" />
        <div class="py-6">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <h1 class="mb-6 text-2xl font-semibold text-gray-900">Fee Schedule</h1>

                <!-- Existing fee structures -->
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

                        <!-- Name -->
                        <div class="col-span-2 sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" placeholder="e.g. Tuition Fee Term 1" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <!-- Academic Year -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Academic Year <span class="text-red-500">*</span></label>
                            <select v-model="form.academic_year_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option :value="null">Select year…</option>
                                <option v-for="y in academic_years" :key="y.id" :value="y.id">{{ y.name }}</option>
                            </select>
                            <p v-if="form.errors.academic_year_id" class="mt-1 text-xs text-red-600">{{ form.errors.academic_year_id }}</p>
                        </div>

                        <!-- Term -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Term <span class="text-red-500">*</span></label>
                            <select v-model="form.term_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option :value="null">Select term…</option>
                                <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                            <p v-if="form.errors.term_id" class="mt-1 text-xs text-red-600">{{ form.errors.term_id }}</p>
                        </div>

                        <!-- Grade (optional) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Grade <span class="text-gray-400 text-xs">(optional — leave blank for all)</span></label>
                            <select v-model="form.grade_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option :value="null">All Grades</option>
                                <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                            <p v-if="form.errors.grade_id" class="mt-1 text-xs text-red-600">{{ form.errors.grade_id }}</p>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount (ZMW) <span class="text-red-500">*</span></label>
                            <input v-model="form.amount" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                        </div>

                        <!-- Applies To -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Applies To</label>
                            <select v-model="form.applies_to" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option v-for="(label, val) in APPLIES_TO_LABELS" :key="val" :value="val">{{ label }}</option>
                            </select>
                        </div>

                        <!-- Mandatory -->
                        <div class="flex items-center gap-2 pt-6">
                            <input v-model="form.is_mandatory" type="checkbox" class="rounded border-gray-300" />
                            <label class="text-sm text-gray-700">Mandatory fee</label>
                        </div>

                        <!-- Submit -->
                        <div class="col-span-2 sm:col-span-3 flex justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Save Fee Structure
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
