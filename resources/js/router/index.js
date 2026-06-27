import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useBudgetTrackingStore } from '@/stores/budgetTracking';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/login', component: () => import('@/pages/Auth/LoginPage.vue'), meta: { guest: true } },
        { path: '/register', component: () => import('@/pages/Auth/RegisterPage.vue'), meta: { guest: true } },
        {
            path: '/',
            component: () => import('@/layouts/AppLayout.vue'),
            meta: { requiresAuth: true },
            children: [
                { path: '', redirect: '/budget-tracking' },
                { path: 'dashboard', component: () => import('@/pages/Dashboard/DashboardPage.vue') },
                { path: 'transactions', component: () => import('@/pages/Dashboard/TransactionsPage.vue') },
                { path: 'income', component: () => import('@/pages/Income/IncomePage.vue') },
                { path: 'expenses', component: () => import('@/pages/Expense/ExpensePage.vue') },
                { path: 'budget', component: () => import('@/pages/Budget/BudgetPage.vue') },
                { path: 'budget-tracking', component: () => import('@/pages/BudgetTracking/BudgetTrackingPage.vue') },
                { path: 'debts', component: () => import('@/pages/Debt/DebtPage.vue') },
                { path: 'payments', component: () => import('@/pages/Payment/PaymentPage.vue') },
                { path: 'purchases', component: () => import('@/pages/Purchase/PurchasePage.vue') },
                { path: 'mp2', component: () => import('@/pages/MP2/MP2Page.vue') },
                { path: 'reports', component: () => import('@/pages/Report/ReportPage.vue') },
            ],
        },
        { path: '/:pathMatch(.*)*', redirect: '/budget-tracking' },
    ],
});

// Holds the in-flight fetchUser() promise so the guard only calls it once per page load.
let authInitPromise = null;

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    // On every page load (or hard reload) the Pinia store is empty and auth.ready
    // is false. We must await the session probe before making any auth decisions,
    // otherwise the guard always sees isAuthenticated=false and redirects to /login
    // before the session cookie has had a chance to restore the user.
    if (!auth.ready) {
        if (!authInitPromise) {
            authInitPromise = auth.fetchUser();
        }
        await authInitPromise;
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) return '/login';
    if (to.meta.guest && auth.isAuthenticated) return '/budget-tracking';

    // Gate: authenticated users without a tracker can only visit /budget-tracking
    if (to.meta.requiresAuth && auth.isAuthenticated && to.path !== '/budget-tracking') {
        const bt = useBudgetTrackingStore();
        // If the check has already resolved and there's no tracker, redirect
        if (bt.hasChecked && !bt.tracker) return '/budget-tracking';
    }
});

export default router;
