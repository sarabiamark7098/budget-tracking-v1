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
        await fetchPortfolio();
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
        await fetchPortfolio();
    }

    async function fetchPayments(id) {
        const { data } = await investmentService.getPayments(id);
        return data.data;
    }

    async function addPayment(id, formData) {
        const { data } = await investmentService.addPayment(id, formData);
        // Update item's payment_status in the list
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) {
            items.value[idx] = {
                ...items.value[idx],
                payment_status: data.data.payment_status,
                total_paid: data.data.total_paid,
            };
        }
        return data.data;
    }

    async function markDone(id) {
        const { data } = await investmentService.markDone(id);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data;
        return data.data;
    }

    return {
        items, loading, pagination, portfolio,
        fetchAll, fetchPortfolio, create, update, remove,
        fetchPayments, addPayment, markDone,
    };
});
