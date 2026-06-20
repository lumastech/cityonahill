<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface LogEntry {
    id: number
    action: string
    notes: string | null
    created_at: string
    actor: { id: number; name: string }
}

interface Application {
    id: number
    status: string
    school_name: string
    subdomain: string
    type: string
    level: string
    province: string
    district: string
    address: string | null
    contact_phone: string
    contact_email: string
    headteacher_name: string
    moe_registration_no: string | null
    modules_config: string[] | null
    mobile_money_number: string | null
    reviewer_notes: string | null
    submitted_at: string | null
    reviewed_at: string | null
    applicant: { id: number; name: string; email: string; phone: string | null }
    logs: LogEntry[]
}

const props = defineProps<{ application: Application }>()

const ACTION_LABELS: Record<string, string> = {
    submitted:   'Application submitted',
    resubmitted: 'Application re-submitted',
    approved:    'Application approved',
    needs_info:  'Additional information requested',
    rejected:    'Application rejected',
}

const MODULE_LABELS: Record<string, string> = {
    finance:       'Finance',
    hr:            'Human Resources',
    library:       'Library',
    transport:     'Transport',
    feeding:       'Feeding',
    boarding:      'Boarding',
    communication: 'Communication',
    ecz:           'ECZ Examinations',
}

const showNeedsInfo = ref(false)
const showReject    = ref(false)

const needsInfoForm = useForm({ reviewer_notes: '' })
const rejectForm    = useForm({ reviewer_notes: '' })

function approve() {
    if (!confirm(`Approve "${props.application.school_name}"? This will create the school and notify the applicant.`)) return
    router.post(route('admin.applications.approve', props.application.id))
}

function submitNeedsInfo() {
    needsInfoForm.post(route('admin.applications.needs-info', props.application.id), {
        onSuccess: () => { showNeedsInfo.value = false },
    })
}

function submitReject() {
    rejectForm.post(route('admin.applications.reject', props.application.id), {
        onSuccess: () => { showReject.value = false },
    })
}

function formatDate(iso: string | null): string {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-ZM', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
    <AppLayout title="Review Application">
        <Head title="Review Application" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <Link :href="route('admin.applications.index')" class="text-xs text-gray-400 hover:text-gray-600">← All Applications</Link>
                    <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ application.school_name }}</h1>
                    <p class="text-sm text-gray-500 font-mono">{{ application.subdomain }}</p>
                </div>
                <span
                    class="rounded-full px-3 py-1 text-xs font-semibold"
                    :class="{
                        'bg-yellow-100 text-yellow-800': application.status === 'pending',
                        'bg-orange-100 text-orange-800': application.status === 'needs_info',
                        'bg-green-100 text-green-800':  application.status === 'approved',
                        'bg-red-100 text-red-800':      application.status === 'rejected',
                    }"
                >
                    {{ application.status.replace('_', ' ') }}
                </span>
            </div>

            <!-- Action buttons (only for pending) -->
            <div v-if="application.status === 'pending'" class="flex gap-3">
                <button
                    class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                    @click="approve"
                >
                    Approve & Create School
                </button>
                <button
                    class="rounded-md bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600"
                    @click="showNeedsInfo = true"
                >
                    Request More Info
                </button>
                <button
                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                    @click="showReject = true"
                >
                    Reject
                </button>
            </div>

            <!-- School Details -->
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-700 uppercase tracking-wide">School Details</h2>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Type</dt>
                        <dd class="capitalize text-gray-900">{{ application.type.replace(/_/g, ' & ') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Level</dt>
                        <dd class="capitalize text-gray-900">{{ application.level }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Province</dt>
                        <dd class="text-gray-900">{{ application.province }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">District</dt>
                        <dd class="text-gray-900">{{ application.district }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-xs text-gray-500 font-medium">Address</dt>
                        <dd class="text-gray-900">{{ application.address ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">MoE Reg No.</dt>
                        <dd class="text-gray-900">{{ application.moe_registration_no ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Submitted</dt>
                        <dd class="text-gray-900">{{ formatDate(application.submitted_at) }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Applicant / Contact -->
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-700 uppercase tracking-wide">Applicant / Contact</h2>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Account Name</dt>
                        <dd class="text-gray-900">{{ application.applicant.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Account Email</dt>
                        <dd class="text-gray-900">{{ application.applicant.email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Headteacher Name</dt>
                        <dd class="text-gray-900">{{ application.headteacher_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Contact Phone</dt>
                        <dd class="text-gray-900">{{ application.contact_phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Contact Email</dt>
                        <dd class="text-gray-900">{{ application.contact_email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Mobile Money No.</dt>
                        <dd class="text-gray-900">{{ application.mobile_money_number ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Requested Modules -->
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-gray-700 uppercase tracking-wide">Requested Modules</h2>
                <div v-if="application.modules_config?.length" class="flex flex-wrap gap-2">
                    <span
                        v-for="mod in application.modules_config"
                        :key="mod"
                        class="rounded-full bg-indigo-100 px-3 py-0.5 text-xs font-medium text-indigo-800"
                    >
                        {{ MODULE_LABELS[mod] ?? mod }}
                    </span>
                </div>
                <p v-else class="text-sm text-gray-400">No additional modules requested.</p>
            </div>

            <!-- Activity Log -->
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-700 uppercase tracking-wide">Activity</h2>
                <ol class="relative border-l border-gray-200 space-y-4 pl-4">
                    <li v-for="log in application.logs" :key="log.id">
                        <div class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-indigo-400"></div>
                        <p class="text-sm font-medium text-gray-800">{{ ACTION_LABELS[log.action] ?? log.action }}</p>
                        <p v-if="log.notes" class="mt-0.5 text-xs text-gray-500 italic">{{ log.notes }}</p>
                        <p class="mt-0.5 text-xs text-gray-400">{{ log.actor.name }} · {{ formatDate(log.created_at) }}</p>
                    </li>
                </ol>
            </div>
        </div>

        <!-- Needs Info modal -->
        <Teleport to="body">
            <div v-if="showNeedsInfo" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">Request More Information</h3>
                    <form @submit.prevent="submitNeedsInfo">
                        <textarea
                            v-model="needsInfoForm.reviewer_notes"
                            rows="4"
                            placeholder="Describe what additional information or corrections are needed…"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                            required
                        />
                        <p v-if="needsInfoForm.errors.reviewer_notes" class="mt-1 text-xs text-red-600">
                            {{ needsInfoForm.errors.reviewer_notes }}
                        </p>
                        <div class="mt-4 flex justify-end gap-3">
                            <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="showNeedsInfo = false">Cancel</button>
                            <button type="submit" :disabled="needsInfoForm.processing" class="rounded-md bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600 disabled:opacity-50">
                                Send to Applicant
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Reject modal -->
        <Teleport to="body">
            <div v-if="showReject" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">Reject Application</h3>
                    <form @submit.prevent="submitReject">
                        <textarea
                            v-model="rejectForm.reviewer_notes"
                            rows="4"
                            placeholder="Provide a reason for rejection (will be sent to the applicant)…"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                        />
                        <div class="mt-4 flex justify-end gap-3">
                            <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="showReject = false">Cancel</button>
                            <button type="submit" :disabled="rejectForm.processing" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">
                                Confirm Rejection
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
