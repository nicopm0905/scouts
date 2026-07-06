<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
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
    router.get(
        route('inventory.index'),
        { category: categoryFilter.value || undefined, condition: conditionFilter.value || undefined },
        { preserveState: true, replace: true }
    )
}

// --- Modal de reserva/préstamo ---
const reserveTarget = ref(null)
const reserveMode = ref('event') // event | member

const reserveForm = useForm({
    quantity: 1,
    event_id: null,
    member_id: null,
    checked_out_at: new Date().toISOString().slice(0, 10),
    expected_return_at: '',
    notes: '',
})

function openReserve(item) {
    reserveTarget.value = item
    reserveMode.value = 'event'
    reserveForm.reset()
    reserveForm.clearErrors()
    reserveForm.quantity = 1
    reserveForm.checked_out_at = new Date().toISOString().slice(0, 10)
}

function closeReserve() {
    reserveTarget.value = null
}

function submitReserve() {
    if (reserveMode.value === 'event') {
        reserveForm.member_id = null
    } else {
        reserveForm.event_id = null
    }

    reserveForm.post(route('inventory.checkouts.store', reserveTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Material reservado.')
            closeReserve()
        },
    })
}

function markReturned(checkoutId) {
    router.post(
        route('inventory.checkouts.return', checkoutId),
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success('Devolución registrada.'),
        }
    )
}

function destroyItem(id) {
    router.delete(route('inventory.destroy', id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Ítem eliminado.'),
    })
}

const hasAlerts = computed(() => props.needingReview.length > 0 || props.overdueCheckouts.length > 0)
</script>

