<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Mobile overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-30 w-64 bg-gray-900 text-white flex flex-col transition-transform duration-300',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'lg:relative lg:translate-x-0'
      ]"
    >
      <!-- Logo -->
      <div class="flex items-center justify-between px-6 py-5 border-b border-gray-700">
        <div class="flex items-center gap-2">
          <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="text-lg font-bold text-white">BudgetTrack</span>
        </div>
        <button class="lg:hidden text-gray-400 hover:text-white" @click="sidebarOpen = false">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">

        <!-- Gate banner: no tracker yet -->
        <div v-if="btStore.hasChecked && !btStore.tracker" class="mx-1 mb-3 px-3 py-3 bg-indigo-900 rounded-lg text-xs text-indigo-200 leading-relaxed">
          <p class="font-semibold text-indigo-100 mb-1">Get started</p>
          Create or join a Budget Tracker to unlock all features.
        </div>

        <RouterLink
          v-for="item in visibleNavItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="isActive(item.path)
            ? 'bg-indigo-600 text-white'
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'"
          @click="sidebarOpen = false"
        >
          <span class="w-5 h-5 flex-shrink-0" v-html="item.icon" />
          {{ item.label }}
        </RouterLink>
      </nav>

      <!-- User info -->
      <div class="px-4 py-4 border-t border-gray-700">
        <button
          class="flex items-center gap-3 w-full text-left hover:bg-gray-800 rounded-lg px-2 py-1.5 transition-colors group"
          @click="openAccountModal"
        >
          <div class="w-9 h-9 rounded-full bg-indigo-500 flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
            {{ userInitial }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ authStore.user?.name ?? 'User' }}</p>
            <p class="text-xs text-gray-400 truncate group-hover:text-gray-300">Manage account</p>
          </div>
          <svg class="w-4 h-4 text-gray-500 group-hover:text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </button>
      </div>
    </aside>

    <!-- ── Manage Account Modal ──────────────────────────────────────────────── -->
    <div v-if="showAccountModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h2 class="text-lg font-semibold text-gray-800">Manage Account</h2>
          <button @click="showAccountModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="px-6 py-5 space-y-6">

          <!-- Change Name -->
          <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Change Name</h3>
            <form @submit.prevent="handleUpdateName" class="space-y-3">
              <input
                v-model="nameForm.name"
                type="text"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Full name"
              />
              <div v-if="nameError" class="text-red-600 text-xs bg-red-50 rounded-lg px-3 py-2">{{ nameError }}</div>
              <div v-if="nameSuccess" class="text-green-600 text-xs bg-green-50 rounded-lg px-3 py-2">{{ nameSuccess }}</div>
              <button
                type="submit"
                :disabled="nameSaving"
                class="w-full bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50"
              >{{ nameSaving ? 'Saving...' : 'Update Name' }}</button>
            </form>
          </div>

          <div class="border-t"></div>

          <!-- Change Password -->
          <div>
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Change Password</h3>
            <form @submit.prevent="handleChangePassword" class="space-y-3">
              <input
                v-model="pwForm.current_password"
                type="password"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Current password"
              />
              <input
                v-model="pwForm.password"
                type="password"
                required
                minlength="8"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="New password (min 8 characters)"
              />
              <input
                v-model="pwForm.password_confirmation"
                type="password"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Confirm new password"
              />
              <div v-if="pwError" class="text-red-600 text-xs bg-red-50 rounded-lg px-3 py-2">{{ pwError }}</div>
              <div v-if="pwSuccess" class="text-green-600 text-xs bg-green-50 rounded-lg px-3 py-2">{{ pwSuccess }}</div>
              <button
                type="submit"
                :disabled="pwSaving"
                class="w-full bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50"
              >{{ pwSaving ? 'Saving...' : 'Change Password' }}</button>
            </form>
          </div>

        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Header -->
      <header class="bg-white shadow-sm z-10 flex items-center justify-between px-4 py-3 lg:px-6">
        <button
          class="lg:hidden p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100"
          @click="sidebarOpen = true"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <h1 class="text-lg font-semibold text-gray-800 hidden lg:block">
          {{ currentPageTitle }}
        </h1>

        <div class="flex items-center gap-3 ml-auto">
          <span class="text-sm text-gray-600 hidden sm:block">{{ authStore.user?.name }}</span>
          <button
            class="flex items-center gap-2 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors"
            @click="handleLogout"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span class="hidden sm:block">Logout</span>
          </button>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-auto p-4 lg:p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useBudgetTrackingStore } from '@/stores/budgetTracking';

// ── Account modal ─────────────────────────────────────────────────────────────

const router   = useRouter();
const route    = useRoute();
const authStore = useAuthStore();
const btStore   = useBudgetTrackingStore();
const sidebarOpen = ref(false);

