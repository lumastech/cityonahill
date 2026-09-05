<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { usePermissions } from '@/composables/usePermissions'
import {
    isRevertable,
    LESSON_PLAN_DECISION_UI,
    LESSON_PLAN_EDITABLE_STATUSES,
    LESSON_PLAN_STATUS_COLOR,
    LESSON_PLAN_STATUS_LABEL,
    streamLabel,
    type LessonPlan,
    type LessonPlanDecision,
    type LessonPlanOption,
    type LessonPlanStatus,
} from '@/composables/useLessonPlans'
import { fmtDate } from '@/utils/date'

interface Paginated {
    data: LessonPlan[]
    links: { url: string | null; label: string; active: boolean }[]
    total: number
}

const props = defineProps<{
    lessonPlans: Paginated
    filters: { status: string; subject_id: number | null; stream_id: number | null }
    canReview: boolean
    subjects: LessonPlanOption[]
    streams: LessonPlanOption[]
}>()

const { can } = usePermissions()
const page = usePage()
const currentUserId = computed<number | undefined>(() => (page.props.auth as any)?.user?.id)

const status = ref(props.filters.status ?? 'all')
const subjectId = ref<number | ''>(props.filters.subject_id ?? '')
const streamId = ref<number | ''>(props.filters.stream_id ?? '')

function applyFilters() {
    router.get(route('lesson-plans.index'), {
        status: status.value,
        subject_id: subjectId.value || undefined,
        stream_id: streamId.value || undefined,
    }, { preserveState: true, replace: true })
}

watch([status, subjectId, streamId], applyFilters)

const STATUS_TABS = [
    ['all', 'All'],
    ['draft', 'Drafts'],
    ['submitted', 'Pending'],
    ['approved', 'Approved'],
    ['rejected', 'Rejected'],
    ['reverted', 'Returned'],
]

function canEdit(plan: LessonPlan): boolean {
    return plan.submitted_by === currentUserId.value
        && LESSON_PLAN_EDITABLE_STATUSES.includes(plan.status as LessonPlanStatus)
}

// Decision modal — approve, reject, or return a decided plan to its teacher.
const deciding = ref<{ plan: LessonPlan; decision: LessonPlanDecision } | null>(null)

const reviewForm = useForm({ status: 'approved', comment: '', reject_reason: '' })
const revertForm = useForm({ revert_reason: '' })

const activeForm = computed(() => (deciding.value?.decision === 'reverted' ? revertForm : reviewForm))

/** Withdrawing a decision is offered to a reviewer once one has been made. */
function canRevert(plan: LessonPlan): boolean {
    return props.canReview && isRevertable(plan)
}

function openDecision(plan: LessonPlan, decision: LessonPlanDecision) {
    deciding.value = { plan, decision }
    reviewForm.reset()
    revertForm.reset()
    reviewForm.clearErrors()
    revertForm.clearErrors()

    if (decision !== 'reverted') reviewForm.status = decision
}

/** The one field each decision writes, so the modal keeps a single textarea. */
const reasonText = computed({
    get: () => {
        if (deciding.value?.decision === 'reverted') return revertForm.revert_reason
        return deciding.value?.decision === 'rejected' ? reviewForm.reject_reason : reviewForm.comment
    },
    set: (value: string) => {
        if (deciding.value?.decision === 'reverted') revertForm.revert_reason = value
        else if (deciding.value?.decision === 'rejected') reviewForm.reject_reason = value
        else reviewForm.comment = value
    },
})

const reasonError = computed(() => {
    if (deciding.value?.decision === 'reverted') return revertForm.errors.revert_reason
    return deciding.value?.decision === 'rejected' ? reviewForm.errors.reject_reason : reviewForm.errors.comment
})

function submitDecision() {
    if (!deciding.value) return

    const { plan, decision } = deciding.value
    const options = {
        preserveScroll: true,
        onSuccess: () => { deciding.value = null },
    }

    if (decision === 'reverted') {
        revertForm.post(route('lesson-plans.revert', plan.id), options)
    } else {
        reviewForm.post(route('lesson-plans.review', plan.id), options)
    }
}

function destroy(plan: LessonPlan) {
    if (!window.confirm('Delete this lesson plan?')) return
    router.delete(route('lesson-plans.destroy', plan.id), { preserveScroll: true })
}
</script>

