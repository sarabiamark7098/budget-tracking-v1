import { defineStore } from 'pinia';
import { ref } from 'vue';
import { budgetTrackingService } from '@/services/index';

export const useBudgetTrackingStore = defineStore('budgetTracking', () => {
    const tracker        = ref(null);
    const summary        = ref(null);
    const consolidated   = ref(null);
    const consolidatedLoading = ref(false);
    const transactions   = ref([]);
    const txPagination   = ref(null);
    const loading        = ref(false);
    const txLoading      = ref(false);
    const notFound       = ref(false);
    const hasChecked     = ref(false); // true once first fetchTracker() completes

    // ── Tracker ───────────────────────────────────────────────────────────────
    async function fetchTracker() {
        loading.value = true;
        notFound.value = false;
        try {
            const { data } = await budgetTrackingService.get();
            tracker.value = data.data;
        } catch (e) {
            if (e.response?.status === 404) {
                tracker.value = null;
                notFound.value = true;
            } else {
                throw e;
            }
        } finally {
            loading.value = false;
            hasChecked.value = true;
        }
    }

    async function create(formData) {
        const { data } = await budgetTrackingService.create(formData);
        tracker.value = data.data;
        notFound.value = false;
        return data.data;
    }

    async function update(formData) {
        const { data } = await budgetTrackingService.update(formData);
        tracker.value = data.data;
        return data.data;
    }

    async function remove() {
        await budgetTrackingService.delete();
        tracker.value = null;
        summary.value = null;
        consolidated.value = null;
        transactions.value = [];
        notFound.value = true;
        hasChecked.value = true;
    }

    // ── Membership ────────────────────────────────────────────────────────────
    async function join(code) {
        const { data } = await budgetTrackingService.join(code);
        tracker.value = data.data;
        notFound.value = false;
        return data.data;
    }

    async function leave() {
        await budgetTrackingService.leave();
        tracker.value = null;
        summary.value = null;
        consolidated.value = null;
        transactions.value = [];
        notFound.value = true;
        hasChecked.value = true;
    }

    async function regenerateCode() {
        const { data } = await budgetTrackingService.regenerateCode();
        if (tracker.value) tracker.value.join_code = data.data.join_code;
        return data.data.join_code;
    }

    async function removeMember(userId) {
        await budgetTrackingService.removeMember(userId);
        if (tracker.value?.members) {
            tracker.value.members = tracker.value.members.filter(m => m.user_id !== userId);
        }
    }

    // ── Summary ───────────────────────────────────────────────────────────────
    async function fetchSummary() {
        const { data } = await budgetTrackingService.getSummary();
        summary.value = data.data;
        return data.data;
    }

    // ── Consolidated member data ───────────────────────────────────────────────
    async function fetchConsolidated() {
        consolidatedLoading.value = true;
        try {
            const { data } = await budgetTrackingService.getConsolidated();
            consolidated.value = data.data;
            return data.data;
        } finally {
            consolidatedLoading.value = false;
        }
    }

    // ── Allocations ───────────────────────────────────────────────────────────
    async function addAllocation(formData) {
        const { data } = await budgetTrackingService.createAllocation(formData);
        if (tracker.value?.allocations) {
            tracker.value.allocations.push(data.data);
        }
        return data.data;
    }

    async function updateAllocation(id, formData) {
        const { data } = await budgetTrackingService.updateAllocation(id, formData);
        if (tracker.value?.allocations) {
            const idx = tracker.value.allocations.findIndex(a => a.id === id);
            if (idx !== -1) tracker.value.allocations[idx] = data.data;
        }
        return data.data;
    }

    async function deleteAllocation(id) {
        await budgetTrackingService.deleteAllocation(id);
        if (tracker.value?.allocations) {
            tracker.value.allocations = tracker.value.allocations.filter(a => a.id !== id);
        }
    }

    // ── Transactions ──────────────────────────────────────────────────────────
    async function fetchTransactions(params = {}) {
        txLoading.value = true;
        try {
            const { data } = await budgetTrackingService.getTransactions(params);
            transactions.value   = data.data.data ?? data.data;
            txPagination.value   = data.data.meta  ?? null;
        } finally {
            txLoading.value = false;
        }
    }

    async function addTransaction(formData) {
        const { data } = await budgetTrackingService.createTransaction(formData);
        transactions.value.unshift(data.data);
        return data.data;
    }

    async function updateTransaction(id, formData) {
        const { data } = await budgetTrackingService.updateTransaction(id, formData);
        const idx = transactions.value.findIndex(t => t.id === id);
        if (idx !== -1) transactions.value[idx] = data.data;
        return data.data;
    }

    async function deleteTransaction(id) {
        await budgetTrackingService.deleteTransaction(id);
        transactions.value = transactions.value.filter(t => t.id !== id);
    }

    return {
        tracker, summary, consolidated, consolidatedLoading,
        transactions, txPagination, loading, txLoading, notFound, hasChecked,
        fetchTracker, create, update, remove,
        join, leave, regenerateCode, removeMember,
        fetchSummary, fetchConsolidated,
        addAllocation, updateAllocation, deleteAllocation,
        fetchTransactions, addTransaction, updateTransaction, deleteTransaction,
    };
});
