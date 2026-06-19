<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { Assessment, AssessmentScore } from '@/types/results'
import { useAssessments } from '@/composables/useAssessments'

interface Pupil { id: number; first_name: string; last_name: string; admission_no: string }

const props = defineProps<{
    assessment: Assessment & { scores: AssessmentScore[] }
    pupils: Pupil[]
}>()

const { typeLabel, typeColor, gradeColor, computeGradeLetter } = useAssessments()

type ScoreRow = { pupil_id: number; marks: number | null; remarks: string }

const rows = ref<ScoreRow[]>(
    props.pupils.map((p) => {
        const existing = props.assessment.scores.find((s) => s.pupil_id === p.id)
        return {
            pupil_id: p.id,
            marks: existing ? Number(existing.marks_obtained) : null,
            remarks: existing?.remarks ?? '',
        }
    }),
)

const isDirty = ref(false)
function markDirty() { isDirty.value = true }

const form = useForm<{ assessment_id: number; scores: ScoreRow[] }>({
    assessment_id: props.assessment.id,
    scores: [],
})

function save() {
    form.scores = rows.value.filter((r) => r.marks !== null) as ScoreRow[]
    form.post(route('assessments.scores.enter', props.assessment.id), {
        onSuccess: () => { isDirty.value = false },
    })
}

const maxMarks = props.assessment.max_marks

function isOutOfRange(marks: number | null): boolean {
    if (marks === null) return false
    return marks < 0 || marks > maxMarks
}

const gradeForRow = computed(() =>
    rows.value.map((r) =>
        r.marks !== null && !isOutOfRange(r.marks)
            ? computeGradeLetter(r.marks, maxMarks)
            : null,
    ),
)

window.onbeforeunload = () => (isDirty.value ? 'You have unsaved changes.' : null)
</script>

<template>
    <AppLayout>
        <Head :title="`Score Entry — ${assessment.name}`" />

        <div class="py-6 mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ assessment.name }}</h1>
                    <div class="mt-1 flex items-center gap-3 text-sm text-gray-600">
                        <span>{{ assessment.subject?.name }}</span>
                        <span>·</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="typeColor(assessment.type)">
                            {{ typeLabel(assessment.type) }}
                        </span>
                        <span>Max: {{ assessment.max_marks }}</span>
                    </div>
                </div>
                <div v-if="isDirty" class="rounded-md bg-yellow-50 px-3 py-2 text-sm text-yellow-800">
                    Unsaved changes
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Pupil</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Adm. No.</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Marks (/ {{ maxMarks }})</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Grade</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(pupil, idx) in pupils" :key="pupil.id">
                            <td class="px-4 py-2 text-sm text-gray-400">{{ idx + 1 }}</td>
                            <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                {{ pupil.last_name }}, {{ pupil.first_name }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ pupil.admission_no }}</td>
                            <td class="px-4 py-2 text-center">
                                <input
                                    v-model.number="rows[idx].marks"
                                    type="number"
                                    :min="0"
                                    :max="maxMarks"
                                    step="0.5"
                                    class="w-24 rounded border text-center text-sm focus:ring-1 focus:ring-indigo-300"
                                    :class="isOutOfRange(rows[idx].marks) ? 'border-red-400 bg-red-50 text-red-700' : 'border-gray-200'"
                                    @input="markDirty"
                                />
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span
                                    v-if="gradeForRow[idx]"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                                    :class="gradeColor(gradeForRow[idx])"
                                >
                                    {{ gradeForRow[idx] }}
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-2">
                                <input
                                    v-model="rows[idx].remarks"
                                    type="text"
                                    placeholder="Optional"
                                    class="w-full rounded border-gray-200 text-xs focus:border-indigo-300 focus:ring-1 focus:ring-indigo-300"
                                    @input="markDirty"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <button
                        :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        @click="save"
                    >
                        Save All
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
