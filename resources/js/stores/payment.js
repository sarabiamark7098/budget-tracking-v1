import { defineStore } from 'pinia';
import { ref } from 'vue';
import { paymentService } from '@/services/index';

export const usePaymentStore = defineStore('payment', () => {
    const items = ref([]);
    const loading = ref(false);
    const pagination = ref(null);

    async function fetchAll(params = {}) {
        loading.value = true;
        try {
            const { data } = await paymentService.getAll(params);
            items.value = data.data.data ?? data.data;
            pagination.value = data.data.meta ?? null;
        } finally {
            loading.value = false;
        }
    }

    async function create(formData) {
        const { data } = await paymentService.create(formData);
        items.value.unshift(data.data);
        return data.data;
    }

    async function remove(id) {
        await paymentService.delete(id);
        items.value = items.value.filter(i => i.id !== id);
    }

    return { items, loading, pagination, fetchAll, create, remove };
});
