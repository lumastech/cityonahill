<script setup lang="ts">
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import FlashToast from '@/Components/FlashToast.vue'
import { useSchool } from '@/composables/useSchool'
import { Head, Link, router } from '@inertiajs/vue3'

defineProps<{ title?: string }>()

const { currentSchool, schoolName, logoUrl } = useSchool()

const tabs = [
    { label: 'My Children', href: '#children' },
    { label: 'Fees', href: '#fees' },
    { label: 'Results', href: '#results' },
    { label: 'Attendance', href: '#attendance' },
    { label: 'Notices', href: '#notices' },
    { label: 'Messages', href: '#messages' },
]

function logout() {
    router.post(route('logout'))
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <Head :title="title" />

        <!-- Header -->
        <header class="border-b border-gray-200 bg-white shadow-sm">
            <div class="mx-auto max-w-5xl px-4">
                <div class="flex h-16 items-center justify-between">
                    <!-- School branding -->
                    <div class="flex items-center gap-3">
                        <img v-if="logoUrl" :src="logoUrl" class="h-10 w-10 rounded-full object-cover"
                            alt="School logo" />
                        <div v-else
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 font-bold text-white">
                            {{ schoolName?.[0] ?? 'Z' }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ schoolName || 'CITYONAHILL' }}</p>
                            <p class="text-xs text-gray-500">Parent Portal</p>
                        </div>
                    </div>

                    <button
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100"
                        @click="logout">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </button>
                </div>

                <!-- Tab navigation -->
                <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="Tabs">
                    <a v-for="tab in tabs" :key="tab.href" :href="tab.href"
                        class="shrink-0 border-b-2 px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors"
                        :class="$page.url.includes(tab.href.slice(1))
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            ">
                        {{ tab.label }}
                    </a>
                </nav>
            </div>
        </header>

        <!-- Page content -->
        <main class="mx-auto max-w-5xl px-4 py-6">
            <slot />
        </main>

        <FlashToast />
        <ConfirmDialog />
    </div>
</template>
