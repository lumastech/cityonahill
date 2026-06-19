<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import type { ChildSummary } from '@/types/portal'

const props = defineProps<{
    summary: ChildSummary
}>()

const gradeColor: Record<string, string> = {
    A: 'bg-green-100 text-green-800',
    B: 'bg-lime-100 text-lime-800',
    C: 'bg-yellow-100 text-yellow-800',
    D: 'bg-orange-100 text-orange-800',
    F: 'bg-red-100 text-red-800',
}

function average(): string {
    const results = props.summary.latest_results
    if (!results.length) return '—'
    const avg = results.reduce((s, r) => s + (r.total_marks ?? 0), 0) / results.length
    return avg.toFixed(1)
}
</script>

<template>
    <Head :title="`${summary.pupil.first_name} — Results`" />
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center gap-3">
                <a :href="route('portal.dashboard')" class="text-sm text-indigo-600 hover:underline">← Back</a>
                <h1 class="text-xl font-semibold text-gray-900">
                    {{ summary.pupil.first_name }} {{ summary.pupil.last_name }} — Results
                </h1>
            </div>

            <!-- Attendance summary -->
            <div v-if="summary.attendance_summary" class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                <p class="text-sm text-blue-800">
                    Attendance: <strong>{{ summary.attendance_summary.percentage }}%</strong>
                    ({{ summary.attendance_summary.present }}/{{ summary.attendance_summary.total }} days)
                </p>
            </div>

            <!-- Results table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <p class="text-sm font-medium text-gray-700">
                        {{ summary.current_term?.name ?? 'Current Term' }} Results
                        <span class="ml-2 text-gray-400">Average: {{ average() }}</span>
                    </p>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Subject</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Total</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Grade</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="result in summary.latest_results" :key="result.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ result.subject?.name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ result.total_marks ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold', gradeColor[result.grade_letter] ?? 'bg-gray-100 text-gray-700']">
                                    {{ result.grade_letter }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!summary.latest_results.length">
                            <td colspan="3" class="px-4 py-8 text-center text-gray-400">No published results yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
