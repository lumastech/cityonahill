<script setup lang="ts">
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import FlashToast from '@/Components/FlashToast.vue'
import { usePermissions } from '@/composables/usePermissions'
import { useSchool } from '@/composables/useSchool'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps<{ title?: string }>()

const { can, hasRole, isSuperAdmin } = usePermissions()
const { currentSchool, schoolName } = useSchool()
const page = usePage()

const sidebarOpen = ref(true)
const schoolSwitchId = ref<number | null>(null)

function logout() {
    router.post(route('logout'))
}

function switchSchool() {
    if (!schoolSwitchId.value) return
    router.get(route('dashboard'), { school_id: schoolSwitchId.value }, { preserveState: false })
}
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-gray-50">
        <Head :title="title" />

        <!-- Sidebar -->
        <aside
            class="flex w-64 shrink-0 flex-col border-r border-gray-200 bg-white transition-all duration-300"
            :class="{ 'w-0 overflow-hidden border-0': !sidebarOpen }"
        >
            <!-- Brand -->
            <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-4">
                <img
                    v-if="currentSchool?.logo_url"
                    :src="currentSchool.logo_url"
                    class="h-8 w-8 rounded-full object-cover"
                    alt="School logo"
                />
                <div v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">
                    Z
                </div>
                <span class="truncate text-sm font-semibold text-gray-900">
                    {{ schoolName || 'ZSSMS' }}
                </span>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto p-3 text-sm">

                <!-- Academic -->
                <details class="group mb-1" open>
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                        Academic
                    </summary>
                    <div class="ml-4 mt-1 space-y-0.5">
                        <Link :href="route('dashboard')" class="nav-link">Dashboard</Link>
                        <Link v-if="can('pupil.view')" href="#" class="nav-link">Pupils</Link>
                        <Link v-if="can('grade.view')" href="#" class="nav-link">Classes</Link>
                        <Link v-if="can('subject.view')" href="#" class="nav-link">Subjects</Link>
                        <Link href="#" class="nav-link">Timetable</Link>
                    </div>
                </details>

                <!-- Exams -->
                <details class="group mb-1" open>
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        Exams & Results
                    </summary>
                    <div class="ml-4 mt-1 space-y-0.5">
                        <Link v-if="can('assessment.view')" href="#" class="nav-link">Assessments</Link>
                        <Link v-if="can('grade.view')" href="#" class="nav-link">Results</Link>
                        <Link v-if="can('report-card.view')" href="#" class="nav-link">Report Cards</Link>
                        <Link v-if="can('ecz.view')" href="#" class="nav-link">ECZ</Link>
                    </div>
                </details>

                <!-- Staff -->
                <details v-if="can('staff.view') || can('leave.apply')" class="group mb-1">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Staff & HR
                    </summary>
                    <div class="ml-4 mt-1 space-y-0.5">
                        <Link v-if="can('staff.view')" href="#" class="nav-link">Staff Directory</Link>
                        <Link v-if="can('leave.apply')" href="#" class="nav-link">Leave</Link>
                        <Link v-if="can('payroll.view')" href="#" class="nav-link">Payroll</Link>
                    </div>
                </details>

                <!-- Finance -->
                <details v-if="can('fee.view') || can('expense.view') || can('payroll.view')" class="group mb-1">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Finance
                    </summary>
                    <div class="ml-4 mt-1 space-y-0.5">
                        <Link v-if="can('fee.view')" href="#" class="nav-link">Fees</Link>
                        <Link v-if="can('fee.collect')" href="#" class="nav-link">Payments</Link>
                        <Link v-if="can('expense.view')" href="#" class="nav-link">Expenses</Link>
                        <Link v-if="can('expense.view')" href="#" class="nav-link">Budget</Link>
                    </div>
                </details>

                <!-- Campus -->
                <details v-if="can('library.view') || can('transport.view') || can('feeding.view') || can('boarding.view')" class="group mb-1">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        Campus
                    </summary>
                    <div class="ml-4 mt-1 space-y-0.5">
                        <Link v-if="can('library.view')" href="#" class="nav-link">Library</Link>
                        <Link v-if="can('transport.view')" href="#" class="nav-link">Transport</Link>
                        <Link v-if="can('feeding.view')" href="#" class="nav-link">Feeding</Link>
                        <Link v-if="can('boarding.view')" href="#" class="nav-link">Boarding</Link>
                    </div>
                </details>

                <!-- Communication -->
                <details v-if="can('notice.view') || can('sms.send')" class="group mb-1">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                        Communication
                    </summary>
                    <div class="ml-4 mt-1 space-y-0.5">
                        <Link v-if="can('notice.view')" href="#" class="nav-link">Notices</Link>
                        <Link v-if="can('sms.send')" href="#" class="nav-link">SMS</Link>
                        <Link href="#" class="nav-link">Messages</Link>
                    </div>
                </details>

                <!-- Admin -->
                <details v-if="can('school.view') || can('settings.manage') || can('audit.view') || isSuperAdmin()" class="group mb-1">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Admin
                    </summary>
                    <div class="ml-4 mt-1 space-y-0.5">
                        <Link v-if="can('school.view')" href="#" class="nav-link">Schools</Link>
                        <Link v-if="isSuperAdmin()" href="#" class="nav-link">Roles</Link>
                        <Link v-if="can('settings.manage')" href="#" class="nav-link">Settings</Link>
                        <Link v-if="can('audit.view')" href="#" class="nav-link">Audit Log</Link>
                    </div>
                </details>
            </nav>

            <!-- User footer -->
            <div class="border-t border-gray-200 p-3">
                <div class="flex items-center gap-3 rounded-lg px-3 py-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-600">
                        {{ page.props.auth?.user?.first_name?.[0] ?? 'U' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900">
                            {{ page.props.auth?.user?.first_name }} {{ page.props.auth?.user?.last_name }}
                        </p>
                        <p class="truncate text-xs text-gray-500">{{ page.props.auth?.user?.roles?.[0] }}</p>
                    </div>
                    <button class="shrink-0 text-gray-400 hover:text-gray-600" @click="logout" title="Logout">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Header -->
            <header class="flex h-16 shrink-0 items-center gap-4 border-b border-gray-200 bg-white px-4">
                <button
                    class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>

                <h1 v-if="title" class="text-base font-semibold text-gray-900">{{ title }}</h1>

                <div class="ml-auto flex items-center gap-3">
                    <!-- School badge -->
                    <span
                        v-if="currentSchool"
                        class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10"
                    >
                        {{ schoolName }}
                    </span>

                    <!-- School switcher for super-admin -->
                    <div v-if="isSuperAdmin()" class="flex items-center gap-2">
                        <select
                            v-model="schoolSwitchId"
                            class="rounded-lg border border-gray-300 px-2 py-1 text-xs"
                            @change="switchSchool"
                        >
                            <option :value="null">Switch School…</option>
                        </select>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-6">
                <slot />
            </main>
        </div>

        <FlashToast />
        <ConfirmDialog />
    </div>
</template>

<style scoped>
.nav-link {
    @apply block rounded-lg px-3 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900;
}
</style>
