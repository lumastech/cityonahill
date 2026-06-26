<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { LibraryBook } from '@/types/library'

const props = defineProps<{
    books: { data: LibraryBook[]; links: unknown[] } | LibraryBook[]
    categories: string[]
    filters: { search?: string | null; category?: string | null }
}>()

const search = ref(props.filters.search ?? '')
const category = ref(props.filters.category ?? '')

let timer: ReturnType<typeof setTimeout>
function onSearch() {
    clearTimeout(timer)
    timer = setTimeout(() => {
        router.get(route('library-books.index'), { search: search.value, category: category.value }, { preserveState: true })
    }, 400)
}

function applyCategory() {
    router.get(route('library-books.index'), { search: search.value, category: category.value }, { preserveState: true })
}

const bookList = Array.isArray(props.books) ? props.books : props.books.data

// Add book modal
const showModal = ref(false)
const form = useForm({
    title: '',
    author: '',
    isbn: '',
    publisher: '',
    publish_year: '' as string | number,
    category: 'General',
    copies_total: 1,
    shelf_location: '',
    description: '',
    cover: null as File | null,
})

function openModal() { showModal.value = true }
function closeModal() { showModal.value = false; form.reset() }

function submit() {
    form.post(route('library-books.store'), {
        forceFormData: true,
        onSuccess: () => closeModal(),
    })
}
</script>

<template>
    <AppLayout>
    <Head title="Library Catalogue" />
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-gray-900 mr-4">Library Catalogue</h1>

                <input v-model="search" type="search" placeholder="Search title, author, ISBN…"
                    class="w-56 rounded-md border-gray-300 text-sm shadow-sm"
                    @input="onSearch" />

                <select v-model="category" class="rounded-md border-gray-300 text-sm shadow-sm" @change="applyCategory">
                    <option value="">All Categories</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>

                <button
                    class="ml-auto rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="openModal"
                >
                    Add Book
                </button>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                <a v-for="book in bookList" :key="book.id" :href="route('library-books.show', book.id)"
                    class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow">
                    <div class="aspect-[3/4] bg-gray-100 flex items-center justify-center overflow-hidden">
                        <img v-if="book.media?.[0]" :src="book.media[0].original_url" :alt="book.title" class="h-full w-full object-cover" />
                        <div v-else class="text-center p-2">
                            <div class="text-3xl text-gray-300">📚</div>
                        </div>
                    </div>
                    <div class="p-2 flex-1 flex flex-col">
                        <p class="text-xs font-semibold text-gray-900 line-clamp-2">{{ book.title }}</p>
                        <p class="mt-0.5 text-xs text-gray-500 line-clamp-1">{{ book.author }}</p>
                        <div class="mt-auto pt-2 flex items-center justify-between">
                            <span class="rounded-full bg-indigo-100 px-1.5 py-0.5 text-xs text-indigo-700">{{ book.category }}</span>
                            <span :class="book.is_available ? 'text-green-600' : 'text-red-500'" class="text-xs">
                                {{ book.copies_available }}/{{ book.copies_total }}
                            </span>
                        </div>
                    </div>
                </a>
            </div>

            <div v-if="!bookList.length" class="py-16 text-center text-gray-400">
                No books found{{ search ? ` for "${search}"` : '' }}.
            </div>
        </div>
    </div>

    <!-- Add Book Modal -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" @click="closeModal" />
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Add Book</h2>
                    <button class="text-gray-400 hover:text-gray-600 text-xl leading-none" @click="closeModal">&times;</button>
                </div>

                <form class="px-6 py-5 space-y-4" @submit.prevent="submit">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                        <input v-model="form.title" type="text" class="w-full rounded-md border-gray-300 text-sm" :class="{ 'border-red-400': form.errors.title }" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                    </div>

                    <!-- Author -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Author <span class="text-red-500">*</span></label>
                        <input v-model="form.author" type="text" class="w-full rounded-md border-gray-300 text-sm" :class="{ 'border-red-400': form.errors.author }" />
                        <p v-if="form.errors.author" class="mt-1 text-xs text-red-600">{{ form.errors.author }}</p>
                    </div>

                    <!-- Category + Copies -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                            <input v-model="form.category" type="text" list="category-list" class="w-full rounded-md border-gray-300 text-sm" />
                            <datalist id="category-list">
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

                    <!-- ISBN + Publisher -->
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

                    <!-- Year + Shelf -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Publish Year</label>
                            <input v-model.number="form.publish_year" type="number" min="1000" max="2099" placeholder="e.g. 2021" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Shelf Location</label>
                            <input v-model="form.shelf_location" type="text" placeholder="e.g. A3-12" class="w-full rounded-md border-gray-300 text-sm" />
                        </div>
                    </div>

                    <!-- Cover -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cover Image</label>
                        <input
                            type="file"
                            accept="image/*"
                            class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            @change="(e) => { const f = (e.target as HTMLInputElement).files?.[0]; if (f) form.cover = f }"
                        />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-md border-gray-300 text-sm" />
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" class="px-4 py-2 text-sm text-gray-700 border rounded-md hover:bg-gray-50" @click="closeModal">Cancel</button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving…' : 'Add Book' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
    </AppLayout>
</template>