<template>
    <AppLayout title="Lesson Plans">
        <Head title="Lesson Plans" />

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <div class="mb-5 flex flex-wrap items-center gap-3">
                    <h1 class="mr-4 text-2xl font-semibold text-gray-900">Lesson Plans</h1>

                    <div class="flex w-full overflow-x-auto rounded-md border border-gray-300 text-sm sm:w-auto sm:overflow-hidden">
                        <button v-for="tab in STATUS_TABS" :key="tab[0]"
                            @click="status = tab[0]"
                            :class="[
                                'shrink-0 whitespace-nowrap px-3 py-1.5 font-medium transition-colors',
                                status === tab[0] ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50',
                            ]">
                            {{ tab[1] }}
                        </button>
                    </div>

                    <select v-model="subjectId" class="min-w-0 flex-1 rounded-md border-gray-300 text-sm shadow-sm sm:flex-none">
                        <option value="">All subjects</option>
                        <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <select v-model="streamId" class="min-w-0 flex-1 rounded-md border-gray-300 text-sm shadow-sm sm:flex-none">
                        <option value="">All classes</option>
                        <option v-for="st in streams" :key="st.id" :value="st.id">{{ st.name }}</option>
                    </select>

                    <span class="ml-auto text-sm text-gray-400">{{ lessonPlans.total }} record{{ lessonPlans.total !== 1 ? 's' : '' }}</span>

                    <Link v-if="can('lesson-plan.create')" :href="route('lesson-plans.create')"
                        class="w-full shrink-0 rounded-md bg-indigo-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-indigo-700 sm:w-auto">
                        New Lesson Plan
                    </Link>
                </div>

                <!-- Table on md and up; the same rows render as cards on phones below. -->
                <div class="hidden overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm md:block">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Topic</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Subject</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Class</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Term</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Date</th>
                                <th v-if="canReview" class="px-4 py-3 text-left font-medium text-gray-600">Teacher</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Files</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="plan in lessonPlans.data" :key="plan.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    <Link :href="route('lesson-plans.show', plan.id)" class="hover:text-indigo-600 hover:underline">
                                        {{ plan.topic }}
                                    </Link>
                                    <span v-if="plan.sub_topic" class="block text-xs font-normal text-gray-500">{{ plan.sub_topic }}</span>
                                    <span v-if="plan.week_number" class="text-xs text-gray-400">Wk {{ plan.week_number }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.subject?.name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ streamLabel(plan.stream) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.term?.name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ plan.lesson_date ? fmtDate(plan.lesson_date) : '—' }}</td>
                                <td v-if="canReview" class="px-4 py-3 text-gray-600">{{ plan.teacher?.name }}</td>
                                <td class="px-4 py-3 text-gray-500">
                                    <span v-if="plan.media_count">{{ plan.media_count }}</span>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', LESSON_PLAN_STATUS_COLOR[plan.status]]">
                                        {{ LESSON_PLAN_STATUS_LABEL[plan.status] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <Link :href="route('lesson-plans.show', plan.id)"
                                        class="mr-3 text-xs font-medium text-indigo-600 hover:text-indigo-900">View</Link>
                                    <template v-if="canReview && plan.status === 'submitted'">
                                        <button class="mr-3 text-xs font-medium text-green-600 hover:text-green-900"
                                            @click="openDecision(plan, 'approved')">Approve</button>
                                        <button class="mr-3 text-xs font-medium text-red-600 hover:text-red-900"
                                            @click="openDecision(plan, 'rejected')">Reject</button>
                                    </template>
                                    <button v-if="canRevert(plan)"
                                        class="mr-3 text-xs font-medium text-orange-600 hover:text-orange-900"
                                        title="Withdraw this decision and return the plan to the teacher"
                                        @click="openDecision(plan, 'reverted')">Return</button>
                                    <Link v-if="canEdit(plan)" :href="route('lesson-plans.edit', plan.id)"
                                        class="mr-3 text-xs font-medium text-indigo-600 hover:text-indigo-900">Edit</Link>
                                    <button v-if="canEdit(plan) && can('lesson-plan.delete')"
                                        class="text-xs font-medium text-gray-500 hover:text-red-700"
                                        @click="destroy(plan)">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!lessonPlans.data.length">
                                <td :colspan="canReview ? 9 : 8" class="px-4 py-10 text-center text-gray-400">
                                    No lesson plans found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Card list for phones -->
                <div class="space-y-3 md:hidden">
                    <article v-for="plan in lessonPlans.data" :key="plan.id"
                        class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="truncate font-medium text-gray-900">
                                    <Link :href="route('lesson-plans.show', plan.id)" class="hover:text-indigo-600 hover:underline">
                                        {{ plan.topic }}
                                    </Link>
                                </h2>
                                <p v-if="plan.sub_topic" class="truncate text-xs text-gray-500">{{ plan.sub_topic }}</p>
                            </div>
                            <span :class="['shrink-0 rounded-full px-2 py-0.5 text-xs font-medium', LESSON_PLAN_STATUS_COLOR[plan.status]]">
                                {{ LESSON_PLAN_STATUS_LABEL[plan.status] }}
                            </span>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <div>
                                <dt class="text-xs text-gray-400">Subject</dt>
                                <dd class="truncate text-gray-700">{{ plan.subject?.name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400">Class</dt>
                                <dd class="truncate text-gray-700">{{ streamLabel(plan.stream) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400">Date</dt>
                                <dd class="text-gray-700">{{ plan.lesson_date ? fmtDate(plan.lesson_date) : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400">Term</dt>
                                <dd class="truncate text-gray-700">
                                    {{ plan.term?.name ?? '—' }}
                                    <span v-if="plan.week_number" class="text-gray-400">· Wk {{ plan.week_number }}</span>
                                </dd>
                            </div>
                            <div v-if="canReview">
                                <dt class="text-xs text-gray-400">Teacher</dt>
                                <dd class="truncate text-gray-700">{{ plan.teacher?.name ?? '—' }}</dd>
                            </div>
                            <div v-if="plan.media_count">
                                <dt class="text-xs text-gray-400">Files</dt>
                                <dd class="text-gray-700">{{ plan.media_count }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3">
                            <Link :href="route('lesson-plans.show', plan.id)"
                                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">View</Link>
                            <template v-if="canReview && plan.status === 'submitted'">
                                <button class="rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700"
                                    @click="openDecision(plan, 'approved')">Approve</button>
                                <button class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700"
                                    @click="openDecision(plan, 'rejected')">Reject</button>
                            </template>
                            <button v-if="canRevert(plan)"
                                class="rounded-md bg-orange-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-orange-700"
                                @click="openDecision(plan, 'reverted')">Return</button>
                            <Link v-if="canEdit(plan)" :href="route('lesson-plans.edit', plan.id)"
                                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Edit</Link>
                            <button v-if="canEdit(plan) && can('lesson-plan.delete')"
                                class="ml-auto text-xs font-medium text-gray-500 hover:text-red-700"
                                @click="destroy(plan)">Delete</button>
                        </div>
                    </article>

                    <p v-if="!lessonPlans.data.length"
                        class="rounded-lg border border-gray-200 bg-white px-4 py-10 text-center text-gray-400">
                        No lesson plans found.
                    </p>
                </div>

                <div v-if="lessonPlans.links.length > 3" class="mt-4 flex justify-center gap-1 text-sm">
                    <template v-for="link in lessonPlans.links" :key="link.label">
                        <a v-if="link.url" :href="link.url"
                            class="rounded border px-3 py-1"
                            :class="link.active ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                            v-html="link.label" />
                        <span v-else class="rounded border border-gray-100 px-3 py-1 text-gray-300" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>

        <!-- Decision modal — approve, reject or return -->
        <div v-if="deciding" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            @click.self="deciding = null">
            <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-1 text-lg font-semibold text-gray-900">
                    {{ LESSON_PLAN_DECISION_UI[deciding.decision].title }}
                </h2>
                <p class="mb-4 text-sm text-gray-500">{{ deciding.plan.topic }} — {{ deciding.plan.teacher?.name }}</p>

                <label class="mb-1 block text-xs font-medium text-gray-600">
                    {{ LESSON_PLAN_DECISION_UI[deciding.decision].label }}
                </label>
                <textarea v-model="reasonText" rows="4" class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                    :placeholder="LESSON_PLAN_DECISION_UI[deciding.decision].hint" />
                <p v-if="reasonError" class="mt-1 text-xs text-red-600">{{ reasonError }}</p>

                <p v-if="deciding.decision === 'reverted'" class="mt-2 text-xs text-gray-500">
                    The plan goes back to the teacher to edit and resubmit. The decision being
                    withdrawn stays on the record.
                </p>

                <div class="mt-5 flex justify-end gap-3">
                    <button class="text-sm text-gray-500 hover:underline" @click="deciding = null">Cancel</button>
                    <button :disabled="activeForm.processing"
                        :class="[
                            'rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-50',
                            LESSON_PLAN_DECISION_UI[deciding.decision].button,
                        ]"
                        @click="submitDecision">
                        {{ LESSON_PLAN_DECISION_UI[deciding.decision].action }}
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
