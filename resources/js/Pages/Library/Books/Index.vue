<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
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

                <a :href="route('library-books.store')" class="ml-auto rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Add Book
                </a>
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
    </AppLayout>
</template>
