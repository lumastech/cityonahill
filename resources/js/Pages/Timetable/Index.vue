<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

interface Stream { id: number; name: string; grade?: { id: number; name: string } }
interface Subject { id: number; name: string; code: string }
interface Teacher { id: number; name: string }
interface TimetableSlot {
    id: number
    subject?: Subject
    teacher?: { id: number; name: string }
    stream?: { id: number; name: string; grade?: { id: number; name: string } }
    day_of_week: number
    period_number: number
    start_time: string
    end_time: string
    room: string | null
}

const props = defineProps<{
    timetable: Record<number, TimetableSlot[]>
    viewMode: 'stream' | 'teacher' | null
    streamId: number | null
    teacherId: number | null
    streams: Stream[]
    subjects: Subject[]
    teachers: Teacher[]
}>()

const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
const periods = computed(() => {
    const all = Object.values(props.timetable).flatMap(slots => slots.map(s => s.period_number))
    return all.length ? [...new Set(all)].sort((a, b) => a - b) : [1, 2, 3, 4, 5, 6, 7, 8]
})

function slotFor(day: number, period: number): TimetableSlot | undefined {
    return props.timetable[day]?.find(s => s.period_number === period)
}

function filterByStream() {
    router.get(route('timetable.index'), { stream_id: selectedStream.value || undefined }, { preserveState: true, replace: true })
}

function filterByTeacher() {
    router.get(route('timetable.index'), { teacher_id: selectedTeacher.value || undefined }, { preserveState: true, replace: true })
}

const selectedStream = ref(props.streamId ?? '')
const selectedTeacher = ref(props.teacherId ?? '')
const showAddForm = ref(false)

const addForm = useForm({
    stream_id: props.streamId ?? '',
    subject_id: '',
    teacher_id: '',
    day_of_week: 1,
    period_number: 1,
    start_time: '07:30',
    end_time: '08:30',
    room: '',
})

function submitSlot() {
    addForm.post(route('timetable.store'), {
        onSuccess: () => { addForm.reset(); showAddForm.value = false },
    })
}

function removeSlot(id: number) {
    if (confirm('Remove this timetable slot?')) {
        useForm({}).delete(route('timetable.destroy', id))
    }
}
</script>

<template>
    <AppLayout title="Timetable">
        <Head title="Timetable" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Timetable</h1>
                <button
                    v-if="viewMode"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="showAddForm = !showAddForm"
                >
                    + Add Slot
                </button>
            </div>

            <!-- Selectors -->
            <div class="mb-6 flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-600">View by Class:</label>
                    <select v-model="selectedStream" class="rounded-md border-gray-300 text-sm shadow-sm" @change="filterByStream">
                        <option value="">Select class…</option>
                        <option v-for="s in streams" :key="s.id" :value="s.id">{{ s.grade?.name }} {{ s.name }}</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-600">or by Teacher:</label>
                    <select v-model="selectedTeacher" class="rounded-md border-gray-300 text-sm shadow-sm" @change="filterByTeacher">
                        <option value="">Select teacher…</option>
                        <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Add slot form -->
            <div v-if="showAddForm" class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-5">
                <h2 class="mb-4 text-sm font-semibold text-indigo-800">Add Timetable Slot</h2>
                <form class="grid grid-cols-2 gap-4 sm:grid-cols-4" @submit.prevent="submitSlot">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Class</label>
                        <select v-model="addForm.stream_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                            <option value="">Select…</option>
                            <option v-for="s in streams" :key="s.id" :value="s.id">{{ s.grade?.name }} {{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Subject</label>
                        <select v-model="addForm.subject_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                            <option value="">Select…</option>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Teacher</label>
                        <select v-model="addForm.teacher_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                            <option value="">Select…</option>
                            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Day</label>
                        <select v-model="addForm.day_of_week" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option v-for="(day, i) in DAYS" :key="i + 1" :value="i + 1">{{ day }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Period</label>
                        <input v-model="addForm.period_number" type="number" min="1" max="10" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Start</label>
                        <input v-model="addForm.start_time" type="time" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">End</label>
                        <input v-model="addForm.end_time" type="time" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Room</label>
                        <input v-model="addForm.room" type="text" placeholder="optional" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="col-span-2 sm:col-span-4 flex gap-2 justify-end">
                        <button type="submit" :disabled="addForm.processing" class="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Add Slot
                        </button>
                        <button type="button" class="rounded-md border px-4 py-1.5 text-sm text-gray-600 hover:bg-gray-100" @click="showAddForm = false">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Grid -->
            <div v-if="viewMode" class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-16 px-3 py-3 text-left font-medium text-gray-500">P#</th>
                            <th v-for="(day, i) in DAYS" :key="i" class="px-3 py-3 text-left font-medium text-gray-600">{{ day }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="period in periods" :key="period" class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-semibold text-gray-500 text-center">{{ period }}</td>
                            <td v-for="(day, i) in DAYS" :key="i" class="px-3 py-2 align-top">
                                <template v-if="slotFor(i + 1, period)">
                                    <div class="rounded-md bg-indigo-50 border border-indigo-100 p-2 text-xs">
                                        <div class="font-semibold text-indigo-800">{{ slotFor(i + 1, period)?.subject?.name }}</div>
                                        <div class="text-gray-500 mt-0.5">
                                            <template v-if="viewMode === 'stream'">{{ slotFor(i + 1, period)?.teacher?.name }}</template>
                                            <template v-else>{{ slotFor(i + 1, period)?.stream?.grade?.name }} {{ slotFor(i + 1, period)?.stream?.name }}</template>
                                        </div>
                                        <div class="text-gray-400 mt-0.5">{{ slotFor(i + 1, period)?.start_time }} – {{ slotFor(i + 1, period)?.end_time }}</div>
                                        <button class="mt-1 text-red-500 hover:underline text-xs" @click="removeSlot(slotFor(i + 1, period)!.id)">Remove</button>
                                    </div>
                                </template>
                                <span v-else class="text-gray-200 text-xs">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="rounded-lg border-2 border-dashed border-gray-200 p-16 text-center text-gray-400">
                Select a class or teacher above to view their timetable.
            </div>
        </div>
    </AppLayout>
</template>
