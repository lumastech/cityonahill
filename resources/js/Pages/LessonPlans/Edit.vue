<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import PlanForm from './PlanForm.vue'
import type {
    LessonPlan,
    LessonPlanFormFields,
    LessonPlanOption,
    LessonPlanStage,
    StreamOption,
    TermOption,
} from '@/composables/useLessonPlans'

const props = defineProps<{
    lessonPlan: LessonPlan
    teacherName: string | null
    subjects: LessonPlanOption[]
    streams: StreamOption[]
    terms: TermOption[]
    blankStages: LessonPlanStage[]
}>()

const savedStages = props.lessonPlan.stages?.length ? props.lessonPlan.stages : props.blankStages

const form = useForm<LessonPlanFormFields>({
    subject_id: props.lessonPlan.subject_id,
    stream_id: props.lessonPlan.stream_id,
    term_id: props.lessonPlan.term_id,
    topic: props.lessonPlan.topic,
    sub_topic: props.lessonPlan.sub_topic ?? '',
    general_competence: props.lessonPlan.general_competence ?? '',
    specific_competence: props.lessonPlan.specific_competence ?? '',
    lesson_goal: props.lessonPlan.lesson_goal,
    reference: props.lessonPlan.reference ?? '',
    prior_knowledge: props.lessonPlan.prior_knowledge ?? '',
    learning_material: props.lessonPlan.learning_material ?? '',
    learning_environment: props.lessonPlan.learning_environment ?? '',
    stages: savedStages.map((s) => ({
        stage: s.stage,
        teacher_activity: s.teacher_activity ?? '',
        learner_activity: s.learner_activity ?? '',
        assessment_criteria: s.assessment_criteria ?? '',
    })),
    conclusion: props.lessonPlan.conclusion ?? '',
    evaluation: props.lessonPlan.evaluation ?? '',
    week_number: props.lessonPlan.week_number,
    lesson_date: props.lessonPlan.lesson_date ? props.lessonPlan.lesson_date.substring(0, 10) : null,
    duration_minutes: props.lessonPlan.duration_minutes,
    boys_count: props.lessonPlan.boys_count,
    girls_count: props.lessonPlan.girls_count,
    attachments: [],
})

// `submit` is sent through transform rather than held as a form field: a field of
// that name would shadow Inertia's own form.submit() method.
function save(submitForApproval: boolean) {
    form
        .transform((data) => ({ ...data, submit: submitForApproval, _method: 'put' }))
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
    <AppLayout :title="`Edit ${lessonPlan.topic}`">
        <Head :title="`Edit ${lessonPlan.topic}`" />

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center gap-3">
                <Link :href="route('lesson-plans.index')" class="text-sm text-indigo-600 hover:underline">← Lesson Plans</Link>
                <span class="text-gray-400">/</span>
                <h1 class="text-xl font-bold text-gray-900">{{ lessonPlan.topic }}</h1>
            </div>

            <div v-if="lessonPlan.reject_reason"
                class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <span class="font-semibold">Reason for rejection:</span>
                <span class="whitespace-pre-line">{{ lessonPlan.reject_reason }}</span>
            </div>

            <div v-if="lessonPlan.revert_reason"
                class="mb-5 rounded-md border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-900">
                <span class="font-semibold">Returned for changes:</span>
                <span class="whitespace-pre-line">{{ lessonPlan.revert_reason }}</span>
            </div>

            <PlanForm
                :form="form"
                :teacher-name="teacherName"
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
