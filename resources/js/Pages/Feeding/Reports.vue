<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { FeedingStats } from '@/types/feeding'

interface Term { id: number; name: string }

const props = defineProps<{
    terms: Term[]
    term_id: number | null
    stats: FeedingStats | null
}>()

const selectedTermId = ref(props.term_id ?? '')

function loadStats() {
    router.get(route('feeding.reports'), { term_id: selectedTermId.value }, { preserveState: true })
}

const maxMeals = computed(() =>
    Math.max(1, ...(props.stats?.by_day.map(d => d.meals) ?? [0]))
)
</script>

<template>
    <Head title="Feeding Reports" />
    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-gray-900 mr-4">Feeding Reports</h1>
                <select v-model="selectedTermId" class="rounded-md border-gray-300 text-sm shadow-sm" @change="loadStats">
                    <option value="">Select term…</option>
                    <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
            </div>

            <template v-if="stats">
                <!-- Summary card -->
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4">
                    <p class="text-3xl font-bold text-green-700">{{ stats.total_served.toLocaleString() }}</p>
                    <p class="mt-0.5 text-sm text-green-600">Total meals served this term</p>
                </div>

                <!-- Per-day bar chart -->
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Daily Meals Served</h2>
                    <div v-if="stats.by_day.length" class="space-y-1.5">
                        <div v-for="day in stats.by_day" :key="day.date" class="flex items-center gap-2">
                            <span class="w-24 shrink-0 text-right text-xs text-gray-500">{{ day.date }}</span>
                            <div class="flex-1 rounded-full bg-gray-100 h-5 overflow-hidden">
                                <div class="h-5 rounded-full bg-green-500"
                                    :style="{ width: (day.meals / maxMeals * 100) + '%' }" />
                            </div>
                            <span class="w-10 text-right text-xs font-medium text-gray-700">{{ day.meals }}</span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No data for this term.</p>
                </div>
            </template>

            <div v-else class="py-16 text-center text-gray-400">Select a term to view feeding statistics.</div>
        </div>
    </div>
</template>
