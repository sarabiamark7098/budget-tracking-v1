import { defineStore } from 'pinia';
import { ref } from 'vue';
import { purchaseService } from '@/services/index';

export const usePurchaseStore = defineStore('purchase', () => {
    const items      = ref([]);
    const loading    = ref(false);
    const pagination = ref(null);
    const summary    = ref(null);

    async function fetchAll(params = {}) {
        loading.value = true;
        try {
            const { data } = await purchaseService.getAll(params);
            items.value      = data.data.data ?? data.data;
            pagination.value = data.data.meta ?? null;
        } finally {
            loading.value = false;
        }
    }

    async function fetchSummary() {
        const { data } = await purchaseService.getSummary();
        summary.value = data.data;
    }

    async function create(formData) {
        const { data } = await purchaseService.create(formData);
        items.value.unshift(data.data);
        return data.data;
    }

    async function update(id, formData) {
        const { data } = await purchaseService.update(id, formData);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data;
        return data.data;
    }

    async function payInstallment(id) {
        const { data } = await purchaseService.payInstallment(id);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data;
        return data.data;
    }

    async function remove(id) {
        await purchaseService.delete(id);
        items.value = items.value.filter(i => i.id !== id);
    }

    return { items, loading, pagination, summary, fetchAll, fetchSummary, create, update, payInstallment, remove };
});
