<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import type {
    LessonPlanAttachment,
    LessonPlanOption,
    StreamOption,
    TermOption,
} from '@/composables/useLessonPlans'

const props = defineProps<{
    form: any
    teacherName?: string | null
    subjects: LessonPlanOption[]
    streams: StreamOption[]
    terms: TermOption[]
    attachments?: LessonPlanAttachment[]
    processing?: boolean
}>()

const emit = defineEmits<{
    (e: 'save-draft'): void
    (e: 'submit'): void
    (e: 'remove-attachment', id: number): void
}>()

const totalPupils = computed(() => {
    const boys = Number(props.form.boys_count) || 0
    const girls = Number(props.form.girls_count) || 0

    return boys + girls
})

const selectedStream = computed(() => props.streams.find((s) => s.id === props.form.stream_id))

const rollIsKnown = computed(() =>
    selectedStream.value?.boys_count !== undefined && selectedStream.value?.girls_count !== undefined)

const rollTotal = computed(() =>
    (selectedStream.value?.boys_count ?? 0) + (selectedStream.value?.girls_count ?? 0))

function fillFromRoll() {
    if (!rollIsKnown.value) return

    props.form.boys_count = selectedStream.value!.boys_count ?? null
    props.form.girls_count = selectedStream.value!.girls_count ?? null
}

// Picking a class fills the pupil stats from its roll. The teacher can still type
// over them — a lesson plan records who was actually in the room.
watch(() => props.form.stream_id, fillFromRoll)

function addStage() {
    props.form.stages.push({
        stage: '',
        teacher_activity: '',
        learner_activity: '',
        assessment_criteria: '',
    })
}

function removeStage(index: number) {
    props.form.stages.splice(index, 1)
}

function stageError(index: number, field: string): string | undefined {
    return props.form.errors[`stages.${index}.${field}`]
}

function onFiles(event: Event, form: any) {
    const target = event.target as HTMLInputElement
    form.attachments = target.files ? Array.from(target.files) : []
}
</script>