const userInitial = computed(() => {
  const name = authStore.user?.name ?? 'U';
  return name.charAt(0).toUpperCase();
});

// ── All nav items ──────────────────────────────────────────────────────────────
const allNavItems = [
  {
    path: '/dashboard',
    label: 'Dashboard',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>',
  },
  {
    path: '/income',
    label: 'Income',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m0 0l-4-4m4 4l4-4" /></svg>',
  },
  {
    path: '/expenses',
    label: 'Expenses',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20V4m0 0l-4 4m4-4l4 4" /></svg>',
  },
  {
    path: '/purchases',
    label: 'Purchases',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
  },
  {
    path: '/budget',
    label: 'Budget',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
  },
  {
    path: '/budget-tracking',
    label: 'Budget Tracker',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
  },
  {
    path: '/debts',
    label: 'Debts',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>',
  },
  {
    path: '/payments',
    label: 'Payments',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
  },
  {
    path: '/investments',
    label: 'Investments',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>',
  },
  {
    path: '/stocks',
    label: 'Stocks',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>',
  },
  {
    path: '/crypto',
    label: 'Crypto',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
  },
  {
    path: '/financial-plans',
    label: 'Financial Plans',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>',
  },
  {
    path: '/insurance',
    label: 'Insurance',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>',
  },
  {
    path: '/mp2',
    label: 'MP2 Calculator',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>',
  },
  {
    path: '/reports',
    label: 'Reports',
    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>',
  },
];

// Show all nav items only when user has an active tracker.
// Before the check resolves (hasChecked=false) show all to avoid flicker on refresh.
const visibleNavItems = computed(() => {
  if (!btStore.hasChecked || btStore.tracker) return allNavItems;
  // No tracker — only show Budget Tracker entry
  return allNavItems.filter(i => i.path === '/budget-tracking');
});

const pageTitles = {
  '/dashboard': 'Dashboard',
  '/income': 'Income',
  '/expenses': 'Expenses',
  '/budget': 'Budget',
  '/budget-tracking': 'Budget Tracker',
  '/debts': 'Debts',
  '/payments': 'Payments',
  '/investments': 'Investments',
  '/stocks': 'Stocks',
  '/crypto': 'Crypto',
  '/financial-plans': 'Financial Plans',
  '/insurance': 'Insurance',
  '/purchases': 'Purchases',
  '/mp2': 'MP2 Calculator',
  '/reports': 'Reports',
};

const currentPageTitle = computed(() => pageTitles[route.path] ?? 'BudgetTrack');

function isActive(path) {
  return route.path === path;
}

// Once we know the user has no tracker, redirect any non-tracker route
watch(
  () => [btStore.hasChecked, btStore.tracker],
  ([checked, tracker]) => {
    if (checked && !tracker && route.path !== '/budget-tracking') {
      router.replace('/budget-tracking');
    }
  },
  { immediate: true },
);

async function handleLogout() {
  await authStore.logout();
  router.push('/login');
}

// ── Account modal ─────────────────────────────────────────────────────────────
const showAccountModal = ref(false);

const nameForm    = ref({ name: '' });
const nameSaving  = ref(false);
const nameError   = ref('');
const nameSuccess = ref('');

const pwForm    = ref({ current_password: '', password: '', password_confirmation: '' });
const pwSaving  = ref(false);
const pwError   = ref('');
const pwSuccess = ref('');

function openAccountModal() {
  nameForm.value.name = authStore.user?.name ?? '';
  nameError.value   = '';
  nameSuccess.value = '';
  pwForm.value      = { current_password: '', password: '', password_confirmation: '' };
  pwError.value     = '';
  pwSuccess.value   = '';
  showAccountModal.value = true;
}

async function handleUpdateName() {
  nameSaving.value  = true;
  nameError.value   = '';
  nameSuccess.value = '';
  try {
    await authStore.updateProfile({ name: nameForm.value.name });
    nameSuccess.value = 'Name updated successfully.';
  } catch (e) {
    const errs = e.response?.data?.errors;
    nameError.value = errs ? Object.values(errs).flat().join(' ') : (e.response?.data?.message ?? 'Failed to update name.');
  } finally {
    nameSaving.value = false;
  }
}

async function handleChangePassword() {
  if (pwForm.value.password !== pwForm.value.password_confirmation) {
    pwError.value = 'Passwords do not match.';
    return;
  }
  pwSaving.value  = true;
  pwError.value   = '';
  pwSuccess.value = '';
  try {
    await authStore.changePassword(pwForm.value);
    pwSuccess.value = 'Password changed successfully.';
    pwForm.value    = { current_password: '', password: '', password_confirmation: '' };
  } catch (e) {
    const errs = e.response?.data?.errors;
    pwError.value = errs ? Object.values(errs).flat().join(' ') : (e.response?.data?.message ?? 'Failed to change password.');
  } finally {
    pwSaving.value = false;
  }
}
</script>
