<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
    events: { type: Array, default: () => [] },
    icalUrl: { type: String, default: '' },
})

const toast = useToast()

const grouped = computed(() => {
    const now = new Date()
    const fmt = new Intl.DateTimeFormat('es-ES', { weekday: 'long', day: 'numeric', month: 'long' })
    const time = new Intl.DateTimeFormat('es-ES', { hour: '2-digit', minute: '2-digit' })
    const upcoming = []
    const past = []
    for (const e of props.events) {
        const start = new Date(e.start_at)
        const row = { ...e, day: fmt.format(start), time: time.format(start) }
        ;(start >= now ? upcoming : past).push(row)
    }
    return { upcoming, past: past.reverse() }
})

function copyIcal() {
    navigator.clipboard?.writeText(props.icalUrl).then(
        () => toast.success('Enlace copiado. Pégalo en tu app de calendario.'),
        () => toast.error('No se pudo copiar el enlace.'),
    )
}

function regenerate() {
    useForm({}).post(route('portal.calendar.ical.regenerate'), {
        preserveScroll: true,
        onSuccess: () => toast.success('Enlace regenerado.'),
    })
}
</script>

<template>
    <Head title="Calendario" />
    <PortalLayout>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Calendario</h1>
        <p class="mt-1 text-slate-500">Reuniones y salidas de las ramas de tus scouts.</p>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm font-semibold text-slate-700">Suscríbete desde tu móvil</p>
            <p class="mt-1 text-sm text-slate-500">
                Añade este enlace en Google Calendar o Apple Calendario y las actividades se actualizarán solas.
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <code class="max-w-full truncate rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-600">{{ icalUrl }}</code>
                <button type="button" class="rounded-md border border-brand-600 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50" @click="copyIcal">
                    Copiar
                </button>
                <button type="button" class="text-xs text-slate-400 hover:text-red-600" @click="regenerate">
                    Regenerar
                </button>
            </div>
        </div>

        <section class="mt-8">
            <h2 class="text-lg font-bold text-slate-800">Próximas</h2>
            <div v-if="grouped.upcoming.length" class="mt-3 space-y-2">
                <article v-for="e in grouped.upcoming" :key="e.id" class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-baseline justify-between gap-3">
                        <h3 class="font-semibold text-slate-800">{{ e.title }}</h3>
                        <span class="shrink-0 text-sm font-semibold text-brand-700">{{ e.day }} · {{ e.time }}</span>
                    </div>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ e.type }}<span v-if="e.location"> · {{ e.location }}</span>
                    </p>
                    <p v-if="e.description" class="mt-2 text-sm text-slate-600">{{ e.description }}</p>
                </article>
            </div>
            <p v-else class="mt-3 rounded-xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-400">
                No hay actividades programadas.
            </p>
        </section>

        <section v-if="grouped.past.length" class="mt-8">
            <h2 class="text-lg font-bold text-slate-800">Pasadas</h2>
            <ul class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white text-sm">
                <li v-for="e in grouped.past" :key="e.id" class="flex items-center justify-between px-4 py-3">
                    <span class="text-slate-600">{{ e.title }}</span>
                    <span class="text-slate-400">{{ e.day }}</span>
                </li>
            </ul>
        </section>
    </PortalLayout>
</template>
