<template>
  <div class="space-y-6">

    <!-- ── Header & Date Filter ─────────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
      <div class="flex gap-2 flex-wrap items-center">
        <input v-model="dateFrom" type="date" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
        <span class="text-gray-400 text-sm">to</span>
        <input v-model="dateTo" type="date" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
        <button @click="loadData" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition">Filter</button>
        <button @click="resetFilter" class="text-gray-400 text-sm px-3 py-2 hover:text-gray-600 transition">Reset</button>
      </div>
    </div>

    <!-- ── Loading ──────────────────────────────────────────────────────── -->
    <div v-if="store.loading" class="flex items-center justify-center py-16 text-gray-400 gap-3">
      <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
      </svg>
      <span>Loading dashboard…</span>
    </div>

    <template v-else-if="store.summary">

      <!-- ── 1. Overview Stat Cards ───────────────────────────────────────── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard title="Total Income"    :value="fmt(store.summary.total_income)"    color="green"  subtitle="Period income" />
        <StatCard title="Total Expenses"  :value="fmt(store.summary.total_expenses)"  color="red"    subtitle="Period expenses" />
        <StatCard
          title="Balance"
          :value="fmt(store.summary.balance)"
          :color="store.summary.balance >= 0 ? 'blue' : 'red'"
          subtitle="Income − Expenses − Payments"
        />
        <StatCard title="Total Debt"      :value="fmt(store.summary.total_debt)"      color="orange" subtitle="Outstanding balance" />
      </div>

      <!-- ── 2. Month & Year Reports ─────────────────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Month Report -->
        <div class="bg-white rounded-xl shadow-sm p-5">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-700">Month Report</h2>
            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">{{ store.summary.month_report?.period }}</span>
          </div>
          <div v-if="store.summary.month_report" class="space-y-2">
            <ReportRow label="Income"           :value="fmt(store.summary.month_report.total_income)"      color="green" />
            <ReportRow label="Expenses"         :value="fmt(store.summary.month_report.total_expenses)"    color="red" />
            <ReportRow label="Debt Payments"    :value="fmt(store.summary.month_report.debt_payments)"     color="orange" />
            <div class="border-t pt-2 mt-2">
              <ReportRow label="Balance"        :value="fmt(store.summary.month_report.balance)"           :color="store.summary.month_report.balance >= 0 ? 'blue' : 'red'" bold />
              <ReportRow label="Balance Remaining" :value="fmt(store.summary.month_report.balance_remaining)" color="blue" bold />
            </div>
            <ReportRow label="Total Debt"       :value="fmt(store.summary.month_report.total_debt)"        color="orange" />
            <ReportRow label="Total Investments":value="fmt(store.summary.month_report.total_investments)" color="purple" />
            <div class="mt-3 flex items-center gap-2">
              <div class="flex-1 bg-gray-100 rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all"
                  :class="savingsColor(store.summary.month_report.savings_rate_pct)"
                  :style="{ width: Math.max(0, Math.min(100, store.summary.month_report.savings_rate_pct)) + '%' }"
                ></div>
              </div>
              <span class="text-xs font-medium text-gray-600">{{ store.summary.month_report.savings_rate_pct }}% saved</span>
            </div>

            <!-- ── Socioeconomic Class ──────────────────────────────────── -->
            <div v-if="store.summary.month_report.socioeconomic_class" class="mt-4 pt-4 border-t">
              <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Socioeconomic Class</p>

              <!-- Current class badge -->
              <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span
                  class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
                  :class="secoClass(store.summary.month_report.socioeconomic_class.color)"
                >
                  {{ store.summary.month_report.socioeconomic_class.label }}
                </span>
                <span class="text-xs text-gray-400">{{ store.summary.month_report.socioeconomic_class.range }}</span>
              </div>

              <!-- Average basis -->
              <p class="text-xs text-gray-500 mb-1">
                Avg monthly income:
                <span class="font-semibold text-gray-700">{{ fmt(store.summary.month_report.socioeconomic_class.avg_monthly_income) }}</span>
                <span class="text-gray-400">
                  ({{ store.summary.month_report.socioeconomic_class.months_count }}
                  {{ store.summary.month_report.socioeconomic_class.months_count === 1 ? 'month' : 'months' }} with records)
                </span>
              </p>

              <!-- Gap to next bracket -->
              <p
                v-if="store.summary.month_report.socioeconomic_class.gap_to_next !== null"
                class="text-xs text-gray-400 mb-3"
              >
                {{ fmt(store.summary.month_report.socioeconomic_class.gap_to_next) }} more avg/month to reach
                <span class="font-medium text-gray-600">{{ store.summary.month_report.socioeconomic_class.next_class }}</span>
              </p>
              <p v-else class="text-xs text-gray-400 mb-3">You are in the highest income bracket.</p>

              <!-- All tiers ladder -->
              <div class="space-y-1">
                <div
                  v-for="tier in store.summary.month_report.socioeconomic_class.all_tiers"
                  :key="tier.key"
                  class="flex items-center gap-2 text-xs rounded-md px-2 py-1 transition-colors"
                  :class="tier.key === store.summary.month_report.socioeconomic_class.key
                    ? secoRowActive(tier.color)
                    : 'text-gray-400 hover:bg-gray-50'"
                >
                  <span
                    class="w-2 h-2 rounded-full shrink-0"
                    :class="tier.key === store.summary.month_report.socioeconomic_class.key
                      ? secoDot(tier.color)
                      : 'bg-gray-200'"
                  ></span>
                  <span class="flex-1 font-medium">{{ tier.label }}</span>
                  <span>{{ tier.range }}</span>
                </div>
              </div>
              <p class="text-[10px] text-gray-300 mt-2 text-right">Based on PSA Philippines income thresholds</p>
            </div>
            <!-- ── /Socioeconomic Class ─────────────────────────────────── -->

          </div>
        </div>

        <!-- Year Report -->
        <div class="bg-white rounded-xl shadow-sm p-5">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-700">Year Report</h2>
            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">{{ store.summary.year_report?.period }}</span>
          </div>
          <div v-if="store.summary.year_report" class="space-y-2">
            <ReportRow label="Income"           :value="fmt(store.summary.year_report.total_income)"      color="green" />
            <ReportRow label="Expenses"         :value="fmt(store.summary.year_report.total_expenses)"    color="red" />
            <ReportRow label="Debt Payments"    :value="fmt(store.summary.year_report.debt_payments)"     color="orange" />
            <div class="border-t pt-2 mt-2">
              <ReportRow label="Balance"        :value="fmt(store.summary.year_report.balance)"           :color="store.summary.year_report.balance >= 0 ? 'blue' : 'red'" bold />
              <ReportRow label="Balance Remaining" :value="fmt(store.summary.year_report.balance_remaining)" color="blue" bold />
            </div>
            <ReportRow label="Total Debt"       :value="fmt(store.summary.year_report.total_debt)"        color="orange" />
            <ReportRow label="Total Investments":value="fmt(store.summary.year_report.total_investments)" color="purple" />
            <div class="mt-3 flex items-center gap-2">
              <div class="flex-1 bg-gray-100 rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all"
                  :class="savingsColor(store.summary.year_report.savings_rate_pct)"
                  :style="{ width: Math.max(0, Math.min(100, store.summary.year_report.savings_rate_pct)) + '%' }"
                ></div>
              </div>
              <span class="text-xs font-medium text-gray-600">{{ store.summary.year_report.savings_rate_pct }}% saved</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── 3. Transactions & Expense Breakdown ─────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Recent Transactions -->
        <div class="bg-white rounded-xl shadow-sm p-5 flex flex-col">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-700">Transactions</h2>
            <span class="text-xs text-gray-400">
              {{ recentTxs.length }} of {{ store.summary.recent_transactions?.total ?? 0 }}
            </span>
          </div>

          <div v-if="!recentTxs.length" class="text-gray-400 text-sm py-4 text-center">No transactions yet</div>

          <div class="flex-1 divide-y divide-gray-50">
            <div
              v-for="tx in recentTxs"
              :key="`${tx.type}-${tx.id}`"
              class="flex items-center justify-between py-2.5"
            >
              <div class="flex items-center gap-3">
                <!-- type icon -->
                <span
                  class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                  :class="txBadgeClass(tx.type)"
                >
                  {{ txIcon(tx.type) }}
                </span>
                <div>
                  <p class="text-sm font-medium text-gray-800 leading-tight">{{ tx.title }}</p>
                  <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(tx.date) }} · <span class="capitalize">{{ tx.type.replace('_', ' ') }}</span>{{ tx.category ? ' · ' + tx.category : '' }}</p>
                </div>
              </div>
              <span
                class="text-sm font-semibold shrink-0 ml-4"
                :class="tx.type === 'income' ? 'text-green-600' : 'text-red-600'"
              >
                {{ tx.type === 'income' ? '+' : '-' }}{{ fmt(tx.amount) }}
              </span>
            </div>
          </div>

          <!-- Show More -->
          <div v-if="store.summary.recent_transactions?.has_more" class="mt-4 pt-3 border-t">
            <router-link
              to="/transactions"
              class="block text-center text-sm text-blue-600 hover:text-blue-700 font-medium"
            >
              Show all transactions →
            </router-link>
          </div>
        </div>

        <!-- Expense Breakdown -->
        <div class="bg-white rounded-xl shadow-sm p-5">
          <h2 class="font-semibold text-gray-700 mb-4">Expense Breakdown</h2>

          <div v-if="!expenseBreakdown.length" class="text-gray-400 text-sm py-4 text-center">
            No expense data for this period
          </div>

          <div v-for="item in expenseBreakdown" :key="item.id ?? item.name" class="mb-4">
            <div class="flex items-center justify-between text-sm mb-1">
              <div class="flex items-center gap-2">
                <span
                  class="w-3 h-3 rounded-full shrink-0"
                  :style="{ backgroundColor: item.color || '#6B7280' }"
                ></span>
                <span class="text-gray-700 font-medium">{{ item.name || 'Uncategorized' }}</span>
                <span class="text-xs text-gray-400">({{ item.count }})</span>
              </div>
              <span class="font-semibold text-gray-800">{{ fmt(item.total) }}</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
              <div
                class="h-2 rounded-full transition-all"
                :style="{
                  width: barWidth(item.total, store.summary.total_expenses) + '%',
                  backgroundColor: item.color || '#3B82F6'
                }"
              ></div>
            </div>
            <p class="text-xs text-gray-400 mt-0.5 text-right">
              {{ barWidth(item.total, store.summary.total_expenses) }}% of total expenses
            </p>
          </div>
        </div>
      </div>

      <!-- ── 4. Budget List ───────────────────────────────────────────────── -->
      <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-700">Budget List</h2>
          <router-link to="/budget" class="text-sm text-blue-600 hover:text-blue-700">Manage →</router-link>
        </div>

        <div v-if="!budgetList.length" class="text-gray-400 text-sm py-4 text-center">
          No budgets created yet
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="b in budgetList"
            :key="b.id"
            class="border border-gray-100 rounded-lg p-4 hover:bg-gray-50 transition"
          >
            <div class="flex items-start justify-between gap-3 mb-2">
              <div>
                <div class="flex items-center gap-2">
                  <p class="font-medium text-gray-800">{{ b.name }}</p>
                  <span v-if="b.category" class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ b.category }}</span>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">{{ b.period }} · {{ formatDate(b.start_date) }} – {{ formatDate(b.end_date) }}</p>
              </div>
              <span class="text-xs font-semibold px-2 py-1 rounded-full shrink-0" :class="statusClass(b.status)">
                {{ statusLabel(b.status) }}
              </span>
            </div>

            <!-- Progress bar -->
            <div class="w-full bg-gray-100 rounded-full h-2 mb-2">
              <div
                class="h-2 rounded-full transition-all"
                :class="usageBarColor(b.usage_pct, b.alert_threshold)"
                :style="{ width: Math.min(100, b.usage_pct) + '%' }"
              ></div>
            </div>

            <!-- Figures -->
            <div class="grid grid-cols-3 gap-2 text-center text-xs">
              <div>
                <p class="text-gray-400">Allocated</p>
                <p class="font-semibold text-gray-700">{{ fmt(b.allocated_amount) }}</p>
              </div>
              <div>
                <p class="text-gray-400">Spent</p>
                <p class="font-semibold text-red-600">{{ fmt(b.spent_amount) }}</p>
              </div>
              <div>
                <p class="text-gray-400">Remaining</p>
                <p class="font-semibold" :class="b.remaining_amount > 0 ? 'text-green-600' : 'text-red-600'">
                  {{ fmt(b.remaining_amount) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── 5. Debt List ─────────────────────────────────────────────────── -->
      <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-700">List of Debts</h2>
          <router-link to="/debts" class="text-sm text-blue-600 hover:text-blue-700">Manage →</router-link>
        </div>

        <div v-if="!debtList.length" class="text-gray-400 text-sm py-4 text-center">
          No active debts
        </div>

        <div v-else class="overflow-x-auto -mx-5">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b bg-gray-50">
                <th class="text-left py-2 px-5 text-gray-500 font-medium">Lender</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium">Original</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium">Paid</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium">Remaining</th>
                <th class="text-center py-2 px-3 text-gray-500 font-medium">Due Date</th>
                <th class="text-center py-2 px-5 text-gray-500 font-medium">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="d in debtList" :key="d.id" class="hover:bg-gray-50 transition">
                <td class="py-3 px-5">
                  <p class="font-medium text-gray-800">{{ d.lender_name }}</p>
                  <p class="text-xs text-gray-400 capitalize">{{ d.type }}</p>
                </td>
                <td class="py-3 px-3 text-right text-gray-700">{{ fmt(d.original_amount) }}</td>
                <td class="py-3 px-3 text-right text-green-600 font-medium">{{ fmt(d.total_paid) }}</td>
                <td class="py-3 px-3 text-right text-red-600 font-semibold">{{ fmt(d.remaining_balance) }}</td>
                <td class="py-3 px-3 text-center text-gray-500 text-xs">{{ formatDate(d.due_date) }}</td>
                <td class="py-3 px-5 text-center">
                  <span class="text-xs font-semibold px-2 py-1 rounded-full" :class="debtStatusClass(d.status)">
                    {{ d.status }}
                  </span>
                </td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-gray-200 bg-gray-50">
              <tr>
                <td class="py-2 px-5 text-sm font-semibold text-gray-700">Total</td>
                <td class="py-2 px-3 text-right text-sm font-semibold text-gray-700">{{ fmt(debtTotals.original) }}</td>
                <td class="py-2 px-3 text-right text-sm font-semibold text-green-600">{{ fmt(debtTotals.paid) }}</td>
                <td class="py-2 px-3 text-right text-sm font-semibold text-red-600">{{ fmt(debtTotals.remaining) }}</td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- ── 6. Monthly Overview ──────────────────────────────────────────── -->
      <div v-if="store.summary.monthly_data?.length" class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-semibold text-gray-700 mb-4">12-Month Overview</h2>
        <div class="overflow-x-auto -mx-5">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b bg-gray-50">
                <th class="text-left py-2 px-5 text-gray-500 font-medium">Month</th>
                <th class="text-right py-2 px-5 text-gray-500 font-medium">Income</th>
                <th class="text-right py-2 px-5 text-gray-500 font-medium">Expenses</th>
                <th class="text-right py-2 px-5 text-gray-500 font-medium">Net</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr
                v-for="m in store.summary.monthly_data"
                :key="m.month"
                class="hover:bg-gray-50 transition"
                :class="{ 'font-medium bg-blue-50': isCurrentMonth(m.month) }"
              >
                <td class="py-2.5 px-5 text-gray-700">
                  {{ m.label }}
                  <span v-if="isCurrentMonth(m.month)" class="ml-1 text-xs text-blue-500">(current)</span>
                </td>
                <td class="py-2.5 px-5 text-right text-green-600">{{ fmt(m.income) }}</td>
                <td class="py-2.5 px-5 text-right text-red-600">{{ fmt(m.expense) }}</td>
                <td class="py-2.5 px-5 text-right font-semibold" :class="m.net >= 0 ? 'text-blue-600' : 'text-red-600'">
                  {{ m.net >= 0 ? '+' : '' }}{{ fmt(m.net) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </template>

    <!-- ── Empty state ─────────────────────────────────────────────────────── -->
    <div v-else-if="!store.loading" class="text-center py-16 text-gray-400">
      <p class="text-lg font-medium">No data available</p>
      <p class="text-sm mt-1">Add some income or expenses to get started.</p>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useDashboardStore } from '@/stores/dashboard';
import { formatDate } from '@/utils/date';
import StatCard  from '@/components/common/StatCard.vue';
import ReportRow from '@/components/common/ReportRow.vue';

// ── Store ─────────────────────────────────────────────────────────────────
const store = useDashboardStore();
const dateFrom = ref('');
const dateTo   = ref('');

// ── Computed helpers ──────────────────────────────────────────────────────
const recentTxs = computed(() =>
  store.summary?.recent_transactions?.data ?? []
);

const expenseBreakdown = computed(() =>
  store.summary?.expense_breakdown ?? store.summary?.category_breakdown ?? []
);

const budgetList = computed(() =>
  store.summary?.budget_list ?? []
);

const debtList = computed(() =>
  store.summary?.debt_list ?? []
);

const debtTotals = computed(() => ({
  original:  debtList.value.reduce((s, d) => s + (d.original_amount  ?? 0), 0),
  paid:      debtList.value.reduce((s, d) => s + (d.total_paid        ?? 0), 0),
  remaining: debtList.value.reduce((s, d) => s + (d.remaining_balance ?? 0), 0),
}));

// ── Formatters ────────────────────────────────────────────────────────────
function fmt(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

// formatDate imported from @/utils/date

function barWidth(amount, total) {
  if (!total || total === 0) return 0;
  return Math.min(100, ((amount / total) * 100)).toFixed(1);
}

function isCurrentMonth(month) {
  const now = new Date();
  return month === `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

// ── Status helpers ────────────────────────────────────────────────────────
function statusClass(s) {
  return {
    on_track:    'bg-green-100 text-green-700',
    warning:     'bg-yellow-100 text-yellow-700',
    over_budget: 'bg-red-100 text-red-700',
    over_income: 'bg-red-200 text-red-800',
  }[s] ?? 'bg-gray-100 text-gray-600';
}

function statusLabel(s) {
  return {
    on_track:    'On Track',
    warning:     'Warning',
    over_budget: 'Over Budget',
    over_income: 'Over Income',
  }[s] ?? s;
}

function usageBarColor(pct, threshold = 80) {
  if (pct > 100)       return 'bg-red-500';
  if (pct >= threshold) return 'bg-yellow-400';
  return 'bg-green-500';
}

function debtStatusClass(s) {
  return {
    active:  'bg-blue-100 text-blue-700',
    overdue: 'bg-red-100 text-red-700',
    paid:    'bg-green-100 text-green-700',
  }[s] ?? 'bg-gray-100 text-gray-600';
}

function savingsColor(pct) {
  if (pct >= 20) return 'bg-green-500';
  if (pct >= 10) return 'bg-yellow-400';
  if (pct > 0)   return 'bg-orange-400';
  return 'bg-red-500';
}

// ── Socioeconomic class helpers ───────────────────────────────────────────
const secoColorMap = {
  red:    { badge: 'bg-red-100 text-red-700',       active: 'bg-red-50 text-red-700 font-semibold',       dot: 'bg-red-500' },
  orange: { badge: 'bg-orange-100 text-orange-700', active: 'bg-orange-50 text-orange-700 font-semibold', dot: 'bg-orange-500' },
  amber:  { badge: 'bg-amber-100 text-amber-700',   active: 'bg-amber-50 text-amber-700 font-semibold',   dot: 'bg-amber-500' },
  blue:   { badge: 'bg-blue-100 text-blue-700',     active: 'bg-blue-50 text-blue-700 font-semibold',     dot: 'bg-blue-500' },
  indigo: { badge: 'bg-indigo-100 text-indigo-700', active: 'bg-indigo-50 text-indigo-700 font-semibold', dot: 'bg-indigo-500' },
  violet: { badge: 'bg-violet-100 text-violet-700', active: 'bg-violet-50 text-violet-700 font-semibold', dot: 'bg-violet-500' },
  green:  { badge: 'bg-green-100 text-green-700',   active: 'bg-green-50 text-green-700 font-semibold',   dot: 'bg-green-500' },
};

function secoClass(color)      { return secoColorMap[color]?.badge      ?? 'bg-gray-100 text-gray-700'; }
function secoRowActive(color)  { return secoColorMap[color]?.active     ?? 'bg-gray-50 text-gray-700 font-semibold'; }
function secoDot(color)        { return secoColorMap[color]?.dot        ?? 'bg-gray-400'; }

// ── Transaction type helpers ───────────────────────────────────────────────
function txIcon(type) {
  return { income: '↑', expense: '↓', debt_payment: '⊘' }[type] ?? '·';
}

function txBadgeClass(type) {
  return {
    income:       'bg-green-100 text-green-700',
    expense:      'bg-red-100 text-red-700',
    debt_payment: 'bg-orange-100 text-orange-700',
  }[type] ?? 'bg-gray-100 text-gray-600';
}

// ── Actions ───────────────────────────────────────────────────────────────
function loadData() {
  const params = {};
  if (dateFrom.value) params.date_from = dateFrom.value;
  if (dateTo.value)   params.date_to   = dateTo.value;
  store.fetchSummary(params);
}

function resetFilter() {
  dateFrom.value = '';
  dateTo.value   = '';
  store.fetchSummary();
}

onMounted(() => store.fetchSummary());
</script>
