<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import type { AcademicYear } from '@/types/calendar'

const props = defineProps<{
    grades: Array<{ id: number; name: string; grade_number: number }>
    streams: Array<{ id: number; name: string; grade_id: number }>
    academicYear: AcademicYear | null
}>()

interface ParsedRow {
    name: string
    sex: string
    dob: string
    guardian_name: string
    guardian_phone: string
    valid: boolean
    issue: string | null
}

const raw = ref('')
const fileName = ref('')
const fileError = ref('')

function readFile(file: File | undefined | null) {
    fileError.value = ''
    raw.value = ''
    fileName.value = ''

    if (!file) return

    if (!/\.csv$/i.test(file.name) && file.type !== 'text/csv') {
        fileError.value = 'Please choose a .csv file.'
        return
    }

    fileName.value = file.name

    const reader = new FileReader()
    reader.onload = () => { raw.value = String(reader.result ?? '') }
    reader.onerror = () => { fileError.value = 'Could not read that file.' }
    reader.readAsText(file)
}

function onFileChange(e: Event) {
    readFile((e.target as HTMLInputElement).files?.[0])
}

function onDrop(e: DragEvent) {
    readFile(e.dataTransfer?.files?.[0])
}

function clearFile() {
    raw.value = ''
    fileName.value = ''
    fileError.value = ''
}

