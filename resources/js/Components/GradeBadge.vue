<script setup lang="ts">
import type { GradeLetter } from '@/types/shared'

const props = defineProps<{
    letter: GradeLetter
    showLabel?: boolean
}>()

const STYLES: Record<GradeLetter, string> = {
    A: 'bg-green-100 text-green-800 ring-green-600/20',
    B: 'bg-teal-100 text-teal-800 ring-teal-600/20',
    C: 'bg-blue-100 text-blue-800 ring-blue-600/20',
    D: 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
    F: 'bg-red-100 text-red-800 ring-red-600/20',
}

const LABELS: Record<GradeLetter, string> = {
    A: 'Distinction',
    B: 'Merit',
    C: 'Credit',
    D: 'Pass',
    F: 'Fail',
}
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset"
        :class="STYLES[props.letter]"
    >
        {{ props.letter }}
        <span v-if="showLabel" class="font-normal">{{ LABELS[props.letter] }}</span>
    </span>
</template>
