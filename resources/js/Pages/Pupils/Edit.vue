<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { Pupil } from '@/types/pupils'

interface Grade        { id: number; name: string; grade_number: number }
interface Stream       { id: number; name: string; grade_id: number }
interface AcademicYear { id: number; name: string }

const props = defineProps<{
    pupil: Pupil
    grades: Grade[]
    streams: Stream[]
    academic_years: AcademicYear[]
}>()

function toDateInput(val: string | null | undefined): string {
    if (!val) return ''
    return val.slice(0, 10)
}

const form = useForm({
    first_name:       props.pupil.first_name ?? '',
    last_name:        props.pupil.last_name ?? '',
    other_name:       props.pupil.other_name ?? '',
    sex:              props.pupil.sex ?? '',
    dob:              toDateInput(props.pupil.dob),
    nationality:      props.pupil.nationality ?? '',
    religion:         props.pupil.religion ?? '',
    tribe:            props.pupil.tribe ?? '',
    blood_group:      props.pupil.blood_group ?? '',
    disability:       props.pupil.disability ?? 'none',
    disability_details: props.pupil.disability_details ?? '',
    previous_school:  props.pupil.previous_school ?? '',
    date_of_admission: toDateInput(props.pupil.date_of_admission),
    grade_id:         props.pupil.grade_id as number | null,
    stream_id:        props.pupil.stream_id as number | null,
    academic_year_id: props.pupil.academic_year_id as number | null,
})

const filteredStreams = computed(() =>
    form.grade_id ? props.streams.filter(s => s.grade_id === form.grade_id) : props.streams
)

function onGradeChange() {
    form.stream_id = null
}

function submit() {
    form.put(route('pupils.update', props.pupil.id))
}
</script>

<template>
    <AppLayout :title="`Edit — ${pupil.full_name}`">
        <Head :title="`Edit — ${pupil.full_name}`" />

        <div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center gap-3">
                <Link :href="route('pupils.show', pupil.id)" class="text-sm text-gray-500 hover:text-gray-700">← Back</Link>
                <h1 class="text-2xl font-bold text-gray-900">Edit Pupil — {{ pupil.full_name }}</h1>
            </div>

            <form class="space-y-6" @submit.prevent="submit">

                <!-- Personal Details -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700 uppercase tracking-wide">Personal Details</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">First Name <span class="text-red-500">*</span></label>
                            <input v-model="form.first_name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="form.errors.first_name" class="mt-1 text-xs text-red-600">{{ form.errors.first_name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Other Name</label>
                            <input v-model="form.other_name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Last Name <span class="text-red-500">*</span></label>
                            <input v-model="form.last_name" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="form.errors.last_name" class="mt-1 text-xs text-red-600">{{ form.errors.last_name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Sex</label>
                            <select v-model="form.sex" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Select…</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            <p v-if="form.errors.sex" class="mt-1 text-xs text-red-600">{{ form.errors.sex }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Date of Birth</label>
                            <input v-model="form.dob" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="form.errors.dob" class="mt-1 text-xs text-red-600">{{ form.errors.dob }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nationality</label>
                            <input v-model="form.nationality" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Religion</label>
                            <input v-model="form.religion" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tribe</label>
                            <input v-model="form.tribe" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Blood Group</label>
                            <select v-model="form.blood_group" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="">Unknown</option>
                                <option v-for="bg in ['A+','A-','B+','B-','AB+','AB-','O+','O-']" :key="bg" :value="bg">{{ bg }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Disability</label>
                            <select v-model="form.disability" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option value="none">None</option>
                                <option value="visual">Visual</option>
                                <option value="hearing">Hearing</option>
                                <option value="physical">Physical</option>
                                <option value="intellectual">Intellectual</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div v-if="form.disability !== 'none'" class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Disability Details</label>
                            <input v-model="form.disability_details" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Previous School</label>
                            <input v-model="form.previous_school" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>

                    </div>
                </div>

                <!-- Enrollment -->
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700 uppercase tracking-wide">Enrollment</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Date of Admission</label>
                            <input v-model="form.date_of_admission" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="form.errors.date_of_admission" class="mt-1 text-xs text-red-600">{{ form.errors.date_of_admission }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Academic Year</label>
                            <select v-model="form.academic_year_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option :value="null">Select year…</option>
                                <option v-for="y in academic_years" :key="y.id" :value="y.id">{{ y.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Grade (Class)</label>
                            <select v-model="form.grade_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" @change="onGradeChange">
                                <option :value="null">Select grade…</option>
                                <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                            <p v-if="form.errors.grade_id" class="mt-1 text-xs text-red-600">{{ form.errors.grade_id }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Stream</label>
                            <select v-model="form.stream_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm">
                                <option :value="null">Select stream…</option>
                                <option v-for="s in filteredStreams" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <p v-if="form.errors.stream_id" class="mt-1 text-xs text-red-600">{{ form.errors.stream_id }}</p>
                        </div>

                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Save Changes
                    </button>
                    <Link :href="route('pupils.show', pupil.id)" class="text-sm text-gray-500 hover:text-gray-700">
                        Cancel
                    </Link>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
