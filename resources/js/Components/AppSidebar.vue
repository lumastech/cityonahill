<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { NavGroup } from '@/types/shared'

const page = usePage()
const nav = computed<NavGroup[]>(() => page.props.nav ?? [])

// SVG path data for each icon (supports multiple paths per icon)
const iconPaths: Record<string, string[]> = {
    'book-open': [
        'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    ],
    'clipboard': [
        'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    ],
    'users': [
        'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    ],
    'banknotes': [
        'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ],
    'building-office': [
        'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    ],
    'chat-bubble': [
        'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
    ],
    'cog': [
        'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    ],
}

function paths(icon: string): string[] {
    return iconPaths[icon] ?? ['M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z']
}

const currentPath = ref(window.location.pathname)

function isActive(url: string | null): boolean {
    if (!url) return false
    try {
        const path = new URL(url).pathname
        return currentPath.value === path
    } catch {
        return currentPath.value === url
    }
}

function isGroupActive(group: NavGroup): boolean {
    return group.items.some((item) => isActive(item.url))
}
</script>

<template>
    <nav class="flex-1 overflow-y-auto p-3 text-sm">
        <details
            v-for="group in nav"
            :key="group.label"
            :open="isGroupActive(group) || undefined"
            class="group mb-1"
        >
            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        v-for="(d, i) in paths(group.icon)"
                        :key="i"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        :d="d"
                    />
                </svg>
                {{ group.label }}
            </summary>

            <div class="ml-4 mt-1 space-y-0.5">
                <Link
                    v-for="item in group.items"
                    :key="item.label"
                    :href="item.url ?? '#'"
                    class="nav-link"
                    :class="{ 'bg-blue-50 text-blue-700 font-medium': isActive(item.url) }"
                >
                    {{ item.label }}
                </Link>
            </div>
        </details>
    </nav>
</template>

<style scoped>
.nav-link {
    @apply block rounded-lg px-3 py-1.5 text-gray-600 hover:bg-gray-100 hover:text-gray-900;
}
</style>
