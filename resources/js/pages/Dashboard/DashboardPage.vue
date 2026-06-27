<template>
  <div class="space-y-6">

    <!-- ── Header & Date Filter ─────────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Dashboard</h1>
      <div class="flex gap-2 flex-wrap items-center">
        <input v-model="dateFrom" type="date" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none flex-1 sm:flex-none" />
        <span class="text-gray-400 text-sm">to</span>
        <input v-model="dateTo" type="date" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none flex-1 sm:flex-none" />
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
      <!-- Row 1: Core cash flow -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <!-- Income -->
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5 border-t-2 border-green-400">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Income</p>
          <p class="text-lg lg:text-xl font-bold text-green-600">{{ fmt(store.summary.total_income) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">
            +{{ fmt(store.summary.total_business_debt_received) }}
            <span class="text-gray-300">biz received</span>
          </p>
        </div>
        <!-- Expenses + outflows -->
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5 border-t-2 border-red-400">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Outgoing</p>
          <p class="text-lg lg:text-xl font-bold text-red-600">{{ fmt(store.summary.total_outgoing) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">
            Exp + debt + CC + cash
          </p>
        </div>
        <!-- Balance -->
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5 border-t-2"
          :class="(transferSummary.income?.available_balance ?? store.summary.balance) >= 0 ? 'border-blue-400' : 'border-red-500'">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Available Balance</p>
          <p class="text-lg lg:text-xl font-bold"
            :class="(transferSummary.income?.available_balance ?? store.summary.balance) >= 0 ? 'text-blue-600' : 'text-red-600'">
            {{ fmt(transferSummary.income?.available_balance ?? store.summary.balance) }}
          </p>
          <div class="mt-2 w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
              :class="(transferSummary.income?.available_balance ?? store.summary.balance) >= 0 ? 'bg-blue-400' : 'bg-red-400'"
              :style="{ width: Math.min(100, Math.abs(transferSummary.income?.available_balance ?? store.summary.balance) / Math.max(store.summary.total_income, 1) * 100) + '%' }"
            />
          </div>
          <p class="text-[10px] text-gray-400 mt-1">After outflows &amp; transfers</p>
        </div>
        <!-- Savings -->
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5 border-t-2"
          :class="store.summary.total_savings > 0 ? 'border-emerald-400' : 'border-gray-300'">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Savings</p>
          <p class="text-lg lg:text-xl font-bold" :class="store.summary.total_savings > 0 ? 'text-emerald-600' : 'text-gray-500'">
            {{ fmt(store.summary.total_savings) }}
          </p>
          <p class="text-[10px] text-gray-400 mt-1">
            <span v-if="(transferSummary.saving?.available_balance ?? 0) > 0" class="text-teal-500 font-medium">
              🏦 {{ fmt(transferSummary.saving?.available_balance ?? 0) }} in saving fund
            </span>
            <span v-else>Income surplus</span>
          </p>
        </div>
      </div>

      <!-- Row 2: Obligations -->
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 lg:gap-4">
        <!-- Outstanding Debt -->
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5 border-t-2 border-orange-400">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Outstanding Debt</p>
          <p class="text-lg lg:text-xl font-bold text-orange-600">{{ fmt(store.summary.total_debt) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">Unpaid balance</p>
        </div>
        <!-- CC Installments -->
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5 border-t-2 border-violet-400">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">CC Installments</p>
          <p class="text-lg lg:text-xl font-bold text-violet-600">{{ fmt(store.summary.total_purchase_payments) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">Credit card monthly</p>
        </div>
        <!-- Cash Purchases -->
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5 border-t-2 border-gray-300">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Cash Purchases</p>
          <p class="text-lg lg:text-xl font-bold text-gray-700">{{ fmt(store.summary.total_cash_purchases) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">
            +{{ fmt(store.summary.total_debt_payments) }}
            <span class="text-gray-300">debt pmts</span>
          </p>
        </div>
      </div>

      <!-- ── 2. Month & Year Reports ─────────────────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Month Report -->
        <div class="bg-white rounded-xl shadow-sm p-5">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-700">Month Report</h2>
            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">{{ store.summary.month_report?.period }}</span>
          </div>
          <div v-if="store.summary.month_report" class="space-y-4">

            <!-- Inflows -->
            <div>
              <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold mb-1.5">Inflows</p>
              <div class="space-y-1.5">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Income</span>
                  <span class="font-semibold text-green-600">{{ fmt(store.summary.month_report.total_income) }}</span>
                </div>
                <div v-if="store.summary.month_report.business_debt_received > 0" class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Biz Debt Received</span>
                  <span class="font-medium text-teal-600">{{ fmt(store.summary.month_report.business_debt_received) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm border-t pt-1.5">
                  <span class="font-semibold text-gray-700">Total Inflow</span>
                  <span class="font-bold text-green-700">{{ fmt((store.summary.month_report.total_income ?? 0) + (store.summary.month_report.business_debt_received ?? 0)) }}</span>
                </div>
              </div>
            </div>

            <!-- Outflows with proportion bars -->
            <div>
              <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold mb-1.5">Outflows</p>
              <div class="space-y-2">
                <div v-for="(row, idx) in monthOutflows(store.summary.month_report)" :key="idx">
                  <div class="flex items-center justify-between text-sm mb-0.5">
                    <span class="text-gray-600">{{ row.label }}</span>
                    <span class="font-medium" :class="row.color">{{ fmt(row.value) }}</span>
                  </div>
                  <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500" :class="row.barColor"
                      :style="{ width: monthOutflowPct(row.value, store.summary.month_report) + '%' }" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Balance & savings rate -->
            <div class="border-t pt-3 space-y-2">
              <div class="flex items-center justify-between text-sm">
                <span class="font-semibold text-gray-700">Balance</span>
                <span class="font-bold text-lg" :class="store.summary.month_report.balance >= 0 ? 'text-blue-600' : 'text-red-600'">
                  {{ fmt(store.summary.month_report.balance) }}
                </span>
              </div>
              <div v-if="store.summary.month_report.balance_remaining !== undefined" class="flex items-center justify-between text-sm">
                <span class="text-gray-500">Balance Remaining</span>
                <span class="font-semibold text-blue-600">{{ fmt(store.summary.month_report.balance_remaining) }}</span>
              </div>
              <!-- Savings rate bar -->
              <div class="flex items-center gap-2 pt-1">
                <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                  <div class="h-2 rounded-full transition-all duration-500"
                    :class="savingsColor(store.summary.month_report.savings_rate_pct)"
                    :style="{ width: Math.max(0, Math.min(100, store.summary.month_report.savings_rate_pct)) + '%' }"
                  />
                </div>
                <span class="text-xs font-semibold w-16 text-right shrink-0"
                  :class="store.summary.month_report.savings_rate_pct >= 20 ? 'text-green-600' : store.summary.month_report.savings_rate_pct >= 10 ? 'text-amber-600' : 'text-red-500'">
                  {{ store.summary.month_report.savings_rate_pct }}% saved
                </span>
              </div>
            </div>

            <!-- Quick stats strip -->
            <div class="grid grid-cols-3 gap-2 pt-1">
              <div class="bg-orange-50 rounded-lg px-3 py-2">
                <p class="text-[10px] text-gray-400 uppercase mb-0.5">Total Debt</p>
                <p class="text-sm font-bold text-orange-600">{{ fmt(store.summary.month_report.total_debt) }}</p>
              </div>
              <div class="bg-teal-50 rounded-lg px-3 py-2">
                <p class="text-[10px] text-gray-400 uppercase mb-0.5">Saving Fund</p>
                <p class="text-sm font-bold text-teal-600">{{ fmt(transferSummary.saving?.available_balance ?? 0) }}</p>
              </div>
            </div>

            <!-- ── Socioeconomic Class ──────────────────────────────────── -->
            <div v-if="store.summary.month_report.socioeconomic_class" class="mt-2 pt-4 border-t">
              <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Socioeconomic Class</p>
              <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
                  :class="secoClass(store.summary.month_report.socioeconomic_class.color)">
                  {{ store.summary.month_report.socioeconomic_class.label }}
                </span>
                <span class="text-xs text-gray-400">{{ store.summary.month_report.socioeconomic_class.range }}</span>
              </div>
              <p class="text-xs text-gray-500 mb-1">
                Avg monthly income:
                <span class="font-semibold text-gray-700">{{ fmt(store.summary.month_report.socioeconomic_class.avg_monthly_income) }}</span>
                <span class="text-gray-400">
                  ({{ store.summary.month_report.socioeconomic_class.months_count }}
                  {{ store.summary.month_report.socioeconomic_class.months_count === 1 ? 'month' : 'months' }} with records)
                </span>
              </p>
              <p v-if="store.summary.month_report.socioeconomic_class.gap_to_next !== null" class="text-xs text-gray-400 mb-3">
                {{ fmt(store.summary.month_report.socioeconomic_class.gap_to_next) }} more avg/month to reach
                <span class="font-medium text-gray-600">{{ store.summary.month_report.socioeconomic_class.next_class }}</span>
              </p>
              <p v-else class="text-xs text-gray-400 mb-3">You are in the highest income bracket.</p>
              <div class="space-y-1">
                <div v-for="tier in store.summary.month_report.socioeconomic_class.all_tiers" :key="tier.key"
                  class="flex items-center gap-2 text-xs rounded-md px-2 py-1 transition-colors"
                  :class="tier.key === store.summary.month_report.socioeconomic_class.key ? secoRowActive(tier.color) : 'text-gray-400 hover:bg-gray-50'">
                  <span class="w-2 h-2 rounded-full shrink-0"
                    :class="tier.key === store.summary.month_report.socioeconomic_class.key ? secoDot(tier.color) : 'bg-gray-200'" />
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
          <div v-if="store.summary.year_report" class="space-y-4">

            <!-- Inflows -->
            <div>
              <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold mb-1.5">Inflows</p>
              <div class="space-y-1.5">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Income</span>
                  <span class="font-semibold text-green-600">{{ fmt(store.summary.year_report.total_income) }}</span>
                </div>
                <div v-if="store.summary.year_report.business_debt_received > 0" class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Biz Debt Received</span>
                  <span class="font-medium text-teal-600">{{ fmt(store.summary.year_report.business_debt_received) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm border-t pt-1.5">
                  <span class="font-semibold text-gray-700">Total Inflow</span>
                  <span class="font-bold text-green-700">{{ fmt((store.summary.year_report.total_income ?? 0) + (store.summary.year_report.business_debt_received ?? 0)) }}</span>
                </div>
              </div>
            </div>

            <!-- Outflows with proportion bars -->
            <div>
              <p class="text-[10px] text-gray-400 uppercase tracking-wide font-semibold mb-1.5">Outflows</p>
              <div class="space-y-2">
                <div v-for="(row, idx) in monthOutflows(store.summary.year_report)" :key="idx">
                  <div class="flex items-center justify-between text-sm mb-0.5">
                    <span class="text-gray-600">{{ row.label }}</span>
                    <span class="font-medium" :class="row.color">{{ fmt(row.value) }}</span>
                  </div>
                  <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500" :class="row.barColor"
                      :style="{ width: monthOutflowPct(row.value, store.summary.year_report) + '%' }" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Balance & savings rate -->
            <div class="border-t pt-3 space-y-2">
              <div class="flex items-center justify-between text-sm">
                <span class="font-semibold text-gray-700">Balance</span>
                <span class="font-bold text-lg" :class="store.summary.year_report.balance >= 0 ? 'text-blue-600' : 'text-red-600'">
                  {{ fmt(store.summary.year_report.balance) }}
                </span>
              </div>
              <div v-if="store.summary.year_report.balance_remaining !== undefined" class="flex items-center justify-between text-sm">
                <span class="text-gray-500">Balance Remaining</span>
                <span class="font-semibold text-blue-600">{{ fmt(store.summary.year_report.balance_remaining) }}</span>
              </div>
              <div class="flex items-center gap-2 pt-1">
                <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                  <div class="h-2 rounded-full transition-all duration-500"
                    :class="savingsColor(store.summary.year_report.savings_rate_pct)"
                    :style="{ width: Math.max(0, Math.min(100, store.summary.year_report.savings_rate_pct)) + '%' }"
                  />
                </div>
                <span class="text-xs font-semibold w-16 text-right shrink-0"
                  :class="store.summary.year_report.savings_rate_pct >= 20 ? 'text-green-600' : store.summary.year_report.savings_rate_pct >= 10 ? 'text-amber-600' : 'text-red-500'">
                  {{ store.summary.year_report.savings_rate_pct }}% saved
                </span>
              </div>
            </div>

            <!-- Quick stats strip -->
            <div class="grid grid-cols-2 gap-2 pt-1">
              <div class="bg-orange-50 rounded-lg px-3 py-2">
                <p class="text-[10px] text-gray-400 uppercase mb-0.5">Total Debt</p>
                <p class="text-sm font-bold text-orange-600">{{ fmt(store.summary.year_report.total_debt) }}</p>
              </div>
              <div class="bg-teal-50 rounded-lg px-3 py-2">
                <p class="text-[10px] text-gray-400 uppercase mb-0.5">Saving Fund</p>
                <p class="text-sm font-bold text-teal-600">{{ fmt(transferSummary.saving?.available_balance ?? 0) }}</p>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ── 3. Income, Expense & Other Transactions ─────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Income Transactions -->
        <div class="bg-white rounded-xl shadow-sm p-5 flex flex-col">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="font-semibold text-gray-700">Income Transactions</h2>
              <p class="text-xs text-gray-400 mt-0.5">{{ incomeTxs.length }} record{{ incomeTxs.length !== 1 ? 's' : '' }}</p>
            </div>
            <span class="text-sm font-bold text-green-600">{{ fmt(store.summary.total_income) }}</span>
          </div>

          <div v-if="!incomeTxs.length" class="flex flex-col items-center justify-center py-10 text-gray-400 gap-2 flex-1">
            <span class="text-3xl">📭</span>
            <span class="text-sm">No income for this period</span>
          </div>

          <div v-else class="flex-1 divide-y divide-gray-50">
            <div v-for="tx in pagedIncomeTxs" :key="tx.id"
              class="flex items-center justify-between py-2.5">
              <div class="flex items-center gap-3 min-w-0">
                <span class="w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold shrink-0">↑</span>
                <div class="min-w-0">
                  <p class="text-sm font-medium text-gray-800 leading-tight truncate">{{ tx.title }}</p>
                  <p class="text-xs text-gray-400 mt-0.5">
                    <span v-if="tx.source" class="mr-1 text-gray-500">{{ tx.source }} ·</span>
                    {{ formatDate(tx.date) }}
                  </p>
                </div>
              </div>
              <span class="text-sm font-semibold text-green-600 shrink-0 ml-3">+{{ fmt(tx.amount) }}</span>
            </div>
          </div>

          <div class="mt-3 pt-3 border-t space-y-2">
            <!-- Pagination -->
            <div v-if="incomeTotalPages > 1" class="flex items-center justify-between text-xs">
              <button @click="incomePage--" :disabled="incomePage === 1"
                class="px-2 py-1 rounded border text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">← Prev</button>
              <span class="text-gray-400">{{ incomePage }} / {{ incomeTotalPages }}</span>
              <button @click="incomePage++" :disabled="incomePage === incomeTotalPages"
                class="px-2 py-1 rounded border text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">Next →</button>
            </div>
            <!-- Total -->
            <div v-if="incomeTxs.length" class="flex items-center justify-between text-xs text-gray-400">
              <span>Period total</span>
              <span class="font-semibold text-green-600">{{ fmt(incomeTxs.reduce((s, t) => s + t.amount, 0)) }}</span>
            </div>
          </div>
        </div>

        <!-- Expense Transactions -->
        <div class="bg-white rounded-xl shadow-sm p-5 flex flex-col">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="font-semibold text-gray-700">Expense Transactions</h2>
              <p class="text-xs text-gray-400 mt-0.5">{{ expenseTxs.length }} record{{ expenseTxs.length !== 1 ? 's' : '' }}</p>
            </div>
            <span class="text-sm font-bold text-red-600">{{ fmt(store.summary.total_expenses) }}</span>
          </div>

          <div v-if="!expenseTxs.length" class="flex flex-col items-center justify-center py-10 text-gray-400 gap-2 flex-1">
            <span class="text-3xl">📭</span>
            <span class="text-sm">No expenses for this period</span>
          </div>

          <div v-else class="flex-1 divide-y divide-gray-50">
            <div v-for="tx in pagedExpenseTxs" :key="tx.id"
              class="flex items-center justify-between py-2.5">
              <div class="flex items-center gap-3 min-w-0">
                <span class="w-2 h-8 rounded-full shrink-0" :style="{ backgroundColor: tx.category_color }"></span>
                <div class="min-w-0">
                  <p class="text-sm font-medium text-gray-800 leading-tight truncate">{{ tx.description }}</p>
                  <p class="text-xs mt-0.5 flex items-center gap-1">
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                      :style="{ backgroundColor: tx.category_color + '22', color: tx.category_color }">
                      {{ tx.category }}
                    </span>
                    <span class="text-gray-400">{{ formatDate(tx.date) }}</span>
                  </p>
                </div>
              </div>
              <span class="text-sm font-semibold text-red-500 shrink-0 ml-3">−{{ fmt(tx.amount) }}</span>
            </div>
          </div>

          <div class="mt-3 pt-3 border-t space-y-2">
            <div v-if="expenseTotalPages > 1" class="flex items-center justify-between text-xs">
              <button @click="expensePage--" :disabled="expensePage === 1"
                class="px-2 py-1 rounded border text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">← Prev</button>
              <span class="text-gray-400">{{ expensePage }} / {{ expenseTotalPages }}</span>
              <button @click="expensePage++" :disabled="expensePage === expenseTotalPages"
                class="px-2 py-1 rounded border text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">Next →</button>
            </div>
            <div v-if="expenseTxs.length" class="flex items-center justify-between text-xs text-gray-400">
              <span>Period total</span>
              <span class="font-semibold text-red-600">{{ fmt(expenseTxs.reduce((s, t) => s + t.amount, 0)) }}</span>
            </div>
          </div>
        </div>

        <!-- Other Transactions -->
        <div class="bg-white rounded-xl shadow-sm p-5 flex flex-col">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="font-semibold text-gray-700">Other Transactions</h2>
              <p class="text-xs text-gray-400 mt-0.5">{{ otherTxs.length }} record{{ otherTxs.length !== 1 ? 's' : '' }}</p>
            </div>
            <span class="text-sm font-bold text-orange-600">{{ fmt(otherTxs.reduce((s, t) => s + t.amount, 0)) }}</span>
          </div>

          <div v-if="!otherTxs.length" class="flex flex-col items-center justify-center py-10 text-gray-400 gap-2 flex-1">
            <span class="text-3xl">📭</span>
            <span class="text-sm">No other transactions for this period</span>
          </div>

          <div v-else class="flex-1 divide-y divide-gray-50">
            <div v-for="tx in pagedOtherTxs" :key="`${tx.type}-${tx.id}`"
              class="flex items-center justify-between py-2.5">
              <div class="flex items-center gap-3 min-w-0">
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                  :class="otherTxBadgeClass(tx.type)">
                  {{ otherTxIcon(tx.type) }}
                </span>
                <div class="min-w-0">
                  <p class="text-sm font-medium text-gray-800 leading-tight truncate">{{ tx.title }}</p>
                  <p class="text-xs mt-0.5 flex items-center gap-1">
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                      :class="otherTxLabelClass(tx.type)">
                      {{ tx.label }}
                    </span>
                    <span class="text-gray-400">{{ formatDate(tx.date) }}</span>
                  </p>
                </div>
              </div>
              <span class="text-sm font-semibold shrink-0 ml-3"
                :class="tx.type === 'business_debt_received' || tx.type === 'module_transfer_back' ? 'text-green-600' : 'text-orange-600'">
                {{ tx.type === 'business_debt_received' || tx.type === 'module_transfer_back' ? '+' : '−' }}{{ fmt(tx.amount) }}
              </span>
            </div>
          </div>

          <div class="mt-3 pt-3 border-t space-y-2">
            <div v-if="otherTotalPages > 1" class="flex items-center justify-between text-xs">
              <button @click="otherPage--" :disabled="otherPage === 1"
                class="px-2 py-1 rounded border text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">← Prev</button>
              <span class="text-gray-400">{{ otherPage }} / {{ otherTotalPages }}</span>
              <button @click="otherPage++" :disabled="otherPage === otherTotalPages"
                class="px-2 py-1 rounded border text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed">Next →</button>
            </div>
            <div v-if="otherTxs.length" class="flex items-center justify-between text-xs text-gray-400">
              <span>Period total</span>
              <span class="font-semibold text-orange-600">{{ fmt(otherTxs.reduce((s, t) => s + t.amount, 0)) }}</span>
            </div>
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
                <p class="text-xs text-gray-400 mt-0.5">{{ b.period }} · from {{ formatDate(b.start_date) }}</p>
              </div>
              <span class="text-xs font-semibold px-2 py-1 rounded-full shrink-0" :class="statusClass(b.status)">
                {{ statusLabel(b.status) }}
              </span>
            </div>

            <!-- Progress bar -->
            <div class="w-full bg-gray-100 rounded-full h-2 mb-2">
              <div
                class="h-2 rounded-full transition-all"
                :class="usageBarColor(b.usage_pct)"
                :style="{ width: Math.min(100, b.usage_pct) + '%' }"
              ></div>
            </div>

            <!-- Figures -->
            <div class="grid grid-cols-3 gap-2 text-center text-xs">
              <div>
                <p class="text-gray-400">Total Budget</p>
                <p class="font-semibold text-blue-600">{{ fmt(b.total_budget) }}</p>
              </div>
              <div>
                <p class="text-gray-400">Spent</p>
                <p class="font-semibold text-red-600">{{ fmt(b.spent_amount) }}</p>
              </div>
              <div>
                <p class="text-gray-400">Remaining</p>
                <p class="font-semibold" :class="b.remaining_amount >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ fmt(b.remaining_amount) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── 5. Purchase List (CC with remaining balance) ───────────────── -->
      <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-700">Purchases with Remaining Balance</h2>
          <router-link to="/purchases" class="text-sm text-blue-600 hover:text-blue-700">Manage →</router-link>
        </div>

        <div v-if="!purchaseList.length" class="text-gray-400 text-sm py-4 text-center">
          No credit card purchases with outstanding balance
        </div>

        <div v-else class="overflow-x-auto -mx-5">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b bg-gray-50">
                <th class="text-left py-2 px-5 text-gray-500 font-medium">Item</th>
                <th class="text-center py-2 px-3 text-gray-500 font-medium">Mode</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium">Total</th>
                <th class="text-right py-2 px-3 text-gray-500 font-medium">Paid</th>
                <th class="text-right py-2 px-5 text-gray-500 font-medium">Unpaid</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="p in purchaseList" :key="p.id" class="hover:bg-gray-50 transition">
                <td class="py-3 px-5">
                  <p class="font-medium text-gray-800">{{ p.title }}</p>
                  <p class="text-xs text-gray-400">{{ formatDate(p.purchase_date) }} · {{ p.installments_paid }}/{{ p.installment_count }} months paid</p>
                </td>
                <td class="py-3 px-3 text-center">
                  <span class="text-xs font-medium px-2 py-1 rounded-full bg-purple-100 text-purple-700">
                    Credit Card
                  </span>
                </td>
                <td class="py-3 px-3 text-right text-gray-700 font-medium">{{ fmt(p.total_amount) }}</td>
                <td class="py-3 px-3 text-right text-green-600 font-medium">{{ fmt(p.paid) }}</td>
                <td class="py-3 px-5 text-right text-red-600 font-semibold">{{ fmt(p.unpaid) }}</td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-gray-200 bg-gray-50">
              <tr>
                <td class="py-2 px-5 text-sm font-semibold text-gray-700">Total</td>
                <td class="py-2 px-3"></td>
                <td class="py-2 px-3 text-right text-sm font-semibold text-gray-700">{{ fmt(purchaseTotals.total) }}</td>
                <td class="py-2 px-3 text-right text-sm font-semibold text-green-600">{{ fmt(purchaseTotals.paid) }}</td>
                <td class="py-2 px-5 text-right text-sm font-semibold text-red-600">{{ fmt(purchaseTotals.unpaid) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- ── 7. Debt List ─────────────────────────────────────────────────── -->
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

      <!-- ── 6. Module Transfers ─────────────────────────────────────────── -->
      <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-700">Fund Balances</h2>
          <button
            @click="openTransferModal"
            class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition font-medium"
          >
            ⇄ Transfer Funds
          </button>
        </div>

        <!-- ── All 5 fund cards (Income + 4 modules) ── -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">

          <!-- Income fund card -->
          <div class="rounded-xl border-2 border-green-200 bg-green-50/40 p-4">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="text-base leading-none">💵</span>
                <span class="text-sm font-bold text-gray-700">Income</span>
              </div>
              <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-600">
                source
              </span>
            </div>
            <div class="space-y-1.5 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">Sent Out</span>
                <span class="font-medium text-red-500">{{ fmt(transferSummary.income?.total_outgoing ?? 0) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Received Back</span>
                <span class="font-medium text-green-600">{{ fmt(transferSummary.income?.total_transferred ?? 0) }}</span>
              </div>
            </div>
            <div class="mt-3 pt-3 border-t border-dashed border-gray-200">
              <div class="flex justify-between items-center">
                <span class="text-sm font-semibold text-gray-600">Available</span>
                <span class="text-base font-bold"
                  :class="(transferSummary.income?.available_balance ?? store.summary.balance) >= 0 ? 'text-green-700' : 'text-red-600'">
                  {{ fmt(transferSummary.income?.available_balance ?? store.summary.balance) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Saving Fund card -->
          <div
            class="rounded-xl border-2 border-teal-200 bg-teal-50/40 p-4 cursor-pointer transition hover:shadow-md"
            @click="openTransferModalFor('saving')"
            title="Click to transfer into saving fund"
          >
            <!-- Card header -->
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="text-base leading-none">🏦</span>
                <span class="text-sm font-bold text-gray-700">Saving</span>
              </div>
              <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-teal-100 text-teal-600">
                {{ transferSummary.saving?.count ?? 0 }}×
              </span>
            </div>

            <!-- Fund balance rows -->
            <div class="space-y-1.5 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">↓ Total In</span>
                <span class="font-medium text-green-600">{{ fmt(transferSummary.saving?.total_transferred ?? 0) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">↑ Total Out</span>
                <span class="font-medium text-red-500">{{ fmt(transferSummary.saving?.total_outgoing ?? 0) }}</span>
              </div>
            </div>

            <!-- Available Funds -->
            <div class="mt-3 pt-3 border-t border-dashed border-gray-200">
              <div class="flex justify-between items-center">
                <span class="text-sm font-semibold text-gray-600">Available</span>
                <span class="text-base font-bold"
                  :class="(transferSummary.saving?.available_balance ?? 0) >= 0 ? 'text-blue-600' : 'text-red-600'">
                  {{ fmt(transferSummary.saving?.available_balance ?? 0) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Recent transfer log ── -->
        <div v-if="transferLogs.length" class="mt-5 border-t pt-4">
          <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Recent Transfers</p>
          <div class="space-y-2">
            <div v-for="log in transferLogs" :key="log.id"
              class="flex items-center justify-between text-sm py-1.5 border-b border-gray-50 last:border-0">
              <!-- Flow arrow -->
              <div class="flex items-center gap-2 min-w-0">
                <span class="shrink-0 text-xs font-bold px-2 py-0.5 rounded-full"
                  :class="fundBadgeClass(log.from)">
                  {{ fundLabel(log.from) }}
                </span>
                <span class="text-gray-400 shrink-0">→</span>
                <span class="shrink-0 text-xs font-bold px-2 py-0.5 rounded-full"
                  :class="fundBadgeClass(log.to)">
                  {{ fundLabel(log.to) }}
                </span>
                <span v-if="log.note" class="text-gray-400 truncate text-xs hidden sm:inline">· {{ log.note }}</span>
              </div>
              <!-- Amount + date -->
              <div class="flex items-center gap-3 shrink-0 ml-2">
                <div class="text-right">
                  <div class="font-semibold text-gray-800">{{ fmt(log.amount) }}</div>
                  <div v-if="log.fee > 0" class="text-[10px] text-gray-400">+{{ fmt(log.fee) }} fee</div>
                </div>
                <span class="text-xs text-gray-400 hidden sm:inline">{{ log.date }}</span>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="mt-4 text-center text-sm text-gray-400 py-3">
          No transfers yet. Click <strong>⇄ Transfer Funds</strong> to move money between funds.
        </div>
      </div>

      <!-- ── 8. Monthly Overview ──────────────────────────────────────────── -->
      <div v-if="store.summary.monthly_data?.length" class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-700">12-Month Overview</h2>
          <div class="flex items-center gap-3 text-xs text-gray-400">
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-green-400 inline-block"></span> Income</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-red-400 inline-block"></span> Outflow</span>
          </div>
        </div>

        <!-- Visual bar chart strip -->
        <div class="space-y-1.5 mb-5">
          <div v-for="m in store.summary.monthly_data" :key="m.month + '-bar'"
            class="flex items-center gap-2 text-xs"
            :class="{ 'font-semibold': isCurrentMonth(m.month) }">
            <span class="w-10 shrink-0 text-right text-gray-500">{{ m.label?.slice(0, 3) }}</span>
            <div class="flex-1 flex gap-0.5 h-4 items-center">
              <!-- Income bar -->
              <div class="h-full rounded-sm bg-green-400 transition-all duration-500 min-w-[2px]"
                :style="{ width: monthlyBarWidth(m.income) + '%' }"
                :title="`Income: ${fmt(m.income)}`"
              />
              <!-- Outflow bar -->
              <div class="h-full rounded-sm bg-red-400 transition-all duration-500 min-w-[2px]"
                :style="{ width: monthlyBarWidth(m.total_outflow) + '%' }"
                :title="`Outflow: ${fmt(m.total_outflow)}`"
              />
            </div>
            <!-- Net pill -->
            <span class="shrink-0 w-20 text-right font-semibold text-xs px-1.5 py-0.5 rounded-full"
              :class="m.net >= 0 ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-600'">
              {{ m.net >= 0 ? '+' : '' }}{{ fmtShort(m.net) }}
            </span>
          </div>
        </div>

        <!-- Detailed table -->
        <div class="overflow-x-auto -mx-5">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b bg-gray-50">
                <th class="text-left py-2 px-5 text-gray-500 font-medium">Month</th>
                <th class="text-right py-2 px-4 text-gray-500 font-medium">Income</th>
                <th class="text-right py-2 px-4 text-gray-500 font-medium">Expenses</th>
                <th class="text-right py-2 px-4 text-gray-500 font-medium hidden lg:table-cell">CC Instal.</th>
                <th class="text-right py-2 px-4 text-gray-500 font-medium hidden lg:table-cell">Cash Purch.</th>
                <th class="text-right py-2 px-4 text-gray-500 font-medium">Outflow</th>
                <th class="text-right py-2 px-5 text-gray-500 font-medium">Net</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr
                v-for="m in store.summary.monthly_data"
                :key="m.month"
                class="hover:bg-gray-50 transition"
                :class="isCurrentMonth(m.month) ? 'bg-blue-50 font-medium' : ''"
              >
                <td class="py-2.5 px-5 text-gray-700">
                  {{ m.label }}
                  <span v-if="isCurrentMonth(m.month)" class="ml-1 text-[10px] bg-blue-200 text-blue-700 px-1.5 py-0.5 rounded-full font-semibold">now</span>
                </td>
                <td class="py-2.5 px-4 text-right">
                  <span class="text-green-600">{{ fmt(m.income) }}</span>
                </td>
                <td class="py-2.5 px-4 text-right text-red-500">{{ fmt(m.expense) }}</td>
                <td class="py-2.5 px-4 text-right text-violet-600 hidden lg:table-cell">{{ fmt(m.purchase_payments) }}</td>
                <td class="py-2.5 px-4 text-right text-purple-600 hidden lg:table-cell">{{ fmt(m.cash_purchases) }}</td>
                <td class="py-2.5 px-4 text-right text-red-600 font-medium">{{ fmt(m.total_outflow) }}</td>
                <td class="py-2.5 px-5 text-right">
                  <span class="font-semibold px-2 py-0.5 rounded-full text-xs"
                    :class="m.net >= 0 ? 'bg-blue-50 text-blue-700' : 'bg-red-50 text-red-700'">
                    {{ m.net >= 0 ? '+' : '' }}{{ fmt(m.net) }}
                  </span>
                </td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-gray-200 bg-gray-50">
              <tr>
                <td class="py-2 px-5 text-sm font-semibold text-gray-700">Total</td>
                <td class="py-2 px-4 text-right text-sm font-semibold text-green-600">{{ fmt(monthlyTotals.income) }}</td>
                <td class="py-2 px-4 text-right text-sm font-semibold text-red-500">{{ fmt(monthlyTotals.expense) }}</td>
                <td class="py-2 px-4 text-right text-sm font-semibold text-violet-600 hidden lg:table-cell">{{ fmt(monthlyTotals.purchase_payments) }}</td>
                <td class="py-2 px-4 text-right text-sm font-semibold text-purple-600 hidden lg:table-cell">{{ fmt(monthlyTotals.cash_purchases) }}</td>
                <td class="py-2 px-4 text-right text-sm font-semibold text-red-600">{{ fmt(monthlyTotals.total_outflow) }}</td>
                <td class="py-2 px-5 text-right text-sm font-semibold" :class="monthlyTotals.net >= 0 ? 'text-blue-600' : 'text-red-600'">
                  {{ monthlyTotals.net >= 0 ? '+' : '' }}{{ fmt(monthlyTotals.net) }}
                </td>
              </tr>
            </tfoot>
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

  <!-- ── Transfer Income Modal ────────────────────────────────────────────── -->
  <div v-if="showTransferModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
      <div class="flex items-center justify-between mb-5">
        <h2 class="text-lg font-semibold text-gray-800">Transfer Income</h2>
        <button @click="closeTransferModal" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
      </div>

      <!-- Source → Destination balance preview -->
      <div class="grid grid-cols-2 gap-2 mb-4">
        <div class="flex flex-col bg-red-50 rounded-lg px-3 py-2 text-sm">
          <span class="text-[10px] text-gray-400 uppercase mb-0.5">From · {{ moduleLabel(transferForm.transfer_from) }}</span>
          <span class="font-bold" :class="sourceAvailableBalance >= 0 ? 'text-red-700' : 'text-red-500'">
            {{ fmt(sourceAvailableBalance) }}
          </span>
          <span v-if="transferTotal > 0" class="text-[10px] text-gray-400 mt-0.5">
            After: {{ fmt(sourceAvailableBalance - transferTotal) }}
          </span>
        </div>
        <div class="flex flex-col bg-green-50 rounded-lg px-3 py-2 text-sm">
          <span class="text-[10px] text-gray-400 uppercase mb-0.5">To · {{ moduleLabel(transferForm.module) }}</span>
          <span class="font-bold text-green-700">{{ fmt(destAvailableBalance) }}</span>
          <span v-if="transferForm.amount > 0" class="text-[10px] text-gray-400 mt-0.5">
            After: {{ fmt(destAvailableBalance + (parseFloat(transferForm.amount) || 0)) }}
          </span>
        </div>
      </div>

      <form @submit.prevent="submitTransfer" class="space-y-4">

        <!-- Transfer From -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Transfer From *</label>
          <select v-model="transferForm.transfer_from" required
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option v-if="transferForm.module !== 'income'"  value="income">Income (Dashboard)</option>
            <option v-if="transferForm.module !== 'saving'"  value="saving">Saving</option>
          </select>
        </div>

        <!-- Transfer To -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Transfer To *</label>
          <select v-model="transferForm.module" required
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option v-if="transferForm.transfer_from !== 'saving'" value="saving">Saving</option>
            <option v-if="transferForm.transfer_from !== 'income'" value="income">Income (Dashboard)</option>
          </select>
        </div>

        <!-- Amount -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
          <input v-model="transferForm.amount" type="number" min="0.01" step="0.01" required
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="0.00" />
        </div>

        <!-- Transfer Fee -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Transfer Fee</label>
          <input v-model="transferForm.transfer_fee" type="number" min="0" step="0.01"
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="0.00" />
        </div>

        <!-- Total (readonly computed) -->
        <div class="flex justify-between items-center bg-gray-50 rounded-lg px-4 py-3">
          <span class="text-sm font-semibold text-gray-700">
            Total Deducted from {{ moduleLabel(transferForm.transfer_from) }}
          </span>
          <span class="text-lg font-bold text-red-600">
            {{ fmt(transferTotal) }}
          </span>
        </div>

        <!-- Transfer Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Transfer Date *</label>
          <input v-model="transferForm.transfer_date" type="date" required
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>

        <!-- Note -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
          <input v-model="transferForm.note" type="text" maxlength="255"
            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="Optional note" />
        </div>

        <p v-if="transferExceedsBalance" class="text-sm text-red-600 font-medium">
          Insufficient balance. Need {{ fmt(transferTotal) }}, have {{ fmt(sourceAvailableBalance) }}.
        </p>
        <p v-if="transferError" class="text-sm text-red-600">{{ transferError }}</p>

        <div class="flex gap-3 pt-2">
          <button type="button" @click="closeTransferModal"
            class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
            Cancel
          </button>
          <button type="submit" :disabled="transferLoading || transferExceedsBalance"
            class="flex-1 bg-indigo-600 text-white py-2 rounded-lg text-sm hover:bg-indigo-700 transition disabled:opacity-50">
            {{ transferLoading ? 'Saving…' : 'Confirm Transfer' }}
          </button>
        </div>
      </form>
    </div>
  </div>

</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useDashboardStore } from '@/stores/dashboard';
import { formatDate } from '@/utils/date';
import { moduleTransferService } from '@/services';
import DonutChart from '@/components/charts/DonutChart.vue';

// ── Store ─────────────────────────────────────────────────────────────────
const store = useDashboardStore();
const dateFrom = ref('');
const dateTo   = ref('');

// ── Computed helpers ──────────────────────────────────────────────────────
const recentTxs = computed(() =>
  store.summary?.recent_transactions?.data ?? []
);

const incomeTxs = computed(() =>
  store.summary?.income_transactions ?? []
);

const expenseTxs = computed(() =>
  store.summary?.expense_transactions ?? []
);

const otherTxs = computed(() =>
  store.summary?.other_transactions ?? []
);

// ── Pagination ────────────────────────────────────────────────────────────
const TX_PER_PAGE = 10;

const incomePage  = ref(1);
const expensePage = ref(1);
const otherPage   = ref(1);

const incomeTotalPages  = computed(() => Math.max(1, Math.ceil(incomeTxs.value.length  / TX_PER_PAGE)));
const expenseTotalPages = computed(() => Math.max(1, Math.ceil(expenseTxs.value.length / TX_PER_PAGE)));
const otherTotalPages   = computed(() => Math.max(1, Math.ceil(otherTxs.value.length   / TX_PER_PAGE)));

const pagedIncomeTxs  = computed(() => incomeTxs.value.slice((incomePage.value  - 1) * TX_PER_PAGE, incomePage.value  * TX_PER_PAGE));
const pagedExpenseTxs = computed(() => expenseTxs.value.slice((expensePage.value - 1) * TX_PER_PAGE, expensePage.value * TX_PER_PAGE));
const pagedOtherTxs   = computed(() => otherTxs.value.slice((otherPage.value   - 1) * TX_PER_PAGE, otherPage.value   * TX_PER_PAGE));

// Reset pages when data reloads
watch(() => store.summary, () => {
  incomePage.value  = 1;
  expensePage.value = 1;
  otherPage.value   = 1;
});

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

const purchaseList = computed(() =>
  store.summary?.purchase_list ?? []
);

const monthlyTotals = computed(() => {
  const rows = store.summary?.monthly_data ?? [];
  return {
    income:            rows.reduce((s, m) => s + (m.income            ?? 0), 0),
    expense:           rows.reduce((s, m) => s + (m.expense           ?? 0), 0),
    purchase_payments: rows.reduce((s, m) => s + (m.purchase_payments ?? 0), 0),
    cash_purchases:    rows.reduce((s, m) => s + (m.cash_purchases    ?? 0), 0),
    total_outflow:     rows.reduce((s, m) => s + (m.total_outflow     ?? 0), 0),
    net:               rows.reduce((s, m) => s + (m.net               ?? 0), 0),
  };
});

const purchaseTotals = computed(() => ({
  total:  purchaseList.value.reduce((s, p) => s + (p.total_amount ?? 0), 0),
  paid:   purchaseList.value.reduce((s, p) => s + (p.paid         ?? 0), 0),
  unpaid: purchaseList.value.reduce((s, p) => s + (p.unpaid       ?? 0), 0),
}));

const transferSummary = computed(() =>
  store.summary?.transfer_summary ?? { saving: {} }
);

const transferLogs = computed(() => store.summary?.transfer_logs ?? []);

const destAvailableBalance = computed(() => {
  const to = transferForm.value.module;
  if (to === 'income') return transferSummary.value.income?.available_balance ?? store.summary?.balance ?? 0;
  return transferSummary.value[to]?.available_balance ?? 0;
});


// ── Expense donut segments ─────────────────────────────────────────────────
const expenseSegments = computed(() => {
  const total = store.summary?.total_expenses ?? 0;
  if (!total) return [];
  return expenseBreakdown.value.map(item => ({
    label: item.name || 'Uncategorized',
    value: item.total,
    pct:   total > 0 ? (item.total / total) * 100 : 0,
    color: item.color || '#6B7280',
  }));
});

// ── Monthly bar chart helpers ─────────────────────────────────────────────
const monthlyMax = computed(() => {
  const rows = store.summary?.monthly_data ?? [];
  return Math.max(...rows.map(m => m.income ?? 0), ...rows.map(m => m.total_outflow ?? 0), 1);
});

function monthlyBarWidth(val) {
  return Math.min(50, ((val ?? 0) / monthlyMax.value) * 50);
}

function fmtShort(val) {
  const n = Number(val || 0);
  if (Math.abs(n) >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
  if (Math.abs(n) >= 1_000)     return (n / 1_000).toFixed(1) + 'K';
  return n.toFixed(0);
}

// ── Report outflow rows helper ─────────────────────────────────────────────
function monthOutflows(report) {
  return [
    { label: 'Expenses',       value: report.total_expenses    ?? 0, color: 'text-red-600',    barColor: 'bg-red-400' },
    { label: 'Debt Payments',  value: report.debt_payments     ?? 0, color: 'text-orange-600', barColor: 'bg-orange-400' },
    { label: 'CC Installments',value: report.purchase_payments ?? 0, color: 'text-violet-600', barColor: 'bg-violet-400' },
    { label: 'Cash Purchases', value: report.cash_purchases    ?? 0, color: 'text-purple-600', barColor: 'bg-purple-400' },
    { label: 'Transfers Out',  value: report.transfers_out     ?? 0, color: 'text-indigo-600', barColor: 'bg-indigo-400' },
  ].filter(r => r.value > 0);
}

function monthOutflowPct(value, report) {
  const totalInflow = (report.total_income ?? 0) + (report.business_debt_received ?? 0);
  if (!totalInflow) return 0;
  return Math.min(100, (value / totalInflow) * 100);
}

// ── Transfer Modal ─────────────────────────────────────────────────────────
const showTransferModal = ref(false);
const transferLoading   = ref(false);
const transferError     = ref('');
const transferForm      = ref({
  module:         'saving',
  transfer_from:  'income',
  amount:         '',
  transfer_fee:   0,
  transfer_date:  new Date().toISOString().slice(0, 10),
  note:           '',
});

// Prevent same-fund selection: auto-fix transfer_from when it matches the destination
watch(() => transferForm.value.module, (mod) => {
  if (transferForm.value.transfer_from === mod) {
    const options = ['income', 'saving'];
    transferForm.value.transfer_from = options.find(o => o !== mod) ?? 'income';
  }
});
// Also fix module (destination) when transfer_from changes to match it
watch(() => transferForm.value.transfer_from, (from) => {
  if (transferForm.value.module === from) {
    const options = ['saving', 'income'];
    transferForm.value.module = options.find(o => o !== from) ?? 'saving';
  }
});

const sourceAvailableBalance = computed(() => {
  const from = transferForm.value.transfer_from;
  if (from === 'income') return transferSummary.value.income?.available_balance ?? store.summary?.balance ?? 0;
  return transferSummary.value[from]?.available_balance ?? 0;
});

function moduleLabel(mod) {
  return { income: 'Income', saving: 'Saving' }[mod] ?? mod;
}

// Alias used by the transfer-log section in the template
const fundLabel = moduleLabel;

function fundBadgeClass(mod) {
  return {
    income: 'bg-green-100 text-green-700',
    saving: 'bg-teal-100 text-teal-700',
  }[mod] ?? 'bg-gray-100 text-gray-600';
}

function openTransferModalFor(mod) {
  transferError.value = '';
  transferForm.value = {
    module:        mod,
    transfer_from: 'income',
    amount:        '',
    transfer_fee:  0,
    transfer_date: new Date().toISOString().slice(0, 10),
    note:          '',
  };
  showTransferModal.value = true;
}

const transferTotal = computed(() => {
  const a = parseFloat(transferForm.value.amount)       || 0;
  const f = parseFloat(transferForm.value.transfer_fee) || 0;
  return a + f;
});

const transferExceedsBalance = computed(() =>
  transferTotal.value > 0 && transferTotal.value > sourceAvailableBalance.value
);

function openTransferModal() {
  transferError.value = '';
  showTransferModal.value = true;
}

function closeTransferModal() {
  showTransferModal.value = false;
  transferForm.value = { module: 'saving', transfer_from: 'income', amount: '', transfer_fee: 0, transfer_date: new Date().toISOString().slice(0, 10), note: '' };
}

async function submitTransfer() {
  transferLoading.value = true;
  transferError.value   = '';
  try {
    await moduleTransferService.create({
      ...transferForm.value,
      amount:       parseFloat(transferForm.value.amount),
      transfer_fee: parseFloat(transferForm.value.transfer_fee) || 0,
    });
    closeTransferModal();
    await store.fetchSummary();
  } catch (e) {
    transferError.value = e.response?.data?.message ?? 'Transfer failed.';
  } finally {
    transferLoading.value = false;
  }
}

// ── Formatters ────────────────────────────────────────────────────────────
function fmt(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

// formatDate imported from @/utils/date

function formatDateTime(val) {
  if (!val) return '';
  const d = new Date(val);
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
    + ' ' + d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true });
}

// ── Other transaction helpers ─────────────────────────────────────────────
function otherTxIcon(type) {
  return {
    debt_payment:           '⊘',
    business_debt_received: '↑',
    purchase:               '🛒',
    purchase_payment:       '💳',
    module_transfer:        '→',
    module_transfer_back:   '←',
  }[type] ?? '·';
}

function otherTxBadgeClass(type) {
  return {
    debt_payment:           'bg-orange-100 text-orange-700',
    business_debt_received: 'bg-teal-100 text-teal-700',
    purchase:               'bg-purple-100 text-purple-700',
    purchase_payment:       'bg-violet-100 text-violet-700',
    module_transfer:        'bg-indigo-100 text-indigo-700',
    module_transfer_back:   'bg-green-100 text-green-700',
  }[type] ?? 'bg-gray-100 text-gray-600';
}

function otherTxLabelClass(type) {
  return {
    debt_payment:           'bg-orange-100 text-orange-700',
    business_debt_received: 'bg-teal-100 text-teal-700',
    purchase:               'bg-purple-100 text-purple-700',
    purchase_payment:       'bg-violet-100 text-violet-700',
    module_transfer:        'bg-indigo-100 text-indigo-700',
    module_transfer_back:   'bg-green-100 text-green-700',
  }[type] ?? 'bg-gray-100 text-gray-600';
}

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
