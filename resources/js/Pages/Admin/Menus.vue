<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

type MenuItem = {
    id: number
    name: string
    icon: string
    route: string | null
    position: number
    is_active: boolean
    parent_id: number | null
    children: MenuItem[]
}

type Role = { id: number; name: string }

const props = defineProps<{
    menus: MenuItem[]
    roles: Role[]
    roleMenuMap: Record<number, number[]>
}>()

// ─── Panel 1: Tree editor ──────────────────────────────────────────────────

const newGroup = ref({ name: '', icon: 'circle', route: '' })
const newItem  = ref({ name: '', icon: 'circle', route: '', parent_id: null as number | null })
const showGroupForm = ref(false)
const showItemForm  = ref(false)

const editing = ref<{ id: number; name: string; icon: string; route: string } | null>(null)

function startEdit(menu: MenuItem) {
    editing.value = { id: menu.id, name: menu.name, icon: menu.icon, route: menu.route ?? '' }
}
function cancelEdit() { editing.value = null }

function saveEdit() {
    if (!editing.value) return
    router.patch(route('admin.menus.update', editing.value.id), {
        name: editing.value.name,
        icon: editing.value.icon,
        route: editing.value.route || null,
    }, { preserveScroll: true })
    editing.value = null
}

function toggleActive(menu: MenuItem) {
    router.patch(route('admin.menus.update', menu.id), { is_active: !menu.is_active }, { preserveScroll: true })
}

function deleteMenu(id: number) {
    if (!confirm('Delete this menu item and all its children?')) return
    router.delete(route('admin.menus.destroy', id))
}

function addGroup() {
    router.post(route('admin.menus.store'), { ...newGroup.value, parent_id: null })
    newGroup.value = { name: '', icon: 'circle', route: '' }
    showGroupForm.value = false
}

function addItem() {
    if (!newItem.value.parent_id) return
    router.post(route('admin.menus.store'), newItem.value)
    newItem.value = { name: '', icon: 'circle', route: '', parent_id: null }
    showItemForm.value = false
}

// ─── Drag & drop ──────────────────────────────────────────────────────────

const draggingId = ref<number | null>(null)
const dropTarget = ref<{ id: number; side: 'top' | 'bottom'; type: 'group' | 'item' } | null>(null)

const isDraggingGroup = computed(() =>
    draggingId.value !== null && props.menus.some(g => g.id === draggingId.value)
)

function dragStart(id: number) { draggingId.value = id; dropTarget.value = null }
function dragEnd() { draggingId.value = null; dropTarget.value = null }

function onGroupDragOver(e: DragEvent, groupId: number) {
    if (draggingId.value === groupId) return
    const el = e.currentTarget as HTMLElement
    const header = el.querySelector('[data-group-header]') as HTMLElement | null
    const rect = (header ?? el).getBoundingClientRect()
    dropTarget.value = { id: groupId, side: e.clientY < rect.top + rect.height / 2 ? 'top' : 'bottom', type: 'group' }
}

function onItemDragOver(e: DragEvent, itemId: number) {
    e.stopPropagation()
    const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
    dropTarget.value = { id: itemId, side: e.clientY < rect.top + rect.height / 2 ? 'top' : 'bottom', type: 'item' }
}

function onGroupDrop(groupId: number) {
    if (draggingId.value === null) { dragEnd(); return }
    if (isDraggingGroup.value) doGroupReorder(draggingId.value, groupId, dropTarget.value?.side ?? 'bottom')
    else doReparent(draggingId.value, groupId)
    dragEnd()
}

function onItemDrop(e: DragEvent, parentGroupId: number, itemId: number) {
    e.stopPropagation()
    if (draggingId.value === null || isDraggingGroup.value) { dragEnd(); return }
    doItemReorder(draggingId.value, parentGroupId, itemId, dropTarget.value?.side ?? 'bottom')
    dragEnd()
}

function doGroupReorder(fromId: number, toId: number, side: 'top' | 'bottom') {
    if (fromId === toId) return
    const groups = props.menus.map((g, i) => ({ id: g.id, position: i, parent_id: null as null | number }))
    const fromIdx = groups.findIndex(g => g.id === fromId)
    const [moved] = groups.splice(fromIdx, 1)
    const toIdx = groups.findIndex(g => g.id === toId)
    groups.splice(side === 'top' ? toIdx : toIdx + 1, 0, moved)
    groups.forEach((g, i) => { g.position = i })
    const childItems = props.menus.flatMap(g => g.children.map((c, ci) => ({ id: c.id, position: ci, parent_id: g.id as null | number })))
    router.patch(route('admin.menus.reorder'), { items: [...groups, ...childItems] }, { preserveState: false, preserveScroll: true })
}

