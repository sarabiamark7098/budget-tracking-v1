<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">MP2 Calculator</h1>

    <!-- Calculator Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Calculator Form -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-gray-700 mb-4">Calculate MP2 Earnings</h2>
        <form @submit.prevent="handleCalculate" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Contribution (₱) *</label>
            <input
              v-model="calcForm.monthly_contribution"
              type="number"
              min="500"
              step="100"
              required
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="e.g. 1000"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Duration (Years) *</label>
            <input
              v-model="calcForm.duration_years"
              type="number"
              min="1"
              max="30"
              required
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="e.g. 5"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input
              v-model="calcForm.start_date"
              type="date"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Annual Dividend Rate (%)</label>
            <input
              v-model="calcForm.annual_rate"
              type="number"
              min="0"
              step="0.1"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Default: 7.03% (2023 rate)"
            />
          </div>
          <div class="flex gap-3 pt-2">
            <button
              type="submit"
              :disabled="calculating"
              class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-blue-700 font-medium"
            >
              {{ calculating ? 'Calculating...' : 'Calculate' }}
            </button>
            <button
              v-if="result"
              type="button"
              @click="openSaveModal"
              class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-green-700 font-medium"
            >
              Save Plan
            </button>
          </div>
        </form>
      </div>

      <!-- Results Summary -->
      <div v-if="result" class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-gray-700 mb-4">Projection Summary</h2>
        <div class="space-y-4">
          <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-xs text-blue-600 font-medium mb-1">Total Contributions</p>
            <p class="text-2xl font-bold text-blue-700">{{ formatCurrency(result.total_contributions) }}</p>
          </div>
          <div class="bg-green-50 rounded-lg p-4">
            <p class="text-xs text-green-600 font-medium mb-1">Projected Dividends</p>
            <p class="text-2xl font-bold text-green-700">{{ formatCurrency(result.total_dividends) }}</p>
          </div>
          <div class="bg-indigo-50 rounded-lg p-4">
            <p class="text-xs text-indigo-600 font-medium mb-1">Total Projected Value</p>
            <p class="text-2xl font-bold text-indigo-700">{{ formatCurrency(result.total_value) }}</p>
          </div>
          <div class="flex items-center gap-2 text-sm text-gray-500 pt-1">
            <span>Return Rate:</span>
            <span class="font-semibold text-green-600">{{ result.return_rate ?? calcForm.annual_rate ?? '7.03' }}%</span>
            <span>per year</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Yearly Breakdown Table -->
    <div v-if="result?.yearly_breakdown?.length" class="bg-white rounded-xl shadow-sm p-5">
      <h2 class="font-semibold text-gray-700 mb-4">Yearly Breakdown</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b">
              <th class="text-left py-2 px-3 text-gray-500 font-medium">Year</th>
              <th class="text-right py-2 px-3 text-gray-500 font-medium">Contributions</th>
              <th class="text-right py-2 px-3 text-gray-500 font-medium">Dividends Earned</th>
              <th class="text-right py-2 px-3 text-gray-500 font-medium">Year-End Balance</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in result.yearly_breakdown" :key="row.year" class="border-b last:border-0 hover:bg-gray-50">
              <td class="py-2 px-3 font-medium text-gray-700">Year {{ row.year }}</td>
              <td class="py-2 px-3 text-right text-blue-600">{{ formatCurrency(row.contributions) }}</td>
              <td class="py-2 px-3 text-right text-green-600">{{ formatCurrency(row.dividends) }}</td>
              <td class="py-2 px-3 text-right font-semibold text-indigo-600">{{ formatCurrency(row.balance) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Saved Plans -->
    <div class="space-y-4">
      <h2 class="text-lg font-semibold text-gray-700">Saved Plans</h2>
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
        <table v-else class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Label</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Monthly</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Duration</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Total Invested</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Projected Value</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Start Date</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="7" class="text-center py-10 text-gray-400">No saved plans yet. Run a calculation and save it.</td>
            </tr>
            <tr v-for="plan in store.items" :key="plan.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-700">{{ plan.label ?? 'MP2 Plan' }}</td>
              <td class="px-4 py-3 text-right text-blue-600">{{ formatCurrency(plan.monthly_contribution) }}</td>
              <td class="px-4 py-3 text-right text-gray-500">{{ plan.duration_years }} yr{{ plan.duration_years > 1 ? 's' : '' }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ formatCurrency(plan.total_contributions) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-green-600">{{ formatCurrency(plan.total_value) }}</td>
              <td class="px-4 py-3 text-gray-500">{{ plan.start_date ?? '—' }}</td>
              <td class="px-4 py-3">
                <button @click="confirmDelete(plan)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Save Plan Modal -->
    <div v-if="showSaveModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">Save MP2 Plan</h2>
          <button @click="showSaveModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSave" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Plan Label</label>
            <input v-model="saveLabel" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. My 5-Year MP2 Plan" />
          </div>
          <div class="text-xs text-gray-500 space-y-1">
            <p>Monthly: <strong>{{ formatCurrency(calcForm.monthly_contribution) }}</strong></p>
            <p>Duration: <strong>{{ calcForm.duration_years }} years</strong></p>
            <p>Projected Value: <strong class="text-green-600">{{ formatCurrency(result?.total_value) }}</strong></p>
          </div>
          <div v-if="formError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showSaveModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-blue-700">
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete Dialog -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Plan</h3>
        <p class="text-sm text-gray-500 mb-4">Delete "{{ deleteTarget.label ?? 'this plan' }}"? This cannot be undone.</p>
        <div class="flex justify-end gap-3">
          <button @click="deleteTarget = null" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handleDelete" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useMp2Store } from '@/stores/mp2';

const store = useMp2Store();
const calculating = ref(false);
const saving = ref(false);
const formError = ref('');
const result = ref(null);
const showSaveModal = ref(false);
const saveLabel = ref('');
const deleteTarget = ref(null);

const calcForm = ref({
  monthly_contribution: '',
  duration_years: '',
  start_date: new Date().toISOString().split('T')[0],
  annual_rate: '',
});

function formatCurrency(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

async function handleCalculate() {
  calculating.value = true;
  try {
    result.value = await store.calculate(calcForm.value);
  } catch (e) {
    alert(e.response?.data?.message ?? 'Calculation failed.');
  } finally {
    calculating.value = false;
  }
}

function openSaveModal() {
  saveLabel.value = '';
  formError.value = '';
  showSaveModal.value = true;
}

async function handleSave() {
  saving.value = true;
  formError.value = '';
  try {
    await store.create({
      ...calcForm.value,
      label: saveLabel.value,
      total_contributions: result.value?.total_contributions,
      total_dividends: result.value?.total_dividends,
      total_value: result.value?.total_value,
    });
    showSaveModal.value = false;
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save plan.';
  } finally {
    saving.value = false;
  }
}

function confirmDelete(plan) {
  deleteTarget.value = plan;
}

async function handleDelete() {
  await store.remove(deleteTarget.value.id);
  deleteTarget.value = null;
}

onMounted(() => store.fetchAll());
</script>
