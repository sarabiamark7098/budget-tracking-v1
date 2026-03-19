import { defineStore } from 'pinia';
import { ref } from 'vue';
import { dashboardService } from '@/services/index';

export const useDashboardStore = defineStore('dashboard', () => {
    const summary = ref(null);
    const loading = ref(false);

    async function fetchSummary(params = {}) {
        loading.value = true;
        try {
            const { data } = await dashboardService.getSummary(params);
            summary.value = data.data;
        } finally {
            loading.value = false;
        }
    }

    return { summary, loading, fetchSummary };
});
