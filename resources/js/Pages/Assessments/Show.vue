<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
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
            marks:    existing ? Number(existing.marks_obtained) : null,
            remarks:  existing?.remarks ?? '',
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
    form.scores = rows.value.filter((r) => r.marks !== null)
    form.post(route('assessments.scores.enter', props.assessment.id), {
        onSuccess: () => { isDirty.value = false },
    })
}

const maxMarks = props.assessment.max_marks

function isOutOfRange(marks: number | null) {
    return marks !== null && (marks < 0 || marks > maxMarks)
}

const gradeForRow = computed(() =>
    rows.value.map((r) =>
        r.marks !== null && !isOutOfRange(r.marks)
            ? computeGradeLetter(r.marks, maxMarks)
            : null,
    ),
)

function fmtDate(d: string) {
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

window.onbeforeunload = () => (isDirty.value ? 'You have unsaved changes.' : null)
</script>

<template>
    <AppLayout :title="assessment.name">
        <Head :title="`${assessment.name} — Score Entry`" />

        <div class="py-6 mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            <!-- Back + header -->
            <div class="mb-6">
                <Link :href="route('assessments.index')" class="mb-3 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Assessments
                </Link>

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ assessment.name }}</h1>
                        <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-gray-600">
                            <span>{{ assessment.subject?.name }}</span>
                            <span>·</span>
                            <span>{{ assessment.stream?.name }}</span>
                            <span>·</span>
                            <span>{{ assessment.term?.name }}</span>
                            <span>·</span>
                            <span>{{ fmtDate(assessment.date) }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="typeColor(assessment.type)">
                                {{ typeLabel(assessment.type) }}
                            </span>
                        </div>
                    </div>
                    <div v-if="isDirty" class="shrink-0 rounded-md bg-yellow-50 px-3 py-2 text-sm text-yellow-800">
                        Unsaved changes
                    </div>
                </div>
            </div>

            <!-- Score table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Pupil</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Adm. No.</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Marks / {{ maxMarks }}</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Grade</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(pupil, idx) in pupils" :key="pupil.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-400">{{ idx + 1 }}</td>
                            <td class="px-4 py-2 font-medium text-gray-900">{{ pupil.last_name }}, {{ pupil.first_name }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ pupil.admission_no }}</td>
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
                        <tr v-if="!pupils.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">No active pupils enrolled in this stream.</td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <button
                        :disabled="form.processing || !isDirty"
                        class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        @click="save"
                    >
                        Save Scores
                    </button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
