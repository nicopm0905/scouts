<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    canLogin: { type: Boolean, default: true },
    appName: { type: String, default: 'Grupo Scout' },
})

const demoUsers = [
    { role: 'Coordinación', email: 'admin@grupo.test', desc: 'Acceso total a gestión, actas y configuración' },
    { role: 'Secretaría', email: 'secretaria@grupo.test', desc: 'Miembros, familias, censo y documentación' },
    { role: 'Tesorería', email: 'tesoreria@grupo.test', desc: 'Cuotas, cobros, facturación y presupuestos' },
    { role: 'Responsable (Lobatos)', email: 'lobatos@grupo.test', desc: 'Gestión de la manada, actividades y asistencia' },
]
</script>

<template>
    <Head title="Plataforma de Gestión Scout" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 text-slate-100 flex flex-col justify-between selection:bg-emerald-500 selection:text-white">
        <!-- Header -->
        <header class="mx-auto flex w-full max-w-7xl items-center justify-between p-6">
            <div class="flex items-center gap-3">
                <img src="/images/logosj.png" alt="Scouts de San José" class="h-12 w-auto object-contain bg-white/90 rounded-xl p-1 shadow-md" />
                <div>
                    <span class="text-xl font-black tracking-tight text-white">Scouts de San José</span>
                    <p class="text-xs font-bold text-red-400">Plataforma de Gestión</p>
                </div>
            </div>

            <nav class="flex items-center gap-3">
                <Link
                    v-if="$page.props.auth?.user"
                    :href="route('dashboard')"
                    class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-bold text-slate-950 transition hover:bg-emerald-400 shadow-md shadow-emerald-500/20"
                >
                    Ir al Panel de Control →
                </Link>
                <Link
                    v-else
                    :href="route('login')"
                    class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-bold text-slate-950 transition hover:bg-emerald-400 shadow-md shadow-emerald-500/20"
                >
                    Iniciar Sesión
                </Link>
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="mx-auto w-full max-w-7xl px-6 py-12">
            <div class="text-center max-w-3xl mx-auto space-y-6">
                <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-4 py-1.5 text-xs font-semibold text-emerald-300 backdrop-blur-md">
                    <span>⚜</span>
                    <span>Gestión Integral del Grupo Scout</span>
                </div>

                <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl leading-tight">
                    Tesorería, Secretaría y Actividades en un solo lugar
                </h1>

                <p class="text-base sm:text-lg text-slate-300 font-normal leading-relaxed">
                    Diseñado para voluntarios scouts: control de cuotas por familia, documentos enlazados en Google Drive, ratios legales para campamentos y gestión por ramas (Castores, Lobatos, Ranger, Pioneros, Rutas y Responsables).
                </p>

                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        :href="route('login')"
                        class="w-full sm:w-auto rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 px-8 py-3.5 text-base font-bold text-slate-950 transition hover:from-emerald-400 hover:to-teal-300 shadow-lg shadow-emerald-500/25"
                    >
                        Entrar a la Plataforma
                    </Link>
                </div>
            </div>

            <!-- Demostración / Usuarios de Acceso Rápido -->
            <div class="mt-16 rounded-3xl border border-slate-800 bg-slate-900/60 p-6 sm:p-8 backdrop-blur-xl shadow-2xl">
                <h2 class="text-xs font-extrabold uppercase tracking-widest text-emerald-400 mb-6 text-center">
                    Usuarios de Prueba (Contraseña: <code class="text-white bg-slate-800 px-2 py-0.5 rounded">password</code>)
                </h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="u in demoUsers" :key="u.email" class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 transition hover:border-slate-700">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wide">{{ u.role }}</span>
                            <span class="text-xs text-slate-500">Demo</span>
                        </div>
                        <p class="mt-2 text-sm font-bold text-white font-mono truncate">{{ u.email }}</p>
                        <p class="mt-1 text-xs text-slate-400 leading-snug">{{ u.desc }}</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="mx-auto w-full max-w-7xl border-t border-slate-800/60 px-6 py-6 text-center text-xs text-slate-500">
            Plataforma de Gestión Grupo Scout · Construida de forma libre para el voluntariado scout.
        </footer>
    </div>
</template>
