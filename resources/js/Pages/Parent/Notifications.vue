<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import type { PortalNotification } from '@/types/portal'

defineProps<{
    notifications: {
        data: PortalNotification[]
        current_page: number
        last_page: number
    }
}>()

const typeIcon: Record<string, string> = {
    result: '📊',
    attendance: '📅',
    fee: '💰',
    general: '🔔',
}

function markRead(id: number) {
    router.patch(route('portal.notifications.read', id), {}, { preserveScroll: true })
}
</script>

<template>
    <Head title="Notifications" />
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-semibold text-gray-900">Notifications</h1>

            <div class="space-y-3">
                <div
                    v-for="notif in notifications.data"
                    :key="notif.id"
                    :class="['rounded-lg border bg-white p-4 shadow-sm', notif.read_at ? 'border-gray-200 opacity-70' : 'border-indigo-200']"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="text-xl" aria-hidden="true">{{ typeIcon[notif.type] ?? '🔔' }}</span>
                            <div>
                                <p class="font-medium text-gray-900">{{ notif.title }}</p>
                                <p class="mt-1 text-sm text-gray-600">{{ notif.message }}</p>
                                <p class="mt-1 text-xs text-gray-400">{{ notif.created_at }}</p>
                            </div>
                        </div>
                        <button
                            v-if="!notif.read_at"
                            class="flex-shrink-0 text-xs text-indigo-600 hover:underline"
                            @click="markRead(notif.id)"
                        >
                            Mark read
                        </button>
                    </div>
                </div>

                <div v-if="!notifications.data.length" class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-400">
                    No notifications yet.
                </div>
            </div>
        </div>
    </div>
</template>
