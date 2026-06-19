<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import type { Notice, NoticeAudience } from '@/types/communication'

const props = defineProps<{
    notices: Notice[]
}>()

const audienceFilter = ref<NoticeAudience | 'all'>('all')

const AUDIENCE_LABELS: Record<string, string> = {
    all: 'All',
    parents: 'Parents',
    staff: 'Staff',
    pupils: 'Pupils',
    grade: 'Grade',
}

const AUDIENCE_COLORS: Record<string, string> = {
    all: 'bg-gray-100 text-gray-700',
    parents: 'bg-blue-100 text-blue-700',
    staff: 'bg-purple-100 text-purple-700',
    pupils: 'bg-green-100 text-green-700',
    grade: 'bg-yellow-100 text-yellow-700',
}

const filtered = computed(() => {
    if (audienceFilter.value === 'all') return props.notices
    return props.notices.filter((n) => n.target_audience === audienceFilter.value)
})

function isActive(notice: Notice): boolean {
    if (!notice.published_at) return false
    const now = new Date()
    if (new Date(notice.published_at) > now) return false
    if (notice.expires_at && new Date(notice.expires_at) <= now) return false
    return true
}

function publish(notice: Notice) {
    router.post(route('notices.publish', notice.id))
}

function remove(notice: Notice) {
    if (confirm('Delete this notice?')) {
        router.delete(route('notices.destroy', notice.id))
    }
}
</script>

<template>
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Notices</h1>
            <Link :href="route('notices.create')" class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                + New Notice
            </Link>
        </div>

        <div class="mb-4 flex gap-2">
            <button
                v-for="aud in ['all', 'parents', 'staff', 'pupils', 'grade']"
                :key="aud"
                @click="audienceFilter = aud as NoticeAudience | 'all'"
                :class="[
                    'rounded-full px-3 py-1 text-sm font-medium transition',
                    audienceFilter === aud ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                ]"
            >
                {{ AUDIENCE_LABELS[aud] }}
            </button>
        </div>

        <div class="space-y-4">
            <div
                v-for="notice in filtered"
                :key="notice.id"
                :class="['rounded-lg border p-4', isActive(notice) ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-white']"
            >
                <div class="mb-2 flex items-start justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', AUDIENCE_COLORS[notice.target_audience]]">
                            {{ AUDIENCE_LABELS[notice.target_audience] }}
                        </span>
                        <span v-if="isActive(notice)" class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                            Active
                        </span>
                        <span v-else-if="!notice.published_at" class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">
                            Draft
                        </span>
                        <span v-else class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                            Expired
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <button
                            v-if="!notice.published_at"
                            @click="publish(notice)"
                            class="text-sm text-indigo-600 hover:underline"
                        >
                            Publish
                        </button>
                        <button @click="remove(notice)" class="text-sm text-red-600 hover:underline">
                            Delete
                        </button>
                    </div>
                </div>

                <h3 class="font-semibold text-gray-900">{{ notice.title }}</h3>
                <p class="mt-1 text-sm text-gray-600 line-clamp-2">{{ notice.content }}</p>
                <div class="mt-2 flex items-center gap-4 text-xs text-gray-400">
                    <span>By {{ notice.creator?.name ?? 'Unknown' }}</span>
                    <span v-if="notice.published_at">Published {{ new Date(notice.published_at).toLocaleDateString() }}</span>
                    <span v-if="notice.expires_at">Expires {{ new Date(notice.expires_at).toLocaleDateString() }}</span>
                </div>
            </div>

            <p v-if="filtered.length === 0" class="py-12 text-center text-gray-500">No notices found.</p>
        </div>
    </div>
</template>
