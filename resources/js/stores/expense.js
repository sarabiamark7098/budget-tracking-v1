import { defineStore } from 'pinia';
import { ref } from 'vue';
import { expenseService } from '@/services/index';

export const useExpenseStore = defineStore('expense', () => {
    const items = ref([]);
    const loading = ref(false);
    const pagination = ref(null);
    const monthly = ref([]);

    async function fetchAll(params = {}) {
        loading.value = true;
        try {
            const { data } = await expenseService.getAll(params);
            items.value = data.data.data ?? data.data;
            pagination.value = data.data.meta ?? null;
        } finally {
            loading.value = false;
        }
    }

    async function fetchMonthly(params = {}) {
        const { data } = await expenseService.getMonthly(params);
        monthly.value = data.data;
    }

    async function create(formData) {
        const { data } = await expenseService.create(formData);
        items.value.unshift(data.data);
        return data.data;
    }

    async function update(id, formData) {
        const { data } = await expenseService.update(id, formData);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data;
        return data.data;
    }

    async function remove(id) {
        await expenseService.delete(id);
        items.value = items.value.filter(i => i.id !== id);
    }

    return { items, loading, pagination, monthly, fetchAll, fetchMonthly, create, update, remove };
});
