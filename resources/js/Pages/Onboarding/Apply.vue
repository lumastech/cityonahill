<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

interface Application {
    id: number
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
}

const props = defineProps<{
    provinces: string[]
    modules: Record<string, string>
    application?: Application
}>()

const isEdit = !!props.application

const form = useForm({
    school_name:          props.application?.school_name ?? '',
    subdomain:            props.application?.subdomain ?? '',
    type:                 props.application?.type ?? '',
    level:                props.application?.level ?? '',
    province:             props.application?.province ?? '',
    district:             props.application?.district ?? '',
    address:              props.application?.address ?? '',
    contact_phone:        props.application?.contact_phone ?? '',
    contact_email:        props.application?.contact_email ?? '',
    headteacher_name:     props.application?.headteacher_name ?? '',
    moe_registration_no:  props.application?.moe_registration_no ?? '',
    modules_config:       props.application?.modules_config ?? [] as string[],
    mobile_money_number:  props.application?.mobile_money_number ?? '',
})

function toggleModule(key: string) {
    const idx = form.modules_config.indexOf(key)
    if (idx === -1) form.modules_config.push(key)
    else form.modules_config.splice(idx, 1)
}

function submit() {
    if (isEdit) {
        form.put(route('onboarding.update', props.application!.id))
    } else {
        form.post(route('onboarding.store'))
    }
}
</script>

<template>
    <AppLayout title="Apply — School Onboarding">
        <Head title="School Application" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ isEdit ? 'Update Your Application' : 'Apply to Register Your School' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Fill in the details below. Our team will review your application and get back to you.
                </p>
            </div>

            <!-- Reviewer notes banner (needs_info state) -->
            <div v-if="application?.reviewer_notes" class="mb-6 rounded-lg border border-orange-200 bg-orange-50 p-4">
                <p class="text-sm font-semibold text-orange-800">Action required from reviewer:</p>
                <p class="mt-1 text-sm text-orange-700">{{ application.reviewer_notes }}</p>
            </div>

            <form class="space-y-8" @submit.prevent="submit">
                <!-- School Details -->
                <section class="rounded-lg border bg-white p-6 shadow-sm space-y-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">School Details</h2>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">School Name <span class="text-red-500">*</span></label>
                        <input v-model="form.school_name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                        <p v-if="form.errors.school_name" class="mt-1 text-xs text-red-600">{{ form.errors.school_name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Preferred Subdomain <span class="text-red-500">*</span>
                        </label>
                        <div class="flex rounded-md shadow-sm">
                            <input
                                v-model="form.subdomain"
                                type="text"
                                placeholder="yourschool"
                                class="flex-1 rounded-l-md border-gray-300 text-sm"
                                pattern="[a-z0-9\-]+"
                                required
                            />
                            <span class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">
                                .{{ $page.props.app_domain ?? 'yourdomain.com' }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">Lowercase letters, numbers, and hyphens only.</p>
                        <p v-if="form.errors.subdomain" class="mt-1 text-xs text-red-600">{{ form.errors.subdomain }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">School Type <span class="text-red-500">*</span></label>
                            <select v-model="form.type" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                                <option value="">Select type…</option>
                                <option value="day">Day</option>
                                <option value="boarding">Boarding</option>
                                <option value="day_and_boarding">Day & Boarding</option>
                            </select>
                            <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">School Level <span class="text-red-500">*</span></label>
                            <select v-model="form.level" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                                <option value="">Select level…</option>
                                <option value="primary">Primary</option>
                                <option value="secondary">Secondary</option>
                                <option value="combined">Combined</option>
                            </select>
                            <p v-if="form.errors.level" class="mt-1 text-xs text-red-600">{{ form.errors.level }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Province <span class="text-red-500">*</span></label>
                            <select v-model="form.province" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                                <option value="">Select province…</option>
                                <option v-for="p in provinces" :key="p" :value="p">{{ p }}</option>
                            </select>
                            <p v-if="form.errors.province" class="mt-1 text-xs text-red-600">{{ form.errors.province }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">District <span class="text-red-500">*</span></label>
                            <input v-model="form.district" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.district" class="mt-1 text-xs text-red-600">{{ form.errors.district }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Physical Address</label>
                        <textarea v-model="form.address" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">MoE Registration No.</label>
                        <input v-model="form.moe_registration_no" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                </section>

                <!-- Contact Details -->
                <section class="rounded-lg border bg-white p-6 shadow-sm space-y-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Contact Details</h2>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Headteacher Name <span class="text-red-500">*</span></label>
                        <input v-model="form.headteacher_name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                        <p v-if="form.errors.headteacher_name" class="mt-1 text-xs text-red-600">{{ form.errors.headteacher_name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Contact Phone <span class="text-red-500">*</span></label>
                            <input v-model="form.contact_phone" type="tel" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.contact_phone" class="mt-1 text-xs text-red-600">{{ form.errors.contact_phone }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Contact Email <span class="text-red-500">*</span></label>
                            <input v-model="form.contact_email" type="email" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.contact_email" class="mt-1 text-xs text-red-600">{{ form.errors.contact_email }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Mobile Money Number (for billing)</label>
                        <input v-model="form.mobile_money_number" type="tel" placeholder="097 / 096 / 076…" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p class="mt-1 text-xs text-gray-400">Used for monthly subscription payments via IzbPay.</p>
                    </div>
                </section>

                <!-- Modules -->
                <section class="rounded-lg border bg-white p-6 shadow-sm space-y-3">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Modules to Enable</h2>
                    <p class="text-xs text-gray-500">Academic management is always included. Select any additional modules.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label
                            v-for="(label, key) in modules"
                            :key="key"
                            class="flex items-center gap-3 rounded-md border p-3 cursor-pointer hover:bg-gray-50"
                            :class="form.modules_config.includes(key) ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200'"
                        >
                            <input
                                type="checkbox"
                                :checked="form.modules_config.includes(key)"
                                class="rounded border-gray-300 text-indigo-600"
                                @change="toggleModule(key)"
                            />
                            <span class="text-sm text-gray-700">{{ label }}</span>
                        </label>
                    </div>
                </section>

                <div class="flex justify-end gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ isEdit ? 'Re-submit Application' : 'Submit Application' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
