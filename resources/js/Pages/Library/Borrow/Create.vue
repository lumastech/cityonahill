<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

interface Pupil { id: number; first_name: string; last_name: string; admission_no: string }
interface Staff { id: number; user_id: number; employee_no: string; position: string; user?: { id: number; name: string } }
interface Book { id: number; title: string; author: string; copies_available: number }

const props = defineProps<{
    available_books: Book[]
    pupils: Pupil[]
    staff: Staff[]
}>()

const borrowerType = ref<'pupil' | 'staff'>('pupil')

const form = useForm({
    book_id: '',
    borrower_type: 'pupil' as 'pupil' | 'staff',
    borrower_id: '',
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

                    <div>
                        <label class="block text-sm text-gray-700">Borrower Type</label>
                        <div class="mt-1 flex rounded-md border border-gray-300 overflow-hidden">
                            <button type="button" :class="['flex-1 py-2 text-sm font-medium', borrowerType === 'pupil' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700']"
                                @click="selectBorrowerType('pupil')">Pupil</button>
                            <button type="button" :class="['flex-1 py-2 text-sm font-medium', borrowerType === 'staff' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700']"
                                @click="selectBorrowerType('staff')">Staff</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700">{{ borrowerType === 'pupil' ? 'Pupil' : 'Staff Member' }}</label>
                        <select v-model="form.borrower_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">Select {{ borrowerType }}…</option>
                            <template v-if="borrowerType === 'pupil'">
                                <option v-for="p in pupils" :key="p.id" :value="p.id">
                                    {{ p.first_name }} {{ p.last_name }} ({{ p.admission_no }})
                                </option>
                            </template>
                            <template v-else>
                                <option v-for="s in staff" :key="s.id" :value="s.id">
                                    {{ s.user?.name }} — {{ s.employee_no }}
                                </option>
                            </template>
                        </select>
                        <p v-if="form.errors.borrower_id" class="mt-1 text-xs text-red-600">{{ form.errors.borrower_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-700">Due Date</label>
                        <input v-model="form.due_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.due_date" class="mt-1 text-xs text-red-600">{{ form.errors.due_date }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a :href="route('borrowings.index')" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Issue Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
