import { defineStore } from 'pinia';
import { ref } from 'vue';
import { financialPlanService, financialGoalService } from '@/services/index';

export const usePlanStore = defineStore('plan', () => {
    const items = ref([]);
    const goals = ref([]);
    const loading = ref(false);
    const pagination = ref(null);

    async function fetchAll(params = {}) {
        loading.value = true;
        try {
            const { data } = await financialPlanService.getAll(params);
            items.value = data.data.data ?? data.data;
            pagination.value = data.data.meta ?? null;
        } finally {
            loading.value = false;
        }
    }

    async function fetchGoals(params = {}) {
        const { data } = await financialGoalService.getAll(params);
        goals.value = data.data.data ?? data.data;
    }

    async function create(formData) {
        const { data } = await financialPlanService.create(formData);
        items.value.unshift(data.data);
        return data.data;
    }

    async function update(id, formData) {
        const { data } = await financialPlanService.update(id, formData);
        const idx = items.value.findIndex(i => i.id === id);
        if (idx !== -1) items.value[idx] = data.data;
        return data.data;
    }

    async function remove(id) {
        await financialPlanService.delete(id);
        items.value = items.value.filter(i => i.id !== id);
    }

    async function createGoal(formData) {
        const { data } = await financialGoalService.create(formData);
        goals.value.unshift(data.data);
        return data.data;
    }

    async function updateGoal(id, formData) {
        const { data } = await financialGoalService.update(id, formData);
        const idx = goals.value.findIndex(i => i.id === id);
        if (idx !== -1) goals.value[idx] = data.data;
        return data.data;
    }

    async function removeGoal(id) {
        await financialGoalService.delete(id);
        goals.value = goals.value.filter(i => i.id !== id);
    }

    return { items, goals, loading, pagination, fetchAll, fetchGoals, create, update, remove, createGoal, updateGoal, removeGoal };
});
