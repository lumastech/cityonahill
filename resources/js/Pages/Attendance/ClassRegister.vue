<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import type { ClassRegister, AttendanceStatus, SessionType } from '@/types/attendance'

interface Stream {
    id: number
    name: string
    grade_id: number
    class_teacher_id: number | null
    grade?: { id: number; name: string; grade_number: number }
}

interface Term {
    id: number
    name: string
    number: number
    is_current: boolean
}

const props = defineProps<{
    streams: Stream[]
    selectedStreamId: number | null
    selectedDate: string
    register: ClassRegister | null
    terms: Term[]
}>()

const selectedStreamId = ref(props.selectedStreamId)
const selectedDate = ref(props.selectedDate)

watch([selectedStreamId, selectedDate], ([streamId, date]) => {
    if (streamId) {
        router.get(route('attendance.index'), { stream_id: streamId, date }, { preserveState: true, replace: true })
    }
})

const currentTerm = computed(() => props.terms.find((t) => t.is_current) ?? props.terms[0] ?? null)

const openForm = useForm({
    stream_id: props.selectedStreamId,
    term_id: currentTerm.value?.id ?? null,
    date: props.selectedDate,
    session_type: 'full_day' as SessionType,
})

function openRegister() {
    openForm.stream_id = selectedStreamId.value
    openForm.date = selectedDate.value
    openForm.post(route('attendance.store'))
}

const STATUS_LABELS: Record<AttendanceStatus, string> = {
    present: 'P',
    absent: 'A',
    late: 'L',
    excused: 'E',
    sick: 'S',
}

const STATUS_COLORS: Record<AttendanceStatus, string> = {
    present: 'bg-green-100 text-green-800 border-green-300',
    absent: 'bg-red-100 text-red-800 border-red-300',
    late: 'bg-yellow-100 text-yellow-800 border-yellow-300',
    excused: 'bg-blue-100 text-blue-800 border-blue-300',
    sick: 'bg-purple-100 text-purple-800 border-purple-300',
}

type RecordEntry = {
    pupil_id: number
    status: AttendanceStatus
    remarks: string
}

const records = ref<RecordEntry[]>([])
const isEditing = ref(false)

watch(
    () => props.register,
    (register) => {
        if (!register) {
            records.value = []
            return
        }
        if (register.session) {
            records.value = register.records.map((r) => ({
                pupil_id: r.pupil_id,
                status: r.status,
                remarks: r.remarks ?? '',
            }))
        } else {
            records.value = register.pupils.map((p) => ({
                pupil_id: p.id,
                status: 'absent' as AttendanceStatus,
                remarks: '',
            }))
        }
        isEditing.value = false
    },
    { immediate: true },
)

function setStatus(pupilId: number, status: AttendanceStatus) {
    const rec = records.value.find((r) => r.pupil_id === pupilId)
    if (rec) {
        rec.status = status
    }
}

const saveForm = useForm<{ records: RecordEntry[]; finalize: boolean }>({
    records: [],
    finalize: false,
})

function save(finalize = false) {
    saveForm.records = records.value
    saveForm.finalize = finalize
    saveForm.put(route('attendance.update', props.register?.session?.id), {
        onSuccess: () => {
            isEditing.value = false
        },
    })
}

const session = computed(() => props.register?.session ?? null)
const isFinalized = computed(() => session.value?.finalized === true)
const hasSession = computed(() => session.value !== null)
const pupils = computed(() => {
    if (!props.register) {
        return []
    }
    if (hasSession.value) {
        return props.register.records.map((r) => {
            const match = props.register!.pupils.find((p) => p.id === r.pupil_id) ??
                { id: r.pupil_id, full_name: r.pupil_name ?? '—', admission_no: r.admission_no ?? '' }
            return { ...match, record: records.value.find((rc) => rc.pupil_id === r.pupil_id)! }
        })
    }
    return props.register.pupils.map((p) => ({
        ...p,
        record: records.value.find((rc) => rc.pupil_id === p.id)!,
    }))
})

const presentCount = computed(() => records.value.filter((r) => r.status === 'present').length)
const absentCount = computed(() => records.value.filter((r) => r.status === 'absent').length)
const totalCount = computed(() => records.value.length)
</script>

