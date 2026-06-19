<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import type { TermResult } from '@/types/results'
import type { GradeLetter } from '@/types/shared'

interface SubjectCol { id: number; name: string; code: string }
interface PupilRow {
    id: number
    first_name: string
    last_name: string
    admission_no: string
    position_in_stream: number | null
    results: Record<number, TermResult>
}

const props = defineProps<{
    pupils: PupilRow[]
    subjects: SubjectCol[]
    streamName: string
    termName: string
}>()

const GRADE_COLOR: Record<GradeLetter, string> = {
    A: 'bg-green-100 text-green-800',
    B: 'bg-blue-100 text-blue-800',
    C: 'bg-yellow-100 text-yellow-800',
    D: 'bg-orange-100 text-orange-800',
    F: 'bg-red-100 text-red-800',
}

function subjectAvg(subjectId: number): string {
    const marks = props.pupils
        .map((p) => p.results[subjectId]?.total_marks)
        .filter((m): m is number => m !== null && m !== undefined)
    if (!marks.length) return '—'
    return (marks.reduce((a, b) => a + b, 0) / marks.length).toFixed(1)
}

function exportExcel() {
    const rows = props.pupils.map((p) => ({
        Name: `${p.last_name}, ${p.first_name}`,
        'Adm No': p.admission_no,
        Position: p.position_in_stream ?? '—',
        ...Object.fromEntries(props.subjects.map((s) => [s.code, p.results[s.id]?.total_marks ?? ''])),
    }))
    const csv = [
        Object.keys(rows[0]).join(','),
        ...rows.map((r) => Object.values(r).join(',')),
    ].join('\n')
    const blob = new Blob([csv], { type: 'text/csv' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `results-${props.streamName}-${props.termName}.csv`
    a.click()
    URL.revokeObjectURL(url)
}

function printPdf() {
    window.print()
}
</script>

<template>
    <AppLayout>
        <Head :title="`Stream Results — ${streamName}`" />

        <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 print:px-0 print:py-0">
            <div class="mb-4 flex items-center justify-between print:hidden">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ streamName }} — {{ termName }}</h1>
                    <p class="text-sm text-gray-500">{{ pupils.length }} pupils · {{ subjects.length }} subjects</p>
                </div>
                <div class="flex gap-3">
                    <button
                        class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
                        @click="exportExcel"
                    >
                        Export CSV
                    </button>
                    <button
                        class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
                        @click="printPdf"
                    >
                        Print / PDF
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="sticky left-0 z-10 bg-gray-50 px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Pos</th>
                            <th class="sticky left-8 z-10 bg-gray-50 px-3 py-2 text-left text-xs font-medium uppercase text-gray-500 whitespace-nowrap">Pupil</th>
                            <th
                                v-for="s in subjects"
                                :key="s.id"
                                class="px-3 py-2 text-center text-xs font-medium uppercase text-gray-500 whitespace-nowrap"
                            >
                                {{ s.code }}
                            </th>
                            <th class="px-3 py-2 text-center text-xs font-medium uppercase text-gray-500">Avg</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="pupil in pupils" :key="pupil.id" class="hover:bg-gray-50">
                            <td class="sticky left-0 bg-white px-3 py-2 text-center text-xs font-bold text-gray-600">
                                {{ pupil.position_in_stream ?? '—' }}
                            </td>
                            <td class="sticky left-8 bg-white px-3 py-2 whitespace-nowrap font-medium text-gray-900">
                                {{ pupil.last_name }}, {{ pupil.first_name }}
                                <span class="ml-1 text-xs text-gray-400">{{ pupil.admission_no }}</span>
                            </td>
                            <td
                                v-for="s in subjects"
                                :key="s.id"
                                class="px-3 py-2 text-center"
                            >
                                <div v-if="pupil.results[s.id]" class="flex flex-col items-center gap-0.5">
                                    <span class="font-semibold text-gray-900">{{ pupil.results[s.id].total_marks ?? '—' }}</span>
                                    <span
                                        v-if="pupil.results[s.id].grade_letter"
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold"
                                        :class="GRADE_COLOR[pupil.results[s.id].grade_letter!]"
                                    >
                                        {{ pupil.results[s.id].grade_letter }}
                                    </span>
                                </div>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-3 py-2 text-center font-semibold text-gray-900">
                                {{
                                    subjects.length
                                        ? (Object.values(pupil.results)
                                            .filter((r) => r.total_marks !== null)
                                            .reduce((a, r) => a + (r.total_marks ?? 0), 0) /
                                            Math.max(1, Object.values(pupil.results).filter((r) => r.total_marks !== null).length)
                                          ).toFixed(1)
                                        : '—'
                                }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 font-medium">
                        <tr>
                            <td colspan="2" class="px-3 py-2 text-xs text-gray-500 uppercase">Average</td>
                            <td
                                v-for="s in subjects"
                                :key="s.id"
                                class="px-3 py-2 text-center text-sm text-gray-700"
                            >
                                {{ subjectAvg(s.id) }}
                            </td>
                            <td />
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
