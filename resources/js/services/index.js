import api from './api';

// Auth
export const authService = {
    register: (data) => api.post('/auth/register', data),
    login: (data) => api.post('/auth/login', data),
    logout: () => api.post('/auth/logout'),
    me: () => api.get('/auth/me'),
    updateProfile: (data) => api.put('/auth/profile', data),
    changePassword: (data) => api.put('/auth/password', data),
};

// Dashboard
export const dashboardService = {
    getSummary:      (params) => api.get('/dashboard',              { params }),
    getTransactions: (params) => api.get('/dashboard/transactions', { params }),
};

// Categories
export const categoryService = {
    getAll: (params) => api.get('/categories', { params }),
    create: (data) => api.post('/categories', data),
    update: (id, data) => api.put(`/categories/${id}`, data),
    delete: (id) => api.delete(`/categories/${id}`),
};

// Incomes
export const incomeService = {
    getAll: (params) => api.get('/incomes', { params }),
    getMonthly: (params) => api.get('/incomes/monthly', { params }),
    create: (data) => api.post('/incomes', data),
    show: (id) => api.get(`/incomes/${id}`),
    update: (id, data) => api.put(`/incomes/${id}`, data),
    delete: (id) => api.delete(`/incomes/${id}`),
};

// Expenses
export const expenseService = {
    getAll: (params) => api.get('/expenses', { params }),
    getMonthly: (params) => api.get('/expenses/monthly', { params }),
    create: (data) => api.post('/expenses', data),
    show: (id) => api.get(`/expenses/${id}`),
    update: (id, data) => api.put(`/expenses/${id}`, data),
    delete: (id) => api.delete(`/expenses/${id}`),
};

// Budgets
export const budgetService = {
    getAll: (params) => api.get('/budgets', { params }),
    getSummary: () => api.get('/budgets/summary'),
    create: (data) => api.post('/budgets', data),
    show: (id) => api.get(`/budgets/${id}`),
    update: (id, data) => api.put(`/budgets/${id}`, data),
    delete: (id) => api.delete(`/budgets/${id}`),
};

// Debts
export const debtService = {
    getAll:     (params) => api.get('/debts', { params }),
    create:     (data)   => api.post('/debts', data),
    show:       (id)     => api.get(`/debts/${id}`),
    update:     (id, data) => api.put(`/debts/${id}`, data),
    delete:     (id)     => api.delete(`/debts/${id}`),
    getBalance: (id)     => api.get(`/debts/${id}/balance`),
    pay:        (id, data) => api.post(`/debts/${id}/pay`, data ?? {}),
};

// Payments
export const paymentService = {
    getAll: (params) => api.get('/payments', { params }),
    create: (data) => api.post('/payments', data),
    delete: (id) => api.delete(`/payments/${id}`),
};

// Investments
export const investmentService = {
    getAll: (params) => api.get('/investments', { params }),
    getPortfolio: () => api.get('/investments/portfolio'),
    create: (data) => api.post('/investments', data),
    show: (id) => api.get(`/investments/${id}`),
    update: (id, data) => api.put(`/investments/${id}`, data),
    delete: (id) => api.delete(`/investments/${id}`),
    getPayments: (id) => api.get(`/investments/${id}/payments`),
    addPayment: (id, data) => api.post(`/investments/${id}/payments`, data),
    markDone: (id) => api.patch(`/investments/${id}/done`),
    getDividends: (id) => api.get(`/investments/${id}/dividends`),
    storeDividend: (id, data) => api.post(`/investments/${id}/dividends`, data),
};

// Stocks
export const stockService = {
    getAll: (params) => api.get('/stocks', { params }),
    getPortfolio: () => api.get('/stocks/portfolio'),
    create: (data) => api.post('/stocks', data),
    show: (id) => api.get(`/stocks/${id}`),
    update: (id, data) => api.put(`/stocks/${id}`, data),
    delete: (id) => api.delete(`/stocks/${id}`),
    getLots: (id) => api.get(`/stocks/${id}/lots`),
    addLot: (id, data) => api.post(`/stocks/${id}/lots`, data),
    updatePrice: (id, data) => api.patch(`/stocks/${id}/price`, data),
    sell: (id, data) => api.post(`/stocks/${id}/sell`, data),
    getDividends: (id) => api.get(`/stocks/${id}/dividends`),
    storeDividend: (id, data) => api.post(`/stocks/${id}/dividends`, data),
};

// Crypto
export const cryptoService = {
    getAll: (params) => api.get('/crypto', { params }),
    getPortfolio: () => api.get('/crypto/portfolio'),
    create: (data) => api.post('/crypto', data),
    show: (id) => api.get(`/crypto/${id}`),
    update: (id, data) => api.put(`/crypto/${id}`, data),
    delete: (id) => api.delete(`/crypto/${id}`),
    getLots: (id) => api.get(`/crypto/${id}/lots`),
    addLot: (id, data) => api.post(`/crypto/${id}/lots`, data),
    updatePrice: (id, data) => api.patch(`/crypto/${id}/price`, data),
    sell: (id, data) => api.post(`/crypto/${id}/sell`, data),
    getDividends: (id) => api.get(`/crypto/${id}/dividends`),
    storeDividend: (id, data) => api.post(`/crypto/${id}/dividends`, data),
};

