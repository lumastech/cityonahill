<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'

defineProps<{ provinces: string[] }>()

const form = useForm({
    organization_name: '',
    name: '',
    code: '',
    type: 'private',
    level: 'combined',
    province: '',
    district: '',
    address: '',
    phone: '',
    email: '',
    website: '',
    moe_registration_no: '',
    established_year: null as number | null,
})

function submit() {
    form.transform((data) => ({
        ...data,
        email: data.email || null,
        website: data.website || null,
        moe_registration_no: data.moe_registration_no || null,
    })).post(route('setup.store'))
}

const TYPES = [
    { value: 'government', label: 'Government' },
    { value: 'private', label: 'Private' },
    { value: 'mission', label: 'Mission' },
    { value: 'grant-aided', label: 'Grant-Aided' },
]

const LEVELS = [
    { value: 'primary', label: 'Primary' },
    { value: 'secondary', label: 'Secondary' },
    { value: 'basic', label: 'Basic' },
    { value: 'combined', label: 'Combined (Grades 1–12)' },
]
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <Head title="Setup" />

        <div class="mx-auto max-w-2xl">
            <div class="mb-8 text-center">
                <h1 class="text-2xl font-bold text-gray-900">Welcome — let's set things up</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Tell us about your organization and create your first school branch. You can add more branches later.
                </p>
            </div>

            <form class="space-y-6 rounded-lg border bg-white p-6 shadow-sm" @submit.prevent="submit">
                <div>
                    <h2 class="mb-3 text-sm font-semibold text-gray-700">Organization</h2>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Organization Name</label>
                    <input v-model="form.organization_name" type="text" placeholder="e.g. City on a Hill Academy"
                        class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                    <p v-if="form.errors.organization_name" class="text-xs text-red-600 mt-1">{{ form.errors.organization_name }}</p>
                </div>

                <div class="border-t pt-6">
                    <h2 class="mb-3 text-sm font-semibold text-gray-700">First Branch</h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Branch Name</label>
                                <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                                <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Code</label>
                                <input v-model="form.code" type="text" maxlength="20" placeholder="e.g. COH-MAIN"
                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                                <p v-if="form.errors.code" class="text-xs text-red-600 mt-1">{{ form.errors.code }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                                <select v-model="form.type" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                                    <option v-for="t in TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Level</label>
                                <select v-model="form.level" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                                    <option v-for="l in LEVELS" :key="l.value" :value="l.value">{{ l.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Province</label>
                                <select v-model="form.province" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                                    <option value="" disabled>Select province…</option>
                                    <option v-for="p in provinces" :key="p" :value="p">{{ p }}</option>
                                </select>
                                <p v-if="form.errors.province" class="text-xs text-red-600 mt-1">{{ form.errors.province }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">District</label>
                                <input v-model="form.district" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                                <p v-if="form.errors.district" class="text-xs text-red-600 mt-1">{{ form.errors.district }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                            <textarea v-model="form.address" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.address" class="text-xs text-red-600 mt-1">{{ form.errors.address }}</p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                                <input v-model="form.phone" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                                <p v-if="form.errors.phone" class="text-xs text-red-600 mt-1">{{ form.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-gray-400">(optional)</span></label>
                                <input v-model="form.email" type="email" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                                <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end border-t pt-6">
                    <button type="submit" :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                        Finish Setup
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
