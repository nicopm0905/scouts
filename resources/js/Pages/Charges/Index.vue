<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import DataTable from '@/Components/Shared/DataTable.vue'
import Modal from '@/Components/Shared/Modal.vue'
import FormField from '@/Components/Shared/FormField.vue'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'

const props = defineProps({
    charges: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    chargeTypes: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    members: { type: Array, default: () => [] },
})

const toast = useToast()
const { can } = useAuth()
const showCreate = ref(false)
const memberFilter = ref('')

const eur = (n) => Number(n ?? 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'

const kpis = computed(() => [
    { label: 'Recaudado', value: eur(props.summary.collected), tone: 'text-emerald-600', sub: `${props.summary.collection_rate ?? 0}% del total` },
    { label: 'Pendiente', value: eur(props.summary.pending), tone: 'text-brand-600', sub: `${props.summary.pending_count ?? 0} pagos sin cerrar` },
    { label: 'Total previsto', value: eur(props.summary.expected), tone: 'text-ink-800', sub: `${props.summary.charges_count ?? 0} cobros` },
])

const columns = [
    { key: 'title', label: 'Cobro', sortable: true },
    { key: 'type_label', label: 'Tipo', sortable: true },
    { key: 'total_expected', label: 'Previsto', sortable: true },
    { key: 'due_date', label: 'Vencimiento', sortable: true },
    { key: 'progress', label: 'Recaudación' },
]

const rows = computed(() =>
    props.charges.map((c) => ({
        ...c,
        pct: c.total_expected > 0 ? Math.round((c.total_collected / c.total_expected) * 100) : 0,
    }))
)

const form = useForm({
    title: '', description: '', amount: '', due_date: '',
    type: props.chargeTypes[0]?.value ?? '',
    sibling_discount_applies: false,
    target_branches: [], member_ids: [],
})

const filteredMembers = computed(() => {
    const q = memberFilter.value.trim().toLowerCase()
    return q ? props.members.filter((m) => m.name.toLowerCase().includes(q)) : props.members
})

function toggleBranch(v) {
    const i = form.target_branches.indexOf(v)
    i === -1 ? form.target_branches.push(v) : form.target_branches.splice(i, 1)
}
function toggleMember(id) {
    const i = form.member_ids.indexOf(id)
    i === -1 ? form.member_ids.push(id) : form.member_ids.splice(i, 1)
}
function submit() {
    form.post(route('charges.store'), {
        onSuccess: () => { showCreate.value = false; form.reset(); toast.success('Cobro creado.') },
    })
}
</script>

<template>
    <Head title="Cobros" />
    <AppLayout>
        <PageHeader title="Cobros" subtitle="Cuotas, salidas y campamentos repartidos por rama o por miembro.">
            <template #actions>
                <Link :href="route('finance.report')" class="btn-secondary btn-sm">📊 Informe</Link>
                <button v-if="can('charges.manage')" class="btn-primary btn-sm" @click="showCreate = true">+ Nuevo cobro</button>
            </template>
        </PageHeader>

        <!-- KPIs -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div v-for="k in kpis" :key="k.label" class="card p-4">
                <p class="section-title">{{ k.label }}</p>
                <p class="mt-1 text-2xl font-bold" :class="k.tone">{{ k.value }}</p>
                <p class="mt-0.5 text-xs text-ink-400">{{ k.sub }}</p>
            </div>
        </div>

        <!-- Barra de recaudación global -->
        <div v-if="summary.expected > 0" class="mb-6 card p-4">
            <div class="mb-1.5 flex items-center justify-between text-sm">
                <span class="font-medium text-ink-700">Recaudación global</span>
                <span class="text-ink-500">{{ eur(summary.collected) }} de {{ eur(summary.expected) }}</span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-ink-100">
                <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: (summary.collection_rate ?? 0) + '%' }" />
            </div>
        </div>

        <DataTable :columns="columns" :rows="rows" persist-key="charges" placeholder="Buscar cobro…">
            <template #cell-total_expected="{ value }">{{ eur(value) }}</template>
            <template #cell-due_date="{ value }">{{ value ?? '—' }}</template>
            <template #cell-progress="{ row }">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-24 overflow-hidden rounded-full bg-ink-100">
                        <div class="h-full rounded-full" :class="row.pct === 100 ? 'bg-emerald-500' : 'bg-brand-500'" :style="{ width: row.pct + '%' }" />
                    </div>
                    <span class="whitespace-nowrap text-xs text-ink-500">{{ row.paid_count }}/{{ row.assignments_count }}</span>
                </div>
            </template>
            <template #actions="{ row }">
                <Link :href="route('charges.show', row.id)" class="text-sm font-medium text-brand-700 hover:underline">Ver control</Link>
            </template>
        </DataTable>

        <Modal :show="showCreate" title="Nuevo cobro" max-width="xl" @close="showCreate = false">
            <form class="space-y-4" @submit.prevent="submit">
                <FormField v-model="form.title" label="Título" required :error="form.errors.title" />
                <FormField v-model="form.description" type="textarea" label="Descripción" :error="form.errors.description" />
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField v-model="form.amount" type="number" label="Importe base (€)" required :error="form.errors.amount" />
                    <FormField v-model="form.due_date" type="date" label="Fecha límite" :error="form.errors.due_date" />
                </div>
                <FormField v-model="form.type" type="select" label="Tipo" :options="chargeTypes" :error="form.errors.type" />
                <FormField v-model="form.sibling_discount_applies" type="checkbox"
                    placeholder="Aplicar descuento por hermanos (a partir del 2º hermano)" />

                <div>
                    <label class="label">Ramas destinatarias</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button v-for="b in branches" :key="b.value" type="button"
                            class="rounded-full border px-3 py-1 text-sm transition"
                            :class="form.target_branches.includes(b.value) ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-ink-300 text-ink-600 hover:bg-ink-50'"
                            @click="toggleBranch(b.value)">{{ b.label }}</button>
                    </div>
                </div>

                <div>
                    <label class="label">Miembros individuales (opcional)</label>
                    <input v-model="memberFilter" type="search" placeholder="Buscar miembro…" class="input mt-1" />
                    <div class="mt-2 max-h-40 overflow-y-auto rounded-lg border border-ink-200 p-2">
                        <label v-for="m in filteredMembers" :key="m.id" class="flex items-center gap-2 py-1 text-sm text-ink-700">
                            <input type="checkbox" class="rounded border-ink-300 text-brand-600 focus:ring-brand-500"
                                :checked="form.member_ids.includes(m.id)" @change="toggleMember(m.id)" />
                            {{ m.name }}
                        </label>
                    </div>
                </div>
                <p v-if="form.errors.member_ids" class="text-xs text-red-600">{{ form.errors.member_ids }}</p>
            </form>

            <template #footer>
                <button class="btn-ghost" @click="showCreate = false">Cancelar</button>
                <button class="btn-primary" :disabled="form.processing" @click="submit">Crear cobro</button>
            </template>
        </Modal>
    </AppLayout>
</template>
