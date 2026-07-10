<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

interface School {
    id: number
    name: string
    code: string
    type: string
    level: string
    province: string
    district: string
    address: string | null
    phone: string | null
    email: string | null
    website: string | null
    moe_registration_no: string | null
    established_year: number | null
    status: string
    headteacher?: { id: number; name: string } | null
}

const props = defineProps<{ school: School | null }>()

const form = useForm({
    name: props.school?.name ?? '',
    phone: props.school?.phone ?? '',
    email: props.school?.email ?? '',
    address: props.school?.address ?? '',
    website: props.school?.website ?? '',
})

function submit() {
    form.put(route('schools.update'))
}

const TYPE_LABELS: Record<string, string> = {
    government: 'Government',
    private: 'Private',
    mission: 'Mission',
    'grant-aided': 'Grant-Aided',
}

const LEVEL_LABELS: Record<string, string> = {
    primary: 'Primary',
    secondary: 'Secondary',
    basic: 'Basic',
    combined: 'Combined',
}
</script>

<template>
    <AppLayout title="School">
        <Head title="School" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
            <h1 class="mb-6 text-2xl font-bold text-gray-900">School Profile</h1>

            <!-- Empty state: no school in context -->
            <div v-if="!school" class="rounded-lg border border-dashed bg-white p-12 text-center shadow-sm">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
                <h2 class="mt-4 text-sm font-semibold text-gray-900">No school yet</h2>
                <p class="mt-1 text-sm text-gray-500">You don't have a school set up. Register your school to get started.</p>
                <Link
                    :href="route('onboarding.create')"
                    class="mt-6 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    Register a School
                </Link>
            </div>

            <template v-else>
            <!-- Info card -->
            <div class="mb-6 grid grid-cols-2 gap-4 rounded-lg border bg-white p-6 shadow-sm sm:grid-cols-4">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Type</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ TYPE_LABELS[school.type] ?? school.type }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Level</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ LEVEL_LABELS[school.level] ?? school.level }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Province</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ school.province }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">District</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ school.district }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Code</p>
                    <p class="mt-1 font-mono text-sm text-gray-900">{{ school.code }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">MOE Reg No</p>
                    <p class="mt-1 text-sm text-gray-900">{{ school.moe_registration_no ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Established</p>
                    <p class="mt-1 text-sm text-gray-900">{{ school.established_year ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Headteacher</p>
                    <p class="mt-1 text-sm text-gray-900">{{ school.headteacher?.name ?? '—' }}</p>
                </div>
            </div>

            <!-- Edit form -->
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-700">Edit Contact Details</h2>
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">School Name</label>
                        <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                        <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                            <input v-model="form.phone" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                            <input v-model="form.email" type="email" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                        <textarea v-model="form.address" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Website</label>
                        <input v-model="form.website" type="url" placeholder="https://" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
            </template>
        </div>
    </AppLayout>
</template>
