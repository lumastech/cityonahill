<script setup lang="ts">
import AppSidebar from '@/Components/AppSidebar.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import FlashToast from '@/Components/FlashToast.vue'
import { usePermissions } from '@/composables/usePermissions'
import { useSchool } from '@/composables/useSchool'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

defineProps<{ title?: string }>()

const { isSuperAdmin } = usePermissions()
const { currentSchool, schoolName } = useSchool()
const page = usePage()

const sidebarOpen = ref(true)
const schoolSwitchId = ref<number | null>(null)

const schools = computed<{ id: number; name: string }[]>(
    () => (page.props.school_options as { id: number; name: string }[]) ?? [],
)

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
        <aside class="flex w-64 shrink-0 flex-col border-r border-gray-200 bg-white transition-all duration-300"
            :class="{ 'w-0 overflow-hidden border-0': !sidebarOpen }">
            <!-- Brand -->
            <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-4">
                <img v-if="currentSchool?.logo_url" :src="currentSchool.logo_url"
                    class="h-8 w-8 rounded-full object-cover" alt="School logo" />
                <div v-else
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white">
                    Z
                </div>
                <span class="truncate text-sm font-semibold text-gray-900">
                    {{ schoolName || 'CITYONAHILL' }}
                </span>
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
            <header class="flex h-16 shrink-0 items-center gap-4 border-b border-gray-200 bg-white px-4">
                <button class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100" @click="sidebarOpen = !sidebarOpen">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <h1 v-if="title" class="text-base font-semibold text-gray-900">{{ title }}</h1>

                <div class="ml-auto flex items-center gap-3">
                    <!-- School badge -->
                    <span v-if="currentSchool"
                        class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                        {{ schoolName }}
                    </span>

                    <!-- Branch switcher for super-admin -->
                    <div v-if="isSuperAdmin() && schools.length > 1" class="flex items-center gap-2">
                        <select v-model="schoolSwitchId" class="rounded-lg border border-gray-300 px-2 py-1 text-xs"
                            @change="switchSchool">
                            <option :value="null">Switch Branch…</option>
                            <option v-for="s in schools" :key="s.id" :value="s.id">{{ s.name }}</option>
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
