<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { Assessment } from '@/types/results'
import type { PaginatedResponse } from '@/types/shared'
import { useAssessments } from '@/composables/useAssessments'

interface Stream { id: number; name: string; grade?: { id: number; name: string } }
interface Subject { id: number; name: string; code: string }
interface Term { id: number; name: string; number: number }

const props = defineProps<{
    assessments: PaginatedResponse<Assessment>
    streams: Stream[]
    subjects: Subject[]
    terms: Term[]
    filters: { stream_id?: string; subject_id?: string; term_id?: string; type?: string }
}>()

const { typeLabel, typeColor } = useAssessments()

const selectedStream = ref(props.filters.stream_id ?? '')
const selectedSubject = ref(props.filters.subject_id ?? '')
const selectedTerm = ref(props.filters.term_id ?? '')
const selectedType = ref(props.filters.type ?? '')

function applyFilters() {
    router.get(route('assessments.index'), {
        stream_id: selectedStream.value || undefined,
        subject_id: selectedSubject.value || undefined,
        term_id: selectedTerm.value || undefined,
        type: selectedType.value || undefined,
    }, { preserveState: true, replace: true })
}

const TYPES = ['ca_test', 'assignment', 'practical', 'mid_term', 'end_of_term'] as const

const showForm = ref(false)

const form = useForm({
    stream_id: '',
    subject_id: '',
    term_id: '',
    name: '',
    type: 'ca_test',
    max_marks: 100,
    weight_percent: 10,
    date: new Date().toISOString().slice(0, 10),
    instructions: '',
})

function submitAssessment() {
    form.post(route('assessments.store'), {
        onSuccess: () => { form.reset(); showForm.value = false },
    })
}
</script>

<template>
    <AppLayout>
        <Head title="Assessments" />

        <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-bold text-gray-900">Assessments</h1>
                <button
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="showForm = !showForm"
                >
                    + New Assessment
                </button>
            </div>

            <!-- Create form -->
            <div v-if="showForm" class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-5">
                <h2 class="mb-4 text-sm font-semibold text-indigo-800">New Assessment</h2>
                <form class="grid grid-cols-2 gap-4 sm:grid-cols-3" @submit.prevent="submitAssessment">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                        <input v-model="form.name" type="text" placeholder="e.g. CA Test 1" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                        <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                        <select v-model="form.type" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option v-for="t in TYPES" :key="t" :value="t">{{ typeLabel(t) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Class</label>
                        <select v-model="form.stream_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                            <option value="">Select…</option>
                            <option v-for="s in streams" :key="s.id" :value="s.id">{{ s.grade?.name }} {{ s.name }}</option>
                        </select>
                        <p v-if="form.errors.stream_id" class="text-xs text-red-600 mt-1">{{ form.errors.stream_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Subject</label>
                        <select v-model="form.subject_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                            <option value="">Select…</option>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <p v-if="form.errors.subject_id" class="text-xs text-red-600 mt-1">{{ form.errors.subject_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Term</label>
                        <select v-model="form.term_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                            <option value="">Select…</option>
                            <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <p v-if="form.errors.term_id" class="text-xs text-red-600 mt-1">{{ form.errors.term_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                        <input v-model="form.date" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Max Marks</label>
                        <input v-model="form.max_marks" type="number" min="1" max="1000" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Weight (%)</label>
                        <input v-model="form.weight_percent" type="number" min="1" max="100" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                    </div>
                    <div class="col-span-2 sm:col-span-3 flex gap-2 justify-end">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Create Assessment
                        </button>
                        <button type="button" class="rounded-md border px-4 py-1.5 text-sm text-gray-600 hover:bg-gray-100" @click="showForm = false">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filters -->
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <select v-model="selectedStream" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilters">
                    <option value="">All Classes</option>
                    <option v-for="s in streams" :key="s.id" :value="s.id">{{ s.grade?.name }} {{ s.name }}</option>
                </select>
                <select v-model="selectedSubject" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilters">
                    <option value="">All Subjects</option>
                    <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <select v-model="selectedTerm" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilters">
                    <option value="">All Terms</option>
                    <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
                <select v-model="selectedType" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilters">
                    <option value="">All Types</option>
                    <option v-for="tp in TYPES" :key="tp" :value="tp">{{ typeLabel(tp) }}</option>
                </select>
            </div>

            <!-- Cards -->
            <div v-if="assessments.data.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="a in assessments.data"
                    :key="a.id"
                    class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-gray-900">{{ a.name }}</p>
                            <p class="text-xs text-gray-500">{{ a.subject?.name }} · {{ a.stream?.name }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="typeColor(a.type)">
                            {{ typeLabel(a.type) }}
                        </span>
                    </div>
                    <div class="mb-4 flex gap-4 text-sm text-gray-600">
                        <span>{{ a.date }}</span>
                        <span>Max: {{ a.max_marks }}</span>
                        <span>Scores: {{ a.scores_count ?? 0 }}</span>
                    </div>
                    <a
                        :href="route('assessments.show', a.id)"
                        class="block w-full rounded-md border border-indigo-300 py-1.5 text-center text-sm font-medium text-indigo-700 hover:bg-indigo-50"
                    >
                        Enter Scores
                    </a>
                </div>
            </div>

            <div v-else class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center text-gray-500">
                No assessments found. Adjust filters or create one.
            </div>
        </div>
    </AppLayout>
</template>
