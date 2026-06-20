<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

interface GradeSubject {
    id: number
    subject: { id: number; name: string; code: string }
}

interface Grade {
    id: number
    name: string
    grade_number: number
    level: string
    is_ecz_year: boolean
    order_index: number
    grade_subjects: GradeSubject[]
}

const props = defineProps<{ grade: Grade }>()

const form = useForm({
    name: props.grade.name,
    grade_number: props.grade.grade_number,
    level: props.grade.level,
    is_ecz_year: props.grade.is_ecz_year,
    order_index: props.grade.order_index,
})

function submit() {
    form.put(route('grades.update', props.grade.id))
}

function unlinkSubject(gradeSubjectId: number) {
    if (confirm('Remove this subject from the grade?')) {
        useForm({}).delete(route('grades.subjects.link', props.grade.id), {
            data: { grade_subject_id: gradeSubjectId },
        })
    }
}
</script>

<template>
    <AppLayout :title="`Edit ${grade.name}`">
        <Head :title="`Edit ${grade.name}`" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
            <div class="mb-6 flex items-center gap-3">
                <Link :href="route('grades.index')" class="text-sm text-indigo-600 hover:underline">← Grades</Link>
                <span class="text-gray-400">/</span>
                <h1 class="text-xl font-bold text-gray-900">{{ grade.name }}</h1>
            </div>

            <!-- Grade form -->
            <div class="mb-8 rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-700">Grade Details</h2>
                <form class="grid grid-cols-2 gap-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                        <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                        <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Grade Number</label>
                        <input v-model="form.grade_number" type="number" min="1" max="12" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Level</label>
                        <select v-model="form.level" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="primary">Primary</option>
                            <option value="junior_secondary">Junior Secondary</option>
                            <option value="senior_secondary">Senior Secondary</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Sort Order</label>
                        <input v-model="form.order_index" type="number" min="0" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="col-span-2 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input v-model="form.is_ecz_year" type="checkbox" class="rounded" />
                            ECZ Exam Year
                        </label>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Linked subjects -->
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-700">Linked Subjects ({{ grade.grade_subjects.length }})</h2>
                <div v-if="grade.grade_subjects.length" class="divide-y divide-gray-100">
                    <div v-for="gs in grade.grade_subjects" :key="gs.id" class="flex items-center justify-between py-2">
                        <div>
                            <span class="font-medium text-gray-800">{{ gs.subject.name }}</span>
                            <span class="ml-2 text-xs text-gray-400">{{ gs.subject.code }}</span>
                        </div>
                        <button class="text-xs text-red-600 hover:underline" @click="unlinkSubject(gs.id)">Remove</button>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-400">No subjects linked to this grade yet.</p>

                <div class="mt-4">
                    <Link
                        :href="route('subjects.index')"
                        class="text-sm text-indigo-600 hover:underline"
                    >
                        Manage subjects →
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
