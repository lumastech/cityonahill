<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import type { TermResult } from '@/types/results'
import { useResults } from '@/composables/useResults'
import { useGrading } from '@/composables/useGrading'
import { usePermissions } from '@/composables/usePermissions'

interface Stream { id: number; name: string; grade?: { id: number; name: string } }
interface Subject { id: number; name: string; code: string }
interface Term { id: number; name: string; number: number; is_current: boolean }
interface PupilRow { id: number; first_name: string; last_name: string; admission_no: string }

const props = defineProps<{
    streams: Stream[]
    subjects: Subject[]
    terms: Term[]
    results: TermResult[] | null
    pupils: PupilRow[]
    filters: { stream_id?: string; term_id?: string; subject_id?: string }
}>()

const { computeTotal } = useResults()
const { getGradeLetter, getGradeLabel, getEczPoints } = useGrading()
const { can } = usePermissions()

const selectedStream = ref(props.filters.stream_id ? Number(props.filters.stream_id) : null)
const selectedSubject = ref(props.filters.subject_id ? Number(props.filters.subject_id) : null)
const selectedTerm = ref(props.filters.term_id ? Number(props.filters.term_id) : null)

function load() {
    if (!selectedStream.value || !selectedTerm.value) return
    router.get(
        route('term-results.index'),
        { stream_id: selectedStream.value, term_id: selectedTerm.value, subject_id: selectedSubject.value ?? undefined },
        { preserveState: true, replace: true },
    )
}

type RowDraft = {
    pupil_id: number
    ca_marks: number | null
    exam_marks: number | null
    teacher_comment: string
}

const drafts = ref<RowDraft[]>([])

watch(
    [() => props.pupils, () => props.results],
    () => {
        drafts.value = props.pupils.map((p) => {
            const existing = props.results?.find((r) => r.pupil_id === p.id)
            return {
                pupil_id: p.id,
                ca_marks: existing?.ca_marks !== undefined ? Number(existing.ca_marks) : null,
                exam_marks: existing?.exam_marks !== undefined ? Number(existing.exam_marks) : null,
                teacher_comment: existing?.teacher_comment ?? '',
            }
        })
    },
    { immediate: true },
)

const liveRows = computed(() =>
    drafts.value.map((d) => {
        const total = computeTotal(d.ca_marks, d.exam_marks)
        const grade = total !== null ? getGradeLetter(total) : null
        const points = grade ? getEczPoints(grade) : null
        return { ...d, total, grade, points }
    }),
)

const GRADE_COLOR: Record<string, string> = {
    A: 'bg-green-100 text-green-800',
    B: 'bg-blue-100 text-blue-800',
    C: 'bg-yellow-100 text-yellow-800',
    D: 'bg-orange-100 text-orange-800',
    F: 'bg-red-100 text-red-800',
}

const saveForm = useForm<{ stream_id: number | null; subject_id: number | null; term_id: number | null; results: RowDraft[] }>({
    stream_id: selectedStream.value,
    subject_id: selectedSubject.value,
    term_id: selectedTerm.value,
    results: [],
})

function saveDraft() {
    saveForm.stream_id = selectedStream.value
    saveForm.subject_id = selectedSubject.value
    saveForm.term_id = selectedTerm.value
    saveForm.results = drafts.value
    saveForm.post(route('term-results.store'))
}

function autoComputeCA(allSubjects = false) {
    if (!selectedStream.value || !selectedTerm.value) return
    router.post(route('term-results.compute-ca'), {
        stream_id:  selectedStream.value,
        term_id:    selectedTerm.value,
        subject_id: allSubjects ? null : (selectedSubject.value ?? null),
    })
}

function publish() {
    if (!selectedStream.value || !selectedTerm.value) return
    router.post(route('term-results.publish'), {
        stream_id: selectedStream.value,
        term_id: selectedTerm.value,
    })
}
</script>

