<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import type { BookBorrowing, LibraryBook } from '@/types/library'

const props = defineProps<{
    book: LibraryBook
    cover_url: string
}>()

const STATUS_COLORS: Record<string, string> = {
    borrowed: 'bg-blue-100 text-blue-800',
    returned: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800',
    lost: 'bg-gray-100 text-gray-600',
}
</script>

<template>
    <Head :title="book.title" />
    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <a :href="route('library-books.index')" class="mb-4 block text-sm text-indigo-600 hover:underline">← Catalogue</a>

            <div class="mb-6 flex gap-6">
                <div class="w-32 flex-none">
                    <div class="aspect-[3/4] overflow-hidden rounded-lg border border-gray-200 bg-gray-100 flex items-center justify-center">
                        <img v-if="cover_url" :src="cover_url" :alt="book.title" class="h-full w-full object-cover" />
                        <span v-else class="text-4xl text-gray-300">📚</span>
                    </div>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">{{ book.title }}</h1>
                    <p class="mt-1 text-lg text-gray-600">{{ book.author }}</p>
                    <div class="mt-3 flex flex-wrap gap-2 text-sm">
                        <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-indigo-700">{{ book.category }}</span>
                        <span :class="book.is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="rounded-full px-2 py-0.5">
                            {{ book.copies_available }} / {{ book.copies_total }} available
                        </span>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                        <div v-if="book.isbn"><dt class="inline text-gray-500">ISBN: </dt><dd class="inline">{{ book.isbn }}</dd></div>
                        <div v-if="book.publisher"><dt class="inline text-gray-500">Publisher: </dt><dd class="inline">{{ book.publisher }}</dd></div>
                        <div v-if="book.publish_year"><dt class="inline text-gray-500">Year: </dt><dd class="inline">{{ book.publish_year }}</dd></div>
                        <div v-if="book.shelf_location"><dt class="inline text-gray-500">Shelf: </dt><dd class="inline">{{ book.shelf_location }}</dd></div>
                    </dl>
                    <p v-if="book.description" class="mt-3 text-sm text-gray-600">{{ book.description }}</p>
                    <div class="mt-4 flex gap-3">
                        <a v-if="book.is_available" :href="route('borrowings.create')" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Issue Book
                        </a>
                    </div>
                </div>
            </div>

            <!-- Borrowing history -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <h2 class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700">Borrowing History</h2>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Borrower</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Issued</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Due</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Returned</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Fine</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="b in book.borrowings" :key="b.id">
                            <td class="px-4 py-3 text-gray-700 capitalize">{{ b.borrower_type }} #{{ b.borrower_id }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ b.borrowed_date }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ b.due_date }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ b.returned_date ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', STATUS_COLORS[b.status]]">{{ b.status }}</span>
                            </td>
                            <td class="px-4 py-3 text-right" :class="Number(b.fine_amount) > 0 ? 'text-red-600 font-medium' : 'text-gray-400'">
                                {{ Number(b.fine_amount) > 0 ? `ZMW ${Number(b.fine_amount).toFixed(2)}` : '—' }}
                            </td>
                        </tr>
                        <tr v-if="!book.borrowings?.length">
                            <td colspan="6" class="px-4 py-6 text-center text-gray-400">No borrowing history.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
