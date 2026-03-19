<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-md">
      <h1 class="text-2xl font-bold text-gray-800 mb-2">Create Account</h1>
      <p class="text-gray-500 mb-6">Start tracking your finances today</p>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
          <input v-model="form.name" type="text" required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Juan Dela Cruz" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input v-model="form.email" type="email" required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="juan@example.com" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input v-model="form.password" type="password" required minlength="8"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Min 8 characters" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
          <input v-model="form.password_confirmation" type="password" required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Repeat password" />
        </div>

        <div v-if="errors" class="bg-red-50 text-red-600 rounded-lg p-3 text-sm">
          <div v-for="(msgs, field) in errors" :key="field">
            <span v-for="msg in msgs" :key="msg">{{ msg }}</span>
          </div>
        </div>

        <button type="submit" :disabled="loading"
          class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium">
          {{ loading ? 'Creating Account...' : 'Create Account' }}
        </button>
      </form>

      <p class="text-center mt-4 text-sm text-gray-600">
        Already have an account?
        <router-link to="/login" class="text-blue-600 hover:underline font-medium">Sign in</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const auth = useAuthStore();

const form = ref({ name: '', email: '', password: '', password_confirmation: '' });
const loading = ref(false);
const errors = ref(null);

async function handleRegister() {
  loading.value = true;
  errors.value = null;
  try {
    await auth.register(form.value);
    router.push('/dashboard');
  } catch (e) {
    errors.value = e.response?.data?.errors ?? { general: [e.response?.data?.message ?? 'Registration failed'] };
  } finally {
    loading.value = false;
  }
}
</script>
