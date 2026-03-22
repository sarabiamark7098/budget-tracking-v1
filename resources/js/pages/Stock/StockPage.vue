<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Stocks</h1>
      <button @click="openAddStockModal()" class="bg-indigo-600 text-white px-3 py-2 sm:px-4 rounded-lg hover:bg-indigo-700 text-sm font-medium">
        + Add
      </button>
    </div>

    <!-- Portfolio Summary -->
    <div v-if="portfolio" class="space-y-3">
      <!-- Primary cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <!-- Cost Basis -->
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5 border-t-2 border-gray-200">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Cost Basis</p>
          <p class="text-lg lg:text-xl font-bold text-gray-800">{{ fmt(portfolio.total_cost_basis) }}</p>
          <p class="text-[10px] text-gray-400 mt-1">{{ portfolio.unique_symbols }} symbol{{ portfolio.unique_symbols !== 1 ? 's' : '' }}</p>
        </div>
        <!-- Portfolio Value + mini P&L bar -->
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5 border-t-2 border-blue-400">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Portfolio Value</p>
          <p class="text-lg lg:text-xl font-bold text-blue-600">{{ fmt(portfolio.total_current_value) }}</p>
          <div class="mt-2 w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
              :class="portfolio.total_profit_loss >= 0 ? 'bg-green-400' : 'bg-red-400'"
              :style="{ width: valueBarWidth + '%' }"
            />
          </div>
          <p class="text-[10px] mt-1" :class="portfolio.total_profit_loss >= 0 ? 'text-green-600' : 'text-red-500'">
            {{ portfolio.total_profit_loss >= 0 ? '+' : '' }}{{ fmt(portfolio.total_profit_loss) }}
            ({{ portfolio.total_profit_loss_percentage >= 0 ? '+' : '' }}{{ Number(portfolio.total_profit_loss_percentage || 0).toFixed(2) }}%)
          </p>
        </div>
        <!-- Available Balance -->
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5 border-t-2"
          :class="(portfolio.available_balance ?? 0) >= 0 ? 'border-emerald-400' : 'border-red-400'">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Available Balance</p>
          <p class="text-lg lg:text-xl font-bold" :class="(portfolio.available_balance ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600'">
            {{ fmt(portfolio.available_balance ?? 0) }}
          </p>
          <p class="text-[10px] text-gray-400 mt-1">In − Out − Lots + Sales + Div</p>
        </div>
        <!-- Dividends -->
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5 border-t-2 border-amber-300">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Dividends</p>
          <p class="text-lg lg:text-xl font-bold text-amber-600">{{ fmt(portfolio.total_dividends ?? 0) }}</p>
          <p class="text-[10px] text-green-600 mt-1">+{{ fmt(portfolio.total_sale_proceeds ?? 0) }} sale proceeds</p>
        </div>
      </div>

      <!-- Funding flow strip -->
      <div class="bg-white rounded-xl shadow-sm px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
        <div>
          <span class="text-xs text-gray-400 uppercase tracking-wide mr-1">Transferred In</span>
          <span class="font-semibold text-indigo-600">{{ fmt(portfolio.total_transferred ?? 0) }}</span>
        </div>
        <span class="text-gray-300 hidden sm:inline">→</span>
        <div>
          <span class="text-xs text-gray-400 mr-1">+Sales</span>
          <span class="font-semibold text-green-600">{{ fmt(portfolio.total_sale_proceeds ?? 0) }}</span>
        </div>
        <span class="text-gray-300 hidden sm:inline">+</span>
        <div>
          <span class="text-xs text-gray-400 mr-1">+Dividends</span>
          <span class="font-semibold text-amber-600">{{ fmt(portfolio.total_dividends ?? 0) }}</span>
        </div>
        <span class="text-gray-300 hidden sm:inline">−</span>
        <div>
          <span class="text-xs text-gray-400 mr-1">−Lots Cost</span>
          <span class="font-semibold text-gray-600">{{ fmt(portfolio.total_cost_basis ?? 0) }}</span>
        </div>
        <span class="text-gray-300 hidden sm:inline">=</span>
        <div class="ml-auto">
          <span class="text-xs text-gray-400 mr-1">Available</span>
          <span class="font-bold" :class="(portfolio.available_balance ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600'">
            {{ fmt(portfolio.available_balance ?? 0) }}
          </span>
        </div>
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
        <div class="space-y-3">
          <div v-for="(sym, i) in sortedBySymbol" :key="i" class="text-sm">
            <div class="flex items-center justify-between mb-1">
              <span class="font-bold text-gray-700 w-16 flex-shrink-0">{{ sym.symbol }}</span>
              <span class="text-xs font-semibold flex-shrink-0" :class="sym.unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ sym.unrealized_pnl >= 0 ? '+' : '' }}{{ fmt(sym.unrealized_pnl) }}
                <span class="font-normal opacity-75">({{ sym.unrealized_pnl_pct >= 0 ? '+' : '' }}{{ sym.unrealized_pnl_pct }}%)</span>
              </span>
            </div>
            <!-- Bidirectional bar: center = 0, positive → right, negative → left -->
            <div class="relative h-3 bg-gray-100 rounded-full overflow-hidden">
              <div class="absolute top-0 bottom-0 left-1/2 w-px bg-gray-300" />
              <div v-if="sym.unrealized_pnl >= 0"
                class="absolute top-0 bottom-0 bg-green-400 rounded-r transition-all duration-500"
                :style="{ left: '50%', width: biBarWidth(sym.unrealized_pnl) + '%' }"
              />
              <div v-else
                class="absolute top-0 bottom-0 bg-red-400 rounded-l transition-all duration-500"
                :style="{ right: '50%', width: biBarWidth(sym.unrealized_pnl) + '%' }"
              />
            </div>
          </div>
        </div>
        <div class="mt-4 pt-3 border-t flex items-center justify-between text-xs text-gray-500">
          <span>Diversification (HHI)</span>
          <span :class="divBadgeClass">{{ portfolio.diversification_level?.toUpperCase() }}</span>
        </div>
      </div>
    </div>

    <!-- By-Symbol Summary Table -->
    <div v-if="portfolio && portfolio.by_symbol?.length" class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h3 class="text-sm font-semibold text-gray-700">By Symbol</h3>
        <div class="flex items-center gap-3">
          <span v-if="portfolio.total_dividends > 0" class="text-xs text-amber-600 font-medium bg-amber-50 px-2 py-0.5 rounded-full">
            Total Dividends: {{ fmt(portfolio.total_dividends) }}
          </span>
          <span class="text-xs text-gray-400">{{ portfolio.unique_symbols }} symbols</span>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="w-1 p-0"></th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Symbol</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Net Shares</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Avg Cost</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Latest Price</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Value</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">P&amp;L</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Dividends</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Alloc</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(sym, i) in portfolio.by_symbol" :key="i" class="border-t hover:bg-gray-50">
              <!-- Color indicator bar -->
              <td class="w-1 p-0">
                <div class="w-1 h-full min-h-[48px] rounded-sm" :class="sym.unrealized_pnl >= 0 ? 'bg-green-400' : 'bg-red-400'" />
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ background: symbolColor(sym.symbol, i) }"></span>
                  <span class="font-bold text-indigo-600">{{ sym.symbol }}</span>
                  <span class="text-gray-400 text-xs hidden sm:block">{{ sym.company_name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right text-gray-700">
                <div class="font-medium">{{ Number(sym.net_shares).toLocaleString() }}</div>
                <div v-if="sym.total_shares_bought > sym.net_shares" class="text-[10px] text-gray-400">
                  {{ Number(sym.total_shares_bought).toLocaleString() }} bought
                </div>
              </td>
              <td class="px-4 py-3 text-right text-gray-500">{{ fmt(sym.weighted_avg_cost) }}</td>
              <td class="px-4 py-3 text-right text-gray-700 font-semibold">{{ fmt(sym.latest_price) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ fmt(sym.current_value) }}</td>
              <td class="px-4 py-3 text-right">
                <div class="font-semibold" :class="sym.unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ sym.unrealized_pnl >= 0 ? '+' : '' }}{{ fmt(sym.unrealized_pnl) }}
                </div>
                <div class="text-xs mt-0.5 font-medium px-1.5 py-0.5 rounded-full inline-block"
                  :class="sym.unrealized_pnl >= 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500'">
                  {{ sym.unrealized_pnl_pct >= 0 ? '+' : '' }}{{ sym.unrealized_pnl_pct }}%
                </div>
              </td>
              <td class="px-4 py-3 text-right">
                <template v-if="sym.total_dividends > 0">
                  <span class="font-semibold text-amber-600">{{ fmt(sym.total_dividends) }}</span>
                  <span class="block text-[10px] text-gray-400">{{ sym.dividend_count }} payment{{ sym.dividend_count !== 1 ? 's' : '' }}</span>
                </template>
                <span v-else class="text-gray-300">—</span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <div class="w-12 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-400 rounded-full" :style="{ width: sym.portfolio_allocation_pct + '%' }" />
                  </div>
                  <span class="text-xs text-gray-500 w-8 text-right">{{ sym.portfolio_allocation_pct }}%</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right">
                <button @click="openUpdatePriceModal(sym)" class="text-indigo-500 hover:text-indigo-700 text-xs px-2 py-1 border rounded whitespace-nowrap">
                  Update Price
                </button>
              </td>
            </tr>
          </tbody>
          <tfoot v-if="portfolio.total_dividends > 0" class="border-t bg-amber-50">
            <tr>
              <td colspan="7" class="px-4 py-2 text-xs font-semibold text-amber-700">Total Dividends Received</td>
              <td class="px-4 py-2 text-right font-bold text-amber-700">{{ fmt(portfolio.total_dividends) }}</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Individual Stocks List -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h3 class="text-sm font-semibold text-gray-700">Individual Stocks</h3>
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
              <th class="text-right px-4 py-3 text-gray-500 font-medium hidden sm:table-cell">Net Shares</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="5" class="text-center py-10 text-gray-400">No stocks found. Add your first stock to get started.</td>
            </tr>
            <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3 font-bold text-indigo-600">{{ item.symbol }}</td>
              <td class="px-4 py-3 text-gray-700 hidden sm:table-cell">{{ item.company_name }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ item.latest_price ? fmt(item.latest_price) : '—' }}</td>
              <td class="px-4 py-3 text-right text-gray-600 hidden sm:table-cell">
                {{ item.net_shares > 0 ? Number(item.net_shares).toLocaleString() : '—' }}
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-1.5 justify-end flex-wrap">
                  <!-- Buy (was Pay) -->
                  <button
                    @click="openBuyModal(item)"
                    class="text-green-600 hover:text-green-800 text-xs px-2 py-1 border border-green-300 rounded bg-green-50 hover:bg-green-100"
                  >Buy</button>
                  <!-- Sell — only when shares > 0 -->
                  <button
                    v-if="item.net_shares > 0"
                    @click="openSellModal(item)"
                    class="text-orange-600 hover:text-orange-800 text-xs px-2 py-1 border border-orange-300 rounded bg-orange-50 hover:bg-orange-100"
                  >Sell</button>
                  <!-- Dividend -->
                  <button
                    @click="openDividendModal(item)"
                    class="text-amber-600 hover:text-amber-800 text-xs px-2 py-1 border border-amber-300 rounded bg-amber-50 hover:bg-amber-100"
                  >Dividend</button>
                  <!-- History -->
                  <button @click="openHistoryModal(item)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">History</button>
                  <!-- Div History -->
                  <button @click="openDivHistoryModal(item)" class="text-amber-500 hover:text-amber-700 text-xs px-2 py-1 border border-amber-300 rounded">Div History</button>
                  <!-- Delete -->
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

    <!-- ── Add Stock Modal ──────────────────────────────────────────────────── -->
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

    <!-- ── Buy Lot Modal ────────────────────────────────────────────────────── -->
    <div v-if="showBuyModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <div>
            <h2 class="font-semibold text-gray-800">Buy Shares — {{ buyTarget?.symbol }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ buyTarget?.company_name }}</p>
          </div>
          <button @click="showBuyModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleBuyLot" class="p-5 space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Shares *</label>
              <input v-model="buyForm.shares" type="number" min="0.0001" step="0.0001" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Buy Price *</label>
              <input v-model="buyForm.buy_price" type="number" min="0" step="0.0001" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
          </div>
          <div v-if="buyForm.shares && buyForm.buy_price" class="text-xs text-gray-500 bg-green-50 rounded-lg px-3 py-2 flex justify-between">
            <span>Total cost</span>
            <span class="font-semibold text-green-700">{{ fmt(Number(buyForm.shares) * Number(buyForm.buy_price)) }}</span>
          </div>
          <div v-if="buyFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ buyFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showBuyModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-green-700">
              {{ saving ? 'Processing...' : 'Buy' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Sell Shares Modal ────────────────────────────────────────────────── -->
    <div v-if="showSellModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <div>
            <h2 class="font-semibold text-gray-800">Sell Shares — {{ sellTarget?.symbol }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ sellTarget?.company_name }} · {{ Number(sellTarget?.net_shares ?? 0).toLocaleString() }} shares available</p>
          </div>
          <button @click="showSellModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSell" class="p-5 space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Shares to Sell *</label>
              <input
                v-model="sellForm.shares_sold"
                type="number"
                min="0.0001"
                step="0.0001"
                :max="sellTarget?.net_shares"
                required
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                :placeholder="`max ${Number(sellTarget?.net_shares ?? 0).toLocaleString()}`"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sell Price *</label>
              <input v-model="sellForm.sell_price" type="number" min="0" step="0.0001" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500" />
            </div>
          </div>
          <!-- Proceeds preview -->
          <div v-if="sellForm.shares_sold && sellForm.sell_price" class="bg-orange-50 rounded-lg px-4 py-3 space-y-1.5 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Shares × Sell Price</span>
              <span class="text-gray-700">{{ Number(sellForm.shares_sold).toLocaleString() }} × {{ fmt(sellForm.sell_price) }}</span>
            </div>
            <div class="flex justify-between border-t pt-1.5">
              <span class="font-semibold text-gray-700">Proceeds</span>
              <span class="font-bold text-orange-600">{{ fmt(sellProceeds) }}</span>
            </div>
            <p class="text-[11px] text-gray-400">Proceeds will be added to your available stock balance.</p>
          </div>
          <div v-if="sellFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ sellFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showSellModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-orange-700">
              {{ saving ? 'Processing...' : 'Confirm Sell' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Dividend Modal ───────────────────────────────────────────────────── -->
    <div v-if="showDividendModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <div>
            <h2 class="font-semibold text-gray-800">Record Dividend — {{ dividendTarget?.symbol }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ dividendTarget?.company_name }}</p>
          </div>
          <button @click="showDividendModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleDividend" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Dividend Amount *</label>
            <input
              v-model="dividendForm.amount"
              type="number"
              min="0.01"
              step="0.01"
              required
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
              placeholder="e.g. 1500.00"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date Received</label>
            <input v-model="dividendForm.paid_at" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
            <input v-model="dividendForm.notes" type="text" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" placeholder="e.g. Q3 2025 cash dividend" />
          </div>
          <div v-if="dividendForm.amount" class="bg-amber-50 rounded-lg px-4 py-3 text-sm flex justify-between">
            <span class="text-gray-500">Amount to credit</span>
            <span class="font-bold text-amber-600">+{{ fmt(dividendForm.amount) }}</span>
          </div>
          <p class="text-xs text-gray-400">Dividend will be added to your available stock balance, ready to be transferred or used to buy more shares.</p>
          <div v-if="dividendFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ dividendFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showDividendModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-amber-600">
              {{ saving ? 'Saving...' : 'Record Dividend' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── History Modal ────────────────────────────────────────────────────── -->
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
                <td class="px-3 py-2 font-semibold text-gray-700" colspan="3">Total P&amp;L</td>
                <td class="px-3 py-2 text-right font-bold" :class="totalHistoryPnl >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ totalHistoryPnl >= 0 ? '+' : '' }}{{ fmt(totalHistoryPnl) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- ── Dividend History Modal ──────────────────────────────────────────── -->
    <div v-if="showDivHistoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b sticky top-0 bg-white">
          <div>
            <h2 class="font-semibold text-gray-800">Dividend History — {{ divHistoryTarget?.symbol }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ divHistoryTarget?.company_name }}</p>
          </div>
          <button @click="showDivHistoryModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5">
          <div v-if="divHistoryLoading" class="text-center py-8 text-gray-400">Loading...</div>
          <div v-else-if="!divHistoryItems.length" class="text-center py-8 text-gray-400">No dividends recorded yet.</div>
          <template v-else>
            <!-- Summary badge -->
            <div class="flex items-center gap-3 mb-4">
              <span class="bg-amber-50 text-amber-700 text-sm font-semibold px-3 py-1.5 rounded-lg">
                Total: {{ fmt(totalDivHistory) }}
              </span>
              <span class="text-xs text-gray-400">{{ divHistoryItems.length }} payment{{ divHistoryItems.length !== 1 ? 's' : '' }}</span>
            </div>
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="text-left px-3 py-2 text-gray-500 font-medium">Date Received</th>
                  <th class="text-right px-3 py-2 text-gray-500 font-medium">Amount</th>
                  <th class="text-left px-3 py-2 text-gray-500 font-medium">Notes</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="div in divHistoryItems" :key="div.id" class="border-t hover:bg-gray-50">
                  <td class="px-3 py-2 text-gray-600">{{ formatDate(div.paid_at) }}</td>
                  <td class="px-3 py-2 text-right font-semibold text-amber-600">{{ fmt(div.amount) }}</td>
                  <td class="px-3 py-2 text-gray-400 text-xs">{{ div.notes || '—' }}</td>
                </tr>
              </tbody>
              <tfoot class="border-t bg-amber-50">
                <tr>
                  <td class="px-3 py-2 font-semibold text-amber-700">Total</td>
                  <td class="px-3 py-2 text-right font-bold text-amber-700">{{ fmt(totalDivHistory) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </template>
        </div>
      </div>
    </div>

    <!-- ── Update Latest Price Modal ───────────────────────────────────────── -->
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

    <!-- ── Confirm Delete Dialog ────────────────────────────────────────────── -->
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
const stockForm      = ref({ symbol: '', company_name: '' });
const stockFormError = ref('');

// --- Buy (was Pay/Add Lot) Modal ---
const showBuyModal = ref(false);
const buyTarget    = ref(null);
const buyForm      = ref({ shares: '', buy_price: '' });
const buyFormError = ref('');

// --- Sell Modal ---
const showSellModal = ref(false);
const sellTarget    = ref(null);
const sellForm      = ref({ shares_sold: '', sell_price: '' });
const sellFormError = ref('');

const sellProceeds = computed(() => {
  const s = parseFloat(sellForm.value.shares_sold) || 0;
  const p = parseFloat(sellForm.value.sell_price)  || 0;
  return s * p;
});

// --- Dividend Modal ---
const showDividendModal = ref(false);
const dividendTarget    = ref(null);
const dividendForm      = ref({ amount: '', paid_at: new Date().toISOString().split('T')[0], notes: '' });
const dividendFormError = ref('');

// --- History Modal ---
const showHistoryModal = ref(false);
const historyTarget    = ref(null);
const historyLots      = ref([]);
const historyLoading   = ref(false);

// --- Update Price Modal ---
const showPriceModal = ref(false);
const priceTarget    = ref(null);
const priceForm      = ref({ latest_price: '' });
const priceFormError = ref('');

// --- Shared ---
const saving       = ref(false);
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

const valueBarWidth = computed(() => {
  const cost = portfolio.value?.total_cost_basis ?? 0;
  const val  = portfolio.value?.total_current_value ?? 0;
  if (cost <= 0) return 100;
  return Math.min(120, Math.round((val / cost) * 100));
});

function biBarWidth(pnl) {
  return Math.min(50, (Math.abs(pnl) / maxAbsPnl.value) * 50);
}

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

// --- Buy (Add Lot) ---
function openBuyModal(stock) {
  buyTarget.value = stock;
  buyForm.value   = { shares: '', buy_price: stock.latest_price ?? '' };
  buyFormError.value = '';
  showBuyModal.value = true;
}

async function handleBuyLot() {
  saving.value = true;
  buyFormError.value = '';
  try {
    await store.addLot(buyTarget.value.id, buyForm.value);
    showBuyModal.value = false;
  } catch (e) {
    buyFormError.value = e.response?.data?.message ?? 'Failed to add lot.';
  } finally {
    saving.value = false;
  }
}

// --- Sell ---
function openSellModal(stock) {
  sellTarget.value    = stock;
  sellForm.value      = { shares_sold: '', sell_price: stock.latest_price ?? '' };
  sellFormError.value = '';
  showSellModal.value = true;
}

async function handleSell() {
  saving.value = true;
  sellFormError.value = '';
  try {
    await store.sell(sellTarget.value.id, sellForm.value);
    showSellModal.value = false;
  } catch (e) {
    sellFormError.value = e.response?.data?.message ?? 'Failed to record sale.';
  } finally {
    saving.value = false;
  }
}

// --- Dividend ---
function openDividendModal(stock) {
  dividendTarget.value    = stock;
  dividendForm.value      = { amount: '', paid_at: new Date().toISOString().split('T')[0], notes: '' };
  dividendFormError.value = '';
  showDividendModal.value = true;
}

async function handleDividend() {
  saving.value = true;
  dividendFormError.value = '';
  try {
    await store.storeDividend(dividendTarget.value.id, dividendForm.value);
    showDividendModal.value = false;
  } catch (e) {
    dividendFormError.value = e.response?.data?.message ?? 'Failed to record dividend.';
  } finally {
    saving.value = false;
  }
}

// --- Dividend History ---
const showDivHistoryModal = ref(false);
const divHistoryTarget    = ref(null);
const divHistoryItems     = ref([]);
const divHistoryLoading   = ref(false);

const totalDivHistory = computed(() => divHistoryItems.value.reduce((sum, d) => sum + (parseFloat(d.amount) || 0), 0));

async function openDivHistoryModal(stock) {
  divHistoryTarget.value = stock;
  divHistoryItems.value  = [];
  showDivHistoryModal.value = true;
  divHistoryLoading.value   = true;
  try {
    divHistoryItems.value = await store.fetchDividends(stock.id);
  } finally {
    divHistoryLoading.value = false;
  }
}

// --- History ---
async function openHistoryModal(stock) {
  historyTarget.value = stock;
  historyLots.value   = [];
  showHistoryModal.value = true;
  historyLoading.value   = true;
  try {
    historyLots.value = await store.fetchLots(stock.id);
  } finally {
    historyLoading.value = false;
  }
}

// --- Update Price ---
function openUpdatePriceModal(sym) {
  priceTarget.value = sym;
  priceForm.value   = { latest_price: sym.latest_price ?? '' };
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
