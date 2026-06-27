<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h1 class="text-2xl font-bold text-gray-800">Reports</h1>
      <button @click="exportCsv" :disabled="exporting" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 disabled:opacity-60 text-sm font-medium flex items-center gap-2">
        <svg v-if="!exporting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
        {{ exporting ? 'Exporting...' : 'Export Excel' }}
      </button>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-xl shadow-sm p-4 space-y-3">
      <div class="flex flex-wrap gap-2">
        <button v-for="preset in datePresets" :key="preset.label"
          @click="applyPreset(preset)"
          class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors"
          :class="activePreset === preset.label
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400 hover:text-blue-600'">
          {{ preset.label }}
        </button>
      </div>
      <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-end sm:gap-3">
        <div>
          <label class="block text-xs text-gray-500 mb-1">From</label>
          <input v-model="filters.date_from" type="date" @change="activePreset = null" class="border rounded-lg px-3 py-2 text-sm w-full" />
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1">To</label>
          <input v-model="filters.date_to" type="date" @change="activePreset = null" class="border rounded-lg px-3 py-2 text-sm w-full" />
        </div>
        <div class="col-span-2 flex gap-2 sm:contents">
          <button @click="loadReports" class="flex-1 sm:flex-none bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Generate</button>
          <button @click="resetFilters" class="text-gray-400 text-sm px-3 py-2 hover:text-gray-600">Reset</button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="text-center py-10 text-gray-400">Generating report...</div>

    <template v-else-if="store.incomeExpenseReport">

      <!-- ── Net Worth Banner ───────────────────────────────────────────── -->
      <div v-if="store.netWorth" class="bg-gradient-to-r from-indigo-600 to-blue-500 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-start justify-between flex-wrap gap-4">
          <div>
            <p class="text-indigo-100 text-sm mb-1">Net Worth</p>
            <p class="text-4xl font-bold">{{ fmt(store.netWorth.net_worth) }}</p>
          </div>
          <div class="text-right">
            <p class="text-indigo-200 text-xs mb-1">Available Cash</p>
            <p class="text-xl font-semibold">{{ fmt(store.netWorth.available_cash) }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-4 pt-4 border-t border-indigo-400">
          <div>
            <p class="text-indigo-200 text-xs">Available Cash</p>
            <p class="font-semibold">{{ fmt(store.netWorth.available_cash) }}</p>
          </div>
          <div>
            <p class="text-indigo-200 text-xs">Total Assets</p>
            <p class="font-semibold">{{ fmt(store.netWorth.total_assets) }}</p>
          </div>
          <div>
            <p class="text-indigo-200 text-xs">Liabilities</p>
            <p class="font-semibold">{{ fmt(store.netWorth.total_liabilities) }}</p>
          </div>
        </div>
      </div>

      <!-- ── Summary Stat Cards ─────────────────────────────────────────── -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 lg:gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4 border-t-2 border-green-400">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Income</p>
          <p class="text-xl font-bold text-green-600">{{ fmt(rpt.total_income) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">Period inflow</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-t-2 border-red-400">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Expenses</p>
          <p class="text-xl font-bold text-red-500">{{ fmt(rpt.total_expenses) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">{{ rpt.expense_ratio_pct?.toFixed(1) }}% of income</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-t-2 border-orange-400">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Debt Payments</p>
          <p class="text-xl font-bold text-orange-500">{{ fmt(rpt.total_debt_payments) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">Personal debt</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-t-2 border-violet-400">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">CC Installments</p>
          <p class="text-xl font-bold text-violet-500">{{ fmt(rpt.total_purchase_payments) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">Credit card</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-t-2 border-pink-400">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Cash Purchases</p>
          <p class="text-xl font-bold text-pink-500">{{ fmt(rpt.total_cash_purchases) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">Direct spend</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-t-2 border-teal-400">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Biz Received</p>
          <p class="text-xl font-bold text-teal-600">{{ fmt(rpt.business_debt_received) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">Business debt in</p>
        </div>
      </div>

      <!-- ── Key Metrics Row ─────────────────────────────────────────────── -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 lg:gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4" :class="rpt.net >= 0 ? 'border-blue-500' : 'border-red-500'">
          <p class="text-xs text-gray-500 mb-1">Net</p>
          <p class="text-2xl font-bold" :class="rpt.net >= 0 ? 'text-blue-600' : 'text-red-600'">{{ fmt(rpt.net) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-indigo-400">
          <p class="text-xs text-gray-500 mb-1">Total Outflow</p>
          <p class="text-2xl font-bold text-indigo-600">{{ fmt(rpt.total_outflow) }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ pct(rpt.outflow_ratio_pct) }} of income</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-400">
          <p class="text-xs text-gray-500 mb-1">Savings Rate</p>
          <p class="text-2xl font-bold text-green-600">{{ pct(rpt.savings_rate_pct) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-amber-400">
          <p class="text-xs text-gray-500 mb-1">Daily Burn Rate</p>
          <p class="text-2xl font-bold text-amber-600">{{ fmt(rpt.daily_burn_rate) }}</p>
          <p v-if="rpt.savings_runway_days !== null" class="text-xs text-gray-400 mt-1">{{ rpt.savings_runway_days }} days runway</p>
          <p v-else class="text-xs text-gray-400 mt-1">No runway data</p>
        </div>
      </div>

      <!-- ── Expense Breakdown ───────────────────────────────────────────── -->
      <div v-if="rpt.expense_breakdown?.length" class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h2 class="font-semibold text-gray-700">Expense Breakdown</h2>
            <p class="text-xs text-gray-400 mt-0.5">
              {{ rpt.expense_breakdown.length }} {{ rpt.expense_breakdown.length === 1 ? 'category' : 'categories' }} ·
              {{ rpt.expense_breakdown.reduce((s, i) => s + i.count, 0) }} expenses
            </p>
          </div>
          <span class="text-sm font-semibold text-red-500">{{ fmt(rpt.total_expenses) }}</span>
        </div>

        <!-- Donut chart + legend -->
        <div class="flex items-center gap-5 mb-5">
          <DonutChart :segments="expenseSegments" :size="120">
            <span class="text-[10px] text-gray-400 leading-tight text-center">Spend<br>split</span>
          </DonutChart>
          <div class="flex-1 space-y-1.5 min-w-0">
            <div v-for="(seg, i) in expenseSegments.slice(0, 6)" :key="i" class="flex items-center gap-2 text-xs">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ background: seg.color }"></span>
              <span class="truncate text-gray-600">{{ seg.label }}</span>
              <span class="ml-auto shrink-0 font-medium text-gray-700">{{ seg.pct.toFixed(1) }}%</span>
            </div>
            <p v-if="expenseSegments.length > 6" class="text-[10px] text-gray-400 pl-4">
              +{{ expenseSegments.length - 6 }} more
            </p>
          </div>
        </div>

        <!-- Breakdown list -->
        <div class="space-y-3">
          <div v-for="(cat, i) in rpt.expense_breakdown" :key="cat.id" class="group">
            <div class="flex items-center gap-3">
              <span class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                :style="{ backgroundColor: cat.color }">{{ i + 1 }}</span>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <span class="text-sm text-gray-700 font-medium truncate">{{ cat.name }}</span>
                  <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="text-xs text-gray-400">{{ cat.count }} txn{{ cat.count !== 1 ? 's' : '' }}</span>
                    <span class="text-sm font-semibold text-gray-800">{{ fmt(cat.total) }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                      :style="{ backgroundColor: cat.color + '22', color: cat.color }">{{ cat.pct }}%</span>
                  </div>
                </div>
                <div class="mt-1.5 w-full bg-gray-100 rounded-full h-1.5">
                  <div class="h-1.5 rounded-full transition-all duration-500"
                    :style="{ width: cat.pct + '%', backgroundColor: cat.color }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-if="rpt.expense_breakdown[0]" class="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-400 flex justify-between">
          <span>Highest: <span class="font-medium text-gray-600">{{ rpt.expense_breakdown[0].name }}</span></span>
          <span>{{ rpt.expense_breakdown[0].pct }}% of expenses</span>
        </div>
      </div>

      <!-- ── Monthly Trend Table ─────────────────────────────────────────── -->
      <div v-if="rpt.monthly_trend?.length" class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-700">Monthly Trend</h2>
          <div class="flex items-center gap-3 text-xs text-gray-400">
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-green-400 inline-block"></span> Income</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-red-400 inline-block"></span> Outflow</span>
          </div>
        </div>

        <!-- Visual bar chart strip -->
        <div class="space-y-1.5 mb-5">
          <div v-for="m in rpt.monthly_trend" :key="m.month + '-bar'"
            class="flex items-center gap-2 text-xs">
            <span class="w-10 shrink-0 text-right text-gray-500">{{ m.label?.slice(0, 3) }}</span>
            <div class="flex-1 flex gap-0.5 h-4 items-center">
              <div class="h-full rounded-sm bg-green-400 transition-all duration-500 min-w-[2px]"
                :style="{ width: monthlyBarWidth(m.income) + '%' }"
                :title="`Income: ${fmt(m.income)}`" />
              <div class="h-full rounded-sm bg-red-400 transition-all duration-500 min-w-[2px]"
                :style="{ width: monthlyBarWidth(m.total_outflow) + '%' }"
                :title="`Outflow: ${fmt(m.total_outflow)}`" />
            </div>
            <span class="shrink-0 w-20 text-right font-semibold text-xs px-1.5 py-0.5 rounded-full"
              :class="m.net >= 0 ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-600'">
              {{ m.net >= 0 ? '+' : '' }}{{ fmtShort(m.net) }}
            </span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b bg-gray-50">
                <th class="text-left py-2 px-3 text-gray-500 font-medium whitespace-nowrap">Month</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium whitespace-nowrap">Income</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium whitespace-nowrap">Expenses</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium whitespace-nowrap">Debt Pmts</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium whitespace-nowrap">CC Inst.</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium whitespace-nowrap">Cash Purch.</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium whitespace-nowrap">Outflow</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium whitespace-nowrap">Net</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium whitespace-nowrap">Savings %</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rpt.monthly_trend" :key="row.month"
                class="border-b last:border-0 hover:bg-gray-50">
                <td class="py-2 px-3 text-gray-700 font-medium whitespace-nowrap">{{ row.label }}</td>
                <td class="py-2 px-3 text-right text-green-600 whitespace-nowrap">{{ fmt(row.income) }}</td>
                <td class="py-2 px-3 text-right text-red-500 whitespace-nowrap">{{ fmt(row.expense) }}</td>
                <td class="py-2 px-3 text-right text-orange-500 whitespace-nowrap">{{ fmt(row.debt_payments) }}</td>
                <td class="py-2 px-3 text-right text-purple-500 whitespace-nowrap">{{ fmt(row.purchase_payments) }}</td>
                <td class="py-2 px-3 text-right text-pink-500 whitespace-nowrap">{{ fmt(row.cash_purchases) }}</td>
                <td class="py-2 px-3 text-right text-indigo-600 font-medium whitespace-nowrap">{{ fmt(row.total_outflow) }}</td>
                <td class="py-2 px-3 text-right font-semibold whitespace-nowrap" :class="row.net >= 0 ? 'text-blue-600' : 'text-red-600'">
                  {{ fmt(row.net) }}
                </td>
                <td class="py-2 px-3 text-right whitespace-nowrap">
                  <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                    :class="row.savings_rate_pct >= 20 ? 'bg-green-100 text-green-700'
                          : row.savings_rate_pct >= 0  ? 'bg-yellow-100 text-yellow-700'
                          : 'bg-red-100 text-red-700'">
                    {{ row.savings_rate_pct?.toFixed(1) }}%
                  </span>
                </td>
              </tr>
            </tbody>
            <!-- Totals footer -->
            <tfoot>
              <tr class="border-t-2 border-gray-300 bg-gray-50 font-semibold">
                <td class="py-2 px-3 text-gray-700">Total</td>
                <td class="py-2 px-3 text-right text-green-600">{{ fmt(rpt.total_income) }}</td>
                <td class="py-2 px-3 text-right text-red-500">{{ fmt(rpt.total_expenses) }}</td>
                <td class="py-2 px-3 text-right text-orange-500">{{ fmt(rpt.total_debt_payments) }}</td>
                <td class="py-2 px-3 text-right text-purple-500">{{ fmt(rpt.total_purchase_payments) }}</td>
                <td class="py-2 px-3 text-right text-pink-500">{{ fmt(rpt.total_cash_purchases) }}</td>
                <td class="py-2 px-3 text-right text-indigo-600">{{ fmt(rpt.total_outflow) }}</td>
                <td class="py-2 px-3 text-right" :class="rpt.net >= 0 ? 'text-blue-600' : 'text-red-600'">{{ fmt(rpt.net) }}</td>
                <td class="py-2 px-3 text-right text-gray-500">{{ pct(rpt.savings_rate_pct) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- ── Income / Expense Transaction Lists ─────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Income list -->
        <div v-if="rpt.incomes?.length" class="bg-white rounded-xl shadow-sm p-5">
          <h2 class="font-semibold text-gray-700 mb-3 flex items-center justify-between">
            <span>Income Transactions</span>
            <span class="text-xs text-gray-400">{{ rpt.incomes.length }} records</span>
          </h2>
          <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
            <div v-for="inc in rpt.incomes" :key="inc.id"
              class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
              <div>
                <p class="text-sm font-medium text-gray-700">{{ inc.source || inc.description || 'Income' }}</p>
                <p class="text-xs text-gray-400">{{ inc.received_at }}</p>
              </div>
              <span class="text-sm font-semibold text-green-600">{{ fmt(inc.amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Expense list -->
        <div v-if="rpt.expenses?.length" class="bg-white rounded-xl shadow-sm p-5">
          <h2 class="font-semibold text-gray-700 mb-3 flex items-center justify-between">
            <span>Expense Transactions</span>
            <span class="text-xs text-gray-400">{{ rpt.expenses.length }} records</span>
          </h2>
          <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
            <div v-for="exp in rpt.expenses" :key="exp.id"
              class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
              <div class="flex items-center gap-2 min-w-0">
                <span v-if="exp.category?.color" class="w-2 h-2 rounded-full flex-shrink-0"
                  :style="{ backgroundColor: exp.category.color }"></span>
                <div class="min-w-0">
                  <p class="text-sm font-medium text-gray-700 truncate">{{ exp.description || 'Expense' }}</p>
                  <p class="text-xs text-gray-400">{{ exp.category?.name || 'Uncategorized' }} · {{ exp.spent_at }}</p>
                </div>
              </div>
              <span class="text-sm font-semibold text-red-500 flex-shrink-0 ml-2">{{ fmt(exp.amount) }}</span>
            </div>
          </div>
        </div>
      </div>

    </template>

    <!-- Empty state -->
    <div v-else-if="!store.loading" class="bg-white rounded-xl shadow-sm p-12 text-center text-gray-400">
      <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-lg font-medium">No report data</p>
      <p class="text-sm mt-1">Select a date range and click "Generate Report"</p>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useReportStore } from '@/stores/report';
import DonutChart from '@/components/charts/DonutChart.vue';

const store = useReportStore();
const exporting = ref(false);

// ── Convenience alias ──────────────────────────────────────────────────────
const rpt = computed(() => store.incomeExpenseReport || {});

// ── Date presets ──────────────────────────────────────────────────────────
const activePreset = ref('This Month');

const datePresets = [
  {
    label: 'This Month',
    get() {
      const now = new Date();
      return {
        from: new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0],
        to:   new Date().toISOString().split('T')[0],
      };
    },
  },
  {
    label: 'Last Month',
    get() {
      const now = new Date();
      const first = new Date(now.getFullYear(), now.getMonth() - 1, 1);
      const last  = new Date(now.getFullYear(), now.getMonth(), 0);
      return { from: first.toISOString().split('T')[0], to: last.toISOString().split('T')[0] };
    },
  },
  {
    label: 'This Year',
    get() {
      const y = new Date().getFullYear();
      return { from: `${y}-01-01`, to: new Date().toISOString().split('T')[0] };
    },
  },
  {
    label: 'Last Year',
    get() {
      const y = new Date().getFullYear() - 1;
      return { from: `${y}-01-01`, to: `${y}-12-31` };
    },
  },
  {
    label: 'All Time',
    get() {
      return { from: '2000-01-01', to: new Date().toISOString().split('T')[0] };
    },
  },
];

function applyPreset(preset) {
  const range = preset.get();
  filters.value.date_from = range.from;
  filters.value.date_to   = range.to;
  activePreset.value = preset.label;
  loadReports();
}

// ── Filters ────────────────────────────────────────────────────────────────
const thisMonth = datePresets[0].get();
const filters = ref({ date_from: thisMonth.from, date_to: thisMonth.to });

// ── Helpers ────────────────────────────────────────────────────────────────
function fmt(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function pct(val) {
  return Number(val || 0).toFixed(1) + '%';
}

function fmtShort(val) {
  const n = Number(val || 0);
  if (Math.abs(n) >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
  if (Math.abs(n) >= 1_000)     return (n / 1_000).toFixed(1) + 'K';
  return n.toFixed(0);
}

const expenseSegments = computed(() => {
  const total = rpt.value.total_expenses ?? 0;
  if (!total) return [];
  return (rpt.value.expense_breakdown ?? []).map(item => ({
    label: item.name || 'Uncategorized',
    value: item.total,
    pct:   total > 0 ? (item.total / total) * 100 : 0,
    color: item.color || '#6B7280',
  }));
});

const monthlyMax = computed(() => {
  const rows = rpt.value.monthly_trend ?? [];
  return Math.max(...rows.map(m => m.income ?? 0), ...rows.map(m => m.total_outflow ?? 0), 1);
});

function monthlyBarWidth(val) {
  return Math.min(50, ((val ?? 0) / monthlyMax.value) * 50);
}

// ── Actions ────────────────────────────────────────────────────────────────
async function loadReports() {
  const params = {};
  if (filters.value.date_from) params.date_from = filters.value.date_from;
  if (filters.value.date_to)   params.date_to   = filters.value.date_to;
  await store.fetchIncomeExpense(params);
  store.fetchNetWorth();
}

function resetFilters() {
  const range = datePresets[0].get();
  filters.value = { date_from: range.from, date_to: range.to };
  activePreset.value = 'This Month';
  loadReports();
}

async function exportCsv() {
  const params = {};
  if (filters.value.date_from) params.date_from = filters.value.date_from;
  if (filters.value.date_to)   params.date_to   = filters.value.date_to;
  exporting.value = true;
  try {
    await store.exportCsv(params);
  } catch {
    alert('Export failed. Please try again.');
  } finally {
    exporting.value = false;
  }
}

onMounted(() => loadReports());
</script>
