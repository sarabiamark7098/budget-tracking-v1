import { defineStore } from 'pinia';
import { ref } from 'vue';
import { mp2Service } from '@/services/index';

export const useMp2Store = defineStore('mp2', () => {
    const items = ref([]);
    const loading = ref(false);
    const calculationResult = ref(null);

    async function fetchAll() {
        loading.value = true;
        try {
            const { data } = await mp2Service.getAll();
            items.value = data.data ?? [];
        } finally {
            loading.value = false;
        }
    }

    async function calculate(formData) {
        const { data } = await mp2Service.calculate(formData);
        calculationResult.value = data.data;
        return data.data;
    }

    async function create(formData) {
        const { data } = await mp2Service.create(formData);
        items.value.unshift(data.data);
        return data.data;
    }

    async function update(id, formData) {
        const { data } = await mp2Service.update(id, formData);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data;
        return data.data;
    }

    async function remove(id) {
        await mp2Service.delete(id);
        items.value = items.value.filter(i => i.id !== id);
    }

    return { items, loading, calculationResult, fetchAll, calculate, create, update, remove };
});
