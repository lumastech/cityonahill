<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { usePermissions } from '@/composables/usePermissions'
import { Head, Link, useForm } from '@inertiajs/vue3'

interface Branch {
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
    pupils_count: number
    staff_count: number
    headteacher?: { id: number; name: string } | null
}

const props = defineProps<{ school: Branch }>()

const { can } = usePermissions()

const form = useForm({
    name: props.school.name,
    code: props.school.code,
    type: props.school.type,
    level: props.school.level,
    province: props.school.province,
    district: props.school.district,
    phone: props.school.phone ?? '',
    email: props.school.email ?? '',
    address: props.school.address ?? '',
    website: props.school.website ?? '',
    moe_registration_no: props.school.moe_registration_no ?? '',
    established_year: props.school.established_year,
})

function submit() {
    form.transform((data) => ({
        ...data,
        email: data.email || null,
        website: data.website || null,
        moe_registration_no: data.moe_registration_no || null,
    })).put(route('schools.update', props.school.id))
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
    <AppLayout title="Branch Profile">
        <Head :title="school.name" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
            <div class="mb-6">
                <Link :href="route('schools.index')" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; Branches</Link>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ school.name }}</h1>
            </div>

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
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Pupils</p>
                    <p class="mt-1 text-sm text-gray-900">{{ school.pupils_count }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Staff</p>
                    <p class="mt-1 text-sm text-gray-900">{{ school.staff_count }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">Status</p>
                    <p class="mt-1 text-sm text-gray-900 capitalize">{{ school.status }}</p>
                </div>
            </div>

            <!-- Edit form -->
            <div v-if="can('school.update')" class="rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold text-gray-700">Edit Branch Details</h2>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Branch Name</label>
                            <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Code</label>
                            <input v-model="form.code" type="text" maxlength="20" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.code" class="text-xs text-red-600 mt-1">{{ form.errors.code }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                            <input v-model="form.phone" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.phone" class="text-xs text-red-600 mt-1">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                            <input v-model="form.email" type="email" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                        <textarea v-model="form.address" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                        <p v-if="form.errors.address" class="text-xs text-red-600 mt-1">{{ form.errors.address }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Website</label>
                        <input v-model="form.website" type="url" placeholder="https://" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.website" class="text-xs text-red-600 mt-1">{{ form.errors.website }}</p>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
