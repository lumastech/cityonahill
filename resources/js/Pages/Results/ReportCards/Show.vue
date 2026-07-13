<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { ReportAnswerSheet } from '@/types/results'

interface Subject  { id: number; name: string; code: string }
interface Result   { id: number; subject: Subject; ca_marks: string | null; exam_marks: string | null; total_marks: string | null; grade_letter: string | null; points: number | null; teacher_comment: string | null; position_in_stream: number | null }
interface Pupil    { id: number; first_name: string; last_name: string; admission_no: string; gender?: string }
interface Term     { id: number; name: string; number: number }
interface Grade    { id: number; name: string }
interface Stream   { id: number; name: string; grade: Grade }
interface ReportCard {
    id: number
    pupil: Pupil
    term: Term
    stream: Stream
    class_teacher_comment: string | null
    headteacher_comment: string | null
    attendance_days: number | null
    attendance_present: number | null
    published: boolean
}

const props = defineProps<{
    report_card: ReportCard
    results: Result[]
    position: number | null
    attendance: { days: number | null; present: number | null }
    answer_sheets: ReportAnswerSheet[]
}>()

/** Answer sheets attached during score entry, grouped under the subject they belong to. */
const sheetsBySubject = computed(() => {
    const groups = new Map<string, ReportAnswerSheet[]>()

    for (const sheet of props.answer_sheets) {
        const key = sheet.subject ?? 'Other'
        groups.set(key, [...(groups.get(key) ?? []), sheet])
    }

    return [...groups.entries()]
})

const GRADE_COLORS: Record<string, string> = {
    A: 'bg-green-100 text-green-800',
    B: 'bg-blue-100 text-blue-800',
    C: 'bg-yellow-100 text-yellow-800',
    D: 'bg-orange-100 text-orange-800',
    E: 'bg-red-100 text-red-800',
    F: 'bg-red-200 text-red-900',
}
function gradeColor(g: string | null) { return GRADE_COLORS[g ?? ''] ?? 'bg-gray-100 text-gray-600' }

const totalMarks = computed(() =>
    props.results.reduce((s, r) => s + (r.total_marks ? Number(r.total_marks) : 0), 0),
)
const attendancePct = computed(() => {
    const { days, present } = props.attendance
    return days && present ? Math.round((present / days) * 100) : null
})

const commentForm = useForm({
    pupil_id:              props.report_card.pupil.id,
    term_id:               props.report_card.term.id,
    class_teacher_comment: props.report_card.class_teacher_comment ?? '',
    headteacher_comment:   props.report_card.headteacher_comment ?? '',
    attendance_days:       props.report_card.attendance_days ?? 0,
    attendance_present:    props.report_card.attendance_present ?? 0,
})

function saveComments() {
    commentForm.put(route('report-cards.update', props.report_card.id))
}
</script>

<template>
    <AppLayout :title="`${report_card.pupil.last_name} — Report Card`">
        <Head :title="`Report Card — ${report_card.pupil.first_name} ${report_card.pupil.last_name}`" />

        <div class="py-6 mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

            <!-- Back -->
            <Link
                :href="route('report-cards.index', { stream_id: report_card.stream?.id, term_id: report_card.term?.id })"
                class="mb-4 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Report Cards
            </Link>

            <!-- Pupil header -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">
                            {{ report_card.pupil.last_name }}, {{ report_card.pupil.first_name }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Adm: {{ report_card.pupil.admission_no }}
                            &nbsp;·&nbsp;
                            {{ report_card.stream?.grade?.name }} {{ report_card.stream?.name }}
                            &nbsp;·&nbsp;
                            {{ report_card.term?.name }}
                        </p>
                    </div>
                    <div class="flex gap-4 text-center">
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ totalMarks.toFixed(1) }}</p>
                            <p class="text-xs text-gray-500">Total Marks</p>
                        </div>
                        <div v-if="position">
                            <p class="text-2xl font-bold text-indigo-600">{{ position }}</p>
                            <p class="text-xs text-gray-500">Position</p>
                        </div>
                        <div v-if="attendancePct !== null">
                            <p class="text-2xl font-bold" :class="attendancePct >= 75 ? 'text-green-600' : 'text-red-600'">{{ attendancePct }}%</p>
                            <p class="text-xs text-gray-500">Attendance</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results table -->
            <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Subject</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">CA</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Exam</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Grade</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Pos.</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Teacher Comment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="r in results" :key="r.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium text-gray-900">{{ r.subject?.name }}</td>
                            <td class="px-4 py-2 text-center text-gray-600">{{ r.ca_marks ?? '—' }}</td>
                            <td class="px-4 py-2 text-center text-gray-600">{{ r.exam_marks ?? '—' }}</td>
                            <td class="px-4 py-2 text-center font-semibold text-gray-900">{{ r.total_marks ?? '—' }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block rounded-full px-2 py-0.5 text-xs font-bold" :class="gradeColor(r.grade_letter)">
                                    {{ r.grade_letter ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center text-gray-500">{{ r.position_in_stream ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500 italic">{{ r.teacher_comment ?? '' }}</td>
                        </tr>
                        <tr v-if="!results.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">No term results recorded yet.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="results.length" class="bg-gray-50">
                        <tr>
                            <td class="px-4 py-2 text-xs font-semibold uppercase text-gray-500">Total</td>
                            <td colspan="2"></td>
                            <td class="px-4 py-2 text-center font-bold text-gray-900">{{ totalMarks.toFixed(1) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Answer sheets attached during score entry -->
            <div v-if="answer_sheets.length" class="mb-6 break-before-page rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-1 text-sm font-semibold text-gray-900">Answer Sheets</h2>
                <p class="mb-4 text-xs text-gray-500">
                    {{ answer_sheets.length }} file(s) attached to this pupil's assessments in {{ report_card.term.name }}.
                </p>

                <div v-for="[subject, sheets] in sheetsBySubject" :key="subject" class="mb-5 last:mb-0">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ subject }}</h3>

                    <div class="grid grid-cols-2 gap-3">
                        <a
                            v-for="sheet in sheets"
                            :key="sheet.id"
                            :href="sheet.url"
                            target="_blank"
                            class="block overflow-hidden rounded-md border border-gray-200 hover:border-indigo-400"
                        >
                            <img
                                v-if="sheet.is_image"
                                :src="sheet.url"
                                :alt="sheet.name"
                                class="max-h-80 w-full bg-gray-50 object-contain"
                            />
                            <div v-else class="flex items-center gap-2 bg-gray-50 px-3 py-4 text-sm text-gray-700">
                                <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="truncate">{{ sheet.name }}</span>
                            </div>
                            <p class="truncate border-t border-gray-100 px-3 py-1.5 text-xs text-gray-500">
                                {{ sheet.assessment ?? sheet.name }}
                            </p>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Comments & attendance form -->
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-900">Comments & Attendance</h2>
                <form class="grid grid-cols-2 gap-4" @submit.prevent="saveComments">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Class Teacher Comment</label>
                        <textarea v-model="commentForm.class_teacher_comment" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Head Teacher Comment</label>
                        <textarea v-model="commentForm.headteacher_comment" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">School Days</label>
                        <input v-model.number="commentForm.attendance_days" type="number" min="0" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Days Present</label>
                        <input v-model.number="commentForm.attendance_present" type="number" min="0" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="col-span-2 flex justify-end">
                        <button
                            type="submit"
                            :disabled="commentForm.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Save Comments
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </AppLayout>
</template>
