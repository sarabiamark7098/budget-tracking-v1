<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Investments</h1>
      <button @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">+ Add Investment</button>
    </div>

    <!-- Portfolio Summary -->
    <div v-if="store.portfolio" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Total Invested</p>
        <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(store.portfolio.total_invested) }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Current Value</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(store.portfolio.total_current_value) }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Total Gain/Loss</p>
        <p class="text-2xl font-bold" :class="store.portfolio.total_gain_loss >= 0 ? 'text-green-600' : 'text-red-600'">
          {{ store.portfolio.total_gain_loss >= 0 ? '+' : '' }}{{ formatCurrency(store.portfolio.total_gain_loss) }}
        </p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Overall ROI</p>
        <p class="text-2xl font-bold" :class="store.portfolio.roi >= 0 ? 'text-green-600' : 'text-red-600'">
          {{ store.portfolio.roi >= 0 ? '+' : '' }}{{ Number(store.portfolio.roi || 0).toFixed(2) }}%
        </p>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
      <div class="overflow-x-auto">
        <table v-if="!store.loading" class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Name</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Type</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Invested</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Current Value</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Gain/Loss</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">ROI %</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="7" class="text-center py-10 text-gray-400">No investment records found</td>
            </tr>
            <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-700">{{ item.name }}</td>
              <td class="px-4 py-3">
                <span class="text-xs px-2 py-1 rounded-full bg-indigo-100 text-indigo-700 capitalize">{{ item.type }}</span>
              </td>
              <td class="px-4 py-3 text-right text-blue-600 font-semibold">{{ formatCurrency(item.amount_invested) }}</td>
              <td class="px-4 py-3 text-right text-gray-700 font-semibold">{{ formatCurrency(item.current_value) }}</td>
              <td class="px-4 py-3 text-right font-semibold" :class="gainLoss(item) >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ gainLoss(item) >= 0 ? '+' : '' }}{{ formatCurrency(gainLoss(item)) }}
              </td>
              <td class="px-4 py-3 text-right font-semibold" :class="roi(item) >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ roi(item) >= 0 ? '+' : '' }}{{ roi(item).toFixed(2) }}%
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
          <h2 class="font-semibold text-gray-800">{{ editing ? 'Edit Investment' : 'Add Investment' }}</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
            <input v-model="form.name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. ACMF Equity Fund" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
            <select v-model="form.type" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="mutual_fund">Mutual Fund</option>
              <option value="uitf">UITF</option>
              <option value="bonds">Bonds</option>
              <option value="real_estate">Real Estate</option>
              <option value="business">Business</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount Invested *</label>
            <input v-model="form.amount_invested" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Current Value</label>
            <input v-model="form.current_value" type="number" min="0" step="0.01" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input v-model="form.start_date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
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

    <!-- Confirm Delete Dialog -->
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
import { ref, onMounted } from 'vue';
import { useInvestmentStore } from '@/stores/investment';

const store = useInvestmentStore();
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const saving = ref(false);
const formError = ref('');

const defaultForm = () => ({
  name: '',
  type: 'mutual_fund',
  amount_invested: '',
  current_value: '',
  start_date: new Date().toISOString().split('T')[0],
  notes: '',
});

const form = ref(defaultForm());

function formatCurrency(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function gainLoss(item) {
  return Number(item.current_value || 0) - Number(item.amount_invested || 0);
}

function roi(item) {
  if (!item.amount_invested || item.amount_invested === 0) return 0;
  return (gainLoss(item) / item.amount_invested) * 100;
}

function openModal(item = null) {
  editing.value = item;
  form.value = item
    ? { ...item, start_date: item.start_date?.split('T')[0] ?? item.start_date ?? '' }
    : defaultForm();
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
