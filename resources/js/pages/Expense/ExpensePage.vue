<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Expenses</h1>
      <button @click="openModal()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm font-medium">+ Add Expense</button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap gap-3">
      <input v-model="filters.date_from" type="date" class="border rounded-lg px-3 py-2 text-sm" />
      <input v-model="filters.date_to" type="date" class="border rounded-lg px-3 py-2 text-sm" />
      <input v-model="filters.search" type="text" class="border rounded-lg px-3 py-2 text-sm min-w-[180px]" placeholder="Search..." />
      <button @click="loadData" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">Filter</button>
      <button @click="resetFilters" class="text-gray-400 text-sm px-2 py-2 hover:text-gray-600">Reset</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="text-left px-4 py-3 text-gray-500 font-medium">Title</th>
            <th class="text-left px-4 py-3 text-gray-500 font-medium">Category</th>
            <th class="text-right px-4 py-3 text-gray-500 font-medium">Amount</th>
            <th class="text-left px-4 py-3 text-gray-500 font-medium">Date</th>
            <th class="text-left px-4 py-3 text-gray-500 font-medium">Recurring</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="store.items.length === 0">
            <td colspan="6" class="text-center py-10 text-gray-400">No expense records found</td>
          </tr>
          <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-700">{{ item.title }}</td>
            <td class="px-4 py-3 text-gray-500">{{ item.category?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-right text-red-600 font-semibold">{{ formatCurrency(item.amount) }}</td>
            <td class="px-4 py-3 text-gray-500">{{ item.spent_at }}</td>
            <td class="px-4 py-3">
              <span v-if="item.is_recurring" class="bg-orange-100 text-orange-700 text-xs px-2 py-1 rounded-full">{{ item.recurrence_interval }}</span>
              <span v-else class="text-gray-400 text-xs">One-time</span>
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
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editing ? 'Edit Expense' : 'Add Expense' }}</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input v-model="form.title" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
            <input v-model="form.amount" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date Spent *</label>
            <input v-model="form.spent_at" type="date" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Merchant / Vendor</label>
            <input v-model="form.merchant" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="e.g. Grocery store" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" rows="2"></textarea>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.is_recurring" type="checkbox" id="expense-recurring" class="rounded" />
            <label for="expense-recurring" class="text-sm text-gray-700">Recurring expense</label>
          </div>
          <div v-if="form.is_recurring">
            <label class="block text-sm font-medium text-gray-700 mb-1">Interval</label>
            <select v-model="form.recurrence_interval" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
              <option value="yearly">Yearly</option>
            </select>
          </div>
          <div v-if="formError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-red-700">
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete Dialog -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Expense</h3>
        <p class="text-sm text-gray-500 mb-4">Delete "{{ deleteTarget.title }}"? This cannot be undone.</p>
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
import { useExpenseStore } from '@/stores/expense';

const store = useExpenseStore();
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const saving = ref(false);
const formError = ref('');
const filters = ref({ date_from: '', date_to: '', search: '' });

const defaultForm = () => ({
  title: '',
  amount: '',
  spent_at: new Date().toISOString().split('T')[0],
  merchant: '',
  description: '',
  is_recurring: false,
  recurrence_interval: 'monthly',
});

const form = ref(defaultForm());

function formatCurrency(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function openModal(item = null) {
  editing.value = item;
  form.value = item
    ? { ...item, spent_at: item.spent_at?.split('T')[0] ?? item.spent_at }
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

function loadData() {
  store.fetchAll({ ...filters.value });
}

function resetFilters() {
  filters.value = { date_from: '', date_to: '', search: '' };
  loadData();
}

function changePage(page) {
  store.fetchAll({ ...filters.value, page });
}

onMounted(() => loadData());
</script>
