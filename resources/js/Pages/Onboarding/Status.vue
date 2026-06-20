<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

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
    contact_phone: string
    contact_email: string
    reviewer_notes: string | null
    submitted_at: string | null
    reviewed_at: string | null
    logs: LogEntry[]
}

const props = defineProps<{ application: Application }>()

const STATUS_CONFIG: Record<string, { label: string; color: string; bg: string }> = {
    pending:    { label: 'Pending Review',            color: 'text-yellow-800', bg: 'bg-yellow-100' },
    needs_info: { label: 'Needs More Information',    color: 'text-orange-800', bg: 'bg-orange-100' },
    approved:   { label: 'Approved',                  color: 'text-green-800',  bg: 'bg-green-100'  },
    rejected:   { label: 'Not Approved',              color: 'text-red-800',    bg: 'bg-red-100'    },
}

const ACTION_LABELS: Record<string, string> = {
    submitted:   'Application submitted',
    resubmitted: 'Application re-submitted',
    approved:    'Application approved',
    needs_info:  'Additional information requested',
    rejected:    'Application rejected',
}

const cfg = STATUS_CONFIG[props.application.status] ?? STATUS_CONFIG.pending

function formatDate(iso: string | null): string {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString('en-ZM', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<template>
    <AppLayout title="Application Status">
        <Head title="Application Status" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ application.school_name }}</h1>
                    <p class="mt-1 text-sm text-gray-500">Subdomain: <span class="font-mono">{{ application.subdomain }}</span></p>
                </div>
                <span :class="[cfg.bg, cfg.color, 'rounded-full px-3 py-1 text-xs font-semibold']">
                    {{ cfg.label }}
                </span>
            </div>

            <!-- Reviewer notes if needs_info -->
            <div v-if="application.status === 'needs_info'" class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                <p class="text-sm font-semibold text-orange-800 mb-1">Action Required</p>
                <p class="text-sm text-orange-700">{{ application.reviewer_notes }}</p>
                <div class="mt-3">
                    <Link
                        :href="route('onboarding.edit', application.id)"
                        class="rounded-md bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700"
                    >
                        Update & Re-submit
                    </Link>
                </div>
            </div>

            <!-- Rejection reason -->
            <div v-if="application.status === 'rejected' && application.reviewer_notes" class="rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-800 mb-1">Reason</p>
                <p class="text-sm text-red-700">{{ application.reviewer_notes }}</p>
            </div>

            <!-- Summary card -->
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-700 uppercase tracking-wide">Application Summary</h2>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Type</dt>
                        <dd class="mt-0.5 capitalize text-gray-900">{{ application.type.replace('_', ' & ') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Level</dt>
                        <dd class="mt-0.5 capitalize text-gray-900">{{ application.level }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Province</dt>
                        <dd class="mt-0.5 text-gray-900">{{ application.province }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">District</dt>
                        <dd class="mt-0.5 text-gray-900">{{ application.district }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Phone</dt>
                        <dd class="mt-0.5 text-gray-900">{{ application.contact_phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Email</dt>
                        <dd class="mt-0.5 text-gray-900">{{ application.contact_email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Submitted</dt>
                        <dd class="mt-0.5 text-gray-900">{{ formatDate(application.submitted_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 font-medium">Last Reviewed</dt>
                        <dd class="mt-0.5 text-gray-900">{{ formatDate(application.reviewed_at) }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Activity log -->
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-700 uppercase tracking-wide">Activity</h2>
                <ol class="relative border-l border-gray-200 space-y-4 pl-4">
                    <li v-for="log in application.logs" :key="log.id">
                        <div class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-400"></div>
                        <p class="text-sm font-medium text-gray-800">{{ ACTION_LABELS[log.action] ?? log.action }}</p>
                        <p v-if="log.notes" class="mt-0.5 text-xs text-gray-500 italic">{{ log.notes }}</p>
                        <p class="mt-0.5 text-xs text-gray-400">
                            {{ log.actor.name }} · {{ formatDate(log.created_at) }}
                        </p>
                    </li>
                </ol>
            </div>
        </div>
    </AppLayout>
</template>
