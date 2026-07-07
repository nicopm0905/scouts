<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import BadgeEstado from '@/Components/Shared/BadgeEstado.vue'
import Modal from '@/Components/Shared/Modal.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    items: { type: Array, default: () => [] },
    needingReview: { type: Array, default: () => [] },
    overdueCheckouts: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    conditions: { type: Array, default: () => [] },
    events: { type: Array, default: () => [] },
    members: { type: Array, default: () => [] },
    can: { type: Object, default: () => ({}) },
})

const toast = useToast()

const stats = computed(() => {
    const units = props.items.reduce((s, i) => s + (i.quantity ?? 0), 0)
    const out = props.items.reduce((s, i) => s + ((i.quantity ?? 0) - (i.available_quantity ?? 0)), 0)
    return { items: props.items.length, units, out, review: props.needingReview.length, overdue: props.overdueCheckouts.length }
})

const columns = [
    { key: 'name', label: 'Nombre', sortable: true },
    { key: 'category_label', label: 'Categoría', sortable: true },
    { key: 'available_quantity', label: 'Disponible', sortable: true },
    { key: 'condition_label', label: 'Estado', sortable: true },
    { key: 'location', label: 'Ubicación', sortable: true },
]

const categoryFilter = ref(props.filters.category ?? '')
const conditionFilter = ref(props.filters.condition ?? '')
function applyFilters() {
    router.get(route('inventory.index'),
        { category: categoryFilter.value || undefined, condition: conditionFilter.value || undefined },
        { preserveState: true, replace: true })
}

const reserveTarget = ref(null)
const reserveMode = ref('event')
const reserveForm = useForm({
    quantity: 1, event_id: null, member_id: null,
    checked_out_at: new Date().toISOString().slice(0, 10), expected_return_at: '', notes: '',
})
function openReserve(item) {
    reserveTarget.value = item
    reserveMode.value = 'event'
    reserveForm.reset(); reserveForm.clearErrors()
    reserveForm.quantity = 1
    reserveForm.checked_out_at = new Date().toISOString().slice(0, 10)
}
function closeReserve() { reserveTarget.value = null }
function submitReserve() {
    if (reserveMode.value === 'event') reserveForm.member_id = null
    else reserveForm.event_id = null
    reserveForm.post(route('inventory.checkouts.store', reserveTarget.value.id), {
        preserveScroll: true, onSuccess: () => { toast.success('Material reservado.'); closeReserve() },
    })
}
function markReturned(id) {
    router.post(route('inventory.checkouts.return', id), {}, {
        preserveScroll: true, onSuccess: () => toast.success('Devolución registrada.'),
    })
}
function destroyItem(id) {
    router.delete(route('inventory.destroy', id), { preserveScroll: true, onSuccess: () => toast.success('Ítem eliminado.') })
}

const hasAlerts = computed(() => props.needingReview.length > 0 || props.overdueCheckouts.length > 0)
const inp = 'mt-1 block w-full rounded-lg border-ink-300 text-sm focus:border-brand-500 focus:ring-brand-500'
</script>

