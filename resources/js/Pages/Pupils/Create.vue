<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import type { AcademicYear } from '@/types/calendar'
import { usePupils } from '@/composables/usePupils'
import { useSchool } from '@/composables/useSchool'

const props = defineProps<{
    grades: Array<{ id: number; name: string; grade_number: number }>
    streams: Array<{ id: number; name: string; grade_id: number }>
    academicYear: AcademicYear | null
}>()

const { admissionNoPreview } = usePupils()
const { currentSchool } = useSchool()

const step = ref(1)
const STEPS = ['Personal Details', 'Academic Placement', 'Guardian', 'Photo']

const form = useForm({
    // Step 1 — Personal
    first_name: '',
    last_name: '',
    other_name: '',
    sex: '' as 'male' | 'female' | '',
    dob: '',
    nationality: 'Zambian',
    religion: '',
    tribe: '',
    disability: 'none',
    disability_details: '',
    blood_group: '',
    // Step 2 — Academic
    previous_school: '',
    date_of_admission: new Date().toISOString().slice(0, 10),
    grade_id: null as number | null,
    stream_id: null as number | null,
    academic_year_id: props.academicYear?.id ?? null,
    // Step 3 — Guardian (sent separately after pupil is created, but included here for UX)
    guardian_first_name: '',
    guardian_last_name: '',
    guardian_relationship: '',
    guardian_phone: '',
    guardian_email: '',
    is_primary: true,
    is_emergency: false,
    can_pickup: true,
})

const filteredStreams = computed(() =>
    form.grade_id ? props.streams.filter((s) => s.grade_id === form.grade_id) : []
)

const admissionPreview = computed(() =>
    admissionNoPreview(
        currentSchool.value?.code ?? 'SCH',
        form.date_of_admission ? new Date(form.date_of_admission).getFullYear() : undefined
    )
)

function nextStep() {
    if (step.value < STEPS.length) {
        step.value++
    }
}

function prevStep() {
    if (step.value > 1) {
        step.value--
    }
}

function submit() {
    form.post(route('pupils.store'))
}
</script>

