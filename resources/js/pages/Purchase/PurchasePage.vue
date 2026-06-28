<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Purchases</h1>
      <button @click="openModal()" class="bg-blue-600 text-white px-3 py-2 sm:px-4 rounded-lg hover:bg-blue-700 text-sm font-medium">+ Add</button>
    </div>

    <!-- Summary Cards -->
    <div v-if="store.summary" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Total Purchases</p>
        <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(store.summary.total_purchase) }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Total Paid</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(store.summary.total_paid) }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Remaining Balance</p>
        <p class="text-2xl font-bold" :class="store.summary.total_remaining > 0 ? 'text-red-600' : 'text-gray-500'">
          {{ formatCurrency(store.summary.total_remaining) }}
        </p>
        <p class="text-xs text-gray-400 mt-1">Credit card balances only</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 space-y-2 sm:space-y-0 sm:flex sm:flex-wrap sm:gap-3">
      <div class="grid grid-cols-2 gap-2 sm:contents">
        <select v-model="filters.payment_method" class="border rounded-lg px-3 py-2 text-sm bg-white w-full sm:w-auto">
          <option value="">All Methods</option>
          <option value="cash">Cash</option>
          <option value="credit_card">Credit Card</option>
          <option value="other">Other</option>
        </select>
        <input v-model="filters.search" type="text" class="border rounded-lg px-3 py-2 text-sm w-full sm:min-w-[180px] sm:w-auto" placeholder="Search item..." />
      </div>
      <div class="flex gap-2 sm:contents">
        <button @click="loadData" class="flex-1 sm:flex-none bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">Filter</button>
        <button @click="resetFilters" class="text-gray-400 text-sm px-3 py-2 hover:text-gray-600">Reset</button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Item</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Payment</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Total Cost</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Amount Paid</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Remaining</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Installments</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Date</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="8" class="text-center py-10 text-gray-400">No purchases found</td>
            </tr>
            <template v-for="item in store.items" :key="item.id">
              <tr class="border-b hover:bg-gray-50" :class="{ 'border-b-0': expandedId === item.id }">
                <td class="px-4 py-3 font-medium text-gray-700">{{ item.item_name }}</td>
                <td class="px-4 py-3">
                  <span class="text-xs px-2 py-1 rounded-full font-medium" :class="methodClass(item.payment_method)">
                    {{ methodLabel(item.payment_method) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-gray-700">{{ formatCurrency(item.total_cost) }}</td>
                <td class="px-4 py-3 text-right text-green-600">{{ formatCurrency(item.amount_paid) }}</td>
                <td class="px-4 py-3 text-right" :class="item.remaining_balance > 0 ? 'text-red-600 font-semibold' : 'text-gray-400'">
                  {{ formatCurrency(item.remaining_balance) }}
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">
                  <template v-if="item.payment_method === 'credit_card'">
                    <div>{{ item.installments_paid }} / {{ item.installment_count }} months paid</div>
                    <div class="text-gray-400">{{ formatCurrency(item.installment_amount) }}/mo</div>
                    <div v-if="item.remaining_installments > 0" class="text-amber-600">
                      {{ item.remaining_installments }} months left
                    </div>
                    <div v-else class="text-green-600">Fully paid</div>
                  </template>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ formatDate(item.purchase_date) }}</td>
                <td class="px-4 py-3">
                  <div class="flex gap-2 justify-end flex-wrap">
                    <button
                      v-if="item.payment_method === 'credit_card' && item.remaining_installments > 0"
                      @click="confirmPayInstallment(item)"
                      class="text-green-600 hover:text-green-800 text-xs px-2 py-1 border border-green-300 rounded bg-green-50 hover:bg-green-100"
                    >Pay Month</button>
                    <button
                      v-if="item.payment_method === 'credit_card' && item.payments?.length"
                      @click="expandedId = expandedId === item.id ? null : item.id"
                      class="text-violet-500 hover:text-violet-700 text-xs px-2 py-1 border border-violet-200 rounded bg-violet-50 hover:bg-violet-100"
                    >{{ expandedId === item.id ? 'Hide' : 'History' }}</button>
                    <button @click="openModal(item)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">Edit</button>
                    <button @click="confirmDelete(item)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
                  </div>
                </td>
              </tr>

              <!-- Payment History expandable row -->
              <tr v-if="expandedId === item.id && item.payments?.length" class="border-b bg-violet-50">
                <td colspan="8" class="px-6 py-3">
                  <p class="text-xs font-semibold text-violet-700 mb-2">Payment History</p>
                  <div class="flex flex-wrap gap-2">
                    <div
                      v-for="p in item.payments"
                      :key="p.id"
                      class="flex items-center gap-1.5 bg-white border border-violet-200 rounded-lg px-3 py-1.5 text-xs"
                    >
                      <span class="font-semibold text-violet-700">#{{ p.installment_number }}</span>
                      <span class="text-gray-400">·</span>
                      <span class="text-gray-600">{{ formatDate(p.paid_at) }}</span>
                      <span class="text-gray-400">·</span>
                      <span class="font-medium text-green-600">{{ formatCurrency(p.amount) }}</span>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="store.pagination" class="flex justify-between items-center text-sm text-gray-500">
      <span>{{ store.pagination.total }} records</span>
      <div class="flex gap-2">
        <button
          :disabled="store.pagination.current_page <= 1"
          @click="changePage(store.pagination.current_page - 1)"
          class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100"
        >Prev</button>
        <span class="px-3 py-1">{{ store.pagination.current_page }} / {{ store.pagination.last_page }}</span>
        <button
          :disabled="store.pagination.current_page >= store.pagination.last_page"
          @click="changePage(store.pagination.current_page + 1)"
          class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100"
        >Next</button>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editing ? 'Edit Purchase' : 'Add Purchase' }}</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Item Name *</label>
            <input v-model="form.item_name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Laptop, Refrigerator" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date *</label>
            <input v-model="form.purchase_date" type="date" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
            <div class="grid grid-cols-3 gap-2">
              <button
                v-for="m in paymentMethods" :key="m.value"
                type="button"
                @click="form.payment_method = m.value"
                :class="[
                  'px-3 py-2 rounded-lg text-sm font-medium border transition-colors',
                  form.payment_method === m.value
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400'
                ]"
              >{{ m.label }}</button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Total Cost *</label>
            <input v-model="form.total_cost" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Credit Card fields -->
          <template v-if="form.payment_method === 'credit_card'">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Months to Pay *</label>
              <input
                v-model.number="form.installment_count"
                type="number" min="1" required
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="e.g. 12"
                @input="autoCalcMonthly"
              />
            </div>
            <div class="bg-blue-50 rounded-lg px-3 py-2 text-xs space-y-1 text-gray-600">
              <div class="flex justify-between">
                <span>Total cost</span>
                <span class="font-medium">{{ formatCurrency(form.total_cost) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Monthly payment</span>
                <span class="font-medium">{{ formatCurrency(form.installment_amount || computedMonthly) }}/mo</span>
              </div>
              <div class="flex justify-between font-semibold text-blue-700 border-t pt-1">
                <span>Total over {{ form.installment_count || 0 }} months</span>
                <span>{{ formatCurrency((form.installment_amount || computedMonthly) * (form.installment_count || 0)) }}</span>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Months Already Paid</label>
              <input
                v-model.number="form.installments_paid"
                type="number" min="0"
                :max="form.installment_count"
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="0"
              />
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

    <!-- Confirm Delete -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Purchase</h3>
        <p class="text-sm text-gray-500 mb-4">Delete "{{ deleteTarget.item_name }}"? This cannot be undone.</p>
        <div class="flex justify-end gap-3">
          <button @click="deleteTarget = null" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handleDelete" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>

    <!-- Confirm Pay Month -->
    <div v-if="payInstallmentTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-800">Record Monthly Payment</h3>
            <p class="text-xs text-gray-400 mt-0.5">Installment #{{ (payInstallmentTarget.installments_paid ?? 0) + 1 }} of {{ payInstallmentTarget.installment_count }}</p>
          </div>
        </div>
        <div class="bg-gray-50 rounded-lg px-4 py-3 mb-4 space-y-1.5 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500">Item</span>
            <span class="font-medium text-gray-700">{{ payInstallmentTarget.item_name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Amount to pay</span>
            <span class="font-semibold text-green-600">{{ formatCurrency(payInstallmentTarget.installment_amount) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">Remaining after</span>
            <span class="font-medium text-gray-700">{{ payInstallmentTarget.remaining_installments - 1 }} months</span>
          </div>
        </div>
        <div class="flex justify-end gap-3">
          <button @click="payInstallmentTarget = null" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handlePayInstallment" :disabled="paying" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 disabled:opacity-50">
            {{ paying ? 'Processing...' : 'Confirm Payment' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePurchaseStore } from '@/stores/purchase';
import { formatDate } from '@/utils/date';

const store = usePurchaseStore();

const showModal             = ref(false);
const editing               = ref(null);
const deleteTarget          = ref(null);
const payInstallmentTarget  = ref(null);
const saving                = ref(false);
const paying                = ref(false);
const formError             = ref('');
const filters               = ref({ payment_method: '', search: '' });
const expandedId            = ref(null); // which purchase row has payment history expanded

const paymentMethods = [
  { value: 'cash',        label: 'Cash'        },
  { value: 'credit_card', label: 'Credit Card' },
  { value: 'other',       label: 'Other'       },
];

const defaultForm = () => ({
  item_name:          '',
  total_cost:         '',
  payment_method:     'cash',
  purchase_date:      new Date().toISOString().split('T')[0],
  installment_count:  '',
  installment_amount: '',
  installments_paid:  0,
});

const form = ref(defaultForm());

// Auto-calculate monthly cost from total / months
const computedMonthly = computed(() => {
  const total  = parseFloat(form.value.total_cost) || 0;
  const months = parseInt(form.value.installment_count) || 0;
  if (total > 0 && months > 0) return total / months;
  return 0;
});

function autoCalcMonthly() {
  // Clear manual override so hint shows auto value
  if (!form.value.installment_amount) return;
  form.value.installment_amount = '';
}

function formatCurrency(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function methodLabel(method) {
  return { cash: 'Cash', credit_card: 'Credit Card', other: 'Other' }[method] ?? method;
}

function methodClass(method) {
  return {
    cash:        'bg-green-100 text-green-700',
    credit_card: 'bg-purple-100 text-purple-700',
    other:       'bg-gray-100 text-gray-600',
  }[method] ?? 'bg-gray-100 text-gray-600';
}

function openModal(item = null) {
  editing.value = item;
  form.value = item
    ? {
        item_name:          item.item_name,
        total_cost:         item.total_cost,
        payment_method:     item.payment_method ?? 'cash',
        purchase_date:      item.purchase_date?.split('T')[0] ?? item.purchase_date ?? '',
        installment_count:  item.installment_count ?? '',
        installment_amount: item.installment_amount ?? '',
        installments_paid:  item.installments_paid ?? 0,
      }
    : defaultForm();
  formError.value = '';
  showModal.value = true;
}

function confirmDelete(item) {
  deleteTarget.value = item;
}

function confirmPayInstallment(item) {
  payInstallmentTarget.value = item;
}

async function handlePayInstallment() {
  paying.value = true;
  try {
    await store.payInstallment(payInstallmentTarget.value.id);
    store.fetchSummary();
    payInstallmentTarget.value = null;
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to record payment.');
  } finally {
    paying.value = false;
  }
}

async function handleSubmit() {
  saving.value    = true;
  formError.value = '';
  try {
    const payload = { ...form.value };
    // Use computed monthly if manual not entered
    if (payload.payment_method === 'credit_card' && !payload.installment_amount && computedMonthly.value) {
      payload.installment_amount = computedMonthly.value;
    }
    if (editing.value) {
      await store.update(editing.value.id, payload);
    } else {
      await store.create(payload);
    }
    store.fetchSummary();
    showModal.value = false;
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save. Please try again.';
  } finally {
    saving.value = false;
  }
}

async function handleDelete() {
  await store.remove(deleteTarget.value.id);
  store.fetchSummary();
  deleteTarget.value = null;
}

function loadData() {
  store.fetchAll({ ...filters.value });
}

function resetFilters() {
  filters.value = { payment_method: '', search: '' };
  loadData();
}

function changePage(page) {
  store.fetchAll({ ...filters.value, page });
}

onMounted(() => {
  loadData();
  store.fetchSummary();
});
</script>
