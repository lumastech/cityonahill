<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import type { FeedingStock } from '@/types/feeding'

const props = defineProps<{
    stocks: FeedingStock[]
    low_stock_ids: number[]
}>()

const showAddForm = ref(false)
const addForm = useForm({ item_name: '', unit: 'kg', quantity: '', reorder_level: '', cost_per_unit: '' })

function submitAdd() {
    addForm.post(route('stock.store'), {
        onSuccess: () => {
            showAddForm.value = false
            addForm.reset()
        },
    })
}

const movementForms = ref<Record<number, ReturnType<typeof useForm>>>({})

function getMovementForm(stockId: number) {
    if (!movementForms.value[stockId]) {
        movementForms.value[stockId] = useForm({ stock_id: stockId, type: 'consumption', quantity: '', notes: '' })
    }
    return movementForms.value[stockId]
}

function submitMovement(stock: FeedingStock) {
    const form = getMovementForm(stock.id)
    form.patch(route('stock.update', stock.id), {
        onSuccess: () => form.reset('quantity', 'notes'),
    })
}

function isLowStock(id: number): boolean {
    return props.low_stock_ids.includes(id)
}
</script>

<template>
    <Head title="Feeding Stock" />
    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900">Feeding Stock</h1>
                <button @click="showAddForm = !showAddForm"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    + Add Stock Item
                </button>
            </div>

            <!-- Add stock form -->
            <div v-if="showAddForm" class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-indigo-700">New Stock Item</h2>
                <form class="grid gap-3 sm:grid-cols-3" @submit.prevent="submitAdd">
                    <div>
                        <label class="block text-xs text-gray-600">Item Name</label>
                        <input v-model="addForm.item_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Unit</label>
                        <input v-model="addForm.unit" type="text" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Opening Quantity</label>
                        <input v-model="addForm.quantity" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Reorder Level</label>
                        <input v-model="addForm.reorder_level" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600">Cost/Unit (ZMW, optional)</label>
                        <input v-model="addForm.cost_per_unit" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" :disabled="addForm.processing"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                            Add
                        </button>
                        <button type="button" @click="showAddForm = false"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <div v-if="low_stock_ids.length" class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                ⚠ {{ low_stock_ids.length }} item{{ low_stock_ids.length !== 1 ? 's' : '' }} at or below reorder level.
            </div>

            <div class="space-y-3">
                <div v-for="stock in stocks" :key="stock.id"
                    :class="['rounded-lg border bg-white p-4 shadow-sm', isLowStock(stock.id) ? 'border-red-300' : 'border-gray-200']">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-900">{{ stock.item_name }}</p>
                                <span v-if="isLowStock(stock.id)"
                                    class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                                    LOW STOCK
                                </span>
                            </div>
                            <p class="mt-0.5 text-sm text-gray-600">
                                {{ stock.quantity_on_hand }} {{ stock.unit }} on hand
                                · Reorder at {{ stock.reorder_level }} {{ stock.unit }}
                            </p>
                            <p v-if="stock.last_restocked_at" class="text-xs text-gray-400">
                                Last restocked: {{ stock.last_restocked_at }}
                            </p>
                        </div>
                    </div>

                    <!-- Movement form -->
                    <form class="mt-3 flex flex-wrap items-end gap-2" @submit.prevent="submitMovement(stock)">
                        <div>
                            <label class="block text-xs text-gray-500">Movement</label>
                            <select :value="getMovementForm(stock.id).type"
                                @change="e => getMovementForm(stock.id).type = (e.target as HTMLSelectElement).value"
                                class="mt-1 rounded-md border-gray-300 text-xs shadow-sm">
                                <option value="restock">Restock</option>
                                <option value="consumption">Consumption</option>
                                <option value="wastage">Wastage</option>
                                <option value="adjustment">Adjustment</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Qty ({{ stock.unit }})</label>
                            <input :value="getMovementForm(stock.id).quantity"
                                @input="e => getMovementForm(stock.id).quantity = (e.target as HTMLInputElement).value"
                                type="number" step="0.01" placeholder="0.00"
                                class="mt-1 w-24 rounded-md border-gray-300 text-xs shadow-sm" />
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs text-gray-500">Notes</label>
                            <input :value="getMovementForm(stock.id).notes"
                                @input="e => getMovementForm(stock.id).notes = (e.target as HTMLInputElement).value"
                                type="text" placeholder="Optional notes"
                                class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm" />
                        </div>
                        <button type="submit" :disabled="getMovementForm(stock.id).processing"
                            class="rounded-md bg-gray-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800 disabled:opacity-50">
                            Record
                        </button>
                    </form>
                </div>
            </div>

            <div v-if="!stocks.length" class="py-16 text-center text-gray-400">No stock items yet.</div>
        </div>
    </div>
</template>
