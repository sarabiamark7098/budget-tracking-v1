import { defineStore } from 'pinia';
import { ref } from 'vue';
import { stockService } from '@/services/index';

export const useStockStore = defineStore('stock', () => {
    const items = ref([]);
    const loading = ref(false);
    const pagination = ref(null);
    const portfolio = ref(null);

    async function fetchAll(params = {}) {
        loading.value = true;
        try {
            const { data } = await stockService.getAll(params);
            items.value = data.data.data ?? data.data;
            pagination.value = data.data.meta ?? null;
        } finally {
            loading.value = false;
        }
    }

    async function fetchPortfolio() {
        const { data } = await stockService.getPortfolio();
        portfolio.value = data.data;
    }

    async function create(formData) {
        const { data } = await stockService.create(formData);
        items.value.unshift(data.data);
        await fetchPortfolio();
        return data.data;
    }

    async function update(id, formData) {
        const { data } = await stockService.update(id, formData);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data;
        return data.data;
    }

    async function remove(id) {
        await stockService.delete(id);
        items.value = items.value.filter(i => i.id !== id);
        await fetchPortfolio();
    }

    async function fetchLots(stockId) {
        const { data } = await stockService.getLots(stockId);
        return data.data.lots ?? [];
    }

    async function addLot(stockId, formData) {
        const { data } = await stockService.addLot(stockId, formData);
        await fetchPortfolio();
        return data.data;
    }

    async function updatePrice(stockId, price) {
        const { data } = await stockService.updatePrice(stockId, { latest_price: price });
        const idx = items.value.findIndex(i => i.id === stockId);
        if (idx !== -1) items.value[idx] = data.data;
        await fetchPortfolio();
        return data.data;
    }

    return { items, loading, pagination, portfolio, fetchAll, fetchPortfolio, create, update, remove, fetchLots, addLot, updatePrice };
});
