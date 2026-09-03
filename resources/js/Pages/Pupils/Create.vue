<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
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

/* ------------------------------------------------------------------ *
 | Client-side validation
 |
 | Mirrors App\Data\AdmitPupilData so the user is told about a problem
 | on the step that owns the field, rather than after a round trip that
 | lands them back on the last step with errors they cannot see.
 * ------------------------------------------------------------------ */

/** Which step owns each field — used to route server errors back to a step. */
const FIELD_STEP: Record<string, number> = {
    first_name: 1, last_name: 1, other_name: 1, sex: 1, dob: 1, nationality: 1,
    religion: 1, tribe: 1, disability: 1, disability_details: 1, blood_group: 1,
    previous_school: 2, date_of_admission: 2, grade_id: 2, stream_id: 2, academic_year_id: 2,
    guardian_first_name: 3, guardian_last_name: 3, guardian_relationship: 3,
    guardian_phone: 3, guardian_email: 3, is_primary: 3, is_emergency: 3, can_pickup: 3,
}

const FIELD_LABELS: Record<string, string> = {
    first_name: 'First name', last_name: 'Last name', other_name: 'Other name',
    sex: 'Sex', dob: 'Date of birth', nationality: 'Nationality', religion: 'Religion',
    tribe: 'Tribe', disability: 'Disability', disability_details: 'Disability details',
    blood_group: 'Blood group', previous_school: 'Previous school',
    date_of_admission: 'Date of admission', grade_id: 'Grade', stream_id: 'Stream',
    academic_year_id: 'Academic year', guardian_first_name: 'Guardian first name',
    guardian_last_name: 'Guardian last name', guardian_relationship: 'Guardian relationship',
    guardian_phone: 'Guardian phone', guardian_email: 'Guardian email',
}

const clientErrors = ref<Record<string, string>>({})
/** Steps the user has attempted to leave — errors only surface after that. */
const validated = ref<Set<number>>(new Set())

const today = new Date().toISOString().slice(0, 10)

/** True once the user has started entering a guardian, making the block required. */
const guardianStarted = computed(() =>
    !!(form.guardian_first_name || form.guardian_last_name || form.guardian_phone ||
        form.guardian_email || form.guardian_relationship)
)

function rulesFor(n: number): Record<string, string | null> {
    if (n === 1) {
        return {
            first_name: !form.first_name.trim()
                ? 'First name is required.'
                : form.first_name.length > 50 ? 'First name may not be longer than 50 characters.' : null,
            last_name: !form.last_name.trim()
                ? 'Last name is required.'
                : form.last_name.length > 50 ? 'Last name may not be longer than 50 characters.' : null,
            other_name: form.other_name.length > 50 ? 'Other name may not be longer than 50 characters.' : null,
            sex: !form.sex ? 'Select the pupil’s sex.' : null,
            dob: !form.dob
                ? 'Date of birth is required.'
                : form.dob > today ? 'Date of birth cannot be in the future.'
                    : form.dob < '1900-01-01' ? 'Enter a valid date of birth.' : null,
            nationality: !form.nationality.trim()
                ? 'Nationality is required.'
                : form.nationality.length > 64 ? 'Nationality may not be longer than 64 characters.' : null,
            religion: form.religion.length > 50 ? 'Religion may not be longer than 50 characters.' : null,
            tribe: form.tribe.length > 50 ? 'Tribe may not be longer than 50 characters.' : null,
            blood_group: form.blood_group.length > 5 ? 'Use a short code such as O+.' : null,
            disability_details: form.disability !== 'none' && !form.disability_details.trim()
                ? 'Describe the disability so staff can support the pupil.' : null,
        }
    }

    if (n === 2) {
        return {
            grade_id: !form.grade_id ? 'Select a grade.' : null,
            date_of_admission: !form.date_of_admission
                ? 'Date of admission is required.'
                : form.dob && form.date_of_admission <= form.dob
                    ? 'Date of admission must be after the date of birth.' : null,
            academic_year_id: !form.academic_year_id
                ? 'No current academic year is set. Set one before admitting pupils.' : null,
            previous_school: form.previous_school.length > 150
                ? 'Previous school may not be longer than 150 characters.' : null,
        }
    }

    if (n === 3) {
        const digits = form.guardian_phone.replace(/\D/g, '')
        return {
            guardian_first_name: guardianStarted.value && !form.guardian_first_name.trim()
                ? 'Guardian first name is required.'
                : form.guardian_first_name.length > 50 ? 'First name may not be longer than 50 characters.' : null,
            guardian_last_name: guardianStarted.value && !form.guardian_last_name.trim()
                ? 'Guardian last name is required.'
                : form.guardian_last_name.length > 50 ? 'Last name may not be longer than 50 characters.' : null,
            guardian_relationship: guardianStarted.value && !form.guardian_relationship
                ? 'Select the relationship to the pupil.' : null,
            guardian_phone: guardianStarted.value && !form.guardian_phone.trim()
                ? 'Guardian phone is required.'
                : form.guardian_phone && (digits.length < 9 || digits.length > 15)
                    ? 'Enter a valid phone number, e.g. 0977123456.'
                    : form.guardian_phone.length > 25 ? 'Phone may not be longer than 25 characters.' : null,
            guardian_email: form.guardian_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.guardian_email)
                ? 'Enter a valid email address.' : null,
        }
    }

    return {}
}

