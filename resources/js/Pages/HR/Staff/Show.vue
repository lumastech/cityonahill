<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useHR, MONTH_NAMES } from '@/composables/useHR'
import type { Leave, LeaveType, Payroll, Staff } from '@/types/hr'

const props = defineProps<{
    staff: Staff
    leave_types: LeaveType[]
    leave_balance: Record<number, number>
}>()

const { positionLabel, positionColor, statusColor, formatZmw } = useHR()
const activeTab = ref<'employment' | 'leaves' | 'payroll'>('employment')

const leaveStatusColor: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-600',
}
</script>

<template>
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
                <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-gray-500">Employee No</dt><dd class="font-medium">{{ staff.employee_no }}</dd></div>
                    <div><dt class="text-gray-500">Employment Type</dt><dd class="font-medium capitalize">{{ staff.employment_type }}</dd></div>
                    <div><dt class="text-gray-500">Start Date</dt><dd class="font-medium">{{ staff.employment_date }}</dd></div>
                    <div><dt class="text-gray-500">Basic Salary</dt><dd class="font-medium">{{ formatZmw(staff.basic_salary) }}</dd></div>
                    <div><dt class="text-gray-500">Department</dt><dd class="font-medium">{{ staff.department ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">NAPSA No</dt><dd class="font-medium">{{ staff.napsa_no ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">TPIN</dt><dd class="font-medium">{{ staff.tpin ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Email</dt><dd class="font-medium">{{ staff.user?.email }}</dd></div>
                </dl>
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
</template>
