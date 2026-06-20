<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

interface GradeSubject {
    id: number
    subject: { id: number; name: string; code: string }
    is_core: boolean
    ca_weight: number
    exam_weight: number
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

interface Subject { id: number; name: string; code: string }

const props = defineProps<{
    grade: Grade
    available_subjects: Subject[]
}>()

const detailForm = useForm({
    name:         props.grade.name,
    grade_number: props.grade.grade_number,
    level:        props.grade.level,
    is_ecz_year:  props.grade.is_ecz_year,
    order_index:  props.grade.order_index,
})

function saveDetails() {
    detailForm.put(route('grades.update', props.grade.id))
}

const linkForm = useForm({
    grade_id:    props.grade.id,
    subject_id:  null as number | null,
    is_core:     true,
    ca_weight:   40,
    exam_weight: 60,
})

function linkSubject() {
    linkForm.post(route('grades.subjects.link', props.grade.id), {
        onSuccess: () => { linkForm.reset('subject_id') },
    })
}

function syncWeights() {
    linkForm.exam_weight = 100 - linkForm.ca_weight
}

function unlinkSubject(gradeSubjectId: number) {
    if (confirm('Remove this subject from the grade?')) {
        useForm({}).delete(route('grades.subjects.unlink', [props.grade.id, gradeSubjectId]))
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

            <!-- Grade details form -->
            <div class="mb-6 rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-700">Grade Details</h2>
                <form class="grid grid-cols-2 gap-4" @submit.prevent="saveDetails">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                        <input v-model="detailForm.name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                        <p v-if="detailForm.errors.name" class="text-xs text-red-600 mt-1">{{ detailForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Grade Number</label>
                        <input v-model="detailForm.grade_number" type="number" min="1" max="12" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Level</label>
                        <select v-model="detailForm.level" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="primary">Primary</option>
                            <option value="junior_secondary">Junior Secondary</option>
                            <option value="senior_secondary">Senior Secondary</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Sort Order</label>
                        <input v-model="detailForm.order_index" type="number" min="0" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="col-span-2 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input v-model="detailForm.is_ecz_year" type="checkbox" class="rounded" />
                            ECZ Exam Year
                        </label>
                        <button type="submit" :disabled="detailForm.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Linked subjects -->
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-700">
                    Linked Subjects ({{ grade.grade_subjects.length }})
                </h2>

                <!-- Current subjects -->
                <div v-if="grade.grade_subjects.length" class="mb-5 divide-y divide-gray-100 rounded-md border border-gray-200">
                    <div v-for="gs in grade.grade_subjects" :key="gs.id" class="flex items-center justify-between px-4 py-2.5">
                        <div class="flex items-center gap-3">
                            <span class="font-medium text-gray-800 text-sm">{{ gs.subject.name }}</span>
                            <span class="text-xs text-gray-400">{{ gs.subject.code }}</span>
                            <span v-if="gs.is_core" class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700">Core</span>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <span>CA {{ gs.ca_weight }}% / Exam {{ gs.exam_weight }}%</span>
                            <button class="text-red-500 hover:text-red-700" @click="unlinkSubject(gs.id)">Remove</button>
                        </div>
                    </div>
                </div>
                <p v-else class="mb-5 text-sm text-gray-400">No subjects linked yet.</p>

                <!-- Add subject form -->
                <div v-if="available_subjects.length" class="rounded-md border border-dashed border-gray-300 p-4">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Link a Subject</h3>
                    <form class="space-y-3" @submit.prevent="linkSubject">
                        <div>
                            <select v-model="linkForm.subject_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                                <option :value="null">Select subject…</option>
                                <option v-for="s in available_subjects" :key="s.id" :value="s.id">
                                    {{ s.name }} ({{ s.code }})
                                </option>
                            </select>
                            <p v-if="linkForm.errors.subject_id" class="mt-1 text-xs text-red-600">{{ linkForm.errors.subject_id }}</p>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-600 mb-1">CA Weight (%)</label>
                                <input
                                    v-model.number="linkForm.ca_weight"
                                    type="number" min="0" max="100"
                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                                    @input="syncWeights"
                                />
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Exam Weight (%)</label>
                                <input
                                    v-model.number="linkForm.exam_weight"
                                    type="number" min="0" max="100"
                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                                    readonly
                                />
                            </div>
                            <div class="pt-5">
                                <label class="flex items-center gap-1.5 text-sm text-gray-600 cursor-pointer">
                                    <input v-model="linkForm.is_core" type="checkbox" class="rounded" />
                                    Core
                                </label>
                            </div>
                        </div>
                        <p v-if="linkForm.errors.ca_weight" class="text-xs text-red-600">{{ linkForm.errors.ca_weight }}</p>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="linkForm.processing || !linkForm.subject_id"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Link Subject
                            </button>
                        </div>
                    </form>
                </div>
                <p v-else class="text-xs text-gray-400 italic">All available subjects are already linked.</p>
            </div>
        </div>
    </AppLayout>
</template>
