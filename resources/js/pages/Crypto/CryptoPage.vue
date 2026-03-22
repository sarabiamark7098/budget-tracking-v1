<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Crypto</h1>
      <button @click="openAddCryptoModal()" class="bg-yellow-500 text-white px-3 py-2 sm:px-4 rounded-lg hover:bg-yellow-600 text-sm font-medium">
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
          <p class="text-[10px] text-gray-400 mt-1">{{ portfolio.count }} coin{{ portfolio.count !== 1 ? 's' : '' }}</p>
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
          <p class="text-[10px] text-gray-400 mt-1">In − Out − Cost + Sales</p>
        </div>
        <!-- Rewards -->
        <div class="bg-white rounded-xl shadow-sm p-4 lg:p-5 border-t-2 border-amber-300">
          <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Rewards</p>
          <p class="text-lg lg:text-xl font-bold text-amber-600">{{ fmtQty(portfolio.total_reward_qty ?? 0) }}</p>
          <p class="text-[10px] text-green-600 mt-1">+{{ fmt(portfolio.total_sale_proceeds ?? 0) }} sale proceeds</p>
        </div>
      </div>

      <!-- Funding flow strip -->
      <div class="bg-white rounded-xl shadow-sm px-5 py-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
        <div>
          <span class="text-xs text-gray-400 uppercase tracking-wide mr-1">Transferred In</span>
          <span class="font-semibold text-yellow-600">{{ fmt(portfolio.total_transferred ?? 0) }}</span>
        </div>
        <span class="text-gray-300 hidden sm:inline">→</span>
        <div>
          <span class="text-xs text-gray-400 mr-1">+Sales</span>
          <span class="font-semibold text-green-600">{{ fmt(portfolio.total_sale_proceeds ?? 0) }}</span>
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

      <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">P&amp;L by Coin</h3>
        <div class="space-y-3">
          <div v-for="(sym, i) in sortedBySymbol" :key="i" class="text-sm">
            <div class="flex items-center justify-between mb-1">
              <span class="font-bold text-gray-700 w-16 flex-shrink-0 uppercase">{{ sym.symbol }}</span>
              <span class="text-xs font-semibold flex-shrink-0" :class="sym.unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ sym.unrealized_pnl >= 0 ? '+' : '' }}{{ fmt(sym.unrealized_pnl) }}
                <span class="font-normal opacity-75">({{ sym.unrealized_pnl_pct >= 0 ? '+' : '' }}{{ sym.unrealized_pnl_pct.toFixed(2) }}%)</span>
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
      </div>
    </div>

    <!-- By-Coin Summary Table -->
    <div v-if="portfolio && portfolio.by_symbol?.length" class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h3 class="text-sm font-semibold text-gray-700">By Coin</h3>
        <div class="flex items-center gap-3">
          <span v-if="portfolio.total_reward_qty > 0" class="text-xs text-amber-600 font-medium bg-amber-50 px-2 py-0.5 rounded-full">
            Total Rewarded: {{ fmtQty(portfolio.total_reward_qty) }}
          </span>
          <span class="text-xs text-gray-400">{{ portfolio.by_symbol.length }} coins</span>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="w-1 p-0"></th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Coin</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Net Qty</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Avg Buy Price</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Latest Price</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Cost Basis</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Value</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">P&amp;L</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Rewards</th>
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
                  <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ background: PALETTE[i % PALETTE.length] }"></span>
                  <span class="font-bold text-yellow-600 uppercase">{{ sym.symbol }}</span>
                  <span class="text-gray-400 text-xs hidden sm:block">{{ sym.coin_name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right text-gray-700">
                <div class="font-medium">{{ fmtQty(sym.net_qty) }}</div>
                <div v-if="sym.reward_qty > 0" class="text-[10px] text-amber-500">+{{ fmtQty(sym.reward_qty) }} reward</div>
                <div v-if="sym.sold_qty > 0" class="text-[10px] text-orange-500">−{{ fmtQty(sym.sold_qty) }} sold</div>
              </td>
              <td class="px-4 py-3 text-right text-gray-500">{{ fmt(sym.avg_cost) }}</td>
              <td class="px-4 py-3 text-right text-gray-700 font-semibold">{{ fmt(sym.latest_price) }}</td>
              <td class="px-4 py-3 text-right text-gray-600">{{ fmt(sym.total_cost) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ fmt(sym.current_value) }}</td>
              <td class="px-4 py-3 text-right">
                <div class="font-semibold" :class="sym.unrealized_pnl >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ sym.unrealized_pnl >= 0 ? '+' : '' }}{{ fmt(sym.unrealized_pnl) }}
                </div>
                <div class="text-xs mt-0.5 font-medium px-1.5 py-0.5 rounded-full inline-block"
                  :class="sym.unrealized_pnl >= 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500'">
                  {{ sym.unrealized_pnl_pct >= 0 ? '+' : '' }}{{ sym.unrealized_pnl_pct.toFixed(2) }}%
                </div>
              </td>
              <td class="px-4 py-3 text-right">
                <template v-if="sym.reward_qty > 0">
                  <span class="font-semibold text-amber-600">{{ fmtQty(sym.reward_qty) }}</span>
                  <span class="block text-[10px] text-gray-400">{{ sym.symbol?.toUpperCase() }}</span>
                  <span v-if="sym.latest_price > 0" class="block text-[10px] text-gray-400">≈ {{ fmt(sym.reward_qty * sym.latest_price) }}</span>
                </template>
                <span v-else class="text-gray-300">—</span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <div class="w-12 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-yellow-400 rounded-full" :style="{ width: sym.alloc_pct + '%' }" />
                  </div>
                  <span class="text-xs text-gray-500 w-8 text-right">{{ sym.alloc_pct.toFixed(1) }}%</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right">
                <button @click="openUpdatePriceModal(sym)" class="text-yellow-600 hover:text-yellow-800 text-xs px-2 py-1 border rounded whitespace-nowrap">
                  Update Price
                </button>
              </td>
            </tr>
          </tbody>
          <tfoot v-if="portfolio.total_reward_qty > 0" class="border-t bg-amber-50">
            <tr>
              <td colspan="8" class="px-4 py-2 text-xs font-semibold text-amber-700">Total Rewards Received</td>
              <td class="px-4 py-2 text-right font-bold text-amber-700">{{ fmtQty(portfolio.total_reward_qty) }}</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Individual Holdings Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <h3 class="text-sm font-semibold text-gray-700">Individual Holdings</h3>
        <span class="text-xs text-gray-400">{{ store.pagination?.total ?? store.items.length }} coins</span>
      </div>
      <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Coin</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium hidden sm:table-cell">Symbol</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Latest Price</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium hidden sm:table-cell">Net Qty</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="5" class="text-center py-10 text-gray-400">No crypto holdings found. Add your first coin to get started.</td>
            </tr>
            <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-700">{{ item.coin_name }}</td>
              <td class="px-4 py-3 font-bold text-yellow-600 uppercase hidden sm:table-cell">{{ item.symbol }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ item.latest_price ? fmt(item.latest_price) : '—' }}</td>
              <td class="px-4 py-3 text-right text-gray-600 hidden sm:table-cell">
                {{ item.net_quantity > 0 ? fmtQty(item.net_quantity) : '—' }}
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-1.5 justify-end flex-wrap">
                  <!-- Buy -->
                  <button
                    @click="openBuyModal(item)"
                    class="text-green-600 hover:text-green-800 text-xs px-2 py-1 border border-green-300 rounded bg-green-50 hover:bg-green-100"
                  >Buy</button>
                  <!-- Sell — only when net_quantity > 0 -->
                  <button
                    v-if="item.net_quantity > 0"
                    @click="openSellModal(item)"
                    class="text-orange-600 hover:text-orange-800 text-xs px-2 py-1 border border-orange-300 rounded bg-orange-50 hover:bg-orange-100"
                  >Sell</button>
                  <!-- Staking Reward -->
                  <button
                    @click="openRewardModal(item)"
                    class="text-amber-600 hover:text-amber-800 text-xs px-2 py-1 border border-amber-300 rounded bg-amber-50 hover:bg-amber-100"
                  >Reward</button>
                  <!-- History -->
                  <button @click="openHistoryModal(item)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">History</button>
                  <!-- Reward History -->
                  <button @click="openRewardHistoryModal(item)" class="text-amber-500 hover:text-amber-700 text-xs px-2 py-1 border border-amber-300 rounded">Reward History</button>
                  <!-- Delete -->
                  <button @click="confirmDelete(item)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="store.pagination && store.pagination.last_page > 1" class="flex justify-between items-center px-5 py-3 border-t text-sm text-gray-500">
        <span>Page {{ store.pagination.current_page }} of {{ store.pagination.last_page }}</span>
        <div class="flex gap-2">
          <button :disabled="store.pagination.current_page <= 1" @click="changePage(store.pagination.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100">Prev</button>
          <button :disabled="store.pagination.current_page >= store.pagination.last_page" @click="changePage(store.pagination.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100">Next</button>
        </div>
      </div>
    </div>

    <!-- ── Add Crypto Modal ─────────────────────────────────────────────────── -->
    <div v-if="showCryptoModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">Add Crypto</h2>
          <button @click="showCryptoModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleAddCrypto" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Coin Name *</label>
            <input v-model="cryptoForm.coin_name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500" placeholder="e.g. Bitcoin, Ethereum" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Symbol *</label>
            <input v-model="cryptoForm.symbol" required class="w-full border rounded-lg px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-yellow-500" placeholder="e.g. BTC, ETH" />
          </div>
          <div v-if="cryptoFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ cryptoFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showCryptoModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-yellow-600">
              {{ saving ? 'Saving...' : 'Add Coin' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Buy Modal ────────────────────────────────────────────────────────── -->
    <!-- Cost Basis = (Purchase Price × Amount) + Buying Fees -->
    <div v-if="showBuyModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <div>
            <h2 class="font-semibold text-gray-800">Buy — {{ buyTarget?.symbol?.toUpperCase() }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ buyTarget?.coin_name }}</p>
          </div>
          <button @click="showBuyModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleBuyLot" class="p-5 space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Amount (Qty) *</label>
              <input v-model="buyForm.quantity" type="number" min="0.00000001" step="0.00000001" required
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500"
                placeholder="0.00000000" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Price (₱) *</label>
              <input v-model="buyForm.buy_price" type="number" min="0" step="0.01" required
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500"
                placeholder="0.00" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Buying Fees (₱) <span class="text-gray-400 font-normal">— taker/maker fee</span></label>
            <input v-model="buyForm.fee" type="number" min="0" step="0.01"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500"
              placeholder="0.00 (optional)" />
          </div>
          <!-- Cost Basis breakdown -->
          <div v-if="buyForm.quantity && buyForm.buy_price" class="bg-green-50 border border-green-100 rounded-lg px-4 py-3 space-y-1.5 text-sm">
            <div class="flex justify-between text-gray-600">
              <span>Purchase Price × Amount</span>
              <span>{{ fmt(Number(buyForm.buy_price) * Number(buyForm.quantity)) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>Buying Fees</span>
              <span>+ {{ fmt(buyForm.fee || 0) }}</span>
            </div>
            <div class="flex justify-between font-semibold text-green-700 border-t border-green-200 pt-1.5">
              <span>Total Cost Basis</span>
              <span>{{ fmt(buyCostBasis) }}</span>
            </div>
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

    <!-- ── Sell Modal ───────────────────────────────────────────────────────── -->
    <!-- Net Proceeds = (Selling Price × Amount) - Selling Fees -->
    <!-- Profit/Loss  = Net Proceeds - Cost Basis -->
    <div v-if="showSellModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <div>
            <h2 class="font-semibold text-gray-800">Sell — {{ sellTarget?.symbol?.toUpperCase() }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ sellTarget?.coin_name }} · {{ fmtQty(sellTarget?.net_quantity ?? 0) }} available</p>
          </div>
          <button @click="showSellModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSell" class="p-5 space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Amount to Sell *</label>
              <input
                v-model="sellForm.quantity_sold"
                type="number" min="0.00000001" step="0.00000001"
                :max="sellTarget?.net_quantity"
                required
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                :placeholder="`max ${fmtQty(sellTarget?.net_quantity ?? 0)}`"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Selling Price (₱) *</label>
              <input v-model="sellForm.sell_price" type="number" min="0" step="0.01" required
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                placeholder="0.00" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Selling Fees (₱) <span class="text-gray-400 font-normal">— exchange fee</span></label>
            <input v-model="sellForm.sell_fee" type="number" min="0" step="0.01"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
              placeholder="0.00 (optional)" />
          </div>
          <!-- Net Proceeds breakdown -->
          <div v-if="sellForm.quantity_sold && sellForm.sell_price" class="bg-orange-50 border border-orange-100 rounded-lg px-4 py-3 space-y-1.5 text-sm">
            <div class="flex justify-between text-gray-600">
              <span>Selling Price × Amount</span>
              <span>{{ fmt(Number(sellForm.sell_price) * Number(sellForm.quantity_sold)) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
              <span>Selling Fees</span>
              <span class="text-red-500">− {{ fmt(sellForm.sell_fee || 0) }}</span>
            </div>
            <div class="flex justify-between font-semibold text-orange-700 border-t border-orange-200 pt-1.5">
              <span>Net Proceeds</span>
              <span>{{ fmt(sellNetProceeds) }}</span>
            </div>
            <div v-if="sellCostBasisOfSold > 0" class="flex justify-between text-xs text-gray-500 border-t border-orange-100 pt-1">
              <span>Est. Cost Basis sold</span>
              <span>{{ fmt(sellCostBasisOfSold) }}</span>
            </div>
            <div v-if="sellCostBasisOfSold > 0" class="flex justify-between text-xs font-semibold" :class="sellEstProfit >= 0 ? 'text-green-600' : 'text-red-600'">
              <span>Est. Profit / Loss</span>
              <span>{{ sellEstProfit >= 0 ? '+' : '' }}{{ fmt(sellEstProfit) }}</span>
            </div>
            <p class="text-[11px] text-gray-400">Net proceeds credited to available balance.</p>
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

    <!-- ── Staking / Reward Modal (quantity-based) ──────────────────────────── -->
    <div v-if="showRewardModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b">
          <div>
            <h2 class="font-semibold text-gray-800">Staking Reward — {{ rewardTarget?.symbol?.toUpperCase() }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ rewardTarget?.coin_name }}</p>
          </div>
          <button @click="showRewardModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleReward" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity Received *</label>
            <input
              v-model="rewardForm.quantity_rewarded"
              type="number" min="0.00000001" step="0.00000001" required
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
              placeholder="e.g. 0.05000000"
            />
            <p class="text-xs text-gray-400 mt-1">Enter the exact amount of {{ rewardTarget?.symbol?.toUpperCase() }} you received.</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Price at Time of Reward (₱)</label>
            <input v-model="rewardForm.price_at_reward" type="number" min="0" step="0.01"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
              placeholder="Auto-filled from latest price" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date Received</label>
            <input v-model="rewardForm.paid_at" type="date"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
            <input v-model="rewardForm.notes" type="text"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
              placeholder="e.g. Staking reward, airdrop" />
          </div>
          <!-- Reward summary -->
          <div v-if="rewardForm.quantity_rewarded" class="bg-amber-50 border border-amber-100 rounded-lg px-4 py-3 space-y-1.5 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Quantity added</span>
              <span class="font-semibold text-amber-700">+{{ fmtQty(rewardForm.quantity_rewarded) }} {{ rewardTarget?.symbol?.toUpperCase() }}</span>
            </div>
            <div v-if="rewardForm.price_at_reward" class="flex justify-between text-xs text-gray-500">
              <span>Est. value at receipt</span>
              <span>{{ fmt(Number(rewardForm.quantity_rewarded) * Number(rewardForm.price_at_reward)) }}</span>
            </div>
            <p class="text-[11px] text-gray-400 border-t border-amber-100 pt-1.5">
              Reward quantity is added to your holdings. Sell it anytime to convert to cash.
            </p>
          </div>
          <div v-if="rewardFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ rewardFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showRewardModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-amber-600">
              {{ saving ? 'Saving...' : 'Record Reward' }}
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
            <h2 class="font-semibold text-gray-800">Lot History — {{ historyTarget?.symbol?.toUpperCase() }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ historyTarget?.coin_name }}</p>
          </div>
          <button @click="showHistoryModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5">
          <div v-if="historyLoading" class="text-center py-8 text-gray-400">Loading...</div>
          <div v-else-if="!historyLots.length" class="text-center py-8 text-gray-400">No lots recorded yet.</div>
          <table v-else class="w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left px-3 py-2 text-gray-500 font-medium">Date</th>
                <th class="text-right px-3 py-2 text-gray-500 font-medium">Quantity</th>
                <th class="text-right px-3 py-2 text-gray-500 font-medium">Buy Price</th>
                <th class="text-right px-3 py-2 text-gray-500 font-medium">Fee</th>
                <th class="text-right px-3 py-2 text-gray-500 font-medium">Cost Basis</th>
                <th class="text-right px-3 py-2 text-gray-500 font-medium">P&amp;L</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="lot in historyLots" :key="lot.id" class="border-t hover:bg-gray-50">
                <td class="px-3 py-2 text-gray-600">{{ formatDate(lot.purchase_date) }}</td>
                <td class="px-3 py-2 text-right text-gray-700">{{ fmtQty(lot.quantity) }}</td>
                <td class="px-3 py-2 text-right text-gray-500">{{ fmt(lot.buy_price) }}</td>
                <td class="px-3 py-2 text-right text-gray-400 text-xs">{{ fmt(lot.fee || 0) }}</td>
                <td class="px-3 py-2 text-right text-gray-600 font-medium">{{ fmt(lot.cost_basis) }}</td>
                <td class="px-3 py-2 text-right font-semibold" :class="lot.pnl >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ lot.pnl >= 0 ? '+' : '' }}{{ fmt(lot.pnl) }}
                  <span class="block text-xs font-normal opacity-75">({{ lot.pnl_pct >= 0 ? '+' : '' }}{{ lot.pnl_pct }}%)</span>
                </td>
              </tr>
            </tbody>
            <tfoot class="border-t bg-gray-50">
              <tr>
                <td class="px-3 py-2 font-semibold text-gray-700" colspan="4">Total Cost Basis</td>
                <td class="px-3 py-2 text-right font-bold text-gray-700">{{ fmt(totalHistoryCostBasis) }}</td>
                <td class="px-3 py-2 text-right font-bold" :class="totalHistoryPnl >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ totalHistoryPnl >= 0 ? '+' : '' }}{{ fmt(totalHistoryPnl) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- ── Reward History Modal ─────────────────────────────────────────────── -->
    <div v-if="showRewardHistoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b sticky top-0 bg-white">
          <div>
            <h2 class="font-semibold text-gray-800">Reward History — {{ rewardHistoryTarget?.symbol?.toUpperCase() }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ rewardHistoryTarget?.coin_name }}</p>
          </div>
          <button @click="showRewardHistoryModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5">
          <div v-if="rewardHistoryLoading" class="text-center py-8 text-gray-400">Loading...</div>
          <div v-else-if="!rewardHistoryItems.length" class="text-center py-8 text-gray-400">No rewards recorded yet.</div>
          <template v-else>
            <!-- Summary badge -->
            <div class="flex items-center gap-3 mb-4">
              <span class="bg-amber-50 text-amber-700 text-sm font-semibold px-3 py-1.5 rounded-lg">
                Total: {{ fmtQty(totalRewardHistoryQty) }} {{ rewardHistoryTarget?.symbol?.toUpperCase() }}
              </span>
              <span v-if="totalRewardHistoryValue > 0" class="bg-gray-50 text-gray-600 text-sm px-3 py-1.5 rounded-lg">
                ≈ {{ fmt(totalRewardHistoryValue) }}
              </span>
              <span class="text-xs text-gray-400">{{ rewardHistoryItems.length }} reward{{ rewardHistoryItems.length !== 1 ? 's' : '' }}</span>
            </div>
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="text-left px-3 py-2 text-gray-500 font-medium">Date Received</th>
                  <th class="text-right px-3 py-2 text-gray-500 font-medium">Qty Received</th>
                  <th class="text-right px-3 py-2 text-gray-500 font-medium">Price at Reward</th>
                  <th class="text-right px-3 py-2 text-gray-500 font-medium">Est. Value</th>
                  <th class="text-left px-3 py-2 text-gray-500 font-medium">Notes</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in rewardHistoryItems" :key="r.id" class="border-t hover:bg-gray-50">
                  <td class="px-3 py-2 text-gray-600">{{ formatDate(r.paid_at) }}</td>
                  <td class="px-3 py-2 text-right font-semibold text-amber-600">+{{ fmtQty(r.quantity_rewarded) }}</td>
                  <td class="px-3 py-2 text-right text-gray-500">{{ r.price_at_reward > 0 ? fmt(r.price_at_reward) : '—' }}</td>
                  <td class="px-3 py-2 text-right text-gray-600">{{ r.est_value > 0 ? fmt(r.est_value) : '—' }}</td>
                  <td class="px-3 py-2 text-gray-400 text-xs">{{ r.notes || '—' }}</td>
                </tr>
              </tbody>
              <tfoot class="border-t bg-amber-50">
                <tr>
                  <td class="px-3 py-2 font-semibold text-amber-700">Total</td>
                  <td class="px-3 py-2 text-right font-bold text-amber-700">{{ fmtQty(totalRewardHistoryQty) }}</td>
                  <td></td>
                  <td class="px-3 py-2 text-right font-bold text-amber-700">{{ totalRewardHistoryValue > 0 ? fmt(totalRewardHistoryValue) : '—' }}</td>
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
            <p class="text-xs text-gray-400 mt-0.5">{{ priceTarget?.symbol?.toUpperCase() }} — {{ priceTarget?.coin_name }}</p>
          </div>
          <button @click="showPriceModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleUpdatePrice" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Latest Price (₱) *</label>
            <input v-model="priceForm.latest_price" type="number" min="0" step="0.00000001" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500" />
          </div>
          <div v-if="priceFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ priceFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showPriceModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-yellow-600">
              {{ saving ? 'Saving...' : 'Update' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Confirm Delete ───────────────────────────────────────────────────── -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Crypto</h3>
        <p class="text-sm text-gray-500 mb-4">Delete <strong>{{ deleteTarget.coin_name }} ({{ deleteTarget.symbol?.toUpperCase() }})</strong> and all its lots? This cannot be undone.</p>
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

const PALETTE = ['#F59E0B','#EF4444','#3B82F6','#10B981','#8B5CF6','#EC4899','#6366F1','#14B8A6','#F97316','#84CC16'];

// --- Add Crypto ---
const showCryptoModal = ref(false);
const cryptoForm      = ref({ coin_name: '', symbol: '' });
const cryptoFormError = ref('');

// --- Buy (Add Lot) ---
const showBuyModal = ref(false);
const buyTarget    = ref(null);
const buyForm      = ref({ quantity: '', buy_price: '', fee: '' });
const buyFormError = ref('');

// Cost Basis = (qty × buy_price) + fee
const buyCostBasis = computed(() => {
  const q   = parseFloat(buyForm.value.quantity)  || 0;
  const p   = parseFloat(buyForm.value.buy_price) || 0;
  const fee = parseFloat(buyForm.value.fee)        || 0;
  return q * p + fee;
});

// --- Sell ---
const showSellModal = ref(false);
const sellTarget    = ref(null);
const sellForm      = ref({ quantity_sold: '', sell_price: '', sell_fee: '' });
const sellFormError = ref('');

// Net Proceeds = (qty × sell_price) - sell_fee
const sellNetProceeds = computed(() => {
  const q   = parseFloat(sellForm.value.quantity_sold) || 0;
  const p   = parseFloat(sellForm.value.sell_price)    || 0;
  const fee = parseFloat(sellForm.value.sell_fee)      || 0;
  return Math.max(0, q * p - fee);
});

// Estimated cost basis of sold shares (avg cost × qty)
const sellCostBasisOfSold = computed(() => {
  if (!sellTarget.value) return 0;
  const sym = store.portfolio?.by_symbol?.find(s => s.asset_id === sellTarget.value.id);
  if (!sym || sym.lots_qty_bought <= 0) return 0;
  const avgCost = sym.total_cost / sym.lots_qty_bought;
  return avgCost * (parseFloat(sellForm.value.quantity_sold) || 0);
});

const sellEstProfit = computed(() => sellNetProceeds.value - sellCostBasisOfSold.value);

// --- Reward ---
const showRewardModal = ref(false);
const rewardTarget    = ref(null);
const rewardForm      = ref({ quantity_rewarded: '', price_at_reward: '', paid_at: '', notes: '' });
const rewardFormError = ref('');

// --- Reward History ---
const showRewardHistoryModal = ref(false);
const rewardHistoryTarget    = ref(null);
const rewardHistoryItems     = ref([]);
const rewardHistoryLoading   = ref(false);

const totalRewardHistoryQty = computed(() =>
  rewardHistoryItems.value.reduce((sum, r) => sum + (parseFloat(r.quantity_rewarded) || 0), 0)
);
const totalRewardHistoryValue = computed(() =>
  rewardHistoryItems.value.reduce((sum, r) => sum + (parseFloat(r.est_value) || 0), 0)
);

// --- History ---
const showHistoryModal = ref(false);
const historyTarget    = ref(null);
const historyLots      = ref([]);
const historyLoading   = ref(false);

// --- Update Price ---
const showPriceModal = ref(false);
const priceTarget    = ref(null);
const priceForm      = ref({ latest_price: '' });
const priceFormError = ref('');

// --- Shared ---
const saving       = ref(false);
const deleteTarget = ref(null);

const portfolio = computed(() => store.portfolio);

const totalHistoryPnl = computed(() =>
  historyLots.value.reduce((sum, l) => sum + (l.pnl ?? 0), 0)
);
const totalHistoryCostBasis = computed(() =>
  historyLots.value.reduce((sum, l) => sum + (l.cost_basis ?? 0), 0)
);

const allocationSegments = computed(() =>
  (portfolio.value?.by_symbol ?? []).map((sym, i) => ({
    label: sym.symbol,
    value: sym.alloc_pct,
    pct:   sym.alloc_pct,
    color: PALETTE[i % PALETTE.length],
  }))
);

const sortedBySymbol = computed(() =>
  [...(portfolio.value?.by_symbol ?? [])].sort((a, b) => Math.abs(b.unrealized_pnl) - Math.abs(a.unrealized_pnl))
);

const maxAbsPnl = computed(() =>
  Math.max(...sortedBySymbol.value.map(s => Math.abs(s.unrealized_pnl)), 1)
);

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

function fmt(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function fmtQty(val) {
  return Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 4, maximumFractionDigits: 8 });
}

function formatDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function today() {
  return new Date().toISOString().split('T')[0];
}

// --- Add Crypto ---
function openAddCryptoModal() {
  cryptoForm.value = { coin_name: '', symbol: '' };
  cryptoFormError.value = '';
  showCryptoModal.value = true;
}

async function handleAddCrypto() {
  saving.value = true;
  cryptoFormError.value = '';
  try {
    await store.create({ ...cryptoForm.value, symbol: cryptoForm.value.symbol.toUpperCase() });
    showCryptoModal.value = false;
  } catch (e) {
    cryptoFormError.value = e.response?.data?.message ?? 'Failed to save. Please try again.';
  } finally {
    saving.value = false;
  }
}

// --- Buy ---
function openBuyModal(item) {
  buyTarget.value = item;
  buyForm.value   = { quantity: '', buy_price: item.latest_price ?? '', fee: '' };
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
function openSellModal(item) {
  sellTarget.value = item;
  sellForm.value   = { quantity_sold: '', sell_price: item.latest_price ?? '', sell_fee: '' };
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

// --- Reward ---
function openRewardModal(item) {
  rewardTarget.value = item;
  rewardForm.value   = {
    quantity_rewarded: '',
    price_at_reward:   item.latest_price ?? '',
    paid_at:           today(),
    notes:             '',
  };
  rewardFormError.value = '';
  showRewardModal.value = true;
}

async function handleReward() {
  saving.value = true;
  rewardFormError.value = '';
  try {
    await store.storeDividend(rewardTarget.value.id, rewardForm.value);
    showRewardModal.value = false;
  } catch (e) {
    rewardFormError.value = e.response?.data?.message ?? 'Failed to record reward.';
  } finally {
    saving.value = false;
  }
}

// --- Reward History ---
async function openRewardHistoryModal(item) {
  rewardHistoryTarget.value = item;
  rewardHistoryItems.value  = [];
  showRewardHistoryModal.value = true;
  rewardHistoryLoading.value   = true;
  try {
    rewardHistoryItems.value = await store.fetchDividends(item.id);
  } finally {
    rewardHistoryLoading.value = false;
  }
}

// --- History ---
async function openHistoryModal(item) {
  historyTarget.value    = item;
  historyLots.value      = [];
  showHistoryModal.value = true;
  historyLoading.value   = true;
  try {
    historyLots.value = await store.fetchLots(item.id);
  } finally {
    historyLoading.value = false;
  }
}

// --- Update Price ---
function openUpdatePriceModal(sym) {
  priceTarget.value    = sym;
  priceForm.value      = { latest_price: sym.latest_price ?? '' };
  priceFormError.value = '';
  showPriceModal.value = true;
}

async function handleUpdatePrice() {
  saving.value = true;
  priceFormError.value = '';
  try {
    await store.updatePrice(priceTarget.value.asset_id, priceForm.value.latest_price);
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
