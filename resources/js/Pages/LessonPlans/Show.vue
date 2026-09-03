<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import {
    LESSON_PLAN_DECISION_UI,
    LESSON_PLAN_STATUS_COLOR,
    LESSON_PLAN_STATUS_LABEL,
    streamLabel,
    type LessonPlan,
    type LessonPlanDecision,
} from '@/composables/useLessonPlans'
import { fmtDate, fmtDateTime } from '@/utils/date'

const props = defineProps<{
    lessonPlan: LessonPlan
    canReview: boolean
    canRevert: boolean
    canEdit: boolean
}>()

/** Header fields, in the order they appear on the paper CBC form. */
const headerFields = computed(() => [
    ['General competence', props.lessonPlan.general_competence],
    ['Specific competence', props.lessonPlan.specific_competence],
    ['Lesson goal', props.lessonPlan.lesson_goal],
    ['Reference', props.lessonPlan.reference],
    ['Prior knowledge', props.lessonPlan.prior_knowledge],
    ['Learning material', props.lessonPlan.learning_material],
    ['Learning environment', props.lessonPlan.learning_environment],
] as [string, string | null][])

const stages = computed(() => props.lessonPlan.stages ?? [])

/**
 * The outcomes recorded on the plan, newest first. A reverted plan still shows the
 * decision that was withdrawn, so the teacher can see what changed and why.
 */
const decisions = computed(() => {
    const plan = props.lessonPlan
    const out: { tone: string; label: string; text: string; who?: string | null; when: string | null }[] = []

    if (plan.revert_reason) {
        out.push({
            tone: 'border-orange-200 bg-orange-50 text-orange-900',
            label: 'Returned for changes',
            text: plan.revert_reason,
            who: plan.reverter?.name,
            when: plan.reverted_at,
        })
    }

    if (plan.reject_reason) {
        out.push({
            tone: 'border-red-200 bg-red-50 text-red-900',
            label: 'Rejected',
            text: plan.reject_reason,
            who: plan.reviewer?.name,
            when: plan.reviewed_at,
        })
    }

    if (plan.comment) {
        out.push({
            tone: 'border-green-200 bg-green-50 text-green-900',
            label: "Reviewer's note",
            text: plan.comment,
            who: plan.reviewer?.name,
            when: plan.reviewed_at,
        })
    }

    return out
})

function printPlan() {
    window.print()
}

function fmtSize(bytes?: number): string {
    if (!bytes) return ''
    const kb = bytes / 1024
    return kb < 1024 ? `${Math.round(kb)} KB` : `${(kb / 1024).toFixed(1)} MB`
}

// Review — the same approve/reject decision offered on the index, so a reviewer can
// read the plan and act on it without going back. `revert` withdraws a decision that
// has already been made and hands the plan back to its author.
const deciding = ref<LessonPlanDecision | null>(null)

const reviewForm = useForm({ status: 'approved', comment: '', reject_reason: '' })
const revertForm = useForm({ revert_reason: '' })

const activeForm = computed(() => (deciding.value === 'reverted' ? revertForm : reviewForm))

function openDecision(decision: LessonPlanDecision) {
    deciding.value = decision
    reviewForm.reset()
    revertForm.reset()
    reviewForm.clearErrors()
    revertForm.clearErrors()

    if (decision !== 'reverted') reviewForm.status = decision
}

/** The one field each decision writes, so the modal keeps a single textarea. */
const reasonText = computed({
    get: () => {
        if (deciding.value === 'reverted') return revertForm.revert_reason
        return deciding.value === 'rejected' ? reviewForm.reject_reason : reviewForm.comment
    },
    set: (value: string) => {
        if (deciding.value === 'reverted') revertForm.revert_reason = value
        else if (deciding.value === 'rejected') reviewForm.reject_reason = value
        else reviewForm.comment = value
    },
})

const reasonError = computed(() => {
    if (deciding.value === 'reverted') return revertForm.errors.revert_reason
    return deciding.value === 'rejected' ? reviewForm.errors.reject_reason : reviewForm.errors.comment
})

function submitDecision() {
    const options = {
        preserveScroll: true,
        onSuccess: () => { deciding.value = null },
    }

    if (deciding.value === 'reverted') {
        revertForm.post(route('lesson-plans.revert', props.lessonPlan.id), options)
    } else {
        reviewForm.post(route('lesson-plans.review', props.lessonPlan.id), options)
    }
}
</script>

