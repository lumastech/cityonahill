<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { SharedProps } from '@/types/shared'
import { fmtDate } from '@/utils/date'

// ── Types ──────────────────────────────────────────────────────────────────

interface PupilStats   { active: number; male: number; female: number }
interface AttendStat   { present: number; absent: number; total: number; percentage: number | null; sessions?: number; recorded?: boolean }
interface FinanceStat  { outstanding_amount: number; outstanding_count: number }
interface Notice       { id: number; title: string; audience: string; published_at: string }
interface Term         { id: number; name: string; start_date: string; end_date: string; number: number }
interface Assessment   { id: number; name: string; type: string; date: string; max_marks: number; subject?: { name: string }; stream?: { name: string } }
interface Stream       { id: number; name: string; grade?: { id: number; name: string; grade_number: number } }
interface Payment      { amount: number; payment_date: string; method: string; pupil_name: string }
interface Borrowing    { id: number; book?: { title: string }; issued_by?: { name: string }; borrowed_date: string; due_date: string; status: string }

interface AdminStats {
    pupils: PupilStats
    staff: number
    attendance: AttendStat
    finance: FinanceStat
    pending_assessments: number
    notices: Notice[]
    current_term: Term | null
    by_grade: { grade_id: number; count: number; grade?: { name: string } }[]
}

interface ClassTeacherStats {
    stream: Stream | null
    pupil_count: number
    attendance: AttendStat
    assessments: Assessment[]
    notices: Notice[]
}

interface SubjectTeacherStats {
    my_assessments: Assessment[]
    pending_scoring: number
    streams: Stream[]
    notices: Notice[]
}

interface FinanceStats {
    outstanding_count: number
    outstanding_amount: number
    collected_this_month: number
    expenses_this_month: number
    recent_payments: Payment[]
    notices: Notice[]
}

interface LibrarianStats {
    total_books: number
    active_borrowings: number
    overdue: number
    recent_borrowings: Borrowing[]
    notices: Notice[]
}

interface BoardingStats {
    total_beds: number
    occupied_beds: number
    available_beds: number
    occupancy_pct: number
    notices: Notice[]
}

interface TransportStats { routes: number; assigned_pupils: number; notices: Notice[] }
interface FeedingStats   { today_session: unknown; low_stock: { item_name: string; quantity_kg: number }[]; notices: Notice[] }

type DashType = 'admin' | 'class_teacher' | 'subject_teacher' | 'finance' | 'librarian' | 'boarding' | 'transport' | 'feeding' | 'default'

const props = defineProps<{
    type: DashType
    stats: AdminStats | ClassTeacherStats | SubjectTeacherStats | FinanceStats | LibrarianStats | BoardingStats | TransportStats | FeedingStats | Record<string, never>
}>()

const page  = usePage<SharedProps>()
const user  = computed(() => page.props.auth?.user)
const school = computed(() => page.props.current_school)

// typed accessors
const adminStats         = computed(() => props.stats as AdminStats)
const classStats         = computed(() => props.stats as ClassTeacherStats)
const subjectStats       = computed(() => props.stats as SubjectTeacherStats)
const financeStats       = computed(() => props.stats as FinanceStats)
const libStats           = computed(() => props.stats as LibrarianStats)
const boardingStats      = computed(() => props.stats as BoardingStats)
const transportStats     = computed(() => props.stats as TransportStats)
const feedingStats       = computed(() => props.stats as FeedingStats)

function greeting(): string {
    const h = new Date().getHours()
    if (h < 12) return 'Good morning'
    if (h < 17) return 'Good afternoon'
    return 'Good evening'
}

function fmtDate(d: string): string {
    return new Date(d).toLocaleDateString('en-ZM', { day: 'numeric', month: 'short', year: 'numeric' })
}

function fmtMoney(n: number): string {
    return 'ZMW ' + n.toLocaleString('en-ZM', { minimumFractionDigits: 2 })
}

const TYPE_LABELS: Record<string, string> = {
    ca_test: 'CA Test', assignment: 'Assignment', practical: 'Practical',
    mid_term: 'Mid-Term', end_of_term: 'End of Term',
}

