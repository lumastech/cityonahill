<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

interface Subject {
    id: number
    name: string
    code: string
    category: string
    is_ecz_subject: boolean
    is_zambian_language: boolean
    description: string | null
}

const props = defineProps<{ subject: Subject }>()

const form = useForm({
    name: props.subject.name,
    code: props.subject.code,
    category: props.subject.category,
    is_ecz_subject: props.subject.is_ecz_subject,
    is_zambian_language: props.subject.is_zambian_language,
    description: props.subject.description ?? '',
})

function submit() {
    form.put(route('subjects.update', props.subject.id))
}

const CATEGORY_LABELS: Record<string, string> = {
    core: 'Core',
    elective: 'Elective',
    language: 'Language',
    vocational: 'Vocational',
    religious: 'Religious',
    physical: 'Physical',
}
</script>

<template>
    <AppLayout :title="`Edit ${subject.name}`">
        <Head :title="`Edit ${subject.name}`" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-2xl mx-auto">
            <div class="mb-6 flex items-center gap-3">
                <Link :href="route('subjects.index')" class="text-sm text-indigo-600 hover:underline">← Subjects</Link>
                <span class="text-gray-400">/</span>
                <h1 class="text-xl font-bold text-gray-900">{{ subject.name }}</h1>
            </div>

            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Subject Name</label>
                            <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Code</label>
                            <input v-model="form.code" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.code" class="text-xs text-red-600 mt-1">{{ form.errors.code }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                        <select v-model="form.category" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option v-for="(label, key) in CATEGORY_LABELS" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                        <textarea v-model="form.description" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>

                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input v-model="form.is_ecz_subject" type="checkbox" class="rounded" />
                            ECZ Subject
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input v-model="form.is_zambian_language" type="checkbox" class="rounded" />
                            Zambian Language
                        </label>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
