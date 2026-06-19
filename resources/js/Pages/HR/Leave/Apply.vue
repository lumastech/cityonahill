<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import type { LeaveType } from '@/types/hr'

defineProps<{
    leave_types: LeaveType[]
    leave_balance: Record<number, number>
}>()

const form = useForm({ leave_type_id: '', start_date: '', end_date: '', reason: '' })

function submit() {
    form.post(route('leaves.store'))
}
</script>

<template>
    <Head title="Apply for Leave" />
    <div class="py-6">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-semibold text-gray-900">Apply for Leave</h1>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-700">Leave Type</label>
                        <select v-model="form.leave_type_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">Select type…</option>
                            <option v-for="lt in leave_types" :key="lt.id" :value="lt.id">
                                {{ lt.name }} ({{ leave_balance[lt.id] ?? 0 }} days remaining)
                            </option>
                        </select>
                        <p v-if="form.errors.leave_type_id" class="mt-1 text-xs text-red-600">{{ form.errors.leave_type_id }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-700">Start Date</label>
                            <input v-model="form.start_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700">End Date</label>
                            <input v-model="form.end_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700">Reason</label>
                        <textarea v-model="form.reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.reason" class="mt-1 text-xs text-red-600">{{ form.errors.reason }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a :href="route('leaves.index')" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