const TYPE_COLORS: Record<string, string> = {
    ca_test: 'bg-blue-100 text-blue-700', assignment: 'bg-purple-100 text-purple-700',
    practical: 'bg-teal-100 text-teal-700', mid_term: 'bg-orange-100 text-orange-700',
    end_of_term: 'bg-red-100 text-red-700',
}
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

            <!-- Greeting -->
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ greeting() }}, {{ user?.name?.split(' ')[0] }} 👋
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ school?.name }} &nbsp;·&nbsp;
                        <span v-if="page.props.terms?.length" class="font-medium text-indigo-600">
                            {{ page.props.terms[0]?.name }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════
                 ADMIN / HEADTEACHER
            ════════════════════════════════════════════════════════════════ -->
            <template v-if="type === 'admin'">

                <!-- KPI row -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Active Pupils</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ adminStats.pupils.active }}</p>
                        <div class="mt-1 flex gap-3 text-xs text-gray-500">
                            <span class="text-blue-600 font-medium">♂ {{ adminStats.pupils.male }}</span>
                            <span class="text-pink-600 font-medium">♀ {{ adminStats.pupils.female }}</span>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Active Staff</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ adminStats.staff }}</p>
                        <Link :href="route('staff.index')" class="mt-1 text-xs text-indigo-600 hover:underline">View directory</Link>
                    </div>

                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Attendance Today</p>
                        <p class="mt-2 text-3xl font-bold" :class="adminStats.attendance.percentage !== null ? (adminStats.attendance.percentage >= 80 ? 'text-green-600' : 'text-red-600') : 'text-gray-400'">
                            {{ adminStats.attendance.percentage !== null ? adminStats.attendance.percentage + '%' : '—' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ adminStats.attendance.present }} present / {{ adminStats.attendance.absent }} absent
                            <span v-if="adminStats.attendance.sessions" class="text-gray-400">({{ adminStats.attendance.sessions }} classes)</span>
                        </p>
                    </div>

                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Fees Outstanding</p>
                        <p class="mt-2 text-2xl font-bold text-red-600">{{ fmtMoney(adminStats.finance.outstanding_amount) }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ adminStats.finance.outstanding_count }} unpaid invoices</p>
                    </div>
                </div>

                <!-- Second row: Assessments alert + Term info -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                    <!-- Pending assessments alert -->
                    <div class="rounded-xl border bg-amber-50 border-amber-200 p-5 shadow-sm flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                            <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-amber-800">{{ adminStats.pending_assessments }} Assessment{{ adminStats.pending_assessments === 1 ? '' : 's' }} Pending</p>
                            <p class="text-xs text-amber-600 mt-0.5">Scores not yet entered</p>
                            <Link :href="route('assessments.index')" class="mt-2 inline-block text-xs font-medium text-amber-700 underline">View assessments</Link>
                        </div>
                    </div>

                    <!-- Current term -->
                    <div v-if="adminStats.current_term" class="rounded-xl border bg-indigo-50 border-indigo-200 p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-indigo-500">Current Term</p>
                        <p class="mt-1 text-lg font-bold text-indigo-900">{{ adminStats.current_term.name }}</p>
                        <p class="text-xs text-indigo-600 mt-1">
                            {{ fmtDate(adminStats.current_term.start_date) }} – {{ fmtDate(adminStats.current_term.end_date) }}
                        </p>
                        <Link :href="route('attendance.school-summary')" class="mt-2 inline-block text-xs font-medium text-indigo-700 underline">Attendance summary</Link>
                    </div>

                    <!-- Quick links -->
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-3">Quick Links</p>
                        <div class="space-y-2">
                            <Link :href="route('pupils.create')" class="flex items-center gap-2 text-sm text-gray-700 hover:text-indigo-700">
                                <span class="flex h-6 w-6 items-center justify-center rounded bg-indigo-100 text-indigo-600 text-xs">+</span> Admit Pupil
                            </Link>
                            <Link :href="route('fee-invoices.index')" class="flex items-center gap-2 text-sm text-gray-700 hover:text-indigo-700">
                                <span class="flex h-6 w-6 items-center justify-center rounded bg-green-100 text-green-600 text-xs">$</span> Fee Invoices
                            </Link>
                            <Link :href="route('assessments.index')" class="flex items-center gap-2 text-sm text-gray-700 hover:text-indigo-700">
                                <span class="flex h-6 w-6 items-center justify-center rounded bg-orange-100 text-orange-600 text-xs">✓</span> Assessments
                            </Link>
                            <Link :href="route('report-cards.index')" class="flex items-center gap-2 text-sm text-gray-700 hover:text-indigo-700">
                                <span class="flex h-6 w-6 items-center justify-center rounded bg-purple-100 text-purple-600 text-xs">📋</span> Report Cards
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Notices + Grade breakdown -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                    <!-- Notices -->
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="font-semibold text-gray-800">Recent Notices</p>
                            <Link :href="route('notices.index')" class="text-xs text-indigo-600 hover:underline">All notices</Link>
                        </div>
                        <div v-if="adminStats.notices.length" class="space-y-3">
                            <div v-for="n in adminStats.notices" :key="n.id" class="border-l-2 border-indigo-200 pl-3">
                                <p class="text-sm font-medium text-gray-800">{{ n.title }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ fmtDate(n.published_at) }} · {{ n.audience }}</p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400">No published notices.</p>
                    </div>

                    <!-- Pupils by grade -->
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="mb-3 font-semibold text-gray-800">Pupils by Grade</p>
                        <div v-if="adminStats.by_grade.length" class="space-y-2">
                            <div v-for="row in adminStats.by_grade" :key="row.grade_id" class="flex items-center gap-3">
                                <span class="w-20 shrink-0 text-xs font-medium text-gray-600">{{ row.grade?.name ?? 'Unknown' }}</span>
                                <div class="relative flex-1 h-4 rounded-full bg-gray-100">
                                    <div
                                        class="h-4 rounded-full bg-indigo-400 transition-all"
                                        :style="{ width: Math.round((row.count / adminStats.pupils.active) * 100) + '%' }"
                                    />
                                </div>
                                <span class="w-8 text-right text-xs text-gray-500">{{ row.count }}</span>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400">No data.</p>
                    </div>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════
                 CLASS TEACHER
            ════════════════════════════════════════════════════════════════ -->
            <template v-else-if="type === 'class_teacher'">
                <div v-if="classStats.stream" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="col-span-2 rounded-xl border bg-indigo-50 border-indigo-200 p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-indigo-500">Your Class</p>
                        <p class="mt-1 text-2xl font-bold text-indigo-900">{{ classStats.stream.grade?.name }} {{ classStats.stream.name }}</p>
                        <p class="mt-1 text-sm text-indigo-600">{{ classStats.pupil_count }} active pupils</p>
                    </div>
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Today's Attendance</p>
                        <p v-if="classStats.attendance.recorded" class="mt-2 text-3xl font-bold" :class="classStats.attendance.total > 0 && (classStats.attendance.present / classStats.attendance.total) >= 0.8 ? 'text-green-600' : 'text-red-600'">
                            {{ classStats.attendance.total > 0 ? Math.round((classStats.attendance.present / classStats.attendance.total) * 100) : 0 }}%
                        </p>
                        <p v-else class="mt-2 text-sm text-gray-400">Not taken yet</p>
                        <p class="mt-1 text-xs text-gray-500">{{ classStats.attendance.present }} present · {{ classStats.attendance.absent }} absent</p>
                    </div>
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-3">Quick Actions</p>
                        <div class="space-y-2">
                            <Link :href="route('attendance.index')" class="block rounded-md bg-indigo-600 px-3 py-1.5 text-center text-xs font-medium text-white hover:bg-indigo-700">
                                Take Attendance
                            </Link>
                            <Link :href="route('assessments.index')" class="block rounded-md border px-3 py-1.5 text-center text-xs font-medium text-gray-700 hover:bg-gray-50">
                                View Assessments
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center text-amber-700">
                    You have not been assigned as class teacher to any stream yet.
                </div>

                <!-- Assessments list -->
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <p class="font-semibold text-gray-800">Recent Assessments</p>
                        <Link :href="route('assessments.index')" class="text-xs text-indigo-600 hover:underline">All</Link>
                    </div>
                    <div v-if="classStats.assessments.length" class="divide-y divide-gray-100">
                        <div v-for="a in classStats.assessments" :key="a.id" class="flex items-center justify-between py-2.5">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ a.name }}</p>
                                <p class="text-xs text-gray-500">{{ a.subject?.name }} · {{ fmtDate(a.date) }}</p>
                            </div>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="TYPE_COLORS[a.type] ?? 'bg-gray-100 text-gray-600'">
                                {{ TYPE_LABELS[a.type] ?? a.type }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No assessments for your class yet.</p>
                </div>

                <!-- Notices -->
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="mb-3 font-semibold text-gray-800">Staff Notices</p>
                    <div v-if="classStats.notices.length" class="space-y-2">
                        <div v-for="n in classStats.notices" :key="n.id" class="border-l-2 border-indigo-200 pl-3">
                            <p class="text-sm font-medium text-gray-800">{{ n.title }}</p>
                            <p class="text-xs text-gray-400">{{ fmtDate(n.published_at) }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No notices.</p>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════
                 SUBJECT TEACHER
            ════════════════════════════════════════════════════════════════ -->
            <template v-else-if="type === 'subject_teacher'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">My Assessments</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ subjectStats.my_assessments.length }}</p>
                    </div>
                    <div class="rounded-xl border p-5 shadow-sm" :class="subjectStats.pending_scoring > 0 ? 'bg-amber-50 border-amber-200' : 'bg-white'">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Pending Scoring</p>
                        <p class="mt-2 text-3xl font-bold" :class="subjectStats.pending_scoring > 0 ? 'text-amber-600' : 'text-gray-400'">
                            {{ subjectStats.pending_scoring }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Assessments past date with no scores</p>
                    </div>
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-3">Quick Actions</p>
                        <Link :href="route('assessments.index')" class="block rounded-md bg-indigo-600 px-3 py-1.5 text-center text-sm font-medium text-white hover:bg-indigo-700">
                            New Assessment
                        </Link>
                    </div>
                </div>

                <!-- Assessment list -->
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-800">My Assessments</p>
                    <div v-if="subjectStats.my_assessments.length" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="pb-2 text-left text-xs font-medium text-gray-500">Name</th>
                                    <th class="pb-2 text-left text-xs font-medium text-gray-500">Subject</th>
                                    <th class="pb-2 text-left text-xs font-medium text-gray-500">Class</th>
                                    <th class="pb-2 text-left text-xs font-medium text-gray-500">Date</th>
                                    <th class="pb-2 text-left text-xs font-medium text-gray-500">Type</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="a in subjectStats.my_assessments" :key="a.id">
                                    <td class="py-2 font-medium text-gray-800">
                                        <Link :href="route('assessments.show', a.id)" class="hover:text-indigo-600">{{ a.name }}</Link>
                                    </td>
                                    <td class="py-2 text-gray-600">{{ a.subject?.name }}</td>
                                    <td class="py-2 text-gray-600">{{ a.stream?.name }}</td>
                                    <td class="py-2 text-gray-600">{{ fmtDate(a.date) }}</td>
                                    <td class="py-2">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="TYPE_COLORS[a.type] ?? 'bg-gray-100 text-gray-600'">
                                            {{ TYPE_LABELS[a.type] ?? a.type }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-sm text-gray-400">No assessments yet. Create one above.</p>
                </div>

                <!-- Notices -->
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="font-semibold text-gray-800">Recent Notices</p>
                        <Link :href="route('notices.index')" class="text-xs text-indigo-600 hover:underline">All notices</Link>
                    </div>
                    <div v-if="subjectStats.notices.length" class="space-y-2">
                        <div v-for="n in subjectStats.notices" :key="n.id" class="border-l-2 border-indigo-200 pl-3">
                            <p class="text-sm font-medium text-gray-800">{{ n.title }}</p>
                            <p class="text-xs text-gray-400">{{ fmtDate(n.published_at) }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No published notices.</p>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════
                 FINANCE OFFICER
            ════════════════════════════════════════════════════════════════ -->
            <template v-else-if="type === 'finance'">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl border border-red-100 bg-red-50 p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-red-500">Outstanding Fees</p>
                        <p class="mt-2 text-2xl font-bold text-red-700">{{ fmtMoney(financeStats.outstanding_amount) }}</p>
                        <p class="mt-1 text-xs text-red-500">{{ financeStats.outstanding_count }} invoices</p>
                    </div>
                    <div class="rounded-xl border border-green-100 bg-green-50 p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-green-600">Collected This Month</p>
                        <p class="mt-2 text-2xl font-bold text-green-700">{{ fmtMoney(financeStats.collected_this_month) }}</p>
                    </div>
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Expenses This Month</p>
                        <p class="mt-2 text-2xl font-bold text-gray-800">{{ fmtMoney(financeStats.expenses_this_month) }}</p>
                    </div>
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-3">Quick Links</p>
                        <div class="space-y-2">
                            <Link :href="route('fee-invoices.index')" class="block text-sm text-indigo-600 hover:underline">Invoices</Link>
                            <Link :href="route('expenses.index')" class="block text-sm text-indigo-600 hover:underline">Expenses</Link>
                            <Link :href="route('finance.reports')" class="block text-sm text-indigo-600 hover:underline">Reports</Link>
                        </div>
                    </div>
                </div>

                <!-- Recent payments -->
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-800">Recent Payments</p>
                    <div v-if="financeStats.recent_payments.length" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="pb-2 text-left text-xs font-medium text-gray-500">Pupil</th>
                                    <th class="pb-2 text-left text-xs font-medium text-gray-500">Amount</th>
                                    <th class="pb-2 text-left text-xs font-medium text-gray-500">Method</th>
                                    <th class="pb-2 text-left text-xs font-medium text-gray-500">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(p, i) in financeStats.recent_payments" :key="i">
                                    <td class="py-2 font-medium text-gray-800">{{ p.pupil_name }}</td>
                                    <td class="py-2 text-green-700 font-medium">{{ fmtMoney(p.amount) }}</td>
                                    <td class="py-2 text-gray-600 capitalize">{{ p.method }}</td>
                                    <td class="py-2 text-gray-500">{{ fmtDate(p.payment_date) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-sm text-gray-400">No payments recorded yet.</p>
                </div>

                <!-- Notices -->
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="font-semibold text-gray-800">Recent Notices</p>
                        <Link :href="route('notices.index')" class="text-xs text-indigo-600 hover:underline">All notices</Link>
                    </div>
                    <div v-if="financeStats.notices.length" class="space-y-2">
                        <div v-for="n in financeStats.notices" :key="n.id" class="border-l-2 border-indigo-200 pl-3">
                            <p class="text-sm font-medium text-gray-800">{{ n.title }}</p>
                            <p class="text-xs text-gray-400">{{ fmtDate(n.published_at) }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No published notices.</p>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════
                 LIBRARIAN
            ════════════════════════════════════════════════════════════════ -->
            <template v-else-if="type === 'librarian'">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Books</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ libStats.total_books }}</p>
                        <Link :href="route('library-books.index')" class="mt-1 text-xs text-indigo-600 hover:underline">Manage catalogue</Link>
                    </div>
                    <div class="rounded-xl border bg-blue-50 border-blue-200 p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-blue-500">Active Borrowings</p>
                        <p class="mt-2 text-3xl font-bold text-blue-700">{{ libStats.active_borrowings }}</p>
                        <Link :href="route('borrowings.index')" class="mt-1 text-xs text-blue-600 hover:underline">View all</Link>
                    </div>
                    <div class="rounded-xl border p-5 shadow-sm" :class="libStats.overdue > 0 ? 'bg-red-50 border-red-200' : 'bg-white'">
                        <p class="text-xs font-medium uppercase tracking-wide" :class="libStats.overdue > 0 ? 'text-red-500' : 'text-gray-500'">Overdue</p>
                        <p class="mt-2 text-3xl font-bold" :class="libStats.overdue > 0 ? 'text-red-700' : 'text-gray-300'">{{ libStats.overdue }}</p>
                        <Link :href="route('library.overdue')" class="mt-1 text-xs text-red-600 hover:underline">See overdue</Link>
                    </div>
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-3">Quick Actions</p>
                        <div class="space-y-2">
                            <Link :href="route('borrowings.create')" class="block rounded-md bg-indigo-600 px-3 py-1.5 text-center text-xs font-medium text-white hover:bg-indigo-700">Issue Book</Link>
                            <Link :href="route('library.overdue')" class="block rounded-md border px-3 py-1.5 text-center text-xs font-medium text-gray-700 hover:bg-gray-50">Overdue Report</Link>
                        </div>
                    </div>
                </div>

                <!-- Recent borrowings -->
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <p class="mb-4 font-semibold text-gray-800">Recent Borrowings</p>
                    <div v-if="libStats.recent_borrowings.length" class="divide-y divide-gray-100">
                        <div v-for="b in libStats.recent_borrowings" :key="b.id" class="flex items-center justify-between py-2.5">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ b.book?.title }}</p>
                                <p class="text-xs text-gray-500">Due {{ fmtDate(b.due_date) }} · issued by {{ b.issued_by?.name }}</p>
                            </div>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                  :class="b.status === 'returned' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'">
                                {{ b.status }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No recent borrowings.</p>
                </div>

                <!-- Notices -->
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="font-semibold text-gray-800">Recent Notices</p>
                        <Link :href="route('notices.index')" class="text-xs text-indigo-600 hover:underline">All notices</Link>
                    </div>
                    <div v-if="libStats.notices.length" class="space-y-2">
                        <div v-for="n in libStats.notices" :key="n.id" class="border-l-2 border-indigo-200 pl-3">
                            <p class="text-sm font-medium text-gray-800">{{ n.title }}</p>
                            <p class="text-xs text-gray-400">{{ fmtDate(n.published_at) }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No published notices.</p>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════
                 BOARDING MASTER
            ════════════════════════════════════════════════════════════════ -->
            <template v-else-if="type === 'boarding'">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Beds</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ boardingStats.total_beds }}</p>
                    </div>
                    <div class="rounded-xl border bg-indigo-50 border-indigo-200 p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-indigo-500">Occupied</p>
                        <p class="mt-2 text-3xl font-bold text-indigo-700">{{ boardingStats.occupied_beds }}</p>
                    </div>
                    <div class="rounded-xl border bg-green-50 border-green-200 p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-green-500">Available</p>
                        <p class="mt-2 text-3xl font-bold text-green-700">{{ boardingStats.available_beds }}</p>
                    </div>
                    <div class="rounded-xl border bg-white p-5 shadow-sm flex flex-col items-center justify-center">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-2">Occupancy</p>
                        <p class="text-4xl font-bold" :class="boardingStats.occupancy_pct > 90 ? 'text-red-600' : boardingStats.occupancy_pct > 70 ? 'text-amber-600' : 'text-green-600'">
                            {{ boardingStats.occupancy_pct }}%
                        </p>
                    </div>
                </div>
                <div class="mt-2">
                    <Link :href="route('boarding.roster')" class="inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        View Roster
                    </Link>
                </div>

                <!-- Notices -->
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="font-semibold text-gray-800">Recent Notices</p>
                        <Link :href="route('notices.index')" class="text-xs text-indigo-600 hover:underline">All notices</Link>
                    </div>
                    <div v-if="boardingStats.notices.length" class="space-y-2">
                        <div v-for="n in boardingStats.notices" :key="n.id" class="border-l-2 border-indigo-200 pl-3">
                            <p class="text-sm font-medium text-gray-800">{{ n.title }}</p>
                            <p class="text-xs text-gray-400">{{ fmtDate(n.published_at) }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No published notices.</p>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════
                 TRANSPORT COORDINATOR
            ════════════════════════════════════════════════════════════════ -->
            <template v-else-if="type === 'transport'">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Routes</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ transportStats.routes }}</p>
                        <Link :href="route('transport-routes.index')" class="mt-1 text-xs text-indigo-600 hover:underline">Manage routes</Link>
                    </div>
                    <div class="rounded-xl border bg-indigo-50 border-indigo-200 p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-indigo-500">Assigned Pupils</p>
                        <p class="mt-2 text-3xl font-bold text-indigo-700">{{ transportStats.assigned_pupils }}</p>
                    </div>
                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-3">Quick Links</p>
                        <div class="space-y-2">
                            <Link :href="route('transport-routes.index')" class="block text-sm text-indigo-600 hover:underline">Route Management</Link>
                            <Link :href="route('pupils.index')" class="block text-sm text-indigo-600 hover:underline">Assign Pupils</Link>
                        </div>
                    </div>
                </div>

                <!-- Notices -->
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="font-semibold text-gray-800">Recent Notices</p>
                        <Link :href="route('notices.index')" class="text-xs text-indigo-600 hover:underline">All notices</Link>
                    </div>
                    <div v-if="transportStats.notices.length" class="space-y-2">
                        <div v-for="n in transportStats.notices" :key="n.id" class="border-l-2 border-indigo-200 pl-3">
                            <p class="text-sm font-medium text-gray-800">{{ n.title }}</p>
                            <p class="text-xs text-gray-400">{{ fmtDate(n.published_at) }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No published notices.</p>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════
                 FEEDING COORDINATOR
            ════════════════════════════════════════════════════════════════ -->
            <template v-else-if="type === 'feeding'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border p-5 shadow-sm" :class="feedingStats.today_session ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200'">
                        <p class="text-xs font-medium uppercase tracking-wide" :class="feedingStats.today_session ? 'text-green-500' : 'text-amber-500'">Today's Session</p>
                        <p class="mt-2 text-lg font-bold" :class="feedingStats.today_session ? 'text-green-700' : 'text-amber-700'">
                            {{ feedingStats.today_session ? 'Session Open' : 'No Session Today' }}
                        </p>
                        <Link :href="route('feeding-sessions.index')" class="mt-2 inline-block text-xs font-medium underline" :class="feedingStats.today_session ? 'text-green-700' : 'text-amber-700'">
                            {{ feedingStats.today_session ? 'Manage session' : 'Open a session' }}
                        </Link>
                    </div>

                    <div class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-3">
                            Low Stock Alert
                            <span v-if="feedingStats.low_stock.length" class="ml-1 rounded-full bg-red-100 px-1.5 text-xs text-red-600">{{ feedingStats.low_stock.length }}</span>
                        </p>
                        <div v-if="feedingStats.low_stock.length" class="space-y-2">
                            <div v-for="item in feedingStats.low_stock" :key="item.item_name" class="flex items-center justify-between text-sm">
                                <span class="text-gray-700">{{ item.item_name }}</span>
                                <span class="font-medium text-red-600">{{ item.quantity_kg }} kg</span>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400">All stock levels OK.</p>
                        <Link :href="route('stock.index')" class="mt-3 inline-block text-xs text-indigo-600 hover:underline">Manage stock</Link>
                    </div>
                </div>

                <!-- Notices -->
                <div class="rounded-xl border bg-white p-5 shadow-sm">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="font-semibold text-gray-800">Recent Notices</p>
                        <Link :href="route('notices.index')" class="text-xs text-indigo-600 hover:underline">All notices</Link>
                    </div>
                    <div v-if="feedingStats.notices.length" class="space-y-2">
                        <div v-for="n in feedingStats.notices" :key="n.id" class="border-l-2 border-indigo-200 pl-3">
                            <p class="text-sm font-medium text-gray-800">{{ n.title }}</p>
                            <p class="text-xs text-gray-400">{{ fmtDate(n.published_at) }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No published notices.</p>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════════
                 DEFAULT (parent / unrecognised role)
            ════════════════════════════════════════════════════════════════ -->
            <template v-else>
                <div class="rounded-xl border bg-white p-10 text-center shadow-sm">
                    <p class="text-lg font-semibold text-gray-700">Welcome to {{ school?.name }}</p>
                    <p class="mt-2 text-sm text-gray-500">Use the navigation on the left to get started.</p>
                </div>
            </template>

        </div>
    </AppLayout>
</template>
