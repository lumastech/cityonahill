<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
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

// New Route modal
const showModal = ref(false)
const form = useForm({
    name: '',
    capacity: 50,
    vehicle_registration: '',
    vehicle_type: '',
    driver_name: '',
    driver_phone: '',
    pickup_points: [''] as string[],
})

function openModal() { showModal.value = true }
function closeModal() { showModal.value = false; form.reset(); form.pickup_points = [''] }

function addPickupPoint() { form.pickup_points.push('') }
function removePickupPoint(i: number) {
    if (form.pickup_points.length > 1) form.pickup_points.splice(i, 1)
}

function submit() {
    // Filter out blank entries before submitting
    form.pickup_points = form.pickup_points.filter(p => p.trim() !== '')
    if (form.pickup_points.length === 0) form.pickup_points = ['']
    form.post(route('transport-routes.store'), {
        onSuccess: () => closeModal(),
    })
}
</script>

<template>
    <AppLayout>
    <Head title="Transport Routes" />
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Transport Routes</h1>
                <button
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="openModal"
                >
                    + New Route
                </button>
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

    <!-- New Route Modal -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="closeModal" />
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">New Transport Route</h2>
                    <button class="text-gray-400 hover:text-gray-600 text-xl leading-none" @click="closeModal">&times;</button>
                </div>

                <form class="px-6 py-5 space-y-4" @submit.prevent="submit">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Route Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" placeholder="e.g. Chilenje North" class="w-full rounded-md border-gray-300 text-sm" :class="{ 'border-red-400': form.errors.name }" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <!-- Capacity -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacity <span class="text-red-500">*</span></label>
                        <input v-model.number="form.capacity" type="number" min="1" class="w-full rounded-md border-gray-300 text-sm" :class="{ 'border-red-400': form.errors.capacity }" />
                        <p v-if="form.errors.capacity" class="mt-1 text-xs text-red-600">{{ form.errors.capacity }}</p>
                    </div>

                    <!-- Vehicle -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
                            <input v-model="form.vehicle_type" type="text" placeholder="e.g. Bus, Minibus" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Registration No.</label>
                            <input v-model="form.vehicle_registration" type="text" placeholder="e.g. ALB 1234" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>

                    <!-- Driver -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Driver Name</label>
                            <input v-model="form.driver_name" type="text" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Driver Phone</label>
                            <input v-model="form.driver_phone" type="text" placeholder="+260…" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>

                    <!-- Pickup points -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pickup Points <span class="text-red-500">*</span></label>
                        <div class="space-y-2">
                            <div v-for="(_, i) in form.pickup_points" :key="i" class="flex items-center gap-2">
                                <span class="text-xs text-gray-400 w-5 text-right">{{ i + 1 }}.</span>
                                <input
                                    v-model="form.pickup_points[i]"
                                    type="text"
                                    placeholder="e.g. Chilenje Market"
                                    class="flex-1 rounded-md border-gray-300 text-sm"
                                />
                                <button
                                    type="button"
                                    class="text-gray-400 hover:text-red-500 text-lg leading-none px-1"
                                    :disabled="form.pickup_points.length === 1"
                                    @click="removePickupPoint(i)"
                                >&times;</button>
                            </div>
                        </div>
                        <button type="button" class="mt-2 text-sm text-indigo-600 hover:underline" @click="addPickupPoint">
                            + Add stop
                        </button>
                        <p v-if="form.errors.pickup_points" class="mt-1 text-xs text-red-600">{{ form.errors.pickup_points }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" class="px-4 py-2 text-sm text-gray-700 border rounded-md hover:bg-gray-50" @click="closeModal">Cancel</button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Creating…' : 'Create Route' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
    </AppLayout>
</template>
