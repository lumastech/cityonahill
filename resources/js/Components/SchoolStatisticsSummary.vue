<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { SchoolStatistics } from '@/types/pupils'

const stats = ref<SchoolStatistics | null>(null)
const loading = ref(true)
const error = ref(false)

onMounted(async () => {
    try {
        const res = await fetch(route('school.statistics'))
        stats.value = await res.json()
    } catch {
        error.value = true
    } finally {
        loading.value = false
    }
})

function genderPercent(count: number): number {
    if (!stats.value) return 0
    const total = stats.value.by_gender.male + stats.value.by_gender.female
    return total ? Math.round((count / total) * 100) : 0
}

function gradeBarWidth(count: number): string {
    if (!stats.value || !stats.value.by_grade.length) return '0%'
    const max = Math.max(...stats.value.by_grade.map((g) => g.count))
    return max ? `${Math.round((count / max) * 100)}%` : '0%'
}
</script>

<template>
    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-800 mb-4">School Statistics</h3>

        <div v-if="loading" class="space-y-3">
            <div v-for="i in 4" :key="i" class="h-4 bg-gray-100 rounded animate-pulse" />
        </div>

        <div v-else-if="error" class="text-sm text-gray-400 text-center py-4">
            Unable to load statistics.
        </div>

        <div v-else-if="stats" class="space-y-5">
            <!-- Total -->
            <div class="text-center">
                <p class="text-4xl font-bold text-indigo-600">{{ stats.total_pupils }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Total Pupils</p>
            </div>

            <!-- Gender donut (CSS) -->
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase mb-2">By Gender</p>
                <div class="flex rounded-full overflow-hidden h-4">
                    <div
                        class="bg-blue-500 transition-all"
                        :style="{ width: genderPercent(stats.by_gender.male) + '%' }"
                    />
                    <div
                        class="bg-pink-400 flex-1"
                    />
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>♂ {{ stats.by_gender.male }} ({{ genderPercent(stats.by_gender.male) }}%)</span>
                    <span>♀ {{ stats.by_gender.female }} ({{ genderPercent(stats.by_gender.female) }}%)</span>
                </div>
            </div>

            <!-- By Grade bars -->
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase mb-2">By Grade</p>
                <div class="space-y-1.5 max-h-40 overflow-y-auto">
                    <div v-for="row in stats.by_grade" :key="row.grade" class="flex items-center gap-2 text-xs">
                        <span class="w-16 text-gray-600 truncate">{{ row.grade }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-2">
                            <div
                                class="bg-indigo-400 rounded-full h-2 transition-all"
                                :style="{ width: gradeBarWidth(row.count) }"
                            />
                        </div>
                        <span class="w-6 text-gray-500 text-right">{{ row.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Status pills -->
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase mb-2">By Status</p>
                <div class="flex flex-wrap gap-2">
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                        Active: {{ stats.by_status.active }}
                    </span>
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                        Transferred: {{ stats.by_status.transferred }}
                    </span>
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                        Withdrawn: {{ stats.by_status.withdrawn }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
