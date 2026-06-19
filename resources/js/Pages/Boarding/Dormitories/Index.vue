<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { Dormitory } from '@/types/boarding'

defineProps<{ dormitories: Dormitory[] }>()

const showForm = ref(false)
const form = useForm({ name: '', gender: 'male', capacity: 50, description: '' })

function submit() {
    form.post(route('dormitories.store'), {
        onSuccess: () => { showForm.value = false; form.reset() },
    })
}

function capacityPct(occupied: number, total: number): number {
    return total > 0 ? Math.round((occupied / total) * 100) : 0
}

function barColor(pct: number): string {
    if (pct >= 90) return 'bg-red-500'
    if (pct >= 70) return 'bg-yellow-400'
    return 'bg-green-500'
}
</script>

<template>
    <Head title="Dormitories" />
    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Dormitories</h1>
                <button @click="showForm = !showForm"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    + New Dormitory
                </button>
            </div>

            <!-- Create form -->
            <div v-if="showForm" class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <form class="grid gap-3 sm:grid-cols-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-xs text-gray-600">Name</label>
                        <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Gender</label>
                        <select v-model="form.gender" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Capacity</label>
                        <input v-model="form.capacity" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" :disabled="form.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Create
                        </button>
                        <button type="button" @click="showForm = false"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="dorm in dormitories" :key="dorm.id"
                    class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div>
                            <a :href="route('dormitories.show', dorm.id)" class="font-semibold text-gray-900 hover:text-indigo-600">
                                {{ dorm.name }}
                            </a>
                            <div class="mt-1 flex gap-2">
                                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', dorm.gender === 'male' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700']">
                                    {{ dorm.gender === 'male' ? '♂ Male' : '♀ Female' }}
                                </span>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-gray-500">{{ dorm.capacity }} beds</span>
                    </div>

                    <div class="mt-3">
                        <div class="mb-1 flex justify-between text-xs text-gray-500">
                            <span>Occupancy</span>
                            <span>{{ dorm.occupied_count ?? 0 }} / {{ dorm.total_beds ?? dorm.capacity }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                            <div :class="['h-2 rounded-full', barColor(capacityPct(dorm.occupied_count ?? 0, dorm.total_beds ?? dorm.capacity))]"
                                :style="{ width: capacityPct(dorm.occupied_count ?? 0, dorm.total_beds ?? dorm.capacity) + '%' }" />
                        </div>
                    </div>

                    <div class="mt-3 flex gap-3 text-xs">
                        <a :href="route('dormitories.show', dorm.id)" class="text-indigo-600 hover:underline">View beds</a>
                        <span class="text-green-600">{{ dorm.available_count ?? 0 }} available</span>
                    </div>
                </div>
            </div>

            <div v-if="!dormitories.length" class="py-16 text-center text-gray-400">No dormitories yet.</div>
        </div>
    </div>
</template>
