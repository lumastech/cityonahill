<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import type { SchoolMessage } from '@/types/communication'

const props = defineProps<{
    threads?: SchoolMessage[]
    thread?: SchoolMessage[]
    threadUser?: { id: number; name: string }
    staff: { id: number; name: string }[]
}>()

const inThread = computed(() => !!props.thread)

const newForm = useForm({
    recipient_id: null as number | null,
    message: '',
    pupil_id: null as number | null,
})

const replyForm = useForm({
    recipient_id: props.threadUser?.id ?? null,
    message: '',
    pupil_id: null as number | null,
})

function sendNew() {
    newForm.post(route('messages.store'), {
        onSuccess: () => newForm.reset(),
    })
}

function sendReply() {
    replyForm.post(route('messages.store'), {
        onSuccess: () => replyForm.reset(),
    })
}

function openThread(userId: number) {
    router.get(route('messages.thread', userId))
}

const currentUserId = ref(0)
</script>

<template>
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">
                <span v-if="inThread">Thread with {{ threadUser?.name }}</span>
                <span v-else>Messages</span>
            </h1>
            <a v-if="inThread" :href="route('messages.index')" class="text-sm text-indigo-600 hover:underline">
                &larr; Back to inbox
            </a>
        </div>

        <div v-if="!inThread" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="mb-3 text-lg font-semibold text-gray-800">Inbox</h2>
                <div class="space-y-2">
                    <div
                        v-for="msg in threads"
                        :key="msg.id"
                        @click="openThread(msg.sender_id === currentUserId ? msg.recipient_id : msg.sender_id)"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-4 hover:bg-gray-50"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 font-semibold">
                            {{ (msg.sender?.name ?? '?')[0].toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900">{{ msg.sender?.name }}</span>
                                <span class="text-xs text-gray-400">{{ new Date(msg.created_at).toLocaleDateString() }}</span>
                            </div>
                            <p class="truncate text-sm text-gray-500">{{ msg.message }}</p>
                        </div>
                        <span v-if="!msg.read_at" class="h-2 w-2 rounded-full bg-indigo-500" />
                    </div>
                    <p v-if="!threads?.length" class="py-8 text-center text-gray-400">No messages yet.</p>
                </div>
            </div>

            <div>
                <h2 class="mb-3 text-lg font-semibold text-gray-800">New Message</h2>
                <form @submit.prevent="sendNew" class="space-y-3 rounded-lg border border-gray-200 p-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">To</label>
                        <select
                            v-model="newForm.recipient_id"
                            required
                            class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option :value="null">-- Select --</option>
                            <option v-for="user in staff" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Message</label>
                        <textarea
                            v-model="newForm.message"
                            rows="4"
                            required
                            class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="newForm.processing"
                        class="w-full rounded bg-indigo-600 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Send
                    </button>
                </form>
            </div>
        </div>

        <div v-else>
            <div class="mb-6 max-h-[60vh] space-y-3 overflow-y-auto">
                <div
                    v-for="msg in thread"
                    :key="msg.id"
                    :class="['flex', msg.sender_id === threadUser?.id ? 'justify-start' : 'justify-end']"
                >
                    <div
                        :class="[
                            'max-w-sm rounded-lg px-4 py-2 text-sm',
                            msg.sender_id === threadUser?.id
                                ? 'bg-gray-100 text-gray-800'
                                : 'bg-indigo-600 text-white'
                        ]"
                    >
                        <p>{{ msg.message }}</p>
                        <p :class="['mt-1 text-xs', msg.sender_id === threadUser?.id ? 'text-gray-400' : 'text-indigo-200']">
                            {{ new Date(msg.created_at).toLocaleString() }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="sendReply" class="flex gap-2">
                <textarea
                    v-model="replyForm.message"
                    rows="2"
                    required
                    placeholder="Type a reply..."
                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <button
                    type="submit"
                    :disabled="replyForm.processing"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    Send
                </button>
            </form>
        </div>
    </div>
</template>