function doReparent(itemId: number, toGroupId: number) {
    const groups = props.menus.map((g, i) => ({ id: g.id, position: i, parent_id: null as null | number }))
    const allItems = props.menus.flatMap(g => g.children.map((c, ci) => ({ id: c.id, position: ci, parent_id: g.id as null | number })))
    const moved = allItems.find(i => i.id === itemId)
    if (!moved) return
    moved.parent_id = toGroupId
    moved.position = allItems.filter(i => i.parent_id === toGroupId && i.id !== itemId).length
    router.patch(route('admin.menus.reorder'), { items: [...groups, ...allItems] }, { preserveState: false, preserveScroll: true })
}

function doItemReorder(fromId: number, toGroupId: number, toItemId: number, side: 'top' | 'bottom') {
    const groups = props.menus.map((g, i) => ({ id: g.id, position: i, parent_id: null as null | number }))
    const updatedItems: { id: number; position: number; parent_id: number | null }[] = []
    const draggedItem = props.menus.flatMap(g => g.children).find(c => c.id === fromId)
    if (!draggedItem) return
    for (const group of props.menus) {
        const children = [...group.children.filter(c => c.id !== fromId)]
        if (group.id === toGroupId) {
            const targetIdx = children.findIndex(c => c.id === toItemId)
            const insertAt = side === 'top' ? targetIdx : targetIdx + 1
            children.splice(insertAt < 0 ? children.length : insertAt, 0, draggedItem)
        }
        children.forEach((c, i) => updatedItems.push({ id: c.id, position: i, parent_id: group.id }))
    }
    router.patch(route('admin.menus.reorder'), { items: [...groups, ...updatedItems] }, { preserveState: false, preserveScroll: true })
}

// ─── Panel 2: Role assignment ──────────────────────────────────────────────

const selectedRoleId = ref<number | null>(null)
const roleMenuIds    = ref<number[]>([])
const selectedRole   = computed(() => props.roles.find(r => r.id === selectedRoleId.value))

function selectRole(roleId: number) {
    selectedRoleId.value = roleId
    roleMenuIds.value = [...(props.roleMenuMap[roleId] ?? [])]
}

function toggleMenuForRole(menuId: number) {
    const idx = roleMenuIds.value.indexOf(menuId)
    if (idx >= 0) roleMenuIds.value.splice(idx, 1)
    else roleMenuIds.value.push(menuId)
}

function saveRoleMenus() {
    if (!selectedRoleId.value) return
    router.post(route('admin.menus.role-assignments'), { role_id: selectedRoleId.value, menu_ids: roleMenuIds.value })
}

// ─── Panel 3: User overrides ───────────────────────────────────────────────

const overrideUserId = ref('')
const overrides      = ref<{ menu_id: number; granted: boolean }[]>([])

function toggleOverride(menuId: number, granted: boolean) {
    const existing = overrides.value.find(o => o.menu_id === menuId)
    if (existing) existing.granted = granted
    else overrides.value.push({ menu_id: menuId, granted })
}

function removeOverride(menuId: number) {
    overrides.value = overrides.value.filter(o => o.menu_id !== menuId)
}

function saveOverrides() {
    if (!overrideUserId.value) return
    router.post(route('admin.menus.user-overrides'), { user_id: parseInt(overrideUserId.value), overrides: overrides.value })
}
</script>

