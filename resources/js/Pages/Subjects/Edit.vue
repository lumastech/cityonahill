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

interface ContentMedia {
    id: number
    name: string
    url: string
}

interface LearningContent {
    id: number
    title: string
    body: string | null
    grade_id: number | null
    sort_order: number
    media: ContentMedia[]
}

interface GradeOption {
    id: number
    name: string
}

const props = defineProps<{
    subject: Subject
    contents: LearningContent[]
    grades: GradeOption[]
}>()

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

// --- Learning content ---
const contentForm = useForm<{
    title: string
    body: string
    grade_id: number | null
    sort_order: number
    files: File[]
}>({
    title: '',
    body: '',
    grade_id: null,
    sort_order: 0,
    files: [],
})

function onContentFiles(event: Event) {
    const target = event.target as HTMLInputElement
    contentForm.files = target.files ? Array.from(target.files) : []
}

const fileInput = ref<HTMLInputElement | null>(null)

function addContent() {
    contentForm.post(route('subjects.contents.store', props.subject.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            contentForm.reset()
            if (fileInput.value) fileInput.value.value = ''
        },
    })
}

function deleteContent(content: LearningContent) {
    if (!window.confirm(`Delete "${content.title}" and its files?`)) return
    router.delete(route('subjects.contents.destroy', [props.subject.id, content.id]), { preserveScroll: true })
}

function deleteMedia(content: LearningContent, media: ContentMedia) {
    if (!window.confirm('Remove this file?')) return
    router.delete(route('subject-contents.media.destroy', [content.id, media.id]), { preserveScroll: true })
}

function gradeName(id: number | null): string {
    return props.grades.find((g) => g.id === id)?.name ?? 'All grades'
}
</script>

<template>
    <AppLayout :title="`Edit ${subject.name}`">
        <Head :title="`Edit ${subject.name}`" />

        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center gap-3">
                <Link :href="route('subjects.index')" class="text-sm text-indigo-600 hover:underline">← Subjects</Link>
                <span class="text-gray-400">/</span>
                <h1 class="text-xl font-bold text-gray-900">{{ subject.name }}</h1>
            </div>

            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Subject Name</label>
                            <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Code</label>
                            <input v-model="form.code" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.code" class="mt-1 text-xs text-red-600">{{ form.errors.code }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Category</label>
                        <select v-model="form.category" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option v-for="(label, key) in CATEGORY_LABELS" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Description</label>
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

            <!-- Learning content -->
            <div class="mt-8">
                <h2 class="mb-3 text-lg font-semibold text-gray-900">Learning Content & Media</h2>

                <div class="space-y-3">
                    <div v-for="content in contents" :key="content.id" class="rounded-lg border bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-medium text-gray-900">{{ content.title }}</h3>
                                <p class="text-xs text-gray-400">{{ gradeName(content.grade_id) }}</p>
                            </div>
                            <button class="text-xs font-medium text-gray-500 hover:text-red-700" @click="deleteContent(content)">Delete</button>
                        </div>
                        <p v-if="content.body" class="mt-2 whitespace-pre-line text-sm text-gray-600">{{ content.body }}</p>
                        <ul v-if="content.media.length" class="mt-3 divide-y divide-gray-100 rounded-md border border-gray-200">
                            <li v-for="m in content.media" :key="m.id" class="flex items-center justify-between px-3 py-2 text-sm">
                                <a :href="m.url" target="_blank" class="text-indigo-600 hover:underline">{{ m.name }}</a>
                                <button class="text-xs font-medium text-red-600 hover:text-red-800" @click="deleteMedia(content, m)">Remove</button>
                            </li>
                        </ul>
                    </div>

                    <p v-if="!contents.length" class="rounded-lg border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-400">
                        No learning content yet. Add topics and upload materials below.
                    </p>
                </div>

                <!-- Add content -->
                <div class="mt-4 rounded-lg border bg-white p-5 shadow-sm">
                    <h3 class="mb-3 text-sm font-semibold text-gray-800">Add content</h3>
                    <form class="space-y-3" @submit.prevent="addContent">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-gray-600">Topic / Title</label>
                                <input v-model="contentForm.title" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                                <p v-if="contentForm.errors.title" class="mt-1 text-xs text-red-600">{{ contentForm.errors.title }}</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Grade <span class="text-gray-400">(optional)</span></label>
                                <select v-model="contentForm.grade_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    <option :value="null">All grades</option>
                                    <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Content <span class="text-gray-400">(optional)</span></label>
                            <textarea v-model="contentForm.body" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Media files <span class="text-gray-400">(images, docs, slides, audio/video — max 50MB each)</span></label>
                            <input ref="fileInput" type="file" multiple class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                                @change="onContentFiles" />
                            <p v-if="contentForm.errors.files" class="mt-1 text-xs text-red-600">{{ contentForm.errors.files }}</p>
                            <p v-if="contentForm.errors['files.0']" class="mt-1 text-xs text-red-600">{{ contentForm.errors['files.0'] }}</p>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="contentForm.processing"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                Add content
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
