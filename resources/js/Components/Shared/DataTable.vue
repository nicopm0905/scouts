<script setup>
import { computed, ref, watch } from 'vue'
import SearchInput from '@/Components/Shared/SearchInput.vue'
import EmptyState from '@/Components/Shared/EmptyState.vue'
import DashIcon from '@/Components/Shared/DashIcon.vue'

/**
 * Tabla reutilizable con búsqueda instantánea (cliente), orden y filtros persistentes.
 * En móvil, cada fila se convierte en una tarjeta legible en vez de una tabla apretada.
 *
 * Props:
 *  - columns: [{ key, label, sortable?, class?, align? ('right'), hideOnMobile? }]
 *  - rows: array de objetos
 *  - searchKeys: claves sobre las que buscar (por defecto, todas las de columns)
 *  - persistKey: guarda el texto de búsqueda en la URL (?q=) y en localStorage
 *
 * Slots:
 *  - cell-<key>: contenido por celda (recibe { row, value })
 *  - actions: acciones por fila (recibe { row })
 *  - filters: controles extra junto al buscador
 *  - empty: estado vacío
 */
const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    searchKeys: { type: Array, default: null },
    searchable: { type: Boolean, default: true },
    persistKey: { type: String, default: null },
    placeholder: { type: String, default: 'Buscar…' },
    emptyTitle: { type: String, default: 'No hay resultados' },
    emptyDescription: { type: String, default: null },
})

const initialQuery = () => {
    if (!props.persistKey) return ''
    try {
        const url = new URL(window.location.href)
        return url.searchParams.get('q') ?? localStorage.getItem(`dt:${props.persistKey}`) ?? ''
    } catch (e) {
        return ''
    }
}

const query = ref(initialQuery())
const sortKey = ref(null)
const sortDir = ref('asc')

watch(query, (q) => {
    if (!props.persistKey) return
    try {
        localStorage.setItem(`dt:${props.persistKey}`, q)
        const url = new URL(window.location.href)
        if (q) url.searchParams.set('q', q)
        else url.searchParams.delete('q')
        window.history.replaceState({}, '', url)
    } catch (e) {
        // Modo privado: la búsqueda sigue funcionando, solo no se recuerda.
    }
})

const keysToSearch = computed(() => props.searchKeys ?? props.columns.map((c) => c.key))

const filtered = computed(() => {
    let out = props.rows
    const q = query.value.trim().toLowerCase()

    if (q) {
        out = out.filter((row) =>
            keysToSearch.value.some((k) => String(getValue(row, k) ?? '').toLowerCase().includes(q)),
        )
    }

    if (sortKey.value) {
        out = [...out].sort((a, b) => {
            const av = getValue(a, sortKey.value)
            const bv = getValue(b, sortKey.value)
            if (av === bv) return 0
            const res = av > bv ? 1 : -1
            return sortDir.value === 'asc' ? res : -res
        })
    }

    return out
})

// Columna principal: la primera. Es el título de la tarjeta en móvil.
const columnaPrincipal = computed(() => props.columns[0])
const columnasSecundarias = computed(() => props.columns.slice(1).filter((c) => !c.hideOnMobile))

function getValue(row, key) {
    return key.split('.').reduce((o, k) => (o == null ? o : o[k]), row)
}

function toggleSort(col) {
    if (!col.sortable) return
    if (sortKey.value === col.key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = col.key
        sortDir.value = 'asc'
    }
}
</script>

<template>
    <div>
        <!-- Buscador y filtros -->
        <div
            v-if="searchable || $slots.filters"
            class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
            <SearchInput v-if="searchable" v-model="query" :placeholder="placeholder" />
            <div v-if="$slots.filters" class="flex flex-wrap items-center gap-2">
                <slot name="filters" />
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
            <!-- ═══ Tabla (a partir de sm) ═══ -->
            <div class="hidden overflow-x-auto sm:block">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50/80">
                        <tr>
                            <th
                                v-for="col in columns"
                                :key="col.key"
                                scope="col"
                                class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500"
                                :class="[
                                    col.class,
                                    col.align === 'right' ? 'text-right' : '',
                                    col.sortable ? 'cursor-pointer select-none transition-colors hover:text-slate-800' : '',
                                ]"
                                @click="toggleSort(col)"
                            >
                                <span class="inline-flex items-center gap-1">
                                    {{ col.label }}
                                    <DashIcon
                                        v-if="col.sortable"
                                        name="chevron"
                                        class="h-3 w-3 transition-transform duration-200"
                                        :class="[
                                            sortKey === col.key ? 'text-brand-600' : 'text-slate-300',
                                            sortKey === col.key && sortDir === 'asc' ? '-rotate-90' : 'rotate-90',
                                        ]"
                                    />
                                </span>
                            </th>
                            <th v-if="$slots.actions" scope="col" class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="(row, i) in filtered"
                            :key="row.id ?? i"
                            class="transition-colors duration-150 hover:bg-slate-50"
                        >
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="px-4 py-3 align-middle text-slate-700"
                                :class="[col.class, col.align === 'right' ? 'text-right' : '']"
                            >
                                <slot :name="`cell-${col.key}`" :row="row" :value="getValue(row, col.key)">
                                    {{ getValue(row, col.key) }}
                                </slot>
                            </td>
                            <td v-if="$slots.actions" class="whitespace-nowrap px-4 py-3 text-right">
                                <slot name="actions" :row="row" />
                            </td>
                        </tr>

                        <tr v-if="filtered.length === 0">
                            <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="p-0">
                                <slot name="empty">
                                    <EmptyState :title="emptyTitle" :description="emptyDescription" />
                                </slot>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ═══ Tarjetas (móvil) ═══ -->
            <ul class="divide-y divide-slate-100 sm:hidden">
                <li v-for="(row, i) in filtered" :key="row.id ?? i" class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1 text-sm font-bold text-slate-900">
                            <slot
                                :name="`cell-${columnaPrincipal.key}`"
                                :row="row"
                                :value="getValue(row, columnaPrincipal.key)"
                            >
                                {{ getValue(row, columnaPrincipal.key) }}
                            </slot>
                        </div>
                        <div v-if="$slots.actions" class="shrink-0">
                            <slot name="actions" :row="row" />
                        </div>
                    </div>

                    <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1.5">
                        <div v-for="col in columnasSecundarias" :key="col.key" class="min-w-0">
                            <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ col.label }}</dt>
                            <dd class="truncate text-sm text-slate-700">
                                <slot :name="`cell-${col.key}`" :row="row" :value="getValue(row, col.key)">
                                    {{ getValue(row, col.key) }}
                                </slot>
                            </dd>
                        </div>
                    </dl>
                </li>

                <li v-if="filtered.length === 0">
                    <slot name="empty">
                        <EmptyState :title="emptyTitle" :description="emptyDescription" />
                    </slot>
                </li>
            </ul>
        </div>

        <p class="mt-2.5 text-xs font-medium text-slate-500">
            {{ filtered.length }} {{ filtered.length === 1 ? 'resultado' : 'resultados' }}
            <span v-if="query" class="text-slate-400">· filtrado por «{{ query }}»</span>
        </p>
    </div>
</template>
