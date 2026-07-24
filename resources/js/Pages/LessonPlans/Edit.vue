<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import PlanForm from './PlanForm.vue'
import type { LessonPlan, LessonPlanOption, StreamOption, TermOption } from '@/composables/useLessonPlans'

const props = defineProps<{
    lessonPlan: LessonPlan
    subjects: LessonPlanOption[]
    streams: StreamOption[]
    terms: TermOption[]
}>()

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
    subject_id: props.lessonPlan.subject_id,
    stream_id: props.lessonPlan.stream_id,
    term_id: props.lessonPlan.term_id,
    title: props.lessonPlan.title,
    week_number: props.lessonPlan.week_number,
    lesson_date: props.lessonPlan.lesson_date ? props.lessonPlan.lesson_date.substring(0, 10) : null,
    objectives: props.lessonPlan.objectives,
    content: props.lessonPlan.content,
    activities: props.lessonPlan.activities ?? '',
    materials: props.lessonPlan.materials ?? '',
    submit: false,
    attachments: [],
})

function save(submit: boolean) {
    form.submit = submit
    form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(route('lesson-plans.update', props.lessonPlan.id), { forceFormData: true })
}

function removeAttachment(mediaId: number) {
    if (!window.confirm('Remove this attachment?')) return
    router.delete(route('lesson-plans.attachments.destroy', [props.lessonPlan.id, mediaId]), {
        preserveScroll: true,
    })
}
</script>

<template>
    <AppLayout :title="`Edit ${lessonPlan.title}`">
        <Head :title="`Edit ${lessonPlan.title}`" />

        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center gap-3">
                <Link :href="route('lesson-plans.index')" class="text-sm text-indigo-600 hover:underline">← Lesson Plans</Link>
                <span class="text-gray-400">/</span>
                <h1 class="text-xl font-bold text-gray-900">{{ lessonPlan.title }}</h1>
            </div>

            <div v-if="lessonPlan.status === 'rejected' && lessonPlan.comment"
                class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <span class="font-semibold">Reviewer feedback:</span> {{ lessonPlan.comment }}
            </div>

            <PlanForm
                :form="form"
                :subjects="subjects"
                :streams="streams"
                :terms="terms"
                :attachments="lessonPlan.attachments"
                :processing="form.processing"
                @save-draft="save(false)"
                @submit="save(true)"
                @remove-attachment="removeAttachment"
            />
        </div>
    </AppLayout>
</template>
