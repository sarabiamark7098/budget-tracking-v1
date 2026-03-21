import { defineStore } from 'pinia';
import { ref } from 'vue';
import { insuranceService } from '@/services/index';

export const useInsuranceStore = defineStore('insurance', () => {
    const items = ref([]);
    const loading = ref(false);
    const pagination = ref(null);

    async function fetchAll(params = {}) {
        loading.value = true;
        try {
            const { data } = await insuranceService.getAll(params);
            items.value = data.data.data ?? data.data;
            pagination.value = data.data.meta ?? null;
        } finally {
            loading.value = false;
        }
    }

    async function create(formData) {
        const { data } = await insuranceService.create(formData);
        items.value.unshift(data.data);
        return data.data;
    }

    async function update(id, formData) {
        const { data } = await insuranceService.update(id, formData);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data;
        return data.data;
    }

    async function remove(id) {
        await insuranceService.delete(id);
        items.value = items.value.filter(i => i.id !== id);
    }

    async function pay(planId, formData) {
        const { data } = await insuranceService.pay(planId, formData);
        // Update total_paid in items list
        const idx = items.value.findIndex(i => i.id === planId);
        if (idx !== -1) {
            items.value[idx] = { ...items.value[idx], total_paid: (Number(items.value[idx].total_paid || 0) + Number(formData.amount)) };
        }
        return data.data;
    }

    async function fetchPlanPayments(planId, params = {}) {
        const { data } = await insuranceService.getPlanPayments(planId, params);
        return data.data;
    }

    return { items, loading, pagination, fetchAll, create, update, remove, pay, fetchPlanPayments };
});
