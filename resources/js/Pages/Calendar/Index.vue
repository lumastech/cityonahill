<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { AcademicYear, Term, SchoolHoliday } from '@/types/calendar'
import { useCalendar } from '@/composables/useCalendar'

const props = defineProps<{
    academicYears: AcademicYear[]
}>()

const { formatDateRange, isTermActive } = useCalendar()

const showAddTermModal = ref(false)
const showHolidayModal = ref(false)
const selectedYearId = ref<number | null>(null)
const selectedTermId = ref<number | null>(null)

const termForm = useForm({
    academic_year_id: 0,
    name: '',
    number: 1 as 1 | 2 | 3,
    start_date: '',
    end_date: '',
    ca_deadline: '',
    exam_start: '',
    exam_end: '',
})

const holidayForm = useForm({
    term_id: null as number | null,
    name: '',
    start_date: '',
    end_date: '',
    type: 'school_holiday' as SchoolHoliday['type'],
})

function openAddTerm(yearId: number) {
    selectedYearId.value = yearId
    termForm.reset()
    termForm.academic_year_id = yearId
    showAddTermModal.value = true
}

function submitTerm() {
    termForm.post(route('terms.store'), {
        onSuccess: () => {
            showAddTermModal.value = false
            termForm.reset()
        },
    })
}

function openAddHoliday(termId: number) {
    selectedTermId.value = termId
    holidayForm.reset()
    holidayForm.term_id = termId
    showHolidayModal.value = true
}

function submitHoliday() {
    holidayForm.post(route('holidays.store'), {
        onSuccess: () => {
            showHolidayModal.value = false
            holidayForm.reset()
        },
    })
}

function removeHoliday(holidayId: number) {
    router.delete(route('holidays.destroy', holidayId), { preserveScroll: true })
}

function setCurrentTerm(termId: number) {
    router.patch(route('terms.update', termId), { is_current: true }, { preserveScroll: true })
}

function termStatusClass(term: Term): string {
    if (term.is_current) {
        return 'bg-green-100 text-green-800'
    }
    if (isTermActive(term)) {
        return 'bg-blue-100 text-blue-800'
    }
    return 'bg-gray-100 text-gray-600'
}
</script>

<template>
    <AppLayout title="Academic Calendar">
        <Head title="Academic Calendar" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Academic Calendar</h1>
                <a
                    :href="route('academic-years.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    Manage Academic Years →
                </a>
            </div>

            <div v-if="academicYears.length === 0" class="text-center py-12 text-gray-500">
                No academic years found. Create one to get started.
            </div>

            <div v-for="year in academicYears" :key="year.id" class="mb-8">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">
                            {{ year.name }}
                            <span
                                v-if="year.is_current"
                                class="ml-2 text-xs font-medium bg-green-100 text-green-700 px-2 py-0.5 rounded-full"
                            >
                                Current
                            </span>
                        </h2>
                        <p class="text-sm text-gray-500">{{ formatDateRange(year.start_date, year.end_date) }}</p>
                    </div>
                    <button
                        v-if="(year.terms?.length ?? 0) < 3"
                        class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700"
                        @click="openAddTerm(year.id)"
                    >
                        + Add Term
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        v-for="term in year.terms"
                        :key="term.id"
                        class="bg-white border rounded-lg p-4 shadow-sm"
                    >
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="font-medium text-gray-900">{{ term.name }}</h3>
                                <span
                                    class="inline-block text-xs px-2 py-0.5 rounded-full mt-1"
                                    :class="termStatusClass(term)"
                                >
                                    {{ term.is_current ? 'Current' : isTermActive(term) ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <button
                                v-if="!term.is_current"
                                class="text-xs text-indigo-600 hover:underline shrink-0"
                                @click="setCurrentTerm(term.id)"
                            >
                                Set Current
                            </button>
                        </div>

                        <p class="text-xs text-gray-500 mb-1">
                            {{ formatDateRange(term.start_date, term.end_date) }}
                        </p>

                        <p v-if="term.weeks !== undefined" class="text-xs text-gray-400 mb-3">
                            ~{{ term.weeks }} teaching weeks
                        </p>

                        <div class="border-t pt-2 mt-2">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-medium text-gray-600">Holidays</span>
                                <button
                                    class="text-xs text-indigo-600 hover:underline"
                                    @click="openAddHoliday(term.id)"
                                >
                                    + Add
                                </button>
                            </div>

                            <div v-if="!term.holidays?.length" class="text-xs text-gray-400 italic">
                                No holidays set
                            </div>

                            <div
                                v-for="holiday in term.holidays"
                                :key="holiday.id"
                                class="flex items-center justify-between text-xs text-gray-600 py-0.5"
                            >
                                <span>{{ holiday.name }}</span>
                                <button
                                    class="text-red-400 hover:text-red-600 ml-2"
                                    @click="removeHoliday(holiday.id)"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="!year.terms?.length"
                        class="col-span-3 text-center py-8 text-gray-400 border rounded-lg border-dashed"
                    >
                        No terms added yet for {{ year.name }}.
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Term Modal -->
        <div
            v-if="showAddTermModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showAddTermModal = false"
        >
            <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
                <h2 class="text-lg font-semibold mb-4">Add Term</h2>

                <div v-if="termForm.errors.conflict" class="mb-3 text-sm text-red-600 bg-red-50 p-2 rounded">
                    {{ termForm.errors.conflict }}
                </div>

                <form @submit.prevent="submitTerm" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input v-model="termForm.name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="e.g. Term 1" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Term Number</label>
                        <select v-model.number="termForm.number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option :value="1">1</option>
                            <option :value="2">2</option>
                            <option :value="3">3</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input v-model="termForm.start_date" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input v-model="termForm.end_date" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CA Deadline (optional)</label>
                        <input v-model="termForm.ca_deadline" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded" @click="showAddTermModal = false">Cancel</button>
                        <button type="submit" :disabled="termForm.processing" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50">
                            Add Term
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Holiday Modal -->
        <div
            v-if="showHolidayModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showHolidayModal = false"
        >
            <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
                <h2 class="text-lg font-semibold mb-4">Add Holiday</h2>

                <form @submit.prevent="submitHoliday" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input v-model="holidayForm.name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select v-model="holidayForm.type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="public_holiday">Public Holiday</option>
                            <option value="school_holiday">School Holiday</option>
                            <option value="event">Event</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input v-model="holidayForm.start_date" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input v-model="holidayForm.end_date" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded" @click="showHolidayModal = false">Cancel</button>
                        <button type="submit" :disabled="holidayForm.processing" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50">
                            Add Holiday
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
