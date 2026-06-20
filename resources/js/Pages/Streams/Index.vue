<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Grade        { id: number; name: string; grade_number: number }
interface Teacher      { id: number; name: string }
interface AcademicYear { id: number; name: string }
interface Stream {
    id: number
    name: string
    capacity: number
    pupils_count: number
    grade: Grade | null
    class_teacher: Teacher | null
}

const props = defineProps<{
    streams: Stream[]
    filterGradeId: number | null
    grades: Grade[]
    teachers: Teacher[]
    academic_years: AcademicYear[]
}>()

const showForm = ref(false)
const selectedGrade = ref<number | ''>(props.filterGradeId ?? '')

function filterByGrade() {
    router.get(route('streams.index'), { grade_id: selectedGrade.value || undefined }, { preserveState: true, replace: true })
}

const form = useForm({
    grade_id:         null as number | null,
    name:             '',
    class_teacher_id: null as number | null,
    capacity:         45,
    academic_year_id: null as number | null,
})

function submit() {
    form.post(route('streams.store'), {
        onSuccess: () => { form.reset(); showForm.value = false },
    })
}

function remove(id: number) {
    if (confirm('Delete this stream? This cannot be undone.')) {
        useForm({}).delete(route('streams.destroy', id))
    }
}
</script>

<template>
    <AppLayout title="Streams">
        <Head title="Streams" />

        <div class="py-6 mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('grades.index')" class="text-sm text-gray-500 hover:text-gray-700">← Grades</Link>
                    <h1 class="text-2xl font-bold text-gray-900">Streams</h1>
                </div>
                <div class="flex items-center gap-3">
                    <select v-model="selectedGrade" class="rounded-md border-gray-300 text-sm shadow-sm" @change="filterByGrade">
                        <option value="">All Grades</option>
                        <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                    </select>
                    <button
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        @click="showForm = !showForm"
                    >
                        + New Stream
                    </button>
                </div>
            </div>

            <!-- Create form -->
            <div v-if="showForm" class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-5">
                <h2 class="mb-4 text-sm font-semibold text-indigo-800">New Stream</h2>
                <form class="grid grid-cols-2 gap-4 sm:grid-cols-3" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Grade <span class="text-red-500">*</span></label>
                        <select v-model="form.grade_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                            <option :value="null">Select grade…</option>
                            <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                        <p v-if="form.errors.grade_id" class="mt-1 text-xs text-red-600">{{ form.errors.grade_id }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Stream Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" placeholder="e.g. A, B, North" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Class Teacher</label>
                        <select v-model="form.class_teacher_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option :value="null">Unassigned</option>
                            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <p v-if="form.errors.class_teacher_id" class="mt-1 text-xs text-red-600">{{ form.errors.class_teacher_id }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Academic Year</label>
                        <select v-model="form.academic_year_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option :value="null">Select year…</option>
                            <option v-for="y in academic_years" :key="y.id" :value="y.id">{{ y.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Capacity</label>
                        <input v-model.number="form.capacity" type="number" min="1" max="100" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.capacity" class="mt-1 text-xs text-red-600">{{ form.errors.capacity }}</p>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Create
                        </button>
                        <button type="button" class="rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50" @click="showForm = false">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Streams table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Stream</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Grade</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Class Teacher</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Pupils</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Capacity</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="s in streams" :key="s.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ s.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ s.grade?.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ s.class_teacher?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="font-medium"
                                    :class="s.pupils_count >= s.capacity ? 'text-red-600' : 'text-gray-900'"
                                >{{ s.pupils_count }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ s.capacity }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3">
                                    <Link :href="route('streams.edit', s.id)" class="text-xs text-indigo-600 hover:underline">Edit</Link>
                                    <button class="text-xs text-red-500 hover:underline" @click="remove(s.id)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!streams.length">
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                No streams found. Click <strong>+ New Stream</strong> to add one.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