<template>
    <AppLayout title="Admit Pupil">
        <Head title="Admit Pupil" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Admit Pupil</h1>

            <!-- Step indicator -->
            <div class="flex items-center mb-8">
                <template v-for="(label, i) in STEPS" :key="i">
                    <div class="flex items-center">
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium border-2 transition-colors"
                            :class="step > i + 1
                                ? 'bg-indigo-600 border-indigo-600 text-white'
                                : step === i + 1
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-gray-300 text-gray-400'"
                        >
                            {{ step > i + 1 ? '✓' : i + 1 }}
                        </div>
                        <span
                            class="ml-2 text-sm hidden sm:block"
                            :class="step === i + 1 ? 'font-medium text-gray-900' : 'text-gray-400'"
                        >{{ label }}</span>
                    </div>
                    <div v-if="i < STEPS.length - 1" class="flex-1 h-0.5 mx-3 bg-gray-200" />
                </template>
            </div>

            <div class="bg-white rounded-lg shadow p-6">

                <!-- Step 1: Personal Details -->
                <div v-if="step === 1" class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Personal Details</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name *</label>
                            <input v-model="form.first_name" type="text" class="mt-1 w-full border-gray-300 rounded-md text-sm" required />
                            <p v-if="form.errors.first_name" class="mt-1 text-xs text-red-600">{{ form.errors.first_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                            <input v-model="form.last_name" type="text" class="mt-1 w-full border-gray-300 rounded-md text-sm" required />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Other Name</label>
                        <input v-model="form.other_name" type="text" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sex *</label>
                            <select v-model="form.sex" class="mt-1 w-full border-gray-300 rounded-md text-sm">
                                <option value="">Select…</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            <p v-if="form.errors.sex" class="mt-1 text-xs text-red-600">{{ form.errors.sex }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date of Birth *</label>
                            <input v-model="form.dob" type="date" class="mt-1 w-full border-gray-300 rounded-md text-sm" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nationality</label>
                            <input v-model="form.nationality" type="text" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Religion</label>
                            <input v-model="form.religion" type="text" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tribe</label>
                            <input v-model="form.tribe" type="text" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Blood Group</label>
                            <input v-model="form.blood_group" type="text" placeholder="e.g. O+" class="mt-1 w-full border-gray-300 rounded-md text-sm" maxlength="5" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Disability</label>
                        <select v-model="form.disability" class="mt-1 w-full border-gray-300 rounded-md text-sm">
                            <option value="none">None</option>
                            <option value="visual">Visual</option>
                            <option value="hearing">Hearing</option>
                            <option value="physical">Physical</option>
                            <option value="intellectual">Intellectual</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div v-if="form.disability !== 'none'">
                        <label class="block text-sm font-medium text-gray-700">Disability Details</label>
                        <textarea v-model="form.disability_details" rows="2" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                    </div>
                </div>

                <!-- Step 2: Academic Placement -->
                <div v-if="step === 2" class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Academic Placement</h2>

                    <div class="bg-indigo-50 border border-indigo-100 rounded-md px-4 py-2 text-sm text-indigo-700 mb-4">
                        Admission No preview: <span class="font-mono font-semibold">{{ admissionPreview }}</span>
                        <span class="text-xs text-indigo-400 ml-1">(assigned on save)</span>
                    </div>

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
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date of Admission *</label>
                            <input v-model="form.date_of_admission" type="date" class="mt-1 w-full border-gray-300 rounded-md text-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Academic Year *</label>
                            <select v-model.number="form.academic_year_id" class="mt-1 w-full border-gray-300 rounded-md text-sm">
                                <option :value="academicYear?.id">{{ academicYear?.name ?? 'Not set' }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Previous School</label>
                        <input v-model="form.previous_school" type="text" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                    </div>
                </div>

                <!-- Step 3: Guardian -->
                <div v-if="step === 3" class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Primary Guardian</h2>
                    <p class="text-sm text-gray-500 mb-4">Add the primary guardian. Additional guardians can be added after admission.</p>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name</label>
                            <input v-model="form.guardian_first_name" type="text" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input v-model="form.guardian_last_name" type="text" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Relationship</label>
                            <select v-model="form.guardian_relationship" class="mt-1 w-full border-gray-300 rounded-md text-sm">
                                <option value="">Select…</option>
                                <option value="father">Father</option>
                                <option value="mother">Mother</option>
                                <option value="guardian">Guardian</option>
                                <option value="grandparent">Grandparent</option>
                                <option value="sibling">Sibling</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone *</label>
                            <input v-model="form.guardian_phone" type="tel" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input v-model="form.guardian_email" type="email" class="mt-1 w-full border-gray-300 rounded-md text-sm" />
                    </div>

                    <div class="flex gap-4 pt-2">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.is_primary" type="checkbox" class="rounded" />
                            Primary guardian
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.is_emergency" type="checkbox" class="rounded" />
                            Emergency contact
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.can_pickup" type="checkbox" class="rounded" />
                            Can pick up
                        </label>
                    </div>
                </div>

                <!-- Step 4: Photo -->
                <div v-if="step === 4" class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Profile Photo</h2>
                    <p class="text-sm text-gray-500">Photo upload is optional and can be added after admission from the pupil's profile page.</p>
                    <div class="border-2 border-dashed border-gray-200 rounded-lg p-12 text-center text-gray-400">
                        Photo upload available after admission
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex justify-between mt-6 pt-4 border-t">
                    <button
                        v-if="step > 1"
                        type="button"
                        class="px-4 py-2 text-sm text-gray-700 border rounded hover:bg-gray-50"
                        @click="prevStep"
                    >
                        ← Back
                    </button>
                    <div v-else />

                    <div class="flex gap-3">
                        <button
                            v-if="step < STEPS.length"
                            type="button"
                            class="px-4 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700"
                            @click="nextStep"
                        >
                            Next →
                        </button>
                        <button
                            v-else
                            type="button"
                            :disabled="form.processing"
                            class="px-6 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50"
                            @click="submit"
                        >
                            {{ form.processing ? 'Admitting…' : 'Admit Pupil' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
