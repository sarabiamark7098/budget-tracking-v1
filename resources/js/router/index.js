import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

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
                { path: '', redirect: '/dashboard' },
                { path: 'dashboard', component: () => import('@/pages/Dashboard/DashboardPage.vue') },
                { path: 'transactions', component: () => import('@/pages/Dashboard/TransactionsPage.vue') },
                { path: 'income', component: () => import('@/pages/Income/IncomePage.vue') },
                { path: 'expenses', component: () => import('@/pages/Expense/ExpensePage.vue') },
                { path: 'budget', component: () => import('@/pages/Budget/BudgetPage.vue') },
                { path: 'budget-tracking', component: () => import('@/pages/BudgetTracking/BudgetTrackingPage.vue') },
                { path: 'debts', component: () => import('@/pages/Debt/DebtPage.vue') },
                { path: 'payments', component: () => import('@/pages/Payment/PaymentPage.vue') },
                { path: 'investments', component: () => import('@/pages/Investment/InvestmentPage.vue') },
                { path: 'stocks', component: () => import('@/pages/Stock/StockPage.vue') },
                { path: 'crypto', component: () => import('@/pages/Crypto/CryptoPage.vue') },
                { path: 'financial-plans', component: () => import('@/pages/Plan/FinancialPlanPage.vue') },
                { path: 'insurance', component: () => import('@/pages/Insurance/InsurancePage.vue') },
                { path: 'purchases', component: () => import('@/pages/Purchase/PurchasePage.vue') },
                { path: 'mp2', component: () => import('@/pages/MP2/MP2Page.vue') },
                { path: 'reports', component: () => import('@/pages/Report/ReportPage.vue') },
            ],
        },
        { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    if (to.meta.requiresAuth && !auth.isAuthenticated) return '/login';
    if (to.meta.guest && auth.isAuthenticated) return '/dashboard';
});

export default router;
