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
    const user  = ref(null);
    // ready becomes true after the first fetchUser() call resolves.
    // The router guard waits for this before making auth decisions so that
    // a page reload doesn't redirect to /login before the session is restored.
    const ready = ref(false);

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
     * /auth/me returns 200+user when a valid session exists, 200+null when not.
     * try/catch guards against any unexpected network or server error so the
     * app always boots cleanly even when the API is temporarily unreachable.
     * Sets ready=true so the router guard knows it's safe to make auth decisions.
     */
    async function fetchUser() {
        try {
            const { data } = await authService.me();
            user.value = data.data ?? null;
        } catch {
            user.value = null;
        } finally {
            ready.value = true;
        }
    }

    async function updateProfile(profileData) {
        const { data: res } = await authService.updateProfile(profileData);
        user.value = res.data;
        return res.data;
    }

    async function changePassword(data) {
        await authService.changePassword(data);
    }

    return { user, ready, isAuthenticated, login, register, logout, fetchUser, updateProfile, changePassword };
});