/** Validate one step, replacing that step's client errors. Returns true if clean. */
function validateStep(n: number): boolean {
    const rules = rulesFor(n)
    const next = { ...clientErrors.value }

    for (const [field, message] of Object.entries(rules)) {
        if (message) {
            next[field] = message
        } else {
            delete next[field]
        }
    }

    clientErrors.value = next
    return Object.values(rules).every((m) => m === null)
}

/** Client error first — it is the fresher of the two — then the server's. */
function error(field: string): string | undefined {
    if (validated.value.has(FIELD_STEP[field])) {
        const message = clientErrors.value[field]
        if (message) return message
    }
    return (form.errors as Record<string, string | undefined>)[field]
}

function inputClass(field: string): string {
    return error(field)
        ? 'border-red-400 focus:border-red-500 focus:ring-red-500'
        : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
}

/** Server errors that belong to an earlier step, grouped for the summary panel. */
const serverErrorList = computed(() =>
    Object.entries(form.errors as Record<string, string>).map(([field, message]) => ({
        field,
        message,
        step: FIELD_STEP[field] ?? STEPS.length,
        label: FIELD_LABELS[field] ?? field,
    }))
)

function stepHasError(n: number): boolean {
    if (serverErrorList.value.some((e) => e.step === n)) return true
    return validated.value.has(n) &&
        Object.keys(clientErrors.value).some((field) => FIELD_STEP[field] === n)
}

/** Snapshot of what was last posted, so server errors can be retired on edit. */
let submitted: Record<string, unknown> = {}

// Once a step has been validated, keep it live so fixes clear the message as
// the user types rather than only on the next attempt to move forward. A server
// error is dropped as soon as its field differs from what was rejected.
watch(
    () => form.data(),
    (data) => {
        validated.value.forEach((n) => validateStep(n))

        const edited = Object.keys(form.errors).filter(
            (field) => (data as Record<string, unknown>)[field] !== submitted[field]
        )
        if (edited.length) {
            form.clearErrors(...(edited as Array<keyof typeof form.errors>))
        }
    },
    { deep: true }
)

function goToStep(n: number) {
    step.value = n
}

