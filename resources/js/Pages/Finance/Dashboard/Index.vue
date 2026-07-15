<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { fmtDate } from '@/utils/date'

interface TrendPoint { month: string; label: string; income: number; expenses: number }
interface Debtor { pupil_id: number; name: string; grade: string | number; outstanding: number; invoice_count: number }
interface Bucket { key: string; label: string; amount: number; count: number }

const props = defineProps<{
    period: { label: string; from: string; to: string }
    summary: {
        total_income: number; fees_collected: number; other_income: number
        total_expenses: number; net: number; outstanding: number; debtor_count: number
    }
    top_debtors: Debtor[]
    aging_buckets: Bucket[]
    trend: TrendPoint[]
}>()

function formatZmw(n: number) { return `ZMW ${Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` }

const INCOME_COLOR = '#2563eb'
const EXPENSE_COLOR = '#ea580c'

// --- grouped bar chart geometry ---
const CHART_W = 640
const CHART_H = 220
const PAD = { top: 12, right: 12, bottom: 28, left: 48 }
const plotW = CHART_W - PAD.left - PAD.right
const plotH = CHART_H - PAD.top - PAD.bottom

const maxVal = computed(() => Math.max(1, ...props.trend.flatMap((t) => [t.income, t.expenses])))
const groupW = computed(() => plotW / Math.max(1, props.trend.length))
const barW = computed(() => Math.min(20, (groupW.value - 8) / 2))

function barH(v: number) { return (v / maxVal.value) * plotH }
function yTop(v: number) { return PAD.top + plotH - barH(v) }
function groupX(i: number) { return PAD.left + i * groupW.value + groupW.value / 2 }

const gridLines = computed(() => {
    const steps = 4
    return Array.from({ length: steps + 1 }, (_, i) => {
        const val = (maxVal.value / steps) * i
        return { y: PAD.top + plotH - (val / maxVal.value) * plotH, val }
    })
})

function shortZmw(n: number) {
    if (n >= 1000) return `${(n / 1000).toFixed(0)}k`
    return String(Math.round(n))
}

const hover = ref<{ x: number; y: number; point: TrendPoint } | null>(null)
</script>