<template>
    <AppLayout title="Menu Management">
        <Head title="Menu Management" />

        <div class="py-8 px-4 sm:px-6 lg:px-8 space-y-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Menu Management</h1>
                <p class="mt-1 text-sm text-gray-500">Control navigation structure and role/user access</p>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">

                <!-- ── Panel 1: Tree ───────────────────────────────────── -->
                <div class="rounded-lg border bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b bg-gray-50 px-4 py-3">
                        <h2 class="text-sm font-semibold text-gray-700">Menu Tree</h2>
                        <div class="flex gap-2">
                            <button
                                class="rounded-md border border-gray-300 bg-white px-3 py-1 text-xs font-medium hover:bg-gray-50"
                                @click="showGroupForm = !showGroupForm"
                            >+ Group</button>
                            <button
                                class="rounded-md border border-gray-300 bg-white px-3 py-1 text-xs font-medium hover:bg-gray-50"
                                @click="showItemForm = !showItemForm"
                            >+ Item</button>
                        </div>
                    </div>

                    <!-- Add group form -->
                    <div v-if="showGroupForm" class="border-b bg-gray-50 p-3 space-y-2">
                        <div class="flex gap-2">
                            <input v-model="newGroup.name" placeholder="Group name" class="flex-1 rounded-md border-gray-300 text-sm shadow-sm" />
                            <input v-model="newGroup.icon" placeholder="Icon key" class="w-32 rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                        <div class="flex justify-end gap-2">
                            <button class="text-xs text-gray-500 hover:text-gray-700 px-2 py-1" @click="showGroupForm = false">Cancel</button>
                            <button class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-700" @click="addGroup">Add Group</button>
                        </div>
                    </div>

                    <!-- Add item form -->
                    <div v-if="showItemForm" class="border-b bg-gray-50 p-3 space-y-2">
                        <div class="flex gap-2">
                            <input v-model="newItem.name" placeholder="Item name" class="flex-1 rounded-md border-gray-300 text-sm shadow-sm" />
                            <input v-model="newItem.icon" placeholder="Icon key" class="w-28 rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                        <div class="flex gap-2">
                            <input v-model="newItem.route" placeholder="route.name or /path" class="flex-1 rounded-md border-gray-300 text-sm shadow-sm" />
                            <select v-model="newItem.parent_id" class="w-36 rounded-md border-gray-300 text-sm shadow-sm">
                                <option :value="null">— group —</option>
                                <option v-for="g in menus" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button class="text-xs text-gray-500 hover:text-gray-700 px-2 py-1" @click="showItemForm = false">Cancel</button>
                            <button class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-700" @click="addItem">Add Item</button>
                        </div>
                    </div>

                    <!-- Tree list -->
                    <ul class="divide-y">
                        <li
                            v-for="group in menus"
                            :key="group.id"
                            :draggable="editing?.id !== group.id"
                            class="transition-[border,opacity] duration-100"
                            :class="{
                                'opacity-40': draggingId === group.id,
                                'border-t-2 border-indigo-400': isDraggingGroup && dropTarget?.id === group.id && dropTarget?.side === 'top',
                                'border-b-2 border-indigo-400': isDraggingGroup && dropTarget?.id === group.id && dropTarget?.side === 'bottom',
                                'ring-2 ring-indigo-300 ring-inset': !isDraggingGroup && dropTarget?.id === group.id && dropTarget?.type === 'group',
                            }"
                            @dragstart="dragStart(group.id)"
                            @dragend="dragEnd"
                            @dragover.prevent="onGroupDragOver($event, group.id)"
                            @drop.prevent="onGroupDrop(group.id)"
                        >
                            <!-- Group header -->
                            <div data-group-header class="flex items-center justify-between px-4 py-2.5">
                                <template v-if="editing?.id !== group.id">
                                    <div class="flex cursor-grab items-center gap-2 text-sm">
                                        <span class="select-none text-gray-400">⠿</span>
                                        <span class="text-xs text-gray-400">[{{ group.icon }}]</span>
                                        <span class="font-medium text-gray-800">{{ group.name }}</span>
                                        <span v-if="!group.is_active" class="rounded-full bg-red-100 px-1.5 py-0.5 text-xs text-red-600">inactive</span>
                                    </div>
                                    <div class="flex gap-1">
                                        <button class="rounded px-2 py-0.5 text-xs text-gray-500 hover:bg-gray-100" @click="startEdit(group)">Edit</button>
                                        <button class="rounded px-2 py-0.5 text-xs text-gray-500 hover:bg-gray-100" @click="toggleActive(group)">
                                            {{ group.is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                        <button class="rounded px-2 py-0.5 text-xs text-red-500 hover:bg-red-50" @click="deleteMenu(group.id)">✕</button>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="flex flex-1 items-center gap-2 pr-2">
                                        <input v-model="editing!.name" placeholder="Name" class="h-7 flex-1 rounded border-gray-300 text-sm" @keyup.enter="saveEdit" @keyup.escape="cancelEdit" />
                                        <input v-model="editing!.icon" placeholder="Icon" class="h-7 w-24 rounded border-gray-300 text-sm" @keyup.enter="saveEdit" @keyup.escape="cancelEdit" />
                                        <input v-model="editing!.route" placeholder="/route" class="h-7 flex-1 rounded border-gray-300 text-sm" @keyup.enter="saveEdit" @keyup.escape="cancelEdit" />
                                    </div>
                                    <div class="flex gap-1">
                                        <button class="rounded bg-indigo-600 px-2 py-0.5 text-xs text-white hover:bg-indigo-700" @click="saveEdit">Save</button>
                                        <button class="rounded px-2 py-0.5 text-xs text-gray-500 hover:bg-gray-100" @click="cancelEdit">Cancel</button>
                                    </div>
                                </template>
                            </div>

                            <!-- Children -->
                            <ul class="border-t bg-gray-50">
                                <li
                                    v-for="item in group.children"
                                    :key="item.id"
                                    :draggable="editing?.id !== item.id"
                                    class="transition-[border,opacity] duration-100"
                                    :class="{
                                        'flex cursor-grab items-center justify-between px-8 py-2 text-sm': editing?.id !== item.id,
                                        'block px-8 py-2': editing?.id === item.id,
                                        'opacity-40': draggingId === item.id,
                                        'border-t-2 border-indigo-400': !isDraggingGroup && dropTarget?.id === item.id && dropTarget?.side === 'top',
                                        'border-b-2 border-indigo-400': !isDraggingGroup && dropTarget?.id === item.id && dropTarget?.side === 'bottom',
                                    }"
                                    @dragstart.stop="dragStart(item.id)"
                                    @dragend.stop="dragEnd"
                                    @dragover.prevent="onItemDragOver($event, item.id)"
                                    @drop.prevent="onItemDrop($event, group.id, item.id)"
                                >
                                    <template v-if="editing?.id !== item.id">
                                        <div class="flex items-center gap-2">
                                            <span class="select-none text-gray-400">⠿</span>
                                            <span class="text-xs text-gray-400">[{{ item.icon }}]</span>
                                            <span class="text-gray-700">{{ item.name }}</span>
                                            <span class="text-xs text-gray-400">→ {{ item.route ?? '—' }}</span>
                                            <span v-if="!item.is_active" class="rounded-full bg-red-100 px-1.5 py-0.5 text-xs text-red-600">inactive</span>
                                        </div>
                                        <div class="flex gap-1">
                                            <button class="rounded px-2 py-0.5 text-xs text-gray-500 hover:bg-gray-100" @click="startEdit(item)">Edit</button>
                                            <button class="rounded px-2 py-0.5 text-xs text-gray-500 hover:bg-gray-100" @click="toggleActive(item)">
                                                {{ item.is_active ? 'Disable' : 'Enable' }}
                                            </button>
                                            <button class="rounded px-2 py-0.5 text-xs text-red-500 hover:bg-red-50" @click="deleteMenu(item.id)">✕</button>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="flex items-center gap-2 pb-1">
                                            <input v-model="editing!.name" placeholder="Name" class="h-7 flex-1 rounded border-gray-300 text-sm" @keyup.enter="saveEdit" @keyup.escape="cancelEdit" />
                                            <input v-model="editing!.icon" placeholder="Icon" class="h-7 w-24 rounded border-gray-300 text-sm" @keyup.enter="saveEdit" @keyup.escape="cancelEdit" />
                                            <input v-model="editing!.route" placeholder="route.name" class="h-7 flex-1 rounded border-gray-300 text-sm" @keyup.enter="saveEdit" @keyup.escape="cancelEdit" />
                                            <button class="rounded bg-indigo-600 px-2 py-0.5 text-xs text-white" @click="saveEdit">Save</button>
                                            <button class="rounded px-2 py-0.5 text-xs text-gray-500 hover:bg-gray-100" @click="cancelEdit">Cancel</button>
                                        </div>
                                    </template>
                                </li>
                                <li v-if="group.children.length === 0" class="px-8 py-2 text-xs italic text-gray-400">
                                    Drop items here or add via "+ Item"
                                </li>
                            </ul>
                        </li>
                        <li v-if="menus.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
                            No menu groups yet. Start by adding a group.
                        </li>
                    </ul>
                </div>

                <!-- ── Panel 2: Role assignment ─────────────────────────── -->
                <div class="rounded-lg border bg-white shadow-sm">
                    <div class="border-b bg-gray-50 px-4 py-3">
                        <h2 class="text-sm font-semibold text-gray-700">Role Access</h2>
                        <p class="text-xs text-gray-500">Select a role then tick which menus it can see</p>
                    </div>

                    <div class="p-4 space-y-4">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="role in roles"
                                :key="role.id"
                                class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                                :class="selectedRoleId === role.id
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                @click="selectRole(role.id)"
                            >{{ role.name }}</button>
                        </div>

                        <div v-if="selectedRoleId" class="space-y-0.5">
                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">
                                Menus for <span class="text-gray-900">{{ selectedRole?.name }}</span>
                            </p>

                            <template v-for="group in menus" :key="group.id">
                                <label class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 hover:bg-gray-50 font-medium text-sm">
                                    <input
                                        type="checkbox"
                                        :checked="roleMenuIds.includes(group.id)"
                                        class="rounded border-gray-300 text-indigo-600"
                                        @change="toggleMenuForRole(group.id)"
                                    />
                                    <span class="text-gray-800">{{ group.name }}</span>
                                </label>
                                <label
                                    v-for="item in group.children"
                                    :key="item.id"
                                    class="flex cursor-pointer items-center gap-2 rounded pl-7 pr-2 py-1 hover:bg-gray-50 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="roleMenuIds.includes(item.id)"
                                        class="rounded border-gray-300 text-indigo-600"
                                        @change="toggleMenuForRole(item.id)"
                                    />
                                    <span class="text-gray-700">{{ item.name }}</span>
                                    <span class="ml-auto text-xs text-gray-400">{{ item.route }}</span>
                                </label>
                            </template>

                            <button
                                class="mt-3 w-full rounded-md bg-indigo-600 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                @click="saveRoleMenus"
                            >Save Access for {{ selectedRole?.name }}</button>
                        </div>

                        <p v-else class="text-sm text-gray-400">Select a role above to configure its menu access.</p>
                    </div>
                </div>
            </div>

            <!-- ── Panel 3: User overrides ─────────────────────────────── -->
            <details class="rounded-lg border bg-white shadow-sm">
                <summary class="cursor-pointer select-none border-b bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700">
                    User Menu Overrides
                    <span class="ml-2 text-xs font-normal text-gray-400">Grant or deny specific menus for individual users</span>
                </summary>

                <div class="p-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">User ID</label>
                        <input v-model="overrideUserId" type="number" placeholder="Enter user ID" class="w-48 rounded-md border-gray-300 text-sm shadow-sm" />
                    </div>

                    <div v-if="overrideUserId" class="space-y-1">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Set overrides</p>

                        <template v-for="group in menus" :key="group.id">
                            <p class="pt-2 text-xs font-semibold text-gray-600">{{ group.name }}</p>
                            <div
                                v-for="item in group.children"
                                :key="item.id"
                                class="flex items-center justify-between rounded px-2 py-1 hover:bg-gray-50 text-sm"
                            >
                                <span class="text-gray-700">{{ item.name }}</span>
                                <div class="flex gap-1">
                                    <button
                                        class="rounded px-2 py-0.5 text-xs transition-colors"
                                        :class="overrides.find(o => o.menu_id === item.id)?.granted === true
                                            ? 'bg-green-100 text-green-700 font-medium'
                                            : 'bg-gray-100 text-gray-500 hover:bg-green-50'"
                                        @click="toggleOverride(item.id, true)"
                                    >Grant</button>
                                    <button
                                        class="rounded px-2 py-0.5 text-xs transition-colors"
                                        :class="overrides.find(o => o.menu_id === item.id)?.granted === false
                                            ? 'bg-red-100 text-red-700 font-medium'
                                            : 'bg-gray-100 text-gray-500 hover:bg-red-50'"
                                        @click="toggleOverride(item.id, false)"
                                    >Deny</button>
                                    <button
                                        v-if="overrides.find(o => o.menu_id === item.id)"
                                        class="rounded px-2 py-0.5 text-xs text-gray-400 hover:text-gray-600"
                                        @click="removeOverride(item.id)"
                                    >✕</button>
                                </div>
                            </div>
                        </template>

                        <button
                            class="mt-3 w-full rounded-md bg-indigo-600 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            @click="saveOverrides"
                        >Save Overrides for User #{{ overrideUserId }}</button>
                    </div>
                </div>
            </details>
        </div>
    </AppLayout>
</template>
