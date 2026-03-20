<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Insurance</h1>
      <div class="flex gap-2">
        <button @click="openPaymentModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm font-medium">+ Record Payment</button>
        <button @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">+ Add Plan</button>
      </div>
    </div>

    <!-- Insurance Plans Table -->
    <div>
      <h2 class="text-lg font-semibold text-gray-700 mb-3">Insurance Plans</h2>
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
        <div class="overflow-x-auto">
          <table v-if="!store.loading" class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Provider</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Type</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Policy No.</th>
                <th class="text-right px-4 py-3 text-gray-500 font-medium">Premium</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Frequency</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Next Due</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Status</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="store.items.length === 0">
                <td colspan="8" class="text-center py-10 text-gray-400">No insurance plans found</td>
              </tr>
              <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-700">{{ item.provider }}</td>
                <td class="px-4 py-3 text-gray-500 capitalize">{{ item.type }}</td>
                <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ item.policy_number ?? '—' }}</td>
                <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ formatCurrency(item.premium_amount) }}</td>
                <td class="px-4 py-3 text-gray-500 capitalize">{{ item.payment_frequency }}</td>
                <td class="px-4 py-3 text-gray-500">{{ formatDate(item.next_due_date) }}</td>
                <td class="px-4 py-3">
                  <span class="text-xs px-2 py-1 rounded-full capitalize" :class="statusClass(item.status)">{{ item.status ?? 'active' }}</span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex gap-2 justify-end">
                    <button @click="openModal(item)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">Edit</button>
                    <button @click="confirmDelete(item)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Insurance Payments Table -->
    <div>
      <h2 class="text-lg font-semibold text-gray-700 mb-3">Payment History</h2>
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Insurance Plan</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Amount Paid</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Date</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Note</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.payments.length === 0">
              <td colspan="4" class="text-center py-8 text-gray-400">No payment history</td>
            </tr>
            <tr v-for="pay in store.payments" :key="pay.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-700">{{ pay.insurance?.provider ?? '—' }}</td>
              <td class="px-4 py-3 text-right text-green-600 font-semibold">{{ formatCurrency(pay.amount) }}</td>
              <td class="px-4 py-3 text-gray-500">{{ formatDate(pay.payment_date) }}</td>
              <td class="px-4 py-3 text-gray-400 text-xs">{{ pay.note ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
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
            <input v-model="form.provider" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Sun Life, AXA, Pru Life" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
            <select v-model="form.type" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="life">Life</option>
              <option value="health">Health</option>
              <option value="vehicle">Vehicle</option>
              <option value="property">Property</option>
              <option value="travel">Travel</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Policy Number</label>
            <input v-model="form.policy_number" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Premium Amount *</label>
            <input v-model="form.premium_amount" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Frequency *</label>
            <select v-model="form.payment_frequency" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="monthly">Monthly</option>
              <option value="quarterly">Quarterly</option>
              <option value="semi-annual">Semi-Annual</option>
              <option value="annual">Annual</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input v-model="form.start_date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Next Due Date</label>
            <input v-model="form.next_due_date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select v-model="form.status" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="active">Active</option>
              <option value="lapsed">Lapsed</option>
              <option value="cancelled">Cancelled</option>
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

    <!-- Record Payment Modal -->
    <div v-if="showPaymentModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">Record Payment</h2>
          <button @click="showPaymentModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handlePaymentSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Insurance Plan *</label>
            <select v-model="paymentForm.insurance_id" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
              <option value="">Select plan...</option>
              <option v-for="plan in store.items" :key="plan.id" :value="plan.id">{{ plan.provider }} ({{ plan.type }})</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
            <input v-model="paymentForm.amount" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date *</label>
            <input v-model="paymentForm.payment_date" type="date" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
            <textarea v-model="paymentForm.note" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" rows="2"></textarea>
          </div>
          <div v-if="formError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showPaymentModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-green-700">
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete Dialog -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Insurance Plan</h3>
        <p class="text-sm text-gray-500 mb-4">Delete plan from "{{ deleteTarget.provider }}"? This cannot be undone.</p>
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
import { formatDate } from '@/utils/date';

const store = useInsuranceStore();
const showModal = ref(false);
const showPaymentModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const saving = ref(false);
const formError = ref('');

const defaultForm = () => ({
  provider: '',
  type: 'life',
  policy_number: '',
  premium_amount: '',
  payment_frequency: 'monthly',
  start_date: '',
  next_due_date: '',
  status: 'active',
  notes: '',
});

const defaultPaymentForm = () => ({
  insurance_id: '',
  amount: '',
  payment_date: new Date().toISOString().split('T')[0],
  note: '',
});

const form = ref(defaultForm());
const paymentForm = ref(defaultPaymentForm());

function formatCurrency(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function statusClass(status) {
  return {
    active: 'bg-green-100 text-green-700',
    lapsed: 'bg-yellow-100 text-yellow-700',
    cancelled: 'bg-red-100 text-red-700',
  }[status] ?? 'bg-gray-100 text-gray-700';
}

function openModal(item = null) {
  editing.value = item;
  form.value = item
    ? {
        ...item,
        start_date: item.start_date?.split('T')[0] ?? item.start_date ?? '',
        next_due_date: item.next_due_date?.split('T')[0] ?? item.next_due_date ?? '',
      }
    : defaultForm();
  formError.value = '';
  showModal.value = true;
}

function openPaymentModal() {
  paymentForm.value = defaultPaymentForm();
  formError.value = '';
  showPaymentModal.value = true;
}

function confirmDelete(item) {
  deleteTarget.value = item;
}

async function handleSubmit() {
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

async function handlePaymentSubmit() {
  saving.value = true;
  formError.value = '';
  try {
    await store.recordPayment(paymentForm.value);
    showPaymentModal.value = false;
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save. Please try again.';
  } finally {
    saving.value = false;
  }
}

async function handleDelete() {
  await store.remove(deleteTarget.value.id);
  deleteTarget.value = null;
}

onMounted(async () => {
  store.fetchAll();
  store.fetchPayments();
});
</script>
