<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

interface Pupil {
    id: number
    first_name: string
    last_name: string
    admission_no: string
}

interface PortalAccess {
    user: { id: number; email: string } | null
}

interface Guardian {
    id: number
    first_name: string
    last_name: string
    full_name: string
    relationship: string
    phone: string
    phone2: string | null
    email: string | null
    pupils: Pupil[]
    portal_access: PortalAccess | null
}

interface PaginatedGuardians {
    data: Guardian[]
    current_page: number
    last_page: number
    total: number
    per_page: number
    from: number
    to: number
    links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
    guardians: PaginatedGuardians
    filters: { search?: string }
}>()

const search = ref(props.filters.search ?? '')

watch(search, (val) => {
    router.get(route('guardians.index'), { search: val || undefined }, {
        preserveState: true,
        replace: true,
    })
})
</script>

<template>
    <AppLayout title="Guardians">
        <Head title="Guardians" />

        <div class="py-6 mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Guardians / Parents</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ guardians.total }} guardian{{ guardians.total !== 1 ? 's' : '' }} registered</p>
                </div>
                <div class="relative w-64">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name or phone…"
                        class="w-full pl-9 pr-4 py-2 rounded-md border-gray-300 text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Phone</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Linked Pupils</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Portal</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!guardians.data.length">
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">No guardians found.</td>
                        </tr>
                        <tr v-for="g in guardians.data" :key="g.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ g.full_name }}</p>
                                <p class="text-xs text-gray-400 capitalize">{{ g.relationship }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ g.phone }}
                                <span v-if="g.phone2" class="block text-xs text-gray-400">{{ g.phone2 }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ g.email ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <Link
                                        v-for="pupil in g.pupils"
                                        :key="pupil.id"
                                        :href="route('pupils.show', pupil.id)"
                                        class="inline-block text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded hover:bg-indigo-100"
                                    >
                                        {{ pupil.first_name }} {{ pupil.last_name }}
                                    </Link>
                                    <span v-if="!g.pupils.length" class="text-xs text-gray-400">—</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    v-if="g.portal_access"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 px-2 py-0.5 rounded-full"
                                >
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                                    Active
                                </span>
                                <span v-else class="text-xs text-gray-400">None</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    v-if="g.pupils.length"
                                    :href="route('pupils.show', g.pupils[0].id) + '#guardians'"
                                    class="text-xs text-indigo-600 hover:underline"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="guardians.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-gray-500">
                <p>Showing {{ guardians.from }}–{{ guardians.to }} of {{ guardians.total }}</p>
                <div class="flex gap-1">
                    <template v-for="link in guardians.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1 rounded border text-xs"
                            :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 hover:bg-gray-50'"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-1 rounded border border-gray-100 text-xs text-gray-300"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
