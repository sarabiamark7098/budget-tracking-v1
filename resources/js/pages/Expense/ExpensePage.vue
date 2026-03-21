<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Expenses</h1>
      <button @click="openModal()" class="bg-red-600 text-white px-3 py-2 sm:px-4 rounded-lg hover:bg-red-700 text-sm font-medium">+ Add</button>
    </div>

    <!-- No budgets warning -->
    <div v-if="!budgetStore.loading && budgets.length === 0" class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
      <span class="text-amber-500 text-lg">⚠</span>
      <div>
        <p class="text-sm font-medium text-amber-800">No budgets found</p>
        <p class="text-sm text-amber-700 mt-0.5">
          Create a budget first before adding expenses.
          <RouterLink to="/budget" class="underline font-medium hover:text-amber-900">Create a budget →</RouterLink>
        </p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 space-y-2 sm:space-y-0 sm:flex sm:flex-wrap sm:gap-3">
      <div class="grid grid-cols-2 gap-2 sm:contents">
        <input v-model="filters.date_from" type="date" class="border rounded-lg px-3 py-2 text-sm w-full sm:w-auto" />
        <input v-model="filters.date_to"   type="date" class="border rounded-lg px-3 py-2 text-sm w-full sm:w-auto" />
      </div>
      <select v-model="filters.budget_id" class="border rounded-lg px-3 py-2 text-sm bg-white w-full sm:w-auto">
        <option value="">All Budgets</option>
        <option v-for="b in budgets" :key="b.id" :value="b.id">{{ b.name }}</option>
      </select>
      <input v-model="filters.search" type="text" class="border rounded-lg px-3 py-2 text-sm w-full sm:min-w-[180px] sm:w-auto" placeholder="Search title..." />
      <div class="flex gap-2 sm:contents">
        <button @click="loadData" class="flex-1 sm:flex-none bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">Filter</button>
        <button @click="resetFilters" class="text-gray-400 text-sm px-3 py-2 hover:text-gray-600">Reset</button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>

    <template v-else>
      <!-- Mobile card list -->
      <div class="sm:hidden space-y-2">
        <div v-if="store.items.length === 0" class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-400">No expense records found</div>
        <div v-for="item in store.items" :key="item.id" class="bg-white rounded-xl shadow-sm p-4">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="font-medium text-gray-800 truncate">{{ item.title }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(item.spent_at) }}</p>
              <span v-if="item.budget" class="inline-block mt-1.5 text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">
                {{ item.budget.name }}
              </span>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="text-base font-bold text-red-600">{{ formatCurrency(item.amount) }}</p>
              <div class="flex gap-2 mt-2 justify-end">
                <button @click="openModal(item)" class="text-blue-500 text-xs px-2.5 py-1 border rounded-lg">Edit</button>
                <button @click="confirmDelete(item)" class="text-red-500 text-xs px-2.5 py-1 border rounded-lg">Delete</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Desktop table -->
      <div class="hidden sm:block bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[500px]">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Title</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Budget</th>
                <th class="text-right px-4 py-3 text-gray-500 font-medium">Amount</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Date</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="store.items.length === 0">
                <td colspan="5" class="text-center py-10 text-gray-400">No expense records found</td>
              </tr>
              <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-700">{{ item.title }}</td>
                <td class="px-4 py-3">
                  <span v-if="item.budget" class="text-xs font-medium px-2 py-1 rounded-full bg-indigo-100 text-indigo-700">{{ item.budget.name }}</span>
                  <span v-else class="text-gray-400 text-xs">—</span>
                </td>
                <td class="px-4 py-3 text-right text-red-600 font-semibold">{{ formatCurrency(item.amount) }}</td>
                <td class="px-4 py-3 text-gray-500">{{ formatDate(item.spent_at) }}</td>
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
    </template>

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
          <h2 class="font-semibold text-gray-800">{{ editing ? 'Edit Expense' : 'Add Expense' }}</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">

          <!-- Budget selector -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Budget *</label>
            <select
              v-model="form.budget_id"
              required
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 bg-white"
            >
              <option value="">— Select budget —</option>
              <option v-for="b in budgets" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
            <!-- Live budget balance hint -->
            <div v-if="selectedBudget" class="mt-2 bg-gray-50 rounded-lg px-3 py-2 text-xs space-y-1">
              <div class="flex justify-between text-gray-500">
                <span>Per period</span>
                <span class="font-medium text-gray-700">{{ formatCurrency(selectedBudget.amount) }}</span>
              </div>
              <div class="flex justify-between text-gray-500">
                <span>Total budget (cumulative)</span>
                <span class="font-medium text-blue-600">{{ formatCurrency(selectedBudget.total_budget) }}</span>
              </div>
              <div class="flex justify-between text-gray-500">
                <span>Spent</span>
                <span class="font-medium text-red-600">{{ formatCurrency(selectedBudget.spent_amount) }}</span>
              </div>
              <div class="flex justify-between text-gray-500 border-t pt-1">
                <span>Remaining</span>
                <span class="font-semibold" :class="selectedBudget.remaining_amount >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ formatCurrency(selectedBudget.remaining_amount) }}
                  <span v-if="selectedBudget.remaining_amount < 0">(Over)</span>
                </span>
              </div>
            </div>
            <p v-if="budgets.length === 0" class="mt-1 text-xs text-red-500">No budgets available. Please create a budget first.</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input v-model="form.title" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="e.g. Grocery run" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
            <input v-model="form.amount" type="number" min="0.01" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500" />
            <div v-if="availableBalance !== null" class="mt-1 flex justify-between text-xs">
              <span class="text-gray-400">Available income balance</span>
              <span :class="availableBalance >= 0 ? 'text-green-600 font-medium' : 'text-red-600 font-medium'">{{ formatCurrency(availableBalance) }}</span>
            </div>
            <p v-if="form.amount && availableBalance !== null && Number(form.amount) > availableBalance" class="text-xs text-red-500 mt-1">
              Amount exceeds available income balance ({{ formatCurrency(availableBalance) }}).
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date Spent *</label>
            <input
              v-model="form.spent_at"
              type="date"
              required
              :min="selectedBudget?.start_date?.split('T')[0] ?? selectedBudget?.start_date ?? undefined"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
            />
            <p v-if="selectedBudget?.start_date && form.spent_at && form.spent_at < (selectedBudget.start_date?.split('T')[0] ?? selectedBudget.start_date)" class="text-xs text-red-500 mt-1">
              Date cannot be before the budget start date ({{ formatDate(selectedBudget.start_date) }})
            </p>
            <p v-else-if="selectedBudget?.start_date" class="text-xs text-gray-400 mt-1">
              Earliest date: {{ formatDate(selectedBudget.start_date) }}
            </p>
          </div>

          <div v-if="formError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button
              type="submit"
              :disabled="saving || budgets.length === 0"
              class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-red-700"
            >{{ saving ? 'Saving...' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete -->
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
import { ref, computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useExpenseStore } from '@/stores/expense';
import { useBudgetStore } from '@/stores/budget';
import { useBudgetTrackingStore } from '@/stores/budgetTracking';
import { formatDate } from '@/utils/date';

const store        = useExpenseStore();
const budgetStore  = useBudgetStore();
const btStore      = useBudgetTrackingStore();

const showModal    = ref(false);
const editing      = ref(null);
const deleteTarget = ref(null);
const saving       = ref(false);
const formError    = ref('');
const filters      = ref({ date_from: '', date_to: '', budget_id: '', search: '' });

const budgets = computed(() => budgetStore.items ?? []);

const selectedBudget = computed(() =>
  budgets.value.find(b => b.id == form.value.budget_id) ?? null
);

const availableBalance = computed(() =>
  btStore.tracker != null ? (btStore.tracker.available_balance ?? null) : null
);


const defaultForm = () => ({
  budget_id: '',
  title:     '',
  amount:    '',
  spent_at:  new Date().toISOString().split('T')[0],
});

const form = ref(defaultForm());

function formatCurrency(val) {
  const n = Number(val || 0);
  const formatted = Math.abs(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });
  return (n < 0 ? '-₱' : '₱') + formatted;
}

function openModal(item = null) {
  editing.value = item;
  form.value = item
    ? {
        budget_id: item.budget_id,
        title:     item.title,
        amount:    item.amount,
        spent_at:  item.spent_at?.split('T')[0] ?? item.spent_at,
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
    // Refresh budget list and tracker balance so hints stay current
    budgetStore.fetchAll({ per_page: 100 });
    btStore.fetchTracker();
    showModal.value = false;
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save. Please try again.';
  } finally {
    saving.value = false;
  }
}

async function handleDelete() {
  await store.remove(deleteTarget.value.id);
  budgetStore.fetchAll({ per_page: 100 });
  btStore.fetchTracker();
  deleteTarget.value = null;
}

function loadData() {
  store.fetchAll({ ...filters.value });
}

function resetFilters() {
  filters.value = { date_from: '', date_to: '', budget_id: '', search: '' };
  loadData();
}

function changePage(page) {
  store.fetchAll({ ...filters.value, page });
}

onMounted(() => {
  loadData();
  budgetStore.fetchAll({ per_page: 100 });
  btStore.fetchTracker();
});
</script>
