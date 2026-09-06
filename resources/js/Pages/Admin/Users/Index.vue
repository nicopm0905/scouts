<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/Shared/PageHeader.vue'
import Modal from '@/Components/Shared/Modal.vue'
import FormField from '@/Components/Shared/FormField.vue'
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    users: { type: Array, default: () => [] },
    roleOptions: { type: Array, default: () => [] },
    branchOptions: { type: Array, default: () => [] },
    inviteUrl: { type: String, default: null },
})

const toast = useToast()
const search = ref('')
const showForm = ref(false)
const editing = ref(null)

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return props.users
    return props.users.filter((u) => `${u.name} ${u.email} ${u.role}`.toLowerCase().includes(q))
})

const roleLabel = (v) => props.roleOptions.find((r) => r.value === v)?.label ?? v
const branchLabel = (v) => props.branchOptions.find((b) => b.value === v)?.label ?? v

const form = useForm({
    name: '',
    email: '',
    role: 'responsable',
    active: true,
    branches: [],
})

function openCreate() {
    editing.value = null
    form.reset()
    form.clearErrors()
    showForm.value = true
}

function openEdit(u) {
    editing.value = u
    form.clearErrors()
    form.name = u.name
    form.email = u.email
    form.role = u.role
    form.active = u.active
    form.branches = [...(u.branches ?? [])]
    showForm.value = true
}

function toggleBranch(value) {
    const i = form.branches.indexOf(value)
    if (i === -1) form.branches.push(value)
    else form.branches.splice(i, 1)
}

function save() {
    if (editing.value) {
        form.patch(route('users.update', editing.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showForm.value = false
                toast.success('Cuenta actualizada.')
            },
        })
    } else {
        form.post(route('users.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showForm.value = false
                toast.success('Cuenta creada.')
            },
        })
    }
}

function resendInvite(u) {
    router.post(route('users.resend-invite', u.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Enlace nuevo generado.'),
    })
}

function destroy(u) {
    router.delete(route('users.destroy', u.id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Cuenta eliminada.'),
    })
}

function copyInvite() {
    navigator.clipboard?.writeText(props.inviteUrl).then(
        () => toast.success('Enlace copiado.'),
        () => toast.error('No se pudo copiar.'),
    )
}
</script>

<template>
    <Head title="Usuarios" />
    <AppLayout>
        <PageHeader title="Usuarios" subtitle="Cuentas de acceso a la plataforma" icon="users">
            <template #actions>
                <button
                    type="button"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
                    @click="openCreate"
                >
                    Nuevo usuario
                </button>
            </template>
        </PageHeader>

        <div
            v-if="inviteUrl"
            class="mb-4 rounded-md border border-brand-200 bg-brand-50 p-3 text-sm"
        >
            <p class="font-semibold text-brand-800">Enlace para crear la contraseña</p>
            <p class="mt-0.5 text-brand-700">Copia este enlace y entrégaselo a la persona (caduca en 1&nbsp;hora).</p>
            <div class="mt-2 flex items-center gap-2">
                <code class="max-w-full truncate rounded bg-white px-2 py-1 text-xs text-slate-600">{{ inviteUrl }}</code>
                <button type="button" class="shrink-0 rounded border border-brand-600 px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-white" @click="copyInvite">
                    Copiar
                </button>
            </div>
        </div>

        <input
            v-model="search"
            type="search"
            placeholder="Buscar por nombre, correo o rol…"
            class="mb-4 w-full max-w-sm rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
        />

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Rol</th>
                        <th class="px-4 py-3">Ramas</th>
                        <th class="px-4 py-3">Último acceso</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="u in filtered" :key="u.id" class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <span class="font-semibold text-slate-800">{{ u.name }}</span>
                            <span class="block text-xs text-slate-400">{{ u.email }}</span>
                        </td>
                        <td class="px-4 py-3">
                            {{ roleLabel(u.role) }}
                            <span v-if="u.role === 'familia' && u.families_count" class="block text-xs text-slate-400">
                                {{ u.families_count }} familia(s)
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">
                            <span v-if="u.branches.length">{{ u.branches.map(branchLabel).join(', ') }}</span>
                            <span v-else class="text-slate-300">—</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ u.last_login_at ?? 'Nunca' }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                :class="u.active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                            >
                                {{ u.active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3 text-xs">
                                <button type="button" class="text-brand-700 hover:underline" @click="openEdit(u)">Editar</button>
                                <button type="button" class="text-slate-500 hover:underline" @click="resendInvite(u)">Nuevo enlace</button>
                                <ConfirmButton
                                    v-if="!u.protected"
                                    title="Eliminar cuenta"
                                    :message="`¿Eliminar la cuenta de ${u.name}? Esta acción no se puede deshacer.`"
                                    confirm-label="Eliminar"
                                    @confirm="destroy(u)"
                                >
                                    <span class="text-red-600 hover:underline">Eliminar</span>
                                </ConfirmButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!filtered.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">Sin resultados.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Modal :show="showForm" :title="editing ? 'Editar cuenta' : 'Nueva cuenta'" @close="showForm = false">
            <form class="space-y-4 p-6" @submit.prevent="save">
                <FormField v-model="form.name" label="Nombre" required :error="form.errors.name" />
                <FormField
                    v-if="!editing"
                    v-model="form.email"
                    type="email"
                    label="Correo electrónico"
                    required
                    :error="form.errors.email"
                    hint="Se generará un enlace para que cree su contraseña."
                />
                <FormField
                    v-model="form.role"
                    type="select"
                    label="Rol"
                    :options="roleOptions"
                    :error="form.errors.role"
                />

                <div v-if="form.role === 'responsable'">
                    <label class="block text-sm font-medium text-slate-700">Ramas que gestiona</label>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="b in branchOptions"
                            :key="b.value"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs font-semibold"
                            :class="form.branches.includes(b.value)
                                ? 'border-brand-600 bg-brand-50 text-brand-700'
                                : 'border-slate-300 text-slate-500'"
                            @click="toggleBranch(b.value)"
                        >
                            {{ b.label }}
                        </button>
                    </div>
                    <p v-if="form.errors.branches" class="mt-1 text-xs text-red-600">{{ form.errors.branches }}</p>
                </div>

                <label v-if="editing" class="flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="form.active" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                    Cuenta activa
                </label>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="rounded-md px-4 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100" @click="showForm = false">
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ editing ? 'Guardar' : 'Crear e invitar' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
