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

// Module Transfers (Income / Saving)
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
