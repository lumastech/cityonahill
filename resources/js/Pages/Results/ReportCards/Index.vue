<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Stream { id: number; name: string; grade?: { id: number; name: string } }
interface Term { id: number; name: string; number: number }
interface ReportCard {
    id: number
    pupil_id: number
    published: boolean
    pupil?: { id: number; first_name: string; last_name: string; admission_no: string }
}

const props = defineProps<{
    streams: Stream[]
    terms: Term[]
    cards: ReportCard[] | null
    filters: { stream_id?: string; term_id?: string }
}>()

const selectedStream = ref(props.filters.stream_id ?? '')
const selectedTerm = ref(props.filters.term_id ?? '')

function applyFilters() {
    router.get(route('report-cards.index'), {
        stream_id: selectedStream.value || undefined,
        term_id: selectedTerm.value || undefined,
    }, { preserveState: true, replace: true })
}

const generateForm = useForm({
    stream_id: props.filters.stream_id ?? '',
    term_id: props.filters.term_id ?? '',
})

function generate() {
    generateForm.stream_id = selectedStream.value as string
    generateForm.term_id = selectedTerm.value as string
    generateForm.post(route('report-cards.store'))
}

const publishForm = useForm({
    stream_id: props.filters.stream_id ?? '',
    term_id: props.filters.term_id ?? '',
})

function publish() {
    if (!confirm('Publish all report cards for this class/term? This makes them visible to parents.')) return
    publishForm.stream_id = selectedStream.value as string
    publishForm.term_id = selectedTerm.value as string
    publishForm.post(route('report-cards.publish'))
}
</script>

<template>
    <AppLayout title="Report Cards">
        <Head title="Report Cards" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">
            <h1 class="mb-6 text-2xl font-bold text-gray-900">Report Cards</h1>

            <!-- Filters + actions -->
            <div class="mb-6 flex flex-wrap items-end gap-4 rounded-lg border bg-white p-4 shadow-sm">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Class / Stream</label>
                    <select v-model="selectedStream" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilters">
                        <option value="">Select class…</option>
                        <option v-for="s in streams" :key="s.id" :value="s.id">{{ s.grade?.name }} {{ s.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Term</label>
                    <select v-model="selectedTerm" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilters">
                        <option value="">Select term…</option>
                        <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                </div>
                <div class="ml-auto flex gap-2">
                    <button
                        :disabled="!selectedStream || !selectedTerm || generateForm.processing"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-40"
                        @click="generate"
                    >
                        Generate Cards
                    </button>
                    <button
                        :disabled="!cards || !cards.length || publishForm.processing"
                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-40"
                        @click="publish"
                    >
                        Publish All
                    </button>
                </div>
            </div>

            <!-- Cards table -->
            <div v-if="cards !== null" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Pupil</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Admission No</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Published</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!cards.length">
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400">
                                No report cards generated yet. Click "Generate Cards" above.
                            </td>
                        </tr>
                        <tr v-for="card in cards" :key="card.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <Link v-if="card.pupil" :href="route('pupils.show', card.pupil.id)" class="hover:underline text-indigo-700">
                                    {{ card.pupil.first_name }} {{ card.pupil.last_name }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-600">{{ card.pupil?.admission_no }}</td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="card.published" class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Published</span>
                                <span v-else class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Draft</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('report-cards.show', card.id)" class="text-xs text-indigo-600 hover:underline">View</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="rounded-lg border-2 border-dashed border-gray-200 p-16 text-center text-gray-400">
                Select a class and term above to view or generate report cards.
            </div>
        </div>
    </AppLayout>
</template>
