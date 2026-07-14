<script setup lang="ts">
import AppSidebar from '@/Components/AppSidebar.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import FlashToast from '@/Components/FlashToast.vue'
import { usePermissions } from '@/composables/usePermissions'
import { useSchool } from '@/composables/useSchool'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

defineProps<{ title?: string }>()

const { isSuperAdmin } = usePermissions()
const { currentSchool, schoolName } = useSchool()
const page = usePage()

const DESKTOP = '(min-width: 1024px)' // Tailwind `lg`

// Desktop collapses the sidebar to zero width; mobile slides it in over the page.
const sidebarOpen = ref(true)
const mobileNavOpen = ref(false)
const isDesktop = ref(true)
const schoolSwitchId = ref<number | null>(null)

const schools = computed<{ id: number; name: string }[]>(
    () => (page.props.school_options as { id: number; name: string }[]) ?? [],
)

let mql: MediaQueryList | undefined

function syncBreakpoint(e: MediaQueryList | MediaQueryListEvent) {
    isDesktop.value = e.matches
    if (e.matches) mobileNavOpen.value = false
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') mobileNavOpen.value = false
}

onMounted(() => {
    mql = window.matchMedia(DESKTOP)
    syncBreakpoint(mql)
    mql.addEventListener('change', syncBreakpoint)
    window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
    mql?.removeEventListener('change', syncBreakpoint)
    window.removeEventListener('keydown', onKeydown)
})

// The drawer overlays the page on mobile, so close it once navigation lands.
const stopNavigate = router.on('navigate', () => {
    mobileNavOpen.value = false
})
onBeforeUnmount(() => stopNavigate())

function toggleSidebar() {
    if (isDesktop.value) sidebarOpen.value = !sidebarOpen.value
    else mobileNavOpen.value = !mobileNavOpen.value
}

function logout() {
    router.post(route('logout'))
}

function switchSchool() {
    if (!schoolSwitchId.value) return
    router.get(route('dashboard'), { school_id: schoolSwitchId.value }, { preserveState: false })
}
</script>

<template>
    <div class="flex h-dvh overflow-hidden bg-gray-50">

        <Head :title="title" />

        <!-- Mobile drawer backdrop -->
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-200" leave-to-class="opacity-0">
            <div v-if="mobileNavOpen" class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" aria-hidden="true"
                @click="mobileNavOpen = false" />
        </Transition>

        <!-- Sidebar: off-canvas drawer below lg, static column from lg up -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-64 shrink-0 flex-col border-r border-gray-200 bg-white transition-all duration-300 lg:static lg:z-auto lg:translate-x-0"
            :class="[
                mobileNavOpen ? 'translate-x-0 shadow-xl' : '-translate-x-full',
                sidebarOpen ? '' : 'lg:w-0 lg:overflow-hidden lg:border-0',
            ]">
            <!-- Brand -->
            <div class="flex h-16 shrink-0 items-center gap-3 border-b border-gray-200 px-4">
                <img v-if="currentSchool?.logo_url" :src="currentSchool.logo_url"
                    class="h-8 w-8 rounded-full object-cover" alt="School logo" />
                <div v-else
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">
                    Z
                </div>
                <span class="truncate text-sm font-semibold text-gray-900">
                    {{ schoolName || 'CITYONAHILL' }}
                </span>
                <button class="ml-auto shrink-0 rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 lg:hidden"
                    aria-label="Close navigation" @click="mobileNavOpen = false">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Nav -->
            <AppSidebar />

            <!-- User footer -->
            <div class="border-t border-gray-200 p-3">
                <div class="flex items-center gap-3 rounded-lg px-3 py-2">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-600">
                        {{ page.props.auth?.user?.first_name?.[0] ?? 'U' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900">
                            {{ page.props.auth?.user?.name }}
                        </p>
                        <p class="truncate text-xs text-gray-500">{{ page.props.auth?.user?.roles?.[0]?.name }}</p>
                    </div>
                    <a v-if="page.props.auth?.staff_profile_url" :href="page.props.auth.staff_profile_url"
                        class="shrink-0 text-gray-400 hover:text-gray-600" title="My Profile">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </a>
                    <Link :href="route('profile.show')" class="shrink-0 text-gray-400 hover:text-gray-600"
                        title="Account settings & password">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 7a2 2 0 012 2m4-2a6 6 0 01-7.743 5.743L11 14H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </Link>
                    <button class="shrink-0 text-gray-400 hover:text-gray-600" @click="logout" title="Logout">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Header -->
            <header class="flex h-16 shrink-0 items-center gap-2 border-b border-gray-200 bg-white px-3 sm:gap-4 sm:px-4">
                <button class="shrink-0 rounded-lg p-1.5 text-gray-500 hover:bg-gray-100"
                    :aria-expanded="isDesktop ? sidebarOpen : mobileNavOpen" aria-label="Toggle navigation"
                    @click="toggleSidebar">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <h1 v-if="title" class="truncate text-base font-semibold text-gray-900">{{ title }}</h1>

                <div class="ml-auto flex min-w-0 items-center gap-2 sm:gap-3">
                    <!-- School badge — redundant with the sidebar brand on small screens -->
                    <span v-if="currentSchool"
                        class="hidden max-w-[12rem] items-center truncate rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 md:inline-flex">
                        {{ schoolName }}
                    </span>

                    <!-- Branch switcher for super-admin -->
                    <select v-if="isSuperAdmin() && schools.length > 1" v-model="schoolSwitchId"
                        class="max-w-[9rem] shrink-0 truncate rounded-lg border border-gray-300 px-2 py-1 text-xs sm:max-w-none"
                        aria-label="Switch branch" @change="switchSchool">
                        <option :value="null">Switch Branch…</option>
                        <option v-for="s in schools" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <slot />
            </main>
        </div>

        <FlashToast />
        <ConfirmDialog />
    </div>
</template>