function downloadTemplate() {
    const lines = [
        'name,sex,dob,guardian_name,guardian_phone',
        'Moses Muchimena,M,10/05/21,Moses Muchena,0971 078901',
        'Jean Nambaye,F,07/10/22,Mulenga Sarah,0966 044458',
    ]
    const blob = new Blob([lines.join('\n') + '\n'], { type: 'text/csv' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'pupil-import-template.csv'
    a.click()
    URL.revokeObjectURL(url)
}

const form = useForm({
    grade_id: null as number | null,
    stream_id: null as number | null,
    academic_year_id: props.academicYear?.id ?? null,
    date_of_admission: new Date().toISOString().slice(0, 10),
    rows: [] as Array<Pick<ParsedRow, 'name' | 'sex' | 'dob' | 'guardian_name' | 'guardian_phone'>>,
})

const filteredStreams = computed(() =>
    form.grade_id ? props.streams.filter((s) => s.grade_id === form.grade_id) : []
)

const HEADER_HINTS = ['name of learner', 'name', 'learner', 'sex', 'gender', 'date of birth', 'dob', 'parent', 'guardian', 'contact']

function normaliseSex(v: string): string | null {
    const s = v.trim().toLowerCase()
    if (s === 'm' || s === 'male') return 'male'
    if (s === 'f' || s === 'female') return 'female'
    return null
}

function isValidDob(v: string): boolean {
    const s = v.trim()
    // dd/mm/yy, dd/mm/yyyy, dd-mm-yy(yy) or yyyy-mm-dd
    return /^\d{1,2}[/-]\d{1,2}[/-]\d{2}(\d{2})?$/.test(s) || /^\d{4}-\d{1,2}-\d{1,2}$/.test(s)
}

/** Split a line into cells, supporting markdown pipe tables, tabs and commas. */
function splitCells(line: string): string[] {
    if (line.includes('|')) {
        return line.split('|').map((c) => c.trim())
    }
    if (line.includes('\t')) {
        return line.split('\t').map((c) => c.trim())
    }
    return line.split(',').map((c) => c.trim())
}

const parsed = computed<ParsedRow[]>(() => {
    const out: ParsedRow[] = []

    for (const rawLine of raw.value.split('\n')) {
        const line = rawLine.trim()
        if (!line) continue

        let cells = splitCells(line).filter((c) => c.length > 0)
        if (cells.length === 0) continue

        // markdown separator row e.g. | -- | -- |
        if (cells.every((c) => /^[-:]+$/.test(c))) continue

        // drop a leading numeric index column (the "#" column)
        if (/^\d+$/.test(cells[0]) && cells.length > 3) cells = cells.slice(1)

        // skip header rows
        const lowered = cells.map((c) => c.toLowerCase())
        if (lowered.some((c) => HEADER_HINTS.includes(c))) continue

        const [name = '', sex = '', dob = '', guardian_name = '', guardian_phone = ''] = cells

        let issue: string | null = null
        if (name.trim().split(/\s+/).length < 2) issue = 'Needs first & last name'
        else if (!normaliseSex(sex)) issue = 'Sex must be M or F'
        else if (!isValidDob(dob)) issue = 'Unreadable date of birth'

        out.push({ name, sex, dob, guardian_name, guardian_phone, valid: issue === null, issue })
    }

    return out
})

const validRows = computed(() => parsed.value.filter((r) => r.valid))
const invalidCount = computed(() => parsed.value.length - validRows.value.length)

const canSubmit = computed(() =>
    !!form.grade_id && !!form.academic_year_id && !!form.date_of_admission && validRows.value.length > 0
)

function submit() {
    form.rows = validRows.value.map((r) => ({
        name: r.name,
        sex: r.sex,
        dob: r.dob,
        guardian_name: r.guardian_name,
        guardian_phone: r.guardian_phone,
    }))
    form.post(route('pupils.import.store'))
}
</script>

<template>
    <AppLayout title="Bulk Import Pupils">
        <Head title="Bulk Import Pupils" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Bulk Import Pupils</h1>
                <Link :href="route('pupils.index')" class="text-sm text-indigo-600 hover:underline">← Back to pupils</Link>
            </div>

            <!-- Placement -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Placement</h2>
                <p class="text-sm text-gray-500 mb-4">Every pupil in this batch is admitted into the grade and stream you choose here.</p>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Grade *</label>
                        <select v-model.number="form.grade_id" class="mt-1 w-full border-gray-300 rounded-md text-sm" @change="form.stream_id = null">
                            <option :value="null">Select grade…</option>
                            <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                        <p v-if="form.errors.grade_id" class="mt-1 text-xs text-red-600">{{ form.errors.grade_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Stream</label>
                        <select v-model.number="form.stream_id" class="mt-1 w-full border-gray-300 rounded-md text-sm" :disabled="!form.grade_id">
                            <option :value="null">No stream</option>
                            <option v-for="s in filteredStreams" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date of Admission *</label>
                        <input v-model="form.date_of_admission" type="date" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                        <p v-if="form.errors.date_of_admission" class="mt-1 text-xs text-red-600">{{ form.errors.date_of_admission }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Academic Year *</label>
                        <select v-model.number="form.academic_year_id" class="mt-1 w-full border-gray-300 rounded-md text-sm">
                            <option :value="academicYear?.id">{{ academicYear?.name ?? 'Not set' }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Upload -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-lg font-semibold text-gray-800">Upload CSV file</h2>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        @click="downloadTemplate"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16" />
                        </svg>
                        Download template
                    </button>
                </div>
                <p class="text-sm text-gray-500 mb-3">
                    Choose a <span class="font-medium text-gray-700">.csv</span> file with columns in this order:
                    <span class="font-medium text-gray-700">Name, Sex (M/F), Date of Birth, Guardian Name, Guardian Phone</span>.
                    A header row and a leading number column are ignored automatically.
                </p>

                <label
                    class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/40 transition-colors"
                    @dragover.prevent
                    @drop.prevent="onDrop"
                >
                    <input type="file" accept=".csv,text/csv" class="sr-only" @change="onFileChange" />
                    <span class="text-sm font-medium text-indigo-700">Click to choose a CSV file</span>
                    <span class="text-xs text-gray-400 mt-1">or drag &amp; drop it here</span>
                </label>

                <div v-if="fileName" class="mt-3 flex items-center justify-between text-sm bg-gray-50 border rounded-md px-3 py-2">
                    <span class="text-gray-700 truncate">
                        <span class="font-medium">{{ fileName }}</span>
                        <span class="text-gray-400 ml-2">{{ parsed.length }} row{{ parsed.length === 1 ? '' : 's' }} found</span>
                    </span>
                    <button type="button" class="text-gray-400 hover:text-red-600 ml-3" @click="clearFile">Remove</button>
                </div>
                <p v-if="fileError" class="mt-2 text-xs text-red-600">{{ fileError }}</p>
            </div>

            <!-- Preview -->
            <div v-if="parsed.length > 0" class="bg-white rounded-lg shadow overflow-hidden mb-6">
                <div class="px-6 py-3 border-b flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">Preview</h2>
                    <div class="text-sm">
                        <span class="text-green-700 font-medium">{{ validRows.length }} ready</span>
                        <span v-if="invalidCount" class="text-red-600 font-medium ml-3">{{ invalidCount }} with problems</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase text-xs">Name</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase text-xs">Sex</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase text-xs">DOB</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase text-xs">Guardian</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase text-xs">Phone</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase text-xs">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(row, i) in parsed" :key="i" :class="row.valid ? '' : 'bg-red-50'">
                                <td class="px-4 py-2 text-gray-800">{{ row.name }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ row.sex }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ row.dob }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ row.guardian_name || '—' }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ row.guardian_phone || '—' }}</td>
                                <td class="px-4 py-2">
                                    <span v-if="row.valid" class="text-green-700">✓ Ready</span>
                                    <span v-else class="text-red-600">{{ row.issue }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3">
                <p v-if="parsed.length > 0 && !form.grade_id" class="text-sm text-amber-600 mr-auto">Choose a grade before importing.</p>
                <button
                    type="button"
                    :disabled="!canSubmit || form.processing"
                    class="px-6 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    @click="submit"
                >
                    {{ form.processing ? 'Importing…' : `Import ${validRows.length} ${validRows.length === 1 ? 'pupil' : 'pupils'}` }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
