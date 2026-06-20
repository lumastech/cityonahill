<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useHR, MONTH_NAMES, POSITION_LABELS, STATUS_COLORS } from '@/composables/useHR'
import type { Leave, LeaveType, Payroll, Staff } from '@/types/hr'

interface Subject { id: number; name: string }

const props = defineProps<{
    staff: Staff
    leave_types: LeaveType[]
    leave_balance: Record<number, number>
    subjects: Subject[]
}>()

const { positionLabel, positionColor, statusColor, formatZmw } = useHR()
const activeTab = ref<'employment' | 'leaves' | 'payroll'>('employment')
const editing = ref(false)

const form = useForm({
    position:        props.staff.position ?? '',
    employment_type: props.staff.employment_type ?? '',
    department:      props.staff.department ?? '',
    basic_salary:    props.staff.basic_salary ?? '',
    status:          props.staff.status ?? 'active',
    bank:            props.staff.bank ?? '',
    bank_account:    props.staff.bank_account ?? '',
    bank_branch:     props.staff.bank_branch ?? '',
    subjects_taught: (props.staff.subjects_taught ?? []) as number[],
})

const isTeacher = computed(() =>
    ['class_teacher', 'subject_teacher'].includes(form.position),
)

function toggleSubject(id: number) {
    const idx = form.subjects_taught.indexOf(id)
    if (idx === -1) form.subjects_taught.push(id)
    else form.subjects_taught.splice(idx, 1)
}

function save() {
    form.put(route('staff.update', props.staff.id), {
        onSuccess: () => { editing.value = false },
    })
}

const leaveStatusColor: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-600',
}
</script>

<template>
    <AppLayout>
    <Head :title="staff.user?.name ?? 'Staff Profile'" />
    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex items-center gap-4">
                <a :href="route('staff.index')" class="text-sm text-indigo-600 hover:underline">← Directory</a>
                <div class="flex-1">
                    <h1 class="text-2xl font-semibold text-gray-900">{{ staff.user?.name }}</h1>
                    <div class="mt-1 flex items-center gap-2">
                        <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', positionColor(staff.position)]">
                            {{ positionLabel(staff.position) }}
                        </span>
                        <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', statusColor(staff.status)]">
                            {{ staff.status }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-4 flex gap-4 border-b border-gray-200">
                <button v-for="tab in (['employment', 'leaves', 'payroll'] as const)" :key="tab"
                    :class="['border-b-2 px-4 py-2 text-sm font-medium capitalize', activeTab === tab ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']"
                    @click="activeTab = tab">
                    {{ tab }}
                </button>
            </div>

            <!-- Employment tab -->
            <div v-if="activeTab === 'employment'" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">

                <!-- View mode -->
                <div v-if="!editing">
                    <div class="mb-4 flex justify-end">
                        <button
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
                            @click="editing = true"
                        >
                            Edit
                        </button>
                    </div>
                    <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                        <div><dt class="text-gray-500">Employee No</dt><dd class="font-medium">{{ staff.employee_no }}</dd></div>
                        <div><dt class="text-gray-500">Position</dt><dd class="font-medium">{{ positionLabel(staff.position) }}</dd></div>
                        <div><dt class="text-gray-500">Employment Type</dt><dd class="font-medium capitalize">{{ staff.employment_type }}</dd></div>
                        <div><dt class="text-gray-500">Start Date</dt><dd class="font-medium">{{ staff.employment_date }}</dd></div>
                        <div><dt class="text-gray-500">Basic Salary</dt><dd class="font-medium">{{ formatZmw(staff.basic_salary) }}</dd></div>
                        <div><dt class="text-gray-500">Department</dt><dd class="font-medium">{{ staff.department ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">Status</dt>
                            <dd><span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusColor(staff.status)">{{ staff.status }}</span></dd>
                        </div>
                        <div><dt class="text-gray-500">NAPSA No</dt><dd class="font-medium">{{ staff.napsa_no ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">TPIN</dt><dd class="font-medium">{{ staff.tpin ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">Email</dt><dd class="font-medium">{{ staff.user?.email }}</dd></div>
                        <div><dt class="text-gray-500">Bank</dt><dd class="font-medium">{{ staff.bank ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">Account No</dt><dd class="font-medium">{{ staff.bank_account ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">Branch</dt><dd class="font-medium">{{ staff.bank_branch ?? '—' }}</dd></div>
                    </dl>
                </div>

                <!-- Edit mode -->
                <form v-else class="space-y-5" @submit.prevent="save">
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Position</label>
                            <select v-model="form.position" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option v-for="(label, val) in POSITION_LABELS" :key="val" :value="val">{{ label }}</option>
                            </select>
                            <p v-if="form.errors.position" class="mt-1 text-xs text-red-600">{{ form.errors.position }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Employment Type</label>
                            <select v-model="form.employment_type" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="permanent">Permanent</option>
                                <option value="contract">Contract</option>
                                <option value="part_time">Part-time</option>
                                <option value="volunteer">Volunteer</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                            <select v-model="form.status" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="active">Active</option>
                                <option value="on_leave">On Leave</option>
                                <option value="suspended">Suspended</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Department</label>
                            <input v-model="form.department" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Basic Salary (ZMW)</label>
                            <input v-model="form.basic_salary" type="number" min="0" step="0.01" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="form.errors.basic_salary" class="mt-1 text-xs text-red-600">{{ form.errors.basic_salary }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Bank</label>
                            <input v-model="form.bank" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Account No</label>
                            <input v-model="form.bank_account" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
                            <input v-model="form.bank_branch" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                    </div>

                    <!-- Subjects taught (teachers only) -->
                    <div v-if="isTeacher">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Subjects Taught</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="s in subjects"
                                :key="s.id"
                                type="button"
                                class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                                :class="form.subjects_taught.includes(s.id)
                                    ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                    : 'border-gray-300 text-gray-600 hover:border-gray-400'"
                                @click="toggleSubject(s.id)"
                            >
                                {{ s.name }}
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                        <button type="button" class="rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50" @click="editing = false">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Leave tab -->
            <div v-else-if="activeTab === 'leaves'">
                <div class="mb-4 flex flex-wrap gap-3">
                    <div v-for="lt in leave_types" :key="lt.id" class="rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm text-sm">
                        <p class="font-medium text-gray-900">{{ lt.name }}</p>
                        <p class="text-gray-500">{{ leave_balance[lt.id] ?? 0 }} / {{ lt.days_per_year }} days remaining</p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Type</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">From</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">To</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Days</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="leave in staff.leaves" :key="leave.id">
                                <td class="px-4 py-3 text-gray-900">{{ leave.leave_type?.name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ leave.start_date }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ leave.end_date }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ leave.total_days }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', leaveStatusColor[leave.status]]">
                                        {{ leave.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!staff.leaves?.length">
                                <td colspan="5" class="px-4 py-6 text-center text-gray-400">No leave history.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payroll tab -->
            <div v-else>
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Period</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Gross</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">NAPSA</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">PAYE</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Net Pay</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Paid</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="p in staff.payrolls" :key="p.id">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ MONTH_NAMES[p.month] }} {{ p.year }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ formatZmw(p.basic_salary) }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ formatZmw(p.napsa_employee) }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ formatZmw(p.paye) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ formatZmw(p.net_pay) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ p.paid_at ? p.paid_at.slice(0, 10) : '—' }}</td>
                            </tr>
                            <tr v-if="!staff.payrolls?.length">
                                <td colspan="6" class="px-4 py-6 text-center text-gray-400">No payroll records.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
