<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-md">

      <!-- Step indicator -->
      <div class="flex items-center gap-2 mb-6">
        <div class="flex items-center gap-1.5">
          <div :class="['w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold', step === 1 ? 'bg-blue-600 text-white' : 'bg-green-500 text-white']">
            <span v-if="step === 1">1</span>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          </div>
          <span class="text-sm font-medium text-gray-700">Account</span>
        </div>
        <div class="flex-1 h-px bg-gray-200"></div>
        <div class="flex items-center gap-1.5">
          <div :class="['w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold', step === 2 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500']">2</div>
          <span class="text-sm font-medium text-gray-700">Budget Tracker</span>
        </div>
      </div>

      <!-- ── Step 1: Account Details ── -->
      <template v-if="step === 1">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Create Account</h1>
        <p class="text-gray-500 text-sm mb-6">Start tracking your finances today</p>

        <form @submit.prevent="handleRegister" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
            <input v-model="form.name" type="text" required autocomplete="name"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Juan Dela Cruz" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input v-model="form.email" type="email" required autocomplete="username"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="juan@example.com" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input v-model="form.password" type="password" required minlength="8" autocomplete="new-password"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Min 8 characters" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
            <input v-model="form.password_confirmation" type="password" required autocomplete="new-password"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Repeat password" />
          </div>

          <div v-if="registerError" class="bg-red-50 text-red-600 rounded-lg p-3 text-sm">
            <div v-for="(msgs, field) in registerError" :key="field">
              <span v-for="msg in msgs" :key="msg">{{ msg }}</span>
            </div>
          </div>

          <button type="submit" :disabled="loading"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium">
            {{ loading ? 'Creating Account...' : 'Continue' }}
          </button>
        </form>

        <p class="text-center mt-4 text-sm text-gray-600">
          Already have an account?
          <router-link to="/login" class="text-blue-600 hover:underline font-medium">Sign in</router-link>
        </p>
      </template>

      <!-- ── Step 2: Tracker Setup ── -->
      <template v-else>
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Set Up Budget Tracker</h1>
        <p class="text-gray-500 text-sm mb-6">Create your own tracker or join an existing one with a code.</p>

        <!-- Mode tabs -->
        <div class="flex rounded-lg border border-gray-200 overflow-hidden mb-6">
          <button
            @click="trackerMode = 'create'"
            :class="['flex-1 py-2 text-sm font-medium transition-colors', trackerMode === 'create' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50']"
          >Create Tracker</button>
          <button
            @click="trackerMode = 'join'"
            :class="['flex-1 py-2 text-sm font-medium transition-colors', trackerMode === 'join' ? 'bg-green-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50']"
          >Join with Code</button>
        </div>

        <!-- Create form -->
        <form v-if="trackerMode === 'create'" @submit.prevent="handleCreate" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tracker Name</label>
            <input v-model="trackerName" type="text" required maxlength="100"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="e.g. Family Budget 2026" />
          </div>

          <div v-if="trackerError" class="bg-red-50 text-red-600 rounded-lg p-3 text-sm">{{ trackerError }}</div>

          <button type="submit" :disabled="trackerLoading"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium">
            {{ trackerLoading ? 'Creating...' : 'Create & Continue' }}
          </button>
        </form>

        <!-- Join form -->
        <form v-else @submit.prevent="handleJoin" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Join Code</label>
            <input v-model="joinCode" type="text" required maxlength="8"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-center font-mono uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-green-500"
              placeholder="XXXXXXXX" />
          </div>

          <div v-if="trackerError" class="bg-red-50 text-red-600 rounded-lg p-3 text-sm">{{ trackerError }}</div>

          <button type="submit" :disabled="trackerLoading || joinCode.length < 8"
            class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 disabled:opacity-50 font-medium">
            {{ trackerLoading ? 'Joining...' : 'Join & Continue' }}
          </button>
        </form>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useBudgetTrackingStore } from '@/stores/budgetTracking';

const router   = useRouter();
const auth     = useAuthStore();
const btStore  = useBudgetTrackingStore();

// Step 1
const step            = ref(1);
const loading         = ref(false);
const registerError   = ref(null);
const form            = ref({ name: '', email: '', password: '', password_confirmation: '' });

// Step 2
const trackerMode     = ref('create');
const trackerName     = ref('');
const joinCode        = ref('');
const trackerLoading  = ref(false);
const trackerError    = ref('');

async function handleRegister() {
  loading.value = true;
  registerError.value = null;
  try {
    await auth.register(form.value);
    step.value = 2;
  } catch (e) {
    registerError.value = e.response?.data?.errors ?? { general: [e.response?.data?.message ?? 'Registration failed'] };
  } finally {
    loading.value = false;
  }
}

async function handleCreate() {
  trackerLoading.value = true;
  trackerError.value = '';
  try {
    await btStore.create({ name: trackerName.value });
    router.push('/budget-tracking');
  } catch (e) {
    trackerError.value = e.response?.data?.message ?? 'Failed to create tracker.';
  } finally {
    trackerLoading.value = false;
  }
}

async function handleJoin() {
  trackerLoading.value = true;
  trackerError.value = '';
  try {
    await btStore.join(joinCode.value.toUpperCase());
    router.push('/budget-tracking');
  } catch (e) {
    trackerError.value = e.response?.data?.message ?? 'Invalid code. Please try again.';
  } finally {
    trackerLoading.value = false;
  }
}
</script>
