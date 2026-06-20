<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps<{
    grades: { id: number; name: string }[]
}>()

const form = useForm({
    title: '',
    content: '',
    target_audience: 'all',
    target_grade_id: null as number | null,
    published_at: null as string | null,
    expires_at: null as string | null,
})

const publishNow = ref(false)

const audiences = [
    { value: 'all', label: 'Everyone' },
    { value: 'parents', label: 'Parents' },
    { value: 'staff', label: 'Staff' },
    { value: 'pupils', label: 'Pupils' },
    { value: 'grade', label: 'Specific Grade' },
]

const showGradeSelect = computed(() => form.target_audience === 'grade')

function submit() {
    if (publishNow.value) {
        form.published_at = new Date().toISOString()
    }
    form.post(route('notices.store'))
}
</script>

<template>
    <AppLayout>
    <div class="mx-auto max-w-2xl p-6">
        <h1 class="mb-6 text-2xl font-bold text-gray-900">Create Notice</h1>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Title</label>
                <input
                    v-model="form.title"
                    type="text"
                    maxlength="200"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Content</label>
                <textarea
                    v-model="form.content"
                    rows="6"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Audience</label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="aud in audiences"
                        :key="aud.value"
                        type="button"
                        @click="form.target_audience = aud.value; if (aud.value !== 'grade') form.target_grade_id = null"
                        :class="[
                            'rounded-full border px-3 py-1 text-sm font-medium transition',
                            form.target_audience === aud.value
                                ? 'border-indigo-600 bg-indigo-600 text-white'
                                : 'border-gray-300 text-gray-600 hover:border-indigo-400'
                        ]"
                    >
                        {{ aud.label }}
                    </button>
                </div>
            </div>

            <div v-if="showGradeSelect">
                <label class="mb-1 block text-sm font-medium text-gray-700">Grade</label>
                <select
                    v-model="form.target_grade_id"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option :value="null">-- Select Grade --</option>
                    <option v-for="grade in grades" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Expires At (optional)</label>
                    <input
                        v-model="form.expires_at"
                        type="datetime-local"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input id="publish-now" v-model="publishNow" type="checkbox" class="rounded border-gray-300 text-indigo-600" />
                <label for="publish-now" class="text-sm font-medium text-gray-700">Publish immediately</label>
            </div>

            <div class="flex gap-3">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    {{ publishNow ? 'Create & Publish' : 'Save as Draft' }}
                </button>
                <a :href="route('notices.index')" class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
    </AppLayout>
</template>
