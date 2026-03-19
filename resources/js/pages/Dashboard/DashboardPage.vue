<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>

    <!-- Date filter -->
    <div class="flex gap-3 flex-wrap">
      <input v-model="dateFrom" type="date" class="border rounded-lg px-3 py-2 text-sm" />
      <input v-model="dateTo" type="date" class="border rounded-lg px-3 py-2 text-sm" />
      <button @click="loadData" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Filter</button>
      <button @click="resetFilter" class="text-gray-400 text-sm px-2 py-2 hover:text-gray-600">Reset</button>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="text-center py-10 text-gray-500">Loading...</div>

    <template v-else-if="store.summary">
      <!-- Stat cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard title="Total Income" :value="formatCurrency(store.summary.total_income)" color="green" />
        <StatCard title="Total Expenses" :value="formatCurrency(store.summary.total_expenses)" color="red" />
        <StatCard title="Balance" :value="formatCurrency(store.summary.balance)" color="blue" />
        <StatCard title="Total Debt" :value="formatCurrency(store.summary.total_debt)" color="orange" />
      </div>

      <!-- Second row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Transactions -->
        <div class="bg-white rounded-xl shadow-sm p-5">
          <h2 class="font-semibold text-gray-700 mb-4">Recent Transactions</h2>
          <div v-if="!store.summary.recent_transactions?.length" class="text-gray-400 text-sm">No transactions yet</div>
          <div
            v-for="tx in store.summary.recent_transactions?.slice(0, 8)"
            :key="tx.id + '-' + tx.type"
            class="flex items-center justify-between py-2 border-b last:border-0"
          >
            <div>
              <p class="text-sm font-medium text-gray-700">{{ tx.title }}</p>
              <p class="text-xs text-gray-400">{{ tx.date }} · {{ tx.type }}</p>
            </div>
            <span
              :class="tx.type === 'income' ? 'text-green-600' : 'text-red-600'"
              class="font-semibold text-sm"
            >
              {{ tx.type === 'income' ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
            </span>
          </div>
        </div>

        <!-- Category Breakdown -->
        <div class="bg-white rounded-xl shadow-sm p-5">
          <h2 class="font-semibold text-gray-700 mb-4">Expense Breakdown</h2>
          <div
            v-if="!store.summary.category_breakdown || Object.keys(store.summary.category_breakdown).length === 0"
            class="text-gray-400 text-sm"
          >No expense data</div>
          <div v-for="(amount, category) in store.summary.category_breakdown" :key="category" class="mb-3">
            <div class="flex justify-between text-sm mb-1">
              <span class="text-gray-600">{{ category || 'Uncategorized' }}</span>
              <span class="font-medium">{{ formatCurrency(amount) }}</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
              <div
                class="bg-blue-500 h-2 rounded-full"
                :style="{ width: getBarWidth(amount, store.summary.total_expenses) + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Budget Usage -->
      <div v-if="store.summary.budget_usage?.length" class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-semibold text-gray-700 mb-4">Budget Usage</h2>
        <div v-for="b in store.summary.budget_usage" :key="b.name" class="mb-4">
          <div class="flex justify-between text-sm mb-1">
            <span class="text-gray-600 font-medium">{{ b.name }}</span>
            <span :class="b.percentage >= 100 ? 'text-red-600' : 'text-gray-600'">
              {{ formatCurrency(b.spent) }} / {{ formatCurrency(b.amount) }} ({{ b.percentage }}%)
            </span>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-2.5">
            <div
              class="h-2.5 rounded-full transition-all"
              :class="b.percentage >= 100 ? 'bg-red-500' : b.percentage >= 80 ? 'bg-yellow-500' : 'bg-green-500'"
              :style="{ width: Math.min(100, b.percentage) + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Monthly Overview -->
      <div v-if="store.summary.monthly_data?.length" class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-semibold text-gray-700 mb-4">Monthly Overview</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b">
                <th class="text-left py-2 text-gray-500 font-medium">Month</th>
                <th class="text-right py-2 text-gray-500 font-medium">Income</th>
                <th class="text-right py-2 text-gray-500 font-medium">Expenses</th>
                <th class="text-right py-2 text-gray-500 font-medium">Net</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in store.summary.monthly_data" :key="m.month" class="border-b last:border-0 hover:bg-gray-50">
                <td class="py-2 text-gray-700">{{ m.month }}</td>
                <td class="py-2 text-right text-green-600">{{ formatCurrency(m.income) }}</td>
                <td class="py-2 text-right text-red-600">{{ formatCurrency(m.expenses) }}</td>
                <td class="py-2 text-right" :class="m.net >= 0 ? 'text-blue-600' : 'text-red-600'">{{ formatCurrency(m.net) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- Empty state -->
    <div v-else-if="!store.loading" class="text-center py-16 text-gray-400">
      <p class="text-lg">No data available</p>
      <p class="text-sm mt-1">Add some income or expenses to get started.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useDashboardStore } from '@/stores/dashboard';
import StatCard from '@/components/common/StatCard.vue';

const store = useDashboardStore();
const dateFrom = ref('');
const dateTo = ref('');

function formatCurrency(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function getBarWidth(amount, total) {
  if (!total || total === 0) return 0;
  return Math.min(100, (amount / total) * 100).toFixed(1);
}

function loadData() {
  const params = {};
  if (dateFrom.value) params.date_from = dateFrom.value;
  if (dateTo.value) params.date_to = dateTo.value;
  store.fetchSummary(params);
}

function resetFilter() {
  dateFrom.value = '';
  dateTo.value = '';
  store.fetchSummary();
}

onMounted(() => store.fetchSummary());
</script>
