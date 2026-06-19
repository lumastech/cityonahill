<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import type { PupilTransport, TransportRoute } from '@/types/transport'

defineProps<{
    route: TransportRoute
    manifest: PupilTransport[]
    term_id: number | null
}>()

function printPage() {
    window.print()
}
</script>

<template>
    <Head :title="`Manifest — ${route.name}`" />
    <div class="min-h-screen bg-white p-8 print:p-4">
        <!-- Print controls — hidden on print -->
        <div class="mb-6 flex items-center justify-between print:hidden">
            <a :href="route('transport-routes.show', route.id)" class="text-sm text-indigo-600 hover:underline">← Back</a>
            <button @click="printPage" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Print
            </button>
        </div>

        <!-- Header -->
        <div class="mb-6 border-b border-gray-300 pb-4">
            <h1 class="text-xl font-bold text-gray-900">Daily Route Manifest</h1>
            <div class="mt-1 flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-600">
                <span><strong>Route:</strong> {{ route.name }}</span>
                <span v-if="route.vehicle_registration"><strong>Vehicle:</strong> {{ route.vehicle_registration }}</span>
                <span v-if="route.driver_name"><strong>Driver:</strong> {{ route.driver_name }}</span>
                <span v-if="route.driver_phone"><strong>Phone:</strong> {{ route.driver_phone }}</span>
                <span><strong>Capacity:</strong> {{ route.capacity }}</span>
                <span><strong>Date:</strong> {{ new Date().toLocaleDateString('en-ZM', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</span>
            </div>
        </div>

        <!-- Pickup stops summary -->
        <div class="mb-4">
            <h2 class="mb-1 text-sm font-semibold text-gray-700">Pickup Stops:</h2>
            <p class="text-sm text-gray-600">{{ route.pickup_points.join(' → ') }}</p>
        </div>

        <!-- Pupil list -->
        <table class="min-w-full border border-gray-300 text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-3 py-2 text-left">#</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Pupil Name</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Admission No.</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Grade</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Pickup Stop</th>
                    <th class="border border-gray-300 px-3 py-2 text-left">Direction</th>
                    <th class="border border-gray-300 px-3 py-2 text-center">✓</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(a, idx) in manifest" :key="a.id" :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                    <td class="border border-gray-300 px-3 py-2 text-gray-500">{{ idx + 1 }}</td>
                    <td class="border border-gray-300 px-3 py-2 font-medium text-gray-900">
                        {{ a.pupil?.first_name }} {{ a.pupil?.last_name }}
                    </td>
                    <td class="border border-gray-300 px-3 py-2 text-gray-600">{{ a.pupil?.admission_no }}</td>
                    <td class="border border-gray-300 px-3 py-2 text-gray-600">
                        {{ a.pupil?.stream?.grade?.name }} {{ a.pupil?.stream?.name }}
                    </td>
                    <td class="border border-gray-300 px-3 py-2 text-gray-600">{{ a.pickup_point }}</td>
                    <td class="border border-gray-300 px-3 py-2 text-gray-500 capitalize">{{ a.direction.replace('_', ' ') }}</td>
                    <td class="border border-gray-300 px-3 py-2 text-center text-gray-300">☐</td>
                </tr>
                <tr v-if="!manifest.length">
                    <td colspan="7" class="border border-gray-300 px-3 py-6 text-center text-gray-400">No pupils assigned.</td>
                </tr>
            </tbody>
        </table>

        <div class="mt-6 flex justify-between text-xs text-gray-400 print:mt-8">
            <span>Total pupils: {{ manifest.length }}</span>
            <span>Printed: {{ new Date().toLocaleString('en-ZM') }}</span>
        </div>
    </div>
</template>
