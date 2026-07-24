<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import type {
    LessonPlanAttachment,
    LessonPlanOption,
    StreamOption,
    TermOption,
} from '@/composables/useLessonPlans'

defineProps<{
    form: any
    subjects: LessonPlanOption[]
    streams: StreamOption[]
    terms: TermOption[]
    attachments?: LessonPlanAttachment[]
    processing?: boolean
}>()

const emit = defineEmits<{
    (e: 'save-draft'): void
    (e: 'submit'): void
    (e: 'remove-attachment', id: number): void
}>()

function onFiles(event: Event, form: any) {
    const target = event.target as HTMLInputElement
    form.attachments = target.files ? Array.from(target.files) : []
}
</script>

<template>
    <div class="rounded-lg border bg-white p-6 shadow-sm">
        <form class="space-y-5" @submit.prevent="emit('submit')">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Subject</label>
                    <select v-model="form.subject_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                        <option :value="null" disabled>Select subject</option>
                        <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <p v-if="form.errors.subject_id" class="mt-1 text-xs text-red-600">{{ form.errors.subject_id }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Class / Stream</label>
                    <select v-model="form.stream_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                        <option :value="null" disabled>Select class</option>
                        <option v-for="st in streams" :key="st.id" :value="st.id">{{ st.name }}</option>
                    </select>
                    <p v-if="form.errors.stream_id" class="mt-1 text-xs text-red-600">{{ form.errors.stream_id }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Term</label>
                    <select v-model="form.term_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                        <option :value="null" disabled>Select term</option>
                        <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}{{ t.is_current ? ' (current)' : '' }}</option>
                    </select>
                    <p v-if="form.errors.term_id" class="mt-1 text-xs text-red-600">{{ form.errors.term_id }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-gray-600">Title</label>
                    <input v-model="form.title" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Week no.</label>
                    <input v-model="form.week_number" type="number" min="1" max="52" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    <p v-if="form.errors.week_number" class="mt-1 text-xs text-red-600">{{ form.errors.week_number }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Lesson date</label>
                    <input v-model="form.lesson_date" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    <p v-if="form.errors.lesson_date" class="mt-1 text-xs text-red-600">{{ form.errors.lesson_date }}</p>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Learning objectives</label>
                <textarea v-model="form.objectives" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                <p v-if="form.errors.objectives" class="mt-1 text-xs text-red-600">{{ form.errors.objectives }}</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Lesson content</label>
                <textarea v-model="form.content" rows="5" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Activities <span class="text-gray-400">(optional)</span></label>
                    <textarea v-model="form.activities" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Materials <span class="text-gray-400">(optional)</span></label>
                    <textarea v-model="form.materials" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                </div>
            </div>

            <!-- Existing attachments (edit) -->
            <div v-if="attachments && attachments.length">
                <label class="mb-1 block text-xs font-medium text-gray-600">Attachments</label>
                <ul class="divide-y divide-gray-100 rounded-md border border-gray-200">
                    <li v-for="a in attachments" :key="a.id" class="flex items-center justify-between px-3 py-2 text-sm">
                        <a :href="a.url" target="_blank" class="text-indigo-600 hover:underline">{{ a.name }}</a>
                        <button type="button" class="text-xs font-medium text-red-600 hover:text-red-800"
                            @click="emit('remove-attachment', a.id)">Remove</button>
                    </li>
                </ul>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Add files <span class="text-gray-400">(worksheets, slides, PDFs — max 20MB each)</span></label>
                <input type="file" multiple class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                    @change="onFiles($event, form)" />
                <p v-if="form.errors.attachments" class="mt-1 text-xs text-red-600">{{ form.errors.attachments }}</p>
                <p v-if="form.errors['attachments.0']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments.0'] }}</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <Link :href="route('lesson-plans.index')" class="text-sm text-gray-500 hover:underline">Cancel</Link>
                <button type="button" :disabled="processing"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    @click="emit('save-draft')">
                    Save draft
                </button>
                <button type="submit" :disabled="processing"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                    Submit for approval
                </button>
            </div>
        </form>
    </div>
</template>
