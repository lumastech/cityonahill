<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import type { ReportCard, TermResult } from '@/types/results'
import type { GradeLetter } from '@/types/shared'
import { useGrading } from '@/composables/useGrading'
import { useReportCards } from '@/composables/useReportCards'

const props = defineProps<{
    report_card: ReportCard
    results: TermResult[]
    position: number | null
    attendance: { days: number | null; present: number | null }
    next_term_start?: string | null
}>()

const { getDivision, getEczPoints } = useGrading()
const { printCard, attendancePct } = useReportCards()

const GRADE_COLOR: Record<GradeLetter, string> = {
    A: 'text-green-700',
    B: 'text-blue-700',
    C: 'text-yellow-700',
    D: 'text-orange-700',
    F: 'text-red-700',
}

const totalMarks = props.results.reduce((s, r) => s + (r.total_marks ?? 0), 0)
const subjectCount = props.results.filter((r) => r.total_marks !== null).length
const average = subjectCount > 0 ? (totalMarks / subjectCount).toFixed(1) : '—'
const totalPoints = props.results.reduce((s, r) => s + (r.points ?? 9), 0)
const division = subjectCount >= 6 ? getDivision(totalPoints, subjectCount) : '—'

const pupil = props.report_card.pupil
const school = props.report_card.stream?.grade
</script>

