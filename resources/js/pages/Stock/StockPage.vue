<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Stocks</h1>
      <button @click="openModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium">
        + Add Stock
      </button>
    </div>

    <!-- Portfolio Summary Cards -->
    <div v-if="portfolio" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-xs text-gray-500 mb-1">Cost Basis</p>
        <p class="text-xl font-bold text-gray-800">{{ fmt(portfolio.total_cost_basis) }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-xs text-gray-500 mb-1">Portfolio Value</p>
        <p class="text-xl font-bold text-blue-600">{{ fmt(portfolio.total_current_value) }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-xs text-gray-500 mb-1">Unrealized P&amp;L</p>
        <p class="text-xl font-bold" :class="portfolio.total_profit_loss >= 0 ? 'text-green-600' : 'text-red-600'">
          {{ portfolio.total_profit_loss >= 0 ? '+' : '' }}{{ fmt(portfolio.total_profit_loss) }}
        </p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-xs text-gray-500 mb-1">Overall Return</p>
        <p class="text-xl font-bold" :class="portfolio.total_profit_loss_percentage >= 0 ? 'text-green-600' : 'text-red-600'">
          {{ portfolio.total_profit_loss_percentage >= 0 ? '+' : '' }}{{ Number(portfolio.total_profit_loss_percentage || 0).toFixed(2) }}%
        </p>
        <p class="text-xs text-gray-400 mt-1">{{ portfolio.unique_symbols }} symbols · {{ portfolio.count }} lots</p>
      </div>
    </div>

    <!-- Charts Row -->
    <div v-if="portfolio && portfolio.by_symbol?.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <!-- Allocation Donut -->
      <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Portfolio Allocation</h3>
        <div class="flex items-center gap-6">
          <DonutChart :segments="allocationSegments" :size="160">
            <span class="text-xs text-gray-400 leading-tight">Allocation</span>
          </DonutChart>
          <div class="flex-1 space-y-2 min-w-0">
            <div v-for="(seg, i) in allocationSegments" :key="i" class="flex items-center gap-2 text-sm">
              <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ background: seg.color }"></span>
              <span class="font-medium text-gray-700 truncate">{{ seg.label }}</span>
              <span class="ml-auto text-gray-500 flex-shrink-0">{{ seg.pct.toFixed(1) }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- P&L Bar Chart by Symbol -->
      <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">P&amp;L by Symbol</h3>
        <div class="space-y-2.5">
          <div v-for="(sym, i) in sortedBySymbol" :key="i" class="flex items-center gap-3 text-sm">
            <span class="w-16 font-bold text-gray-700 text-right flex-shrink-0">{{ sym.symbol }}</span>
            <div class="flex-1 h-5 bg-gray-100 rounded overflow-hidden">
              <div
                class="h-full rounded transition-all duration-500"
                :class="sym.unrealized_pnl >= 0 ? 'bg-green-500' : 'bg-red-500'"
                :style="{ width: plBarWidth(sym.unrealized_pnl) + '%' }"
              />
            </div>
            <span class="w-28 text-right flex-shrink-0 font-semibold text-xs" :class="sym.unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600'">
              {{ sym.unrealized_pnl >= 0 ? '+' : '' }}{{ fmt(sym.unrealized_pnl) }}
            </span>
          </div>
        </div>
        <!-- Diversification badge -->
        <div class="mt-4 pt-4 border-t flex items-center justify-between text-xs text-gray-500">
          <span>Diversification (HHI)</span>
          <span :class="divBadgeClass">{{ portfolio.diversification_level?.toUpperCase() }}</span>
        </div>
      </div>
    </div>

    <!-- By-Symbol Summary Table -->
    <div v-if="portfolio && portfolio.by_symbol?.length" class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h3 class="text-sm font-semibold text-gray-700">By Symbol</h3>
        <span class="text-xs text-gray-400">{{ portfolio.unique_symbols }} symbols</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Symbol</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Shares</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Avg Cost</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Current</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Value</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">P&amp;L</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Alloc</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(sym, i) in portfolio.by_symbol" :key="i" class="border-t hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ background: symbolColor(sym.symbol, i) }"></span>
                  <span class="font-bold text-indigo-600">{{ sym.symbol }}</span>
                  <span class="text-gray-400 text-xs hidden sm:block">{{ sym.company_name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right text-gray-700">{{ Number(sym.total_shares).toLocaleString() }}</td>
              <td class="px-4 py-3 text-right text-gray-500">{{ fmt(sym.weighted_avg_cost) }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ fmt(sym.current_price) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ fmt(sym.current_value) }}</td>
              <td class="px-4 py-3 text-right font-semibold" :class="sym.unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ sym.unrealized_pnl >= 0 ? '+' : '' }}{{ fmt(sym.unrealized_pnl) }}
                <span class="block text-xs font-normal opacity-75">({{ sym.unrealized_pnl_pct >= 0 ? '+' : '' }}{{ sym.unrealized_pnl_pct }}%)</span>
              </td>
              <td class="px-4 py-3 text-right">
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ sym.portfolio_allocation_pct }}%</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Individual Lots Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h3 class="text-sm font-semibold text-gray-700">Individual Lots</h3>
        <span class="text-xs text-gray-400">{{ store.pagination?.total ?? store.items.length }} records</span>
      </div>
      <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Symbol</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium hidden sm:table-cell">Company</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Shares</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Buy</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Current</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">P&amp;L</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="7" class="text-center py-10 text-gray-400">No stock holdings found. Add your first stock to get started.</td>
            </tr>
            <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3 font-bold text-indigo-600">{{ item.symbol }}</td>
              <td class="px-4 py-3 text-gray-700 hidden sm:table-cell">{{ item.company_name }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ Number(item.shares).toLocaleString() }}</td>
              <td class="px-4 py-3 text-right text-gray-500">{{ fmt(item.buy_price) }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ fmt(item.current_price) }}</td>
              <td class="px-4 py-3 text-right font-semibold" :class="stockPL(item) >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ stockPL(item) >= 0 ? '+' : '' }}{{ fmt(stockPL(item)) }}
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
      <div v-if="store.pagination && store.pagination.last_page > 1" class="flex justify-between items-center px-5 py-3 border-t text-sm text-gray-500">
        <span>Page {{ store.pagination.current_page }} of {{ store.pagination.last_page }}</span>
        <div class="flex gap-2">
          <button :disabled="store.pagination.current_page <= 1" @click="changePage(store.pagination.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100">Prev</button>
          <button :disabled="store.pagination.current_page >= store.pagination.last_page" @click="changePage(store.pagination.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100">Next</button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editing ? 'Edit Stock Lot' : 'Add Stock Lot' }}</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Symbol *</label>
            <input v-model="form.symbol" required class="w-full border rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. JFC, SM, BDO" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
            <input v-model="form.company_name" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Shares *</label>
              <input v-model="form.shares" type="number" min="0" step="0.0001" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Buy Price *</label>
              <input v-model="form.buy_price" type="number" min="0" step="0.0001" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Current Price</label>
              <input v-model="form.current_price" type="number" min="0" step="0.0001" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
              <input v-model="form.purchase_date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="2"></textarea>
          </div>
          <div v-if="formError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-indigo-700">
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete Dialog -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Stock Lot</h3>
        <p class="text-sm text-gray-500 mb-4">Delete {{ deleteTarget.symbol }} lot? This cannot be undone.</p>
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
import { useStockStore } from '@/stores/stock';
import DonutChart from '@/components/charts/DonutChart.vue';

