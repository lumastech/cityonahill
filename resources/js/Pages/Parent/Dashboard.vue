<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { ChildSummaryPupil } from '@/types/portal'

const props = defineProps<{
    children: ChildSummaryPupil[]
}>()

const selected = ref<ChildSummaryPupil | null>(props.children[0] ?? null)

function viewChild(pupil: ChildSummaryPupil) {
    router.visit(route('portal.child.detail', pupil.id))
}
</script>

<template>
    <Head title="Parent Dashboard" />
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-semibold text-gray-900">Parent Portal</h1>

            <div v-if="children.length === 0" class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500">
                No children linked to your account. Please contact the school.
            </div>

            <div v-else>
                <!-- Child tabs -->
                <div class="mb-6 flex gap-2 border-b border-gray-200">
                    <button
                        v-for="child in children"
                        :key="child.id"
                        :class="[
                            'border-b-2 px-4 py-2 text-sm font-medium transition',
                            selected?.id === child.id
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700',
                        ]"
                        @click="selected = child"
                    >
                        {{ child.first_name }} {{ child.last_name }}
                    </button>
                </div>

                <div v-if="selected" class="space-y-4">
                    <!-- Pupil card -->
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">
                                    {{ selected.first_name }} {{ selected.last_name }}
                                </h2>
                                <p class="text-sm text-gray-500">
                                    {{ selected.grade?.name }} · {{ selected.stream?.name }} ·
                                    {{ selected.admission_no }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick action cards -->
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <button
                            class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-left hover:bg-indigo-100"
                            @click="viewChild(selected)"
                        >
                            <p class="text-sm font-medium text-indigo-800">Results</p>
                            <p class="mt-1 text-xs text-indigo-600">View term results</p>
                        </button>
                        <a :href="route('portal.messages.index')" class="rounded-lg border border-green-200 bg-green-50 p-4 text-left hover:bg-green-100">
                            <p class="text-sm font-medium text-green-800">Messages</p>
                            <p class="mt-1 text-xs text-green-600">Contact school</p>
                        </a>
                        <a :href="route('portal.notifications.index')" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-left hover:bg-yellow-100">
                            <p class="text-sm font-medium text-yellow-800">Notifications</p>
                            <p class="mt-1 text-xs text-yellow-600">View alerts</p>
                        </a>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-left">
                            <p class="text-sm font-medium text-gray-600">Fees</p>
                            <p class="mt-1 text-xs text-gray-400">Coming soon</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
