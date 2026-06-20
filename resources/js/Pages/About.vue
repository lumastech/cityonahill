<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

const features = [
    {
        icon: 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
        title: 'Academic Management',
        desc: 'Manage grades, streams, subjects, timetables, and attendance from one place.',
    },
    {
        icon: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z',
        title: 'Exams & Results',
        desc: 'Record assessments, compute CA and exam marks, generate report cards instantly.',
    },
    {
        icon: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        title: 'Staff & HR',
        desc: 'Staff directory, leave management, payroll generation, and subject assignments.',
    },
    {
        icon: 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z',
        title: 'Finance',
        desc: 'Fee structures, invoices, expense tracking, budgets, and payment recording.',
    },
    {
        icon: 'M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819',
        title: 'Campus Life',
        desc: 'Library borrowings, transport routes, feeding sessions, and boarding management.',
    },
    {
        icon: 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z',
        title: 'Communication',
        desc: 'Notices, bulk SMS to parents, internal messaging, and parent portal access.',
    },
]
</script>

<template>
    <Head title="About SKUU" />

    <div class="min-h-screen bg-white font-sans antialiased">

        <!-- Navbar -->
        <header class="fixed top-0 inset-x-0 z-50 bg-white/90 backdrop-blur border-b border-gray-100">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
                <Link :href="route('welcome')" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-sky-600 flex items-center justify-center">
                        <span class="text-white font-bold text-sm">SK</span>
                    </div>
                    <span class="text-lg font-bold text-gray-900 tracking-tight">SKUU</span>
                </Link>
                <Link
                    :href="route('login')"
                    class="text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 px-4 py-2 rounded-md transition-colors"
                >
                    Sign In
                </Link>
            </div>
        </header>

        <!-- Hero -->
        <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-sky-50 to-white">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-block mb-4 px-3 py-1 text-xs font-semibold text-sky-700 bg-sky-100 rounded-full tracking-wide uppercase">
                    Built for Zambian Schools
                </span>
                <h1 class="text-5xl font-extrabold text-gray-900 leading-tight tracking-tight">
                    School Management,<br />
                    <span class="text-sky-600">Simplified.</span>
                </h1>
                <p class="mt-6 text-lg text-gray-500 max-w-2xl mx-auto leading-relaxed">
                    SKUU brings every part of your school — pupils, staff, academics, finance, and communication — into one seamless platform aligned with the Zambian curriculum.
                </p>
            </div>
        </section>

        <!-- Features -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
            <div class="mx-auto max-w-7xl">
                <div class="text-center mb-14">
                    <h2 class="text-3xl font-bold text-gray-900">Everything your school needs</h2>
                    <p class="mt-3 text-gray-500">One platform. Every department. Zero duplication.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        v-for="f in features"
                        :key="f.title"
                        class="group p-6 rounded-xl border border-gray-100 hover:border-sky-200 hover:shadow-md transition-all"
                    >
                        <div class="w-10 h-10 rounded-lg bg-sky-50 group-hover:bg-sky-100 flex items-center justify-center mb-4 transition-colors">
                            <svg class="w-5 h-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="f.icon" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1">{{ f.title }}</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">{{ f.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50">
            <div class="mx-auto max-w-5xl">
                <div class="text-center mb-14">
                    <h2 class="text-3xl font-bold text-gray-900">How it works</h2>
                    <p class="mt-3 text-gray-500">From setup to report cards in minutes.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-10 relative">
                    <div class="hidden sm:block absolute top-6 left-1/4 right-1/4 h-0.5 bg-sky-100" />
                    <div
                        v-for="(step, i) in [
                            { n: '01', title: 'Set up your school', desc: 'Add grades, streams, subjects, and staff. The setup takes minutes, not days.' },
                            { n: '02', title: 'Enrol pupils', desc: 'Admit pupils, assign them to streams, and record guardian contacts in one form.' },
                            { n: '03', title: 'Run your school', desc: 'Record attendance, enter assessment scores, generate report cards, and bill parents — all in one place.' },
                        ]"
                        :key="i"
                        class="flex flex-col items-center text-center"
                    >
                        <div class="w-12 h-12 rounded-full bg-sky-600 flex items-center justify-center text-white font-bold text-sm mb-4 relative z-10 shadow-md">
                            {{ step.n }}
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1">{{ step.title }}</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-20 px-4 sm:px-6 lg:px-8" style="background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 100%);">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold text-white">Ready to modernise your school?</h2>
                <p class="mt-4 text-sky-200">Sign in and take full control of your school's operations today.</p>
                <Link
                    :href="route('login')"
                    class="mt-8 inline-block px-8 py-3.5 bg-white text-sky-700 font-semibold rounded-lg hover:bg-sky-50 transition-colors shadow"
                >
                    Sign In Now
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-8 px-4 sm:px-6 lg:px-8 bg-gray-900">
            <div class="mx-auto max-w-7xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-sky-500 flex items-center justify-center">
                        <span class="text-white font-bold text-xs">SK</span>
                    </div>
                    <span class="text-sm text-gray-400">SKUU School Management System</span>
                </div>
                <p class="text-xs text-gray-600">© {{ new Date().getFullYear() }} SKUU. Built for Zambian schools.</p>
            </div>
        </footer>

    </div>
</template>
