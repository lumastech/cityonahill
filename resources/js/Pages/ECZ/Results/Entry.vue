<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { EczCandidate } from '@/types/ecz'

const props = defineProps<{
    candidates: EczCandidate[]
    grade_level: number
    exam_year: number
}>()

interface ResultRow {
    candidate_id: number
    subject_id: number
    actual_grade: string
    actual_points: number
}

const gradePoints: Record<string, number> = { A: 1, B: 2, C: 3, D: 4, E: 5, F: 9, U: 9, X: 9 }
const gradeOptions = ['A', 'B', 'C', 'D', 'E', 'F', 'U', 'X']

const rows = ref<ResultRow[]>(
    props.candidates.flatMap((c) =>
        (c.subject_entries ?? []).map((e) => ({
            candidate_id: c.id,
            subject_id: e.subject_id,
            actual_grade: e.actual_grade ?? '',
            actual_points: e.actual_points ?? 9,
        })),
    ),
)

function onGradeChange(row: ResultRow) {
    row.actual_points = gradePoints[row.actual_grade] ?? 9
}

const form = useForm({ results: rows.value })

function submit() {
    form.results = rows.value
    form.post(route('ecz.results.enter'))
}

const candidateMap = computed(() =>
    Object.fromEntries(props.candidates.map((c) => [c.id, `${c.pupil?.first_name} ${c.pupil?.last_name}`])),
)

const subjectMap = computed(() => {
    const map: Record<number, string> = {}
    for (const c of props.candidates) {
        for (const e of c.subject_entries ?? []) {
            if (e.subject) map[e.subject_id] = e.subject.name
        }
    }
    return map
})
</script>

<template>
    <AppLayout>
    <Head title="ECZ Results Entry" />
    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">Enter ECZ Results</h1>
                <p class="mt-1 text-sm text-gray-500">Grade {{ grade_level }} · {{ exam_year }}</p>
            </div>

            <form @submit.prevent="submit">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Pupil</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Subject</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Grade</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(row, i) in rows" :key="i">
                                <td class="px-4 py-2 text-gray-900">{{ candidateMap[row.candidate_id] }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ subjectMap[row.subject_id] }}</td>
                                <td class="px-4 py-2">
                                    <select
                                        v-model="row.actual_grade"
                                        class="rounded border-gray-300 text-sm shadow-sm"
                                        @change="onGradeChange(row)"
                                    >
                                        <option value="">—</option>
                                        <option v-for="g in gradeOptions" :key="g" :value="g">{{ g }}</option>
                                    </select>
                                </td>
                                <td class="px-4 py-2 text-gray-600">{{ row.actual_points }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Save Results
                    </button>
                </div>
            </form>
        </div>
    </div>
    </AppLayout>
</template>
