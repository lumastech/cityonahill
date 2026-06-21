<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import type { Pupil, SchoolStatistics } from '@/types/pupils'
import type { PaginatedResponse } from '@/types/shared'
import { usePupils } from '@/composables/usePupils'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps<{
    pupils: PaginatedResponse<Pupil>
    filters: {
        grade_id?: string
        stream_id?: string
        sex?: string
        status?: string
        search?: string
    }
    grades: Array<{ id: number; name: string }>
    stats?: SchoolStatistics
}>()

const { statusClass, sexClass, exportToCsv } = usePupils()
const { can } = usePermissions()

const search = ref(props.filters.search ?? '')
const selectedGrade = ref(props.filters.grade_id ?? '')
const selectedSex = ref(props.filters.sex ?? '')
const selectedStatus = ref(props.filters.status ?? '')
const selected = ref<number[]>([])

function applyFilters() {
    router.get(
        route('pupils.index'),
        {
            search: search.value || undefined,
            grade_id: selectedGrade.value || undefined,
            sex: selectedSex.value || undefined,
            status: selectedStatus.value || undefined,
        },
        { preserveState: true, replace: true }
    )
}

function toggleSelect(id: number) {
    const idx = selected.value.indexOf(id)
    if (idx === -1) {
        selected.value.push(id)
    } else {
        selected.value.splice(idx, 1)
    }
}

function selectAll() {
    if (selected.value.length === props.pupils.data.length) {
        selected.value = []
    } else {
        selected.value = props.pupils.data.map((p) => p.id)
    }
}

function handleExport() {
    exportToCsv(props.pupils.data)
}

const allSelected = computed(
    () => selected.value.length === props.pupils.data.length && props.pupils.data.length > 0
)
</script>

<template>
    <AppLayout title="Pupils">
        <Head title="Pupils" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">

            <!-- Stats bar -->
            <div v-if="stats" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                <div class="bg-white rounded-lg border px-4 py-3 text-center shadow-sm">
                    <p class="text-2xl font-bold text-gray-900">{{ stats.total_pupils }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Total Pupils</p>
                </div>
                <div class="bg-blue-50 rounded-lg border border-blue-100 px-4 py-3 text-center shadow-sm">
                    <p class="text-2xl font-bold text-blue-700">{{ stats.by_gender.male }}</p>
                    <p class="text-xs text-blue-500 mt-0.5">Male</p>
                </div>
                <div class="bg-pink-50 rounded-lg border border-pink-100 px-4 py-3 text-center shadow-sm">
                    <p class="text-2xl font-bold text-pink-700">{{ stats.by_gender.female }}</p>
                    <p class="text-xs text-pink-500 mt-0.5">Female</p>
                </div>
                <div class="bg-green-50 rounded-lg border border-green-100 px-4 py-3 text-center shadow-sm">
                    <p class="text-2xl font-bold text-green-700">{{ stats.by_status.active }}</p>
                    <p class="text-xs text-green-500 mt-0.5">Active</p>
                </div>
            </div>
            <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                <div v-for="i in 4" :key="i" class="bg-gray-100 rounded-lg h-16 animate-pulse" />
            </div>

            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Pupils</h1>
                <div class="flex items-center gap-2">
                    <button
                        v-if="selected.length"
                        class="text-sm px-3 py-1.5 border rounded text-gray-700 hover:bg-gray-50"
                        @click="handleExport"
                    >
                        Export {{ selected.length }} selected
                    </button>
                    <Link
                        v-if="can('pupil.create')"
                        :href="route('pupils.create')"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700"
                    >
                        + Admit Pupil
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg border p-3 mb-4 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search name or admission no…"
                        class="w-full border-gray-300 rounded-md text-sm"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <div>
                    <select v-model="selectedGrade" class="border-gray-300 rounded-md text-sm" @change="applyFilters">
                        <option value="">All Grades</option>
                        <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                    </select>
                </div>
                <div>
                    <select v-model="selectedSex" class="border-gray-300 rounded-md text-sm" @change="applyFilters">
                        <option value="">All Sexes</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div>
                    <select v-model="selectedStatus" class="border-gray-300 rounded-md text-sm" @change="applyFilters">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="transferred">Transferred</option>
                        <option value="withdrawn">Withdrawn</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <button
                    class="px-3 py-1.5 text-sm bg-gray-100 rounded hover:bg-gray-200"
                    @click="applyFilters"
                >
                    Search
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 w-8">
                                <input type="checkbox" :checked="allSelected" @change="selectAll" class="rounded" />
                            </th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Pupil</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Admission No</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Grade / Stream</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Sex</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Age</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="pupils.data.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-400">No pupils found.</td>
                        </tr>
                        <tr
                            v-for="pupil in pupils.data"
                            :key="pupil.id"
                            class="hover:bg-gray-50 cursor-pointer"
                            @click="router.visit(route('pupils.show', pupil.id))"
                        >
                            <td class="px-4 py-3" @click.stop>
                                <input
                                    type="checkbox"
                                    :checked="selected.includes(pupil.id)"
                                    class="rounded"
                                    @change="toggleSelect(pupil.id)"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="route('pupils.show', pupil.id)" class="font-medium text-indigo-700 hover:underline">{{ pupil.first_name }} {{ pupil.last_name }}</Link>
                            </td>
                            <td class="px-4 py-3 font-mono text-gray-600">{{ pupil.admission_no }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ pupil.grade?.name }}
                                <span v-if="pupil.stream" class="text-gray-400"> / {{ pupil.stream.name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full"
                                    :class="sexClass(pupil.sex)"
                                >
                                    {{ pupil.sex === 'male' ? 'M' : 'F' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ pupil.age }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full"
                                    :class="statusClass(pupil.status)"
                                >
                                    {{ pupil.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <Pagination :meta="pupils" />
            </div>
        </div>
    </AppLayout>
</template>
