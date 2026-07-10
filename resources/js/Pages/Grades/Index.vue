<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Grade {
    id: number
    name: string
    grade_number: number
    level: string
    is_ecz_year: boolean
    order_index: number
    streams_count: number
    pupils_count: number
}

defineProps<{ grades: Grade[] }>()

const showForm = ref(false)

const form = useForm({
    name: '',
    grade_number: '',
    level: 'junior_secondary',
    is_ecz_year: false,
    order_index: 0,
})

function submit() {
    form.post(route('grades.store'), {
        onSuccess: () => { form.reset(); showForm.value = false },
    })
}

function remove(id: number) {
    if (confirm('Delete this grade?')) {
        useForm({}).delete(route('grades.destroy', id))
    }
}

const LEVEL_LABELS: Record<string, string> = {
    ece: 'Early Childhood Education',
    primary: 'Primary',
    junior_secondary: 'Junior Secondary',
    senior_secondary: 'Senior Secondary',
}

const LEVEL_COLORS: Record<string, string> = {
    ece: 'bg-amber-100 text-amber-700',
    primary: 'bg-green-100 text-green-700',
    junior_secondary: 'bg-blue-100 text-blue-700',
    senior_secondary: 'bg-purple-100 text-purple-700',
}
</script>

<template>
    <AppLayout title="Grades">
        <Head title="Grades" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Grades / Classes</h1>
                <button
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="showForm = !showForm"
                >
                    + Add Grade
                </button>
            </div>

            <!-- Add form -->
            <div v-if="showForm" class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-5">
                <h2 class="mb-4 text-sm font-semibold text-indigo-800">New Grade</h2>
                <div v-if="form.errors.is_ecz_year || form.errors.order_index" class="mb-4 rounded-md bg-red-50 border border-red-200 p-3">
                    <p v-if="form.errors.is_ecz_year" class="text-xs text-red-600">{{ form.errors.is_ecz_year }}</p>
                    <p v-if="form.errors.order_index" class="text-xs text-red-600">{{ form.errors.order_index }}</p>
                </div>
                <form class="grid grid-cols-2 gap-4 sm:grid-cols-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                        <input v-model="form.name" type="text" placeholder="e.g. Grade 8" class="w-full rounded-md border-gray-300 text-sm shadow-sm" :class="{ 'border-red-400': form.errors.name }" required />
                        <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Grade No.</label>
                        <input v-model="form.grade_number" type="number" min="0" max="12" class="w-full rounded-md border-gray-300 text-sm shadow-sm" :class="{ 'border-red-400': form.errors.grade_number }" required />
                        <p v-if="form.errors.grade_number" class="text-xs text-red-600 mt-1">{{ form.errors.grade_number }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Level</label>
                        <select v-model="form.level" class="w-full rounded-md border-gray-300 text-sm shadow-sm" :class="{ 'border-red-400': form.errors.level }">
                            <option value="ece">Early Childhood Education</option>
                            <option value="primary">Primary</option>
                            <option value="junior_secondary">Junior Secondary</option>
                            <option value="senior_secondary">Senior Secondary</option>
                        </select>
                        <p v-if="form.errors.level" class="text-xs text-red-600 mt-1">{{ form.errors.level }}</p>
                    </div>
                    <div class="flex flex-col justify-end gap-2">
                        <label class="flex items-center gap-2 text-xs text-gray-600">
                            <input v-model="form.is_ecz_year" type="checkbox" class="rounded" />
                            ECZ Exam Year
                        </label>
                        <div class="flex gap-2">
                            <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                Save
                            </button>
                            <button type="button" class="rounded-md border px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100" @click="showForm = false">
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Grade</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Level</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Streams</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Pupils</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">ECZ</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!grades.length">
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">No grades defined yet.</td>
                        </tr>
                        <tr v-for="grade in grades" :key="grade.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold">
                                <Link :href="route('pupils.index', { grade_id: grade.id })" class="text-indigo-700 hover:underline">
                                    {{ grade.name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="LEVEL_COLORS[grade.level] ?? 'bg-gray-100 text-gray-600'">
                                    {{ LEVEL_LABELS[grade.level] ?? grade.level }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ grade.streams_count }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ grade.pupils_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="grade.is_ecz_year" class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">ECZ</span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('grades.edit', grade.id)" class="mr-3 text-xs text-indigo-600 hover:underline">Edit</Link>
                                <button class="text-xs text-red-600 hover:underline" @click="remove(grade.id)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
