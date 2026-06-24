<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import type { Pupil, Guardian } from '@/types/pupils'
import { usePupils } from '@/composables/usePupils'
import { useGuardians } from '@/composables/useGuardians'
import { usePermissions } from '@/composables/usePermissions'

interface Stream { id: number; name: string; grade_id: number; grade?: { name: string } }
interface Invoice {
    id: number
    term_id: number | null
    notes: string | null
    amount: number
    discount: number
    balance_due: number
    status: 'unpaid' | 'partial' | 'paid'
    due_date: string | null
    created_at: string
    term?: { id: number; name: string }
}
interface Payment {
    id: number
    invoice_id: number
    amount: number
    payment_method: string
    payment_date: string
    received_by: string | null
    invoice?: { term?: { id: number; name: string } }
}

interface AttendanceDay { date: string; status: 'present' | 'absent' | 'late' }

const props = defineProps<{
    pupil: Pupil
    invoices: Invoice[]
    recentPayments: Payment[]
    attendanceRecords: AttendanceDay[]
    termResults: Record<string, unknown>[]
    streams: Stream[]
}>()

function fmtDate(d: string | null | undefined): string {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

function fmtMoney(amount: number | string): string {
    return 'ZMW ' + Number(amount).toLocaleString('en-ZM', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const { statusClass, sexClass } = usePupils()
const { removeGuardian, relationshipLabel } = useGuardians()
const { can, hasRole } = usePermissions()

const canTransfer = computed(() =>
    hasRole('super-admin') || hasRole('school-admin') || hasRole('headteacher') ||
    hasRole('deputy-headteacher') || hasRole('class-teacher')
)

const activeTab = ref<'profile' | 'guardians' | 'academic' | 'fees' | 'attendance'>('profile')
const showAddGuardian = ref(false)

// ── Attendance calendar ────────────────────────────────────────────────────
const calendarDate = ref(new Date())

const calendarYear  = computed(() => calendarDate.value.getFullYear())
const calendarMonth = computed(() => calendarDate.value.getMonth()) // 0-indexed

const MONTH_NAMES = ['January','February','March','April','May','June',
                     'July','August','September','October','November','December']
const DAY_NAMES = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']

// Map date string → status for O(1) lookup
const attendanceMap = computed(() => {
    const m: Record<string, string> = {}
    for (const r of props.attendanceRecords) m[r.date] = r.status
    return m
})

// Build the grid: leading nulls for offset, then day numbers
const calendarGrid = computed(() => {
    const year  = calendarYear.value
    const month = calendarMonth.value
    const firstDow = new Date(year, month, 1).getDay()
    const daysInMonth = new Date(year, month + 1, 0).getDate()
    const cells: (number | null)[] = Array(firstDow).fill(null)
    for (let d = 1; d <= daysInMonth; d++) cells.push(d)
    return cells
})

const attendanceSummary = computed(() => {
    const year  = calendarYear.value
    const month = calendarMonth.value
    let present = 0, absent = 0, late = 0
    for (const r of props.attendanceRecords) {
        const d = new Date(r.date)
        if (d.getFullYear() === year && d.getMonth() === month) {
            if (r.status === 'present') present++
            else if (r.status === 'absent') absent++
            else if (r.status === 'late') late++
        }
    }
    const total = present + absent + late
    return { present, absent, late, total, pct: total > 0 ? Math.round((present / total) * 100) : null }
})

function prevMonth() {
    const d = new Date(calendarDate.value)
    d.setDate(1)
    d.setMonth(d.getMonth() - 1)
    calendarDate.value = d
}

function nextMonth() {
    const d = new Date(calendarDate.value)
    d.setDate(1)
    d.setMonth(d.getMonth() + 1)
    calendarDate.value = d
}

function isoDate(day: number): string {
    return `${calendarYear.value}-${String(calendarMonth.value + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`
}

function dayClass(day: number | null): string {
    if (!day) return ''
    const status = attendanceMap.value[isoDate(day)]
    if (status === 'present') return 'bg-green-100 text-green-800 font-semibold'
    if (status === 'absent')  return 'bg-red-100 text-red-800 font-semibold'
    if (status === 'late')    return 'bg-yellow-100 text-yellow-800 font-semibold'
    const dow = new Date(calendarYear.value, calendarMonth.value, day).getDay()
    return dow === 0 || dow === 6 ? 'text-gray-300' : 'text-gray-400'
}
const showTransferModal = ref(false)

const guardianForm = useForm({
    first_name: '',
    last_name: '',
    relationship: '' as Guardian['relationship'] | '',
    phone: '',
    phone2: '',
    email: '',
    nrc: '',
    occupation: '',
    employer: '',
    address: '',
    is_primary: false,
    is_emergency: false,
    can_pickup: true,
})

const transferForm = useForm({
    type: 'external' as 'external' | 'internal',
    to_school: '',
    transfer_date: new Date().toISOString().slice(0, 10),
    reason: '',
    stream_id: null as number | null,
})

function addGuardian() {
    guardianForm.post(route('pupils.guardians.store', props.pupil.id), {
        onSuccess: () => {
            showAddGuardian.value = false
            guardianForm.reset()
        },
    })
}

function submitTransfer() {
    transferForm.post(route('pupils.transfer', props.pupil.id), {
        onSuccess: () => {
            showTransferModal.value = false
        },
    })
}

function confirmRemoveGuardian(guardian: Guardian) {
    if (confirm(`Remove ${guardian.full_name} as guardian?`)) {
        removeGuardian(props.pupil.id, guardian.id)
    }
}

const TABS = [
    { key: 'profile', label: 'Profile' },
    { key: 'guardians', label: 'Guardians' },
    { key: 'academic', label: 'Academic' },
    { key: 'fees', label: 'Fees' },
    { key: 'attendance', label: 'Attendance' },
] as const
</script>

<template>
    <AppLayout :title="pupil.full_name">
        <Head :title="pupil.full_name" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">

            <!-- Pupil header -->
            <div class="bg-white rounded-lg shadow p-6 mb-4 flex items-center gap-6">
                <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-600">
                    {{ pupil.first_name[0] }}{{ pupil.last_name[0] }}
                </div>
                <div class="flex-1">
                    <h1 class="text-xl font-bold text-gray-900">{{ pupil.full_name }}</h1>
                    <p class="text-sm text-gray-500 font-mono">{{ pupil.admission_no }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="inline-flex text-xs px-2 py-0.5 rounded-full font-medium" :class="statusClass(pupil.status)">
                            {{ pupil.status }}
                        </span>
                        <span class="inline-flex text-xs px-2 py-0.5 rounded-full font-medium" :class="sexClass(pupil.sex)">
                            {{ pupil.sex }}
                        </span>
                        <span class="text-xs text-gray-400">Age {{ pupil.age }}</span>
                    </div>
                </div>
                <Link v-if="can('pupil.update')" :href="route('pupils.edit', pupil.id)" class="text-sm text-indigo-600 hover:underline">
                    Edit Profile
                </Link>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-4">
                <nav class="flex -mb-px gap-6">
                    <button
                        v-for="tab in TABS"
                        :key="tab.key"
                        class="py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === tab.key
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700'"
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Profile Tab -->
            <div v-if="activeTab === 'profile'" class="bg-white rounded-lg shadow p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Full Name</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">{{ pupil.full_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Date of Birth</dt>
                        <dd class="mt-0.5 text-gray-900">{{ fmtDate(pupil.dob) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Sex</dt>
                        <dd class="mt-0.5 text-gray-900 capitalize">{{ pupil.sex }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Nationality</dt>
                        <dd class="mt-0.5 text-gray-900">{{ pupil.nationality }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Religion</dt>
                        <dd class="mt-0.5 text-gray-900">{{ pupil.religion ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tribe</dt>
                        <dd class="mt-0.5 text-gray-900">{{ pupil.tribe ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Blood Group</dt>
                        <dd class="mt-0.5 text-gray-900">{{ pupil.blood_group ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Disability</dt>
                        <dd class="mt-0.5 text-gray-900 capitalize">{{ pupil.disability }}</dd>
                    </div>
                    <div v-if="pupil.disability_details">
                        <dt class="text-gray-500">Disability Details</dt>
                        <dd class="mt-0.5 text-gray-900">{{ pupil.disability_details }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Date of Admission</dt>
                        <dd class="mt-0.5 text-gray-900">{{ fmtDate(pupil.date_of_admission) }}</dd>
                    </div>
                    <div v-if="pupil.previous_school">
                        <dt class="text-gray-500">Previous School</dt>
                        <dd class="mt-0.5 text-gray-900">{{ pupil.previous_school }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Guardians Tab -->
            <div v-if="activeTab === 'guardians'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-gray-800">Guardians</h2>
                    <button
                        class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700"
                        @click="showAddGuardian = !showAddGuardian"
                    >
                        + Add Guardian
                    </button>
                </div>

                <!-- Add guardian form -->
                <div v-if="showAddGuardian" class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-medium text-gray-800 mb-3">Add Guardian</h3>
                    <form @submit.prevent="addGuardian" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">First Name *</label>
                                <input v-model="guardianForm.first_name" type="text" class="mt-1 w-full border-gray-300 rounded-md text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Last Name *</label>
                                <input v-model="guardianForm.last_name" type="text" class="mt-1 w-full border-gray-300 rounded-md text-sm" required />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Relationship *</label>
                                <select v-model="guardianForm.relationship" class="mt-1 w-full border-gray-300 rounded-md text-sm">
                                    <option value="">Select…</option>
                                    <option value="father">Father</option>
                                    <option value="mother">Mother</option>
                                    <option value="guardian">Guardian</option>
                                    <option value="grandparent">Grandparent</option>
                                    <option value="sibling">Sibling</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Phone *</label>
                                <input v-model="guardianForm.phone" type="tel" class="mt-1 w-full border-gray-300 rounded-md text-sm" required />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Email</label>
                            <input v-model="guardianForm.email" type="email" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                        </div>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-1.5 text-xs text-gray-700">
                                <input v-model="guardianForm.is_primary" type="checkbox" class="rounded" />
                                Primary
                            </label>
                            <label class="flex items-center gap-1.5 text-xs text-gray-700">
                                <input v-model="guardianForm.is_emergency" type="checkbox" class="rounded" />
                                Emergency
                            </label>
                            <label class="flex items-center gap-1.5 text-xs text-gray-700">
                                <input v-model="guardianForm.can_pickup" type="checkbox" class="rounded" />
                                Can pickup
                            </label>
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button type="button" class="text-sm text-gray-600 hover:underline" @click="showAddGuardian = false">Cancel</button>
                            <button type="submit" :disabled="guardianForm.processing" class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50">Add</button>
                        </div>
                    </form>
                </div>

                <!-- Guardian cards -->
                <div v-if="!pupil.guardians?.length" class="bg-white rounded-lg shadow p-6 text-center text-gray-400 text-sm">
                    No guardians added yet.
                </div>
                <div v-for="guardian in pupil.guardians" :key="guardian.id" class="bg-white rounded-lg shadow p-4 flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="font-medium text-gray-900">{{ guardian.full_name }}</p>
                            <span v-if="guardian.pivot?.is_primary" class="text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded">Primary</span>
                            <span v-if="guardian.pivot?.is_emergency" class="text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded">Emergency</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">{{ relationshipLabel(guardian.relationship) }} · {{ guardian.phone }}</p>
                        <p v-if="guardian.email" class="text-xs text-gray-400">{{ guardian.email }}</p>
                    </div>
                    <button
                        class="text-sm text-red-500 hover:text-red-700"
                        @click="confirmRemoveGuardian(guardian)"
                    >
                        Remove
                    </button>
                </div>
            </div>

            <!-- Academic Tab -->
            <div v-if="activeTab === 'academic'" class="bg-white rounded-lg shadow p-6 space-y-4">
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Grade</p>
                        <p class="font-medium text-gray-900">{{ pupil.grade?.name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Stream</p>
                        <p class="font-medium text-gray-900">{{ pupil.stream?.name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Academic Year</p>
                        <p class="font-medium text-gray-900">{{ pupil.academic_year?.name ?? '—' }}</p>
                    </div>
                </div>

                <div class="pt-4 border-t flex justify-between items-center">
                    <h3 class="font-medium text-gray-800">Transfer History</h3>
                    <button
                        v-if="pupil.status === 'active' && canTransfer"
                        class="text-sm text-indigo-600 border border-indigo-300 px-3 py-1.5 rounded hover:bg-indigo-50"
                        @click="showTransferModal = true"
                    >
                        Transfer Pupil
                    </button>
                </div>

                <div v-if="!pupil.transfers?.length" class="text-sm text-gray-400">No transfers recorded.</div>
                <div v-for="t in pupil.transfers" :key="t.id" class="text-sm border-l-2 border-gray-200 pl-3 py-1">
                    <p class="font-medium">{{ t.from_school }} → {{ t.to_school }}</p>
                    <p class="text-gray-500">{{ fmtDate(t.transfer_date) }} <span v-if="t.reason">· {{ t.reason }}</span></p>
                </div>
            </div>

            <!-- Fees Tab -->
            <div v-if="activeTab === 'fees'" class="space-y-4">

                <!-- Balance summary -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Billed</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">
                            {{ fmtMoney(invoices.reduce((s, i) => s + Number(i.amount), 0)) }}
                        </p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-xs font-medium uppercase tracking-wide text-green-600">Total Paid</p>
                        <p class="mt-1 text-2xl font-bold text-green-700">
                            {{ fmtMoney(recentPayments.reduce((s, p) => s + Number(p.amount), 0)) }}
                        </p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-xs font-medium uppercase tracking-wide text-red-500">Outstanding</p>
                        <p class="mt-1 text-2xl font-bold text-red-600">
                            {{ fmtMoney(invoices.reduce((s, i) => s + Number(i.balance_due), 0)) }}
                        </p>
                    </div>
                </div>

                <!-- Invoices -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                        <p class="font-semibold text-gray-800 text-sm">Invoices</p>
                        <Link :href="route('fee-invoices.index')" class="text-xs text-indigo-600 hover:underline">
                            View all
                        </Link>
                    </div>
                    <div v-if="invoices.length" class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500">Term</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500">Description</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">Amount</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">Balance</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500">Due</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500">Status</th>
                                    <th class="px-4 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="inv in invoices" :key="inv.id">
                                    <td class="px-4 py-2.5 text-gray-700 whitespace-nowrap">{{ inv.term?.name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-gray-500 text-xs">{{ inv.notes ?? 'School fees' }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-700">{{ fmtMoney(inv.amount) }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium" :class="Number(inv.balance_due) > 0 ? 'text-red-600' : 'text-gray-400'">
                                        {{ fmtMoney(inv.balance_due) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ inv.due_date ? fmtDate(inv.due_date) : '—' }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                            :class="{
                                                'bg-green-100 text-green-700': inv.status === 'paid',
                                                'bg-yellow-100 text-yellow-700': inv.status === 'partial',
                                                'bg-red-100 text-red-700': inv.status === 'unpaid',
                                            }">
                                            {{ inv.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <Link :href="route('fee-invoices.show', inv.id)" class="text-xs text-indigo-600 hover:underline">View</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="px-5 py-8 text-center text-sm text-gray-400">No invoices yet.</p>
                </div>

                <!-- Recent payments -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <p class="px-5 py-3 border-b border-gray-100 font-semibold text-gray-800 text-sm">Recent Payments</p>
                    <div v-if="recentPayments.length" class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500">Date</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500">Term</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500">Amount</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500">Method</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="pay in recentPayments" :key="pay.id">
                                    <td class="px-4 py-2.5 text-gray-700">{{ fmtDate(pay.payment_date) }}</td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ pay.invoice?.term?.name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium text-green-700">{{ fmtMoney(pay.amount) }}</td>
                                    <td class="px-4 py-2.5 text-gray-500 capitalize">{{ pay.payment_method?.replace('_', ' ') ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="px-5 py-8 text-center text-sm text-gray-400">No payments recorded.</p>
                </div>
            </div>

            <!-- Attendance Tab -->
            <div v-if="activeTab === 'attendance'" class="space-y-4">

                <!-- Month summary stats -->
                <div class="grid grid-cols-4 gap-3">
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Days Recorded</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ attendanceSummary.total }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-xs font-medium uppercase tracking-wide text-green-600">Present</p>
                        <p class="mt-1 text-2xl font-bold text-green-700">{{ attendanceSummary.present }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-xs font-medium uppercase tracking-wide text-red-500">Absent</p>
                        <p class="mt-1 text-2xl font-bold text-red-600">{{ attendanceSummary.absent }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 text-center">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Rate</p>
                        <p class="mt-1 text-2xl font-bold"
                            :class="attendanceSummary.pct === null ? 'text-gray-300' : attendanceSummary.pct >= 80 ? 'text-green-600' : 'text-red-600'">
                            {{ attendanceSummary.pct !== null ? attendanceSummary.pct + '%' : '—' }}
                        </p>
                    </div>
                </div>

                <!-- Calendar -->
                <div class="bg-white rounded-lg shadow p-5">

                    <!-- Month navigation -->
                    <div class="mb-4 flex items-center justify-between">
                        <button class="rounded-md border border-gray-200 px-3 py-1 text-sm text-gray-600 hover:bg-gray-50" @click="prevMonth">← Prev</button>
                        <p class="font-semibold text-gray-800">{{ MONTH_NAMES[calendarMonth] }} {{ calendarYear }}</p>
                        <button class="rounded-md border border-gray-200 px-3 py-1 text-sm text-gray-600 hover:bg-gray-50" @click="nextMonth">Next →</button>
                    </div>

                    <!-- Day-of-week headers -->
                    <div class="mb-1 grid grid-cols-7 text-center">
                        <span v-for="d in DAY_NAMES" :key="d" class="text-xs font-medium text-gray-400">{{ d }}</span>
                    </div>

                    <!-- Day cells -->
                    <div class="grid grid-cols-7 gap-1">
                        <div v-for="(day, i) in calendarGrid" :key="i"
                            class="flex aspect-square items-center justify-center rounded-md text-sm"
                            :class="day ? dayClass(day) : ''">
                            {{ day ?? '' }}
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="mt-4 flex items-center gap-4 text-xs text-gray-500">
                        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-green-200"></span> Present</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-red-200"></span> Absent</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded bg-yellow-200"></span> Late</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer Modal -->
        <div
            v-if="showTransferModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showTransferModal = false"
        >
            <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
                <h2 class="text-lg font-semibold mb-4">Transfer Pupil</h2>

                <form @submit.prevent="submitTransfer" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Transfer Type</label>
                        <div class="flex gap-4 mt-1">
                            <label class="flex items-center gap-1.5 text-sm">
                                <input v-model="transferForm.type" type="radio" value="external" />
                                External (leaves school)
                            </label>
                            <label class="flex items-center gap-1.5 text-sm">
                                <input v-model="transferForm.type" type="radio" value="internal" />
                                Internal (stream change)
                            </label>
                        </div>
                        <p v-if="transferForm.errors.type" class="mt-1 text-xs text-red-600">{{ transferForm.errors.type }}</p>
                    </div>

                    <div v-if="transferForm.type === 'external'">
                        <label class="block text-sm font-medium text-gray-700">To School *</label>
                        <input v-model="transferForm.to_school" type="text" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                        <p v-if="transferForm.errors.to_school" class="mt-1 text-xs text-red-600">{{ transferForm.errors.to_school }}</p>
                    </div>

                    <div v-if="transferForm.type === 'internal'">
                        <label class="block text-sm font-medium text-gray-700">New Stream *</label>
                        <select v-model="transferForm.stream_id" class="mt-1 w-full border-gray-300 rounded-md text-sm">
                            <option :value="null">Select stream…</option>
                            <option v-for="s in streams" :key="s.id" :value="s.id">{{ s.grade?.name }} {{ s.name }}</option>
                        </select>
                        <p v-if="transferForm.errors.stream_id" class="mt-1 text-xs text-red-600">{{ transferForm.errors.stream_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Transfer Date *</label>
                        <input v-model="transferForm.transfer_date" type="date" class="mt-1 w-full border-gray-300 rounded-md text-sm" required />
                        <p v-if="transferForm.errors.transfer_date" class="mt-1 text-xs text-red-600">{{ transferForm.errors.transfer_date }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reason</label>
                        <textarea v-model="transferForm.reason" rows="2" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                        <p v-if="transferForm.errors.reason" class="mt-1 text-xs text-red-600">{{ transferForm.errors.reason }}</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded" @click="showTransferModal = false">Cancel</button>
                        <button type="submit" :disabled="transferForm.processing" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50">
                            Confirm Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
