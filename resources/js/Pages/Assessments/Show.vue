<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { AnswerSheet, Assessment, AssessmentScore } from '@/types/results'
import { useAssessments } from '@/composables/useAssessments'

interface Pupil { id: number; first_name: string; last_name: string; admission_no: string }

const props = defineProps<{
    assessment: Assessment & { scores: AssessmentScore[] }
    pupils: Pupil[]
    attachments: Record<number, AnswerSheet[]>
}>()

const { typeLabel, typeColor, gradeColor, computeGradeLetter } = useAssessments()

type ScoreRow = { pupil_id: number; marks: number | null; remarks: string; files: File[] }

const MAX_FILES = 5
const MAX_FILE_BYTES = 10 * 1024 * 1024
const ACCEPTED = '.jpg,.jpeg,.png,.webp,.pdf,.doc,.docx'

const rows = ref<ScoreRow[]>(
    props.pupils.map((p) => {
        const existing = props.assessment.scores.find((s) => s.pupil_id === p.id)
        return {
            pupil_id: p.id,
            marks:    existing ? Number(existing.marks_obtained) : null,
            remarks:  existing?.remarks ?? '',
            files:    [],
        }
    }),
)

const isDirty = ref(false)
function markDirty() { isDirty.value = true }

/** Answer sheets already saved for a pupil, if any. */
function saved(pupilId: number): AnswerSheet[] {
    return props.attachments[pupilId] ?? []
}

const fileError = ref<string | null>(null)

function stageFiles(idx: number, event: Event) {
    const input = event.target as HTMLInputElement
    const picked = Array.from(input.files ?? [])
    input.value = '' // let the same file be re-picked after removal
    if (!picked.length) return

    const row = rows.value[idx]
    const tooBig = picked.find((f) => f.size > MAX_FILE_BYTES)
    if (tooBig) {
        fileError.value = `"${tooBig.name}" is larger than 10MB.`
        return
    }
    if (saved(row.pupil_id).length + row.files.length + picked.length > MAX_FILES) {
        fileError.value = `At most ${MAX_FILES} answer sheet files per pupil.`
        return
    }

    fileError.value = null
    row.files.push(...picked)
    markDirty()
}

function unstageFile(idx: number, fileIdx: number) {
    rows.value[idx].files.splice(fileIdx, 1)
    markDirty()
}

function deleteAttachment(sheet: AnswerSheet) {
    if (!confirm(`Remove "${sheet.name}"? This cannot be undone.`)) return

    router.delete(route('assessment-scores.attachments.destroy', [sheet.score_id, sheet.id]), {
        preserveScroll: true,
    })
}

/** Marks are required, so a staged file on a blank row would be dropped on save. */
const rowsMissingMarks = computed(() =>
    rows.value.filter((r) => r.files.length > 0 && r.marks === null),
)

const form = useForm<{ assessment_id: number; scores: ScoreRow[] }>({
    assessment_id: props.assessment.id,
    scores: [],
})

function save() {
    if (rowsMissingMarks.value.length) return

    form.scores = rows.value.filter((r) => r.marks !== null)
    form.post(route('assessments.scores.enter', props.assessment.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDirty.value = false
            fileError.value = null
            rows.value.forEach((r) => { r.files = [] })
        },
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
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Answer Sheet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(pupil, idx) in pupils" :key="pupil.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-400">{{ idx + 1 }}</td>
                            <td class="px-4 py-2 font-medium text-gray-900">
                                <Link :href="route('pupils.show', pupil.id)" class="hover:underline text-indigo-700">{{ pupil.last_name }}, {{ pupil.first_name }}</Link>
                            </td>
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

                            <!-- Answer sheet: saved files, plus any staged for the next save -->
                            <td class="px-4 py-2">
                                <div class="flex flex-col gap-1">
                                    <a
                                        v-for="sheet in saved(pupil.id)"
                                        :key="sheet.id"
                                        :href="sheet.url"
                                        target="_blank"
                                        class="group inline-flex max-w-[15rem] items-center gap-1 text-xs text-indigo-700 hover:underline"
                                    >
                                        <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                        <span class="truncate">{{ sheet.name }}</span>
                                        <button
                                            type="button"
                                            class="ml-auto shrink-0 text-gray-300 opacity-0 transition group-hover:opacity-100 hover:text-red-600"
                                            title="Remove answer sheet"
                                            @click.prevent="deleteAttachment(sheet)"
                                        >
                                            &times;
                                        </button>
                                    </a>

                                    <span
                                        v-for="(file, fileIdx) in rows[idx].files"
                                        :key="`${file.name}-${fileIdx}`"
                                        class="inline-flex max-w-[15rem] items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 text-xs text-amber-800"
                                    >
                                        <span class="truncate">{{ file.name }}</span>
                                        <button
                                            type="button"
                                            class="ml-auto shrink-0 text-amber-500 hover:text-red-600"
                                            title="Remove"
                                            @click="unstageFile(idx, fileIdx)"
                                        >
                                            &times;
                                        </button>
                                    </span>

                                    <label class="inline-flex w-fit cursor-pointer items-center gap-1 text-xs text-gray-500 hover:text-indigo-700">
                                        <input type="file" :accept="ACCEPTED" multiple class="hidden" @change="stageFiles(idx, $event)" />
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Attach
                                    </label>

                                    <p v-if="rows[idx].files.length && rows[idx].marks === null" class="text-xs text-red-600">
                                        Enter marks to save this attachment.
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!pupils.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">No active pupils enrolled in this stream.</td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex items-center justify-between gap-4 border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <div class="text-xs">
                        <p v-if="fileError" class="text-red-600">{{ fileError }}</p>
                        <p v-else-if="rowsMissingMarks.length" class="text-red-600">
                            {{ rowsMissingMarks.length }} attached answer sheet(s) need marks before they can be saved.
                        </p>
                        <p v-else-if="form.progress" class="text-gray-500">
                            Uploading… {{ form.progress.percentage }}%
                        </p>
                        <p v-else class="text-gray-400">
                            Optionally attach a scan or photo of each pupil's answer sheet (max 5 files, 10MB each).
                        </p>
                    </div>
                    <button
                        :disabled="form.processing || !isDirty || rowsMissingMarks.length > 0"
                        class="shrink-0 rounded-md bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        @click="save"
                    >
                        Save Scores
                    </button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
