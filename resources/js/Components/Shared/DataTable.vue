<script setup>
import { computed, ref, watch } from 'vue'

/**
 * Tabla reutilizable con búsqueda instantánea (cliente) y filtros persistentes.
 *
 * Props:
 *  - columns: [{ key, label, sortable?, class? }]
 *  - rows: array de objetos
 *  - searchKeys: claves sobre las que buscar (por defecto, todas las de columns)
 *  - persistKey: si se define, guarda el texto de búsqueda en la URL (?q=) y localStorage
 *
 * Slots:
 *  - cell-<key>: contenido personalizado por celda (recibe { row, value })
 *  - actions: columna de acciones por fila (recibe { row })
 *  - empty: estado vacío
 */
const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    searchKeys: { type: Array, default: null },
    searchable: { type: Boolean, default: true },
    persistKey: { type: String, default: null },
    placeholder: { type: String, default: 'Buscar…' },
})

const initialQuery = () => {
    if (!props.persistKey) return ''
    const url = new URL(window.location.href)
    return url.searchParams.get('q') ?? localStorage.getItem(`dt:${props.persistKey}`) ?? ''
}

const query = ref(initialQuery())
const sortKey = ref(null)
const sortDir = ref('asc')

watch(query, (q) => {
    if (!props.persistKey) return
    localStorage.setItem(`dt:${props.persistKey}`, q)
    const url = new URL(window.location.href)
    if (q) url.searchParams.set('q', q)
    else url.searchParams.delete('q')
    window.history.replaceState({}, '', url)
})

const keysToSearch = computed(
    () => props.searchKeys ?? props.columns.map((c) => c.key)
)

const filtered = computed(() => {
    let out = props.rows
    const q = query.value.trim().toLowerCase()
    if (q) {
        out = out.filter((row) =>
            keysToSearch.value.some((k) =>
                String(getValue(row, k) ?? '').toLowerCase().includes(q)
            )
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
        <div v-if="searchable || $slots.filters" class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div v-if="searchable" class="relative w-full sm:max-w-xs">
                <input
                    v-model="query"
                    type="search"
                    :placeholder="placeholder"
                    class="w-full rounded-md border-slate-300 pl-9 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                />
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">⌕</span>
            </div>
            <div v-if="$slots.filters" class="flex flex-wrap items-center gap-2">
                <slot name="filters" />
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-4 py-3 text-left font-semibold text-slate-600"
                            :class="[col.class, col.sortable ? 'cursor-pointer select-none' : '']"
                            @click="toggleSort(col)"
                        >
                            {{ col.label }}
                            <span v-if="sortKey === col.key">{{ sortDir === 'asc' ? '▲' : '▼' }}</span>
                        </th>
                        <th v-if="$slots.actions" class="px-4 py-3 text-right font-semibold text-slate-600">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <tr v-for="(row, i) in filtered" :key="row.id ?? i" class="hover:bg-slate-50">
                        <td v-for="col in columns" :key="col.key" class="px-4 py-3 text-slate-700" :class="col.class">
                            <slot :name="`cell-${col.key}`" :row="row" :value="getValue(row, col.key)">
                                {{ getValue(row, col.key) }}
                            </slot>
                        </td>
                        <td v-if="$slots.actions" class="px-4 py-3 text-right">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>
                    <tr v-if="filtered.length === 0">
                        <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-4 py-10 text-center text-slate-400">
                            <slot name="empty">No hay resultados.</slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="mt-2 text-xs text-slate-400">{{ filtered.length }} resultado(s)</p>
    </div>
</template>
