<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import type { AttendanceSummary, ChildSummaryPupil } from '@/types/portal'

defineProps<{
    pupil: ChildSummaryPupil
    summary: AttendanceSummary | null
    monthly: { month: string; present: number; absent: number; total: number }[]
}>()
</script>

<template>
    <Head :title="`${pupil.first_name} — Attendance`" />
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center gap-3">
                <a :href="route('portal.dashboard')" class="text-sm text-indigo-600 hover:underline">← Back</a>
                <h1 class="text-xl font-semibold text-gray-900">
                    {{ pupil.first_name }} — Attendance
                </h1>
            </div>

            <div v-if="summary" class="mb-6 grid grid-cols-3 gap-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 text-center shadow-sm">
                    <p class="text-2xl font-bold text-gray-900">{{ summary.total }}</p>
                    <p class="text-sm text-gray-500">Days Recorded</p>
                </div>
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-center shadow-sm">
                    <p class="text-2xl font-bold text-green-700">{{ summary.present }}</p>
                    <p class="text-sm text-green-600">Days Present</p>
                </div>
                <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-center shadow-sm">
                    <p class="text-2xl font-bold text-indigo-700">{{ summary.percentage }}%</p>
                    <p class="text-sm text-indigo-600">Attendance Rate</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Month</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Present</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Absent</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in monthly" :key="row.month">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ row.month }}</td>
                            <td class="px-4 py-3 text-green-700">{{ row.present }}</td>
                            <td class="px-4 py-3 text-red-600">{{ row.absent }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ row.total }}</td>
                        </tr>
                        <tr v-if="!monthly.length">
                            <td colspan="4" class="px-4 py-6 text-center text-gray-400">No attendance data yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
