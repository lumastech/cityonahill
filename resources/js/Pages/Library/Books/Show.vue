<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { BookBorrowing, LibraryBook } from '@/types/library'
import { fmtDate } from '@/utils/date'

const props = defineProps<{
    book: LibraryBook
    cover_url: string
    categories: string[]
}>()

const STATUS_COLORS: Record<string, string> = {
    borrowed: 'bg-blue-100 text-blue-800',
    returned: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800',
    lost: 'bg-gray-100 text-gray-600',
}

// Edit modal
const showEdit = ref(false)
const form = useForm({
    title: props.book.title,
    author: props.book.author,
    isbn: props.book.isbn ?? '',
    publisher: props.book.publisher ?? '',
    publish_year: props.book.publish_year ?? ('' as string | number),
    category: props.book.category,
    copies_total: props.book.copies_total,
    shelf_location: props.book.shelf_location ?? '',
    description: props.book.description ?? '',
    cover: null as File | null,
})

function openEdit() { showEdit.value = true }
function closeEdit() { showEdit.value = false; form.clearErrors() }

function submit() {
    form.transform((data) => ({ ...data, _method: 'PUT' })).post(route('library-books.update', props.book.id), {
        forceFormData: true,
        onSuccess: () => closeEdit(),
    })
}
</script>

<template>
    <AppLayout>
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
                        <button class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="openEdit">
                            Edit
                        </button>
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
                            <td class="px-4 py-3 text-gray-600">{{ fmtDate(b.borrowed_date) }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ fmtDate(b.due_date) }}</td>
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

    <!-- Edit Book Modal -->
    <Teleport to="body">
        <div v-if="showEdit" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="closeEdit" />
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Edit Book</h2>
                    <button class="text-gray-400 hover:text-gray-600 text-xl leading-none" @click="closeEdit">&times;</button>
                </div>

                <form class="px-6 py-5 space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                        <input v-model="form.title" type="text" class="w-full rounded-md border-gray-300 text-sm" :class="{ 'border-red-400': form.errors.title }" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Author <span class="text-red-500">*</span></label>
                        <input v-model="form.author" type="text" class="w-full rounded-md border-gray-300 text-sm" :class="{ 'border-red-400': form.errors.author }" />
                        <p v-if="form.errors.author" class="mt-1 text-xs text-red-600">{{ form.errors.author }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                            <input v-model="form.category" type="text" list="edit-category-list" class="w-full rounded-md border-gray-300 text-sm" />
                            <datalist id="edit-category-list">
                                <option v-for="cat in categories" :key="cat" :value="cat" />
                                <option value="Fiction" />
                                <option value="Non-Fiction" />
                                <option value="Science" />
                                <option value="Mathematics" />
                                <option value="Reference" />
                                <option value="General" />
                            </datalist>
                            <p v-if="form.errors.category" class="mt-1 text-xs text-red-600">{{ form.errors.category }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Copies <span class="text-red-500">*</span></label>
                            <input v-model.number="form.copies_total" type="number" min="1" class="w-full rounded-md border-gray-300 text-sm" />
                            <p v-if="form.errors.copies_total" class="mt-1 text-xs text-red-600">{{ form.errors.copies_total }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ISBN</label>
                            <input v-model="form.isbn" type="text" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Publisher</label>
                            <input v-model="form.publisher" type="text" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Publish Year</label>
                            <input v-model.number="form.publish_year" type="number" min="1000" max="2099" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Shelf Location</label>
                            <input v-model="form.shelf_location" type="text" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Cover Image
                            <span v-if="cover_url" class="ml-1 text-xs text-gray-400 font-normal">(leave blank to keep current)</span>
                        </label>
                        <input
                            type="file"
                            accept="image/*"
                            class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            @change="(e) => { const f = (e.target as HTMLInputElement).files?.[0]; if (f) form.cover = f }"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" class="px-4 py-2 text-sm text-gray-700 border rounded-md hover:bg-gray-50" @click="closeEdit">Cancel</button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving…' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
    </AppLayout>
</template>
