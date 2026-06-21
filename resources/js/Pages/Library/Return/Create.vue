<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { BookBorrowing } from '@/types/library'
import { fmtDate } from '@/utils/date'

const props = defineProps<{
    active_borrowings: BookBorrowing[]
}>()

const search = ref('')

const filtered = computed(() =>
    props.active_borrowings.filter(b => {
        const s = search.value.toLowerCase()
        return !s || b.book?.title.toLowerCase().includes(s) || String(b.borrower_id).includes(s)
    })
)

const form = useForm({ borrowing_id: '', returned_date: new Date().toISOString().slice(0, 10), fine_paid: '' })

const selectedBorrowing = computed(() => props.active_borrowings.find(b => b.id === Number(form.borrowing_id)))

const today = new Date().toISOString().slice(0, 10)
const daysOverdue = computed(() => {
    if (!selectedBorrowing.value) return 0
    const due = new Date(selectedBorrowing.value.due_date)
    const now = new Date()
    return Math.max(0, Math.floor((now.getTime() - due.getTime()) / 86400000))
})

const estimatedFine = computed(() => daysOverdue.value * 0.5)

function submit() {
    form.post(route('borrowings.update', { borrowing: form.borrowing_id }))
}
</script>

<template>
    <AppLayout>
    <Head title="Return Book" />
    <div class="py-6">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-semibold text-gray-900">Return Book</h1>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4">
                    <label class="block text-sm text-gray-700">Search borrowings</label>
                    <input v-model="search" type="search" placeholder="Book title or borrower ID…"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                </div>

                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm text-gray-700">Borrowing</label>
                        <select v-model="form.borrowing_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">Select borrowing…</option>
                            <option v-for="b in filtered" :key="b.id" :value="b.id">
                                {{ b.book?.title }} — {{ b.borrower_type }} #{{ b.borrower_id }} (due {{ fmtDate(b.due_date) }})
                            </option>
                        </select>
                    </div>

                    <div v-if="selectedBorrowing && daysOverdue > 0" class="rounded-md bg-red-50 border border-red-200 p-3 text-sm">
                        <p class="font-semibold text-red-700">⚠ Overdue by {{ daysOverdue }} day{{ daysOverdue !== 1 ? 's' : '' }}</p>
                        <p class="text-red-600">Estimated fine: ZMW {{ estimatedFine.toFixed(2) }} (ZMW 0.50/day)</p>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700">Return Date</label>
                        <input v-model="form.returned_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700">Fine Paid (ZMW, optional)</label>
                        <input v-model="form.fine_paid" type="number" step="0.01" placeholder="0.00"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a :href="route('borrowings.index')" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" :disabled="form.processing || !form.borrowing_id"
                            class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">
                            Process Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