<template>
    <AppLayout>
        <Head :title="`Report Card — ${pupil?.full_name}`" />

        <!-- Print button (hidden on print) -->
        <div class="mb-4 flex justify-end px-4 print:hidden">
            <button
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                @click="printCard"
            >
                Print Report Card
            </button>
        </div>

        <!-- Report card body -->
        <div class="mx-auto max-w-3xl bg-white px-8 py-8 shadow print:shadow-none print:max-w-none" id="report-card">

            <!-- School header -->
            <div class="mb-6 border-b-2 border-gray-800 pb-4 text-center">
                <h1 class="text-xl font-extrabold uppercase tracking-wide text-gray-900">
                    Republic of Zambia — Ministry of Education
                </h1>
                <h2 class="mt-1 text-lg font-bold text-gray-800">
                    {{ report_card.stream?.grade?.name ?? 'School' }} — End of Term Report Card
                </h2>
                <p class="mt-0.5 text-sm text-gray-600">{{ report_card.term?.name }} · {{ report_card.stream?.grade?.name }} {{ report_card.stream?.name }}</p>
            </div>

            <!-- Pupil details -->
            <div class="mb-6 grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                <div class="flex gap-2">
                    <span class="font-medium text-gray-700 w-32">Full Name:</span>
                    <span class="font-bold text-gray-900">{{ pupil?.full_name }}</span>
                </div>
                <div class="flex gap-2">
                    <span class="font-medium text-gray-700 w-32">Adm. No.:</span>
                    <span class="text-gray-800">{{ pupil?.admission_no }}</span>
                </div>
                <div class="flex gap-2">
                    <span class="font-medium text-gray-700 w-32">Sex:</span>
                    <span class="capitalize text-gray-800">{{ pupil?.sex }}</span>
                </div>
                <div class="flex gap-2">
                    <span class="font-medium text-gray-700 w-32">D.O.B.:</span>
                    <span class="text-gray-800">{{ pupil?.dob }}</span>
                </div>
                <div class="flex gap-2">
                    <span class="font-medium text-gray-700 w-32">Class:</span>
                    <span class="text-gray-800">{{ report_card.stream?.grade?.name }} {{ report_card.stream?.name }}</span>
                </div>
                <div class="flex gap-2">
                    <span class="font-medium text-gray-700 w-32">Position:</span>
                    <span class="font-bold text-gray-900">{{ position ?? '—' }}</span>
                </div>
            </div>

            <!-- Results table -->
            <table class="mb-6 w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Subject</th>
                        <th class="border border-gray-300 px-3 py-2 text-center font-semibold text-gray-700">CA</th>
                        <th class="border border-gray-300 px-3 py-2 text-center font-semibold text-gray-700">Exam</th>
                        <th class="border border-gray-300 px-3 py-2 text-center font-semibold text-gray-700">Total</th>
                        <th class="border border-gray-300 px-3 py-2 text-center font-semibold text-gray-700">Grade</th>
                        <th class="border border-gray-300 px-3 py-2 text-center font-semibold text-gray-700">Points</th>
                        <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">Teacher Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in results" :key="r.id" class="even:bg-gray-50">
                        <td class="border border-gray-300 px-3 py-1.5 font-medium text-gray-900">{{ r.subject?.name }}</td>
                        <td class="border border-gray-300 px-3 py-1.5 text-center text-gray-700">{{ r.ca_marks ?? '—' }}</td>
                        <td class="border border-gray-300 px-3 py-1.5 text-center text-gray-700">{{ r.exam_marks ?? '—' }}</td>
                        <td class="border border-gray-300 px-3 py-1.5 text-center font-semibold text-gray-900">{{ r.total_marks ?? '—' }}</td>
                        <td class="border border-gray-300 px-3 py-1.5 text-center font-bold" :class="r.grade_letter ? GRADE_COLOR[r.grade_letter] : 'text-gray-400'">
                            {{ r.grade_letter ?? '—' }}
                        </td>
                        <td class="border border-gray-300 px-3 py-1.5 text-center text-gray-700">{{ r.points ?? '—' }}</td>
                        <td class="border border-gray-300 px-3 py-1.5 text-xs text-gray-600">{{ r.teacher_comment ?? '' }}</td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-100 font-semibold">
                    <tr>
                        <td class="border border-gray-300 px-3 py-2">Totals / Average</td>
                        <td colspan="2" />
                        <td class="border border-gray-300 px-3 py-2 text-center">{{ totalMarks.toFixed(1) }}</td>
                        <td colspan="2" class="border border-gray-300 px-3 py-2 text-center">{{ average }}</td>
                        <td />
                    </tr>
                </tfoot>
            </table>

            <!-- Summary row -->
            <div class="mb-6 grid grid-cols-4 gap-4 rounded-lg border border-gray-300 bg-gray-50 p-4 text-sm">
                <div class="text-center">
                    <p class="text-xs font-medium uppercase text-gray-500">Total Marks</p>
                    <p class="text-lg font-bold text-gray-900">{{ totalMarks.toFixed(1) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs font-medium uppercase text-gray-500">Average</p>
                    <p class="text-lg font-bold text-gray-900">{{ average }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs font-medium uppercase text-gray-500">Position</p>
                    <p class="text-lg font-bold text-gray-900">{{ position ?? '—' }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs font-medium uppercase text-gray-500">Division</p>
                    <p class="text-lg font-bold text-gray-900">{{ division }}</p>
                </div>
            </div>

            <!-- Attendance -->
            <div class="mb-6 rounded-lg border border-gray-300 p-4 text-sm">
                <p class="mb-2 font-semibold text-gray-700">Attendance</p>
                <div class="flex gap-8">
                    <span>Days Present: <strong>{{ attendance.present ?? '—' }}</strong></span>
                    <span>Total School Days: <strong>{{ attendance.days ?? '—' }}</strong></span>
                    <span>Attendance Rate: <strong>{{ attendancePct(attendance.present, attendance.days) }}</strong></span>
                </div>
            </div>

            <!-- Comments -->
            <div class="mb-6 grid grid-cols-2 gap-6 text-sm">
                <div>
                    <p class="mb-1 font-semibold text-gray-700">Class Teacher's Comment</p>
                    <div class="min-h-16 rounded border border-gray-200 bg-gray-50 p-3 text-gray-800">
                        {{ report_card.class_teacher_comment || '—' }}
                    </div>
                </div>
                <div>
                    <p class="mb-1 font-semibold text-gray-700">Head Teacher's Comment</p>
                    <div class="min-h-16 rounded border border-gray-200 bg-gray-50 p-3 text-gray-800">
                        {{ report_card.headteacher_comment || '—' }}
                    </div>
                </div>
            </div>

            <!-- Signature lines -->
            <div class="grid grid-cols-2 gap-8 text-sm">
                <div>
                    <div class="mt-8 border-t border-gray-400 pt-1 text-xs text-gray-500">Class Teacher Signature & Date</div>
                </div>
                <div>
                    <div class="mt-8 border-t border-gray-400 pt-1 text-xs text-gray-500">Head Teacher Signature & Date</div>
                </div>
            </div>

            <p v-if="next_term_start" class="mt-6 text-center text-xs text-gray-500">
                Next Term Begins: <strong>{{ next_term_start }}</strong>
            </p>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    header, nav, .print\:hidden { display: none !important; }
    body { background: white; }
    #report-card { box-shadow: none; margin: 0; padding: 1.5cm; }
}
</style>
