<script setup>
/**
 * Iconos de línea para los pilares de la portada pública.
 * El nombre viene de config/group.php ('pillars.*.icon').
 */
defineProps({
    name: { type: String, default: 'compass' },
})
</script>

<template>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <!-- Brújula: educación en valores -->
        <template v-if="name === 'compass'">
            <circle cx="12" cy="12" r="9" />
            <path d="m15.5 8.5-2 5.2-5.2 2 2-5.2 5.2-2Z" />
        </template>

        <!-- Tienda de campaña: naturaleza y aventura -->
        <template v-else-if="name === 'tent'">
            <path d="m12 4 8.5 15H3.5L12 4Z" />
            <path d="M12 4v15" />
            <path d="m12 12 4 7M12 12l-4 7" />
        </template>

        <!-- Corazón: servicio -->
        <template v-else-if="name === 'heart'">
            <path d="M12 20s-7-4.4-7-9.2A4 4 0 0 1 12 8a4 4 0 0 1 7 2.8C19 15.6 12 20 12 20Z" />
        </template>

        <!-- Personas: comunidad -->
        <template v-else-if="name === 'users'">
            <circle cx="9" cy="8" r="3.2" />
            <path d="M3.5 19a5.5 5.5 0 0 1 11 0" />
            <path d="M16 5.6a3.2 3.2 0 0 1 0 4.9M17.5 14.2A5.5 5.5 0 0 1 20.5 19" />
        </template>

        <!-- Respaldo: estrella -->
        <template v-else>
            <path d="m12 4 2.4 5 5.6.8-4 3.9 1 5.5-5-2.7-5 2.7 1-5.5-4-3.9 5.6-.8L12 4Z" />
        </template>
    </svg>
</template>
