import { defineStore } from 'pinia';
import { ref } from 'vue';
import { budgetService } from '@/services/index';

export const useBudgetStore = defineStore('budget', () => {
    const items = ref([]);
    const loading = ref(false);
    const pagination = ref(null);
    const summary = ref(null);

    async function fetchAll(params = {}) {
        loading.value = true;
        try {
            const { data } = await budgetService.getAll(params);
            items.value = data.data.data ?? data.data;
            pagination.value = data.data.meta ?? null;
        } finally {
            loading.value = false;
        }
    }

    async function fetchSummary() {
        const { data } = await budgetService.getSummary();
        summary.value = data.data;
    }

    async function create(formData) {
        const { data } = await budgetService.create(formData);
        items.value.unshift(data.data);
        return data.data;
    }

    async function update(id, formData) {
        const { data } = await budgetService.update(id, formData);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data;
        return data.data;
    }

    async function remove(id) {
        await budgetService.delete(id);
        items.value = items.value.filter(i => i.id !== id);
    }

    return { items, loading, pagination, summary, fetchAll, fetchSummary, create, update, remove };
});