function nextStep() {
    validated.value = new Set([...validated.value, step.value])
    if (!validateStep(step.value)) return

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
    validated.value = new Set(STEPS.map((_, i) => i + 1))

    const firstInvalid = STEPS.map((_, i) => i + 1).find((n) => !validateStep(n))
    if (firstInvalid) {
        step.value = firstInvalid
        return
    }

    submitted = { ...form.data() }

    form.post(route('pupils.store'), {
        // Stay put: the summary panel below shows whatever the server rejected.
        onError: () => { step.value = STEPS.length },
    })
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
                        <button
                            type="button"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium border-2 transition-colors"
                            :class="stepHasError(i + 1)
                                ? 'bg-red-50 border-red-500 text-red-600'
                                : step > i + 1
                                    ? 'bg-indigo-600 border-indigo-600 text-white'
                                    : step === i + 1
                                        ? 'border-indigo-600 text-indigo-600'
                                        : 'border-gray-300 text-gray-400'"
                            @click="goToStep(i + 1)"
                        >
                            {{ stepHasError(i + 1) ? '!' : step > i + 1 ? '✓' : i + 1 }}
                        </button>
                        <span
                            class="ml-2 text-sm hidden sm:block"
                            :class="stepHasError(i + 1)
                                ? 'text-red-600'
                                : step === i + 1 ? 'font-medium text-gray-900' : 'text-gray-400'"
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
                            <input v-model="form.first_name" type="text" class="mt-1 w-full rounded-md text-sm" :class="inputClass('first_name')" />
                            <p v-if="error('first_name')" class="mt-1 text-xs text-red-600">{{ error('first_name') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                            <input v-model="form.last_name" type="text" class="mt-1 w-full rounded-md text-sm" :class="inputClass('last_name')" />
                            <p v-if="error('last_name')" class="mt-1 text-xs text-red-600">{{ error('last_name') }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Other Name</label>
                        <input v-model="form.other_name" type="text" class="mt-1 w-full rounded-md text-sm" :class="inputClass('other_name')" />
                        <p v-if="error('other_name')" class="mt-1 text-xs text-red-600">{{ error('other_name') }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sex *</label>
                            <select v-model="form.sex" class="mt-1 w-full rounded-md text-sm" :class="inputClass('sex')">
                                <option value="">Select…</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            <p v-if="error('sex')" class="mt-1 text-xs text-red-600">{{ error('sex') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date of Birth *</label>
                            <input v-model="form.dob" type="date" :max="today" class="mt-1 w-full rounded-md text-sm" :class="inputClass('dob')" />
                            <p v-if="error('dob')" class="mt-1 text-xs text-red-600">{{ error('dob') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nationality *</label>
                            <input v-model="form.nationality" type="text" class="mt-1 w-full rounded-md text-sm" :class="inputClass('nationality')" />
                            <p v-if="error('nationality')" class="mt-1 text-xs text-red-600">{{ error('nationality') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Religion</label>
                            <input v-model="form.religion" type="text" class="mt-1 w-full rounded-md text-sm" :class="inputClass('religion')" />
                            <p v-if="error('religion')" class="mt-1 text-xs text-red-600">{{ error('religion') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tribe</label>
                            <input v-model="form.tribe" type="text" class="mt-1 w-full rounded-md text-sm" :class="inputClass('tribe')" />
                            <p v-if="error('tribe')" class="mt-1 text-xs text-red-600">{{ error('tribe') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Blood Group</label>
                            <input v-model="form.blood_group" type="text" placeholder="e.g. O+" class="mt-1 w-full rounded-md text-sm" :class="inputClass('blood_group')" maxlength="5" />
                            <p v-if="error('blood_group')" class="mt-1 text-xs text-red-600">{{ error('blood_group') }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Disability</label>
                        <select v-model="form.disability" class="mt-1 w-full rounded-md text-sm" :class="inputClass('disability')">
                            <option value="none">None</option>
                            <option value="visual">Visual</option>
                            <option value="hearing">Hearing</option>
                            <option value="physical">Physical</option>
                            <option value="intellectual">Intellectual</option>
                            <option value="other">Other</option>
                        </select>
                        <p v-if="error('disability')" class="mt-1 text-xs text-red-600">{{ error('disability') }}</p>
                    </div>

                    <div v-if="form.disability !== 'none'">
                        <label class="block text-sm font-medium text-gray-700">Disability Details *</label>
                        <textarea v-model="form.disability_details" rows="2" class="mt-1 w-full rounded-md text-sm" :class="inputClass('disability_details')" />
                        <p v-if="error('disability_details')" class="mt-1 text-xs text-red-600">{{ error('disability_details') }}</p>
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
                            <select v-model.number="form.grade_id" class="mt-1 w-full rounded-md text-sm" :class="inputClass('grade_id')" @change="form.stream_id = null">
                                <option :value="null">Select grade…</option>
                                <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                            <p v-if="error('grade_id')" class="mt-1 text-xs text-red-600">{{ error('grade_id') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Stream</label>
                            <select v-model.number="form.stream_id" class="mt-1 w-full rounded-md text-sm" :class="inputClass('stream_id')" :disabled="!form.grade_id">
                                <option :value="null">No stream</option>
                                <option v-for="s in filteredStreams" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <p v-if="error('stream_id')" class="mt-1 text-xs text-red-600">{{ error('stream_id') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date of Admission *</label>
                            <input v-model="form.date_of_admission" type="date" class="mt-1 w-full rounded-md text-sm" :class="inputClass('date_of_admission')" />
                            <p v-if="error('date_of_admission')" class="mt-1 text-xs text-red-600">{{ error('date_of_admission') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Academic Year *</label>
                            <select v-model.number="form.academic_year_id" class="mt-1 w-full rounded-md text-sm" :class="inputClass('academic_year_id')">
                                <option :value="academicYear?.id">{{ academicYear?.name ?? 'Not set' }}</option>
                            </select>
                            <p v-if="error('academic_year_id')" class="mt-1 text-xs text-red-600">{{ error('academic_year_id') }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Previous School</label>
                        <input v-model="form.previous_school" type="text" class="mt-1 w-full rounded-md text-sm" :class="inputClass('previous_school')" />
                        <p v-if="error('previous_school')" class="mt-1 text-xs text-red-600">{{ error('previous_school') }}</p>
                    </div>
                </div>

                <!-- Step 3: Guardian -->
                <div v-if="step === 3" class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Primary Guardian</h2>
                    <p class="text-sm text-gray-500 mb-4">
                        Optional — but if you start a guardian, the name, relationship and phone are all
                        needed before the contact can be saved. Additional guardians can be added after admission.
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name <span v-if="guardianStarted">*</span></label>
                            <input v-model="form.guardian_first_name" type="text" class="mt-1 w-full rounded-md text-sm" :class="inputClass('guardian_first_name')" />
                            <p v-if="error('guardian_first_name')" class="mt-1 text-xs text-red-600">{{ error('guardian_first_name') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name <span v-if="guardianStarted">*</span></label>
                            <input v-model="form.guardian_last_name" type="text" class="mt-1 w-full rounded-md text-sm" :class="inputClass('guardian_last_name')" />
                            <p v-if="error('guardian_last_name')" class="mt-1 text-xs text-red-600">{{ error('guardian_last_name') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Relationship <span v-if="guardianStarted">*</span></label>
                            <select v-model="form.guardian_relationship" class="mt-1 w-full rounded-md text-sm" :class="inputClass('guardian_relationship')">
                                <option value="">Select…</option>
                                <option value="father">Father</option>
                                <option value="mother">Mother</option>
                                <option value="guardian">Guardian</option>
                                <option value="grandparent">Grandparent</option>
                                <option value="sibling">Sibling</option>
                                <option value="other">Other</option>
                            </select>
                            <p v-if="error('guardian_relationship')" class="mt-1 text-xs text-red-600">{{ error('guardian_relationship') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone <span v-if="guardianStarted">*</span></label>
                            <input v-model="form.guardian_phone" type="tel" placeholder="0977123456" class="mt-1 w-full rounded-md text-sm" :class="inputClass('guardian_phone')" />
                            <p v-if="error('guardian_phone')" class="mt-1 text-xs text-red-600">{{ error('guardian_phone') }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input v-model="form.guardian_email" type="email" class="mt-1 w-full rounded-md text-sm" :class="inputClass('guardian_email')" />
                        <p v-if="error('guardian_email')" class="mt-1 text-xs text-red-600">{{ error('guardian_email') }}</p>
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

                    <!-- Server-side rejections land here, where the user is standing -->
                    <div v-if="serverErrorList.length" class="border border-red-200 bg-red-50 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-red-800">
                            The admission was rejected — {{ serverErrorList.length }}
                            {{ serverErrorList.length === 1 ? 'field needs' : 'fields need' }} attention
                        </h3>
                        <ul class="mt-3 space-y-2">
                            <li v-for="e in serverErrorList" :key="e.field" class="text-sm text-red-700 flex items-start gap-2">
                                <span class="flex-1">
                                    <span class="font-medium">{{ e.label }}:</span> {{ e.message }}
                                </span>
                                <button
                                    type="button"
                                    class="shrink-0 text-xs text-red-800 underline hover:no-underline"
                                    @click="goToStep(e.step)"
                                >
                                    Fix on step {{ e.step }}
                                </button>
                            </li>
                        </ul>
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
