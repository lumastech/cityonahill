<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { usePermissions } from '@/composables/usePermissions'
import {
    LESSON_PLAN_STATUS_COLOR,
    LESSON_PLAN_STATUS_LABEL,
    type LessonPlan,
    type LessonPlanOption,
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
]

function canEdit(plan: LessonPlan): boolean {
    return plan.submitted_by === currentUserId.value && ['draft', 'rejected'].includes(plan.status)
}

// Review modal
const reviewing = ref<LessonPlan | null>(null)
const reviewForm = useForm({ status: 'approved', comment: '' })

function openReview(plan: LessonPlan, decision: 'approved' | 'rejected') {
    reviewing.value = plan
    reviewForm.status = decision
    reviewForm.comment = ''
    reviewForm.clearErrors()
}

function submitReview() {
    if (!reviewing.value) return
    reviewForm.post(route('lesson-plans.review', reviewing.value.id), {
        preserveScroll: true,
        onSuccess: () => { reviewing.value = null },
    })
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
                                    {{ plan.topic }}
                                    <span v-if="plan.sub_topic" class="block text-xs font-normal text-gray-500">{{ plan.sub_topic }}</span>
                                    <span v-if="plan.week_number" class="text-xs text-gray-400">Wk {{ plan.week_number }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.subject?.name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.stream?.name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.term?.name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ plan.lesson_date ? fmtDate(plan.lesson_date) : '—' }}</td>
                                <td v-if="canReview" class="px-4 py-3 text-gray-600">{{ plan.submittedBy?.name }}</td>
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
                                    <template v-if="canReview && plan.status === 'submitted'">
                                        <button class="mr-3 text-xs font-medium text-green-600 hover:text-green-900"
                                            @click="openReview(plan, 'approved')">Approve</button>
                                        <button class="mr-3 text-xs font-medium text-red-600 hover:text-red-900"
                                            @click="openReview(plan, 'rejected')">Reject</button>
                                    </template>
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
                                <h2 class="truncate font-medium text-gray-900">{{ plan.topic }}</h2>
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
                                <dd class="truncate text-gray-700">{{ plan.stream?.name ?? '—' }}</dd>
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
                                <dd class="truncate text-gray-700">{{ plan.submittedBy?.name ?? '—' }}</dd>
                            </div>
                            <div v-if="plan.media_count">
                                <dt class="text-xs text-gray-400">Files</dt>
                                <dd class="text-gray-700">{{ plan.media_count }}</dd>
                            </div>
                        </dl>

                        <div v-if="(canReview && plan.status === 'submitted') || canEdit(plan)"
                            class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3">
                            <template v-if="canReview && plan.status === 'submitted'">
                                <button class="rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700"
                                    @click="openReview(plan, 'approved')">Approve</button>
                                <button class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700"
                                    @click="openReview(plan, 'rejected')">Reject</button>
                            </template>
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

        <!-- Review modal -->
        <div v-if="reviewing" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            @click.self="reviewing = null">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-1 text-lg font-semibold text-gray-900">
                    {{ reviewForm.status === 'approved' ? 'Approve' : 'Reject' }} lesson plan
                </h2>
                <p class="mb-4 text-sm text-gray-500">{{ reviewing.topic }} — {{ reviewing.submittedBy?.name }}</p>

                <label class="mb-1 block text-xs font-medium text-gray-600">
                    Comment {{ reviewForm.status === 'rejected' ? '(required)' : '(optional)' }}
                </label>
                <textarea v-model="reviewForm.comment" rows="4" class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                    :placeholder="reviewForm.status === 'rejected' ? 'Explain what needs to change…' : 'Optional note to the teacher…'" />
                <p v-if="reviewForm.errors.comment" class="mt-1 text-xs text-red-600">{{ reviewForm.errors.comment }}</p>

                <div class="mt-5 flex justify-end gap-3">
                    <button class="text-sm text-gray-500 hover:underline" @click="reviewing = null">Cancel</button>
                    <button :disabled="reviewForm.processing"
                        :class="[
                            'rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-50',
                            reviewForm.status === 'approved' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700',
                        ]"
                        @click="submitReview">
                        {{ reviewForm.status === 'approved' ? 'Approve' : 'Reject' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
