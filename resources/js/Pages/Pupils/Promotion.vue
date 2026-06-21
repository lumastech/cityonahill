<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import type { Pupil } from '@/types/pupils'
import { useConfirm } from '@/composables/useConfirm'

const props = defineProps<{
    grades: Array<{ id: number; name: string; grade_number: number }>
    streams: Array<{ id: number; name: string; grade_id: number }>
    pupils: Pupil[]
}>()

const { confirm } = useConfirm()

const selectedStreamId = ref<number | null>(null)
const selectedGradeId = ref<number | null>(null)
const targetGradeId = ref<number | null>(null)
const targetStreamId = ref<number | null>(null)

const form = useForm({
    to_grade_id: null as number | null,
    to_stream_id: null as number | null,
})

// Since pupils come from props filtered by stream, show them directly
const streamPupils = computed(() => props.pupils ?? [])

const targetStreams = computed(() =>
    targetGradeId.value
        ? props.streams.filter((s) => s.grade_id === targetGradeId.value)
        : []
)

async function handlePromote() {
    if (!selectedStreamId.value) {
        return
    }
    const count = streamPupils.value.filter((p) => p.status === 'active').length
    const ok = await confirm(
        `Promote ${count} active pupil(s) to the selected grade/stream? This cannot be undone.`,
        { dangerMode: false }
    )
    if (!ok) {
        return
    }

    form.to_grade_id = targetGradeId.value
    form.to_stream_id = targetStreamId.value
    form.post(route('streams.promote', selectedStreamId.value))
}
</script>

<template>
    <AppLayout title="Pupil Promotion">
        <Head title="Pupil Promotion" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Bulk Pupil Promotion</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Source -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h2 class="font-semibold text-gray-800 mb-3">Source</h2>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Grade</label>
                            <select
                                v-model.number="selectedGradeId"
                                class="mt-1 w-full border-gray-300 rounded-md text-sm"
                                @change="selectedStreamId = null"
                            >
                                <option :value="null">Select grade…</option>
                                <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Stream</label>
                            <select
                                v-model.number="selectedStreamId"
                                class="mt-1 w-full border-gray-300 rounded-md text-sm"
                                :disabled="!selectedGradeId"
                            >
                                <option :value="null">Select stream…</option>
                                <option
                                    v-for="s in streams.filter((s) => s.grade_id === selectedGradeId)"
                                    :key="s.id"
                                    :value="s.id"
                                >{{ s.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Target -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h2 class="font-semibold text-gray-800 mb-3">Promote To</h2>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Target Grade *</label>
                            <select
                                v-model.number="targetGradeId"
                                class="mt-1 w-full border-gray-300 rounded-md text-sm"
                                @change="targetStreamId = null"
                            >
                                <option :value="null">Select grade…</option>
                                <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Target Stream (optional)</label>
                            <select
                                v-model.number="targetStreamId"
                                class="mt-1 w-full border-gray-300 rounded-md text-sm"
                                :disabled="!targetGradeId"
                            >
                                <option :value="null">No specific stream</option>
                                <option v-for="s in targetStreams" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pupil list preview -->
            <div v-if="selectedStreamId" class="bg-white rounded-lg shadow mb-6 overflow-hidden">
                <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between">
                    <h3 class="font-medium text-gray-800">
                        Pupils in source stream
                        <span class="text-gray-400 font-normal">({{ streamPupils.filter(p => p.status === 'active').length }} active)</span>
                    </h3>
                </div>
                <div v-if="!streamPupils.length" class="p-6 text-center text-gray-400 text-sm">
                    No pupils in this stream.
                </div>
                <table v-else class="min-w-full text-sm divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Admission No</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="pupil in streamPupils" :key="pupil.id" :class="pupil.status !== 'active' ? 'opacity-50' : ''">
                            <td class="px-4 py-2">
                                <Link :href="route('pupils.show', pupil.id)" class="hover:underline text-indigo-700">{{ pupil.full_name }}</Link>
                            </td>
                            <td class="px-4 py-2 font-mono text-gray-500">{{ pupil.admission_no }}</td>
                            <td class="px-4 py-2 capitalize text-gray-500">{{ pupil.status }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button
                    :disabled="!selectedStreamId || !targetGradeId || form.processing"
                    class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50"
                    @click="handlePromote"
                >
                    {{ form.processing ? 'Promoting…' : 'Promote All Active Pupils' }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