<template>
    <Head title="Inventario" />
    <AppLayout>
        <PageHeader title="Inventario" subtitle="Material del grupo: tiendas, cocina, botiquín y más.">
            <template #actions>
                <Link v-if="can.manage" :href="route('inventory.create')" class="btn-primary btn-sm">+ Nuevo ítem</Link>
            </template>
        </PageHeader>

        <!-- KPIs -->
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card p-4">
                <p class="section-title">Ítems</p>
                <p class="mt-1 text-2xl font-bold text-ink-800">{{ stats.items }}</p>
                <p class="text-xs text-ink-400">{{ stats.units }} unidades</p>
            </div>
            <div class="card p-4">
                <p class="section-title">Prestadas</p>
                <p class="mt-1 text-2xl font-bold" :class="stats.out > 0 ? 'text-blue-600' : 'text-ink-800'">{{ stats.out }}</p>
                <p class="text-xs text-ink-400">unidades fuera</p>
            </div>
            <div class="card p-4">
                <p class="section-title">A revisar</p>
                <p class="mt-1 text-2xl font-bold" :class="stats.review > 0 ? 'text-amber-600' : 'text-ink-800'">{{ stats.review }}</p>
                <p class="text-xs text-ink-400">próximas revisiones</p>
            </div>
            <div class="card p-4">
                <p class="section-title">Fuera de plazo</p>
                <p class="mt-1 text-2xl font-bold" :class="stats.overdue > 0 ? 'text-brand-600' : 'text-ink-800'">{{ stats.overdue }}</p>
                <p class="text-xs text-ink-400">sin devolver</p>
            </div>
        </div>

        <!-- Alertas -->
        <div v-if="hasAlerts" class="mb-6 grid gap-4 sm:grid-cols-2">
            <div v-if="needingReview.length" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-semibold text-amber-800">⚠️ Material pendiente de revisión</p>
                <ul class="mt-2 space-y-1 text-sm text-amber-700">
                    <li v-for="r in needingReview" :key="r.id">{{ r.name }} <span class="text-amber-500">· {{ r.category_label }}</span> — {{ r.next_review_at }}</li>
                </ul>
            </div>
            <div v-if="overdueCheckouts.length" class="rounded-xl border border-brand-200 bg-brand-50 p-4">
                <p class="text-sm font-semibold text-brand-800">⏰ Material sin devolver (fuera de plazo)</p>
                <ul class="mt-2 space-y-2 text-sm text-brand-700">
                    <li v-for="c in overdueCheckouts" :key="c.id" class="flex items-center justify-between gap-2">
                        <span>{{ c.item_name }} <span class="text-brand-500">— {{ c.event_title || c.member_name || 'sin asignar' }}</span> (prev. {{ c.expected_return_at }})</span>
                        <button v-if="can.reserve" type="button" class="shrink-0 rounded-md bg-brand-600 px-2 py-1 text-xs font-semibold text-white hover:bg-brand-700" @click="markReturned(c.id)">Devuelto</button>
                    </li>
                </ul>
            </div>
        </div>

        <DataTable :columns="columns" :rows="items" persist-key="inventory" placeholder="Buscar por nombre o ubicación…">
            <template #filters>
                <select v-model="categoryFilter" class="input w-auto py-1.5 text-sm" @change="applyFilters">
                    <option value="">Todas las categorías</option>
                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
                <select v-model="conditionFilter" class="input w-auto py-1.5 text-sm" @change="applyFilters">
                    <option value="">Todos los estados</option>
                    <option v-for="c in conditions" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
            </template>

            <template #cell-name="{ row }">
                <div class="flex items-center gap-2.5">
                    <img v-if="row.photo_url" :src="row.photo_url" class="h-9 w-9 rounded-lg object-cover" alt="" />
                    <span v-else class="flex h-9 w-9 items-center justify-center rounded-lg bg-ink-100 text-ink-400">📦</span>
                    <span class="font-medium text-ink-800">{{ row.name }}</span>
                </div>
            </template>

            <template #cell-available_quantity="{ row }">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-16 overflow-hidden rounded-full bg-ink-100">
                        <div class="h-full rounded-full" :class="row.available_quantity === 0 ? 'bg-brand-500' : 'bg-emerald-500'"
                            :style="{ width: (row.quantity ? row.available_quantity / row.quantity * 100 : 0) + '%' }" />
                    </div>
                    <span class="whitespace-nowrap text-xs" :class="row.available_quantity === 0 ? 'font-semibold text-brand-600' : 'text-ink-600'">
                        {{ row.available_quantity }} / {{ row.quantity }}
                    </span>
                </div>
            </template>

            <template #cell-condition_label="{ row }">
                <BadgeEstado :label="row.condition_label" :color="row.condition_color" />
            </template>
            <template #cell-location="{ value }">{{ value ?? '—' }}</template>

            <template #actions="{ row }">
                <div class="flex flex-wrap justify-end gap-2 text-xs">
                    <button v-if="can.reserve" type="button"
                        class="rounded-md bg-blue-50 px-2 py-1 font-semibold text-blue-700 hover:bg-blue-100"
                        :disabled="row.available_quantity === 0" :class="{ 'opacity-40': row.available_quantity === 0 }"
                        @click="openReserve(row)">Reservar</button>
                    <Link v-if="can.manage" :href="route('inventory.edit', row.id)"
                        class="rounded-md bg-ink-100 px-2 py-1 font-semibold text-ink-700 hover:bg-ink-200">Editar</Link>
                    <ConfirmButton v-if="can.manage" title="¿Eliminar este ítem?"
                        message="Se eliminará del inventario junto con su historial de reservas." confirm-label="Eliminar" @confirm="destroyItem(row.id)">
                        <span class="rounded-md bg-brand-50 px-2 py-1 font-semibold text-brand-700 hover:bg-brand-100">Eliminar</span>
                    </ConfirmButton>
                </div>
            </template>

            <template #empty>No hay ítems en el inventario.</template>
        </DataTable>

        <!-- Modal de reserva -->
        <Modal :show="!!reserveTarget" title="Reservar / prestar material" max-width="md" @close="closeReserve">
            <form v-if="reserveTarget" class="space-y-4" @submit.prevent="submitReserve">
                <p class="rounded-lg bg-ink-50 p-3 text-sm text-ink-600">
                    <strong class="text-ink-800">{{ reserveTarget.name }}</strong> — disponible: {{ reserveTarget.available_quantity }} / {{ reserveTarget.quantity }}
                </p>

                <div class="inline-flex rounded-lg border border-ink-200 bg-white p-0.5 text-sm">
                    <button type="button" class="rounded-md px-3 py-1 font-medium transition" :class="reserveMode === 'event' ? 'bg-brand-600 text-white' : 'text-ink-600'" @click="reserveMode = 'event'">Para un evento</button>
                    <button type="button" class="rounded-md px-3 py-1 font-medium transition" :class="reserveMode === 'member' ? 'bg-brand-600 text-white' : 'text-ink-600'" @click="reserveMode = 'member'">Para un responsable</button>
                </div>

                <div v-if="reserveMode === 'event'">
                    <label class="label">Evento</label>
                    <select v-model="reserveForm.event_id" :class="inp" required>
                        <option :value="null" disabled>Selecciona un evento…</option>
                        <option v-for="e in events" :key="e.id" :value="e.id">{{ e.title }} ({{ e.start_at }})</option>
                    </select>
                    <p v-if="reserveForm.errors.event_id" class="mt-1 text-xs text-brand-600">{{ reserveForm.errors.event_id }}</p>
                </div>
                <div v-else>
                    <label class="label">Responsable</label>
                    <select v-model="reserveForm.member_id" :class="inp" required>
                        <option :value="null" disabled>Selecciona un responsable…</option>
                        <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Cantidad</label>
                        <input type="number" min="1" :max="reserveTarget.available_quantity" v-model.number="reserveForm.quantity" :class="inp" />
                        <p v-if="reserveForm.errors.quantity" class="mt-1 text-xs text-brand-600">{{ reserveForm.errors.quantity }}</p>
                    </div>
                    <div>
                        <label class="label">Salida</label>
                        <input type="date" v-model="reserveForm.checked_out_at" :class="inp" />
                    </div>
                </div>
                <div>
                    <label class="label">Retorno previsto</label>
                    <input type="date" v-model="reserveForm.expected_return_at" :class="inp" />
                </div>
            </form>

            <template #footer>
                <button type="button" class="btn-ghost" @click="closeReserve">Cancelar</button>
                <button type="button" class="btn-primary" :disabled="reserveForm.processing" @click="submitReserve">Reservar</button>
            </template>
        </Modal>
    </AppLayout>
</template>