<template>
    <AppLayout>
        <Head title="Term Results Entry" />

        <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-bold text-gray-900">Term Results Entry</h1>

            <!-- Selectors -->
            <div class="mb-4 flex flex-wrap gap-3">
                <select v-model="selectedStream" class="rounded-md border-gray-300 text-sm shadow-sm" @change="load">
                    <option :value="null">Select Class…</option>
                    <option v-for="s in streams" :key="s.id" :value="s.id">{{ s.grade?.name }} {{ s.name }}</option>
                </select>
                <select v-model="selectedSubject" class="rounded-md border-gray-300 text-sm shadow-sm" @change="load">
                    <option :value="null">Select Subject…</option>
                    <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <select v-model="selectedTerm" class="rounded-md border-gray-300 text-sm shadow-sm" @change="load">
                    <option :value="null">Select Term…</option>
                    <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>

                <!-- Compute all subjects when only stream+term are picked -->
                <button
                    v-if="selectedStream && selectedTerm && !selectedSubject"
                    class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 border border-indigo-300 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
                    title="Pull CA marks from all assessment scores for every subject in this class"
                    @click="autoComputeCA(true)"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Compute CA from Assessments (All Subjects)
                </button>

                <!-- Compute single subject when subject is also selected -->
                <button
                    v-if="selectedStream && selectedSubject && selectedTerm"
                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
                    title="Pull CA marks from assessment scores for this subject only"
                    @click="autoComputeCA(false)"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Compute CA (This Subject)
                </button>
            </div>

            <div v-if="!pupils.length" class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center text-gray-500">
                Select a class, subject and term to enter results.
            </div>

            <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Pupil</th>
                            <th class="px-3 py-3 text-center text-xs font-medium uppercase text-gray-500">CA Marks</th>
                            <th class="px-3 py-3 text-center text-xs font-medium uppercase text-gray-500">Exam Marks</th>
                            <th class="px-3 py-3 text-center text-xs font-medium uppercase text-gray-500">Total</th>
                            <th class="px-3 py-3 text-center text-xs font-medium uppercase text-gray-500">Grade</th>
                            <th class="px-3 py-3 text-center text-xs font-medium uppercase text-gray-500">Pts</th>
                            <th class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Comment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(pupil, idx) in pupils" :key="pupil.id">
                            <td class="px-3 py-2 text-sm font-medium text-gray-900">
                                {{ pupil.last_name }}, {{ pupil.first_name }}
                                <span class="ml-1 text-xs text-gray-400">{{ pupil.admission_no }}</span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <input
                                    v-model.number="drafts[idx].ca_marks"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.5"
                                    class="w-20 rounded border-gray-200 text-center text-sm focus:ring-1 focus:ring-indigo-300"
                                />
                            </td>
                            <td class="px-3 py-2 text-center">
                                <input
                                    v-model.number="drafts[idx].exam_marks"
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.5"
                                    class="w-20 rounded border-gray-200 text-center text-sm focus:ring-1 focus:ring-indigo-300"
                                />
                            </td>
                            <td class="px-3 py-2 text-center text-sm font-semibold text-gray-900">
                                {{ liveRows[idx].total !== null ? liveRows[idx].total : '—' }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span
                                    v-if="liveRows[idx].grade"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                                    :class="GRADE_COLOR[liveRows[idx].grade!]"
                                    :title="liveRows[idx].grade ? getGradeLabel(liveRows[idx].total!) : ''"
                                >
                                    {{ liveRows[idx].grade }}
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-3 py-2 text-center text-sm text-gray-600">
                                {{ liveRows[idx].points ?? '—' }}
                            </td>
                            <td class="px-3 py-2">
                                <input
                                    v-model="drafts[idx].teacher_comment"
                                    type="text"
                                    placeholder="Comment…"
                                    class="w-full rounded border-gray-200 text-xs focus:border-indigo-300 focus:ring-1 focus:ring-indigo-300"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-3">
                    <span class="text-xs text-gray-500">{{ pupils.length }} pupils</span>
                    <div class="flex gap-3">
                        <button
                            :disabled="saveForm.processing"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                            @click="saveDraft"
                        >
                            Save Draft
                        </button>
                        <button
                            v-if="can('results.publish')"
                            class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                            @click="publish"
                        >
                            Publish
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
