<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

interface SettingField { label: string; value: string }
interface PaymentSettings {
    cash_enabled: string
    izb_enabled: string
    izb_base_url: string
    izb_username: string
    izb_password: string
    izb_verify_ssl: string
    lenco_enabled: string
    lenco_base_url: string
    lenco_api_token: string
    lenco_webhook_hash: string
}

const props = defineProps<{
    settings: Record<string, SettingField>
    payment_settings: PaymentSettings
}>()

const values: Record<string, string> = {}
for (const [key, field] of Object.entries(props.settings)) {
    values[key] = field.value
}
const form = useForm({ settings: values })
function submit() { form.put(route('settings.update')) }

const payForm = useForm({
    cash_enabled:       props.payment_settings.cash_enabled === '1',
    izb_enabled:        props.payment_settings.izb_enabled === '1',
    izb_base_url:       props.payment_settings.izb_base_url,
    izb_username:       props.payment_settings.izb_username,
    izb_password:       props.payment_settings.izb_password,
    izb_verify_ssl:     props.payment_settings.izb_verify_ssl === '1',
    lenco_enabled:      props.payment_settings.lenco_enabled === '1',
    lenco_base_url:     props.payment_settings.lenco_base_url,
    lenco_api_token:    props.payment_settings.lenco_api_token,
    lenco_webhook_hash: props.payment_settings.lenco_webhook_hash,
})
function savePayment() { payForm.put(route('settings.payment.update')) }
</script>

<template>
    <AppLayout title="Settings">
        <Head title="Settings" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto space-y-8">
            <h1 class="text-2xl font-bold text-gray-900">School Settings</h1>

            <!-- General settings -->
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-5 text-base font-semibold text-gray-900">General</h2>
                <form class="space-y-5" @submit.prevent="submit">
                    <div v-for="(field, key) in settings" :key="key">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ field.label }}</label>
                        <input v-model="form.settings[key]" type="text"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                        <p v-if="form.wasSuccessful" class="text-sm text-green-600">Settings saved.</p>
                        <div v-else />
                        <button type="submit" :disabled="form.processing"
                            class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Payment gateways -->
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h2 class="mb-1 text-base font-semibold text-gray-900">Payment Gateways</h2>
                <p class="mb-6 text-sm text-gray-500">Configure which payment methods are available. If all gateways are disabled, only cash is accepted.</p>

                <form class="space-y-7" @submit.prevent="savePayment">

                    <!-- Cash -->
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Cash Payments</p>
                            <p class="text-xs text-gray-500">Allow finance staff to record cash receipts</p>
                        </div>
                        <button type="button"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200"
                            :class="payForm.cash_enabled ? 'bg-indigo-600' : 'bg-gray-200'"
                            @click="payForm.cash_enabled = !payForm.cash_enabled">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
                                :class="payForm.cash_enabled ? 'translate-x-5' : 'translate-x-0'" />
                        </button>
                    </div>

                    <!-- IZB Pay -->
                    <div class="rounded-lg border border-gray-200 p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">IZB Pay <span class="ml-1.5 rounded-full bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700">Primary</span></p>
                                <p class="text-xs text-gray-500">Mobile money via CGRATE/IZB (synchronous)</p>
                            </div>
                            <button type="button"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200"
                                :class="payForm.izb_enabled ? 'bg-indigo-600' : 'bg-gray-200'"
                                @click="payForm.izb_enabled = !payForm.izb_enabled">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
                                    :class="payForm.izb_enabled ? 'translate-x-5' : 'translate-x-0'" />
                            </button>
                        </div>
                        <div v-if="payForm.izb_enabled" class="space-y-3 border-t border-gray-100 pt-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Base URL</label>
                                <input v-model="payForm.izb_base_url" type="text"
                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Username</label>
                                    <input v-model="payForm.izb_username" type="text" autocomplete="off"
                                        class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Password</label>
                                    <input v-model="payForm.izb_password" type="password" autocomplete="new-password"
                                        class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                                </div>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input v-model="payForm.izb_verify_ssl" type="checkbox" class="rounded border-gray-300" />
                                Verify SSL certificate
                            </label>
                        </div>
                    </div>

                    <!-- Lenco Pay -->
                    <div class="rounded-lg border border-gray-200 p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Lenco Pay <span class="ml-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">Fallback</span></p>
                                <p class="text-xs text-gray-500">Mobile money via Lenco API (async — webhook required)</p>
                            </div>
                            <button type="button"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200"
                                :class="payForm.lenco_enabled ? 'bg-indigo-600' : 'bg-gray-200'"
                                @click="payForm.lenco_enabled = !payForm.lenco_enabled">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
                                    :class="payForm.lenco_enabled ? 'translate-x-5' : 'translate-x-0'" />
                            </button>
                        </div>
                        <div v-if="payForm.lenco_enabled" class="space-y-3 border-t border-gray-100 pt-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Base URL</label>
                                <input v-model="payForm.lenco_base_url" type="text"
                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">API Token</label>
                                <input v-model="payForm.lenco_api_token" type="password" autocomplete="new-password"
                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Webhook Hash Secret</label>
                                <input v-model="payForm.lenco_webhook_hash" type="password" autocomplete="new-password"
                                    class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                                <p class="mt-1 text-xs text-gray-400">
                                    Configure Lenco to POST to: <code class="font-mono">{{ $page.props.settings?.app_url ?? '' }}/webhooks/lenco</code>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                        <p v-if="payForm.wasSuccessful" class="text-sm text-green-600">Payment settings saved.</p>
                        <div v-else />
                        <button type="submit" :disabled="payForm.processing"
                            class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save Payment Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
