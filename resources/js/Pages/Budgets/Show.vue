<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue'
import AppButton from '@/Components/Shared/AppButton.vue';
import { useAuth } from '@/composables/useAuth';
import { useToast } from '@/composables/useToast';
import ConfirmButton from '@/Components/Shared/ConfirmButton.vue';

const props = defineProps({
    budget: Object,
    comparison: { type: Object, default: () => ({ variance: 0, expected_balance: 0, real_balance: 0 }) },
});

const { can } = useAuth();
const toast = useToast();

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
};

const incomeItems = computed(() => props.budget.items.filter(i => i.type === 'income'));
const expenseItems = computed(() => props.budget.items.filter(i => i.type === 'expense'));

const expectedIncome = computed(() => incomeItems.value.reduce((sum, item) => sum + Number(item.amount), 0));
const expectedExpense = computed(() => expenseItems.value.reduce((sum, item) => sum + Number(item.amount), 0));

const realIncome = computed(() => {
    return incomeItems.value.reduce((sum, item) => {
        const itemReal = item.invoices.reduce((s, i) => s + Number(i.amount) + Number(i.vat), 0);
        return sum + itemReal;
    }, 0);
});

const realExpense = computed(() => {
    return expenseItems.value.reduce((sum, item) => {
        const itemReal = item.invoices.reduce((s, i) => s + Number(i.amount) + Number(i.vat), 0);
        return sum + itemReal;
    }, 0);
});

const newItemForm = useForm({
    description: '',
    type: 'expense',
    amount: '',
});

const isAddingItem = ref(false);

const addItem = () => {
    newItemForm.post(route('budgets.items.store', props.budget.id), {
        onSuccess: () => {
            isAddingItem.value = false;
            newItemForm.reset();
            toast.success('Partida añadida');
        }
    });
};

const updateItem = (item, field, event) => {
    const val = event.target.value;
    router.put(route('budgets.items.update', item.id), {
        ...item,
        [field]: val
    }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Partida actualizada'),
    });
};

const deleteItem = (item) => {
    router.delete(route('budgets.items.destroy', item.id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Partida eliminada'),
        onError: () => toast.error('No se pudo eliminar la partida'),
    });
};

const updateStatus = (event) => {
    router.put(route('budgets.update', props.budget.id), {
        status: event.target.value
    }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Estado del presupuesto actualizado'),
    });
};

const inp = 'w-full bg-transparent border-transparent hover:bg-slate-50 focus:bg-white focus:border-brand-500 focus:ring-1 focus:ring-brand-500 rounded text-sm px-2 py-1 transition-colors cursor-pointer focus:cursor-text truncate';
</script>

