<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Insurance</h1>
      <button @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">+ Add Plan</button>
    </div>

    <!-- Insurance Plans Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h2 class="text-sm font-semibold text-gray-700">Insurance Plans</h2>
        <span class="text-xs text-gray-400">{{ store.pagination?.total ?? store.items.length }} plans</span>
      </div>
      <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Provider / Plan</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Type</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium hidden sm:table-cell">Policy No.</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Premium</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Frequency</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Total Paid</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="7" class="text-center py-10 text-gray-400">No insurance plans found. Add your first plan to get started.</td>
            </tr>
            <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="font-medium text-gray-800">{{ item.provider_name }}</div>
                <div class="text-xs text-gray-400">{{ item.plan_name }}</div>
              </td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span v-for="t in (item.coverage_type ?? [])" :key="t" class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full capitalize">{{ t }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-400 font-mono text-xs hidden sm:table-cell">{{ item.policy_number ?? '—' }}</td>
              <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ fmt(item.premium_amount) }}</td>
              <td class="px-4 py-3 text-gray-500 capitalize">{{ item.payment_frequency?.replace('_', ' ') }}</td>
              <td class="px-4 py-3 text-right font-semibold text-green-600">{{ fmt(item.total_paid ?? 0) }}</td>
              <td class="px-4 py-3">
                <div class="flex gap-2 justify-end">
                  <button @click="openPayModal(item)" class="text-green-600 hover:text-green-800 text-xs px-2 py-1 border border-green-300 rounded">Pay</button>
                  <button @click="openHistoryModal(item)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">History</button>
                  <button @click="openModal(item)" class="text-gray-500 hover:text-gray-700 text-xs px-2 py-1 border rounded">Edit</button>
                  <button @click="confirmDelete(item)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="store.pagination && store.pagination.last_page > 1" class="flex justify-between items-center px-5 py-3 border-t text-sm text-gray-500">
        <span>Page {{ store.pagination.current_page }} of {{ store.pagination.last_page }}</span>
        <div class="flex gap-2">
          <button :disabled="store.pagination.current_page <= 1" @click="changePage(store.pagination.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100">Prev</button>
          <button :disabled="store.pagination.current_page >= store.pagination.last_page" @click="changePage(store.pagination.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100">Next</button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Plan Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editing ? 'Edit Insurance Plan' : 'Add Insurance Plan' }}</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Provider *</label>
            <input v-model="form.provider_name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Sun Life, AXA, Pru Life" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Plan Name *</label>
            <input v-model="form.plan_name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Sun Life Brilliance" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type * <span class="text-xs text-gray-400">(select all that apply)</span></label>
            <div class="grid grid-cols-3 gap-2">
              <label v-for="t in COVERAGE_TYPES" :key="t.value" class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" :value="t.value" v-model="form.coverage_type" class="rounded border-gray-300 text-blue-600" />
                <span class="text-sm text-gray-700">{{ t.label }}</span>
              </label>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Policy Number</label>
            <input v-model="form.policy_number" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Premium Amount *</label>
              <input v-model="form.premium_amount" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Coverage Amount</label>
              <input v-model="form.coverage_amount" type="number" min="0" step="0.01" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Frequency *</label>
            <select v-model="form.payment_frequency" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="monthly">Monthly</option>
              <option value="quarterly">Quarterly</option>
              <option value="semi_annually">Semi-Annually</option>
              <option value="annually">Annually</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" rows="2"></textarea>
          </div>
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

    <!-- Pay Modal -->
    <div v-if="showPayModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b">
          <div>
            <h2 class="font-semibold text-gray-800">Record Payment</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ payTarget?.provider_name }} — {{ payTarget?.plan_name }}</p>
          </div>
          <button @click="showPayModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handlePay" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
            <input v-model="payForm.amount" type="number" min="0.01" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
              :placeholder="`e.g. ${fmt(payTarget?.premium_amount ?? 0)}`" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
            <textarea v-model="payForm.note" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" rows="2"></textarea>
          </div>
          <div v-if="payError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ payError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showPayModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-green-700">
              {{ saving ? 'Saving...' : 'Record Payment' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- History Modal -->
    <div v-if="showHistoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b sticky top-0 bg-white">
          <div>
            <h2 class="font-semibold text-gray-800">Payment History</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ historyTarget?.provider_name }} — {{ historyTarget?.plan_name }}</p>
          </div>
          <button @click="showHistoryModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5">
          <div v-if="historyLoading" class="text-center py-8 text-gray-400">Loading...</div>
          <div v-else-if="!historyData.data?.length" class="text-center py-8 text-gray-400">No payments recorded yet.</div>
          <div v-else>
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="text-left px-3 py-2 text-gray-500 font-medium">Date</th>
                  <th class="text-right px-3 py-2 text-gray-500 font-medium">Amount</th>
                  <th class="text-left px-3 py-2 text-gray-500 font-medium">Note</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pay in historyData.data" :key="pay.id" class="border-t hover:bg-gray-50">
                  <td class="px-3 py-2 text-gray-600">{{ formatDate(pay.payment_date) }}</td>
                  <td class="px-3 py-2 text-right font-semibold text-green-600">{{ fmt(pay.amount) }}</td>
                  <td class="px-3 py-2 text-gray-400 text-xs">{{ pay.note ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
            <!-- Pagination -->
            <div v-if="historyData.meta && historyData.meta.last_page > 1" class="flex justify-between items-center mt-4 text-sm text-gray-500">
              <span>Page {{ historyData.meta.current_page }} of {{ historyData.meta.last_page }}</span>
              <div class="flex gap-2">
                <button :disabled="historyData.meta.current_page <= 1" @click="loadHistoryPage(historyData.meta.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100">Prev</button>
                <button :disabled="historyData.meta.current_page >= historyData.meta.last_page" @click="loadHistoryPage(historyData.meta.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100">Next</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Delete -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Insurance Plan</h3>
        <p class="text-sm text-gray-500 mb-4">Delete <strong>{{ deleteTarget.provider_name }}</strong> — {{ deleteTarget.plan_name }}? This cannot be undone.</p>
        <div class="flex justify-end gap-3">
          <button @click="deleteTarget = null" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handleDelete" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useInsuranceStore } from '@/stores/insurance';

const store = useInsuranceStore();

const COVERAGE_TYPES = [
  { value: 'life',     label: 'Life' },
  { value: 'health',   label: 'Health' },
  { value: 'vehicle',  label: 'Vehicle' },
  { value: 'property', label: 'Property' },
  { value: 'travel',   label: 'Travel' },
  { value: 'other',    label: 'Other' },
];

// --- Add/Edit Modal ---
const showModal   = ref(false);
const editing     = ref(null);
const saving      = ref(false);
const formError   = ref('');

const defaultForm = () => ({
  provider_name:     '',
  plan_name:         '',
  coverage_type:     [],
  coverage_amount:   '',
  premium_amount:    '',
  payment_frequency: 'monthly',
  policy_number:     '',
  notes:             '',
});

const form = ref(defaultForm());

// --- Pay Modal ---
const showPayModal = ref(false);
const payTarget    = ref(null);
const payForm      = ref({ amount: '', note: '' });
const payError     = ref('');

// --- History Modal ---
const showHistoryModal = ref(false);
const historyTarget    = ref(null);
const historyLoading   = ref(false);
const historyData      = ref({ data: [], meta: null });

// --- Delete ---
const deleteTarget = ref(null);

function fmt(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function formatDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function openModal(item = null) {
  editing.value = item;
  form.value = item
    ? { ...item, coverage_type: Array.isArray(item.coverage_type) ? [...item.coverage_type] : [] }
    : defaultForm();
  formError.value = '';
  showModal.value = true;
}

async function handleSubmit() {
  if (!form.value.coverage_type?.length) {
    formError.value = 'Please select at least one coverage type.';
    return;
  }
  saving.value = true;
  formError.value = '';
  try {
    if (editing.value) {
      await store.update(editing.value.id, form.value);
    } else {
      await store.create(form.value);
    }
    showModal.value = false;
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save. Please try again.';
  } finally {
    saving.value = false;
  }
}

function openPayModal(item) {
  payTarget.value = item;
  payForm.value = { amount: item.premium_amount ?? '', note: '' };
  payError.value = '';
  showPayModal.value = true;
}

async function handlePay() {
  saving.value = true;
  payError.value = '';
  try {
    await store.pay(payTarget.value.id, payForm.value);
    showPayModal.value = false;
  } catch (e) {
    payError.value = e.response?.data?.message ?? 'Failed to record payment.';
  } finally {
    saving.value = false;
  }
}

async function openHistoryModal(item) {
  historyTarget.value = item;
  historyData.value = { data: [], meta: null };
  showHistoryModal.value = true;
  await loadHistoryPage(1);
}

async function loadHistoryPage(page) {
  historyLoading.value = true;
  try {
    historyData.value = await store.fetchPlanPayments(historyTarget.value.id, { page });
  } finally {
    historyLoading.value = false;
  }
}

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

onMounted(() => {
  store.fetchAll();
});
</script>
