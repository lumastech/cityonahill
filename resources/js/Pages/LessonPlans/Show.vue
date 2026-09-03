<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import {
    LESSON_PLAN_STATUS_COLOR,
    LESSON_PLAN_STATUS_LABEL,
    type LessonPlan,
} from '@/composables/useLessonPlans'
import { fmtDate, fmtDateTime } from '@/utils/date'

const props = defineProps<{
    lessonPlan: LessonPlan
    canReview: boolean
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

function printPlan() {
    window.print()
}

function fmtSize(bytes?: number): string {
    if (!bytes) return ''
    const kb = bytes / 1024
    return kb < 1024 ? `${Math.round(kb)} KB` : `${(kb / 1024).toFixed(1)} MB`
}

// Review — the same approve/reject decision offered on the index, so a reviewer can
// read the plan and act on it without going back.
const reviewing = ref<'approved' | 'rejected' | null>(null)
const reviewForm = useForm({ status: 'approved', comment: '' })

function openReview(decision: 'approved' | 'rejected') {
    reviewing.value = decision
    reviewForm.status = decision
    reviewForm.comment = ''
    reviewForm.clearErrors()
}

function submitReview() {
    reviewForm.post(route('lesson-plans.review', props.lessonPlan.id), {
        preserveScroll: true,
        onSuccess: () => { reviewing.value = null },
    })
}
</script>

<template>
    <AppLayout :title="lessonPlan.topic">
        <Head :title="lessonPlan.topic" />

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

            <div class="mb-6 flex flex-wrap items-start gap-x-3 gap-y-2">
                <div class="min-w-0 flex-1">
                    <Link :href="route('lesson-plans.index')" class="text-sm text-indigo-600 hover:underline">← Lesson Plans</Link>
                    <h1 class="mt-1 text-xl font-bold text-gray-900">{{ lessonPlan.topic }}</h1>
                    <p v-if="lessonPlan.sub_topic" class="text-sm text-gray-500">{{ lessonPlan.sub_topic }}</p>
                </div>

                <div class="flex items-center gap-2 print:hidden">
                    <span :class="['rounded-full px-2.5 py-1 text-xs font-medium', LESSON_PLAN_STATUS_COLOR[lessonPlan.status]]">
                        {{ LESSON_PLAN_STATUS_LABEL[lessonPlan.status] }}
                    </span>
                    <button class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        @click="printPlan">Print</button>
                    <Link v-if="canEdit" :href="route('lesson-plans.edit', lessonPlan.id)"
                        class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">Edit</Link>
                </div>
            </div>

            <!-- Reviewer feedback -->
            <div v-if="lessonPlan.comment"
                :class="[
                    'mb-5 rounded-md border px-4 py-3 text-sm',
                    lessonPlan.status === 'rejected'
                        ? 'border-red-200 bg-red-50 text-red-800'
                        : 'border-green-200 bg-green-50 text-green-800',
                ]">
                <span class="font-semibold">Reviewer feedback:</span> {{ lessonPlan.comment }}
                <span v-if="lessonPlan.reviewedBy" class="block text-xs opacity-75">
                    {{ lessonPlan.reviewedBy.name }} · {{ fmtDateTime(lessonPlan.reviewed_at) }}
                </span>
            </div>

            <div class="space-y-6 rounded-lg border bg-white p-6 shadow-sm">

                <!-- Class block -->
                <dl class="grid grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Teacher</dt>
                        <dd class="text-sm text-gray-900">{{ lessonPlan.submittedBy?.name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Subject</dt>
                        <dd class="text-sm text-gray-900">{{ lessonPlan.subject?.name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">Class</dt>
                        <dd class="text-sm text-gray-900">{{ lessonPlan.stream?.name ?? '—' }}</dd>
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
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div v-for="[label, value] in headerFields" :key="label">
                        <dt class="mb-1 text-xs font-medium text-gray-600">{{ label }}</dt>
                        <dd class="whitespace-pre-line text-sm text-gray-800">
                            {{ value || '—' }}
                        </dd>
                    </div>
                </dl>

                <hr class="border-gray-200" />

                <!-- Stage table: cards on phones, the four-column paper form on md and up. -->
                <div>
                    <h2 class="mb-2 text-sm font-semibold text-gray-900">Lesson development</h2>

                    <div v-if="stages.length"
                        class="space-y-3 md:space-y-0 md:divide-y md:divide-gray-100 md:overflow-hidden md:rounded-md md:border md:border-gray-200">
                        <div class="hidden border-b border-gray-200 bg-gray-50 px-3 py-2 md:grid md:grid-cols-[7rem_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] md:gap-3">
                            <span class="text-xs font-medium text-gray-600">Stage</span>
                            <span class="text-xs font-medium text-gray-600">Teacher activity</span>
                            <span class="text-xs font-medium text-gray-600">Learners activity</span>
                            <span class="text-xs font-medium text-gray-600">Assessment criteria</span>
                        </div>

                        <div v-for="(stage, i) in stages" :key="i"
                            class="rounded-md border border-gray-200 p-3 md:grid md:grid-cols-[7rem_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] md:gap-3 md:rounded-none md:border-0 md:px-3 md:py-3">
                            <div class="mb-3 md:mb-0">
                                <p class="text-sm font-medium text-gray-900">{{ stage.stage || '—' }}</p>
                            </div>
                            <div class="mb-3 md:mb-0">
                                <p class="mb-1 text-xs font-medium text-gray-500 md:hidden">Teacher activity</p>
                                <p class="whitespace-pre-line text-sm text-gray-800">{{ stage.teacher_activity || '—' }}</p>
                            </div>
                            <div class="mb-3 md:mb-0">
                                <p class="mb-1 text-xs font-medium text-gray-500 md:hidden">Learners activity</p>
                                <p class="whitespace-pre-line text-sm text-gray-800">{{ stage.learner_activity || '—' }}</p>
                            </div>
                            <div>
                                <p class="mb-1 text-xs font-medium text-gray-500 md:hidden">Assessment criteria</p>
                                <p class="whitespace-pre-line text-sm text-gray-800">{{ stage.assessment_criteria || '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="rounded-md border border-dashed border-gray-200 px-3 py-6 text-center text-sm text-gray-400">
                        No stages recorded.
                    </p>
                </div>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

                <div v-if="lessonPlan.attachments?.length">
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

            <div v-if="canReview" class="mt-5 flex flex-wrap justify-end gap-3 print:hidden">
                <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                    @click="openReview('rejected')">Reject</button>
                <button class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                    @click="openReview('approved')">Approve</button>
            </div>
        </div>

        <!-- Review modal -->
        <div v-if="reviewing" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            @click.self="reviewing = null">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-1 text-lg font-semibold text-gray-900">
                    {{ reviewing === 'approved' ? 'Approve' : 'Reject' }} lesson plan
                </h2>
                <p class="mb-4 text-sm text-gray-500">{{ lessonPlan.topic }} — {{ lessonPlan.submittedBy?.name }}</p>

                <label class="mb-1 block text-xs font-medium text-gray-600">
                    Comment {{ reviewing === 'rejected' ? '(required)' : '(optional)' }}
                </label>
                <textarea v-model="reviewForm.comment" rows="4" class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                    :placeholder="reviewing === 'rejected' ? 'Explain what needs to change…' : 'Optional note to the teacher…'" />
                <p v-if="reviewForm.errors.comment" class="mt-1 text-xs text-red-600">{{ reviewForm.errors.comment }}</p>

                <div class="mt-5 flex justify-end gap-3">
                    <button class="text-sm text-gray-500 hover:underline" @click="reviewing = null">Cancel</button>
                    <button :disabled="reviewForm.processing"
                        :class="[
                            'rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-50',
                            reviewing === 'approved' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700',
                        ]"
                        @click="submitReview">
                        {{ reviewing === 'approved' ? 'Approve' : 'Reject' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