<template>
    <Head :title="'Presupuesto: ' + budget.name" />

    <AppLayout>
        <PageHeader :title="budget.name" subtitle="Gestión detallada de ingresos y gastos previstos" icon="chart">
            <template #actions>
                <div class="flex items-center gap-3">
                    <select class="input py-1.5 text-sm font-semibold text-slate-700 bg-white" :value="budget.status" @change="updateStatus">
                        <option value="draft">🟠 Borrador</option>
                        <option value="active">🟢 Activo</option>
                        <option value="finished">🔴 Terminado</option>
                    </select>
                    <Link :href="route('finance.dashboard')" class="btn-secondary">Volver al Panel</Link>
                </div>
            </template>
        </PageHeader>

        <!-- Desvío del balance -->
        <div v-if="budget.status !== 'draft'" class="mb-4 flex items-center justify-between rounded-xl border p-4"
            :class="comparison.variance < 0 ? 'border-rose-200 bg-rose-50/60' : 'border-emerald-200 bg-emerald-50/60'">
            <div class="text-sm text-slate-600">
                Balance previsto <b>{{ formatCurrency(comparison.expected_balance) }}</b>
                · real <b>{{ formatCurrency(comparison.real_balance) }}</b>
            </div>
            <div class="text-right">
                <p class="text-xs font-medium text-slate-500">Desvío</p>
                <p class="text-xl font-bold" :class="comparison.variance < 0 ? 'text-rose-600' : 'text-emerald-600'">
                    {{ comparison.variance > 0 ? '+' : '' }}{{ formatCurrency(comparison.variance) }}
                </p>
            </div>
        </div>

        <!-- Resumen -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-8">
            <div class="card p-5 border-t-4 border-t-emerald-500">
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-3">Total Ingresos</h3>
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-xs text-slate-400 font-medium mb-1">Previsto</p>
                        <p class="text-2xl font-bold text-slate-700">{{ formatCurrency(expectedIncome) }}</p>
                    </div>
                    <div v-if="budget.status !== 'draft'" class="text-right">
                        <p class="text-xs text-emerald-600/70 font-medium mb-1">Real</p>
                        <p class="text-2xl font-bold text-emerald-600">{{ formatCurrency(realIncome) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="card p-5 border-t-4 border-t-rose-500">
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-3">Total Gastos</h3>
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-xs text-slate-400 font-medium mb-1">Previsto</p>
                        <p class="text-2xl font-bold text-slate-700">{{ formatCurrency(expectedExpense) }}</p>
                    </div>
                    <div v-if="budget.status !== 'draft'" class="text-right">
                        <p class="text-xs text-rose-600/70 font-medium mb-1">Real</p>
                        <p class="text-2xl font-bold text-rose-600">{{ formatCurrency(realExpense) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Partidas -->
        <div class="card">
            <div class="border-b border-slate-200 px-6 py-4 flex justify-between items-center bg-slate-50/50 rounded-t-xl">
                <h3 class="text-lg font-bold text-slate-800">Partidas del Presupuesto</h3>
                <AppButton v-if="!isAddingItem" variant="primary" size="sm" icon="plus" @click="isAddingItem = true">
                    Añadir partida
                </AppButton>
            </div>

            <!-- Formulario nueva partida -->
            <div v-if="isAddingItem" class="bg-indigo-50/50 p-6 border-b border-indigo-100">
                <form @submit.prevent="addItem" class="flex flex-col sm:flex-row items-end gap-4">
                    <div class="w-full sm:w-1/3">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Concepto</label>
                        <input type="text" v-model="newItemForm.description" class="input w-full" placeholder="Ej. Autobuses" required>
                    </div>
                    <div class="w-full sm:w-1/4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
                        <select v-model="newItemForm.type" class="input w-full">
                            <option value="expense">Gasto</option>
                            <option value="income">Ingreso</option>
                        </select>
                    </div>
                    <div class="w-full sm:w-1/4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cantidad Prevista (€)</label>
                        <input type="number" step="0.01" min="0" v-model="newItemForm.amount" class="input w-full" placeholder="0.00" required>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary" :disabled="newItemForm.processing">Guardar</button>
                        <button type="button" @click="isAddingItem = false" class="btn-secondary">Cancelar</button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Concepto</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Tipo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Previsto</th>
                            <th v-if="budget.status !== 'draft'" scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Realizado</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr v-for="item in budget.items" :key="item.id" class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-3 whitespace-nowrap">
                                <input :class="inp" style="min-width: 200px" :value="item.description" @change="updateItem(item, 'description', $event)" />
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span :class="item.type === 'income' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'" class="px-2 py-1 rounded-md text-xs font-bold uppercase">
                                    {{ item.type === 'income' ? 'Ingreso' : 'Gasto' }}
                                </span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <input type="number" step="0.01" :class="inp" style="min-width: 100px" :value="item.amount" @change="updateItem(item, 'amount', $event)" />
                            </td>
                            <td v-if="budget.status !== 'draft'" class="px-6 py-3 whitespace-nowrap">
                                <span class="font-medium text-slate-900">
                                    {{ formatCurrency(item.invoices.reduce((s, i) => s + Number(i.amount) + Number(i.vat), 0)) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium">
                                <ConfirmButton message="¿Eliminar esta partida?" confirm-label="Eliminar" @confirm="deleteItem(item)">
                                    <span class="text-rose-500 hover:text-rose-700 opacity-0 group-hover:opacity-100 transition cursor-pointer font-medium">Borrar</span>
                                </ConfirmButton>
                            </td>
                        </tr>
                        <tr v-if="budget.items.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500 text-sm">No hay partidas definidas. Añade ingresos o gastos previstos.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
