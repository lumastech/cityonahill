<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import type { TransportRoute } from '@/types/transport'

defineProps<{
    routes: (TransportRoute & { occupancy: number })[]
}>()

function capacityPct(occupied: number, total: number): number {
    return total > 0 ? Math.round((occupied / total) * 100) : 0
}

function barColor(pct: number): string {
    if (pct >= 90) return 'bg-red-500'
    if (pct >= 70) return 'bg-yellow-400'
    return 'bg-green-500'
}
</script>

<template>
    <Head title="Transport Routes" />
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Transport Routes</h1>
                <a :href="route('transport-routes.index')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    + New Route
                </a>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="r in routes" :key="r.id"
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div>
                            <a :href="route('transport-routes.show', r.id)" class="text-base font-semibold text-gray-900 hover:text-indigo-600">
                                {{ r.name }}
                            </a>
                            <p v-if="r.vehicle_registration" class="mt-0.5 text-xs text-gray-500">
                                {{ r.vehicle_type ? r.vehicle_type + ' · ' : '' }}{{ r.vehicle_registration }}
                            </p>
                        </div>
                        <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', r.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                            {{ r.status }}
                        </span>
                    </div>

                    <div v-if="r.driver_name" class="mt-2 text-xs text-gray-600">
                        Driver: {{ r.driver_name }}
                        <span v-if="r.driver_phone"> · {{ r.driver_phone }}</span>
                    </div>

                    <!-- Capacity bar -->
                    <div class="mt-3">
                        <div class="mb-1 flex justify-between text-xs text-gray-500">
                            <span>Capacity</span>
                            <span>{{ r.occupancy ?? 0 }} / {{ r.capacity }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                            <div :class="['h-2 rounded-full transition-all', barColor(capacityPct(r.occupancy ?? 0, r.capacity))]"
                                :style="{ width: capacityPct(r.occupancy ?? 0, r.capacity) + '%' }" />
                        </div>
                    </div>

                    <div class="mt-3 flex gap-2 text-xs">
                        <a :href="route('transport-routes.show', r.id)" class="text-indigo-600 hover:underline">View manifest</a>
                    </div>
                </div>
            </div>

            <div v-if="!routes.length" class="py-16 text-center text-gray-400">No transport routes yet.</div>
        </div>
    </div>
</template>
