<template>
  <div class="space-y-6">

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Budget Tracker</h1>
      <div v-if="store.tracker" class="flex gap-2">
        <button
          v-if="store.tracker.is_owner"
          @click="openEditModal"
          class="text-sm px-3 py-2 border rounded-lg text-gray-600 hover:bg-gray-50"
        >Edit</button>
        <button
          v-if="store.tracker.is_owner"
          @click="confirmArchiveTracker = true"
          class="text-sm px-3 py-2 border border-yellow-200 rounded-lg text-yellow-700 hover:bg-yellow-50"
        >Archive</button>
        <button
          v-if="store.tracker.is_owner"
          @click="confirmDeleteTracker = true"
          class="text-sm px-3 py-2 border border-red-200 rounded-lg text-red-600 hover:bg-red-50"
        >Delete</button>
        <button
          v-else
          @click="confirmLeave = true"
          class="text-sm px-3 py-2 border border-orange-200 rounded-lg text-orange-600 hover:bg-orange-50"
        >Leave Tracker</button>
      </div>
    </div>

    <!-- ── Loading ────────────────────────────────────────────────────────── -->
    <div v-if="store.loading" class="flex justify-center py-16 text-gray-400 gap-3">
      <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
      </svg>
      <span>Loading tracker…</span>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         NO TRACKER STATE — Create or Join
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-else-if="!store.tracker">
      <p class="text-gray-500 text-sm">You are not part of any budget tracker. Create your own or join one with a code.</p>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Create panel -->
        <div class="bg-white rounded-xl shadow-sm p-8 flex flex-col items-center text-center gap-4">
          <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
            <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </div>
          <div>
            <h2 class="font-semibold text-gray-800 text-lg">Create a Tracker</h2>
            <p class="text-sm text-gray-500 mt-1">Start a shared budget tracker and invite others using a unique code.</p>
          </div>
          <button
            @click="openCreateModal"
            class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 w-full"
          >Create Tracker</button>
        </div>

        <!-- Join panel -->
        <div class="bg-white rounded-xl shadow-sm p-8 flex flex-col items-center text-center gap-4">
          <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
          </div>
          <div>
            <h2 class="font-semibold text-gray-800 text-lg">Join with a Code</h2>
            <p class="text-sm text-gray-500 mt-1">Ask the tracker owner for their share code and enter it below.</p>
          </div>
          <div class="w-full space-y-3">
            <input
              v-model="joinCode"
              maxlength="8"
              placeholder="Enter 8-character code"
              class="w-full border rounded-lg px-3 py-2.5 text-sm text-center font-mono uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-green-500"
            />
            <div v-if="joinError" class="text-red-600 text-xs bg-red-50 rounded-lg px-3 py-2">{{ joinError }}</div>
            <button
              @click="handleJoin"
              :disabled="joining || joinCode.length < 8"
              class="bg-green-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 disabled:opacity-50 w-full"
            >{{ joining ? 'Joining…' : 'Join Tracker' }}</button>
          </div>
        </div>

      </div>
    </template>

    <!-- ══════════════════════════════════════════════════════════════════════
         HAS TRACKER STATE
    ══════════════════════════════════════════════════════════════════════ -->
    <template v-else>

      <!-- ── Tracker Info Card ─────────────────────────────────────────── -->
      <div class="bg-white rounded-xl shadow-sm p-5 space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h2 class="text-lg font-bold text-gray-800">{{ store.tracker.name }}</h2>
              <span class="text-xs px-2 py-0.5 rounded-full capitalize font-medium" :class="statusBadge(store.tracker.status)">
                {{ store.tracker.status }}
              </span>
              <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 capitalize">
                {{ store.tracker.period }}
              </span>
            </div>
            <p v-if="store.tracker.description" class="text-sm text-gray-500 mt-1">{{ store.tracker.description }}</p>
            <p class="text-xs text-gray-400 mt-1">
              Started {{ formatDate(store.tracker.start_date) }}
              · {{ store.tracker.members?.length ?? 0 }} member{{ store.tracker.members?.length !== 1 ? 's' : '' }}
            </p>
          </div>

          <!-- Role badge -->
          <span
            class="text-xs font-semibold px-3 py-1 rounded-full shrink-0"
            :class="store.tracker.is_owner ? 'bg-indigo-100 text-indigo-700' : 'bg-teal-100 text-teal-700'"
          >
            {{ store.tracker.is_owner ? 'Owner' : 'Member' }}
          </span>
        </div>

        <!-- Share Code -->
        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 flex flex-wrap items-center gap-3">
          <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-indigo-500 mb-0.5">Share Code</p>
            <p class="font-mono font-bold text-indigo-800 text-2xl tracking-[0.3em]">{{ store.tracker.join_code }}</p>
            <p class="text-xs text-indigo-400 mt-0.5">Share this code with others so they can join your tracker.</p>
          </div>
          <div class="flex gap-2 shrink-0">
            <button
              @click="copyCode"
              class="flex items-center gap-1.5 text-xs px-3 py-2 bg-white border border-indigo-200 rounded-lg text-indigo-700 hover:bg-indigo-50 font-medium"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              {{ codeCopied ? 'Copied!' : 'Copy' }}
            </button>
            <button
              v-if="store.tracker.is_owner"
              @click="handleRegenerateCode"
              :disabled="regenerating"
              class="flex items-center gap-1.5 text-xs px-3 py-2 bg-white border border-indigo-200 rounded-lg text-indigo-700 hover:bg-indigo-50 font-medium disabled:opacity-50"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              {{ regenerating ? 'Regenerating…' : 'Regenerate' }}
            </button>
          </div>
        </div>
      </div>

      <!-- ── Summary Cards ─────────────────────────────────────────────── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4">
          <p class="text-xs text-gray-500 mb-1">Total Allocated</p>
          <p class="text-xl font-bold text-blue-600">{{ fmt(store.tracker.total_allocated) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
          <p class="text-xs text-gray-500 mb-1">Total Income</p>
          <p class="text-xl font-bold text-green-600">{{ fmt(store.tracker.total_income) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
          <p class="text-xs text-gray-500 mb-1">Total Expense</p>
          <p class="text-xl font-bold text-red-600">{{ fmt(store.tracker.total_expense) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
          <p class="text-xs text-gray-500 mb-1">Balance</p>
          <p class="text-xl font-bold" :class="store.tracker.balance >= 0 ? 'text-indigo-600' : 'text-red-600'">
            {{ fmt(store.tracker.balance) }}
          </p>
        </div>
      </div>

      <!-- ── Tabs ──────────────────────────────────────────────────────── -->
      <div class="flex gap-1 bg-gray-100 rounded-lg p-1 w-fit flex-wrap">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="activeTab = tab.value"
          class="px-4 py-2 rounded-md text-sm font-medium transition-colors"
          :class="activeTab === tab.value ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
        >{{ tab.label }}</button>
      </div>

      <!-- ────────────────────────────────────────────────────────────────
           TAB: ALLOCATIONS
      ──────────────────────────────────────────────────────────────────── -->
      <div v-if="activeTab === 'allocations'" class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-gray-700">Allocations</h2>
          <button
            v-if="store.tracker.is_owner"
            @click="openAllocModal()"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700"
          >+ Add Allocation</button>
        </div>

        <div v-if="!store.tracker.allocations?.length" class="bg-white rounded-xl shadow-sm py-12 text-center text-gray-400 text-sm">
          No allocations yet{{ store.tracker.is_owner ? '. Add one to start tracking spending.' : '.' }}
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="alloc in store.tracker.allocations"
            :key="alloc.id"
            class="bg-white rounded-xl shadow-sm p-5"
          >
            <div class="flex items-start justify-between gap-2 mb-3">
              <div class="flex items-center gap-2">
                <span
                  class="w-3 h-3 rounded-full shrink-0"
                  :style="{ backgroundColor: alloc.color || '#6366F1' }"
                ></span>
                <span class="font-semibold text-gray-800">{{ alloc.name }}</span>
                <span v-if="alloc.category" class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ alloc.category?.name }}</span>
              </div>
              <div v-if="store.tracker.is_owner" class="flex gap-1 shrink-0">
                <button @click="openAllocModal(alloc)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">Edit</button>
                <button @click="confirmDeleteAlloc = alloc" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
              </div>
            </div>

            <!-- Progress bar -->
            <div class="w-full bg-gray-100 rounded-full h-2 mb-2">
              <div
                class="h-2 rounded-full transition-all"
                :class="alloc.usage_percentage >= 100 ? 'bg-red-500' : alloc.usage_percentage >= 80 ? 'bg-yellow-400' : 'bg-green-500'"
                :style="{ width: Math.min(100, alloc.usage_percentage ?? 0) + '%' }"
              ></div>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center text-xs mt-2">
              <div>
                <p class="text-gray-400">Allocated</p>
                <p class="font-semibold text-gray-700">{{ fmt(alloc.allocated_amount) }}</p>
              </div>
              <div>
                <p class="text-gray-400">Spent</p>
                <p class="font-semibold text-red-600">{{ fmt(alloc.spent_amount) }}</p>
              </div>
              <div>
                <p class="text-gray-400">Remaining</p>
                <p class="font-semibold" :class="(alloc.remaining_amount ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'">
                  {{ fmt(alloc.remaining_amount) }}
                </p>
              </div>
            </div>
            <p class="text-right text-xs text-gray-400 mt-1">{{ (alloc.usage_percentage ?? 0).toFixed(1) }}% used</p>
          </div>
        </div>
      </div>

      <!-- ────────────────────────────────────────────────────────────────
           TAB: TRANSACTIONS
      ──────────────────────────────────────────────────────────────────── -->
      <div v-if="activeTab === 'transactions'" class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="font-semibold text-gray-700">Transactions</h2>
          <div class="flex gap-2 flex-wrap">
            <!-- Filters -->
            <select v-model="txFilter.type" @change="loadTransactions" class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">All Types</option>
              <option value="income">Income</option>
              <option value="expense">Expense</option>
            </select>
            <button
              @click="openTxModal()"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700"
            >+ Add Transaction</button>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div v-if="store.txLoading" class="text-center py-10 text-gray-400">Loading…</div>
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="text-left px-4 py-3 text-gray-500 font-medium">Title</th>
                  <th class="text-left px-4 py-3 text-gray-500 font-medium">Type</th>
                  <th class="text-left px-4 py-3 text-gray-500 font-medium">Allocation</th>
                  <th class="text-right px-4 py-3 text-gray-500 font-medium">Amount</th>
                  <th class="text-left px-4 py-3 text-gray-500 font-medium">Date</th>
                  <th class="text-left px-4 py-3 text-gray-500 font-medium">Added by</th>
                  <th class="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!store.transactions.length">
                  <td colspan="7" class="text-center py-10 text-gray-400">No transactions yet</td>
                </tr>
                <tr
                  v-for="tx in store.transactions"
                  :key="tx.id"
                  class="border-b last:border-0 hover:bg-gray-50"
                >
                  <td class="px-4 py-3 font-medium text-gray-700">
                    {{ tx.title }}
                    <div v-if="tx.description" class="text-xs text-gray-400">{{ tx.description }}</div>
                  </td>
                  <td class="px-4 py-3">
                    <span
                      class="text-xs px-2 py-1 rounded-full font-medium"
                      :class="tx.type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                    >{{ tx.type }}</span>
                  </td>
                  <td class="px-4 py-3 text-gray-500 text-xs">{{ tx.allocation?.name ?? '—' }}</td>
                  <td class="px-4 py-3 text-right font-semibold" :class="tx.type === 'income' ? 'text-green-600' : 'text-red-600'">
                    {{ tx.type === 'income' ? '+' : '-' }}{{ fmt(tx.amount) }}
                  </td>
                  <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(tx.date) }}</td>
                  <td class="px-4 py-3 text-gray-500 text-xs">{{ tx.added_by?.name ?? '—' }}</td>
                  <td class="px-4 py-3">
                    <div v-if="canEditTx(tx)" class="flex gap-1 justify-end">
                      <button @click="openTxModal(tx)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">Edit</button>
                      <button @click="confirmDeleteTx = tx" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="store.txPagination" class="flex justify-between items-center text-sm text-gray-500">
          <span>{{ store.txPagination.total }} transactions</span>
          <div class="flex gap-2">
            <button
              :disabled="store.txPagination.current_page <= 1"
              @click="changeTxPage(store.txPagination.current_page - 1)"
              class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100"
            >Prev</button>
            <span class="px-3 py-1">{{ store.txPagination.current_page }} / {{ store.txPagination.last_page }}</span>
            <button
              :disabled="store.txPagination.current_page >= store.txPagination.last_page"
              @click="changeTxPage(store.txPagination.current_page + 1)"
              class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100"
            >Next</button>
          </div>
        </div>
      </div>

      <!-- ────────────────────────────────────────────────────────────────
           TAB: MEMBERS
      ──────────────────────────────────────────────────────────────────── -->
      <div v-if="activeTab === 'members'" class="space-y-4">
        <h2 class="font-semibold text-gray-700">Members ({{ store.tracker.members?.length ?? 0 }})</h2>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Name</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Email</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Role</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Joined</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!store.tracker.members?.length">
                <td colspan="5" class="text-center py-10 text-gray-400">No members yet</td>
              </tr>
              <tr
                v-for="member in store.tracker.members"
                :key="member.id"
                class="border-b last:border-0 hover:bg-gray-50"
              >
                <td class="px-4 py-3 font-medium text-gray-700">{{ member.user?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500">{{ member.user?.email ?? '—' }}</td>
                <td class="px-4 py-3">
                  <span
                    class="text-xs px-2 py-1 rounded-full font-medium"
                    :class="member.role === 'owner' ? 'bg-indigo-100 text-indigo-700' : 'bg-teal-100 text-teal-700'"
                  >{{ member.role }}</span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(member.joined_at) }}</td>
                <td class="px-4 py-3 text-right">
                  <button
                    v-if="store.tracker.is_owner && member.role !== 'owner'"
                    @click="confirmRemoveMember = member"
                    class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded"
                  >Remove</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ────────────────────────────────────────────────────────────────
           TAB: MEMBERS DATA (CONSOLIDATED)
      ──────────────────────────────────────────────────────────────────── -->
      <div v-if="activeTab === 'consolidated'" class="space-y-4">

        <!-- Header row with refresh -->
        <div class="flex items-center justify-between">
          <h2 class="font-semibold text-gray-700">Members Financial Data</h2>
          <button
            @click="store.fetchConsolidated()"
            :disabled="store.consolidatedLoading"
            class="flex items-center gap-1.5 text-xs px-3 py-2 border rounded-lg text-gray-600 hover:bg-gray-50 disabled:opacity-50"
          >
            <svg class="w-3.5 h-3.5" :class="store.consolidatedLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ store.consolidatedLoading ? 'Loading…' : 'Refresh' }}
          </button>
        </div>

        <!-- Loading skeleton -->
        <div v-if="store.consolidatedLoading && !store.consolidated" class="bg-white rounded-xl shadow-sm py-12 text-center text-gray-400 text-sm">
          Loading consolidated data…
        </div>

        <template v-else-if="store.consolidated">

          <!-- Sub-tabs -->
          <div class="flex gap-1 bg-gray-100 rounded-lg p-1 overflow-x-auto flex-nowrap w-full">
            <button
              v-for="st in consolidatedSubTabs"
              :key="st.value"
              @click="consolidatedSubTab = st.value"
              class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors whitespace-nowrap flex-shrink-0"
              :class="consolidatedSubTab === st.value ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
            >{{ st.label }}</button>
          </div>

          <!-- ── OVERVIEW sub-tab ────────────────────────────────────────── -->
          <div v-if="consolidatedSubTab === 'overview'" class="space-y-4">
            <div
              v-for="(member, mi) in store.consolidated.member_summary"
              :key="member.user_id"
              class="bg-white rounded-xl shadow-sm p-5"
            >
              <!-- Member name header -->
              <div class="flex items-center gap-3 mb-4 pb-3 border-b">
                <span
                  class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                  :style="{ background: memberColor(mi) }"
                >{{ member.name.charAt(0).toUpperCase() }}</span>
                <div>
                  <p class="font-semibold text-gray-800">{{ member.name }}</p>
                  <span
                    class="text-xs px-2 py-0.5 rounded-full font-medium"
                    :class="member.role === 'owner' ? 'bg-indigo-100 text-indigo-700' : 'bg-teal-100 text-teal-700'"
                  >{{ member.role }}</span>
                </div>
              </div>
              <!-- 7-card stats grid -->
              <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 text-center">
                <div class="bg-green-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500">Total Income</p>
                  <p class="font-bold text-green-700 mt-0.5">{{ fmt(member.total_income) }}</p>
                </div>
                <div class="bg-red-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500">Total Expenses</p>
                  <p class="font-bold text-red-700 mt-0.5">{{ fmt(member.total_expenses) }}</p>
                </div>
                <div class="bg-orange-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500">Total Debt</p>
                  <p class="font-bold text-orange-700 mt-0.5">{{ fmt(member.total_debt) }}</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500">Invested</p>
                  <p class="font-bold text-blue-700 mt-0.5">{{ fmt(member.total_invested) }}</p>
                </div>
                <div class="bg-indigo-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500">Invest. Value</p>
                  <p class="font-bold text-indigo-700 mt-0.5">{{ fmt(member.total_invest_val) }}</p>
                </div>
                <div class="bg-violet-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500">Stocks Value</p>
                  <p class="font-bold text-violet-700 mt-0.5">{{ fmt(member.total_stocks_val) }}</p>
                </div>
                <div class="bg-yellow-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500">Crypto Value</p>
                  <p class="font-bold text-yellow-700 mt-0.5">{{ fmt(member.total_crypto_val) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                  <p class="text-xs text-gray-500">Net (Inc−Exp)</p>
                  <p class="font-bold mt-0.5" :class="(member.total_income - member.total_expenses) >= 0 ? 'text-green-700' : 'text-red-700'">
                    {{ fmt(member.total_income - member.total_expenses) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- ── INCOME sub-tab ──────────────────────────────────────────── -->
          <div v-if="consolidatedSubTab === 'income'" class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b flex items-center justify-between">
              <span class="text-sm font-semibold text-gray-700">Income Records</span>
              <span class="text-xs text-gray-400">{{ store.consolidated.income.length }} records</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Member</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Title</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Source</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Amount</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!store.consolidated.income.length">
                    <td colspan="5" class="text-center py-8 text-gray-400">No income records</td>
                  </tr>
                  <tr v-for="(row, i) in store.consolidated.income" :key="i" class="border-b last:border-0 hover:bg-gray-50">
                    <td class="px-4 py-3">
                      <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: consolidatedMemberColors[row.user_name] }"></span>
                        <span class="text-xs font-medium text-gray-700">{{ row.user_name }}</span>
                      </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ row.title ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ row.source ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-green-600">+{{ fmt(row.amount) }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(row.received_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ── EXPENSES sub-tab ────────────────────────────────────────── -->
          <div v-if="consolidatedSubTab === 'expenses'" class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b flex items-center justify-between">
              <span class="text-sm font-semibold text-gray-700">Expense Records</span>
              <span class="text-xs text-gray-400">{{ store.consolidated.expenses.length }} records</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Member</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Title</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Amount</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!store.consolidated.expenses.length">
                    <td colspan="4" class="text-center py-8 text-gray-400">No expense records</td>
                  </tr>
                  <tr v-for="(row, i) in store.consolidated.expenses" :key="i" class="border-b last:border-0 hover:bg-gray-50">
                    <td class="px-4 py-3">
                      <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: consolidatedMemberColors[row.user_name] }"></span>
                        <span class="text-xs font-medium text-gray-700">{{ row.user_name }}</span>
                      </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ row.title ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-red-600">-{{ fmt(row.amount) }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(row.spent_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ── DEBTS sub-tab ───────────────────────────────────────────── -->
          <div v-if="consolidatedSubTab === 'debts'" class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b flex items-center justify-between">
              <span class="text-sm font-semibold text-gray-700">Debt Records</span>
              <span class="text-xs text-gray-400">{{ store.consolidated.debts.length }} records</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Member</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Lender</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Original</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Remaining</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Status</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Due</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!store.consolidated.debts.length">
                    <td colspan="6" class="text-center py-8 text-gray-400">No debt records</td>
                  </tr>
                  <tr v-for="(row, i) in store.consolidated.debts" :key="i" class="border-b last:border-0 hover:bg-gray-50">
                    <td class="px-4 py-3">
                      <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: consolidatedMemberColors[row.user_name] }"></span>
                        <span class="text-xs font-medium text-gray-700">{{ row.user_name }}</span>
                      </span>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-700">{{ row.lender_name }}</td>
                    <td class="px-4 py-3 text-right text-gray-500">{{ fmt(row.amount) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-orange-600">{{ fmt(row.remaining_balance) }}</td>
                    <td class="px-4 py-3">
                      <span class="text-xs px-2 py-0.5 rounded-full font-medium capitalize"
                        :class="row.status === 'paid' ? 'bg-green-100 text-green-700' : row.status === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'"
                      >{{ row.status }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(row.due_date) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ── INVESTMENTS sub-tab ────────────────────────────────────── -->
          <div v-if="consolidatedSubTab === 'investments'" class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b flex items-center justify-between">
              <span class="text-sm font-semibold text-gray-700">Investments</span>
              <span class="text-xs text-gray-400">{{ store.consolidated.investments.length }} records</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Member</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Name</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Type</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Invested</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Value</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">ROI</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!store.consolidated.investments.length">
                    <td colspan="6" class="text-center py-8 text-gray-400">No investment records</td>
                  </tr>
                  <tr v-for="(row, i) in store.consolidated.investments" :key="i" class="border-b last:border-0 hover:bg-gray-50">
                    <td class="px-4 py-3">
                      <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: consolidatedMemberColors[row.user_name] }"></span>
                        <span class="text-xs font-medium text-gray-700">{{ row.user_name }}</span>
                      </span>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-700">{{ row.name }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs capitalize">{{ row.type ?? '—' }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">{{ fmt(row.amount_invested) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ fmt(row.current_value) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-xs" :class="row.roi >= 0 ? 'text-green-600' : 'text-red-600'">
                      {{ row.roi >= 0 ? '+' : '' }}{{ Number(row.roi || 0).toFixed(2) }}%
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ── STOCKS sub-tab ─────────────────────────────────────────── -->
          <div v-if="consolidatedSubTab === 'stocks'" class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b flex items-center justify-between">
              <span class="text-sm font-semibold text-gray-700">Stock Holdings</span>
              <span class="text-xs text-gray-400">{{ store.consolidated.stocks.length }} lots</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Member</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Symbol</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Company</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Shares</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Buy</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Current</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">P&amp;L</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!store.consolidated.stocks.length">
                    <td colspan="7" class="text-center py-8 text-gray-400">No stock records</td>
                  </tr>
                  <tr v-for="(row, i) in store.consolidated.stocks" :key="i" class="border-b last:border-0 hover:bg-gray-50">
                    <td class="px-4 py-3">
                      <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: consolidatedMemberColors[row.user_name] }"></span>
                        <span class="text-xs font-medium text-gray-700">{{ row.user_name }}</span>
                      </span>
                    </td>
                    <td class="px-4 py-3 font-bold text-indigo-600">{{ row.symbol }}</td>
                    <td class="px-4 py-3 text-gray-600 text-xs">{{ row.company_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">{{ Number(row.shares).toLocaleString() }}</td>
                    <td class="px-4 py-3 text-right text-gray-500">{{ fmt(row.buy_price) }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">{{ fmt(row.current_price) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-xs" :class="(row.current_price - row.buy_price) * row.shares >= 0 ? 'text-green-600' : 'text-red-600'">
                      {{ fmt((Number(row.current_price) - Number(row.buy_price)) * Number(row.shares)) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ── CRYPTO sub-tab ─────────────────────────────────────────── -->
          <div v-if="consolidatedSubTab === 'crypto'" class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b flex items-center justify-between">
              <span class="text-sm font-semibold text-gray-700">Crypto Holdings</span>
              <span class="text-xs text-gray-400">{{ store.consolidated.crypto.length }} records</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Member</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Coin</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Qty</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Buy</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Current</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">P&amp;L</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!store.consolidated.crypto.length">
                    <td colspan="6" class="text-center py-8 text-gray-400">No crypto records</td>
                  </tr>
                  <tr v-for="(row, i) in store.consolidated.crypto" :key="i" class="border-b last:border-0 hover:bg-gray-50">
                    <td class="px-4 py-3">
                      <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: consolidatedMemberColors[row.user_name] }"></span>
                        <span class="text-xs font-medium text-gray-700">{{ row.user_name }}</span>
                      </span>
                    </td>
                    <td class="px-4 py-3">
                      <span class="font-bold text-yellow-600 uppercase">{{ row.symbol }}</span>
                      <span class="text-xs text-gray-400 ml-1">{{ row.coin_name }}</span>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-700">{{ Number(row.quantity).toLocaleString('en-PH', { minimumFractionDigits: 4 }) }}</td>
                    <td class="px-4 py-3 text-right text-gray-500">{{ fmt(row.buy_price) }}</td>
                    <td class="px-4 py-3 text-right text-gray-700">{{ fmt(row.current_price) }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-xs" :class="(row.current_price - row.buy_price) * row.quantity >= 0 ? 'text-green-600' : 'text-red-600'">
                      {{ fmt((Number(row.current_price) - Number(row.buy_price)) * Number(row.quantity)) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ── PAYMENTS sub-tab ───────────────────────────────────────── -->
          <div v-if="consolidatedSubTab === 'payments'" class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b flex items-center justify-between">
              <span class="text-sm font-semibold text-gray-700">Debt Payments</span>
              <span class="text-xs text-gray-400">{{ store.consolidated.payments.length }} records</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Member</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Debt / Lender</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Amount</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Date</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Note</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!store.consolidated.payments.length">
                    <td colspan="5" class="text-center py-8 text-gray-400">No payment records</td>
                  </tr>
                  <tr v-for="(row, i) in store.consolidated.payments" :key="i" class="border-b last:border-0 hover:bg-gray-50">
                    <td class="px-4 py-3">
                      <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: consolidatedMemberColors[row.user_name] }"></span>
                        <span class="text-xs font-medium text-gray-700">{{ row.user_name }}</span>
                      </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ row.debt?.lender_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ fmt(row.amount) }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(row.payment_date) }}</td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ row.notes ?? '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ── PURCHASES sub-tab ──────────────────────────────────────── -->
          <div v-if="consolidatedSubTab === 'purchases'" class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b flex items-center justify-between">
              <span class="text-sm font-semibold text-gray-700">Purchases</span>
              <span class="text-xs text-gray-400">{{ store.consolidated.purchases.length }} records</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Member</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Item</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Total Cost</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Type</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Installment</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!store.consolidated.purchases.length">
                    <td colspan="6" class="text-center py-8 text-gray-400">No purchase records</td>
                  </tr>
                  <tr v-for="(row, i) in store.consolidated.purchases" :key="i" class="border-b last:border-0 hover:bg-gray-50">
                    <td class="px-4 py-3">
                      <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: consolidatedMemberColors[row.user_name] }"></span>
                        <span class="text-xs font-medium text-gray-700">{{ row.user_name }}</span>
                      </span>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-700">{{ row.item_name }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ fmt(row.total_cost) }}</td>
                    <td class="px-4 py-3">
                      <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                        :class="row.is_installment ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'"
                      >{{ row.is_installment ? 'Installment' : 'Lump Sum' }}</span>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-500 text-xs">
                      <template v-if="row.is_installment">
                        {{ fmt(row.installment_amount) }}/mo
                        · {{ row.installments_paid }}/{{ row.installment_count }} paid
                      </template>
                      <template v-else>—</template>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ formatDate(row.purchase_date) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </template><!-- end consolidated loaded -->
      </div>

    </template><!-- end has-tracker -->

    <!-- ══════════════════════════════════════════════════════════════════════
         MODALS
    ══════════════════════════════════════════════════════════════════════ -->

    <!-- Create / Edit Tracker Modal -->
    <div v-if="showTrackerModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editingTracker ? 'Edit Tracker' : 'Create Tracker' }}</h2>
          <button @click="showTrackerModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleTrackerSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tracker Name *</label>
            <input v-model="trackerForm.name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Family Budget 2026" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="trackerForm.description" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Optional description"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Period *</label>
            <select v-model="trackerForm.period" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
              <option value="yearly">Yearly</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input v-model="trackerForm.start_date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div v-if="editingTracker">
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select v-model="trackerForm.status" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="active">Active</option>
              <option value="archived">Archived</option>
            </select>
          </div>
          <div v-if="trackerFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ trackerFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showTrackerModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="savingTracker" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-blue-700">
              {{ savingTracker ? 'Saving…' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Allocation Modal (owner only) -->
    <div v-if="showAllocModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editingAlloc ? 'Edit Allocation' : 'Add Allocation' }}</h2>
          <button @click="showAllocModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleAllocSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
            <input v-model="allocForm.name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Groceries, Rent" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Allocated Amount *</label>
            <input v-model="allocForm.allocated_amount" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
            <input v-model="allocForm.color" type="color" class="h-9 w-full border rounded-lg px-1 py-1 text-sm cursor-pointer" />
          </div>
          <div v-if="allocFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ allocFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showAllocModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="savingAlloc" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-blue-700">
              {{ savingAlloc ? 'Saving…' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Transaction Modal -->
    <div v-if="showTxModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editingTx ? 'Edit Transaction' : 'Add Transaction' }}</h2>
          <button @click="showTxModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleTxSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
            <select v-model="txForm.type" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="income">Income</option>
              <option value="expense">Expense</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input v-model="txForm.title" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Grocery run, Salary" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
            <input v-model="txForm.amount" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
            <input v-model="txForm.date" type="date" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div v-if="store.tracker.allocations?.length">
            <label class="block text-sm font-medium text-gray-700 mb-1">Allocation</label>
            <select v-model="txForm.budget_tracking_allocation_id" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">— None —</option>
              <option v-for="a in store.tracker.allocations" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="txForm.description" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
          </div>
          <div v-if="txFormError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ txFormError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showTxModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="savingTx" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-blue-700">
              {{ savingTx ? 'Saving…' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm: Delete Tracker -->
    <div v-if="confirmDeleteTracker" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Tracker</h3>
        <p class="text-sm text-gray-500 mb-4">This will permanently delete the tracker and all its data for everyone. This cannot be undone.</p>
        <div class="flex justify-end gap-3">
          <button @click="confirmDeleteTracker = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handleDeleteTracker" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>

    <!-- Confirm: Leave Tracker -->
    <div v-if="confirmLeave" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Leave Tracker</h3>
        <p class="text-sm text-gray-500 mb-4">You will lose access to this tracker. The owner and other members won't be affected.</p>
        <div class="flex justify-end gap-3">
          <button @click="confirmLeave = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handleLeave" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700">Leave</button>
        </div>
      </div>
    </div>

    <!-- Confirm: Archive Tracker -->
    <div v-if="confirmArchiveTracker" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Archive Tracker</h3>
        <p class="text-sm text-gray-500 mb-4">All data will be preserved but all members (including you) will be removed. Everyone will be able to create or join a new tracker.</p>
        <div class="flex justify-end gap-3">
          <button @click="confirmArchiveTracker = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handleArchiveTracker" class="bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-yellow-700">Archive</button>
        </div>
      </div>
    </div>

    <!-- Confirm: Delete Allocation -->
    <div v-if="confirmDeleteAlloc" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Allocation</h3>
        <p class="text-sm text-gray-500 mb-4">Delete "{{ confirmDeleteAlloc.name }}"? Existing transactions linked to it will be unlinked.</p>
        <div class="flex justify-end gap-3">
          <button @click="confirmDeleteAlloc = null" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handleDeleteAlloc" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>

    <!-- Confirm: Delete Transaction -->
    <div v-if="confirmDeleteTx" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Transaction</h3>
        <p class="text-sm text-gray-500 mb-4">Delete "{{ confirmDeleteTx.title }}"? This cannot be undone.</p>
        <div class="flex justify-end gap-3">
          <button @click="confirmDeleteTx = null" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handleDeleteTx" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>

    <!-- Confirm: Remove Member -->
    <div v-if="confirmRemoveMember" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Remove Member</h3>
        <p class="text-sm text-gray-500 mb-4">Remove {{ confirmRemoveMember.user?.name ?? 'this member' }} from the tracker?</p>
        <div class="flex justify-end gap-3">
          <button @click="confirmRemoveMember = null" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handleRemoveMember" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">Remove</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useBudgetTrackingStore } from '@/stores/budgetTracking';
import { useAuthStore } from '@/stores/auth';
import { formatDate } from '@/utils/date';

const store    = useBudgetTrackingStore();
const authStore = useAuthStore();

// ── Tabs ──────────────────────────────────────────────────────────────────
const activeTab = ref('allocations');
const tabs = [
  { value: 'allocations',  label: 'Allocations' },
  { value: 'transactions', label: 'Transactions' },
  { value: 'members',      label: 'Members' },
  { value: 'consolidated', label: 'Members Data' },
];

// ── Helpers ───────────────────────────────────────────────────────────────
function fmt(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function statusBadge(s) {
  return s === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500';
}

function canEditTx(tx) {
  if (store.tracker?.is_owner) return true;
  return tx.added_by?.id === authStore.user?.id;
}

// ── Join (no-tracker state) ───────────────────────────────────────────────
const joinCode  = ref('');
const joining   = ref(false);
const joinError = ref('');

async function handleJoin() {
  joining.value = true;
  joinError.value = '';
  try {
    await store.join(joinCode.value.toUpperCase());
    joinCode.value = '';
    await loadTransactions();
  } catch (e) {
    joinError.value = e.response?.data?.message ?? 'Invalid or expired code.';
  } finally {
    joining.value = false;
  }
}

// ── Share code ────────────────────────────────────────────────────────────
const codeCopied  = ref(false);
const regenerating = ref(false);

function copyCode() {
  navigator.clipboard.writeText(store.tracker?.join_code ?? '');
  codeCopied.value = true;
  setTimeout(() => { codeCopied.value = false; }, 2000);
}

async function handleRegenerateCode() {
  regenerating.value = true;
  try {
    await store.regenerateCode();
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to regenerate code.');
  } finally {
    regenerating.value = false;
  }
}

// ── Create / Edit Tracker ─────────────────────────────────────────────────
const showTrackerModal  = ref(false);
const editingTracker    = ref(false);
const savingTracker     = ref(false);
const trackerFormError  = ref('');

const defaultTrackerForm = () => ({
  name:        '',
  description: '',
  period:      'monthly',
  start_date:  new Date().toISOString().split('T')[0],
  status:      'active',
});

const trackerForm = ref(defaultTrackerForm());

function openCreateModal() {
  editingTracker.value  = false;
  trackerForm.value     = defaultTrackerForm();
  trackerFormError.value = '';
  showTrackerModal.value = true;
}

function openEditModal() {
  editingTracker.value = true;
  const t = store.tracker;
  trackerForm.value = {
    name:        t.name,
    description: t.description ?? '',
    period:      t.period,
    start_date:  t.start_date?.substring(0, 10) ?? '',
    status:      t.status ?? 'active',
  };
  trackerFormError.value = '';
  showTrackerModal.value = true;
}

async function handleTrackerSubmit() {
  savingTracker.value    = true;
  trackerFormError.value = '';
  try {
    if (editingTracker.value) {
      await store.update(trackerForm.value);
    } else {
      await store.create(trackerForm.value);
      await loadTransactions();
    }
    showTrackerModal.value = false;
  } catch (e) {
    trackerFormError.value = e.response?.data?.message ?? 'Failed to save.';
  } finally {
    savingTracker.value = false;
  }
}

// ── Delete / Leave / Archive tracker ─────────────────────────────────────
const confirmDeleteTracker  = ref(false);
const confirmLeave          = ref(false);
const confirmArchiveTracker = ref(false);

async function handleDeleteTracker() {
  try {
    await store.remove();
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to delete tracker.');
  } finally {
    confirmDeleteTracker.value = false;
  }
}

async function handleLeave() {
  try {
    await store.leave();
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to leave tracker.');
  } finally {
    confirmLeave.value = false;
  }
}

async function handleArchiveTracker() {
  try {
    await store.archive();
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to archive tracker.');
  } finally {
    confirmArchiveTracker.value = false;
  }
}

// ── Allocations ───────────────────────────────────────────────────────────
const showAllocModal   = ref(false);
const editingAlloc     = ref(null);
const savingAlloc      = ref(false);
const allocFormError   = ref('');
const confirmDeleteAlloc = ref(null);

const defaultAllocForm = () => ({ name: '', allocated_amount: '', color: '#6366F1' });
const allocForm = ref(defaultAllocForm());

function openAllocModal(item = null) {
  editingAlloc.value   = item;
  allocForm.value      = item
    ? { name: item.name, allocated_amount: item.allocated_amount, color: item.color || '#6366F1' }
    : defaultAllocForm();
  allocFormError.value = '';
  showAllocModal.value = true;
}

async function handleAllocSubmit() {
  savingAlloc.value    = true;
  allocFormError.value = '';
  try {
    if (editingAlloc.value) {
      await store.updateAllocation(editingAlloc.value.id, allocForm.value);
    } else {
      await store.addAllocation(allocForm.value);
    }
    showAllocModal.value = false;
  } catch (e) {
    allocFormError.value = e.response?.data?.message ?? 'Failed to save allocation.';
  } finally {
    savingAlloc.value = false;
  }
}

async function handleDeleteAlloc() {
  try {
    await store.deleteAllocation(confirmDeleteAlloc.value.id);
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to delete allocation.');
  } finally {
    confirmDeleteAlloc.value = null;
  }
}

// ── Transactions ──────────────────────────────────────────────────────────
const showTxModal       = ref(false);
const editingTx         = ref(null);
const savingTx          = ref(false);
const txFormError       = ref('');
const confirmDeleteTx   = ref(null);
const txFilter          = ref({ type: '' });

const defaultTxForm = () => ({
  type:                          'expense',
  title:                         '',
  amount:                        '',
  date:                          new Date().toISOString().split('T')[0],
  budget_tracking_allocation_id: '',
  description:                   '',
});

const txForm = ref(defaultTxForm());

function openTxModal(item = null) {
  editingTx.value   = item;
  txForm.value      = item
    ? {
        type:                          item.type,
        title:                         item.title,
        amount:                        item.amount,
        date:                          item.date?.substring(0, 10) ?? '',
        budget_tracking_allocation_id: item.allocation?.id ?? '',
        description:                   item.description ?? '',
      }
    : defaultTxForm();
  txFormError.value = '';
  showTxModal.value = true;
}

async function handleTxSubmit() {
  savingTx.value    = true;
  txFormError.value = '';
  const payload = {
    ...txForm.value,
    budget_tracking_allocation_id: txForm.value.budget_tracking_allocation_id || null,
  };
  try {
    if (editingTx.value) {
      await store.updateTransaction(editingTx.value.id, payload);
    } else {
      await store.addTransaction(payload);
    }
    showTxModal.value = false;
  } catch (e) {
    txFormError.value = e.response?.data?.message ?? 'Failed to save transaction.';
  } finally {
    savingTx.value = false;
  }
}

async function handleDeleteTx() {
  try {
    await store.deleteTransaction(confirmDeleteTx.value.id);
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to delete transaction.');
  } finally {
    confirmDeleteTx.value = null;
  }
}

function loadTransactions(params = {}) {
  const filters = { ...params };
  if (txFilter.value.type) filters.type = txFilter.value.type;
  return store.fetchTransactions(filters);
}

function changeTxPage(page) {
  loadTransactions({ page });
}

// ── Members ───────────────────────────────────────────────────────────────
const confirmRemoveMember = ref(null);

async function handleRemoveMember() {
  try {
    await store.removeMember(confirmRemoveMember.value.user_id);
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to remove member.');
  } finally {
    confirmRemoveMember.value = null;
  }
}

// ── Consolidated tab ──────────────────────────────────────────────────────
const consolidatedSubTab = ref('overview');
const consolidatedSubTabs = [
  { value: 'overview',     label: 'Overview' },
  { value: 'income',       label: 'Income' },
  { value: 'expenses',     label: 'Expenses' },
  { value: 'debts',        label: 'Debts' },
  { value: 'investments',  label: 'Investments' },
  { value: 'stocks',       label: 'Stocks' },
  { value: 'crypto',       label: 'Crypto' },
  { value: 'payments',     label: 'Payments' },
  { value: 'purchases',    label: 'Purchases' },
];

async function loadConsolidated() {
  if (!store.consolidated) {
    await store.fetchConsolidated();
  }
}

watch(activeTab, (tab) => {
  if (tab === 'consolidated' && store.tracker) {
    loadConsolidated();
  }
});

function memberColor(index) {
  const colors = ['#6366F1','#10B981','#F59E0B','#EF4444','#3B82F6','#8B5CF6','#EC4899','#14B8A6'];
  return colors[index % colors.length];
}

const consolidatedMemberColors = computed(() => {
  const members = store.consolidated?.member_summary ?? [];
  return Object.fromEntries(members.map((m, i) => [m.name, memberColor(i)]));
});

// ── Mount ─────────────────────────────────────────────────────────────────
onMounted(async () => {
  await store.fetchTracker();
  if (store.tracker) {
    await loadTransactions();
  }
});
</script>
