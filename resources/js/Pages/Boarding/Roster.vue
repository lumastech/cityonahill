<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { BoardingAllocation } from '@/types/boarding'

interface Term { id: number; name: string }

const props = defineProps<{
    terms: Term[]
    term_id: number | null
    roster: BoardingAllocation[]
}>()

const selectedTermId = ref(props.term_id ?? '')

function loadRoster() {
    router.get(route('boarding.roster'), { term_id: selectedTermId.value }, { preserveState: true })
}

function printPage() {
    window.print()
}
</script>

<template>
    <AppLayout>
    <Head title="Boarding Roster" />
    <div class="min-h-screen bg-white py-6 print:py-2">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 print:px-2">

            <!-- Controls — hidden on print -->
            <div class="mb-6 flex flex-wrap items-center gap-3 print:hidden">
                <h1 class="text-2xl font-semibold text-gray-900 mr-4">Term Boarding Roster</h1>
                <select v-model="selectedTermId" class="rounded-md border-gray-300 text-sm shadow-sm" @change="loadRoster">
                    <option value="">Select term…</option>
                    <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
                <button v-if="roster.length" @click="printPage"
                    class="ml-auto rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Print
                </button>
            </div>

            <template v-if="roster.length">
                <!-- Print header -->
                <div class="mb-4 hidden print:block">
                    <h1 class="text-xl font-bold">Term Boarding Roster</h1>
                    <p class="text-sm text-gray-500">Printed: {{ new Date().toLocaleDateString('en-ZM') }}</p>
                </div>

                <table class="min-w-full border border-gray-300 text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-3 py-2 text-left">#</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Pupil Name</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Adm. No.</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Grade</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Dormitory</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Bed</th>
                            <th class="border border-gray-300 px-3 py-2 text-left">Guardian Phone</th>
                            <th class="border border-gray-300 px-3 py-2 text-right print:hidden">Fee (ZMW)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(a, idx) in roster" :key="a.id" :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                            <td class="border border-gray-300 px-3 py-2 text-gray-500">{{ idx + 1 }}</td>
                            <td class="border border-gray-300 px-3 py-2 font-medium text-gray-900">
                                {{ a.pupil?.first_name }} {{ a.pupil?.last_name }}
                            </td>
                            <td class="border border-gray-300 px-3 py-2 text-gray-600">{{ a.pupil?.admission_no }}</td>
                            <td class="border border-gray-300 px-3 py-2 text-gray-600">
                                {{ a.pupil?.stream?.grade?.name }} {{ a.pupil?.stream?.name }}
                            </td>
                            <td class="border border-gray-300 px-3 py-2 text-gray-600">
                                {{ a.bed?.dormitory?.name }}
                                <span :class="['ml-1 text-xs', a.bed?.dormitory?.gender === 'male' ? 'text-blue-500' : 'text-pink-500']">
                                    ({{ a.bed?.dormitory?.gender }})
                                </span>
                            </td>
                            <td class="border border-gray-300 px-3 py-2 font-medium text-gray-700">{{ a.bed?.bed_number }}</td>
                            <td class="border border-gray-300 px-3 py-2 text-gray-600">
                                {{ a.pupil?.guardians?.[0]?.phone ?? '—' }}
                            </td>
                            <td class="border border-gray-300 px-3 py-2 text-right text-gray-700 print:hidden">
                                {{ Number(a.fee_amount).toFixed(2) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 font-semibold print:hidden">
                            <td colspan="7" class="border border-gray-300 px-3 py-2 text-right">Total boarders:</td>
                            <td class="border border-gray-300 px-3 py-2 text-right">{{ roster.length }}</td>
                        </tr>
                    </tfoot>
                </table>
            </template>

            <div v-else class="py-16 text-center text-gray-400">
                {{ term_id ? 'No boarding allocations for this term.' : 'Select a term to view the roster.' }}
            </div>
        </div>
    </div>
    </AppLayout>
</template>
