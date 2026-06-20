<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Subject {
    id: number
    name: string
    code: string
    category: string
    is_ecz_subject: boolean
    is_zambian_language: boolean
    description: string | null
}

const props = defineProps<{
    subjects: Subject[]
    filterCategory: string | null
}>()

const showForm = ref(false)
const selectedCategory = ref(props.filterCategory ?? '')

const form = useForm({
    name: '',
    code: '',
    category: 'core',
    is_ecz_subject: false,
    is_zambian_language: false,
    description: '',
})

function submit() {
    form.post(route('subjects.store'), {
        onSuccess: () => { form.reset(); showForm.value = false },
    })
}

function remove(id: number) {
    if (confirm('Delete this subject?')) {
        useForm({}).delete(route('subjects.destroy', id))
    }
}

function applyFilter() {
    router.get(route('subjects.index'), { category: selectedCategory.value || undefined }, { preserveState: true, replace: true })
}

const CATEGORY_LABELS: Record<string, string> = {
    core: 'Core',
    elective: 'Elective',
    language: 'Language',
    vocational: 'Vocational',
    religious: 'Religious',
    physical: 'Physical',
}

const CATEGORY_COLORS: Record<string, string> = {
    core: 'bg-blue-100 text-blue-700',
    elective: 'bg-purple-100 text-purple-700',
    language: 'bg-green-100 text-green-700',
    vocational: 'bg-orange-100 text-orange-700',
    religious: 'bg-yellow-100 text-yellow-700',
    physical: 'bg-red-100 text-red-700',
}
</script>

<template>
    <AppLayout title="Subjects">
        <Head title="Subjects" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Subjects</h1>
                <button
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="showForm = !showForm"
                >
                    + Add Subject
                </button>
            </div>

            <!-- Add form -->
            <div v-if="showForm" class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-5">
                <h2 class="mb-4 text-sm font-semibold text-indigo-800">New Subject</h2>
                <form class="grid grid-cols-2 gap-4 sm:grid-cols-3" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                        <input v-model="form.name" type="text" placeholder="e.g. Mathematics" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                        <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Code</label>
                        <input v-model="form.code" type="text" placeholder="e.g. MATH" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                        <select v-model="form.category" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option v-for="(label, key) in CATEGORY_LABELS" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div class="col-span-2 sm:col-span-3 flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2 text-xs text-gray-600">
                            <input v-model="form.is_ecz_subject" type="checkbox" class="rounded" />
                            ECZ Subject
                        </label>
                        <label class="flex items-center gap-2 text-xs text-gray-600">
                            <input v-model="form.is_zambian_language" type="checkbox" class="rounded" />
                            Zambian Language
                        </label>
                        <div class="ml-auto flex gap-2">
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

            <!-- Filter -->
            <div class="mb-4 flex items-center gap-3">
                <select v-model="selectedCategory" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyFilter">
                    <option value="">All Categories</option>
                    <option v-for="(label, key) in CATEGORY_LABELS" :key="key" :value="key">{{ label }}</option>
                </select>
                <span class="text-sm text-gray-500">{{ subjects.length }} subject{{ subjects.length === 1 ? '' : 's' }}</span>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Subject</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Code</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Category</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">ECZ</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Zam. Lang.</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!subjects.length">
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">No subjects found.</td>
                        </tr>
                        <tr v-for="subject in subjects" :key="subject.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ subject.name }}</td>
                            <td class="px-4 py-3 font-mono text-gray-600">{{ subject.code }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="CATEGORY_COLORS[subject.category] ?? 'bg-gray-100 text-gray-600'">
                                    {{ CATEGORY_LABELS[subject.category] ?? subject.category }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="subject.is_ecz_subject" class="text-green-600">✓</span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="subject.is_zambian_language" class="text-green-600">✓</span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('subjects.edit', subject.id)" class="mr-3 text-xs text-indigo-600 hover:underline">Edit</Link>
                                <button class="text-xs text-red-600 hover:underline" @click="remove(subject.id)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