<template>
    <AppLayout :title="lessonPlan.topic">
        <Head :title="lessonPlan.topic" />

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8 print:max-w-none print:p-0">

            <div class="mb-6 flex flex-wrap items-start gap-x-3 gap-y-2">
                <div class="min-w-0 flex-1">
                    <Link :href="route('lesson-plans.index')" class="text-sm text-indigo-600 hover:underline print:hidden">← Lesson Plans</Link>
                    <h1 class="mt-1 text-xl font-bold text-gray-900">{{ lessonPlan.topic }}</h1>
                    <p v-if="lessonPlan.sub_topic" class="text-sm text-gray-500">{{ lessonPlan.sub_topic }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <span :class="['rounded-full px-2.5 py-1 text-xs font-medium', LESSON_PLAN_STATUS_COLOR[lessonPlan.status]]">
                        {{ LESSON_PLAN_STATUS_LABEL[lessonPlan.status] }}
                    </span>
                    <button class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 print:hidden"
                        @click="printPlan">Print</button>
                    <a :href="route('lesson-plans.pdf', lessonPlan.id)"
                        class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 print:hidden">Download PDF</a>
                    <Link v-if="canEdit" :href="route('lesson-plans.edit', lessonPlan.id)"
                        class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 print:hidden">Edit</Link>
                </div>
            </div>

            <!-- Review outcomes, newest first -->
            <div v-if="decisions.length" class="mb-5 space-y-3">
                <div v-for="d in decisions" :key="d.label"
                    :class="['print-keep rounded-md border px-4 py-3 text-sm', d.tone]">
                    <span class="font-semibold">{{ d.label }}:</span>
                    <span class="whitespace-pre-line">{{ d.text }}</span>
                    <span class="mt-0.5 block text-xs opacity-75">
                        {{ d.who ?? 'Reviewer' }} · {{ fmtDateTime(d.when) }}
                    </span>
                </div>
            </div>

            <div class="space-y-6 rounded-lg border bg-white p-6 shadow-sm print:space-y-4 print:rounded-none print:border-0 print:p-0 print:shadow-none">

                <!-- Class block -->
                <dl class="print-keep grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-4 print:grid-cols-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Teacher</dt>
                        <dd class="text-sm text-gray-900">{{ lessonPlan.teacher?.name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Subject</dt>
                        <dd class="text-sm text-gray-900">{{ lessonPlan.subject?.name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Class</dt>
                        <dd class="text-sm text-gray-900">{{ streamLabel(lessonPlan.stream) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Term</dt>
                        <dd class="text-sm text-gray-900">
                            {{ lessonPlan.term?.name ?? '—' }}
                            <span v-if="lessonPlan.week_number" class="text-gray-500">· Wk {{ lessonPlan.week_number }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Date</dt>
                        <dd class="text-sm text-gray-900">{{ fmtDate(lessonPlan.lesson_date) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Duration</dt>
                        <dd class="text-sm text-gray-900">
                            {{ lessonPlan.duration_minutes ? `${lessonPlan.duration_minutes} min` : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Pupils</dt>
                        <dd class="text-sm text-gray-900">
                            <template v-if="lessonPlan.total_pupils !== null">
                                {{ lessonPlan.total_pupils }}
                                <span class="text-gray-500">({{ lessonPlan.boys_count ?? 0 }}B / {{ lessonPlan.girls_count ?? 0 }}G)</span>
                            </template>
                            <template v-else>—</template>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Submitted</dt>
                        <dd class="text-sm text-gray-900">{{ lessonPlan.submitted_at ? fmtDateTime(lessonPlan.submitted_at) : '—' }}</dd>
                    </div>
                </dl>

                <hr class="border-gray-200" />

                <!-- Header block -->
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 print:grid-cols-2">
                    <div v-for="[label, value] in headerFields" :key="label">
                        <dt class="mb-1 text-xs font-medium text-gray-600">{{ label }}</dt>
                        <dd class="whitespace-pre-line text-sm text-gray-800">
                            {{ value || '—' }}
                        </dd>
                    </div>
                </dl>

                <hr class="border-gray-200" />

                <!-- Stage table: cards on phones, the four-column paper form on md and up. -->
                <div class="print:break-inside-auto">
                    <h2 class="mb-2 text-sm font-semibold text-gray-900">Lesson development</h2>

                    <div v-if="stages.length"
                        class="space-y-3 md:space-y-0 md:divide-y md:divide-gray-100 md:overflow-hidden md:rounded-md md:border md:border-gray-200 print:space-y-0 print:overflow-visible print:divide-y print:divide-gray-200 print:rounded-none print:border print:border-gray-300">
                        <div class="hidden border-b border-gray-200 bg-gray-50 px-3 py-2 md:grid md:grid-cols-[7rem_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] md:gap-3 print:grid print:grid-cols-[6rem_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] print:gap-3">
                            <span class="text-xs font-medium text-gray-600">Stage</span>
                            <span class="text-xs font-medium text-gray-600">Teacher activity</span>
                            <span class="text-xs font-medium text-gray-600">Learners activity</span>
                            <span class="text-xs font-medium text-gray-600">Assessment criteria</span>
                        </div>

                        <div v-for="(stage, i) in stages" :key="i"
                            class="print-keep rounded-md border border-gray-200 p-3 md:grid md:grid-cols-[7rem_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] md:gap-3 md:rounded-none md:border-0 md:px-3 md:py-3 print:grid print:grid-cols-[6rem_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] print:gap-3 print:rounded-none print:border-0 print:px-3 print:py-2">
                            <div class="mb-3 md:mb-0 print:mb-0">
                                <p class="text-sm font-medium text-gray-900">{{ stage.stage || '—' }}</p>
                            </div>
                            <div class="mb-3 md:mb-0 print:mb-0">
                                <p class="mb-1 text-xs font-medium text-gray-500 md:hidden print:hidden">Teacher activity</p>
                                <p class="whitespace-pre-line text-sm text-gray-800">{{ stage.teacher_activity || '—' }}</p>
                            </div>
                            <div class="mb-3 md:mb-0 print:mb-0">
                                <p class="mb-1 text-xs font-medium text-gray-500 md:hidden print:hidden">Learners activity</p>
                                <p class="whitespace-pre-line text-sm text-gray-800">{{ stage.learner_activity || '—' }}</p>
                            </div>
                            <div>
                                <p class="mb-1 text-xs font-medium text-gray-500 md:hidden print:hidden">Assessment criteria</p>
                                <p class="whitespace-pre-line text-sm text-gray-800">{{ stage.assessment_criteria || '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="rounded-md border border-dashed border-gray-200 px-3 py-6 text-center text-sm text-gray-400">
                        No stages recorded.
                    </p>
                </div>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 print:grid-cols-2">
                    <div>
                        <dt class="mb-1 text-xs font-medium text-gray-600">Conclusion</dt>
                        <dd class="whitespace-pre-line text-sm text-gray-800">{{ lessonPlan.conclusion || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="mb-1 text-xs font-medium text-gray-600">
                            Evaluation <span class="font-normal text-gray-400">(after teaching)</span>
                        </dt>
                        <dd class="whitespace-pre-line text-sm text-gray-800">{{ lessonPlan.evaluation || '—' }}</dd>
                    </div>
                </dl>

                <div v-if="lessonPlan.attachments?.length" class="print-keep">
                    <h2 class="mb-1 text-xs font-medium text-gray-600">Attachments</h2>
                    <ul class="divide-y divide-gray-100 rounded-md border border-gray-200">
                        <li v-for="a in lessonPlan.attachments" :key="a.id"
                            class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                            <a :href="a.url" target="_blank" class="truncate text-indigo-600 hover:underline">{{ a.name }}</a>
                            <span class="shrink-0 text-xs text-gray-400">{{ fmtSize(a.size) }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div v-if="canReview || canRevert" class="mt-5 flex flex-wrap items-center justify-end gap-3 print:hidden">
                <p v-if="canRevert" class="mr-auto text-xs text-gray-500">
                    Returning the plan withdraws this decision and lets the teacher edit and resubmit it.
                </p>
                <button v-if="canRevert"
                    class="rounded-md bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700"
                    @click="openDecision('reverted')">Return to teacher</button>
                <button v-if="canReview" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                    @click="openDecision('rejected')">Reject</button>
                <button v-if="canReview" class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                    @click="openDecision('approved')">Approve</button>
            </div>
        </div>

        <!-- Decision modal — approve, reject or return -->
        <div v-if="deciding" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            @click.self="deciding = null">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-1 text-lg font-semibold text-gray-900">{{ LESSON_PLAN_DECISION_UI[deciding].title }}</h2>
                <p class="mb-4 text-sm text-gray-500">{{ lessonPlan.topic }} — {{ lessonPlan.teacher?.name }}</p>

                <label class="mb-1 block text-xs font-medium text-gray-600">{{ LESSON_PLAN_DECISION_UI[deciding].label }}</label>
                <textarea v-model="reasonText" rows="4" class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                    :placeholder="LESSON_PLAN_DECISION_UI[deciding].hint" />
                <p v-if="reasonError" class="mt-1 text-xs text-red-600">{{ reasonError }}</p>

                <div class="mt-5 flex justify-end gap-3">
                    <button class="text-sm text-gray-500 hover:underline" @click="deciding = null">Cancel</button>
                    <button :disabled="activeForm.processing"
                        :class="['rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-50', LESSON_PLAN_DECISION_UI[deciding].button]"
                        @click="submitDecision">
                        {{ LESSON_PLAN_DECISION_UI[deciding].action }}
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
