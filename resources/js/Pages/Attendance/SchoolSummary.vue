<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { StreamDailySummary } from '@/types/attendance'

const props = defineProps<{
    summary: StreamDailySummary[]
    date: string
}>()

const selectedDate = ref(props.date)

function loadDate() {
    router.get(route('attendance.school-summary'), { date: selectedDate.value })
}

function attendancePct(session: StreamDailySummary['session']): string {
    if (!session || session.total === 0) {
        return '—'
    }
    return ((session.present / session.total) * 100).toFixed(0) + '%'
}
</script>

<template>
    <AppLayout>
        <Head title="School Attendance Summary" />

        <div class="py-6 mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-bold text-gray-900">School Attendance Summary</h1>

                <div class="flex items-center gap-3">
                    <input
                        v-model="selectedDate"
                        type="date"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    />
                    <button
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        @click="loadDate"
                    >
                        Go
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Present</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Absent</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Late</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Rate</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in summary" :key="row.stream_id">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ row.grade_name }} {{ row.stream_name }}
                            </td>
                            <template v-if="row.session">
                                <td class="px-4 py-3 text-center text-sm text-green-700 font-semibold">{{ row.session.present }}</td>
                                <td class="px-4 py-3 text-center text-sm text-red-700 font-semibold">{{ row.session.absent }}</td>
                                <td class="px-4 py-3 text-center text-sm text-yellow-700 font-semibold">{{ row.session.late }}</td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600">{{ row.session.total }}</td>
                                <td class="px-4 py-3 text-center text-sm font-semibold text-gray-900">{{ attendancePct(row.session) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="row.session.finalized ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                    >
                                        {{ row.session.finalized ? 'Finalised' : 'In Progress' }}
                                    </span>
                                </td>
                            </template>
                            <template v-else>
                                <td colspan="6" class="px-4 py-3 text-center text-sm text-gray-400 italic">
                                    No register opened
                                </td>
                            </template>
                        </tr>
                        <tr v-if="summary.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                No streams found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