<template>
    <AppLayout>
    <Head title="Finance Dashboard" />
    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Finance Overview</h1>
                    <p class="text-sm text-gray-500">{{ period.label }} · {{ fmtDate(period.from) }} — {{ fmtDate(period.to) }}</p>
                </div>
                <div class="flex gap-2 text-sm">
                    <Link :href="route('finance.profit-loss')" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50">Profit &amp; Loss</Link>
                    <Link :href="route('finance.receivables')" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50">Receivables</Link>
                </div>
            </div>

            <!-- Summary tiles -->
            <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Income</p>
                    <p class="mt-1 text-xl font-bold text-green-700">{{ formatZmw(summary.total_income) }}</p>
                    <p class="mt-1 text-xs text-gray-400">Fees {{ formatZmw(summary.fees_collected) }} · Other {{ formatZmw(summary.other_income) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Expenses</p>
                    <p class="mt-1 text-xl font-bold text-red-700">{{ formatZmw(summary.total_expenses) }}</p>
                </div>
                <div class="rounded-lg border p-4 shadow-sm" :class="summary.net >= 0 ? 'border-green-100 bg-green-50' : 'border-red-100 bg-red-50'">
                    <p class="text-sm" :class="summary.net >= 0 ? 'text-green-600' : 'text-red-600'">Net {{ summary.net >= 0 ? 'Surplus' : 'Deficit' }}</p>
                    <p class="mt-1 text-xl font-bold" :class="summary.net >= 0 ? 'text-green-800' : 'text-red-800'">{{ formatZmw(Math.abs(summary.net)) }}</p>
                </div>
                <Link :href="route('finance.receivables')" class="rounded-lg border border-indigo-100 bg-indigo-50 p-4 shadow-sm transition hover:bg-indigo-100">
                    <p class="text-sm text-indigo-600">Outstanding</p>
                    <p class="mt-1 text-xl font-bold text-indigo-800">{{ formatZmw(summary.outstanding) }}</p>
                    <p class="mt-1 text-xs text-indigo-400">{{ summary.debtor_count }} debtors</p>
                </Link>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Trend chart -->
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-900">Income vs Expenses</h2>
                        <div class="flex items-center gap-4 text-xs">
                            <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm" :style="{ background: INCOME_COLOR }" />Income</span>
                            <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm" :style="{ background: EXPENSE_COLOR }" />Expenses</span>
                        </div>
                    </div>

                    <div class="relative">
                        <svg :viewBox="`0 0 ${CHART_W} ${CHART_H}`" class="w-full" role="img" aria-label="Monthly income versus expenses">
                            <!-- gridlines -->
                            <g>
                                <line v-for="(g, i) in gridLines" :key="i" :x1="PAD.left" :x2="CHART_W - PAD.right" :y1="g.y" :y2="g.y" stroke="#f1f1ef" stroke-width="1" />
                                <text v-for="(g, i) in gridLines" :key="'l' + i" :x="PAD.left - 6" :y="g.y + 3" text-anchor="end" class="fill-gray-400" style="font-size: 9px">{{ shortZmw(g.val) }}</text>
                            </g>
                            <!-- bars -->
                            <g v-for="(t, i) in trend" :key="t.month" @mouseenter="hover = { x: groupX(i), y: PAD.top, point: t }" @mouseleave="hover = null">
                                <rect :x="groupX(i) - barW - 1" :y="yTop(t.income)" :width="barW" :height="barH(t.income)" :rx="Math.min(4, barW / 2)" :fill="INCOME_COLOR" />
                                <rect :x="groupX(i) + 1" :y="yTop(t.expenses)" :width="barW" :height="barH(t.expenses)" :rx="Math.min(4, barW / 2)" :fill="EXPENSE_COLOR" />
                                <rect :x="groupX(i) - groupW / 2" :y="PAD.top" :width="groupW" :height="plotH" fill="transparent" />
                                <text :x="groupX(i)" :y="CHART_H - 10" text-anchor="middle" class="fill-gray-500" style="font-size: 10px">{{ t.label }}</text>
                            </g>
                        </svg>
                        <div v-if="hover" class="pointer-events-none absolute rounded-md bg-gray-900 px-2.5 py-1.5 text-xs text-white shadow-lg" :style="{ left: `${(hover.x / CHART_W) * 100}%`, top: '0', transform: 'translate(-50%, calc(-100% - 4px))' }">
                            <p class="font-medium">{{ hover.point.label }}</p>
                            <p><span :style="{ color: '#93c5fd' }">Income</span> {{ formatZmw(hover.point.income) }}</p>
                            <p><span :style="{ color: '#fdba74' }">Expenses</span> {{ formatZmw(hover.point.expenses) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Aging + debtors -->
                <div class="space-y-6">
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 class="mb-3 text-base font-semibold text-gray-900">Receivables aging</h2>
                        <div class="space-y-2">
                            <div v-for="b in aging_buckets" :key="b.key" class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">{{ b.label }}</span>
                                <span class="font-medium text-gray-900">{{ formatZmw(b.amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 class="mb-3 text-base font-semibold text-gray-900">Top debtors</h2>
                        <ul class="space-y-2 text-sm">
                            <li v-for="d in top_debtors" :key="d.pupil_id" class="flex items-center justify-between">
                                <Link :href="route('pupils.show', d.pupil_id)" class="truncate text-indigo-600 hover:underline">{{ d.name }}</Link>
                                <span class="ml-2 shrink-0 font-medium text-red-700">{{ formatZmw(d.outstanding) }}</span>
                            </li>
                            <li v-if="!top_debtors.length" class="text-gray-400">No outstanding receivables.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
