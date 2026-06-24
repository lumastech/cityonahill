<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { Bed, Dormitory } from '@/types/boarding'

interface Term { id: number; name: string }
interface PupilResult { id: number; first_name: string; last_name: string; admission_no: string }

const props = defineProps<{
    dormitory: Dormitory
    terms: Term[]
    term_id: number | null
}>()

const selectedBed = ref<Bed | null>(null)
const showAddBedForm = ref(false)

const addBedForm = useForm({ bed_number: '' })
const allocateForm = useForm({ pupil_id: '' as string | number, bed_id: '', term_id: props.term_id ?? '', fee_amount: '0' })

// Pupil autocomplete state
const pupilQuery = ref('')
const pupilResults = ref<PupilResult[]>([])
const selectedPupil = ref<PupilResult | null>(null)
const showDropdown = ref(false)
let searchTimer: ReturnType<typeof setTimeout> | null = null

function onPupilInput() {
    selectedPupil.value = null
    allocateForm.pupil_id = ''
    if (searchTimer) clearTimeout(searchTimer)
    if (pupilQuery.value.length < 2) { pupilResults.value = []; showDropdown.value = false; return }
    searchTimer = setTimeout(async () => {
        const params = new URLSearchParams({ q: pupilQuery.value, gender: props.dormitory.gender })
        const res = await fetch(route('pupils.search') + '?' + params.toString())
        pupilResults.value = await res.json()
        showDropdown.value = pupilResults.value.length > 0
    }, 250)
}

function selectPupil(p: PupilResult) {
    selectedPupil.value = p
    allocateForm.pupil_id = p.id
    pupilQuery.value = `${p.first_name} ${p.last_name} (${p.admission_no})`
    pupilResults.value = []
    showDropdown.value = false
}

function clearPupil() {
    selectedPupil.value = null
    allocateForm.pupil_id = ''
    pupilQuery.value = ''
    pupilResults.value = []
    showDropdown.value = false
}

function selectBed(bed: Bed) {
    if (bed.status === 'available') {
        selectedBed.value = bed
        allocateForm.bed_id = String(bed.id)
        clearPupil()
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
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => { selectedBed.value = null; allocateForm.reset(); clearPupil() },
    })
}

function fmtDate(d: string | null | undefined): string {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

function vacateBed(allocationId: number) {
    if (!confirm('Vacate this bed?')) return
    router.post(route('allocations.vacate', allocationId), {}, {
        onSuccess: () => { selectedBed.value = null },
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
                    <p class="text-xs text-gray-500 mt-0.5">Since {{ fmtDate(selectedBed.active_allocation.allocated_date) }}</p>
                    <button
                        type="button"
                        class="mt-3 rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700"
                        @click="vacateBed(selectedBed!.active_allocation!.id)"
                    >
                        Vacate Bed
                    </button>
                </div>

                <!-- Available: allocate form -->
                <form v-else-if="selectedBed.status === 'available'" class="grid gap-3 sm:grid-cols-3" @submit.prevent="submitAllocate">
                    <div class="relative sm:col-span-3">
                        <label class="block text-xs text-gray-600">Pupil</label>
                        <input
                            v-model="pupilQuery"
                            type="text"
                            placeholder="Search by name or admission no…"
                            autocomplete="off"
                            :class="['mt-1 block w-full rounded-md text-sm shadow-sm', allocateForm.errors.pupil_id ? 'border-red-400 focus:ring-red-400' : 'border-gray-300']"
                            @input="onPupilInput"
                            @blur="() => setTimeout(() => { showDropdown = false }, 150)"
                        />
                        <ul v-if="showDropdown"
                            class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg text-sm">
                            <li v-for="p in pupilResults" :key="p.id"
                                class="cursor-pointer px-3 py-2 hover:bg-indigo-50"
                                @mousedown.prevent="selectPupil(p)">
                                {{ p.first_name }} {{ p.last_name }}
                                <span class="text-gray-400 text-xs ml-1">({{ p.admission_no }})</span>
                            </li>
                        </ul>
                        <p v-if="allocateForm.errors.pupil_id" class="mt-1 flex items-center gap-1 text-xs text-red-600">
                            <span>⚠</span> {{ allocateForm.errors.pupil_id }}
                        </p>
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
