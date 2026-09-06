<script setup>
/**
 * Patrón decorativo de respaldo para bloques sin fotografía: curvas de nivel
 * topográficas + flor de lis discreta. Hereda el color con `currentColor`, así
 * que basta con aplicarle una clase de texto (p. ej. text-white/10).
 */
import { getCurrentInstance } from 'vue'

// Id único por instancia: el mismo patrón se usa varias veces en la página.
const patternId = `scout-topo-${getCurrentInstance()?.uid ?? Math.random().toString(36).slice(2)}`
</script>

<template>
    <svg
        viewBox="0 0 400 300"
        preserveAspectRatio="xMidYMid slice"
        fill="none"
        aria-hidden="true"
        focusable="false"
    >
        <defs>
            <pattern :id="patternId" width="200" height="150" patternUnits="userSpaceOnUse">
                <g stroke="currentColor" stroke-width="1.2" fill="none">
                    <path d="M-20 30c40-28 80-28 120 0s80 28 120 0" />
                    <path d="M-20 55c40-28 80-28 120 0s80 28 120 0" />
                    <path d="M-20 80c40-28 80-28 120 0s80 28 120 0" />
                    <path d="M-20 105c40-28 80-28 120 0s80 28 120 0" />
                    <path d="M-20 130c40-28 80-28 120 0s80 28 120 0" />
                    <path d="M-20 155c40-28 80-28 120 0s80 28 120 0" />
                </g>
            </pattern>
        </defs>

        <rect width="400" height="300" :fill="`url(#${patternId})`" />

        <!-- Flor de lis simplificada -->
        <g transform="translate(330 60) scale(1.6)" fill="currentColor" opacity="0.55">
            <path d="M20 2c2.6 4.8 3.9 9 3.9 12.6 0 2.6-.7 4.9-2 6.9h4.4c1.9-4.2 4.6-6.6 8-7.2-1.1 4.4-.6 8.4 1.6 12 1.5 2.5 1.3 4.9-.6 7.2-1.7 2-4 3-6.7 3-3.2 0-5.8-1.4-7.7-4.1v6.2l3.6 2.6-3.6 2.6-1 4.2-1-4.2-3.6-2.6 3.6-2.6v-6.2c-1.9 2.7-4.5 4.1-7.7 4.1-2.7 0-5-1-6.7-3-1.9-2.3-2.1-4.7-.6-7.2 2.2-3.6 2.7-7.6 1.6-12 3.4.6 6.1 3 8 7.2h4.4c-1.3-2-2-4.3-2-6.9C16.1 11 17.4 6.8 20 2Z" />
        </g>
    </svg>
</template>
