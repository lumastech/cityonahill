<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { EczCandidate } from '@/types/ecz'

const props = defineProps<{
    candidate: EczCandidate
    subjects: { id: number; name: string; code: string }[]
}>()

const indexForm = useForm({
    action: 'update_index',
    index_number: props.candidate.index_number ?? '',
    centre_number: props.candidate.centre_number ?? '',
})

const subjectForm = useForm({
    action: 'add_subjects',
    subject_id: '',
    predicted_grade: '',
})

function submitIndex() {
    indexForm.put(route('ecz-candidates.update', props.candidate.id))
}

function addSubject() {
    subjectForm.put(route('ecz-candidates.update', props.candidate.id), {
        onSuccess: () => subjectForm.reset(),
    })
}

const gradeOptions = ['A', 'B', 'C', 'D', 'F']

const statusBadge: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    submitted: 'bg-blue-100 text-blue-800',
    confirmed: 'bg-green-100 text-green-800',
    withdrawn: 'bg-red-100 text-red-800',
}
</script>

<template>
    <AppLayout>
    <Head :title="`${candidate.pupil?.first_name} ${candidate.pupil?.last_name} — ECZ`" />
    <div class="py-6">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        {{ candidate.pupil?.first_name }} {{ candidate.pupil?.last_name }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Adm: {{ candidate.pupil?.admission_no }} · Grade {{ candidate.grade_level }} · {{ candidate.exam_year }}
                    </p>
                </div>
                <span :class="['rounded-full px-3 py-1 text-sm font-medium', statusBadge[candidate.registration_status] ?? 'bg-gray-100']">
                    {{ candidate.registration_status }}
                </span>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Index number card -->
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 font-medium text-gray-900">Exam Registration</h2>
                    <form @submit.prevent="submitIndex">
                        <div class="mb-3">
                            <label class="block text-sm text-gray-700">Index Number</label>
                            <input v-model="indexForm.index_number" type="text" maxlength="30" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="indexForm.errors.index_number" class="mt-1 text-xs text-red-600">{{ indexForm.errors.index_number }}</p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm text-gray-700">Centre Number</label>
                            <input v-model="indexForm.centre_number" type="text" maxlength="20" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="indexForm.errors.centre_number" class="mt-1 text-xs text-red-600">{{ indexForm.errors.centre_number }}</p>
                        </div>
                        <button type="submit" :disabled="indexForm.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save
                        </button>
                    </form>
                </div>

                <!-- Add subject card -->
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 font-medium text-gray-900">Add Subject Entry</h2>
                    <form @submit.prevent="addSubject">
                        <div class="mb-3">
                            <label class="block text-sm text-gray-700">Subject</label>
                            <select v-model="subjectForm.subject_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Select subject…</option>
                                <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm text-gray-700">Predicted Grade</label>
                            <select v-model="subjectForm.predicted_grade" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">—</option>
                                <option v-for="g in gradeOptions" :key="g" :value="g">{{ g }}</option>
                            </select>
                        </div>
                        <button type="submit" :disabled="subjectForm.processing || !subjectForm.subject_id" class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">
                            Add
                        </button>
                    </form>
                </div>
            </div>

            <!-- Subject entries table -->
            <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Subject</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Predicted</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Actual Grade</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Points</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="entry in candidate.subject_entries" :key="entry.id">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ entry.subject?.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ entry.predicted_grade ?? '—' }}</td>
                            <td class="px-4 py-3 font-semibold">{{ entry.actual_grade ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ entry.actual_points ?? '—' }}</td>
                        </tr>
                        <tr v-if="!candidate.subject_entries?.length">
                            <td colspan="4" class="px-4 py-6 text-center text-gray-400">No subject entries yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Division summary -->
            <div v-if="candidate.division" class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm">
                <span class="font-medium text-green-800">Final Division: {{ candidate.division }}</span>
                <span class="ml-4 text-green-700">Total Points: {{ candidate.total_points }}</span>
            </div>
            <div v-else-if="candidate.predicted_division && candidate.predicted_division !== '—'" class="mt-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm">
                <span class="font-medium text-yellow-800">Predicted Division: {{ candidate.predicted_division }}</span>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
