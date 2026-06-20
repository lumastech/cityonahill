<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

interface AvailableUser { id: number; name: string; email: string }
interface Subject { id: number; name: string }

const props = defineProps<{
    available_users: AvailableUser[]
    subjects: Subject[]
}>()

const mode = ref<'existing' | 'new'>('new')

const form = useForm({
    mode: 'new' as 'existing' | 'new',
    // existing user
    user_id: null as number | null,
    // new user
    name: '',
    email: '',
    // staff details
    employee_no: '',
    position: 'subject_teacher',
    employment_type: 'permanent',
    employment_date: new Date().toISOString().slice(0, 10),
    basic_salary: '',
    department: '',
    subjects_taught: [] as number[],
    napsa_no: '',
    tpin: '',
})

function setMode(m: 'existing' | 'new') {
    mode.value = m
    form.mode = m
}

function toggleSubject(id: number) {
    const idx = form.subjects_taught.indexOf(id)
    if (idx === -1) form.subjects_taught.push(id)
    else form.subjects_taught.splice(idx, 1)
}

function submit() {
    form.post(route('staff.store'))
}

const POSITIONS = [
    { value: 'headteacher',           label: 'Headteacher' },
    { value: 'deputy_headteacher',    label: 'Deputy Headteacher' },
    { value: 'class_teacher',         label: 'Class Teacher' },
    { value: 'subject_teacher',       label: 'Subject Teacher' },
    { value: 'bursar',                label: 'Bursar' },
    { value: 'librarian',             label: 'Librarian' },
    { value: 'boarding_master',       label: 'Boarding Master' },
    { value: 'transport_coordinator', label: 'Transport Coordinator' },
    { value: 'feeding_coordinator',   label: 'Feeding Coordinator' },
    { value: 'admin',                 label: 'Admin Staff' },
    { value: 'support',               label: 'Support Staff' },
    { value: 'counsellor',            label: 'Counsellor' },
]

const EMPLOYMENT_TYPES = [
    { value: 'permanent',  label: 'Permanent' },
    { value: 'contract',   label: 'Contract' },
    { value: 'temporary',  label: 'Temporary' },
    { value: 'volunteer',  label: 'Volunteer' },
]

const showSubjects = computed(() =>
    ['class_teacher', 'subject_teacher'].includes(form.position)
)
</script>

<template>
    <AppLayout title="Add Staff">
        <Head title="Add Staff Member" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
            <div class="mb-6 flex items-center gap-3">
                <Link :href="route('staff.index')" class="text-sm text-indigo-600 hover:underline">← Staff Directory</Link>
                <span class="text-gray-400">/</span>
                <h1 class="text-xl font-bold text-gray-900">Add Staff Member</h1>
            </div>

            <form class="space-y-6" @submit.prevent="submit">

                <!-- Mode toggle -->
                <div class="rounded-lg border bg-white p-5 shadow-sm">
                    <p class="mb-3 text-sm font-semibold text-gray-700">User Account</p>
                    <div class="flex gap-3 mb-4">
                        <button
                            type="button"
                            class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
                            :class="mode === 'new' ? 'bg-indigo-600 text-white' : 'border text-gray-600 hover:bg-gray-50'"
                            @click="setMode('new')"
                        >
                            Create new user
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
                            :class="mode === 'existing' ? 'bg-indigo-600 text-white' : 'border text-gray-600 hover:bg-gray-50'"
                            @click="setMode('existing')"
                        >
                            Link existing user
                        </button>
                    </div>

                    <!-- New user fields -->
                    <div v-if="mode === 'new'" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Full Name</label>
                            <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Email Address</label>
                            <input v-model="form.email" type="email" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
                        </div>
                        <p class="col-span-2 text-xs text-gray-400">A temporary password will be generated. The user can reset it on first login.</p>
                    </div>

                    <!-- Existing user picker -->
                    <div v-else>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Select User</label>
                        <select v-model="form.user_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                            <option :value="null">Choose a user…</option>
                            <option v-for="u in available_users" :key="u.id" :value="u.id">
                                {{ u.name }} ({{ u.email }})
                            </option>
                        </select>
                        <p v-if="!available_users.length" class="text-xs text-amber-600 mt-1">
                            All existing users are already staff members.
                        </p>
                        <p v-if="form.errors.user_id" class="text-xs text-red-600 mt-1">{{ form.errors.user_id }}</p>
                    </div>
                </div>

                <!-- Staff details -->
                <div class="rounded-lg border bg-white p-5 shadow-sm">
                    <p class="mb-4 text-sm font-semibold text-gray-700">Employment Details</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Employee No.</label>
                            <input v-model="form.employee_no" type="text" placeholder="e.g. EMP-001" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.employee_no" class="text-xs text-red-600 mt-1">{{ form.errors.employee_no }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Position</label>
                            <select v-model="form.position" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option v-for="p in POSITIONS" :key="p.value" :value="p.value">{{ p.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Employment Type</label>
                            <select v-model="form.employment_type" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option v-for="t in EMPLOYMENT_TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Employment Date</label>
                            <input v-model="form.employment_date" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.employment_date" class="text-xs text-red-600 mt-1">{{ form.errors.employment_date }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Basic Salary (ZMW)</label>
                            <input v-model="form.basic_salary" type="number" min="0" step="0.01" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                            <p v-if="form.errors.basic_salary" class="text-xs text-red-600 mt-1">{{ form.errors.basic_salary }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Department</label>
                            <input v-model="form.department" type="text" placeholder="e.g. Sciences" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">NAPSA No. <span class="text-gray-400">(optional)</span></label>
                            <input v-model="form.napsa_no" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">TPIN <span class="text-gray-400">(optional)</span></label>
                            <input v-model="form.tpin" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                    </div>
                </div>

                <!-- Subjects taught (only for teachers) -->
                <div v-if="showSubjects && subjects.length" class="rounded-lg border bg-white p-5 shadow-sm">
                    <p class="mb-3 text-sm font-semibold text-gray-700">Subjects Taught</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <label
                            v-for="s in subjects"
                            :key="s.id"
                            class="flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-sm transition-colors"
                            :class="form.subjects_taught.includes(s.id) ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                        >
                            <input
                                type="checkbox"
                                class="rounded"
                                :checked="form.subjects_taught.includes(s.id)"
                                @change="toggleSubject(s.id)"
                            />
                            {{ s.name }}
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('staff.index')" class="rounded-md border px-5 py-2 text-sm text-gray-600 hover:bg-gray-50">
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Add Staff Member
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
