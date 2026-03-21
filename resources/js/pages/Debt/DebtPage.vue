<template>
  <div class="space-y-8">

    <!-- ── Page header ─────────────────────────────────────────────────── -->
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Debts</h1>
      <button @click="openModal()" class="bg-blue-600 text-white px-3 py-2 sm:px-4 rounded-lg hover:bg-blue-700 text-sm font-medium">
        + Add
      </button>
    </div>

    <div v-if="store.loading" class="text-center py-16 text-gray-400">Loading…</div>

    <template v-else>
      <!-- ── Personal Debts ─────────────────────────────────────────────── -->
      <section>
        <h2 class="text-base font-semibold text-gray-700 mb-3">Personal Debts</h2>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Lender</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Mode</th>
                <th class="text-right px-4 py-3 text-gray-500 font-medium">Amount</th>
                <th class="text-right px-4 py-3 text-gray-500 font-medium">Remaining</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Progress</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="personalItems.length === 0">
                <td colspan="6" class="text-center py-10 text-gray-400">No personal debts</td>
              </tr>
              <template v-for="item in personalItems" :key="item.id">
                <!-- Main row -->
                <tr class="border-b last:border-0 hover:bg-gray-50">
                  <td class="px-4 py-3 font-medium text-gray-700">{{ item.lender_name }}</td>
                  <td class="px-4 py-3">
                    <span class="text-xs px-2 py-1 rounded-full font-medium"
                      :class="item.personal_mode === 'shop_pay_later'
                        ? 'bg-sky-100 text-sky-700'
                        : 'bg-indigo-100 text-indigo-700'">
                      {{ item.personal_mode === 'shop_pay_later' ? 'Shop Pay Later' : 'Pay Installment' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-right font-semibold text-gray-700">{{ fmt(item.amount) }}</td>
                  <td class="px-4 py-3 text-right font-semibold"
                    :class="Number(item.remaining_balance) <= 0 ? 'text-green-600' : 'text-orange-600'">
                    {{ fmt(item.remaining_balance) }}
                  </td>
                  <td class="px-4 py-3">
                    <!-- Shop Pay Later: paid / unpaid badge -->
                    <template v-if="item.personal_mode === 'shop_pay_later'">
                      <span class="text-xs px-2 py-1 rounded-full"
                        :class="item.status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'">
                        {{ item.status === 'paid' ? 'Paid' : 'Unpaid' }}
                      </span>
                      <p v-if="item.payments && item.payments.length" class="text-xs text-gray-400 mt-0.5">
                        Paid {{ formatDate(item.payments[item.payments.length - 1]?.payment_date) }}
                      </p>
                    </template>
                    <!-- Pay Installment: X / Y -->
                    <template v-else>
                      <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-1.5 min-w-[60px]">
                          <div class="bg-indigo-500 h-1.5 rounded-full transition-all"
                            :style="{ width: installmentPct(item) + '%' }"></div>
                        </div>
                        <span class="text-xs text-gray-500 shrink-0">
                          {{ item.installments_paid ?? 0 }} / {{ item.months_to_pay }}
                        </span>
                      </div>
                    </template>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex gap-1.5 justify-end flex-wrap">
                      <!-- Pay / Pay Month button -->
                      <template v-if="item.status !== 'paid'">
                        <button
                          v-if="item.personal_mode === 'shop_pay_later'"
                          @click="confirmPersonalPay(item)"
                          class="text-green-600 hover:text-green-800 text-xs px-2 py-1 border border-green-300 rounded hover:bg-green-50">
                          Pay
                        </button>
                        <button
                          v-else-if="(item.installments_paid ?? 0) < item.months_to_pay"
                          @click="confirmPersonalPay(item)"
                          class="text-indigo-600 hover:text-indigo-800 text-xs px-2 py-1 border border-indigo-300 rounded hover:bg-indigo-50">
                          Pay Month
                        </button>
                      </template>
                      <!-- History toggle for pay_installment -->
                      <button
                        v-if="item.personal_mode === 'pay_installment' && item.payments && item.payments.length"
                        @click="toggleHistory(item.id)"
                        class="text-gray-500 hover:text-gray-700 text-xs px-2 py-1 border rounded">
                        {{ expandedId === item.id ? 'Hide' : 'History' }}
                      </button>
                      <button @click="openModal(item)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">Edit</button>
                      <button @click="confirmDelete(item)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
                    </div>
                  </td>
                </tr>
                <!-- Payment history sub-row -->
                <tr v-if="expandedId === item.id && item.payments && item.payments.length" class="bg-indigo-50">
                  <td colspan="6" class="px-6 py-3">
                    <p class="text-xs font-medium text-indigo-700 mb-2">Payment History</p>
                    <div class="flex flex-wrap gap-2">
                      <span
                        v-for="p in item.payments" :key="p.id"
                        class="text-xs bg-white border border-indigo-200 text-indigo-700 px-2.5 py-1 rounded-full">
                        #{{ p.installment_number }} · {{ formatDate(p.payment_date) }} · {{ fmt(p.amount) }}
                      </span>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
          </div>
        </div>
      </section>

      <!-- ── Business Debts ─────────────────────────────────────────────── -->
      <section>
        <h2 class="text-base font-semibold text-gray-700 mb-3">Business Debts</h2>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[600px]">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Lender</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Borrower</th>
                <th class="text-right px-4 py-3 text-gray-500 font-medium">Principal</th>
                <th class="text-right px-4 py-3 text-gray-500 font-medium">Annual Rate</th>
                <th class="text-right px-4 py-3 text-gray-500 font-medium">Remaining</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Status</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="businessItems.length === 0">
                <td colspan="7" class="text-center py-10 text-gray-400">No business debts</td>
              </tr>
              <tr v-for="item in businessItems" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-700">{{ item.lender_name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ item.borrower_name ?? '—' }}</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-700">{{ fmt(item.amount) }}</td>
                <td class="px-4 py-3 text-right text-gray-500">{{ item.interest_rate ? item.interest_rate + '%' : '—' }}</td>
                <td class="px-4 py-3 text-right font-semibold"
                  :class="Number(item.remaining_balance) <= 0 ? 'text-green-600' : 'text-orange-600'">
                  {{ fmt(item.remaining_balance) }}
                </td>
                <td class="px-4 py-3">
                  <span class="text-xs px-2 py-1 rounded-full"
                    :class="item.status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'">
                    {{ item.status }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex gap-1.5 justify-end flex-wrap">
                    <button
                      v-if="item.status !== 'paid'"
                      @click="openBusinessPay(item)"
                      class="text-purple-600 hover:text-purple-800 text-xs px-2 py-1 border border-purple-300 rounded hover:bg-purple-50">
                      Pay
                    </button>
                    <button
                      v-if="item.payments && item.payments.length"
                      @click="openInvoice(item, item.payments[item.payments.length - 1])"
                      class="text-gray-500 hover:text-gray-700 text-xs px-2 py-1 border rounded">
                      Print
                    </button>
                    <button @click="openModal(item)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">Edit</button>
                    <button @click="confirmDelete(item)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          </div>
        </div>
      </section>
    </template>

    <!-- ── Add / Edit Modal ───────────────────────────────────────────────── -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editing ? 'Edit Debt' : 'Add Debt' }}</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">
          <!-- Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
            <select v-model="form.type" required :disabled="!!editing"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white disabled:bg-gray-100">
              <option value="personal">Personal</option>
              <option value="business">Business</option>
            </select>
          </div>

          <!-- Personal mode -->
          <div v-if="form.type === 'personal'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Mode *</label>
            <select v-model="form.personal_mode" required :disabled="!!editing"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white disabled:bg-gray-100">
              <option value="shop_pay_later">Shop Pay Later</option>
              <option value="pay_installment">Pay Installment</option>
            </select>
          </div>

          <!-- Lender Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Lender Name *</label>
            <input v-model="form.lender_name" required placeholder="Person or institution"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Business: Borrower Name -->
          <div v-if="form.type === 'business'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Borrower Name *</label>
            <input v-model="form.borrower_name" required
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Amount -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
            <input v-model="form.amount" type="number" min="0.01" step="0.01" required
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Business: Annual Interest Rate -->
          <div v-if="form.type === 'business'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Annual Interest Rate (%) *</label>
            <input v-model="form.interest_rate" type="number" min="0" max="100" step="0.001" required
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <p class="text-xs text-gray-400 mt-1">Enter a value between 0.000 and 100.000</p>
          </div>

          <!-- Pay Installment fields -->
          <template v-if="form.type === 'personal' && form.personal_mode === 'pay_installment'">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Months to Pay *</label>
              <input v-model="form.months_to_pay" type="number" min="1" step="1" required
                @input="computeMonthly"
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Payment *</label>
              <input v-model="form.monthly_payment" type="number" min="0.01" step="0.01" required
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <p class="text-xs text-gray-400 mt-1">Auto-computed from amount ÷ months (editable)</p>
            </div>
          </template>

          <div v-if="formError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showModal = false"
              class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-blue-700">
              {{ saving ? 'Saving…' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Personal Pay Confirm ───────────────────────────────────────────── -->
    <div v-if="personalPayTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Confirm Payment</h3>
        <template v-if="personalPayTarget.personal_mode === 'shop_pay_later'">
          <p class="text-sm text-gray-500 mb-1">Debt: <strong>{{ personalPayTarget.lender_name }}</strong></p>
          <p class="text-sm text-gray-500 mb-4">Pay off full balance of <strong>{{ fmt(personalPayTarget.remaining_balance) }}</strong>?</p>
        </template>
        <template v-else>
          <p class="text-sm text-gray-500 mb-1">Debt: <strong>{{ personalPayTarget.lender_name }}</strong></p>
          <p class="text-sm text-gray-500 mb-1">
            Installment #{{ (personalPayTarget.installments_paid ?? 0) + 1 }} of {{ personalPayTarget.months_to_pay }}
          </p>
          <p class="text-sm text-gray-500 mb-4">Amount: <strong>{{ fmt(personalPayTarget.monthly_payment) }}</strong></p>
        </template>
        <div class="flex justify-end gap-3">
          <button @click="personalPayTarget = null" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handlePersonalPay" :disabled="paying" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 disabled:opacity-50">
            {{ paying ? 'Recording…' : 'Confirm' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── Business Pay Modal ─────────────────────────────────────────────── -->
    <div v-if="businessPayTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">Business Debt Payment</h2>
          <button @click="closeBusinessPay" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5 space-y-5">

          <!-- Balance breakdown (readonly) -->
          <div class="bg-gray-50 rounded-lg p-4 space-y-2 text-sm">
            <p class="font-medium text-gray-700 mb-3">Balance Calculation</p>
            <div v-if="loadingBalance" class="text-gray-400 text-center py-2">Calculating…</div>
            <template v-else-if="balanceInfo">
              <div class="flex justify-between"><span class="text-gray-500">Amount Borrowed</span><span class="font-medium">{{ fmt(balanceInfo.amount_borrowed) }}</span></div>
              <div class="flex justify-between"><span class="text-gray-500">Annual Interest Rate</span><span class="font-medium">{{ balanceInfo.annual_rate }}%</span></div>
              <div class="flex justify-between"><span class="text-gray-500">Days Accumulated</span><span class="font-medium">{{ balanceInfo.days_elapsed }} days</span></div>
              <div class="flex justify-between"><span class="text-gray-500">Daily Interest</span><span class="font-medium text-orange-500">{{ fmt(balanceInfo.daily_interest ?? 0) }}</span></div>
              <div class="flex justify-between"><span class="text-gray-500">Interest Accrued</span><span class="font-medium text-orange-600">{{ fmt(balanceInfo.accrued_interest) }}</span></div>
              <div class="flex justify-between border-t pt-2"><span class="font-semibold">Balance to Pay</span><span class="font-bold text-red-600">{{ fmt(balanceInfo.balance_due) }}</span></div>
              <div class="flex justify-between mt-1"><span class="font-semibold text-gray-700">Rounded Balance to Pay</span><span class="font-bold text-red-700">{{ fmt(Math.round(balanceInfo.balance_due)) }}</span></div>
            </template>
          </div>

          <!-- Amount to pay input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount to Pay *</label>
            <input v-model="businessPayAmount" type="number" min="0.01" step="0.01"
              @input="computePayBreakdown"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
          </div>

          <!-- Payment breakdown preview -->
          <div v-if="payBreakdown" class="bg-purple-50 rounded-lg p-4 space-y-2 text-sm">
            <p class="font-medium text-purple-700 mb-2">Payment Breakdown</p>
            <div class="flex justify-between"><span class="text-gray-500">Interest Portion</span><span class="font-medium text-orange-600">{{ fmt(payBreakdown.interest_paid) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Principal Deducted</span><span class="font-medium text-blue-600">{{ fmt(payBreakdown.principal_paid) }}</span></div>
          </div>

          <div v-if="businessPayError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ businessPayError }}</div>

          <div class="flex justify-end gap-3">
            <button @click="closeBusinessPay" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button @click="handleBusinessPay" :disabled="paying || !businessPayAmount"
              class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-700 disabled:opacity-50">
              {{ paying ? 'Recording…' : 'Record & Invoice' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Invoice Modal (A5 Print) ───────────────────────────────────────── -->
    <div v-if="invoiceData" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b no-print">
          <h2 class="font-semibold text-gray-800">Invoice</h2>
          <div class="flex gap-2">
            <button @click="printInvoice" class="bg-gray-800 text-white text-sm px-3 py-1.5 rounded-lg hover:bg-gray-900">Print / Save PDF</button>
            <button @click="invoiceData = null" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
          </div>
        </div>
        <!-- Invoice content — A5 layout matching professional invoice sample -->
        <div id="invoice-print-area" class="p-8 font-sans text-gray-900 bg-white">

          <!-- Header: Logo (left) + INVOICE title (center/right area) -->
          <div class="flex justify-between items-start mb-6">
            <!-- Left: Logo / Company name -->
            <div class="flex flex-col">
              <div class="w-14 h-14 bg-gray-800 flex items-center justify-center rounded mb-1">
                <span class="text-white text-xs font-bold text-center leading-tight px-1">BUDGET<br>TRACKER</span>
              </div>
            </div>
            <!-- Right: INVOICE title -->
            <div class="text-right">
              <h1 class="text-2xl font-bold underline tracking-widest">INVOICE</h1>
            </div>
          </div>

          <!-- TO + Invoice details -->
          <div class="flex justify-between mb-6 text-xs">
            <div class="flex-1">
              <p class="font-bold text-gray-500 uppercase tracking-wide mb-1">TO</p>
              <p class="font-semibold text-gray-800 border-b border-dotted border-gray-400 pb-1">{{ invoiceData.borrower_name ?? '—' }}</p>
              <p class="text-gray-500 mt-1 border-b border-dotted border-gray-400 pb-1">{{ invoiceData.lender_name }}</p>
            </div>
            <div class="ml-8 text-right space-y-1">
              <div class="flex gap-4 justify-end">
                <span class="font-bold text-gray-500 uppercase tracking-wide">INVOICE NO:</span>
                <span class="border-b border-dotted border-gray-400 min-w-[80px] text-right">PAY-{{ String(invoiceData.payment_id).padStart(5,'0') }}</span>
              </div>
              <div class="flex gap-4 justify-end">
                <span class="font-bold text-gray-500 uppercase tracking-wide">DATE:</span>
                <span class="border-b border-dotted border-gray-400 min-w-[80px] text-right">{{ invoiceData.payment_date }}</span>
              </div>
            </div>
          </div>

          <!-- Line items table -->
          <table class="w-full text-xs mb-4 border-collapse border border-gray-800">
            <thead class="bg-gray-800 text-white">
              <tr>
                <th class="px-3 py-2 text-left font-semibold border-r border-gray-600 w-8">No.</th>
                <th class="px-3 py-2 text-left font-semibold border-r border-gray-600">ITEM DESCRIPTION</th>
                <th class="px-3 py-2 text-center font-semibold border-r border-gray-600 w-12">Qty.</th>
                <th class="px-3 py-2 text-right font-semibold border-r border-gray-600 w-28">PRICE</th>
                <th class="px-3 py-2 text-right font-semibold w-28">TOTAL</th>
              </tr>
            </thead>
            <tbody>
              <tr class="border-b border-gray-300">
                <td class="px-3 py-2 border-r border-gray-300 text-center">1</td>
                <td class="px-3 py-2 border-r border-gray-300">
                  Amount Borrowed (Principal)
                </td>
                <td class="px-3 py-2 border-r border-gray-300 text-center">1</td>
                <td class="px-3 py-2 border-r border-gray-300 text-right">{{ fmtNum(invoiceData.amount_borrowed) }}</td>
                <td class="px-3 py-2 text-right">{{ fmtNum(invoiceData.amount_borrowed) }}</td>
              </tr>
              <tr class="border-b border-gray-300">
                <td class="px-3 py-2 border-r border-gray-300 text-center">2</td>
                <td class="px-3 py-2 border-r border-gray-300">
                  Interest Accrued
                  <span class="text-gray-400 ml-1">({{ invoiceData.annual_rate }}% p.a. × {{ invoiceData.days_elapsed }} days)</span>
                </td>
                <td class="px-3 py-2 border-r border-gray-300 text-center">1</td>
                <td class="px-3 py-2 border-r border-gray-300 text-right">{{ fmtNum(invoiceData.accrued_interest) }}</td>
                <td class="px-3 py-2 text-right">{{ fmtNum(invoiceData.accrued_interest) }}</td>
              </tr>
              <!-- Empty filler rows to match invoice template style -->
              <tr v-for="n in 3" :key="n" class="border-b border-gray-200">
                <td class="px-3 py-2 border-r border-gray-200 text-center text-gray-300">{{ n + 2 }}</td>
                <td class="px-3 py-2 border-r border-gray-200">&nbsp;</td>
                <td class="px-3 py-2 border-r border-gray-200"></td>
                <td class="px-3 py-2 border-r border-gray-200"></td>
                <td class="px-3 py-2"></td>
              </tr>
            </tbody>
          </table>

          <!-- Bottom section: Payment Info (left) + Totals (right) -->
          <div class="flex justify-between items-start gap-6">
            <!-- Payment Info -->
            <div class="flex-1 text-xs text-gray-700">
              <p class="font-bold uppercase tracking-wide text-gray-500 mb-2">PAYMENT INFO:</p>
              <p class="border-b border-dotted border-gray-400 pb-1 mb-1">
                Interest Applied: {{ fmtNum(invoiceData.interest_paid) }}
              </p>
              <p class="border-b border-dotted border-gray-400 pb-1">
                Principal Deducted: {{ fmtNum(invoiceData.principal_paid) }}
              </p>
            </div>

            <!-- Sub Total / Tax / Total block -->
            <div class="text-xs min-w-[200px]">
              <table class="w-full border-collapse border border-gray-300">
                <tr class="border-b border-gray-300">
                  <td class="px-3 py-1.5 text-gray-600 font-medium">SUB TOTAL</td>
                  <td class="px-3 py-1.5 text-right border-l border-gray-300">{{ fmtNum(invoiceData.balance_due) }}</td>
                </tr>
                <tr class="border-b border-gray-300">
                  <td class="px-3 py-1.5 text-gray-600 font-medium bg-gray-100">TAX</td>
                  <td class="px-3 py-1.5 text-right border-l border-gray-300 bg-gray-100">—</td>
                </tr>
                <tr class="bg-gray-800 text-white font-bold">
                  <td class="px-3 py-1.5">TOTAL</td>
                  <td class="px-3 py-1.5 text-right border-l border-gray-600">{{ fmtNum(invoiceData.amount_paid) }}</td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Authorised Sign -->
          <div class="mt-8 text-center text-xs text-gray-400">
            <div class="border-t border-dotted border-gray-400 pt-2 w-40 mx-auto"></div>
            <p>Authorised Sign</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Confirm Delete ─────────────────────────────────────────────────── -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Debt</h3>
        <p class="text-sm text-gray-500 mb-4">Delete debt from "{{ deleteTarget.lender_name }}"? This cannot be undone.</p>
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
import { useDebtStore } from '@/stores/debt';
import { formatDate } from '@/utils/date';

const store = useDebtStore();

// ── State ──────────────────────────────────────────────────────────────────
const showModal        = ref(false);
const editing          = ref(null);
const deleteTarget     = ref(null);
const saving           = ref(false);
const paying           = ref(false);
const formError        = ref('');
const expandedId       = ref(null);

const personalPayTarget = ref(null);

const businessPayTarget = ref(null);
const loadingBalance    = ref(false);
const balanceInfo       = ref(null);
const businessPayAmount = ref('');
const payBreakdown      = ref(null);
const businessPayError  = ref('');
const lastBreakdown     = ref(null); // stores breakdown after successful business pay

const invoiceData = ref(null);

// ── Computed ───────────────────────────────────────────────────────────────
const personalItems = computed(() => store.items.filter(i => i.type === 'personal'));
const businessItems = computed(() => store.items.filter(i => i.type === 'business'));

// ── Form ───────────────────────────────────────────────────────────────────
const defaultForm = () => ({
  type:           'personal',
  personal_mode:  'shop_pay_later',
  lender_name:    '',
  borrower_name:  '',
  business_name:  '',
  amount:         '',
  interest_rate:  '',
  months_to_pay:  '',
  monthly_payment: '',
});

const form = ref(defaultForm());

function computeMonthly() {
  if (form.value.amount && form.value.months_to_pay) {
    const monthly = Number(form.value.amount) / Number(form.value.months_to_pay);
    form.value.monthly_payment = Math.round(monthly * 100) / 100;
  }
}

// ── Helpers ────────────────────────────────────────────────────────────────
function fmt(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function fmtNum(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function installmentPct(item) {
  if (!item.months_to_pay) return 0;
  return Math.min(100, ((item.installments_paid ?? 0) / item.months_to_pay) * 100);
}

function toggleHistory(id) {
  expandedId.value = expandedId.value === id ? null : id;
}

// ── Add / Edit ─────────────────────────────────────────────────────────────
function openModal(item = null) {
  editing.value = item;
  form.value = item
    ? {
        type:           item.type,
        personal_mode:  item.personal_mode ?? 'shop_pay_later',
        lender_name:    item.lender_name   ?? '',
        borrower_name:  item.borrower_name ?? '',
        business_name:  item.business_name ?? '',
        amount:         item.amount,
        interest_rate:  item.interest_rate ?? '',
        months_to_pay:  item.months_to_pay ?? '',
        monthly_payment: item.monthly_payment ?? '',
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
    // Remove fields irrelevant to the selected type
    if (payload.type === 'personal') {
      delete payload.borrower_name;
      delete payload.interest_rate;
      if (payload.personal_mode !== 'pay_installment') {
        delete payload.months_to_pay;
        delete payload.monthly_payment;
      }
    } else {
      delete payload.personal_mode;
      delete payload.months_to_pay;
      delete payload.monthly_payment;
    }

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

// ── Personal Pay ───────────────────────────────────────────────────────────
function confirmPersonalPay(item) {
  personalPayTarget.value = item;
}

async function handlePersonalPay() {
  if (!personalPayTarget.value) return;
  paying.value = true;
  try {
    await store.pay(personalPayTarget.value.id);
    personalPayTarget.value = null;
  } catch (e) {
    alert(e.response?.data?.message ?? 'Payment failed.');
  } finally {
    paying.value = false;
  }
}

// ── Business Pay ───────────────────────────────────────────────────────────
async function openBusinessPay(item) {
  businessPayTarget.value = item;
  businessPayAmount.value = '';
  payBreakdown.value      = null;
  businessPayError.value  = '';
  balanceInfo.value       = null;
  loadingBalance.value    = true;
  try {
    balanceInfo.value = await store.getBalance(item.id);
  } finally {
    loadingBalance.value = false;
  }
}

function closeBusinessPay() {
  businessPayTarget.value = null;
  balanceInfo.value       = null;
  businessPayAmount.value = '';
  payBreakdown.value      = null;
}

function computePayBreakdown() {
  if (!balanceInfo.value || !businessPayAmount.value) {
    payBreakdown.value = null;
    return;
  }
  const amount       = parseFloat(businessPayAmount.value) || 0;
  const interestDue  = balanceInfo.value.accrued_interest;
  const interestPaid = Math.min(amount, interestDue);
  const principalPaid = Math.max(0, Math.min(amount - interestDue, balanceInfo.value.amount_borrowed));
  payBreakdown.value = {
    interest_paid:  Math.round(interestPaid  * 100) / 100,
    principal_paid: Math.round(principalPaid * 100) / 100,
  };
}

async function handleBusinessPay() {
  if (!businessPayTarget.value || !businessPayAmount.value) return;
  paying.value = true;
  businessPayError.value = '';
  try {
    const result = await store.pay(businessPayTarget.value.id, { amount: parseFloat(businessPayAmount.value) });
    // Build invoice data from server breakdown
    const debt = businessPayTarget.value;
    const bd   = result.breakdown;
    lastBreakdown.value = bd;

    closeBusinessPay();

    invoiceData.value = {
      payment_id:      result.payment.id,
      payment_date:    result.payment.payment_date,
      lender_name:     debt.lender_name,
      borrower_name:   debt.borrower_name,
      amount_borrowed: bd.amount_borrowed,
      annual_rate:     bd.annual_rate,
      days_elapsed:    bd.days_elapsed,
      accrued_interest: bd.accrued_interest,
      balance_due:     bd.balance_due,
      amount_paid:     bd.amount_paid,
      interest_paid:   bd.interest_paid,
      principal_paid:  bd.principal_paid,
    };
  } catch (e) {
    businessPayError.value = e.response?.data?.message ?? 'Payment failed.';
  } finally {
    paying.value = false;
  }
}

// ── Invoice / Print ────────────────────────────────────────────────────────
function openInvoice(debt, payment) {
  invoiceData.value = {
    payment_id:       payment.id,
    payment_date:     payment.payment_date,
    lender_name:      debt.lender_name,
    borrower_name:    debt.borrower_name,
    amount_borrowed:  payment.principal_paid !== null
      ? Number(debt.amount) // original amount for reference
      : Number(debt.amount),
    annual_rate:      debt.interest_rate,
    days_elapsed:     payment.days_elapsed,
    accrued_interest: payment.interest_paid,
    balance_due:      Number(payment.amount),
    amount_paid:      Number(payment.amount),
    interest_paid:    Number(payment.interest_paid  ?? 0),
    principal_paid:   Number(payment.principal_paid ?? 0),
  };
}

function printInvoice() {
  const el = document.getElementById('invoice-print-area');
  if (!el) return;
  const content = el.innerHTML;
  const win = window.open('', '_blank', 'width=600,height=850');
  win.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Invoice PAY-${String(invoiceData.value?.payment_id ?? '').padStart(5,'0')}</title>
      <style>
        @page { size: A5; margin: 10mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111827; background: #fff; padding: 20px; }
        h1 { font-size: 20px; font-weight: 700; text-decoration: underline; letter-spacing: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px 8px; }
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .items-start { align-items: flex-start; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .text-white { color: #fff; }
        .text-gray-300 { color: #d1d5db; }
        .text-gray-400 { color: #9ca3af; }
        .text-gray-500 { color: #6b7280; }
        .text-gray-600 { color: #4b5563; }
        .text-gray-800 { color: #1f2937; }
        .bg-gray-800 { background-color: #1f2937; color: #fff; }
        .bg-gray-100 { background-color: #f3f4f6; }
        .border { border: 1px solid #374151; }
        .border-r { border-right: 1px solid #374151; }
        .border-b { border-bottom: 1px solid #374151; }
        .border-l { border-left: 1px solid #374151; }
        .border-gray-200 { border-color: #e5e7eb; }
        .border-gray-300 { border-color: #d1d5db; }
        .border-gray-600 { border-color: #4b5563; }
        .border-dotted { border-style: dotted; }
        .min-w-\\[80px\\] { min-width: 80px; }
        .min-w-\\[200px\\] { min-width: 200px; }
        .w-8 { width: 32px; }
        .w-12 { width: 48px; }
        .w-14 { width: 56px; }
        .w-28 { width: 112px; }
        .w-40 { width: 160px; }
        .h-14 { height: 56px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-6 { margin-bottom: 24px; }
        .mt-8 { margin-top: 32px; }
        .ml-1 { margin-left: 4px; }
        .ml-8 { margin-left: 32px; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .pb-1 { padding-bottom: 4px; }
        .pb-2 { padding-bottom: 8px; }
        .pt-2 { padding-top: 8px; }
        .px-3 { padding-left: 12px; padding-right: 12px; }
        .py-1\\.5 { padding-top: 6px; padding-bottom: 6px; }
        .py-2 { padding-top: 8px; padding-bottom: 8px; }
        .p-8 { padding: 32px; }
        .space-y-1 > * + * { margin-top: 4px; }
        .gap-4 { gap: 16px; }
        .gap-6 { gap: 24px; }
        .flex-1 { flex: 1; }
        .tracking-wide { letter-spacing: 0.05em; }
        .tracking-widest { letter-spacing: 0.25em; }
        .uppercase { text-transform: uppercase; }
        .rounded { border-radius: 4px; }
        .items-center { align-items: center; }
        .justify-end { justify-content: flex-end; }
        .text-xs { font-size: 11px; }
        .text-2xl { font-size: 20px; }
        .leading-tight { line-height: 1.25; }
        .px-1 { padding-left: 4px; padding-right: 4px; }
        .underline { text-decoration: underline; }
      </style>
    </head>
    <body>${content}</body>
    </html>
  `);
  win.document.close();
  win.focus();
  setTimeout(() => { win.print(); }, 400);
}

// ── Delete ─────────────────────────────────────────────────────────────────
function confirmDelete(item) {
  deleteTarget.value = item;
}

async function handleDelete() {
  await store.remove(deleteTarget.value.id);
  deleteTarget.value = null;
}

// ── Init ───────────────────────────────────────────────────────────────────
onMounted(() => store.fetchAll());
</script>
