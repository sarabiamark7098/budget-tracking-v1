import { defineStore } from 'pinia';
import { ref } from 'vue';
import { cryptoService } from '@/services/index';

export const useCryptoStore = defineStore('crypto', () => {
    const items = ref([]);
    const loading = ref(false);
    const pagination = ref(null);
    const portfolio = ref(null);

    async function fetchAll(params = {}) {
        loading.value = true;
        try {
            const { data } = await cryptoService.getAll(params);
            items.value = data.data.data ?? data.data;
            pagination.value = data.data.meta ?? null;
        } finally {
            loading.value = false;
        }
    }

    async function fetchPortfolio() {
        const { data } = await cryptoService.getPortfolio();
        portfolio.value = data.data;
    }

    async function create(formData) {
        const { data } = await cryptoService.create(formData);
        items.value.unshift(data.data);
        await fetchPortfolio();
        return data.data;
    }

    async function update(id, formData) {
        const { data } = await cryptoService.update(id, formData);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data;
        return data.data;
    }

    async function remove(id) {
        await cryptoService.delete(id);
        items.value = items.value.filter(i => i.id !== id);
        await fetchPortfolio();
    }

    async function fetchLots(cryptoId) {
        const { data } = await cryptoService.getLots(cryptoId);
        return data.data.lots ?? [];
    }

    async function addLot(cryptoId, formData) {
        const { data } = await cryptoService.addLot(cryptoId, formData);
        await fetchPortfolio();
        return data.data;
    }

    async function updatePrice(cryptoId, price) {
        const { data } = await cryptoService.updatePrice(cryptoId, { latest_price: price });
        const idx = items.value.findIndex(i => i.id === cryptoId);
        if (idx !== -1) items.value[idx] = data.data;
        await fetchPortfolio();
        return data.data;
    }

    return { items, loading, pagination, portfolio, fetchAll, fetchPortfolio, create, update, remove, fetchLots, addLot, updatePrice };
});
