<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { Term } from '@/types/shared'

const props = defineProps<{
    modelValue: number | null
}>()

const emit = defineEmits<{
    'update:modelValue': [value: number | null]
}>()

const page = usePage()
const terms = computed<Term[]>(() => page.props.terms ?? [])
</script>

<template>
    <select
        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
        :value="modelValue"
        @change="emit('update:modelValue', Number(($event.target as HTMLSelectElement).value) || null)"
    >
        <option :value="null">All Terms</option>
        <option v-for="term in terms" :key="term.id" :value="term.id">
            {{ term.name }}
            <template v-if="term.is_current"> (Current)</template>
        </option>
    </select>
</template>
