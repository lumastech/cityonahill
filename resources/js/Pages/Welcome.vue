<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps<{
    canResetPassword: boolean
    status?: string
    stats: { pupils: number; schools: number; staff: number }
}>()

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.transform(data => ({ ...data, remember: data.remember ? 'on' : '' }))
        .post(route('login'), { onFinish: () => form.reset('password') })
}
</script>

<template>
    <Head title="SKUU — School Management System" />

    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- Left panel — login -->
        <div class="flex flex-col justify-center items-center w-full lg:w-[42%] bg-white px-8 py-12 min-h-screen lg:min-h-0">
            <div class="w-full max-w-sm">

                <!-- Logo -->
                <div class="flex items-center gap-2.5 mb-10">
                    <div class="w-9 h-9 rounded-xl bg-sky-600 flex items-center justify-center shadow-sm">
                        <span class="text-white font-bold text-sm tracking-tight">SK</span>
                    </div>
                    <span class="text-lg font-bold text-gray-900 tracking-tight">SKUU</span>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-1">Welcome back</h1>
                <p class="text-sm text-gray-500 mb-8">Sign in to manage your school.</p>

                <div v-if="status" class="mb-6 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                    {{ status }}
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                            </div>
                            <input
                                v-model="form.email"
                                type="email"
                                autocomplete="username"
                                required
                                autofocus
                                placeholder="you@school.ac.zm"
                                class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </div>
                            <input
                                v-model="form.password"
                                type="password"
                                autocomplete="current-password"
                                required
                                placeholder="••••••••••"
                                class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition"
                            />
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input v-model="form.remember" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500" />
                            <span class="text-sm text-gray-600">Remember me</span>
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-sky-600 hover:text-sky-700 font-medium"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-sky-600 hover:bg-sky-700 disabled:opacity-60 text-white font-semibold text-sm rounded-lg shadow-sm transition-colors"
                    >
                        <span>Sign in</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </form>

                <p class="mt-10 text-center text-xs text-gray-400">
                    Locally hosted · Protected by SKUU ·
                    <Link :href="route('about')" class="hover:text-gray-600 underline">About</Link>
                </p>
            </div>
        </div>

        <!-- Right panel — brand -->
        <div class="hidden lg:flex flex-col justify-between w-[58%] px-14 py-12"
             style="background: linear-gradient(155deg, #0c4a6e 0%, #0369a1 50%, #0e7490 100%);">

            <!-- Top brand -->
            <div>
                <span class="text-xs font-bold tracking-[0.2em] text-sky-300 uppercase">SKUU</span>
            </div>

            <!-- Centre copy -->
            <div>
                <p class="text-xs font-semibold tracking-[0.15em] text-sky-300 uppercase mb-4">Built for Zambia</p>
                <h2 class="text-4xl xl:text-5xl font-extrabold text-white leading-tight mb-5">
                    One system for<br />your whole school.
                </h2>
                <p class="text-sky-200 text-base leading-relaxed max-w-md">
                    Enrolment, fees, attendance and termly reports —
                    managed in one place and supported locally.
                </p>
            </div>

            <!-- Stats -->
            <div class="flex items-end justify-between border-t border-white/10 pt-8">
                <div class="flex gap-12">
                    <div>
                        <div class="text-3xl font-extrabold text-white">{{ stats.pupils.toLocaleString() }}</div>
                        <div class="text-xs text-sky-300 mt-1">Pupils enrolled</div>
                    </div>
                    <div>
                        <div class="text-3xl font-extrabold text-white">{{ stats.staff.toLocaleString() }}</div>
                        <div class="text-xs text-sky-300 mt-1">Staff accounts</div>
                    </div>
                    <div>
                        <div class="text-3xl font-extrabold text-white">{{ stats.schools }}</div>
                        <div class="text-xs text-sky-300 mt-1">Schools</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
