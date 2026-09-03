<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import PlanForm from './PlanForm.vue'
import type {
    LessonPlanFormFields,
    LessonPlanOption,
    LessonPlanStage,
    StreamOption,
    TermOption,
} from '@/composables/useLessonPlans'

const props = defineProps<{
    teacherName: string
    subjects: LessonPlanOption[]
    streams: StreamOption[]
    terms: TermOption[]
    blankStages: LessonPlanStage[]
}>()

const currentTerm = props.terms.find((t) => t.is_current)

const form = useForm<LessonPlanFormFields>({
    subject_id: null,
    stream_id: null,
    term_id: currentTerm?.id ?? null,
    topic: '',
    sub_topic: '',
    general_competence: '',
    specific_competence: '',
    lesson_goal: '',
    reference: '',
    prior_knowledge: '',
    learning_material: '',
    learning_environment: '',
    stages: props.blankStages.map((s) => ({ ...s })),
    conclusion: '',
    evaluation: '',
    week_number: null,
    lesson_date: null,
    duration_minutes: null,
    boys_count: null,
    girls_count: null,
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

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center gap-3">
                <Link :href="route('lesson-plans.index')" class="text-sm text-indigo-600 hover:underline">← Lesson Plans</Link>
                <span class="text-gray-400">/</span>
                <h1 class="text-xl font-bold text-gray-900">New Lesson Plan</h1>
            </div>

            <PlanForm
                :form="form"
                :teacher-name="teacherName"
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
