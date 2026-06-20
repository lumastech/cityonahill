<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { EczCandidate } from '@/types/ecz'

const props = defineProps<{
    candidates: EczCandidate[]
    filters: { grade_level: number; exam_year: number }
}>()

const gradeLevel = ref(props.filters.grade_level)
const examYear = ref(props.filters.exam_year)

const statusColor: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    submitted: 'bg-blue-100 text-blue-800',
    confirmed: 'bg-green-100 text-green green-800',
    withdrawn: 'bg-red-100 text-red-800',
}

function applyFilter() {
    router.get(
        route('ecz-candidates.index'),
        { grade_level: gradeLevel.value, exam_year: examYear.value },
        { preserveScroll: true, preserveState: true },
    )
}

const registerForm = useForm({ pupil_id: '', grade_level: gradeLevel.value, exam_year: examYear.value })

function viewCandidate(id: number) {
    router.visit(route('ecz-candidates.show', id))
}
</script>

<template>
    <AppLayout>
    <Head title="ECZ Candidates" />
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">ECZ Candidates</h1>
            </div>

            <!-- Filters -->
            <div class="mb-4 flex flex-wrap gap-3">
                <select v-model="gradeLevel" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter">
                    <option :value="7">Grade 7</option>
                    <option :value="9">Grade 9</option>
                    <option :value="12">Grade 12</option>
                </select>
                <input
                    v-model="examYear"
                    type="number"
                    class="w-28 rounded-md border-gray-300 text-sm shadow-sm"
                    placeholder="Year"
                    @change="applyFilter"
                />
                <a :href="route('ecz.analytics', { grade_level: gradeLevel, exam_year: examYear })" class="ml-auto rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Analytics
                </a>
            </div>

            <!-- Candidates table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Pupil</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Adm No</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Index No</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Subjects</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Division</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in candidates" :key="c.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ c.pupil?.first_name }} {{ c.pupil?.last_name }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ c.pupil?.admission_no }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ c.index_number ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ c.subject_entries?.length ?? 0 }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', statusColor[c.registration_status] ?? 'bg-gray-100 text-gray-700']">
                                    {{ c.registration_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ c.division ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button class="text-indigo-600 hover:text-indigo-900" @click="viewCandidate(c.id)">View</button>
                            </td>
                        </tr>
                        <tr v-if="candidates.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">No candidates registered for this exam year.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
