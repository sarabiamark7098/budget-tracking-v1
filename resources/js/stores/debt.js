import { defineStore } from 'pinia';
import { ref } from 'vue';
import { debtService } from '@/services/index';

export const useDebtStore = defineStore('debt', () => {
    const items      = ref([]);
    const loading    = ref(false);
    const pagination = ref(null);

    async function fetchAll(params = {}) {
        loading.value = true;
        try {
            const { data } = await debtService.getAll(params);
            items.value      = data.data.data ?? data.data;
            pagination.value = data.data.meta  ?? null;
        } finally {
            loading.value = false;
        }
    }

    async function create(formData) {
        const { data } = await debtService.create(formData);
        items.value.unshift(data.data);
        return data.data;
    }

    async function update(id, formData) {
        const { data } = await debtService.update(id, formData);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data;
        return data.data;
    }

    async function remove(id) {
        await debtService.delete(id);
        items.value = items.value.filter(i => i.id !== id);
    }

    async function pay(id, payload = {}) {
        const { data } = await debtService.pay(id, payload);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data.debt;
        return data.data;
    }

    async function getBalance(id) {
        const { data } = await debtService.getBalance(id);
        return data.data;
    }

    return { items, loading, pagination, fetchAll, create, update, remove, pay, getBalance };
});
