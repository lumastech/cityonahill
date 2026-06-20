<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

interface Grade        { id: number; name: string; grade_number: number }
interface Teacher      { id: number; name: string }
interface AcademicYear { id: number; name: string }
interface Stream {
    id: number
    name: string
    capacity: number
    grade_id: number | null
    class_teacher_id: number | null
    academic_year_id: number | null
    grade: Grade | null
    class_teacher: Teacher | null
}

const props = defineProps<{
    stream: Stream
    grades: Grade[]
    teachers: Teacher[]
    academic_years: AcademicYear[]
}>()

const form = useForm({
    grade_id:         props.stream.grade_id,
    name:             props.stream.name,
    class_teacher_id: props.stream.class_teacher_id,
    capacity:         props.stream.capacity,
    academic_year_id: props.stream.academic_year_id,
})

function submit() {
    form.put(route('streams.update', props.stream.id))
}
</script>

<template>
    <AppLayout :title="`Edit Stream — ${stream.grade?.name} ${stream.name}`">
        <Head :title="`Edit Stream — ${stream.grade?.name} ${stream.name}`" />

        <div class="py-6 mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center gap-3">
                <Link :href="route('streams.index')" class="text-sm text-gray-500 hover:text-gray-700">← Streams</Link>
                <h1 class="text-2xl font-bold text-gray-900">
                    Edit Stream — {{ stream.grade?.name }} {{ stream.name }}
                </h1>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form class="space-y-5" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Grade <span class="text-red-500">*</span>
                        </label>
                        <select v-model="form.grade_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option :value="null">Select grade…</option>
                            <option v-for="g in grades" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                        <p v-if="form.errors.grade_id" class="mt-1 text-xs text-red-600">{{ form.errors.grade_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Stream Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. A, B, North"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class Teacher</label>
                        <select v-model="form.class_teacher_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option :value="null">Unassigned</option>
                            <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <p v-if="form.errors.class_teacher_id" class="mt-1 text-xs text-red-600">{{ form.errors.class_teacher_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                        <select v-model="form.academic_year_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option :value="null">Select year…</option>
                            <option v-for="y in academic_years" :key="y.id" :value="y.id">{{ y.name }}</option>
                        </select>
                        <p v-if="form.errors.academic_year_id" class="mt-1 text-xs text-red-600">{{ form.errors.academic_year_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                        <input
                            v-model.number="form.capacity"
                            type="number"
                            min="1"
                            max="200"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm"
                        />
                        <p v-if="form.errors.capacity" class="mt-1 text-xs text-red-600">{{ form.errors.capacity }}</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Save Changes
                        </button>
                        <Link :href="route('streams.index')" class="text-sm text-gray-500 hover:text-gray-700">
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
