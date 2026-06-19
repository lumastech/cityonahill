<script setup lang="ts">
import { useConfirm } from '@/composables/useConfirm'

const { state, accept, cancel } = useConfirm()
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="state.open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="cancel"
            >
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="scale-95 opacity-0"
                    enter-to-class="scale-100 opacity-100"
                >
                    <div
                        v-if="state.open"
                        class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
                        role="dialog"
                        aria-modal="true"
                    >
                        <h3 class="text-lg font-semibold text-gray-900">{{ state.title }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ state.message }}</p>

                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                @click="cancel"
                            >
                                Cancel
                            </button>
                            <button
                                class="rounded-lg px-4 py-2 text-sm font-medium text-white"
                                :class="
                                    state.dangerMode
                                        ? 'bg-red-600 hover:bg-red-700'
                                        : 'bg-blue-600 hover:bg-blue-700'
                                "
                                @click="accept"
                            >
                                {{ state.confirmLabel }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
