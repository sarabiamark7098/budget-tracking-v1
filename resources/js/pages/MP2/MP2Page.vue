<template>
  <div class="space-y-6">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">MP2 Calculator</h1>

    <!-- Calculator + Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">

      <!-- Calculator Form -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-semibold text-gray-700 mb-1">Calculate MP2 Earnings</h2>
        <p class="text-xs text-gray-400 mb-4">Based on PAG-IBIG Average Daily Balance (ADB) method</p>

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
              placeholder="e.g. 2,000"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
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
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Annual Dividend Rate (%)
                <span class="text-xs text-gray-400 font-normal">optional</span>
              </label>
              <input
                v-model="calcForm.dividend_rate"
                type="number"
                min="0.01"
                max="30"
                step="0.01"
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Default: 7.1%"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input
              v-model="calcForm.start_date"
              type="date"
              class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p class="text-xs text-gray-400 mt-1">Used to label years in the breakdown (optional)</p>
          </div>

          <!-- Rate reference note -->
          <div class="bg-blue-50 rounded-lg px-3 py-2 text-xs text-blue-700 space-y-0.5">
            <p class="font-medium">PAG-IBIG Declared MP2 Dividend Rates:</p>
            <p>2024: <strong>7.10%</strong> &nbsp;·&nbsp; 2023: <strong>7.03%</strong> &nbsp;·&nbsp; 2022: <strong>6.53%</strong></p>
          </div>

          <div class="flex gap-3 pt-1">
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
        <div class="flex items-start justify-between mb-4">
          <h2 class="font-semibold text-gray-700">Projection Summary</h2>
          <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-medium">
            Rate: {{ result.annual_dividend_rate }}% / yr
          </span>
        </div>

        <div class="space-y-3">
          <!-- Total Contributions -->
          <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
            <div>
              <p class="text-xs text-gray-500 font-medium">Total Contributions</p>
              <p class="text-xs text-gray-400">{{ result.duration_years }} yr{{ result.duration_years > 1 ? 's' : '' }} × ₱{{ Number(result.monthly_contribution).toLocaleString() }}/mo</p>
            </div>
            <p class="text-lg font-bold text-gray-700">{{ formatCurrency(result.total_contributions) }}</p>
          </div>

          <!-- Dividends Earned -->
          <div class="flex items-center justify-between bg-green-50 rounded-lg px-4 py-3">
            <div>
              <p class="text-xs text-green-700 font-medium">Projected Dividends</p>
              <p class="text-xs text-green-600">+{{ result.effective_total_return }}% return on contributions</p>
            </div>
            <p class="text-lg font-bold text-green-700">{{ formatCurrency(result.projected_earnings) }}</p>
          </div>

          <!-- Total Value -->
          <div class="flex items-center justify-between bg-blue-600 rounded-lg px-4 py-3">
            <div>
              <p class="text-xs text-blue-100 font-medium">Total Projected Value</p>
              <p class="text-xs text-blue-200">Contributions + Dividends</p>
            </div>
            <p class="text-xl font-bold text-white">{{ formatCurrency(result.total_value) }}</p>
          </div>

          <!-- Growth pill -->
          <div class="flex justify-end">
            <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium">
              ×{{ growthMultiple }} growth over {{ result.duration_years }} year{{ result.duration_years > 1 ? 's' : '' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Empty state when no result -->
      <div v-else class="bg-white rounded-xl shadow-sm p-6 flex flex-col items-center justify-center text-gray-400 gap-3">
        <span class="text-4xl">📊</span>
        <p class="text-sm text-center">Fill in the form and hit <strong>Calculate</strong> to see your MP2 projection.</p>
      </div>
    </div>

    <!-- Yearly Breakdown Table -->
    <div v-if="result?.yearly_breakdown?.length" class="bg-white rounded-xl shadow-sm p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-700">Yearly Breakdown</h2>
        <span class="text-xs text-gray-400">ADB (Average Daily Balance) method</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b bg-gray-50">
              <th class="text-left py-2.5 px-3 text-gray-500 font-medium">Year</th>
              <th class="text-right py-2.5 px-3 text-gray-500 font-medium">Opening Balance</th>
              <th class="text-right py-2.5 px-3 text-gray-500 font-medium">Contributions</th>
              <th class="text-right py-2.5 px-3 text-gray-500 font-medium">Dividends Earned</th>
              <th class="text-right py-2.5 px-3 text-gray-500 font-medium">Closing Balance</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in result.yearly_breakdown"
              :key="row.year"
              class="border-b last:border-0 hover:bg-gray-50 transition"
            >
              <td class="py-2.5 px-3 font-medium text-gray-700">
                Year {{ row.year }}
                <span v-if="row.calendar_year" class="text-xs text-gray-400 font-normal">({{ row.calendar_year }})</span>
              </td>
              <td class="py-2.5 px-3 text-right text-gray-500">{{ formatCurrency(row.opening_balance) }}</td>
              <td class="py-2.5 px-3 text-right text-blue-600">{{ formatCurrency(row.yearly_contribution) }}</td>
              <td class="py-2.5 px-3 text-right text-green-600">
                {{ formatCurrency(row.total_dividends) }}
                <span class="block text-xs text-gray-400">({{ row.effective_return_pct }}%)</span>
              </td>
              <td class="py-2.5 px-3 text-right font-semibold text-indigo-600">{{ formatCurrency(row.closing_balance) }}</td>
            </tr>
          </tbody>
          <tfoot class="border-t-2 border-gray-200 bg-gray-50">
            <tr>
              <td class="py-2 px-3 text-sm font-semibold text-gray-700">Total</td>
              <td class="py-2 px-3 text-right text-sm text-gray-400">—</td>
              <td class="py-2 px-3 text-right text-sm font-semibold text-blue-600">{{ formatCurrency(result.total_contributions) }}</td>
              <td class="py-2 px-3 text-right text-sm font-semibold text-green-600">{{ formatCurrency(result.projected_earnings) }}</td>
              <td class="py-2 px-3 text-right text-sm font-bold text-indigo-600">{{ formatCurrency(result.total_value) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Saved Plans -->
    <div class="space-y-3">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-700">Saved Plans</h2>
        <span v-if="store.items.length" class="text-xs text-gray-400">{{ store.items.length }} plan{{ store.items.length > 1 ? 's' : '' }}</span>
      </div>

      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>

        <div v-else-if="store.items.length === 0" class="text-center py-10 text-gray-400 text-sm">
          No saved plans yet. Run a calculation and save it.
        </div>

        <table v-else class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Plan Name</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Monthly</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Duration</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Total Invested</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Dividends</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Total Value</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Start Date</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="plan in store.items" :key="plan.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-700">{{ plan.name ?? 'MP2 Plan' }}</td>
              <td class="px-4 py-3 text-right text-blue-600">{{ formatCurrency(plan.monthly_contribution) }}</td>
              <td class="px-4 py-3 text-right text-gray-500">{{ plan.duration_years }} yr{{ plan.duration_years > 1 ? 's' : '' }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ formatCurrency(plan.total_contributions) }}</td>
              <td class="px-4 py-3 text-right text-green-600">{{ formatCurrency(plan.projected_earnings) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-indigo-600">{{ formatCurrency(plan.total_value) }}</td>
              <td class="px-4 py-3 text-gray-500">{{ formatDate(plan.start_date) }}</td>
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
          <div class="bg-gray-50 rounded-lg px-4 py-3 space-y-1.5 text-xs text-gray-600">
            <div class="flex justify-between">
              <span>Monthly contribution</span>
              <span class="font-semibold">{{ formatCurrency(calcForm.monthly_contribution) }}</span>
            </div>
            <div class="flex justify-between">
              <span>Duration</span>
              <span class="font-semibold">{{ calcForm.duration_years }} years</span>
            </div>
            <div class="flex justify-between">
              <span>Rate used</span>
              <span class="font-semibold">{{ result?.annual_dividend_rate }}%</span>
            </div>
            <div class="flex justify-between border-t pt-1.5">
              <span>Total projected value</span>
              <span class="font-bold text-indigo-600">{{ formatCurrency(result?.total_value) }}</span>
            </div>
          </div>
          <div v-if="formError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showSaveModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-blue-700">
              {{ saving ? 'Saving...' : 'Save Plan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirm Delete Dialog -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Plan</h3>
        <p class="text-sm text-gray-500 mb-4">Delete "{{ deleteTarget.name ?? 'this plan' }}"? This cannot be undone.</p>
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
import { useMp2Store } from '@/stores/mp2';
import { formatDate } from '@/utils/date';

const store      = useMp2Store();
const calculating = ref(false);
const saving      = ref(false);
const formError   = ref('');
const result      = ref(null);
const showSaveModal = ref(false);
const saveLabel   = ref('');
const deleteTarget = ref(null);

const calcForm = ref({
  monthly_contribution: '',
  duration_years:       '',
  dividend_rate:        '',
  start_date:           new Date().toISOString().split('T')[0],
});

const growthMultiple = computed(() => {
  if (!result.value || !result.value.total_contributions) return '—';
  return (result.value.total_value / result.value.total_contributions).toFixed(2) + 'x';
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
  saveLabel.value  = '';
  formError.value  = '';
  showSaveModal.value = true;
}

async function handleSave() {
  saving.value    = true;
  formError.value = '';
  try {
    await store.create({
      name:                 saveLabel.value || 'MP2 Plan',
      monthly_contribution: Number(calcForm.value.monthly_contribution),
      duration_years:       Number(calcForm.value.duration_years),
      start_date:           calcForm.value.start_date,
      dividend_rate:        calcForm.value.dividend_rate ? Number(calcForm.value.dividend_rate) : undefined,
    });
    showSaveModal.value = false;
    await store.fetchAll();
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
  if (!deleteTarget.value?.id) { deleteTarget.value = null; return; }
  await store.remove(deleteTarget.value.id);
  deleteTarget.value = null;
}

onMounted(() => store.fetchAll());
</script>