<template>
    <div class="rounded-lg border bg-white p-6 shadow-sm">
        <form class="space-y-6" @submit.prevent="emit('submit')">

            <!-- Header block -->
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Teacher's name</label>
                        <input :value="teacherName ?? '—'" type="text" disabled
                            class="w-full rounded-md border-gray-200 bg-gray-50 text-sm text-gray-600 shadow-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Subject</label>
                        <select v-model="form.subject_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                            <option :value="null" disabled>Select subject</option>
                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <p v-if="form.errors.subject_id" class="mt-1 text-xs text-red-600">{{ form.errors.subject_id }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Class</label>
                        <select v-model="form.stream_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                            <option :value="null" disabled>Select class</option>
                            <option v-for="st in streams" :key="st.id" :value="st.id">{{ st.grade ? `${st.grade.name} - ${st.name}` : st.name }}</option>
                        </select>
                        <p v-if="form.errors.stream_id" class="mt-1 text-xs text-red-600">{{ form.errors.stream_id }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Topic</label>
                        <input v-model="form.topic" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                        <p v-if="form.errors.topic" class="mt-1 text-xs text-red-600">{{ form.errors.topic }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Sub-topic</label>
                        <input v-model="form.sub_topic" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.sub_topic" class="mt-1 text-xs text-red-600">{{ form.errors.sub_topic }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">General competence</label>
                        <textarea v-model="form.general_competence" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                        <p v-if="form.errors.general_competence" class="mt-1 text-xs text-red-600">{{ form.errors.general_competence }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Specific competence</label>
                        <textarea v-model="form.specific_competence" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                        <p v-if="form.errors.specific_competence" class="mt-1 text-xs text-red-600">{{ form.errors.specific_competence }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Lesson goal</label>
                    <textarea v-model="form.lesson_goal" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required />
                    <p v-if="form.errors.lesson_goal" class="mt-1 text-xs text-red-600">{{ form.errors.lesson_goal }}</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Reference</label>
                        <textarea v-model="form.reference" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                            placeholder="Syllabus, textbook, page numbers…" />
                        <p v-if="form.errors.reference" class="mt-1 text-xs text-red-600">{{ form.errors.reference }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Prior knowledge</label>
                        <textarea v-model="form.prior_knowledge" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                            placeholder="What learners already know…" />
                        <p v-if="form.errors.prior_knowledge" class="mt-1 text-xs text-red-600">{{ form.errors.prior_knowledge }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Learning material</label>
                        <textarea v-model="form.learning_material" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        <p v-if="form.errors.learning_material" class="mt-1 text-xs text-red-600">{{ form.errors.learning_material }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Learning environment</label>
                        <textarea v-model="form.learning_environment" rows="2" class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                            placeholder="Classroom, school garden, laboratory…" />
                        <p v-if="form.errors.learning_environment" class="mt-1 text-xs text-red-600">{{ form.errors.learning_environment }}</p>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200" />

            <!-- Class block -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-6">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Date</label>
                    <input v-model="form.lesson_date" type="date" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    <p v-if="form.errors.lesson_date" class="mt-1 text-xs text-red-600">{{ form.errors.lesson_date }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Duration (min)</label>
                    <input v-model="form.duration_minutes" type="number" min="5" max="480" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    <p v-if="form.errors.duration_minutes" class="mt-1 text-xs text-red-600">{{ form.errors.duration_minutes }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Boys</label>
                    <input v-model="form.boys_count" type="number" min="0" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    <p v-if="form.errors.boys_count" class="mt-1 text-xs text-red-600">{{ form.errors.boys_count }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Girls</label>
                    <input v-model="form.girls_count" type="number" min="0" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    <p v-if="form.errors.girls_count" class="mt-1 text-xs text-red-600">{{ form.errors.girls_count }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Total pupils</label>
                    <input :value="totalPupils" type="number" disabled
                        class="w-full rounded-md border-gray-200 bg-gray-50 text-sm text-gray-600 shadow-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Week no.</label>
                    <input v-model="form.week_number" type="number" min="1" max="52" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    <p v-if="form.errors.week_number" class="mt-1 text-xs text-red-600">{{ form.errors.week_number }}</p>
                </div>
            </div>

            <p v-if="rollIsKnown" class="-mt-2 text-xs text-gray-500">
                Class roll: {{ selectedStream?.boys_count }} boys, {{ selectedStream?.girls_count }} girls
                ({{ rollTotal }} on register).
                <button v-if="totalPupils !== rollTotal" type="button"
                    class="font-medium text-indigo-600 hover:text-indigo-800" @click="fillFromRoll">
                    Reset to roll
                </button>
                <span v-else class="text-gray-400">Edit the counts if attendance differs.</span>
            </p>

            <div class="sm:w-1/3">
                <label class="mb-1 block text-xs font-medium text-gray-600">Term</label>
                <select v-model="form.term_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm" required>
                    <option :value="null" disabled>Select term</option>
                    <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}{{ t.is_current ? ' (current)' : '' }}</option>
                </select>
                <p v-if="form.errors.term_id" class="mt-1 text-xs text-red-600">{{ form.errors.term_id }}</p>
            </div>

            <hr class="border-gray-200" />

            <!--
                Stage table. One set of inputs for both layouts: a labelled card per
                stage on phones, the four-column table from the paper form on md and up.
            -->
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Lesson development</h2>
                    <button type="button" class="text-xs font-medium text-indigo-600 hover:text-indigo-800" @click="addStage">
                        + Add stage
                    </button>
                </div>

                <div class="space-y-3 md:space-y-0 md:divide-y md:divide-gray-100 md:overflow-hidden md:rounded-md md:border md:border-gray-200">
                    <div class="hidden border-b border-gray-200 bg-gray-50 px-3 py-2 md:grid md:grid-cols-[7rem_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_1.5rem] md:gap-3">
                        <span class="text-xs font-medium text-gray-600">Stage</span>
                        <span class="text-xs font-medium text-gray-600">Teacher activity</span>
                        <span class="text-xs font-medium text-gray-600">Learners activity</span>
                        <span class="text-xs font-medium text-gray-600">Assessment criteria</span>
                        <span class="sr-only">Actions</span>
                    </div>

                    <div v-for="(stage, i) in form.stages" :key="i"
                        class="rounded-md border border-gray-200 p-3 md:grid md:grid-cols-[7rem_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_1.5rem] md:gap-3 md:rounded-none md:border-0 md:px-3 md:py-2">
                        <div class="mb-3 md:mb-0">
                            <label class="mb-1 block text-xs font-medium text-gray-600 md:hidden">Stage</label>
                            <input v-model="stage.stage" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                            <p v-if="stageError(i, 'stage')" class="mt-1 text-xs text-red-600">{{ stageError(i, 'stage') }}</p>
                        </div>
                        <div class="mb-3 md:mb-0">
                            <label class="mb-1 block text-xs font-medium text-gray-600 md:hidden">Teacher activity</label>
                            <textarea v-model="stage.teacher_activity" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                        <div class="mb-3 md:mb-0">
                            <label class="mb-1 block text-xs font-medium text-gray-600 md:hidden">Learners activity</label>
                            <textarea v-model="stage.learner_activity" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                        <div class="mb-3 md:mb-0">
                            <label class="mb-1 block text-xs font-medium text-gray-600 md:hidden">Assessment criteria</label>
                            <textarea v-model="stage.assessment_criteria" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                        <div v-if="form.stages.length > 1" class="flex justify-end md:block md:pt-1.5">
                            <button type="button" class="text-xs font-medium text-gray-400 hover:text-red-600"
                                title="Remove stage" @click="removeStage(i)">
                                <span class="md:hidden">Remove stage</span>
                                <span class="hidden md:inline" aria-hidden="true">&#10005;</span>
                            </button>
                        </div>
                    </div>
                </div>
                <p v-if="form.errors.stages" class="mt-1 text-xs text-red-600">{{ form.errors.stages }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Conclusion</label>
                    <textarea v-model="form.conclusion" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                        placeholder="How the lesson is drawn together — summary, homework, what comes next…" />
                    <p v-if="form.errors.conclusion" class="mt-1 text-xs text-red-600">{{ form.errors.conclusion }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">
                        Evaluation
                        <span class="font-normal text-gray-400">(after teaching)</span>
                    </label>
                    <textarea v-model="form.evaluation" rows="3" class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                        placeholder="Was the lesson goal met? What worked, what to reteach…" />
                    <p v-if="form.errors.evaluation" class="mt-1 text-xs text-red-600">{{ form.errors.evaluation }}</p>
                </div>
            </div>

            <!-- Existing attachments (edit) -->
            <div v-if="attachments && attachments.length">
                <label class="mb-1 block text-xs font-medium text-gray-600">Attachments</label>
                <ul class="divide-y divide-gray-100 rounded-md border border-gray-200">
                    <li v-for="a in attachments" :key="a.id" class="flex items-center justify-between px-3 py-2 text-sm">
                        <a :href="a.url" target="_blank" class="text-indigo-600 hover:underline">{{ a.name }}</a>
                        <button type="button" class="text-xs font-medium text-red-600 hover:text-red-800"
                            @click="emit('remove-attachment', a.id)">Remove</button>
                    </li>
                </ul>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Add files <span class="text-gray-400">(worksheets, slides, PDFs — max 20MB each)</span></label>
                <input type="file" multiple class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                    @change="onFiles($event, form)" />
                <p v-if="form.errors.attachments" class="mt-1 text-xs text-red-600">{{ form.errors.attachments }}</p>
                <p v-if="form.errors['attachments.0']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments.0'] }}</p>
            </div>

            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:items-center sm:justify-end">
                <Link :href="route('lesson-plans.index')"
                    class="text-center text-sm text-gray-500 hover:underline">Cancel</Link>
                <button type="button" :disabled="processing"
                    class="w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 sm:w-auto"
                    @click="emit('save-draft')">
                    Save draft
                </button>
                <button type="submit" :disabled="processing"
                    class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50 sm:w-auto">
                    Submit for approval
                </button>
            </div>
        </form>
    </div>
</template>
