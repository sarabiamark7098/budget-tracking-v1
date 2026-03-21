<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Budget</h1>
      <button @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">+ Add Budget</button>
    </div>

    <!-- Summary Cards -->
    <div v-if="store.summary" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Total Budget</p>
        <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(store.summary.total_budget) }}</p>
        <p class="text-xs text-gray-400 mt-1">Cumulative since start</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Total Spent</p>
        <p class="text-2xl font-bold text-red-600">{{ formatCurrency(store.summary.total_spent) }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Remaining</p>
        <p class="text-2xl font-bold" :class="store.summary.total_remaining >= 0 ? 'text-green-600' : 'text-red-600'">
          {{ formatCurrency(store.summary.total_remaining) }}
        </p>
        <p v-if="store.summary.total_remaining < 0" class="text-xs text-red-400 mt-1">Over budget</p>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="text-left px-4 py-3 text-gray-500 font-medium">Name</th>
            <th class="text-left px-4 py-3 text-gray-500 font-medium">Period</th>
            <th class="text-left px-4 py-3 text-gray-500 font-medium">Start Date</th>
            <th class="text-right px-4 py-3 text-gray-500 font-medium">Per Period</th>
            <th class="text-right px-4 py-3 text-gray-500 font-medium">Total Budget</th>
            <th class="text-right px-4 py-3 text-gray-500 font-medium">Spent</th>
            <th class="text-right px-4 py-3 text-gray-500 font-medium">Remaining</th>
            <th class="text-left px-4 py-3 text-gray-500 font-medium">Usage</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="store.items.length === 0">
            <td colspan="9" class="text-center py-10 text-gray-400">No budget records found</td>
          </tr>
          <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-700">{{ item.name }}</td>
            <td class="px-4 py-3 text-gray-500 capitalize">{{ item.period }}</td>
            <td class="px-4 py-3 text-gray-500">{{ formatDate(item.start_date) }}</td>
            <td class="px-4 py-3 text-right text-gray-600">{{ formatCurrency(item.amount) }}</td>
            <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ formatCurrency(item.total_budget) }}</td>
            <td class="px-4 py-3 text-right text-red-600">{{ formatCurrency(item.spent_amount) }}</td>
            <td class="px-4 py-3 text-right font-semibold" :class="item.remaining_amount >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ formatCurrency(item.remaining_amount) }}
              <span v-if="item.remaining_amount < 0" class="block text-xs font-normal text-red-400">Over budget</span>
            </td>
            <td class="px-4 py-3 min-w-[120px]">
              <div class="flex items-center gap-2">
                <div class="flex-1 bg-gray-100 rounded-full h-2">
                  <div
                    class="h-2 rounded-full transition-all"
                    :class="usagePct(item) >= 100 ? 'bg-red-500' : usagePct(item) >= 80 ? 'bg-yellow-500' : 'bg-green-500'"
                    :style="{ width: Math.min(100, usagePct(item)) + '%' }"
                  ></div>
                </div>
                <span class="text-xs text-gray-500 w-10 text-right">{{ usagePct(item).toFixed(0) }}%</span>
              </div>
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
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editing ? 'Edit Budget' : 'Add Budget' }}</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
            <input v-model="form.name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Groceries, Entertainment" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
            <input v-model="form.amount" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Amount per period" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Period *</label>
            <select v-model="form.period" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
              <option value="yearly">Yearly</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
            <input v-model="form.start_date" type="date" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <p class="text-xs text-gray-400 mt-1">Total budget = amount × number of {{ form.period ?? 'period' }}s elapsed</p>
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
        <h3 class="font-semibold text-gray-800 mb-2">Delete Budget</h3>
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
import { useBudgetStore } from '@/stores/budget';

const store = useBudgetStore();
const showModal   = ref(false);
const editing     = ref(null);
const deleteTarget = ref(null);
const saving      = ref(false);
const formError   = ref('');

const defaultForm = () => ({
  name:       '',
  amount:     '',
  period:     'monthly',
  start_date: new Date().toISOString().split('T')[0],
});

const form = ref(defaultForm());

function formatCurrency(val) {
  const n = Number(val || 0);
  const formatted = Math.abs(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });
  return (n < 0 ? '-₱' : '₱') + formatted;
}

function formatDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function usagePct(item) {
  const total = Number(item.total_budget ?? item.amount ?? 0);
  if (total === 0) return 0;
  return (Number(item.spent_amount ?? 0) / total) * 100;
}

function openModal(item = null) {
  editing.value = item;
  form.value = item
    ? {
        name:       item.name,
        amount:     item.amount,
        period:     item.period,
        start_date: item.start_date?.split('T')[0] ?? item.start_date,
      }
    : defaultForm();
  formError.value = '';
  showModal.value = true;
}

function confirmDelete(item) {
  deleteTarget.value = item;
}

async function handleSubmit() {
  saving.value    = true;
  formError.value = '';
  try {
    if (editing.value) {
      await store.update(editing.value.id, form.value);
    } else {
      await store.create(form.value);
    }
    showModal.value = false;
    store.fetchSummary();
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save. Please try again.';
  } finally {
    saving.value = false;
  }
}

async function handleDelete() {
  await store.remove(deleteTarget.value.id);
  deleteTarget.value = null;
  store.fetchSummary();
}

function changePage(page) {
  store.fetchAll({ page });
}

onMounted(async () => {
  await store.fetchAll();
  store.fetchSummary();
});
</script>
