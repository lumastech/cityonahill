<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import type { PaginatedResponse } from '@/types/shared'

interface Borrowing {
    id: number
    borrower_type: string
    borrower_name: string | null
    borrowed_date: string
    due_date: string
    returned_date: string | null
    book?: { id: number; title: string; author: string }
    issued_by?: { id: number; name: string }
}

defineProps<{ borrowings: PaginatedResponse<Borrowing> }>()

function returnBook(id: number) {
    if (confirm('Mark this book as returned?')) {
        useForm({ returned_date: new Date().toISOString().slice(0, 10) }).patch(route('borrowings.update', id))
    }
}

function isOverdue(due: string, returned: string | null): boolean {
    if (returned) return false
    return new Date(due) < new Date()
}
</script>

<template>
    <AppLayout title="Borrowings">
        <Head title="Borrowings" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Book Borrowings</h1>
                <Link
                    :href="route('borrowings.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    + Issue Book
                </Link>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Book</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Borrower</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Borrowed</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Due</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Issued By</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!borrowings.data.length">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-400">No borrowings recorded yet.</td>
                        </tr>
                        <tr
                            v-for="b in borrowings.data"
                            :key="b.id"
                            class="hover:bg-gray-50"
                            :class="{ 'bg-red-50': isOverdue(b.due_date, b.returned_date) }"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ b.book?.title }}</div>
                                <div class="text-xs text-gray-400">{{ b.book?.author }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ b.borrower_name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ b.borrowed_date }}</td>
                            <td class="px-4 py-3 text-gray-600" :class="{ 'text-red-600 font-medium': isOverdue(b.due_date, b.returned_date) }">
                                {{ b.due_date }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="b.returned_date" class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Returned</span>
                                <span v-else-if="isOverdue(b.due_date, b.returned_date)" class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Overdue</span>
                                <span v-else class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Borrowed</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ b.issued_by?.name }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    v-if="!b.returned_date"
                                    class="text-xs text-green-700 hover:underline"
                                    @click="returnBook(b.id)"
                                >
                                    Mark Returned
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <Pagination :meta="borrowings" />
            </div>
        </div>
    </AppLayout>
</template>