<template>
    <AppLayout>
        <Head title="Class Register" />

        <div class="py-6 mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Class Register</h1>

                <div class="flex flex-wrap gap-3">
                    <select
                        v-model="selectedStreamId"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    >
                        <option :value="null">Select class…</option>
                        <option v-for="s in streams" :key="s.id" :value="s.id">
                            {{ s.grade?.name }} {{ s.name }}
                        </option>
                    </select>

                    <input
                        v-model="selectedDate"
                        type="date"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    />
                </div>
            </div>

            <div v-if="!selectedStreamId" class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center text-gray-500">
                Select a class to view the register.
            </div>

            <template v-else>
                <!-- Stats bar -->
                <div v-if="hasSession" class="mb-4 flex gap-4 rounded-lg bg-gray-50 p-4">
                    <span class="text-sm font-medium text-gray-700">
                        Present: <strong class="text-green-700">{{ presentCount }}</strong>
                    </span>
                    <span class="text-sm font-medium text-gray-700">
                        Absent: <strong class="text-red-700">{{ absentCount }}</strong>
                    </span>
                    <span class="text-sm font-medium text-gray-700">
                        Total: <strong>{{ totalCount }}</strong>
                    </span>
                    <span v-if="isFinalized" class="ml-auto rounded-full bg-green-100 px-3 py-0.5 text-xs font-semibold text-green-800">
                        Finalised
                    </span>
                </div>

                <!-- Error -->
                <div v-if="$page.props.errors?.conflict" class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                    {{ $page.props.errors.conflict }}
                </div>

                <!-- No session yet -->
                <div v-if="!hasSession" class="mb-6 rounded-lg border border-gray-200 bg-white p-6">
                    <p class="mb-4 text-sm text-gray-600">No register has been opened for this class on {{ selectedDate }}.</p>

                    <div class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Session Type</label>
                            <select
                                v-model="openForm.session_type"
                                class="rounded-md border-gray-300 text-sm shadow-sm"
                            >
                                <option value="full_day">Full Day</option>
                                <option value="morning">Morning</option>
                                <option value="afternoon">Afternoon</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Term</label>
                            <select
                                v-model="openForm.term_id"
                                class="rounded-md border-gray-300 text-sm shadow-sm"
                            >
                                <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                        <button
                            :disabled="openForm.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            @click="openRegister"
                        >
                            Open Register
                        </button>
                    </div>
                </div>

                <!-- Pupil list -->
                <div v-if="register" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pupil</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adm. No.</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th v-if="!isFinalized || isEditing" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(pupil, idx) in pupils" :key="pupil.id">
                                <td class="px-4 py-2 text-sm text-gray-500">{{ idx + 1 }}</td>
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ pupil.full_name }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ pupil.admission_no }}</td>
                                <td class="px-4 py-2">
                                    <div v-if="isFinalized && !isEditing" class="flex justify-center">
                                        <span
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-full border text-xs font-bold"
                                            :class="STATUS_COLORS[pupil.record?.status ?? 'absent']"
                                        >
                                            {{ STATUS_LABELS[pupil.record?.status ?? 'absent'] }}
                                        </span>
                                    </div>
                                    <div v-else class="flex justify-center gap-1">
                                        <button
                                            v-for="(label, status) in STATUS_LABELS"
                                            :key="status"
                                            class="h-7 w-7 rounded-full border text-xs font-bold transition-colors"
                                            :class="pupil.record?.status === status ? STATUS_COLORS[status as AttendanceStatus] : 'border-gray-200 text-gray-400 hover:border-gray-400'"
                                            @click="setStatus(pupil.id, status as AttendanceStatus)"
                                        >
                                            {{ label }}
                                        </button>
                                    </div>
                                </td>
                                <td v-if="!isFinalized || isEditing" class="px-4 py-2">
                                    <input
                                        v-model="pupil.record!.remarks"
                                        type="text"
                                        placeholder="Remarks…"
                                        class="w-full rounded border-gray-200 text-xs focus:border-indigo-300 focus:ring-1 focus:ring-indigo-300"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="hasSession" class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-4 py-3">
                        <template v-if="isFinalized && !isEditing">
                            <button
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                @click="isEditing = true"
                            >
                                Edit
                            </button>
                        </template>
                        <template v-else>
                            <button
                                v-if="isEditing"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                @click="isEditing = false"
                            >
                                Cancel
                            </button>
                            <button
                                :disabled="saveForm.processing"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                                @click="save(false)"
                            >
                                Save
                            </button>
                            <button
                                v-if="!isFinalized"
                                :disabled="saveForm.processing"
                                class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                                @click="save(true)"
                            >
                                Finalise
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
