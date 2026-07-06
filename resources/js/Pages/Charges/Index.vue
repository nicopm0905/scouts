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
    chargeTypes: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    members: { type: Array, default: () => [] },
})

const toast = useToast()
const { can } = useAuth()
const showCreate = ref(false)
const memberFilter = ref('')

const columns = [
    { key: 'title', label: 'Cobro', sortable: true },
    { key: 'type_label', label: 'Tipo', sortable: true },
    { key: 'amount', label: 'Importe base', sortable: true },
    { key: 'due_date', label: 'Vencimiento', sortable: true },
    { key: 'progress', label: 'Pagados' },
]

const rows = computed(() =>
    props.charges.map((c) => ({ ...c, progress: `${c.paid_count}/${c.assignments_count}` }))
)

const form = useForm({
    title: '',
    description: '',
    amount: '',
    due_date: '',
    type: props.chargeTypes[0]?.value ?? '',
    sibling_discount_applies: false,
    target_branches: [],
    member_ids: [],
})

const filteredMembers = computed(() => {
    const q = memberFilter.value.trim().toLowerCase()
    if (!q) return props.members
    return props.members.filter((m) => m.name.toLowerCase().includes(q))
})

function toggleBranch(value) {
    const i = form.target_branches.indexOf(value)
    if (i === -1) form.target_branches.push(value)
    else form.target_branches.splice(i, 1)
}

function toggleMember(id) {
    const i = form.member_ids.indexOf(id)
    if (i === -1) form.member_ids.push(id)
    else form.member_ids.splice(i, 1)
}

function submit() {
    form.post(route('charges.store'), {
        onSuccess: () => {
            showCreate.value = false
            form.reset()
            toast.success('Cobro creado.')
        },
    })
}
</script>

<template>
    <Head title="Cobros" />
    <AppLayout>
        <PageHeader title="Cobros" subtitle="Cuotas, salidas y campamentos repartidos por rama o por miembro.">
            <template #actions>
                <button
                    v-if="can('charges.manage')"
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                    @click="showCreate = true"
                >
                    + Nuevo cobro
                </button>
            </template>
        </PageHeader>

        <DataTable :columns="columns" :rows="rows" persist-key="charges" placeholder="Buscar cobro…">
            <template #cell-amount="{ value }">{{ Number(value).toFixed(2) }} €</template>
            <template #cell-due_date="{ value }">{{ value ?? 'Sin fecha' }}</template>
            <template #actions="{ row }">
                <Link :href="route('charges.show', row.id)" class="text-emerald-700 hover:underline">Ver control</Link>
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

                <FormField
                    v-model="form.type"
                    type="select"
                    label="Tipo"
                    :options="chargeTypes"
                    :error="form.errors.type"
                />

                <FormField
                    v-model="form.sibling_discount_applies"
                    type="checkbox"
                    placeholder="Aplicar descuento por hermanos (a partir del 2º hermano)"
                />

                <div>
                    <label class="block text-sm font-medium text-slate-700">Ramas destinatarias</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="b in branches"
                            :key="b.value"
                            type="button"
                            class="rounded-full border px-3 py-1 text-sm"
                            :class="form.target_branches.includes(b.value)
                                ? 'border-emerald-600 bg-emerald-50 text-emerald-700'
                                : 'border-slate-300 text-slate-600'"
                            @click="toggleBranch(b.value)"
                        >
                            {{ b.label }}
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Miembros individuales (opcional)</label>
                    <input
                        v-model="memberFilter"
                        type="search"
                        placeholder="Buscar miembro…"
                        class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm"
                    />
                    <div class="mt-2 max-h-40 overflow-y-auto rounded-md border border-slate-200 p-2">
                        <label v-for="m in filteredMembers" :key="m.id" class="flex items-center gap-2 py-1 text-sm">
                            <input
                                type="checkbox"
                                :checked="form.member_ids.includes(m.id)"
                                @change="toggleMember(m.id)"
                            />
                            {{ m.name }}
                        </label>
                    </div>
                </div>

                <p v-if="form.errors.member_ids" class="text-xs text-red-600">{{ form.errors.member_ids }}</p>
            </form>

            <template #footer>
                <button class="rounded-md px-4 py-2 text-sm text-slate-600 hover:bg-slate-100" @click="showCreate = false">
                    Cancelar
                </button>
                <button
                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                    :disabled="form.processing"
                    @click="submit"
                >
                    Crear cobro
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