const store = useStockStore();
const showModal = ref(false);
const editing  = ref(null);
const deleteTarget = ref(null);
const saving   = ref(false);
const formError = ref('');

const PALETTE = ['#6366F1','#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#F97316','#84CC16'];

const defaultForm = () => ({
  symbol: '',
  company_name: '',
  shares: '',
  buy_price: '',
  current_price: '',
  purchase_date: new Date().toISOString().split('T')[0],
  notes: '',
});

const form = ref(defaultForm());

const portfolio = computed(() => store.portfolio);

// Allocation segments for DonutChart
const allocationSegments = computed(() => {
  return (portfolio.value?.by_symbol ?? []).map((sym, i) => ({
    label: sym.symbol,
    value: sym.portfolio_allocation_pct,
    pct:   sym.portfolio_allocation_pct,
    color: PALETTE[i % PALETTE.length],
  }));
});

// P&L bars — sorted by absolute value so largest bar fills width
const sortedBySymbol = computed(() => {
  return [...(portfolio.value?.by_symbol ?? [])].sort(
    (a, b) => Math.abs(b.unrealized_pnl) - Math.abs(a.unrealized_pnl)
  );
});

const maxAbsPnl = computed(() => {
  const vals = sortedBySymbol.value.map(s => Math.abs(s.unrealized_pnl));
  return Math.max(...vals, 1);
});

function plBarWidth(pnl) {
  return Math.min((Math.abs(pnl) / maxAbsPnl.value) * 100, 100);
}

function symbolColor(symbol, fallbackIndex) {
  const idx = (portfolio.value?.by_symbol ?? []).findIndex(s => s.symbol === symbol);
  return PALETTE[(idx >= 0 ? idx : fallbackIndex) % PALETTE.length];
}

const divBadgeClass = computed(() => {
  const level = portfolio.value?.diversification_level;
  if (level === 'high')     return 'bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-semibold';
  if (level === 'moderate') return 'bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-semibold';
  return 'bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-semibold';
});

function fmt(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function stockPL(item) {
  return (Number(item.current_price || 0) - Number(item.buy_price || 0)) * Number(item.shares || 0);
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
    await store.fetchPortfolio();
    showModal.value = false;
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save. Please try again.';
  } finally {
    saving.value = false;
  }
}

async function handleDelete() {
  await store.remove(deleteTarget.value.id);
  await store.fetchPortfolio();
  deleteTarget.value = null;
}

function changePage(page) {
  store.fetchAll({ page });
}

onMounted(() => {
  store.fetchAll();
  store.fetchPortfolio();
});
</script>
