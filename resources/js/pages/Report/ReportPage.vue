<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="text-2xl font-bold text-gray-800">Reports</h1>
      <div class="flex gap-2">
        <button @click="exportCsv" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm font-medium flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Export CSV
        </button>
      </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap gap-3 items-end">
      <div>
        <label class="block text-xs text-gray-500 mb-1">From</label>
        <input v-model="filters.date_from" type="date" class="border rounded-lg px-3 py-2 text-sm" />
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">To</label>
        <input v-model="filters.date_to" type="date" class="border rounded-lg px-3 py-2 text-sm" />
      </div>
      <button @click="loadReports" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Generate Report</button>
      <button @click="resetFilters" class="text-gray-400 text-sm px-2 py-2 hover:text-gray-600">Reset</button>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="text-center py-10 text-gray-400">Generating report...</div>

    <template v-else>
      <!-- Net Worth Card -->
      <div v-if="store.netWorth" class="bg-gradient-to-r from-indigo-600 to-blue-500 rounded-xl p-6 text-white shadow-lg">
        <p class="text-indigo-100 text-sm mb-1">Net Worth</p>
        <p class="text-4xl font-bold">{{ formatCurrency(store.netWorth.net_worth) }}</p>
        <div class="grid grid-cols-3 gap-4 mt-4 pt-4 border-t border-indigo-400">
          <div>
            <p class="text-indigo-200 text-xs">Assets</p>
            <p class="font-semibold">{{ formatCurrency(store.netWorth.total_assets) }}</p>
          </div>
          <div>
            <p class="text-indigo-200 text-xs">Liabilities</p>
            <p class="font-semibold">{{ formatCurrency(store.netWorth.total_liabilities) }}</p>
          </div>
          <div>
            <p class="text-indigo-200 text-xs">Investments</p>
            <p class="font-semibold">{{ formatCurrency(store.netWorth.total_investments) }}</p>
          </div>
        </div>
      </div>

      <!-- Income vs Expenses Summary -->
      <div v-if="store.incomeExpenseReport" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-sm text-gray-500 mb-1">Total Income</p>
          <p class="text-2xl font-bold text-green-600">{{ formatCurrency(store.incomeExpenseReport.total_income) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-sm text-gray-500 mb-1">Total Expenses</p>
          <p class="text-2xl font-bold text-red-600">{{ formatCurrency(store.incomeExpenseReport.total_expenses) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-sm text-gray-500 mb-1">Net Savings</p>
          <p class="text-2xl font-bold" :class="store.incomeExpenseReport.net >= 0 ? 'text-blue-600' : 'text-red-600'">
            {{ formatCurrency(store.incomeExpenseReport.net) }}
          </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-sm text-gray-500 mb-1">Savings Rate</p>
          <p class="text-2xl font-bold text-indigo-600">{{ savingsRate }}%</p>
        </div>
      </div>

      <!-- Monthly Breakdown Table -->
      <div v-if="store.incomeExpenseReport?.monthly?.length" class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-semibold text-gray-700 mb-4">Monthly Breakdown</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b">
                <th class="text-left py-2 px-3 text-gray-500 font-medium">Month</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium">Income</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium">Expenses</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium">Net</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium">Savings Rate</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in store.incomeExpenseReport.monthly" :key="row.month" class="border-b last:border-0 hover:bg-gray-50">
                <td class="py-2 px-3 text-gray-700 font-medium">{{ row.month }}</td>
                <td class="py-2 px-3 text-right text-green-600">{{ formatCurrency(row.income) }}</td>
                <td class="py-2 px-3 text-right text-red-600">{{ formatCurrency(row.expenses) }}</td>
                <td class="py-2 px-3 text-right font-semibold" :class="row.net >= 0 ? 'text-blue-600' : 'text-red-600'">
                  {{ formatCurrency(row.net) }}
                </td>
                <td class="py-2 px-3 text-right text-gray-500">
                  {{ row.income > 0 ? ((row.net / row.income) * 100).toFixed(1) : '0.0' }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Category Breakdown -->
      <div v-if="store.incomeExpenseReport?.expense_by_category" class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-semibold text-gray-700 mb-4">Expenses by Category</h2>
        <div v-if="Object.keys(store.incomeExpenseReport.expense_by_category).length === 0" class="text-gray-400 text-sm">No expense data for period</div>
        <div v-for="(amount, category) in store.incomeExpenseReport.expense_by_category" :key="category" class="mb-3">
          <div class="flex justify-between text-sm mb-1">
            <span class="text-gray-600">{{ category || 'Uncategorized' }}</span>
            <span class="font-medium">{{ formatCurrency(amount) }}</span>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-2">
            <div
              class="bg-red-400 h-2 rounded-full"
              :style="{ width: getBarWidth(amount, store.incomeExpenseReport.total_expenses) + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="!store.incomeExpenseReport && !store.loading" class="bg-white rounded-xl shadow-sm p-12 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-lg font-medium">No report data</p>
        <p class="text-sm mt-1">Select a date range and click "Generate Report"</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useReportStore } from '@/stores/report';

const store = useReportStore();

const filters = ref({
  date_from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
  date_to: new Date().toISOString().split('T')[0],
});

function formatCurrency(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function getBarWidth(amount, total) {
  if (!total || total === 0) return 0;
  return Math.min(100, (amount / total) * 100).toFixed(1);
}

const savingsRate = computed(() => {
  const report = store.incomeExpenseReport;
  if (!report || !report.total_income || report.total_income === 0) return '0.0';
  return ((report.net / report.total_income) * 100).toFixed(1);
});

async function loadReports() {
  const params = {};
  if (filters.value.date_from) params.date_from = filters.value.date_from;
  if (filters.value.date_to) params.date_to = filters.value.date_to;
  await store.fetchIncomeExpense(params);
  store.fetchNetWorth();
}

function resetFilters() {
  filters.value = {
    date_from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
    date_to: new Date().toISOString().split('T')[0],
  };
  loadReports();
}

async function exportCsv() {
  const params = {};
  if (filters.value.date_from) params.date_from = filters.value.date_from;
  if (filters.value.date_to) params.date_to = filters.value.date_to;
  try {
    await store.exportCsv(params);
  } catch (e) {
    alert('Export failed. Please try again.');
  }
}

onMounted(() => loadReports());
</script>
