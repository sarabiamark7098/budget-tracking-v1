<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Investments</h1>
      <button @click="openModal()" class="bg-blue-600 text-white px-3 py-2 sm:px-4 rounded-lg hover:bg-blue-700 text-sm font-medium">+ Add</button>
    </div>

    <!-- Portfolio Summary -->
    <div v-if="store.portfolio" class="space-y-4">

      <!-- Summary Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Invested</p>
          <p class="text-lg lg:text-2xl font-bold text-blue-600">{{ fmt(store.portfolio.total_invested) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">{{ store.portfolio.count }} investment{{ store.portfolio.count !== 1 ? 's' : '' }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Current Value</p>
          <p class="text-lg lg:text-2xl font-bold text-green-600">{{ fmt(store.portfolio.total_current_value) }}</p>
          <p class="text-[10px] mt-1" :class="portfolioGainLoss >= 0 ? 'text-green-500' : 'text-red-500'">
            {{ portfolioGainLoss >= 0 ? '+' : '' }}{{ fmt(portfolioGainLoss) }} unrealized
          </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Available Balance</p>
          <p class="text-lg lg:text-2xl font-bold" :class="(store.portfolio.available_balance ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600'">
            {{ fmt(store.portfolio.available_balance ?? 0) }}
          </p>
          <p class="text-[10px] text-gray-400 mt-1">In − Out − Invested + Div</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Dividends</p>
          <p class="text-lg lg:text-2xl font-bold text-amber-600">{{ fmt(store.portfolio.total_dividends ?? 0) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">Cash income received</p>
        </div>
      </div>

      <!-- Charts Row -->
      <div v-if="typeChartSegments.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Donut: Allocation by Type -->
        <div class="bg-white rounded-xl shadow-sm p-5">
          <h3 class="text-sm font-semibold text-gray-700 mb-4">Allocation by Type</h3>
          <div class="flex items-center gap-6">
            <DonutChart :segments="typeChartSegments" :size="150">
              <span class="text-xs text-gray-400 leading-tight text-center">By Type</span>
            </DonutChart>
            <div class="flex-1 space-y-2 min-w-0">
              <div v-for="(seg, i) in typeChartSegments" :key="i" class="text-sm">
                <div class="flex items-center gap-2 mb-0.5">
                  <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ background: seg.color }"></span>
                  <span class="font-medium text-gray-700 truncate">{{ seg.label }}</span>
                  <span class="ml-auto text-gray-500 flex-shrink-0 text-xs">{{ seg.pct.toFixed(1) }}%</span>
                </div>
                <div class="ml-4 flex justify-between text-xs text-gray-400">
                  <span>{{ fmt(seg.amount) }}</span>
                  <span>{{ seg.count }} item{{ seg.count !== 1 ? 's' : '' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Obligations Tracker -->
        <div class="bg-white rounded-xl shadow-sm p-5">
          <h3 class="text-sm font-semibold text-gray-700 mb-4">Payment Obligations</h3>
          <template v-if="store.portfolio.total_obligations > 0">
            <!-- Overall stacked bar -->
            <div class="mb-4">
              <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Overall Progress</span>
                <span class="font-semibold">{{ obligationProgress }}% paid</span>
              </div>
              <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                <div
                  class="h-3 rounded-full transition-all duration-500"
                  :class="obligationProgress >= 100 ? 'bg-green-500' : 'bg-emerald-400'"
                  :style="{ width: obligationProgress + '%' }"
                />
              </div>
            </div>
            <!-- Stats grid -->
            <div class="grid grid-cols-3 gap-3 text-center">
              <div class="bg-orange-50 rounded-lg p-3">
                <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Obligations</p>
                <p class="text-sm font-bold text-orange-600">{{ fmt(store.portfolio.total_obligations) }}</p>
              </div>
              <div class="bg-emerald-50 rounded-lg p-3">
                <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Paid</p>
                <p class="text-sm font-bold text-emerald-600">{{ fmt(store.portfolio.total_paid_all) }}</p>
              </div>
              <div class="bg-rose-50 rounded-lg p-3">
                <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1">Remaining</p>
                <p class="text-sm font-bold text-rose-600">{{ fmt(store.portfolio.remaining_obligations) }}</p>
              </div>
            </div>
            <!-- Available for payments -->
            <div class="mt-3 flex items-center justify-between rounded-lg px-3 py-2 text-xs"
              :class="(store.portfolio.available_for_payments ?? 0) > 0 ? 'bg-blue-50 text-blue-700' : 'bg-gray-50 text-gray-500'">
              <span>Available for payments</span>
              <span class="font-semibold">{{ fmt(store.portfolio.available_for_payments ?? 0) }}</span>
            </div>
          </template>
          <div v-else class="flex flex-col items-center justify-center h-32 text-gray-300 text-sm">
            <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            No payment obligations
          </div>
        </div>
      </div>
    </div>

    <!-- Investments Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h3 class="text-sm font-semibold text-gray-700">All Investments</h3>
        <span class="text-xs text-gray-400">{{ store.pagination?.total ?? store.items.length }} records</span>
      </div>
      <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Investment</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Amount</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium min-w-[180px]">Progress / Value</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Schedule</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Status</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="6" class="text-center py-10 text-gray-400">No investment records found.</td>
            </tr>
            <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">

              <!-- Investment (name + type badge) -->
              <td class="px-4 py-3">
                <div class="font-medium text-gray-800 leading-tight">{{ item.name }}</div>
                <div class="flex items-center gap-1.5 mt-1">
                  <span class="text-[11px] px-1.5 py-0.5 rounded-full font-medium" :class="typeBadgeClass(item.type)">{{ typeLabel(item) }}</span>
                </div>
              </td>

              <!-- Amount (context-aware) -->
              <td class="px-4 py-3 text-right">
                <template v-if="item.type === 'real_estate'">
                  <span class="font-semibold text-blue-600">{{ fmt(item.total_value) }}</span>
                  <span class="block text-[10px] text-gray-400">property value</span>
                </template>
                <template v-else-if="item.type === 'other'">
                  <span class="font-semibold text-blue-600">{{ fmt((item.months_of_payment || 0) * (item.amount_per_payment || 0)) }}</span>
                  <span class="block text-[10px] text-gray-400">total expected</span>
                </template>
                <template v-else-if="item.type === 'mutual_fund'">
                  <span class="font-semibold text-blue-600">{{ fmt(item.total_paid ?? 0) }}</span>
                  <span class="block text-[10px] text-gray-400">total paid</span>
                </template>
                <template v-else>
                  <span class="font-semibold text-blue-600">{{ fmt(item.amount_invested) }}</span>
                  <span class="block text-[10px] text-gray-400">invested</span>
                </template>
              </td>

              <!-- Progress / Value -->
              <td class="px-4 py-3">
                <!-- Real Estate + Other: progress bar -->
                <template v-if="['real_estate', 'other'].includes(item.type)">
                  <div class="flex justify-between text-xs mb-1">
                    <span class="font-medium text-emerald-600">{{ fmt(item.total_paid ?? 0) }}</span>
                    <span class="text-gray-400">{{ payProgress(item) }}%</span>
                  </div>
                  <div class="w-full bg-gray-100 rounded-full h-2">
                    <div
                      class="h-2 rounded-full transition-all duration-500"
                      :class="item.payment_status === 'paid' ? 'bg-green-500' : 'bg-emerald-400'"
                      :style="{ width: payProgress(item) + '%' }"
                    />
                  </div>
                  <span class="text-[10px] text-gray-400 mt-0.5 block">of {{ fmt((item.months_of_payment || 0) * (item.amount_per_payment || 0)) }}</span>
                </template>
                <!-- Mutual Fund: running total -->
                <template v-else-if="item.type === 'mutual_fund'">
                  <span class="font-semibold text-emerald-600">{{ fmt(item.total_paid ?? 0) }}</span>
                  <span class="block text-[10px] text-gray-400">running total</span>
                </template>
                <!-- Standard types: current value + gain/loss -->
                <template v-else>
                  <span class="font-semibold text-gray-800">{{ fmt(item.current_value) }}</span>
                  <span class="block text-[11px] font-medium mt-0.5" :class="gainLoss(item) >= 0 ? 'text-green-600' : 'text-red-500'">
                    {{ gainLoss(item) >= 0 ? '+' : '' }}{{ fmt(gainLoss(item)) }}
                    ({{ roi(item) >= 0 ? '+' : '' }}{{ roi(item).toFixed(1) }}%)
                  </span>
                </template>
              </td>

              <!-- Schedule -->
              <td class="px-4 py-3">
                <template v-if="['real_estate', 'other'].includes(item.type) && item.period">
                  <span class="text-xs text-gray-500 block">{{ periodLabel(item.period) }}</span>
                  <span class="font-semibold text-gray-700 text-sm">{{ fmt(item.amount_per_payment) }}</span>
                </template>
                <span v-else class="text-xs text-gray-300">—</span>
              </td>

              <!-- Status -->
              <td class="px-4 py-3">
                <span v-if="isPayableType(item)" class="text-xs px-2 py-1 rounded-full font-medium" :class="statusBadgeClass(item.payment_status)">
                  {{ statusLabel(item.payment_status) }}
                </span>
                <span v-else class="text-xs text-gray-300">—</span>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3">
                <div class="flex gap-1.5 justify-end flex-wrap">
                  <button
                    v-if="isPayableType(item) && item.payment_status === 'active'"
                    @click="openPayModal(item)"
                    class="text-emerald-600 hover:text-emerald-800 text-xs px-2 py-1 border border-emerald-300 rounded bg-emerald-50 hover:bg-emerald-100"
                  >Pay</button>
                  <button
                    @click="openDividendModal(item)"
                    class="text-amber-600 hover:text-amber-800 text-xs px-2 py-1 border border-amber-300 rounded bg-amber-50 hover:bg-amber-100"
                  >Dividend</button>
                  <button
                    v-if="isPayableType(item)"
                    @click="openHistoryModal(item)"
                    class="text-indigo-500 hover:text-indigo-700 text-xs px-2 py-1 border rounded"
                  >History</button>
                  <button
                    @click="openDivHistoryModal(item)"
                    class="text-amber-500 hover:text-amber-700 text-xs px-2 py-1 border border-amber-300 rounded"
                  >Div History</button>
                  <button @click="openModal(item)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">Edit</button>
                  <button @click="confirmDelete(item)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="store.pagination && store.pagination.last_page > 1" class="flex justify-between items-center text-sm text-gray-500">
      <span>Page {{ store.pagination.current_page }} of {{ store.pagination.last_page }}</span>
      <div class="flex gap-2">
        <button :disabled="store.pagination.current_page <= 1" @click="changePage(store.pagination.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100">Prev</button>
        <button :disabled="store.pagination.current_page >= store.pagination.last_page" @click="changePage(store.pagination.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100">Next</button>
      </div>
    </div>

    <!-- ───── Add / Edit Modal ───── -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editing ? 'Edit Investment' : 'Add Investment' }}</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">

          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
            <input v-model="form.name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. ACMF Equity Fund" />
          </div>

          <!-- Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
            <select v-model="form.type" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="real_estate">Real Estate</option>
              <option value="mutual_fund">Mutual Fund</option>
              <option value="other">Other Investment</option>
              <option disabled class="text-gray-400">──────</option>
              <option value="uitf">UITF</option>
              <option value="bonds">Bonds</option>
              <option value="business">Business</option>
              <option value="stocks">Stocks</option>
              <option value="crypto">Crypto</option>
              <optgroup v-if="otherTitles.length" label="── Custom Others ──">
                <option v-for="t in otherTitles" :key="t" :value="'other'" @click="form.other_investment_title = t">{{ t }}</option>
              </optgroup>
            </select>
          </div>

          <!-- Other Investment Title (only for "other") -->
          <div v-if="form.type === 'other'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Other Investment Title *</label>
            <input v-model="form.other_investment_title" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Gold Trading" list="other-titles-list" />
            <datalist id="other-titles-list">
              <option v-for="t in otherTitles" :key="t" :value="t" />
            </datalist>
          </div>

          <!-- Amount Invested (only for types that don't auto-compute it) -->
          <div v-if="!['real_estate', 'mutual_fund', 'other'].includes(form.type)">
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount Invested *</label>
            <input v-model="form.amount_invested" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Current Value (only for types that don't auto-compute it) -->
          <div v-if="!['real_estate', 'mutual_fund', 'other'].includes(form.type)">
            <label class="block text-sm font-medium text-gray-700 mb-1">Current Value</label>
            <input v-model="form.current_value" type="number" min="0" step="0.01" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" :placeholder="`Defaults to ${form.amount_invested || '0'}`" />
          </div>

          <!-- ── Real Estate fields ── -->
          <template v-if="form.type === 'real_estate'">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Total Property Value *</label>
              <input v-model="form.total_value" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. 3500000.00" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Period</label>
                <select v-model="form.period" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option value="">— Select —</option>
                  <option value="monthly">Monthly</option>
                  <option value="quarterly">Quarterly</option>
                  <option value="semi_annual">Semi-Annual</option>
                  <option value="annual">Annual</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Months of Payment</label>
                <input v-model="form.months_of_payment" type="number" min="1" step="1" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. 120" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Payment</label>
              <input v-model="form.amount_per_payment" type="number" min="0" step="0.01" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. 5000.00" />
            </div>
          </template>

          <!-- ── Mutual Fund: no extra fields ── -->

          <!-- ── Other Investment fields ── -->
          <template v-if="form.type === 'other'">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Period</label>
                <select v-model="form.period" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option value="">— Select —</option>
                  <option value="monthly">Monthly</option>
                  <option value="quarterly">Quarterly</option>
                  <option value="semi_annual">Semi-Annual</option>
                  <option value="annual">Annual</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Months of Payment</label>
                <input v-model="form.months_of_payment" type="number" min="1" step="1" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. 24" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Amount per Payment</label>
              <input v-model="form.amount_per_payment" type="number" min="0" step="0.01" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. 2000.00" />
            </div>
          </template>

          <div v-if="formError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-blue-700">
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ───── Pay Modal (Real Estate + Other: confirm installment) ───── -->
    <div v-if="showPayModal && payTarget && payTarget.type !== 'mutual_fund'" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">Record Payment</h2>
          <button @click="showPayModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5 space-y-4">
          <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600 space-y-1">
            <p><span class="font-medium">Investment:</span> {{ payTarget.name }}</p>
            <p v-if="payTarget.other_investment_title"><span class="font-medium">Title:</span> {{ payTarget.other_investment_title }}</p>
            <p><span class="font-medium">Amount per Payment:</span> <span class="text-blue-600 font-semibold">{{ fmt(payTarget.amount_per_payment) }}</span></p>
            <p><span class="font-medium">Total Paid so far:</span> {{ fmt(payTarget.total_paid ?? 0) }}</p>
            <p v-if="payTarget.months_of_payment && payTarget.amount_per_payment">
              <span class="font-medium">Total Due:</span> {{ fmt(payTarget.months_of_payment * payTarget.amount_per_payment) }}
            </p>
          </div>
          <!-- Available balance indicator -->
          <div class="flex items-center justify-between rounded-lg px-3 py-2 text-sm" :class="payExceedsBalance ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'">
            <span class="font-medium">Investment Balance Available</span>
            <span class="font-bold">{{ fmt(availableForPayments) }}</span>
          </div>
          <div v-if="payExceedsBalance" class="text-red-600 text-xs bg-red-50 rounded-lg px-3 py-2">
            Insufficient balance. Transfer more funds to the investment module first.
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date *</label>
            <input v-model="payForm.payment_date" type="date" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <input v-model="payForm.notes" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Optional" />
          </div>
          <div v-if="payError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ payError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button @click="showPayModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button @click="submitPayment" :disabled="payingSaving || payExceedsBalance" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-emerald-700">
              {{ payingSaving ? 'Saving...' : 'Confirm Payment' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ───── Pay Modal (Mutual Fund: custom amount) ───── -->
    <div v-if="showPayModal && payTarget && payTarget.type === 'mutual_fund'" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">Record Payment — Mutual Fund</h2>
          <button @click="showPayModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5 space-y-4">
          <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-600">
            <p><span class="font-medium">Fund:</span> {{ payTarget.name }}</p>
            <p><span class="font-medium">Total Paid so far:</span> {{ fmt(payTarget.total_paid ?? 0) }}</p>
          </div>
          <!-- Available balance indicator -->
          <div class="flex items-center justify-between rounded-lg px-3 py-2 text-sm" :class="payExceedsBalance ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'">
            <span class="font-medium">Investment Balance Available</span>
            <span class="font-bold">{{ fmt(availableForPayments) }}</span>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount to Pay *</label>
            <input v-model="payForm.amount" type="number" min="0.01" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter amount" />
          </div>
          <div v-if="payExceedsBalance" class="text-red-600 text-xs bg-red-50 rounded-lg px-3 py-2">
            Insufficient balance. Transfer more funds to the investment module first.
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date *</label>
            <input v-model="payForm.payment_date" type="date" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <input v-model="payForm.notes" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Optional" />
          </div>
          <div v-if="payError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ payError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button @click="showPayModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button @click="submitPayment" :disabled="payingSaving || payExceedsBalance" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-emerald-700">
              {{ payingSaving ? 'Saving...' : 'Record Payment' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ───── Payment History Modal ───── -->
    <div v-if="showHistoryModal && historyTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-5 border-b flex-shrink-0">
          <div>
            <h2 class="font-semibold text-gray-800">Payment History</h2>
            <p class="text-xs text-gray-500 mt-0.5">{{ historyTarget.name }}{{ historyTarget.other_investment_title ? ' — ' + historyTarget.other_investment_title : '' }}</p>
          </div>
          <button @click="showHistoryModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <!-- Summary bar -->
        <div class="px-5 py-3 bg-gray-50 border-b flex-shrink-0 flex flex-wrap gap-4 text-sm">
          <div>
            <span class="text-gray-500">Total Paid:</span>
            <span class="font-semibold text-blue-600 ml-1">{{ fmt(historyData.total_paid ?? 0) }}</span>
          </div>
          <div v-if="historyData.total_due">
            <span class="text-gray-500">Total Due:</span>
            <span class="font-semibold text-gray-700 ml-1">{{ fmt(historyData.total_due) }}</span>
          </div>
          <div v-if="historyData.total_due">
            <span class="text-gray-500">Remaining:</span>
            <span class="font-semibold ml-1" :class="remaining <= 0 ? 'text-green-600' : 'text-orange-600'">
              {{ fmt(Math.max(0, remaining)) }}
            </span>
          </div>
          <div>
            <span class="text-gray-500">Status:</span>
            <span class="ml-1 text-xs font-medium px-2 py-0.5 rounded-full" :class="statusBadgeClass(historyData.status)">
              {{ statusLabel(historyData.status) }}
            </span>
          </div>
        </div>

        <!-- Payment list -->
        <div class="overflow-y-auto flex-1 p-5">
          <div v-if="historyLoading" class="text-center py-6 text-gray-400">Loading...</div>
          <div v-else-if="!historyData.payments?.length" class="text-center py-6 text-gray-400">No payments recorded yet.</div>
          <div v-else class="space-y-2">
            <div v-for="(p, i) in historyData.payments" :key="p.id" class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
              <div>
                <p class="text-sm font-medium text-gray-700">Payment #{{ historyData.payments.length - i }}</p>
                <p class="text-xs text-gray-400">{{ formatDate(p.payment_date) }}<span v-if="p.notes"> · {{ p.notes }}</span></p>
              </div>
              <p class="text-sm font-bold text-emerald-600">{{ fmt(p.amount) }}</p>
            </div>
          </div>
        </div>

        <!-- Footer actions -->
        <div class="p-5 border-t flex-shrink-0 flex justify-between items-center">
          <button
            v-if="historyTarget.type === 'mutual_fund' && historyData.status !== 'done'"
            @click="handleMarkDone"
            :disabled="markingDone"
            class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-indigo-700"
          >{{ markingDone ? 'Saving...' : 'Mark as Done' }}</button>
          <span v-else></span>
          <button @click="showHistoryModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Close</button>
        </div>
      </div>
    </div>

    <!-- ───── Dividend Modal ───── -->
    <div v-if="showDividendModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <div>
            <h2 class="font-semibold text-gray-800">Record Dividend — {{ dividendTarget?.name }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ typeLabel(dividendTarget ?? {}) }}</p>
          </div>
          <button @click="showDividendModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleDividend" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Dividend Amount *</label>
            <input
              v-model="dividendForm.amount"
              type="number" min="0.01" step="0.01" required
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
              placeholder="e.g. 2500.00"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date Received</label>
            <input v-model="dividendForm.paid_at" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
            <input v-model="dividendForm.notes" type="text" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" placeholder="e.g. Q3 2025 dividend payout" />
          </div>
          <div v-if="dividendForm.amount" class="bg-amber-50 rounded-lg px-4 py-3 text-sm flex justify-between">
            <span class="text-gray-500">Amount to credit</span>
            <span class="font-bold text-amber-600">+{{ fmt(dividendForm.amount) }}</span>
          </div>
          <p class="text-xs text-gray-400">Dividend will be added to your available investment balance.</p>
          <div v-if="dividendFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ dividendFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showDividendModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="dividendSaving" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-amber-600">
              {{ dividendSaving ? 'Saving...' : 'Record Dividend' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ───── Dividend History Modal ───── -->
    <div v-if="showDivHistoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b sticky top-0 bg-white">
          <div>
            <h2 class="font-semibold text-gray-800">Dividend History — {{ divHistoryTarget?.name }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ typeLabel(divHistoryTarget ?? {}) }}</p>
          </div>
          <button @click="showDivHistoryModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5">
          <div v-if="divHistoryLoading" class="text-center py-8 text-gray-400">Loading...</div>
          <div v-else-if="!divHistoryItems.length" class="text-center py-8 text-gray-400">No dividends recorded yet.</div>
          <template v-else>
            <div class="flex items-center gap-3 mb-4">
              <span class="bg-amber-50 text-amber-700 text-sm font-semibold px-3 py-1.5 rounded-lg">
                Total: {{ fmt(totalDivHistory) }}
              </span>
              <span class="text-xs text-gray-400">{{ divHistoryItems.length }} payment{{ divHistoryItems.length !== 1 ? 's' : '' }}</span>
            </div>
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="text-left px-3 py-2 text-gray-500 font-medium">Date Received</th>
                  <th class="text-right px-3 py-2 text-gray-500 font-medium">Amount</th>
                  <th class="text-left px-3 py-2 text-gray-500 font-medium">Notes</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="div in divHistoryItems" :key="div.id" class="border-t hover:bg-gray-50">
                  <td class="px-3 py-2 text-gray-600">{{ formatDate(div.paid_at) }}</td>
                  <td class="px-3 py-2 text-right font-semibold text-amber-600">{{ fmt(div.amount) }}</td>
                  <td class="px-3 py-2 text-gray-400 text-xs">{{ div.notes || '—' }}</td>
                </tr>
              </tbody>
              <tfoot class="border-t bg-amber-50">
                <tr>
                  <td class="px-3 py-2 font-semibold text-amber-700">Total</td>
                  <td class="px-3 py-2 text-right font-bold text-amber-700">{{ fmt(totalDivHistory) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </template>
        </div>
      </div>
    </div>

    <!-- ───── Confirm Delete Dialog ───── -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Investment</h3>
        <p class="text-sm text-gray-500 mb-4">Delete "{{ deleteTarget.name }}"? This cannot be undone.</p>
        <div class="flex justify-end gap-3">
          <button @click="deleteTarget = null" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handleDelete" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useInvestmentStore } from '@/stores/investment';
import DonutChart from '@/components/charts/DonutChart.vue';

const store = useInvestmentStore();

// ── Modal state ──────────────────────────────────────────────
const showModal   = ref(false);
const editing     = ref(null);
const deleteTarget = ref(null);
const saving      = ref(false);
const formError   = ref('');

// ── Pay modal state ──────────────────────────────────────────
const showPayModal = ref(false);
const payTarget    = ref(null);
const payingSaving = ref(false);
const payError     = ref('');
const payForm      = ref({ amount: '', payment_date: today(), notes: '' });

// ── Dividend modal state ─────────────────────────────────────
const showDividendModal = ref(false);
const dividendTarget    = ref(null);
const dividendSaving    = ref(false);
const dividendFormError = ref('');
const dividendForm      = ref({ amount: '', paid_at: today(), notes: '' });

// ── Dividend History modal state ─────────────────────────────
const showDivHistoryModal = ref(false);
const divHistoryTarget    = ref(null);
const divHistoryLoading   = ref(false);
const divHistoryItems     = ref([]);

const totalDivHistory = computed(() =>
  divHistoryItems.value.reduce((sum, d) => sum + (parseFloat(d.amount) || 0), 0)
);

// ── History modal state ──────────────────────────────────────
const showHistoryModal = ref(false);
const historyTarget    = ref(null);
const historyLoading   = ref(false);
const historyData      = ref({ payments: [], total_paid: 0, total_due: null, status: 'active' });
const markingDone      = ref(false);

// ── Form ─────────────────────────────────────────────────────
const defaultForm = () => ({
  name:                   '',
  type:                   'real_estate',
  amount_invested:        '',
  current_value:          '',
  // real_estate + other
  total_value:            '',
  period:                 '',
  months_of_payment:      '',
  amount_per_payment:     '',
  other_investment_title: '',
});

const form = ref(defaultForm());

// ── Derived ──────────────────────────────────────────────────
const otherTitles = computed(() => {
  const set = new Set();
  store.items.forEach(i => {
    if (i.type === 'other' && i.other_investment_title) set.add(i.other_investment_title);
  });
  return [...set];
});

const remaining = computed(() => {
  const due  = historyData.value.total_due ?? 0;
  const paid = historyData.value.total_paid ?? 0;
  return due - paid;
});

const obligationProgress = computed(() => {
  const total = store.portfolio?.total_obligations ?? 0;
  const paid  = store.portfolio?.total_paid_all ?? 0;
  if (!total) return 0;
  return Math.min(100, Math.round((paid / total) * 100));
});

const availableForPayments = computed(() => store.portfolio?.available_for_payments ?? 0);

const portfolioGainLoss = computed(() =>
  (store.portfolio?.total_current_value ?? 0) - (store.portfolio?.total_invested ?? 0)
);

const TYPE_PALETTE = {
  real_estate: '#F97316',
  mutual_fund: '#3B82F6',
  other:       '#8B5CF6',
  uitf:        '#6366F1',
  bonds:       '#EAB308',
  business:    '#14B8A6',
  stocks:      '#10B981',
  crypto:      '#EC4899',
};
const FALLBACK_COLORS = ['#6366F1','#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6'];

const typeChartSegments = computed(() => {
  const byType = store.portfolio?.by_type;
  if (!byType) return [];
  const total = Object.values(byType).reduce((s, t) => s + (t.amount_invested ?? 0), 0);
  if (total === 0) return [];
  return Object.entries(byType).map(([type, t], i) => ({
    label:  typeLabelFromType(type),
    value:  t.amount_invested ?? 0,
    pct:    total > 0 ? ((t.amount_invested ?? 0) / total) * 100 : 0,
    amount: t.amount_invested ?? 0,
    count:  t.count ?? 0,
    color:  TYPE_PALETTE[type] ?? FALLBACK_COLORS[i % FALLBACK_COLORS.length],
  })).filter(s => s.value > 0);
});

function typeLabelFromType(type) {
  return { real_estate: 'Real Estate', mutual_fund: 'Mutual Fund', other: 'Other', uitf: 'UITF', bonds: 'Bonds', business: 'Business', stocks: 'Stocks', crypto: 'Crypto' }[type] ?? type;
}

const payExceedsBalance = computed(() => {
  if (!payTarget.value) return false;
  const amount = payTarget.value.type !== 'mutual_fund'
    ? Number(payTarget.value.amount_per_payment || 0)
    : Number(payForm.value.amount || 0);
  return amount > availableForPayments.value;
});

// ── Helpers ──────────────────────────────────────────────────
function fmt(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function today() {
  return new Date().toISOString().split('T')[0];
}

function formatDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function gainLoss(item) {
  return Number(item.current_value || 0) - Number(item.amount_invested || 0);
}

function roi(item) {
  if (!item.amount_invested || item.amount_invested == 0) return 0;
  return (gainLoss(item) / Number(item.amount_invested)) * 100;
}

function isPayableType(item) {
  return ['real_estate', 'mutual_fund', 'other'].includes(item.type);
}

function payProgress(item) {
  const total = (item.months_of_payment || 0) * (item.amount_per_payment || 0);
  if (!total) return 0;
  return Math.min(100, Math.round(((item.total_paid || 0) / total) * 100));
}

function periodLabel(p) {
  return { monthly: 'Monthly', quarterly: 'Quarterly', semi_annual: 'Semi-Annual', annual: 'Annual' }[p] ?? p;
}

function typeLabel(item) {
  const map = {
    real_estate: 'Real Estate',
    mutual_fund: 'Mutual Fund',
    other:       item.other_investment_title || 'Other',
    uitf:        'UITF',
    bonds:       'Bonds',
    business:    'Business',
    stocks:      'Stocks',
    crypto:      'Crypto',
  };
  return map[item.type] ?? item.type;
}

function typeBadgeClass(type) {
  const map = {
    real_estate: 'bg-orange-100 text-orange-700',
    mutual_fund: 'bg-blue-100 text-blue-700',
    other:       'bg-purple-100 text-purple-700',
    uitf:        'bg-indigo-100 text-indigo-700',
    bonds:       'bg-yellow-100 text-yellow-700',
    business:    'bg-teal-100 text-teal-700',
    stocks:      'bg-green-100 text-green-700',
    crypto:      'bg-pink-100 text-pink-700',
  };
  return map[type] ?? 'bg-gray-100 text-gray-700';
}

function statusLabel(status) {
  return { active: 'Active', paid: 'Paid', done: 'Done' }[status] ?? 'Active';
}

function statusBadgeClass(status) {
  return {
    active: 'bg-blue-100 text-blue-700',
    paid:   'bg-green-100 text-green-700',
    done:   'bg-gray-100 text-gray-600',
  }[status] ?? 'bg-gray-100 text-gray-600';
}

// ── Add / Edit ────────────────────────────────────────────────
function openModal(item = null) {
  editing.value = item;
  form.value = item
    ? {
        name:                   item.name ?? '',
        type:                   item.type ?? 'real_estate',
        amount_invested:        item.amount_invested ?? '',
        current_value:          item.current_value ?? '',
        total_value:            item.total_value ?? '',
        period:                 item.period ?? '',
        months_of_payment:      item.months_of_payment ?? '',
        amount_per_payment:     item.amount_per_payment ?? '',
        other_investment_title: item.other_investment_title ?? '',
      }
    : defaultForm();
  formError.value = '';
  showModal.value = true;
}

async function handleSubmit() {
  saving.value = true;
  formError.value = '';
  try {
    const payload = { ...form.value };
    if (payload.type === 'real_estate') {
      payload.amount_invested = payload.total_value;
      payload.current_value   = payload.total_value;
    } else if (payload.type === 'mutual_fund') {
      payload.amount_invested = 0;
      payload.current_value   = 0;
      payload.total_value     = null;
    } else if (payload.type === 'other') {
      const months  = Number(payload.months_of_payment) || 0;
      const payment = Number(payload.amount_per_payment) || 0;
      payload.amount_invested = months * payment;
      payload.current_value   = 0;
      payload.total_value     = null;
    } else {
      payload.total_value = null;
    }
    if (!['real_estate', 'other'].includes(payload.type)) {
      payload.period = null;
      payload.months_of_payment = null;
      payload.amount_per_payment = null;
    }
    if (payload.type !== 'other') payload.other_investment_title = null;

    if (editing.value) {
      await store.update(editing.value.id, payload);
    } else {
      await store.create(payload);
    }
    showModal.value = false;
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save. Please try again.';
  } finally {
    saving.value = false;
  }
}

// ── Pay ───────────────────────────────────────────────────────
function openPayModal(item) {
  payTarget.value  = item;
  payError.value   = '';
  payForm.value    = {
    amount:       '',
    payment_date: today(),
    notes:        '',
  };
  showPayModal.value = true;
}

async function submitPayment() {
  payingSaving.value = true;
  payError.value = '';
  try {
    const payload = {
      amount:       payTarget.value.type !== 'mutual_fund'
                      ? payTarget.value.amount_per_payment
                      : payForm.value.amount,
      payment_date: payForm.value.payment_date,
      notes:        payForm.value.notes,
    };
    await store.addPayment(payTarget.value.id, payload);
    showPayModal.value = false;
  } catch (e) {
    payError.value = e.response?.data?.message ?? 'Failed to record payment.';
  } finally {
    payingSaving.value = false;
  }
}

// ── Dividend ──────────────────────────────────────────────────
function openDividendModal(item) {
  dividendTarget.value    = item;
  dividendForm.value      = { amount: '', paid_at: today(), notes: '' };
  dividendFormError.value = '';
  showDividendModal.value = true;
}

async function handleDividend() {
  dividendSaving.value    = true;
  dividendFormError.value = '';
  try {
    await store.storeDividend(dividendTarget.value.id, dividendForm.value);
    showDividendModal.value = false;
  } catch (e) {
    dividendFormError.value = e.response?.data?.message ?? 'Failed to record dividend.';
  } finally {
    dividendSaving.value = false;
  }
}

// ── Dividend History ──────────────────────────────────────────
async function openDivHistoryModal(item) {
  divHistoryTarget.value    = item;
  divHistoryItems.value     = [];
  showDivHistoryModal.value = true;
  divHistoryLoading.value   = true;
  try {
    divHistoryItems.value = await store.fetchDividends(item.id);
  } finally {
    divHistoryLoading.value = false;
  }
}

// ── History ───────────────────────────────────────────────────
async function openHistoryModal(item) {
  historyTarget.value     = item;
  historyData.value       = { payments: [], total_paid: 0, total_due: null, status: 'active' };
  historyLoading.value    = true;
  showHistoryModal.value  = true;
  try {
    historyData.value = await store.fetchPayments(item.id);
  } finally {
    historyLoading.value = false;
  }
}

async function handleMarkDone() {
  markingDone.value = true;
  try {
    await store.markDone(historyTarget.value.id);
    historyData.value.status = 'done';
    // update local item reference
    historyTarget.value = { ...historyTarget.value, payment_status: 'done' };
  } finally {
    markingDone.value = false;
  }
}

// ── Delete ────────────────────────────────────────────────────
function confirmDelete(item) {
  deleteTarget.value = item;
}

async function handleDelete() {
  await store.remove(deleteTarget.value.id);
  deleteTarget.value = null;
}

function changePage(page) {
  store.fetchAll({ page });
}

onMounted(async () => {
  store.fetchAll();
  store.fetchPortfolio();
});
</script>
