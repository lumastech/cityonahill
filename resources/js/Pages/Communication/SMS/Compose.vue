<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

defineProps<{
    grades: { id: number; name: string }[]
}>()

const mode = ref<'manual' | 'grade'>('manual')
const rawPhones = ref('')

const form = useForm({
    phones: [] as string[],
    message: '',
    provider: null as string | null,
})

const charCount = computed(() => form.message.length)
const smsCount = computed(() => Math.ceil(charCount.value / 160) || 1)

function submit() {
    form.phones = rawPhones.value
        .split(/[\n,;]+/)
        .map((p) => p.trim())
        .filter(Boolean)

    form.post(route('sms.send'))
}
</script>

<template>
    <div class="mx-auto max-w-2xl p-6">
        <h1 class="mb-6 text-2xl font-bold text-gray-900">Compose SMS</h1>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Recipients</label>
                <div class="mb-2 flex gap-2">
                    <button
                        type="button"
                        @click="mode = 'manual'"
                        :class="['rounded px-3 py-1 text-sm', mode === 'manual' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600']"
                    >
                        Enter Numbers
                    </button>
                    <button
                        type="button"
                        @click="mode = 'grade'"
                        :class="['rounded px-3 py-1 text-sm', mode === 'grade' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600']"
                    >
                        Grade Parents
                    </button>
                </div>

                <textarea
                    v-if="mode === 'manual'"
                    v-model="rawPhones"
                    rows="4"
                    placeholder="Enter phone numbers separated by commas, semicolons, or new lines"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />

                <select
                    v-else
                    v-model="form.phones[0]"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="">-- Select Grade --</option>
                    <option v-for="grade in grades" :key="grade.id" :value="String(grade.id)">
                        {{ grade.name }}
                    </option>
                </select>

                <p v-if="form.errors.phones" class="mt-1 text-sm text-red-600">{{ form.errors.phones }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Message</label>
                <textarea
                    v-model="form.message"
                    rows="5"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <div class="mt-1 flex justify-between text-xs text-gray-400">
                    <span :class="charCount > 160 ? 'text-yellow-600' : ''">
                        {{ charCount }} characters
                    </span>
                    <span>{{ smsCount }} SMS part{{ smsCount !== 1 ? 's' : '' }}</span>
                </div>
                <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Provider (optional)</label>
                <select
                    v-model="form.provider"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option :value="null">Auto (default)</option>
                    <option value="airtel">Airtel</option>
                    <option value="mtn">MTN</option>
                </select>
            </div>

            <div class="flex gap-3">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    Send SMS
                </button>
                <a :href="route('sms.log')" class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    View Log
                </a>
            </div>
        </form>
    </div>
</template>
