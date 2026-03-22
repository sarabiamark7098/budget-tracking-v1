/**
 * Auth store — S-02: migrated to Sanctum SPA cookie auth.
 *
 * No longer stores the token in localStorage. Authentication state is
 * maintained by the HttpOnly laravel_session cookie set by the server.
 * Axios sends this cookie automatically on every request (withCredentials: true).
 *
 * Flow:
 *  1. App boot → fetchUser() probes /auth/me to restore session state.
 *  2. Login/Register → call initCsrf() first to set XSRF-TOKEN cookie,
 *     then hit the endpoint; the server sets the session cookie in the response.
 *  3. Logout → server invalidates the session; local user state is cleared.
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authService } from '@/services/index';
import { initCsrf } from '@/services/api';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);

    // Authentication is determined by whether the user object is set.
    // The actual credential is the HttpOnly session cookie — not a localStorage token.
    const isAuthenticated = computed(() => !!user.value);

    async function login(credentials) {
        await initCsrf();  // Ensure XSRF-TOKEN cookie is set before mutation
        const { data } = await authService.login(credentials);
        user.value = data.data;
        return data;
    }

    async function register(userData) {
        await initCsrf();  // Ensure XSRF-TOKEN cookie is set before mutation
        const { data } = await authService.register(userData);
        user.value = data.data;
        return data;
    }

    async function logout() {
        try { await authService.logout(); } catch {}
        user.value = null;
    }

    /**
     * Probe the server to restore auth state on app load.
     * /auth/me always returns 200: data.data is the user object when a valid
     * session exists, or null when there is no active session.
     * No try/catch needed — the endpoint never returns 4xx/5xx for this case.
     */
    async function fetchUser() {
        const { data } = await authService.me();
        user.value = data.data ?? null;
    }

    async function updateProfile(profileData) {
        const { data: res } = await authService.updateProfile(profileData);
        user.value = res.data;
        return res.data;
    }

    async function changePassword(data) {
        await authService.changePassword(data);
    }

    return { user, isAuthenticated, login, register, logout, fetchUser, updateProfile, changePassword };
});
