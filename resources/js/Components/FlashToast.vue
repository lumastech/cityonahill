<script setup lang="ts">
import { useFlash } from '@/composables/useFlash'
import { Transition } from 'vue'

const { flash, hasFlash, clear } = useFlash()
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div
            v-if="hasFlash"
            class="fixed bottom-4 right-4 z-50 flex max-w-sm flex-col gap-2"
            role="alert"
        >
            <div
                v-if="flash.success"
                class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-lg"
            >
                <svg class="h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span class="flex-1">{{ flash.success }}</span>
                <button class="ml-2 text-green-600 hover:text-green-800" @click="clear">✕</button>
            </div>

            <div
                v-if="flash.error"
                class="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-lg"
            >
                <svg class="h-5 w-5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span class="flex-1">{{ flash.error }}</span>
                <button class="ml-2 text-red-600 hover:text-red-800" @click="clear">✕</button>
            </div>

            <div
                v-if="flash.info"
                class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 shadow-lg"
            >
                <svg class="h-5 w-5 shrink-0 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span class="flex-1">{{ flash.info }}</span>
                <button class="ml-2 text-blue-600 hover:text-blue-800" @click="clear">✕</button>
            </div>
        </div>
    </Transition>
</template>
