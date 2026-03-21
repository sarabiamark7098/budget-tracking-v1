<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Stocks</h1>
      <button @click="openAddStockModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium">
        + Add Stock
      </button>
    </div>

    <!-- Portfolio Summary Cards -->
    <div v-if="portfolio" class="grid grid-cols-2 lg:grid-cols-3 gap-4">
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
        <p class="text-xs text-gray-400 mt-1">{{ portfolio.unique_symbols }} symbols</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-xs text-gray-500 mb-1">Total Transferred</p>
        <p class="text-xl font-bold text-indigo-600">{{ fmt(portfolio.total_transferred ?? 0) }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5">
        <p class="text-xs text-gray-500 mb-1">Available Balance</p>
        <p class="text-xl font-bold" :class="(portfolio.available_balance ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600'">
          {{ fmt(portfolio.available_balance ?? 0) }}
        </p>
        <p class="text-xs text-gray-400 mt-1">Transferred − Cost Basis</p>
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
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Latest Price</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Value</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">P&amp;L</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Alloc</th>
              <th class="px-4 py-3"></th>
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
              <td class="px-4 py-3 text-right text-gray-700 font-semibold">{{ fmt(sym.latest_price) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ fmt(sym.current_value) }}</td>
              <td class="px-4 py-3 text-right font-semibold" :class="sym.unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ sym.unrealized_pnl >= 0 ? '+' : '' }}{{ fmt(sym.unrealized_pnl) }}
                <span class="block text-xs font-normal opacity-75">({{ sym.unrealized_pnl_pct >= 0 ? '+' : '' }}{{ sym.unrealized_pnl_pct }}%)</span>
              </td>
              <td class="px-4 py-3 text-right">
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ sym.portfolio_allocation_pct }}%</span>
              </td>
              <td class="px-4 py-3 text-right">
                <button @click="openUpdatePriceModal(sym)" class="text-indigo-500 hover:text-indigo-700 text-xs px-2 py-1 border rounded whitespace-nowrap">
                  Update Price
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Individual Lots (Stocks List) -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h3 class="text-sm font-semibold text-gray-700">Individual Lots</h3>
        <span class="text-xs text-gray-400">{{ store.pagination?.total ?? store.items.length }} stocks</span>
      </div>
      <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Symbol</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium hidden sm:table-cell">Company</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Latest Price</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="4" class="text-center py-10 text-gray-400">No stocks found. Add your first stock to get started.</td>
            </tr>
            <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3 font-bold text-indigo-600">{{ item.symbol }}</td>
              <td class="px-4 py-3 text-gray-700 hidden sm:table-cell">{{ item.company_name }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ item.latest_price ? fmt(item.latest_price) : '—' }}</td>
              <td class="px-4 py-3">
                <div class="flex gap-2 justify-end">
                  <button @click="openAddLotModal(item)" class="text-green-600 hover:text-green-800 text-xs px-2 py-1 border border-green-300 rounded">Pay</button>
                  <button @click="openHistoryModal(item)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">History</button>
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

    <!-- Add Stock Modal -->
    <div v-if="showStockModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">Add Stock</h2>
          <button @click="showStockModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleAddStock" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Symbol *</label>
            <input v-model="stockForm.symbol" required class="w-full border rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. JFC, SM, BDO" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Company Name *</label>
            <input v-model="stockForm.company_name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. Jollibee Foods Corp." />
          </div>
          <div v-if="stockFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ stockFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showStockModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-indigo-700">
              {{ saving ? 'Saving...' : 'Add Stock' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Lot (Pay) Modal -->
    <div v-if="showLotModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <div>
            <h2 class="font-semibold text-gray-800">Add Lot — {{ lotTarget?.symbol }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ lotTarget?.company_name }}</p>
          </div>
          <button @click="showLotModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleAddLot" class="p-5 space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Shares *</label>
              <input v-model="lotForm.shares" type="number" min="0.0001" step="0.0001" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Buy Price *</label>
              <input v-model="lotForm.buy_price" type="number" min="0" step="0.0001" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
          </div>
          <!-- Cost preview -->
          <div v-if="lotForm.shares && lotForm.buy_price" class="text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
            Total cost: <span class="font-semibold text-gray-700">{{ fmt(Number(lotForm.shares) * Number(lotForm.buy_price)) }}</span>
          </div>
          <div v-if="lotFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ lotFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showLotModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-green-700">
              {{ saving ? 'Saving...' : 'Add Lot' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- History Modal -->
    <div v-if="showHistoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b sticky top-0 bg-white">
          <div>
            <h2 class="font-semibold text-gray-800">Lot History — {{ historyTarget?.symbol }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ historyTarget?.company_name }}</p>
          </div>
          <button @click="showHistoryModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5">
          <div v-if="historyLoading" class="text-center py-8 text-gray-400">Loading...</div>
          <div v-else-if="!historyLots.length" class="text-center py-8 text-gray-400">No lots recorded yet.</div>
          <table v-else class="w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left px-3 py-2 text-gray-500 font-medium">Date Purchased</th>
                <th class="text-right px-3 py-2 text-gray-500 font-medium">Shares</th>
                <th class="text-right px-3 py-2 text-gray-500 font-medium">Buy Price</th>
                <th class="text-right px-3 py-2 text-gray-500 font-medium">P&amp;L</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="lot in historyLots" :key="lot.id" class="border-t hover:bg-gray-50">
                <td class="px-3 py-2 text-gray-600">{{ formatDate(lot.purchase_date) }}</td>
                <td class="px-3 py-2 text-right text-gray-700">{{ Number(lot.shares).toLocaleString() }}</td>
                <td class="px-3 py-2 text-right text-gray-500">{{ fmt(lot.buy_price) }}</td>
                <td class="px-3 py-2 text-right font-semibold" :class="lot.pnl >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ lot.pnl >= 0 ? '+' : '' }}{{ fmt(lot.pnl) }}
                  <span class="block text-xs font-normal opacity-75">({{ lot.pnl_pct >= 0 ? '+' : '' }}{{ lot.pnl_pct }}%)</span>
                </td>
              </tr>
            </tbody>
            <tfoot class="border-t bg-gray-50">
              <tr>
                <td class="px-3 py-2 font-semibold text-gray-700" colspan="4">Total P&amp;L</td>
                <td class="px-3 py-2 text-right font-bold" :class="totalHistoryPnl >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ totalHistoryPnl >= 0 ? '+' : '' }}{{ fmt(totalHistoryPnl) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Update Latest Price Modal -->
    <div v-if="showPriceModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b">
          <div>
            <h2 class="font-semibold text-gray-800">Update Latest Price</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ priceTarget?.symbol }} — {{ priceTarget?.company_name }}</p>
          </div>
          <button @click="showPriceModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleUpdatePrice" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Latest Price *</label>
            <input v-model="priceForm.latest_price" type="number" min="0" step="0.0001" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
          </div>
          <div v-if="priceFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ priceFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showPriceModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-indigo-700">
              {{ saving ? 'Saving...' : 'Update' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete Dialog -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Stock</h3>
        <p class="text-sm text-gray-500 mb-4">Delete <strong>{{ deleteTarget.symbol }}</strong> and all its lots? This cannot be undone.</p>
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

// --- Add Stock Modal ---
const showStockModal = ref(false);
const stockForm = ref({ symbol: '', company_name: '' });
const stockFormError = ref('');

// --- Add Lot Modal ---
const showLotModal = ref(false);
const lotTarget = ref(null);
const lotForm = ref({ shares: '', buy_price: '', current_price: '', purchase_date: '' });
const lotFormError = ref('');

// --- History Modal ---
const showHistoryModal = ref(false);
const historyTarget = ref(null);
const historyLots = ref([]);
const historyLoading = ref(false);

// --- Update Price Modal ---
const showPriceModal = ref(false);
const priceTarget = ref(null);
const priceForm = ref({ latest_price: '' });
const priceFormError = ref('');

// --- Shared ---
const saving = ref(false);
const deleteTarget = ref(null);

const portfolio = computed(() => store.portfolio);

const PALETTE = ['#6366F1','#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#F97316','#84CC16'];

const allocationSegments = computed(() =>
  (portfolio.value?.by_symbol ?? []).map((sym, i) => ({
    label: sym.symbol,
    value: sym.portfolio_allocation_pct,
    pct:   sym.portfolio_allocation_pct,
    color: PALETTE[i % PALETTE.length],
  }))
);

const sortedBySymbol = computed(() =>
  [...(portfolio.value?.by_symbol ?? [])].sort((a, b) => Math.abs(b.unrealized_pnl) - Math.abs(a.unrealized_pnl))
);

const maxAbsPnl = computed(() => Math.max(...sortedBySymbol.value.map(s => Math.abs(s.unrealized_pnl)), 1));

const totalHistoryPnl = computed(() => historyLots.value.reduce((sum, l) => sum + (l.pnl ?? 0), 0));

const divBadgeClass = computed(() => {
  const level = portfolio.value?.diversification_level;
  if (level === 'high')     return 'bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-semibold';
  if (level === 'moderate') return 'bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-semibold';
  return 'bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-semibold';
});

function plBarWidth(pnl) {
  return Math.min((Math.abs(pnl) / maxAbsPnl.value) * 100, 100);
}

function symbolColor(symbol, fallbackIndex) {
  const idx = (portfolio.value?.by_symbol ?? []).findIndex(s => s.symbol === symbol);
  return PALETTE[(idx >= 0 ? idx : fallbackIndex) % PALETTE.length];
}

function fmt(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function formatDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

// --- Add Stock ---
function openAddStockModal() {
  stockForm.value = { symbol: '', company_name: '' };
  stockFormError.value = '';
  showStockModal.value = true;
}

async function handleAddStock() {
  saving.value = true;
  stockFormError.value = '';
  try {
    await store.create({ ...stockForm.value, symbol: stockForm.value.symbol.toUpperCase() });
    showStockModal.value = false;
  } catch (e) {
    stockFormError.value = e.response?.data?.message ?? 'Failed to save. Please try again.';
  } finally {
    saving.value = false;
  }
}

// --- Add Lot ---
function openAddLotModal(stock) {
  lotTarget.value = stock;
  lotForm.value = {
    shares: '',
    buy_price: '',
  };
  lotFormError.value = '';
  showLotModal.value = true;
}

async function handleAddLot() {
  saving.value = true;
  lotFormError.value = '';
  try {
    await store.addLot(lotTarget.value.id, lotForm.value);
    showLotModal.value = false;
  } catch (e) {
    lotFormError.value = e.response?.data?.message ?? 'Failed to add lot.';
  } finally {
    saving.value = false;
  }
}

// --- History ---
async function openHistoryModal(stock) {
  historyTarget.value = stock;
  historyLots.value = [];
  showHistoryModal.value = true;
  historyLoading.value = true;
  try {
    historyLots.value = await store.fetchLots(stock.id);
  } finally {
    historyLoading.value = false;
  }
}

// --- Update Price ---
function openUpdatePriceModal(sym) {
  priceTarget.value = sym;
  priceForm.value = { latest_price: sym.latest_price ?? '' };
  priceFormError.value = '';
  showPriceModal.value = true;
}

async function handleUpdatePrice() {
  saving.value = true;
  priceFormError.value = '';
  try {
    await store.updatePrice(priceTarget.value.stock_id, priceForm.value.latest_price);
    showPriceModal.value = false;
  } catch (e) {
    priceFormError.value = e.response?.data?.message ?? 'Failed to update price.';
  } finally {
    saving.value = false;
  }
}

// --- Delete ---
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
  store.fetchPortfolio();
});
</script>
