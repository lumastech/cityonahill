<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import type { PaginatedResponse } from '@/types/shared'

defineProps<{
    meta: Pick<PaginatedResponse<unknown>, 'current_page' | 'last_page' | 'per_page' | 'total' | 'links'>
}>()
</script>

<template>
    <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3">
        <div class="text-sm text-gray-600">
            Page {{ meta.current_page }} of {{ meta.last_page }}
            <span class="ml-1 text-gray-400">({{ meta.total }} total)</span>
        </div>

        <div class="flex items-center gap-1">
            <template v-for="link in meta.links" :key="link.label">
                <component
                    :is="link.url ? Link : 'span'"
                    :href="link.url ?? undefined"
                    preserve-scroll
                    preserve-state
                    class="inline-flex min-w-[2rem] items-center justify-center rounded px-2 py-1 text-sm"
                    :class="[
                        link.active
                            ? 'bg-blue-600 font-semibold text-white'
                            : link.url
                              ? 'text-gray-600 hover:bg-gray-100'
                              : 'cursor-default text-gray-300',
                    ]"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
