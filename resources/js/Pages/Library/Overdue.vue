<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import type { BookBorrowing } from '@/types/library'

defineProps<{ overdue_borrowings: BookBorrowing[] }>()

function daysOverdue(dueDate: string): number {
    const due = new Date(dueDate)
    const now = new Date()
    return Math.max(0, Math.floor((now.getTime() - due.getTime()) / 86400000))
}

function fineAmount(dueDate: string): string {
    return (daysOverdue(dueDate) * 0.5).toFixed(2)
}
</script>

<template>
    <Head title="Overdue Books" />
    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">
                    Overdue Books
                    <span v-if="overdue_borrowings.length" class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-sm font-medium text-red-700">
                        {{ overdue_borrowings.length }}
                    </span>
                </h1>
                <a :href="route('library-books.index')" class="text-sm text-indigo-600 hover:underline">← Catalogue</a>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Book</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Borrower</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Borrowed</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Due</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Days Overdue</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Fine (ZMW)</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="b in overdue_borrowings" :key="b.id" class="bg-red-50/30">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ b.book?.title }}</p>
                                <p class="text-xs text-gray-500">{{ b.book?.author }}</p>
                            </td>
                            <td class="px-4 py-3 capitalize text-gray-700">{{ b.borrower_type }} #{{ b.borrower_id }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ b.borrowed_date }}</td>
                            <td class="px-4 py-3 font-medium text-red-700">{{ b.due_date }}</td>
                            <td class="px-4 py-3 text-right font-bold text-red-700">{{ daysOverdue(b.due_date) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-red-600">{{ fineAmount(b.due_date) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a :href="route('borrowings.index')" class="text-xs text-indigo-600 hover:underline">Return</a>
                            </td>
                        </tr>
                        <tr v-if="!overdue_borrowings.length">
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400">No overdue borrowings.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
