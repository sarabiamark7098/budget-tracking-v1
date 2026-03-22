/**
 * Axios instance configured for Sanctum SPA cookie auth (S-02).
 *
 * Key changes from token-based auth:
 *  - withCredentials: true  → browser includes HttpOnly session cookie on every request
 *  - withXSRFToken: true    → axios reads XSRF-TOKEN cookie, sends X-XSRF-TOKEN header
 *  - X-Requested-With       → identifies request as an XHR so Sanctum treats it as stateful
 *  - No Authorization: Bearer header — the session cookie authenticates the request
 *
 * Before login, call initCsrf() once to fetch /sanctum/csrf-cookie, which sets
 * the XSRF-TOKEN cookie. Axios then auto-attaches it on every mutating request.
 */
import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,    // Send HttpOnly session cookie on every request
    withXSRFToken: true,      // Auto-attach X-XSRF-TOKEN header from XSRF-TOKEN cookie
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',  // Marks request as SPA / stateful
    },
});

/**
 * Fetch the Sanctum CSRF cookie before the first mutation (login / register).
 * Sets the XSRF-TOKEN cookie; axios reads it automatically on subsequent requests.
 */
export async function initCsrf() {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

// No Bearer token injection — session cookie handles authentication.
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            const url = error.config?.url ?? '';
            const onAuthPage = ['/login', '/register'].some(
                (p) => window.location.pathname.startsWith(p)
            );
            // Don't redirect when:
            //  • The 401 came from the session-probe itself (/auth/me) — that
            //    route always returns 200+null, so a 401 here is an edge case
            //    that the router guard will handle gracefully via auth.ready.
            //  • The user is already on a guest page (avoids infinite reload).
            if (!url.includes('auth/me') && !onAuthPage) {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

export default api;
