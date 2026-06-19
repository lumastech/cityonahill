<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { EczPassRateAnalytics } from '@/types/ecz'

const props = defineProps<{
    analytics: EczPassRateAnalytics
    grade_level: number
    exam_year: number
}>()

const gradeLevel = ref(props.grade_level)
const examYear = ref(props.exam_year)

function applyFilter() {
    router.get(route('ecz.analytics'), { grade_level: gradeLevel.value, exam_year: examYear.value }, { preserveState: true, preserveScroll: true })
}

const divisionColors = ['bg-green-500', 'bg-lime-400', 'bg-yellow-400', 'bg-orange-400']
const divisionLabels = ['Division I', 'Division II', 'Division III', 'Division IV']
const divisionCounts = [props.analytics.div1, props.analytics.div2, props.analytics.div3, props.analytics.div4]

function barWidth(count: number): number {
    return props.analytics.registered > 0 ? Math.round((count / props.analytics.registered) * 100) : 0
}
</script>

<template>
    <Head title="ECZ Analytics" />
    <div class="py-6">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">ECZ Analytics</h1>
                <div class="flex gap-3">
                    <select v-model="gradeLevel" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter">
                        <option :value="7">Grade 7</option>
                        <option :value="9">Grade 9</option>
                        <option :value="12">Grade 12</option>
                    </select>
                    <input v-model="examYear" type="number" class="w-24 rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter" />
                </div>
            </div>

            <!-- Summary cards -->
            <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 text-center shadow-sm">
                    <p class="text-3xl font-bold text-gray-900">{{ analytics.registered }}</p>
                    <p class="mt-1 text-sm text-gray-500">Registered</p>
                </div>
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-center shadow-sm">
                    <p class="text-3xl font-bold text-green-700">{{ analytics.passed }}</p>
                    <p class="mt-1 text-sm text-green-600">Passed</p>
                </div>
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-center shadow-sm">
                    <p class="text-3xl font-bold text-red-700">{{ analytics.failed }}</p>
                    <p class="mt-1 text-sm text-red-600">Failed</p>
                </div>
                <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-center shadow-sm">
                    <p class="text-3xl font-bold text-indigo-700">{{ analytics.pass_rate_pct }}%</p>
                    <p class="mt-1 text-sm text-indigo-600">Pass Rate</p>
                </div>
            </div>

            <!-- Division breakdown -->
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 font-medium text-gray-900">Division Breakdown</h2>
                <div class="space-y-3">
                    <div v-for="(label, i) in divisionLabels" :key="label" class="flex items-center gap-3">
                        <span class="w-24 text-sm text-gray-600">{{ label }}</span>
                        <div class="flex-1 overflow-hidden rounded-full bg-gray-100 h-4">
                            <div :class="['h-4 rounded-full', divisionColors[i]]" :style="{ width: barWidth(divisionCounts[i]) + '%' }"></div>
                        </div>
                        <span class="w-10 text-right text-sm font-medium text-gray-700">{{ divisionCounts[i] }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-24 text-sm text-gray-600">Fail</span>
                        <div class="flex-1 overflow-hidden rounded-full bg-gray-100 h-4">
                            <div class="h-4 rounded-full bg-red-400" :style="{ width: barWidth(analytics.failed) + '%' }"></div>
                        </div>
                        <span class="w-10 text-right text-sm font-medium text-gray-700">{{ analytics.failed }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