// Financial Plans
export const financialPlanService = {
    getAll: (params) => api.get('/financial-plans', { params }),
    create: (data) => api.post('/financial-plans', data),
    show: (id) => api.get(`/financial-plans/${id}`),
    update: (id, data) => api.put(`/financial-plans/${id}`, data),
    delete: (id) => api.delete(`/financial-plans/${id}`),
};

// Financial Goals
export const financialGoalService = {
    getAll: (params) => api.get('/financial-goals', { params }),
    create: (data) => api.post('/financial-goals', data),
    update: (id, data) => api.put(`/financial-goals/${id}`, data),
    updateProgress: (id, amount) => api.patch(`/financial-goals/${id}/progress`, { amount }),
    delete: (id) => api.delete(`/financial-goals/${id}`),
};

// Insurance
export const insuranceService = {
    getAll: (params) => api.get('/insurance-plans', { params }),
    create: (data) => api.post('/insurance-plans', data),
    show: (id) => api.get(`/insurance-plans/${id}`),
    update: (id, data) => api.put(`/insurance-plans/${id}`, data),
    delete: (id) => api.delete(`/insurance-plans/${id}`),
    pay: (id, data) => api.post(`/insurance-plans/${id}/pay`, data),
    getPlanPayments: (id, params) => api.get(`/insurance-plans/${id}/payments`, { params }),
};

// Purchases
export const purchaseService = {
    getAll:          (params) => api.get('/purchases', { params }),
    getSummary:      ()       => api.get('/purchases/summary'),
    create:          (data)   => api.post('/purchases', data),
    show:            (id)     => api.get(`/purchases/${id}`),
    update:          (id, data) => api.put(`/purchases/${id}`, data),
    payInstallment:  (id)     => api.patch(`/purchases/${id}/installment`),
    delete:          (id)     => api.delete(`/purchases/${id}`),
};

// MP2
export const mp2Service = {
    calculate: (data) => api.post('/mp2/calculate', data),
    getAll: () => api.get('/mp2-plans'),
    create: (data) => api.post('/mp2-plans', data),
    update: (id, data) => api.put(`/mp2-plans/${id}`, data),
    delete: (id) => api.delete(`/mp2-plans/${id}`),
};

// Budget Tracking (shared / collaborative)
export const budgetTrackingService = {
    get:                  ()         => api.get('/budget-tracking'),
    create:               (data)     => api.post('/budget-tracking', data),
    update:               (data)     => api.put('/budget-tracking', data),
    delete:               ()         => api.delete('/budget-tracking'),
    join:                 (code)     => api.post('/budget-tracking/join', { join_code: code }),
    leave:                ()         => api.post('/budget-tracking/leave'),
    archive:              ()         => api.patch('/budget-tracking/archive'),
    getSummary:           ()         => api.get('/budget-tracking/summary'),
    getHistory:           (params)   => api.get('/budget-tracking/history', { params }),
    regenerateCode:       ()         => api.post('/budget-tracking/code/regenerate'),
    removeMember:         (userId)   => api.delete(`/budget-tracking/members/${userId}`),
    getAllocations:        ()         => api.get('/budget-tracking/allocations'),
    createAllocation:     (data)     => api.post('/budget-tracking/allocations', data),
    updateAllocation:     (id, data) => api.put(`/budget-tracking/allocations/${id}`, data),
    deleteAllocation:     (id)       => api.delete(`/budget-tracking/allocations/${id}`),
    getConsolidated:      ()         => api.get('/budget-tracking/consolidated'),
    getTransactions:      (params)   => api.get('/budget-tracking/transactions', { params }),
    createTransaction:    (data)     => api.post('/budget-tracking/transactions', data),
    updateTransaction:    (id, data) => api.put(`/budget-tracking/transactions/${id}`, data),
    deleteTransaction:    (id)       => api.delete(`/budget-tracking/transactions/${id}`),
};

// Module Transfers (Investment / Stock / Crypto)
export const moduleTransferService = {
    getAll:  (params) => api.get('/module-transfers', { params }),
    create:  (data)   => api.post('/module-transfers', data),
};

// Reports
export const reportService = {
    getIncomeExpense: (params) => api.get('/reports/income-expense', { params }),
    getNetWorth: () => api.get('/reports/net-worth'),
    exportCsv: (params) => api.get('/reports/export/csv', { params, responseType: 'blob', headers: { Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' } }),
    exportPdf: (params) => api.get('/reports/export/pdf', { params, responseType: 'blob' }),
};

// Files
export const fileService = {
    getAll: (params) => api.get('/files', { params }),
    upload: (data) => api.post('/files', data, { headers: { 'Content-Type': 'multipart/form-data' } }),
    delete: (id) => api.delete(`/files/${id}`),
    download: (id) => api.get(`/files/${id}/download`, { responseType: 'blob' }),
};
