<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { FeedingSession } from '@/types/feeding'

interface Pupil { id: number; first_name: string; last_name: string; admission_no: string }
interface Stream { id: number; name: string }
interface SessionPage { data: FeedingSession[]; links: unknown[] }

const props = defineProps<{
    sessions?: SessionPage
    streams?: Stream[]
    session?: FeedingSession
    pupils?: Pupil[]
    served_ids?: number[]
}>()

// Session-list mode
const openForm = useForm({ date: new Date().toISOString().slice(0, 10), meal_type: 'lunch', stream_id: '' })

function openSession() {
    openForm.post(route('feeding-sessions.store'))
}

// Register mode (session detail)
const checkedIds = ref<Set<number>>(new Set(props.served_ids ?? []))

function toggleAll(checked: boolean) {
    if (!props.pupils) return
    if (checked) props.pupils.forEach(p => checkedIds.value.add(p.id))
    else checkedIds.value.clear()
}

const allChecked = computed(() => props.pupils?.every(p => checkedIds.value.has(p.id)) ?? false)

const saveForm = useForm({ session_id: props.session?.id ?? 0, pupil_ids: [] as number[] })

function save() {
    saveForm.pupil_ids = [...checkedIds.value]
    saveForm.patch(route('feeding-sessions.update', props.session!.id))
}

function finalize() {
    if (!confirm('Finalize this session? No further changes will be allowed.')) return
    router.post(route('feeding-sessions.finalize', props.session!.id))
}

const MEAL_COLORS: Record<string, string> = {
    breakfast: 'bg-yellow-100 text-yellow-800',
    lunch: 'bg-green-100 text-green-800',
    snack: 'bg-blue-100 text-blue-800',
}
</script>

<template>
    <AppLayout>
    <Head :title="session ? `Feeding Register — ${session.date}` : 'Daily Feeding Register'" />
    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            <!-- Session detail / register mode -->
            <template v-if="session">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <a :href="route('feeding-sessions.index')" class="text-sm text-indigo-600 hover:underline">← Sessions</a>
                        <h1 class="mt-1 text-2xl font-bold text-gray-900">
                            {{ session.date }}
                            <span :class="['ml-2 rounded-full px-2 py-0.5 text-sm font-medium capitalize', MEAL_COLORS[session.meal_type]]">
                                {{ session.meal_type }}
                            </span>
                        </h1>
                        <p v-if="session.stream" class="text-sm text-gray-500">{{ session.stream.name }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button v-if="!session.finalized" @click="save" :disabled="saveForm.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Save
                        </button>
                        <button v-if="!session.finalized" @click="finalize"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Finalize
                        </button>
                        <span v-if="session.finalized" class="rounded-full bg-gray-100 px-3 py-2 text-sm text-gray-500">Finalized</span>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                        <span class="text-sm text-gray-600">{{ checkedIds.size }} / {{ pupils?.length ?? 0 }} pupils served</span>
                        <label v-if="!session.finalized" class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" :checked="allChecked" @change="e => toggleAll((e.target as HTMLInputElement).checked)" />
                            Select all
                        </label>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        <li v-for="pupil in pupils" :key="pupil.id" class="flex items-center gap-3 px-4 py-2">
                            <input v-if="!session.finalized" type="checkbox" :value="pupil.id"
                                :checked="checkedIds.has(pupil.id)"
                                @change="e => (e.target as HTMLInputElement).checked ? checkedIds.add(pupil.id) : checkedIds.delete(pupil.id)"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600" />
                            <span v-else :class="checkedIds.has(pupil.id) ? 'text-green-600' : 'text-gray-300'">
                                {{ checkedIds.has(pupil.id) ? '✓' : '✗' }}
                            </span>
                            <span class="flex-1 text-sm text-gray-900">{{ pupil.first_name }} {{ pupil.last_name }}</span>
                            <span class="text-xs text-gray-400">{{ pupil.admission_no }}</span>
                        </li>
                    </ul>
                </div>
            </template>

            <!-- Session list / open session mode -->
            <template v-else>
                <div class="mb-6 flex flex-wrap items-end gap-3">
                    <h1 class="w-full text-2xl font-semibold text-gray-900">Daily Feeding Register</h1>

                    <div>
                        <label class="block text-xs text-gray-600">Date</label>
                        <input v-model="openForm.date" type="date" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Meal Type</label>
                        <select v-model="openForm.meal_type" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="breakfast">Breakfast</option>
                            <option value="lunch">Lunch</option>
                            <option value="snack">Snack</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Stream (optional)</label>
                        <select v-model="openForm.stream_id" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                            <option value="">Whole school</option>
                            <option v-for="s in streams" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <button @click="openSession" :disabled="openForm.processing"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                        Open Session
                    </button>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Date</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Meal</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Stream</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-600">Served</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="s in sessions?.data" :key="s.id">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ s.date }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2 py-0.5 text-xs capitalize', MEAL_COLORS[s.meal_type]]">{{ s.meal_type }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ s.stream?.name ?? 'Whole school' }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-700">{{ s.served_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="s.finalized ? 'bg-gray-100 text-gray-500' : 'bg-yellow-100 text-yellow-700'"
                                        class="rounded-full px-2 py-0.5 text-xs">
                                        {{ s.finalized ? 'Finalized' : 'Open' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a :href="route('feeding-sessions.show', s.id)" class="text-xs text-indigo-600 hover:underline">Open</a>
                                </td>
                            </tr>
                            <tr v-if="!sessions?.data.length">
                                <td colspan="6" class="px-4 py-10 text-center text-gray-400">No sessions yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>
    </AppLayout>
</template>
