<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <router-link to="/dashboard" class="text-gray-400 hover:text-gray-600 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </router-link>
        <h1 class="text-2xl font-bold text-gray-800">All Transactions</h1>
      </div>
      <span class="text-sm text-gray-400">{{ pagination.total }} total</span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-16 text-gray-400 gap-3">
      <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
      </svg>
      <span>Loading…</span>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="!transactions.length" class="text-center py-16 text-gray-400">
        <p class="text-lg font-medium">No transactions yet</p>
        <p class="text-sm mt-1">Start by adding income or expenses.</p>
      </div>

      <div v-else>
        <!-- Transaction rows -->
        <div class="divide-y divide-gray-50">
          <div
            v-for="tx in transactions"
            :key="`${tx.type}-${tx.id}`"
            class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 transition"
          >
            <div class="flex items-center gap-4">
              <!-- Type badge -->
              <span
                class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0"
                :class="txBadgeClass(tx.type)"
              >
                {{ txIcon(tx.type) }}
              </span>

              <div>
                <p class="font-medium text-gray-800 text-sm">{{ tx.title }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                  <span class="text-gray-500">Recorded {{ formatDateTime(tx.created_at) }}</span>
                  · <span class="capitalize">{{ tx.type.replace(/_/g, ' ') }}</span>
                  <span v-if="tx.category"> · {{ tx.category }}</span>
                  <span class="text-gray-300"> · {{ formatDate(tx.date) }}</span>
                </p>
              </div>
            </div>

            <div class="text-right">
              <p
                class="font-semibold text-sm"
                :class="['income','business_debt_received','saving_transfer'].includes(tx.type) ? 'text-green-600' : 'text-red-600'"
              >
                {{ ['income','business_debt_received','saving_transfer'].includes(tx.type) ? '+' : '-' }}{{ fmt(tx.amount) }}
              </p>
              <p class="text-xs text-gray-400 capitalize mt-0.5">{{ tx.type.replace(/_/g, ' ') }}</p>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-5 py-4 border-t bg-gray-50">
          <button
            @click="goToPage(pagination.currentPage - 1)"
            :disabled="pagination.currentPage <= 1"
            class="px-3 py-1.5 text-sm rounded-lg border transition"
            :class="pagination.currentPage <= 1
              ? 'text-gray-300 border-gray-200 cursor-not-allowed'
              : 'text-gray-600 border-gray-300 hover:bg-white'"
          >
            ← Previous
          </button>

          <div class="flex items-center gap-1">
            <button
              v-for="p in pageNumbers"
              :key="p"
              @click="goToPage(p)"
              class="w-8 h-8 rounded-lg text-sm transition"
              :class="p === pagination.currentPage
                ? 'bg-blue-600 text-white font-semibold'
                : 'text-gray-600 hover:bg-white border border-gray-200'"
            >
              {{ p }}
            </button>
          </div>

          <button
            @click="goToPage(pagination.currentPage + 1)"
            :disabled="!pagination.hasMore"
            class="px-3 py-1.5 text-sm rounded-lg border transition"
            :class="!pagination.hasMore
              ? 'text-gray-300 border-gray-200 cursor-not-allowed'
              : 'text-gray-600 border-gray-300 hover:bg-white'"
          >
            Next →
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { formatDate } from '@/utils/date';
import { dashboardService } from '@/services/index';

const transactions = ref([]);
const loading      = ref(false);
const pagination   = ref({
  total:       0,
  currentPage: 1,
  lastPage:    1,
  perPage:     15,
  hasMore:     false,
});

async function fetchPage(page = 1) {
  loading.value = true;
  try {
    const { data } = await dashboardService.getTransactions({ page, per_page: pagination.value.perPage });
    transactions.value         = data.data.data ?? [];
    pagination.value.total       = data.data.total       ?? 0;
    pagination.value.currentPage = data.data.current_page ?? page;
    pagination.value.lastPage    = data.data.last_page    ?? 1;
    pagination.value.hasMore     = data.data.has_more     ?? false;
  } finally {
    loading.value = false;
  }
}

function goToPage(page) {
  if (page < 1 || page > pagination.value.lastPage) return;
  fetchPage(page);
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Show at most 7 page buttons centred on current page
const pageNumbers = computed(() => {
  const total   = pagination.value.lastPage;
  const current = pagination.value.currentPage;
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

  const pages = new Set([1, total, current]);
  for (let d = 1; d <= 2; d++) {
    if (current - d >= 1)     pages.add(current - d);
    if (current + d <= total) pages.add(current + d);
  }
  return [...pages].sort((a, b) => a - b);
});

// ── Helpers ───────────────────────────────────────────────────────────────
function fmt(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

// formatDate imported from @/utils/date

function formatDateTime(val) {
  if (!val) return '';
  const d = new Date(val);
  return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
    + ' ' + d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function txIcon(type) {
  return {
    income:                 '↑',
    expense:                '↓',
    debt_payment:           '⊘',
    business_debt_received: '↑',
    purchase:               '🛒',
    purchase_payment:       '💳',
    module_transfer:        '→',
    module_transfer_back:   '←',
    saving_transfer:        '↑',
  }[type] ?? '·';
}

function txBadgeClass(type) {
  return {
    income:                 'bg-green-100 text-green-700',
    expense:                'bg-red-100 text-red-700',
    debt_payment:           'bg-orange-100 text-orange-700',
    business_debt_received: 'bg-teal-100 text-teal-700',
    purchase:               'bg-purple-100 text-purple-700',
    purchase_payment:       'bg-violet-100 text-violet-700',
    module_transfer:        'bg-indigo-100 text-indigo-700',
    module_transfer_back:   'bg-green-100 text-green-700',
    saving_transfer:        'bg-green-100 text-green-700',
  }[type] ?? 'bg-gray-100 text-gray-600';
}

onMounted(() => fetchPage(1));
</script>
