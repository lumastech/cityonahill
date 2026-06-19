<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { TermAttendanceSummary, AttendanceStatus } from '@/types/attendance'

interface PupilOption {
    id: number
    first_name: string
    last_name: string
    admission_no: string
}

interface TermOption {
    id: number
    name: string
    number: number
}

const props = defineProps<{
    summary: TermAttendanceSummary | null
    pupil_id: number | null
    term_id: number | null
    pupils: PupilOption[]
    terms: TermOption[]
}>()

const selectedPupilId = ref<number | null>(props.pupil_id)
const selectedTermId = ref<number | null>(props.term_id)

function loadReport() {
    if (!selectedPupilId.value || !selectedTermId.value) {
        return
    }
    router.get(route('attendance.report'), {
        pupil_id: selectedPupilId.value,
        term_id: selectedTermId.value,
    })
}

const STATUS_COLOR: Record<AttendanceStatus, string> = {
    present: '#22c55e',
    absent: '#ef4444',
    late: '#f59e0b',
    excused: '#3b82f6',
    sick: '#a855f7',
}

const STATUS_BG: Record<AttendanceStatus, string> = {
    present: 'bg-green-400',
    absent: 'bg-red-400',
    late: 'bg-yellow-400',
    excused: 'bg-blue-400',
    sick: 'bg-purple-400',
}

const donutSegments = computed(() => {
    if (!props.summary || props.summary.total_days === 0) {
        return []
    }
    const total = props.summary.total_days
    const segments = [
        { label: 'Present', value: props.summary.present, color: STATUS_COLOR.present },
        { label: 'Absent', value: props.summary.absent, color: STATUS_COLOR.absent },
        { label: 'Late', value: props.summary.late, color: STATUS_COLOR.late },
        { label: 'Excused', value: props.summary.excused, color: STATUS_COLOR.excused },
        { label: 'Sick', value: props.summary.sick, color: STATUS_COLOR.sick },
    ].filter((s) => s.value > 0)

    let cumulative = 0
    const r = 40
    const cx = 60
    const cy = 60
    const circumference = 2 * Math.PI * r

    return segments.map((seg) => {
        const pct = seg.value / total
        const dashLength = pct * circumference
        const offset = circumference - cumulative * circumference
        cumulative += pct
        return { ...seg, dashLength, offset }
    })
})

const heatmapByWeek = computed(() => {
    if (!props.summary) {
        return []
    }
    const weeks: Array<Array<{ date: string; status: AttendanceStatus | null }>> = []
    let currentWeek: Array<{ date: string; status: AttendanceStatus | null }> = []

    props.summary.daily.forEach((d) => {
        const dayOfWeek = new Date(d.date).getDay()
        if (dayOfWeek === 1 && currentWeek.length > 0) {
            weeks.push(currentWeek)
            currentWeek = []
        }
        currentWeek.push({ date: d.date, status: d.status })
    })
    if (currentWeek.length > 0) {
        weeks.push(currentWeek)
    }
    return weeks
})
</script>

<template>
    <AppLayout>
        <Head title="Attendance Report" />

        <div class="py-6 mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-bold text-gray-900">Attendance Report</h1>

            <!-- Filters -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Pupil</label>
                        <select
                            v-model="selectedPupilId"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                        >
                            <option :value="null">Select pupil…</option>
                            <option v-for="p in pupils" :key="p.id" :value="p.id">
                                {{ p.last_name }}, {{ p.first_name }} ({{ p.admission_no }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Term</label>
                        <select
                            v-model="selectedTermId"
                            class="rounded-md border-gray-300 text-sm shadow-sm"
                        >
                            <option :value="null">Select term…</option>
                            <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <button
                        :disabled="!selectedPupilId || !selectedTermId"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        @click="loadReport"
                    >
                        View Report
                    </button>
                </div>
            </div>

            <div v-if="!summary" class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center text-gray-500">
                Select a pupil and term to view the attendance report.
            </div>

            <template v-else>
                <!-- Summary stats -->
                <div class="mb-6 grid grid-cols-3 gap-4 sm:grid-cols-6">
                    <div
                        v-for="(val, key) in { present: summary.present, absent: summary.absent, late: summary.late, excused: summary.excused, sick: summary.sick }"
                        :key="key"
                        class="rounded-lg border border-gray-200 bg-white p-3 text-center shadow-sm"
                    >
                        <div class="text-2xl font-bold text-gray-900">{{ val }}</div>
                        <div class="text-xs capitalize text-gray-500">{{ key }}</div>
                    </div>
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-3 text-center shadow-sm">
                        <div class="text-2xl font-bold text-indigo-700">{{ summary.percentage.toFixed(1) }}%</div>
                        <div class="text-xs text-indigo-500">Attendance</div>
                    </div>
                </div>

                <!-- Donut chart -->
                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Breakdown</h2>
                    <div class="flex items-center gap-8">
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="40" fill="none" stroke="#e5e7eb" stroke-width="20" />
                            <circle
                                v-for="(seg, i) in donutSegments"
                                :key="i"
                                cx="60"
                                cy="60"
                                r="40"
                                fill="none"
                                :stroke="seg.color"
                                stroke-width="20"
                                :stroke-dasharray="`${seg.dashLength} ${2 * Math.PI * 40 - seg.dashLength}`"
                                :stroke-dashoffset="seg.offset"
                                transform="rotate(-90 60 60)"
                            />
                            <text x="60" y="65" text-anchor="middle" class="text-xs font-bold fill-gray-700" font-size="12">
                                {{ summary.total_days }}d
                            </text>
                        </svg>
                        <div class="space-y-1">
                            <div v-for="seg in donutSegments" :key="seg.label" class="flex items-center gap-2 text-sm">
                                <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: seg.color }" />
                                <span class="text-gray-700">{{ seg.label }}: {{ seg.value }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar heatmap -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Daily Attendance</h2>
                    <div class="flex gap-1 flex-wrap">
                        <div v-for="(week, wi) in heatmapByWeek" :key="wi" class="flex flex-col gap-1">
                            <div v-for="day in week" :key="day.date" class="group relative">
                                <div
                                    class="h-5 w-5 rounded-sm"
                                    :class="day.status ? STATUS_BG[day.status] : 'bg-gray-100'"
                                    :title="day.date + ': ' + (day.status ?? 'no data')"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-3 flex-wrap">
                        <div v-for="(color, status) in STATUS_BG" :key="status" class="flex items-center gap-1 text-xs text-gray-600">
                            <span class="h-3 w-3 rounded-sm" :class="color" />
                            <span class="capitalize">{{ status }}</span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
