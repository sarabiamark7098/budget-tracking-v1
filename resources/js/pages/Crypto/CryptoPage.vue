<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Crypto</h1>
      <button @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">+ Add Crypto</button>
    </div>

    <!-- Portfolio Summary -->
    <div v-if="store.portfolio" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Total Invested</p>
        <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(store.portfolio.total_invested) }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Portfolio Value</p>
        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(store.portfolio.total_value) }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Total P&amp;L</p>
        <p class="text-2xl font-bold" :class="store.portfolio.total_pl >= 0 ? 'text-green-600' : 'text-red-600'">
          {{ store.portfolio.total_pl >= 0 ? '+' : '' }}{{ formatCurrency(store.portfolio.total_pl) }}
        </p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-sm text-gray-500 mb-1">Overall Return</p>
        <p class="text-2xl font-bold" :class="store.portfolio.return_pct >= 0 ? 'text-green-600' : 'text-red-600'">
          {{ store.portfolio.return_pct >= 0 ? '+' : '' }}{{ Number(store.portfolio.return_pct || 0).toFixed(2) }}%
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
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Coin</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Symbol</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Quantity</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Buy Price</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Current Price</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Value</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">P&amp;L</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="8" class="text-center py-10 text-gray-400">No crypto holdings found</td>
            </tr>
            <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-700">{{ item.coin_name }}</td>
              <td class="px-4 py-3 font-bold text-yellow-600 uppercase">{{ item.symbol }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ Number(item.quantity).toLocaleString('en-PH', { minimumFractionDigits: 4 }) }}</td>
              <td class="px-4 py-3 text-right text-gray-500">{{ formatCurrency(item.buy_price) }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ formatCurrency(item.current_price) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ formatCurrency(cryptoValue(item)) }}</td>
              <td class="px-4 py-3 text-right font-semibold" :class="cryptoPL(item) >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ cryptoPL(item) >= 0 ? '+' : '' }}{{ formatCurrency(cryptoPL(item)) }}
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
          <h2 class="font-semibold text-gray-800">{{ editing ? 'Edit Crypto' : 'Add Crypto' }}</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Coin Name *</label>
            <input v-model="form.coin_name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Bitcoin, Ethereum" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Symbol *</label>
            <input v-model="form.symbol" required class="w-full border rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. BTC, ETH" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
            <input v-model="form.quantity" type="number" min="0" step="0.00000001" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Buy Price (per coin in ₱) *</label>
            <input v-model="form.buy_price" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Current Price (per coin in ₱)</label>
            <input v-model="form.current_price" type="number" min="0" step="0.01" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
            <input v-model="form.purchase_date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
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
        <h3 class="font-semibold text-gray-800 mb-2">Delete Crypto</h3>
        <p class="text-sm text-gray-500 mb-4">Delete {{ deleteTarget.coin_name }} ({{ deleteTarget.symbol }}) holding? This cannot be undone.</p>
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
import { useCryptoStore } from '@/stores/crypto';

const store = useCryptoStore();
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const saving = ref(false);
const formError = ref('');

const defaultForm = () => ({
  coin_name: '',
  symbol: '',
  quantity: '',
  buy_price: '',
  current_price: '',
  purchase_date: new Date().toISOString().split('T')[0],
  notes: '',
});

const form = ref(defaultForm());

function formatCurrency(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function cryptoValue(item) {
  return Number(item.quantity || 0) * Number(item.current_price || 0);
}

function cryptoPL(item) {
  return (Number(item.current_price || 0) - Number(item.buy_price || 0)) * Number(item.quantity || 0);
}

function openModal(item = null) {
  editing.value = item;
  form.value = item
    ? { ...item, purchase_date: item.purchase_date?.split('T')[0] ?? item.purchase_date ?? '' }
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
