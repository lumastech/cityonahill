<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps<{
    canResetPassword?: boolean
    status?: string
    stats?: { pupils: number; schools: number; staff: number }
}>()

// Set to the school crest path (e.g. '/images/landing/crest.png') once the image is linked.
const crest = ''

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
    <Head>
        <title>Log in — City on a Hill Academy</title>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet" />
    </Head>

    <div class="coa-login min-h-screen flex flex-col lg:flex-row">

        <!-- Left panel — login -->
        <div class="flex flex-col justify-center items-center w-full lg:w-[42%] bg-[#FFFDF7] px-8 py-12 min-h-screen lg:min-h-0">
            <div class="w-full max-w-sm">

                <!-- Brand -->
                <Link :href="route('welcome')" class="flex items-center gap-3 mb-10">
                    <img v-if="crest" :src="crest" alt="City on a Hill Academy crest" class="w-12 h-12 object-contain" />
                    <div v-else class="w-12 h-12 rounded-xl border-2 border-dashed border-[#9FB4C2] bg-[#EAF7FB] flex items-center justify-center text-[10px] font-semibold text-[#5A7285]">
                        Crest
                    </div>
                    <span class="disp font-extrabold text-lg text-[#17255A] leading-tight">
                        City on a Hill
                        <small class="block font-sans font-semibold text-[11px] tracking-[0.12em] uppercase text-[#D5273B]">Academy · Lusaka</small>
                    </span>
                </Link>

                <h1 class="disp text-3xl font-extrabold text-[#17255A] mb-1">Welcome back</h1>
                <p class="text-sm text-[#41497A] mb-8">Sign in to the school portal.</p>

                <div v-if="status" class="mb-6 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                    {{ status }}
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm font-semibold text-[#1B2340] mb-1.5">Email address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-[#9FB4C2]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
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
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[#17255A]/20 bg-white text-sm text-[#1B2340] placeholder-[#9FB4C2] focus:outline-none focus:ring-2 focus:ring-[#F6B31B] focus:border-transparent transition"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-xs text-[#D5273B]">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-[#1B2340] mb-1.5">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-[#9FB4C2]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </div>
                            <input
                                v-model="form.password"
                                type="password"
                                autocomplete="current-password"
                                required
                                placeholder="••••••••••"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-[#17255A]/20 bg-white text-sm text-[#1B2340] placeholder-[#9FB4C2] focus:outline-none focus:ring-2 focus:ring-[#F6B31B] focus:border-transparent transition"
                            />
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-[#D5273B]">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input v-model="form.remember" type="checkbox" class="w-4 h-4 rounded border-[#17255A]/30 text-[#1B93B6] focus:ring-[#F6B31B]" />
                            <span class="text-sm text-[#41497A]">Remember me</span>
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-[#1B93B6] hover:text-[#17255A] font-semibold"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 py-3 px-4 bg-[#D5273B] hover:-translate-y-0.5 disabled:opacity-60 disabled:translate-y-0 text-white font-bold text-sm rounded-full shadow-[0_5px_0_#A31B2C] transition-transform"
                    >
                        <span>Sign in</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </form>

                <p class="mt-10 text-center text-xs text-[#5A6180]">
                    <Link :href="route('welcome')" class="hover:text-[#17255A] underline">← Back to school website</Link>
                    ·
                    <Link :href="route('about')" class="hover:text-[#17255A] underline">About</Link>
                </p>
            </div>
        </div>

        <!-- Right panel — brand -->
        <div class="hidden lg:flex flex-col justify-between w-[58%] px-14 py-12 bg-[#17255A]">

            <!-- Top brand -->
            <div>
                <span class="text-xs font-bold tracking-[0.2em] text-[#F6B31B] uppercase">City on a Hill Academy</span>
            </div>

            <!-- Centre copy -->
            <div>
                <p class="text-xs font-semibold tracking-[0.15em] text-[#2BAFD4] uppercase mb-4">Kabanana, Great North Road · Lusaka</p>
                <h2 class="disp text-4xl xl:text-5xl font-extrabold text-white leading-tight mb-5">
                    Learning today,<br />
                    <span class="hl">leading tomorrow.</span>
                </h2>
                <p class="text-[#C9D2F2] text-base leading-relaxed max-w-md">
                    Enrolment, fees, attendance and termly reports —
                    managed in one place for our whole school community.
                </p>
            </div>

            <!-- Stats -->
            <div v-if="stats" class="flex items-end justify-between border-t border-white/10 pt-8">
                <div class="flex gap-12">
                    <div>
                        <div class="disp text-3xl font-extrabold text-white">{{ stats.pupils.toLocaleString() }}</div>
                        <div class="text-xs text-[#2BAFD4] mt-1">Pupils enrolled</div>
                    </div>
                    <div>
                        <div class="disp text-3xl font-extrabold text-white">{{ stats.staff.toLocaleString() }}</div>
                        <div class="text-xs text-[#2BAFD4] mt-1">Staff accounts</div>
                    </div>
                    <div>
                        <div class="disp text-3xl font-extrabold text-white">{{ stats.schools }}</div>
                        <div class="text-xs text-[#2BAFD4] mt-1">Schools</div>
                    </div>
                </div>
                <div class="disp text-sm font-bold text-[#F6B31B]">"Learning Today, Leading Tomorrow."</div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.coa-login { font-family: 'Figtree', sans-serif; }
.disp { font-family: 'Bricolage Grotesque', sans-serif; }
.hl { position: relative; white-space: nowrap; }
.hl::after {
    content: "";
    position: absolute;
    left: 0; right: 0; bottom: 4px;
    height: .3em;
    background: #F6B31B;
    z-index: -1;
    border-radius: 3px;
    transform: rotate(-1deg);
}
</style>
