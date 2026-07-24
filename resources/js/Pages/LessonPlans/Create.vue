<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import PlanForm from './PlanForm.vue'
import type { LessonPlanOption, StreamOption, TermOption } from '@/composables/useLessonPlans'

const props = defineProps<{
    subjects: LessonPlanOption[]
    streams: StreamOption[]
    terms: TermOption[]
}>()

const currentTerm = props.terms.find((t) => t.is_current)

const form = useForm<{
    subject_id: number | null
    stream_id: number | null
    term_id: number | null
    title: string
    week_number: number | null
    lesson_date: string | null
    objectives: string
    content: string
    activities: string
    materials: string
    submit: boolean
    attachments: File[]
}>({
    subject_id: null,
    stream_id: null,
    term_id: currentTerm?.id ?? null,
    title: '',
    week_number: null,
    lesson_date: null,
    objectives: '',
    content: '',
    activities: '',
    materials: '',
    submit: false,
    attachments: [],
})

function save(submit: boolean) {
    form.submit = submit
    form.post(route('lesson-plans.store'), { forceFormData: true })
}
</script>

<template>
    <AppLayout title="New Lesson Plan">
        <Head title="New Lesson Plan" />

        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center gap-3">
                <Link :href="route('lesson-plans.index')" class="text-sm text-indigo-600 hover:underline">← Lesson Plans</Link>
                <span class="text-gray-400">/</span>
                <h1 class="text-xl font-bold text-gray-900">New Lesson Plan</h1>
            </div>

            <PlanForm
                :form="form"
                :subjects="subjects"
                :streams="streams"
                :terms="terms"
                :processing="form.processing"
                @save-draft="save(false)"
                @submit="save(true)"
            />
        </div>
    </AppLayout>
</template>
