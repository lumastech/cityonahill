<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { AcademicYear } from '@/types/calendar'
import { useCalendar } from '@/composables/useCalendar'

defineProps<{
    academicYears: AcademicYear[]
}>()

const { formatDateRange } = useCalendar()

const showModal = ref(false)
const editingYear = ref<AcademicYear | null>(null)

const form = useForm({
    name: '',
    start_date: '',
    end_date: '',
    is_current: false,
})

function openCreate() {
    editingYear.value = null
    form.reset()
    showModal.value = true
}

function openEdit(year: AcademicYear) {
    editingYear.value = year
    form.name = year.name
    form.start_date = year.start_date
    form.end_date = year.end_date
    form.is_current = year.is_current
    showModal.value = true
}

function submit() {
    if (editingYear.value) {
        form.put(route('academic-years.update', editingYear.value.id), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            },
        })
    } else {
        form.post(route('academic-years.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            },
        })
    }
}

function deleteYear(year: AcademicYear) {
    if (!confirm(`Delete academic year "${year.name}"? All terms and holidays will be lost.`)) {
        return
    }
    router.delete(route('academic-years.destroy', year.id))
}
</script>

<template>
    <AppLayout title="Academic Years">
        <Head title="Academic Years" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Academic Years</h1>
                <button
                    class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700"
                    @click="openCreate"
                >
                    + New Academic Year
                </button>
            </div>

            <div v-if="academicYears.length === 0" class="text-center py-12 text-gray-400">
                No academic years created yet.
            </div>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table v-if="academicYears.length > 0" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terms</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="year in academicYears" :key="year.id">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ year.name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDateRange(year.start_date, year.end_date) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ year.terms_count ?? year.terms?.length ?? 0 }} / 3</td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full"
                                    :class="year.is_current ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                >
                                    {{ year.is_current ? 'Current' : 'Past' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-3">
                                <button class="text-sm text-indigo-600 hover:underline" @click="openEdit(year)">Edit</button>
                                <button class="text-sm text-red-600 hover:underline" @click="deleteYear(year)">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showModal = false"
        >
            <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
                <h2 class="text-lg font-semibold mb-4">
                    {{ editingYear ? 'Edit Academic Year' : 'New Academic Year' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Year Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. 2025/2026"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                            required
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input v-model="form.start_date" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input v-model="form.end_date" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="is_current" v-model="form.is_current" type="checkbox" class="rounded border-gray-300" />
                        <label for="is_current" class="text-sm text-gray-700">Set as current academic year</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded" @click="showModal = false">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50">
                            {{ editingYear ? 'Save Changes' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
