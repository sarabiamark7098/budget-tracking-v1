<template>
  <RouterView />
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useBudgetTrackingStore } from '@/stores/budgetTracking';

const authStore = useAuthStore();
const btStore   = useBudgetTrackingStore();

onMounted(async () => {
    // The router's beforeEach guard already called fetchUser() and awaited it
    // before this component mounted, so auth.ready is always true here.
    // We only need to trigger the budget tracker fetch.
    if (authStore.isAuthenticated) {
        btStore.fetchTracker();
    }
});
</script>
