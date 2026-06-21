<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { Bed, Dormitory } from '@/types/boarding'

interface Term { id: number; name: string }

const props = defineProps<{
    dormitory: Dormitory
    terms: Term[]
    term_id: number | null
}>()

const selectedBed = ref<Bed | null>(null)
const showAddBedForm = ref(false)

const addBedForm = useForm({ bed_number: '' })
const allocateForm = useForm({ pupil_id: '', bed_id: '', term_id: props.term_id ?? '', fee_amount: '0' })

function selectBed(bed: Bed) {
    if (bed.status === 'available') {
        selectedBed.value = bed
        allocateForm.bed_id = String(bed.id)
    } else if (bed.status === 'occupied') {
        selectedBed.value = bed
    }
}

function submitAddBed() {
    addBedForm.post(route('dormitories.beds.store', props.dormitory.id), {
        onSuccess: () => { showAddBedForm.value = false; addBedForm.reset() },
    })
}

function submitAllocate() {
    allocateForm.post(route('allocations.store'), {
        onSuccess: () => { selectedBed.value = null; allocateForm.reset() },
    })
}

const BED_COLORS: Record<string, string> = {
    available: 'bg-green-100 border-green-400 hover:bg-green-200 cursor-pointer',
    occupied: 'bg-red-100 border-red-400 hover:bg-red-200 cursor-pointer',
    maintenance: 'bg-gray-100 border-gray-300 cursor-default',
}
</script>

<template>
    <AppLayout>
    <Head :title="dormitory.name" />
    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <a :href="route('dormitories.index')" class="mb-4 block text-sm text-indigo-600 hover:underline">← Dormitories</a>

            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ dormitory.name }}</h1>
                    <span :class="['mt-1 inline-block rounded-full px-2 py-0.5 text-sm font-medium', dormitory.gender === 'male' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700']">
                        {{ dormitory.gender === 'male' ? '♂ Male' : '♀ Female' }} · {{ dormitory.capacity }} capacity
                    </span>
                </div>
                <button @click="showAddBedForm = !showAddBedForm"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    + Add Bed
                </button>
            </div>

            <!-- Add bed form -->
            <div v-if="showAddBedForm" class="mb-4 rounded-md border border-indigo-200 bg-indigo-50 p-3">
                <form class="flex items-end gap-3" @submit.prevent="submitAddBed">
                    <div>
                        <label class="block text-xs text-gray-600">Bed Number</label>
                        <input v-model="addBedForm.bed_number" type="text" placeholder="e.g. A-01"
                            class="mt-1 rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <button type="submit" :disabled="addBedForm.processing"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                        Add
                    </button>
                    <button type="button" @click="showAddBedForm = false"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700">
                        Cancel
                    </button>
                </form>
            </div>

            <!-- Legend -->
            <div class="mb-4 flex gap-4 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-green-200 border border-green-400"></span> Available</span>
                <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-red-200 border border-red-400"></span> Occupied</span>
                <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-gray-200 border border-gray-300"></span> Maintenance</span>
            </div>

            <!-- Bed grid -->
            <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-8">
                <button v-for="bed in dormitory.beds" :key="bed.id"
                    :class="['rounded-lg border-2 p-3 text-center transition-colors', BED_COLORS[bed.status], selectedBed?.id === bed.id ? 'ring-2 ring-indigo-400' : '']"
                    @click="selectBed(bed)">
                    <p class="text-xs font-bold text-gray-800">{{ bed.bed_number }}</p>
                    <p v-if="bed.status === 'occupied' && bed.active_allocation" class="mt-0.5 text-[10px] text-gray-600 truncate">
                        {{ bed.active_allocation.pupil?.first_name }}
                    </p>
                </button>
            </div>

            <!-- Selected bed info / allocate form -->
            <div v-if="selectedBed" class="mt-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-gray-700">
                    Bed {{ selectedBed.bed_number }}
                    <span :class="['ml-2 rounded-full px-2 py-0.5 text-xs font-normal', selectedBed.status === 'available' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                        {{ selectedBed.status }}
                    </span>
                </h2>

                <!-- Occupied: show pupil info -->
                <div v-if="selectedBed.status === 'occupied' && selectedBed.active_allocation">
                    <p class="text-sm text-gray-700">
                        <Link v-if="selectedBed.active_allocation.pupil" :href="route('pupils.show', selectedBed.active_allocation.pupil.id)" class="font-semibold hover:underline text-indigo-700">
                            {{ selectedBed.active_allocation.pupil.first_name }} {{ selectedBed.active_allocation.pupil.last_name }}
                        </Link>
                        ({{ selectedBed.active_allocation.pupil?.admission_no }})
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">Since {{ selectedBed.active_allocation.allocated_date }}</p>
                    <form class="mt-3" @submit.prevent="() => {
                        if (!confirm('Vacate this bed?')) return;
                        $inertia.post(route('allocations.vacate', selectedBed!.active_allocation!.id));
                    }">
                        <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">
                            Vacate Bed
                        </button>
                    </form>
                </div>

                <!-- Available: allocate form -->
                <form v-else-if="selectedBed.status === 'available'" class="grid gap-3 sm:grid-cols-3" @submit.prevent="submitAllocate">
                    <div>
                        <label class="block text-xs text-gray-600">Pupil ID</label>
                        <input v-model="allocateForm.pupil_id" type="number" placeholder="Pupil ID"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Term</label>
                        <select v-model="allocateForm.term_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">Select term…</option>
                            <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Fee (ZMW)</label>
                        <input v-model="allocateForm.fee_amount" type="number" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="flex items-end gap-2 sm:col-span-3">
                        <button type="submit" :disabled="allocateForm.processing"
                            class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">
                            Allocate Bed
                        </button>
                        <button type="button" @click="selectedBed = null"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
