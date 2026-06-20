<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { FeeReport } from '@/types/finance'

const props = defineProps<{
    report: FeeReport | null
    terms: Array<{ id: number; name: string }>
    filters: { termId?: number | null }
}>()

const termId = ref(props.filters.termId ?? '')

function applyFilter() {
    router.get(route('finance.reports'), { term_id: termId.value }, { preserveState: true })
}

function formatZmw(n: number) { return `ZMW ${Number(n).toFixed(2)}` }

const collectionDeg = computed(() => {
    if (!props.report) return 0
    return Math.min(180, (props.report.collection_rate_pct / 100) * 180)
})
</script>

<template>
    <AppLayout>
    <Head title="Finance Report" />
    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center gap-4">
                <h1 class="text-2xl font-semibold text-gray-900">Term Finance Report</h1>
                <select v-model="termId" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter">
                    <option value="">Select Term</option>
                    <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
            </div>

            <div v-if="report">
                <!-- Summary cards -->
                <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 text-center shadow-sm">
                        <p class="text-xl font-bold text-gray-900">{{ formatZmw(report.total_invoiced) }}</p>
                        <p class="text-sm text-gray-500">Total Invoiced</p>
                    </div>
                    <div class="rounded-lg border border-green-100 bg-green-50 p-4 text-center shadow-sm">
                        <p class="text-xl font-bold text-green-700">{{ formatZmw(report.total_collected) }}</p>
                        <p class="text-sm text-green-600">Collected</p>
                    </div>
                    <div class="rounded-lg border border-red-100 bg-red-50 p-4 text-center shadow-sm">
                        <p class="text-xl font-bold text-red-700">{{ formatZmw(report.outstanding) }}</p>
                        <p class="text-sm text-red-600">Outstanding</p>
                    </div>
                    <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-center shadow-sm">
                        <p class="text-3xl font-bold text-indigo-700">{{ report.collection_rate_pct }}%</p>
                        <p class="text-sm text-indigo-600">Collection Rate</p>
                    </div>
                </div>

                <!-- By-grade table -->
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <h2 class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700">By Grade</h2>
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Grade</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Invoiced</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Collected</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Outstanding</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="row in report.by_grade" :key="row.grade">
                                <td class="px-4 py-3 font-medium text-gray-900">Grade {{ row.grade }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ formatZmw(row.invoiced) }}</td>
                                <td class="px-4 py-3 text-right text-green-700">{{ formatZmw(row.collected) }}</td>
                                <td class="px-4 py-3 text-right text-red-700">{{ formatZmw(row.outstanding) }}</td>
                                <td class="px-4 py-3 text-right font-semibold" :class="row.collection_pct >= 80 ? 'text-green-600' : 'text-yellow-600'">
                                    {{ row.collection_pct }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else class="rounded-lg border border-gray-200 bg-white py-16 text-center text-gray-400">
                Select a term to view the finance report.
            </div>
        </div>
    </div>
    </AppLayout>
</template>
