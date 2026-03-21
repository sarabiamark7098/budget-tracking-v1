<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Debt Payments</h1>
      <button @click="openModal()" class="bg-blue-600 text-white px-3 py-2 sm:px-4 rounded-lg hover:bg-blue-700 text-sm font-medium">+ Record</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
      <template v-else>
        <!-- Mobile card list -->
        <div class="sm:hidden divide-y divide-gray-100">
          <div v-if="store.items.length === 0" class="text-center py-10 text-gray-400 text-sm">No payment records found</div>
          <div v-for="item in store.items" :key="item.id" class="px-4 py-3 flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
              <p class="font-medium text-gray-700 text-sm truncate">{{ item.debt?.lender_name ?? '—' }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(item.payment_date) }}{{ item.note ? ' · ' + item.note : '' }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <span class="text-sm font-semibold text-green-600">{{ formatCurrency(item.amount) }}</span>
              <button @click="confirmDelete(item)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Del</button>
            </div>
          </div>
        </div>
        <!-- Desktop table -->
        <div class="hidden sm:block overflow-x-auto">
          <table class="w-full text-sm min-w-[500px]">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Debt / Lender</th>
                <th class="text-right px-4 py-3 text-gray-500 font-medium">Amount Paid</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Payment Date</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Note</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="store.items.length === 0">
                <td colspan="5" class="text-center py-10 text-gray-400">No payment records found</td>
              </tr>
              <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-700">{{ item.debt?.lender_name ?? '—' }}</td>
                <td class="px-4 py-3 text-right text-green-600 font-semibold">{{ formatCurrency(item.amount) }}</td>
                <td class="px-4 py-3 text-gray-500">{{ formatDate(item.payment_date) }}</td>
                <td class="px-4 py-3 text-gray-400 text-xs">{{ item.note ?? '—' }}</td>
                <td class="px-4 py-3">
                  <button @click="confirmDelete(item)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
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

    <!-- Add Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">Record Payment</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Debt *</label>
            <select v-model="form.debt_id" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">Select a debt...</option>
              <option v-for="debt in debtStore.items" :key="debt.id" :value="debt.id">
                {{ debt.lender_name }} — {{ formatCurrency(debt.remaining_balance) }} remaining
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
            <input v-model="form.amount" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date *</label>
            <input v-model="form.payment_date" type="date" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
            <textarea v-model="form.note" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" rows="2" placeholder="Optional note..."></textarea>
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

    <!-- Confirm Delete Dialog -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Payment</h3>
        <p class="text-sm text-gray-500 mb-4">Delete this payment of {{ formatCurrency(deleteTarget.amount) }}? This cannot be undone.</p>
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
import { usePaymentStore } from '@/stores/payment';
import { useDebtStore } from '@/stores/debt';
import { formatDate } from '@/utils/date';

const store = usePaymentStore();
const debtStore = useDebtStore();
const showModal = ref(false);
const deleteTarget = ref(null);
const saving = ref(false);
const formError = ref('');

const defaultForm = () => ({
  debt_id: '',
  amount: '',
  payment_date: new Date().toISOString().split('T')[0],
  note: '',
});

const form = ref(defaultForm());

function formatCurrency(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function openModal() {
  form.value = defaultForm();
  formError.value = '';
  showModal.value = true;
}

function confirmDelete(item) {
  deleteTarget.value = item;
}

async function handleSubmit() {
  saving.value = true;
  formError.value = '';
  try {
    await store.create(form.value);
    showModal.value = false;
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

function changePage(page) {
  store.fetchAll({ page });
}

onMounted(async () => {
  store.fetchAll();
  debtStore.fetchAll();
});
</script>
