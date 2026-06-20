<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { PupilTransport, TransportRoute } from '@/types/transport'

interface Term { id: number; name: string }
interface Pupil { id: number; first_name: string; last_name: string; admission_no: string }

const props = defineProps<{
    route: TransportRoute
    terms: Term[]
    term_id: number | null
    manifest: PupilTransport[]
}>()

const selectedTermId = ref(props.term_id ?? '')

function loadManifest() {
    router.get(route('transport-routes.show', props.route.id), { term_id: selectedTermId.value }, { preserveState: true })
}

const showAssignForm = ref(false)

const assignForm = useForm({
    pupil_id: '',
    route_id: props.route.id,
    pickup_point: '',
    direction: 'both',
    term_id: props.term_id ?? '',
    fee_amount: '0',
})

function submitAssign() {
    assignForm.post(route('pupil-transport.store'), {
        onSuccess: () => {
            showAssignForm.value = false
            assignForm.reset()
        },
    })
}

function removeAssignment(id: number) {
    if (!confirm('Remove this pupil from the route?')) return
    router.delete(route('pupil-transport.destroy', id))
}
</script>

<template>
    <AppLayout>
    <Head :title="route.name" />
    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <a :href="route('transport-routes.index')" class="mb-4 block text-sm text-indigo-600 hover:underline">← Routes</a>

            <div class="mb-6 flex flex-wrap items-start gap-4">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">{{ route.name }}</h1>
                    <p v-if="route.vehicle_registration" class="mt-1 text-sm text-gray-500">
                        {{ route.vehicle_type }} · {{ route.vehicle_registration }}
                    </p>
                    <p v-if="route.driver_name" class="text-sm text-gray-500">
                        Driver: {{ route.driver_name }}{{ route.driver_phone ? ' · ' + route.driver_phone : '' }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <a :href="route('route-manifest.show', route.id) + (term_id ? '?term_id=' + term_id : '')"
                        target="_blank" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Print Manifest
                    </a>
                    <button @click="showAssignForm = !showAssignForm"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        + Assign Pupil
                    </button>
                </div>
            </div>

            <!-- Pickup points -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-sm font-semibold text-gray-700">Pickup Stops</h2>
                <ol class="list-decimal pl-5 space-y-1 text-sm text-gray-700">
                    <li v-for="(stop, i) in route.pickup_points" :key="i">{{ stop }}</li>
                </ol>
            </div>

            <!-- Assign form -->
            <div v-if="showAssignForm" class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-indigo-700">Assign Pupil to Route</h2>
                <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitAssign">
                    <div>
                        <label class="block text-xs text-gray-600">Pupil ID</label>
                        <input v-model="assignForm.pupil_id" type="number" placeholder="Pupil ID"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Pickup Point</label>
                        <select v-model="assignForm.pickup_point" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">Select stop…</option>
                            <option v-for="stop in route.pickup_points" :key="stop" :value="stop">{{ stop }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Direction</label>
                        <select v-model="assignForm.direction" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="both">Both ways</option>
                            <option value="to_school">To school only</option>
                            <option value="from_school">From school only</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Term</label>
                        <select v-model="assignForm.term_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">Select term…</option>
                            <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Fee (ZMW)</label>
                        <input v-model="assignForm.fee_amount" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" :disabled="assignForm.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Assign
                        </button>
                        <button type="button" @click="showAssignForm = false" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Manifest table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <h2 class="text-sm font-semibold text-gray-700">Pupil Manifest</h2>
                    <select v-model="selectedTermId" class="rounded-md border-gray-300 text-sm shadow-sm" @change="loadManifest">
                        <option value="">All terms</option>
                        <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Pupil</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Admission No.</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Grade</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Pickup Stop</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Direction</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="a in manifest" :key="a.id">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ a.pupil?.first_name }} {{ a.pupil?.last_name }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ a.pupil?.admission_no }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ a.pupil?.stream?.grade?.name }} {{ a.pupil?.stream?.name }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ a.pickup_point }}</td>
                            <td class="px-4 py-3 text-gray-500 capitalize">{{ a.direction.replace('_', ' ') }}</td>
                            <td class="px-4 py-3 text-right">
                                <button @click="removeAssignment(a.id)" class="text-xs text-red-500 hover:underline">Remove</button>
                            </td>
                        </tr>
                        <tr v-if="!manifest.length">
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                {{ term_id ? 'No pupils assigned for this term.' : 'Select a term to view manifest.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
