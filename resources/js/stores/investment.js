import { defineStore } from 'pinia';
import { ref } from 'vue';
import { investmentService } from '@/services/index';

export const useInvestmentStore = defineStore('investment', () => {
    const items = ref([]);
    const loading = ref(false);
    const pagination = ref(null);
    const portfolio = ref(null);

    async function fetchAll(params = {}) {
        loading.value = true;
        try {
            const { data } = await investmentService.getAll(params);
            items.value = data.data.data ?? data.data;
            pagination.value = data.data.meta ?? null;
        } finally {
            loading.value = false;
        }
    }

    async function fetchPortfolio() {
        const { data } = await investmentService.getPortfolio();
        portfolio.value = data.data;
    }

    async function create(formData) {
        const { data } = await investmentService.create(formData);
        items.value.unshift(data.data);
        return data.data;
    }

    async function update(id, formData) {
        const { data } = await investmentService.update(id, formData);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data;
        return data.data;
    }

    async function remove(id) {
        await investmentService.delete(id);
        items.value = items.value.filter(i => i.id !== id);
    }

    return { items, loading, pagination, portfolio, fetchAll, fetchPortfolio, create, update, remove };
});
