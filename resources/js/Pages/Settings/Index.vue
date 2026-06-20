<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

interface SettingField {
    label: string
    value: string
}

const props = defineProps<{
    settings: Record<string, SettingField>
}>()

const values: Record<string, string> = {}
for (const [key, field] of Object.entries(props.settings)) {
    values[key] = field.value
}

const form = useForm({ settings: values })

function submit() {
    form.put(route('settings.update'))
}
</script>

<template>
    <AppLayout title="Settings">
        <Head title="Settings" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
            <h1 class="mb-6 text-2xl font-bold text-gray-900">School Settings</h1>

            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <form class="space-y-5" @submit.prevent="submit">
                    <div v-for="(field, key) in settings" :key="key">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ field.label }}</label>
                        <input
                            v-model="form.settings[key]"
                            type="text"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                        />
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                        <p v-if="form.wasSuccessful" class="text-sm text-green-600">Settings saved.</p>
                        <div v-else />
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
