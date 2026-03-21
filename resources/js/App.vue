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
    await authStore.fetchUser();
    if (authStore.isAuthenticated) {
        btStore.fetchTracker();
    }
});
</script>
