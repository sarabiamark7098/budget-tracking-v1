import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authService } from '@/services/index';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const token = ref(localStorage.getItem('auth_token'));

    const isAuthenticated = computed(() => !!token.value);

    async function login(credentials) {
        const { data } = await authService.login(credentials);
        token.value = data.data.token;
        user.value = data.data;
        localStorage.setItem('auth_token', token.value);
        return data;
    }

    async function register(userData) {
        const { data } = await authService.register(userData);
        token.value = data.data.token;
        user.value = data.data;
        localStorage.setItem('auth_token', token.value);
        return data;
    }

    async function logout() {
        try { await authService.logout(); } catch {}
        token.value = null;
        user.value = null;
        localStorage.removeItem('auth_token');
    }

    async function fetchUser() {
        if (!token.value) return;
        try {
            const { data } = await authService.me();
            user.value = data.data;
        } catch {
            token.value = null;
            localStorage.removeItem('auth_token');
        }
    }

    async function updateProfile(data) {
        const { data: res } = await authService.updateProfile(data);
        user.value = res.data;
        return res.data;
    }

    async function changePassword(data) {
        await authService.changePassword(data);
    }

    return { user, token, isAuthenticated, login, register, logout, fetchUser, updateProfile, changePassword };
});
