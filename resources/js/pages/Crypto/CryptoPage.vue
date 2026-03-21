<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Crypto</h1>
      <button @click="openModal()" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 text-sm font-medium">
        + Add Crypto
      </button>
    </div>

    <!-- Portfolio Summary Cards -->
    <div v-if="portfolio" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-xs text-gray-500 mb-1">Total Invested</p>
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
        <p class="text-xs text-gray-400 mt-1">{{ bySymbol.length }} coins · {{ portfolio.count }} entries</p>
      </div>
    </div>

    <!-- Charts Row -->
    <div v-if="portfolio && bySymbol.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
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
              <span class="font-medium text-gray-700 truncate uppercase">{{ seg.label }}</span>
              <span class="ml-auto text-gray-500 flex-shrink-0">{{ seg.pct.toFixed(1) }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- P&L Bar Chart by Coin -->
      <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">P&amp;L by Coin</h3>
        <div class="space-y-2.5">
          <div v-for="(sym, i) in sortedBySymbol" :key="i" class="flex items-center gap-3 text-sm">
            <span class="w-14 font-bold text-gray-700 text-right flex-shrink-0 uppercase">{{ sym.symbol }}</span>
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
      </div>
    </div>

    <!-- By-Coin Summary Table -->
    <div v-if="bySymbol.length" class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h3 class="text-sm font-semibold text-gray-700">By Coin</h3>
        <span class="text-xs text-gray-400">{{ bySymbol.length }} coins</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Coin</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Qty</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Avg Cost</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Current</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Value</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">P&amp;L</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Alloc</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(sym, i) in bySymbol" :key="i" class="border-t hover:bg-gray-50">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ background: PALETTE[i % PALETTE.length] }"></span>
                  <span class="font-bold text-yellow-600 uppercase">{{ sym.symbol }}</span>
                  <span class="text-gray-400 text-xs hidden sm:block">{{ sym.coin_name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right text-gray-700">{{ Number(sym.total_qty).toLocaleString('en-PH', { minimumFractionDigits: 4 }) }}</td>
              <td class="px-4 py-3 text-right text-gray-500">{{ fmt(sym.avg_cost) }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ fmt(sym.current_price) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ fmt(sym.current_value) }}</td>
              <td class="px-4 py-3 text-right font-semibold" :class="sym.unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ sym.unrealized_pnl >= 0 ? '+' : '' }}{{ fmt(sym.unrealized_pnl) }}
                <span class="block text-xs font-normal opacity-75">({{ sym.unrealized_pnl_pct >= 0 ? '+' : '' }}{{ sym.unrealized_pnl_pct.toFixed(2) }}%)</span>
              </td>
              <td class="px-4 py-3 text-right">
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ sym.alloc_pct.toFixed(1) }}%</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Individual Holdings Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h3 class="text-sm font-semibold text-gray-700">Individual Holdings</h3>
        <span class="text-xs text-gray-400">{{ store.pagination?.total ?? store.items.length }} records</span>
      </div>
      <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Coin</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium hidden sm:table-cell">Symbol</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Qty</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Buy</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Current</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">P&amp;L</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="7" class="text-center py-10 text-gray-400">No crypto holdings found. Add your first coin to get started.</td>
            </tr>
            <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-700">{{ item.coin_name }}</td>
              <td class="px-4 py-3 font-bold text-yellow-600 uppercase hidden sm:table-cell">{{ item.symbol }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ Number(item.quantity).toLocaleString('en-PH', { minimumFractionDigits: 4 }) }}</td>
              <td class="px-4 py-3 text-right text-gray-500">{{ fmt(item.buy_price) }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ fmt(item.current_price) }}</td>
              <td class="px-4 py-3 text-right font-semibold" :class="cryptoPL(item) >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ cryptoPL(item) >= 0 ? '+' : '' }}{{ fmt(cryptoPL(item)) }}
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
          <h2 class="font-semibold text-gray-800">{{ editing ? 'Edit Crypto' : 'Add Crypto' }}</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Coin Name *</label>
            <input v-model="form.coin_name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500" placeholder="e.g. Bitcoin, Ethereum" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Symbol *</label>
            <input v-model="form.symbol" required class="w-full border rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-yellow-500" placeholder="e.g. BTC, ETH" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
              <input v-model="form.quantity" type="number" min="0" step="0.00000001" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Buy Price (₱) *</label>
              <input v-model="form.buy_price" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Current Price (₱)</label>
              <input v-model="form.current_price" type="number" min="0" step="0.01" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
              <input v-model="form.purchase_date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500" rows="2"></textarea>
          </div>
          <div v-if="formError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-yellow-600">
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
import { ref, computed, onMounted } from 'vue';
import { useCryptoStore } from '@/stores/crypto';
import DonutChart from '@/components/charts/DonutChart.vue';

const store = useCryptoStore();
const showModal   = ref(false);
const editing     = ref(null);
const deleteTarget = ref(null);
const saving      = ref(false);
const formError   = ref('');

const PALETTE = ['#F59E0B','#EF4444','#3B82F6','#10B981','#8B5CF6','#EC4899','#6366F1','#14B8A6','#F97316','#84CC16'];

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

const portfolio = computed(() => store.portfolio);

// Client-side aggregation by symbol (API returns flat `assets` array)
const bySymbol = computed(() => {
  const assets = portfolio.value?.assets ?? [];
  const totalCurrentValue = portfolio.value?.total_current_value ?? 0;
  const map = {};

  for (const a of assets) {
    const sym = (a.symbol ?? '').toUpperCase();
    if (!map[sym]) {
      map[sym] = { symbol: sym, coin_name: a.coin_name, total_qty: 0, total_cost: 0, current_value: 0, current_price: 0 };
    }
    const qty   = Number(a.quantity || 0);
    const buy   = Number(a.buy_price || 0);
    const price = Number(a.current_price || 0);
    map[sym].total_qty    += qty;
    map[sym].total_cost   += qty * buy;
    map[sym].current_value += qty * price;
    map[sym].current_price  = price; // keep latest
  }

  return Object.values(map)
    .map(s => {
      const unrealized_pnl     = s.current_value - s.total_cost;
      const unrealized_pnl_pct = s.total_cost > 0 ? (unrealized_pnl / s.total_cost) * 100 : 0;
      const alloc_pct          = totalCurrentValue > 0 ? (s.current_value / totalCurrentValue) * 100 : 0;
      return {
        ...s,
        avg_cost: s.total_qty > 0 ? s.total_cost / s.total_qty : 0,
        unrealized_pnl,
        unrealized_pnl_pct,
        alloc_pct,
      };
    })
    .sort((a, b) => b.current_value - a.current_value);
});

const allocationSegments = computed(() =>
  bySymbol.value.map((sym, i) => ({
    label: sym.symbol,
    value: sym.alloc_pct,
    pct:   sym.alloc_pct,
    color: PALETTE[i % PALETTE.length],
  }))
);

const sortedBySymbol = computed(() =>
  [...bySymbol.value].sort((a, b) => Math.abs(b.unrealized_pnl) - Math.abs(a.unrealized_pnl))
);

const maxAbsPnl = computed(() => {
  const vals = sortedBySymbol.value.map(s => Math.abs(s.unrealized_pnl));
  return Math.max(...vals, 1);
});

function plBarWidth(pnl) {
  return Math.min((Math.abs(pnl) / maxAbsPnl.value) * 100, 100);
}

function fmt(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
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
