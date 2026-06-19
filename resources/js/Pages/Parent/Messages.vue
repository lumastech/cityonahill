<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { ParentMessage } from '@/types/portal'

const props = defineProps<{
    threads: Record<string, ParentMessage[]>
}>()

const threadKeys = Object.keys(props.threads)
const activeThread = ref<string | null>(threadKeys[0] ?? null)

const messages = (key: string): ParentMessage[] => props.threads[key] ?? []

const form = useForm({ recipient_id: '', pupil_id: null, message: '' })

function openThread(key: string) {
    activeThread.value = key
    form.recipient_id = key
}

function send() {
    form.post(route('portal.messages.send'), { onSuccess: () => form.reset('message') })
}

function threadLabel(msgs: ParentMessage[]): string {
    const first = msgs[0]
    if (!first) return 'Thread'
    const other = first.sender_id === Number(activeThread.value) ? first.recipient : first.sender
    return other?.name ?? 'Unknown'
}
</script>

<template>
    <Head title="Messages" />
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-semibold text-gray-900">Messages</h1>

            <div class="flex h-[600px] rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
                <!-- Thread list -->
                <div class="w-64 flex-shrink-0 border-r border-gray-200 overflow-y-auto">
                    <div
                        v-for="key in threadKeys"
                        :key="key"
                        :class="[
                            'cursor-pointer border-b border-gray-100 px-4 py-3 hover:bg-gray-50',
                            activeThread === key ? 'bg-indigo-50' : '',
                        ]"
                        @click="openThread(key)"
                    >
                        <p class="text-sm font-medium text-gray-900 truncate">{{ threadLabel(messages(key)) }}</p>
                        <p class="mt-0.5 truncate text-xs text-gray-400">{{ messages(key)[0]?.message?.slice(0, 40) }}</p>
                    </div>
                    <div v-if="!threadKeys.length" class="px-4 py-6 text-center text-sm text-gray-400">
                        No messages yet.
                    </div>
                </div>

                <!-- Message pane -->
                <div class="flex flex-1 flex-col">
                    <div class="flex-1 overflow-y-auto p-4 space-y-3">
                        <template v-if="activeThread">
                            <div
                                v-for="msg in messages(activeThread)"
                                :key="msg.id"
                                :class="['max-w-sm rounded-lg px-3 py-2 text-sm', msg.sender_id === Number(activeThread) ? 'ml-auto bg-indigo-600 text-white' : 'bg-gray-100 text-gray-900']"
                            >
                                {{ msg.message }}
                            </div>
                        </template>
                    </div>

                    <!-- Compose -->
                    <form class="border-t border-gray-200 p-3 flex gap-2" @submit.prevent="send">
                        <input
                            v-model="form.message"
                            type="text"
                            placeholder="Type a message…"
                            class="flex-1 rounded-md border-gray-300 text-sm shadow-sm"
                        />
                        <button type="submit" :disabled="form.processing || !form.message" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white disabled:opacity-50">
                            Send
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