<template>
    <AppLayout>
        <PageHeader title="Inventario" subtitle="Material del grupo: tiendas, cocina, botiquín y más.">
            <template #actions>
                <Link
                    v-if="can.manage"
                    :href="route('inventory.create')"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700"
                >
                    + Nuevo ítem
                </Link>
            </template>
        </PageHeader>

        <div v-if="hasAlerts" class="mb-6 grid gap-3 sm:grid-cols-2">
            <div v-if="needingReview.length" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-semibold text-amber-800">⚠️ Material pendiente de revisión</p>
                <ul class="mt-2 space-y-1 text-sm text-amber-700">
                    <li v-for="r in needingReview" :key="r.id">
                        {{ r.name }} <span class="text-amber-500">({{ r.category_label }})</span>
                        — {{ r.next_review_at }}
                    </li>
                </ul>
            </div>
            <div v-if="overdueCheckouts.length" class="rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-800">⏰ Material sin devolver (fuera de plazo)</p>
                <ul class="mt-2 space-y-2 text-sm text-red-700">
                    <li v-for="c in overdueCheckouts" :key="c.id" class="flex items-center justify-between gap-2">
                        <span>
                            {{ c.item_name }}
                            <span class="text-red-500">— {{ c.event_title || c.member_name || 'sin asignar' }}</span>
                            (prev. {{ c.expected_return_at }})
                        </span>
                        <button
                            v-if="can.reserve"
                            type="button"
                            class="shrink-0 rounded-md bg-red-600 px-2 py-1 text-xs font-semibold text-white hover:bg-red-700"
                            @click="markReturned(c.id)"
                        >
                            Marcar devuelto
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <DataTable :columns="columns" :rows="items" persist-key="inventory" placeholder="Buscar por nombre o ubicación…">
            <template #filters>
                <select v-model="categoryFilter" class="rounded-md border-slate-300 text-sm" @change="applyFilters">
                    <option value="">Todas las categorías</option>
                    <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
                <select v-model="conditionFilter" class="rounded-md border-slate-300 text-sm" @change="applyFilters">
                    <option value="">Todos los estados</option>
                    <option v-for="c in conditions" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
            </template>

            <template #cell-name="{ row }">
                <div class="flex items-center gap-2">
                    <img v-if="row.photo_url" :src="row.photo_url" class="h-8 w-8 rounded object-cover" alt="" />
                    <span class="font-medium text-slate-800">{{ row.name }}</span>
                </div>
            </template>

            <template #cell-available_quantity="{ row }">
                <span :class="row.available_quantity === 0 ? 'font-semibold text-red-600' : ''">
                    {{ row.available_quantity }} / {{ row.quantity }}
                </span>
                <span v-if="row.outstanding_checkouts_count" class="ml-1 text-xs text-slate-400">
                    ({{ row.outstanding_checkouts_count }} fuera)
                </span>
            </template>

            <template #cell-condition_label="{ row }">
                <BadgeEstado :label="row.condition_label" :color="row.condition_color" />
            </template>

            <template #actions="{ row }">
                <div class="flex flex-wrap justify-end gap-2">
                    <button
                        v-if="can.reserve"
                        type="button"
                        class="rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                        @click="openReserve(row)"
                    >
                        Reservar
                    </button>
                    <Link
                        v-if="can.manage"
                        :href="route('inventory.edit', row.id)"
                        class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Editar
                    </Link>
                    <ConfirmButton
                        v-if="can.manage"
                        title="¿Eliminar este ítem?"
                        message="Se eliminará del inventario junto con su historial de reservas."
                        confirm-label="Eliminar"
                        @confirm="destroyItem(row.id)"
                    >
                        <span class="rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-100">
                            Eliminar
                        </span>
                    </ConfirmButton>
                </div>
            </template>

            <template #empty>No hay ítems en el inventario.</template>
        </DataTable>

        <!-- Modal de reserva/préstamo (1-2 clics) -->
        <Modal :show="!!reserveTarget" title="Reservar / prestar material" max-width="md" @close="closeReserve">
            <form v-if="reserveTarget" class="space-y-4" @submit.prevent="submitReserve">
                <p class="text-sm text-slate-600">
                    <strong>{{ reserveTarget.name }}</strong> — disponible: {{ reserveTarget.available_quantity }} / {{ reserveTarget.quantity }}
                </p>

                <div class="flex gap-4 text-sm">
                    <label class="flex items-center gap-1">
                        <input type="radio" value="event" v-model="reserveMode" /> Para un evento
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="radio" value="member" v-model="reserveMode" /> Para un responsable
                    </label>
                </div>

                <div v-if="reserveMode === 'event'">
                    <label class="block text-sm font-medium text-slate-700">Evento</label>
                    <select v-model="reserveForm.event_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm" required>
                        <option :value="null" disabled>Selecciona un evento…</option>
                        <option v-for="e in events" :key="e.id" :value="e.id">{{ e.title }} ({{ e.start_at }})</option>
                    </select>
                    <p v-if="reserveForm.errors.event_id" class="mt-1 text-xs text-red-600">{{ reserveForm.errors.event_id }}</p>
                </div>
                <div v-else>
                    <label class="block text-sm font-medium text-slate-700">Responsable</label>
                    <select v-model="reserveForm.member_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm" required>
                        <option :value="null" disabled>Selecciona un responsable…</option>
                        <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Cantidad</label>
                        <input
                            type="number"
                            min="1"
                            :max="reserveTarget.available_quantity"
                            v-model.number="reserveForm.quantity"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm"
                        />
                        <p v-if="reserveForm.errors.quantity" class="mt-1 text-xs text-red-600">{{ reserveForm.errors.quantity }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Salida</label>
                        <input type="date" v-model="reserveForm.checked_out_at" class="mt-1 block w-full rounded-md border-slate-300 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Retorno previsto</label>
                    <input type="date" v-model="reserveForm.expected_return_at" class="mt-1 block w-full rounded-md border-slate-300 text-sm" />
                </div>
            </form>

            <template #footer>
                <button type="button" class="rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100" @click="closeReserve">
                    Cancelar
                </button>
                <button
                    type="button"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                    :disabled="reserveForm.processing"
                    @click="submitReserve"
                >
                    Reservar
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
