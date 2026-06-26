<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

interface Book { id: number; title: string; author: string; copies_available: number }
interface BorrowerOption { id: number; label: string }

const props = defineProps<{
    available_books: Book[]
}>()

const borrowerType = ref<'pupil' | 'staff'>('pupil')

const form = useForm({
    book_id: '' as string | number,
    borrower_type: 'pupil' as 'pupil' | 'staff',
    borrower_id: '' as string | number,
    due_date: '',
})

const defaultDueDate = computed(() => {
    const d = new Date()
    d.setDate(d.getDate() + 14)
    return d.toISOString().slice(0, 10)
})

form.due_date = defaultDueDate.value

function selectBorrowerType(type: 'pupil' | 'staff') {
    borrowerType.value = type
    form.borrower_type = type
    form.borrower_id = ''
    borrowerQuery.value = ''
    borrowerLabel.value = ''
    dropdownResults.value = []
}

// Autocomplete state
const borrowerQuery = ref('')
const borrowerLabel = ref('')
const dropdownResults = ref<BorrowerOption[]>([])
const showDropdown = ref(false)
let debounceTimer: ReturnType<typeof setTimeout>

function onBorrowerInput() {
    form.borrower_id = ''
    borrowerLabel.value = ''
    clearTimeout(debounceTimer)
    const q = borrowerQuery.value.trim()
    if (q.length < 1) { dropdownResults.value = []; showDropdown.value = false; return }
    debounceTimer = setTimeout(async () => {
        const res = await fetch(route('borrowings.search-borrower') + `?type=${borrowerType.value}&q=${encodeURIComponent(q)}`)
        dropdownResults.value = await res.json()
        showDropdown.value = true
    }, 300)
}

function selectBorrower(option: BorrowerOption) {
    form.borrower_id = option.id
    borrowerLabel.value = option.label
    borrowerQuery.value = option.label
    showDropdown.value = false
}

function submit() {
    form.post(route('borrowings.store'))
}
</script>

<template>
    <AppLayout>
    <Head title="Issue Book" />
    <div class="py-6">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-semibold text-gray-900">Issue Book</h1>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <form class="space-y-4" @submit.prevent="submit">
                    <!-- Book -->
                    <div>
                        <label class="block text-sm text-gray-700">Book</label>
                        <select v-model="form.book_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">Select book…</option>
                            <option v-for="b in available_books" :key="b.id" :value="b.id">
                                {{ b.title }} — {{ b.author }} ({{ b.copies_available }} available)
                            </option>
                        </select>
                        <p v-if="form.errors.book_id" class="mt-1 text-xs text-red-600">{{ form.errors.book_id }}</p>
                    </div>

                    <!-- Borrower type toggle -->
                    <div>
                        <label class="block text-sm text-gray-700">Borrower Type</label>
                        <div class="mt-1 flex rounded-md border border-gray-300 overflow-hidden">
                            <button
                                type="button"
                                :class="['flex-1 py-2 text-sm font-medium', borrowerType === 'pupil' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700']"
                                @click="selectBorrowerType('pupil')"
                            >Pupil</button>
                            <button
                                type="button"
                                :class="['flex-1 py-2 text-sm font-medium', borrowerType === 'staff' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700']"
                                @click="selectBorrowerType('staff')"
                            >Staff</button>
                        </div>
                    </div>

                    <!-- Borrower autocomplete -->
                    <div>
                        <label class="block text-sm text-gray-700">
                            {{ borrowerType === 'pupil' ? 'Pupil' : 'Staff Member' }}
                        </label>
                        <div class="relative mt-1">
                            <input
                                v-model="borrowerQuery"
                                type="text"
                                :placeholder="`Search ${borrowerType === 'pupil' ? 'name or admission no' : 'name or employee no'}…`"
                                autocomplete="off"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm"
                                :class="{ 'border-red-400': form.errors.borrower_id }"
                                @input="onBorrowerInput"
                                @focus="showDropdown = dropdownResults.length > 0"
                                @blur="setTimeout(() => showDropdown = false, 150)"
                            />
                            <!-- Selected indicator -->
                            <span
                                v-if="form.borrower_id"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-green-600 text-xs font-medium"
                            >✓ selected</span>

                            <!-- Dropdown -->
                            <ul
                                v-if="showDropdown && dropdownResults.length"
                                class="absolute z-20 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg max-h-52 overflow-y-auto text-sm"
                            >
                                <li
                                    v-for="opt in dropdownResults"
                                    :key="opt.id"
                                    class="cursor-pointer px-3 py-2 hover:bg-indigo-50"
                                    @mousedown.prevent="selectBorrower(opt)"
                                >
                                    {{ opt.label }}
                                </li>
                            </ul>
                            <p v-if="showDropdown && dropdownResults.length === 0 && borrowerQuery.length > 0 && !form.borrower_id" class="absolute z-20 mt-1 w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-400 shadow">
                                No results found.
                            </p>
                        </div>
                        <p v-if="form.errors.borrower_id" class="mt-1 text-xs text-red-600">{{ form.errors.borrower_id }}</p>
                    </div>

                    <!-- Due date -->
                    <div>
                        <label class="block text-sm text-gray-700">Due Date</label>
                        <input v-model="form.due_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.due_date" class="mt-1 text-xs text-red-600">{{ form.errors.due_date }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a :href="route('borrowings.index')" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.borrower_id"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Issue Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
