<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    child: { type: Object, required: true },
    attendance: { type: Object, default: () => ({ present: 0, total: 0 }) },
    guardians: { type: Array, default: () => [] },
    editable: { type: Object, default: () => ({}) },
    pendingReview: { type: Object, default: null },
})

const toast = useToast()

const rate = computed(() =>
    props.attendance.total > 0 ? Math.round((props.attendance.present / props.attendance.total) * 100) : null,
)

const form = useForm({
    member: { phone: props.editable.phone ?? '', email: props.editable.email ?? '' },
    health: {
        allergies: props.editable.allergies ?? '',
        intolerances: props.editable.intolerances ?? '',
        medication: props.editable.medication ?? '',
        observations: props.editable.observations ?? '',
    },
    note: '',
})

function submit() {
    form.post(`/portal/scouts/${props.child.id}/revision`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Cambios enviados a secretaría.'),
    })
}
</script>

<template>
    <Head :title="child.name" />
    <PortalLayout>
        <Link href="/portal/dashboard" class="text-sm text-slate-400 hover:text-slate-700">&larr; Volver</Link>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">{{ child.name }}</h1>
        <p class="mt-1 text-slate-500">{{ child.branch }}</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">Rama</p>
                <p class="mt-1 text-lg font-bold text-slate-800">{{ child.branch }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">Alta</p>
                <p class="mt-1 text-lg font-bold text-slate-800">{{ child.joined_at ?? '—' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">Asistencia (curso)</p>
                <p class="mt-1 text-lg font-bold text-slate-800">
                    <span v-if="rate !== null">{{ rate }}%</span>
                    <span v-else class="text-slate-400">Sin datos</span>
                </p>
                <p v-if="rate !== null" class="text-xs text-slate-400">{{ attendance.present }} de {{ attendance.total }} sesiones</p>
            </div>
        </div>

        <section v-if="guardians.length" class="mt-8">
            <h2 class="text-lg font-bold text-slate-800">Contacto familiar registrado</h2>
            <ul class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white text-sm">
                <li v-for="(g, i) in guardians" :key="i" class="px-4 py-3">
                    <span class="font-semibold text-slate-700">{{ g.family }}</span>
                    <span class="block text-slate-500">
                        <span v-if="g.phone">{{ g.phone }}</span>
                        <span v-if="g.phone && g.email"> · </span>
                        <span v-if="g.email">{{ g.email }}</span>
                    </span>
                </li>
            </ul>
            <p class="mt-2 text-xs text-slate-400">
                ¿Los datos de contacto de la familia no son correctos? Escríbelo abajo en "Otra información".
            </p>
        </section>

        <!-- Revisión de datos: la familia propone cambios y secretaría los aplica -->
        <section class="mt-8">
            <h2 class="text-lg font-bold text-slate-800">Revisar datos de {{ child.name }}</h2>

            <div v-if="pendingReview" class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                Enviaste cambios el {{ pendingReview.submitted_at }}. Secretaría los revisará y te avisará.
                Puedes volver a enviar el formulario para corregir lo que propusiste.
            </div>

            <form class="mt-4 space-y-5 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="submit">
                <p class="text-xs text-slate-500">
                    Cambia solo lo que esté desactualizado. Nada se guarda hasta que secretaría lo aprueba.
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">
                        <span class="font-semibold text-slate-700">Teléfono del scout</span>
                        <input v-model="form.member.phone" type="text" class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
                    </label>
                    <label class="block text-sm">
                        <span class="font-semibold text-slate-700">Email del scout</span>
                        <input v-model="form.member.email" type="email" class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
                        <span v-if="form.errors['member.email']" class="text-xs text-rose-600">{{ form.errors['member.email'] }}</span>
                    </label>
                </div>

                <div class="space-y-4 border-t border-slate-100 pt-4">
                    <p class="text-sm font-bold text-slate-700">Salud</p>
                    <label class="block text-sm">
                        <span class="font-semibold text-slate-700">Alergias</span>
                        <textarea v-model="form.health.allergies" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm">
                            <span class="font-semibold text-slate-700">Intolerancias</span>
                            <textarea v-model="form.health.intolerances" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
                        </label>
                        <label class="block text-sm">
                            <span class="font-semibold text-slate-700">Medicación</span>
                            <textarea v-model="form.health.medication" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
                        </label>
                    </div>
                    <label class="block text-sm">
                        <span class="font-semibold text-slate-700">Observaciones médicas</span>
                        <textarea v-model="form.health.observations" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
                    </label>
                </div>

                <label class="block border-t border-slate-100 pt-4 text-sm">
                    <span class="font-semibold text-slate-700">Otra información para secretaría</span>
                    <textarea v-model="form.note" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                        placeholder="Ej.: el teléfono de contacto de la familia ha cambiado a…" />
                </label>

                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing"
                        class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-50">
                        Enviar cambios a secretaría
                    </button>
                </div>
            </form>
        </section>
    </PortalLayout>
</template>
