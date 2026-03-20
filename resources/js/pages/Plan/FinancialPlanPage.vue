<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Financial Plans &amp; Goals</h1>

    <!-- Plans Section -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-700">Plans</h2>
        <button @click="openPlanModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">+ Add Plan</button>
      </div>

      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
        <table v-else class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Title</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Description</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Start Date</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">End Date</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Status</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="6" class="text-center py-10 text-gray-400">No financial plans yet</td>
            </tr>
            <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-700">{{ item.title }}</td>
              <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ item.description ?? '—' }}</td>
              <td class="px-4 py-3 text-gray-500">{{ formatDate(item.start_date) }}</td>
              <td class="px-4 py-3 text-gray-500">{{ formatDate(item.end_date) }}</td>
              <td class="px-4 py-3">
                <span class="text-xs px-2 py-1 rounded-full capitalize" :class="planStatusClass(item.status)">{{ item.status ?? 'active' }}</span>
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-2 justify-end">
                  <button @click="openPlanModal(item)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">Edit</button>
                  <button @click="confirmDeletePlan(item)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Goals Section -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-700">Goals</h2>
        <button @click="openGoalModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm font-medium">+ Add Goal</button>
      </div>

      <div v-if="store.goals.length === 0 && !store.loading" class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
        No goals set yet
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="goal in store.goals" :key="goal.id" class="bg-white rounded-xl shadow-sm p-5">
          <div class="flex items-start justify-between mb-3">
            <div>
              <h3 class="font-semibold text-gray-800">{{ goal.title }}</h3>
              <p v-if="goal.description" class="text-xs text-gray-400 mt-0.5">{{ goal.description }}</p>
            </div>
            <div class="flex gap-2">
              <button @click="openGoalModal(goal)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">Edit</button>
              <button @click="confirmDeleteGoal(goal)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
            </div>
          </div>
          <div class="flex justify-between text-sm mb-1">
            <span class="text-gray-600">{{ formatCurrency(goal.current_amount) }} saved</span>
            <span class="font-medium text-gray-700">{{ formatCurrency(goal.target_amount) }} goal</span>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-3 mb-2">
            <div
              class="h-3 rounded-full transition-all"
              :class="goalPercent(goal) >= 100 ? 'bg-green-500' : 'bg-blue-500'"
              :style="{ width: Math.min(100, goalPercent(goal)) + '%' }"
            ></div>
          </div>
          <div class="flex justify-between text-xs text-gray-400">
            <span>{{ goalPercent(goal).toFixed(1) }}% complete</span>
            <span v-if="goal.target_date">Target: {{ formatDate(goal.target_date) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Plan Add/Edit Modal -->
    <div v-if="showPlanModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editingPlan ? 'Edit Plan' : 'Add Plan' }}</h2>
          <button @click="showPlanModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handlePlanSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input v-model="planForm.title" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="planForm.description" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input v-model="planForm.start_date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
            <input v-model="planForm.end_date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select v-model="planForm.status" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="active">Active</option>
              <option value="completed">Completed</option>
              <option value="paused">Paused</option>
            </select>
          </div>
          <div v-if="formError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showPlanModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-blue-700">
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Goal Add/Edit Modal -->
    <div v-if="showGoalModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editingGoal ? 'Edit Goal' : 'Add Goal' }}</h2>
          <button @click="showGoalModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleGoalSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input v-model="goalForm.title" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="e.g. Emergency Fund, Vacation" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="goalForm.description" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" rows="2"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Target Amount *</label>
            <input v-model="goalForm.target_amount" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Current Amount Saved</label>
            <input v-model="goalForm.current_amount" type="number" min="0" step="0.01" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Target Date</label>
            <input v-model="goalForm.target_date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500" />
          </div>
          <div v-if="formError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showGoalModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
            <button type="submit" :disabled="saving" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm disabled:opacity-50 hover:bg-green-700">
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirm (Plan) -->
    <div v-if="deletePlanTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Plan</h3>
        <p class="text-sm text-gray-500 mb-4">Delete "{{ deletePlanTarget.title }}"? This cannot be undone.</p>
        <div class="flex justify-end gap-3">
          <button @click="deletePlanTarget = null" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handleDeletePlan" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirm (Goal) -->
    <div v-if="deleteGoalTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 max-w-sm w-full shadow-xl">
        <h3 class="font-semibold text-gray-800 mb-2">Delete Goal</h3>
        <p class="text-sm text-gray-500 mb-4">Delete goal "{{ deleteGoalTarget.title }}"? This cannot be undone.</p>
        <div class="flex justify-end gap-3">
          <button @click="deleteGoalTarget = null" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
          <button @click="handleDeleteGoal" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { usePlanStore } from '@/stores/plan';
import { formatDate } from '@/utils/date';

const store = usePlanStore();
const saving = ref(false);
const formError = ref('');

// Plans
const showPlanModal = ref(false);
const editingPlan = ref(null);
const deletePlanTarget = ref(null);
const defaultPlanForm = () => ({ title: '', description: '', start_date: '', end_date: '', status: 'active' });
const planForm = ref(defaultPlanForm());

// Goals
const showGoalModal = ref(false);
const editingGoal = ref(null);
const deleteGoalTarget = ref(null);
const defaultGoalForm = () => ({ title: '', description: '', target_amount: '', current_amount: '0', target_date: '' });
const goalForm = ref(defaultGoalForm());

function formatCurrency(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function goalPercent(goal) {
  if (!goal.target_amount || goal.target_amount === 0) return 0;
  return (Number(goal.current_amount || 0) / Number(goal.target_amount)) * 100;
}

function planStatusClass(status) {
  return {
    active: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
    paused: 'bg-yellow-100 text-yellow-700',
  }[status] ?? 'bg-gray-100 text-gray-700';
}

function openPlanModal(item = null) {
  editingPlan.value = item;
  planForm.value = item
    ? {
        ...item,
        start_date: item.start_date?.split('T')[0] ?? item.start_date ?? '',
        end_date: item.end_date?.split('T')[0] ?? item.end_date ?? '',
      }
    : defaultPlanForm();
  formError.value = '';
  showPlanModal.value = true;
}

function openGoalModal(item = null) {
  editingGoal.value = item;
  goalForm.value = item
    ? { ...item, target_date: item.target_date?.split('T')[0] ?? item.target_date ?? '' }
    : defaultGoalForm();
  formError.value = '';
  showGoalModal.value = true;
}

function confirmDeletePlan(item) { deletePlanTarget.value = item; }
function confirmDeleteGoal(item) { deleteGoalTarget.value = item; }

async function handlePlanSubmit() {
  saving.value = true;
  formError.value = '';
  try {
    if (editingPlan.value) {
      await store.update(editingPlan.value.id, planForm.value);
    } else {
      await store.create(planForm.value);
    }
    showPlanModal.value = false;
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save. Please try again.';
  } finally {
    saving.value = false;
  }
}

async function handleGoalSubmit() {
  saving.value = true;
  formError.value = '';
  try {
    if (editingGoal.value) {
      await store.updateGoal(editingGoal.value.id, goalForm.value);
    } else {
      await store.createGoal(goalForm.value);
    }
    showGoalModal.value = false;
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save. Please try again.';
  } finally {
    saving.value = false;
  }
}

async function handleDeletePlan() {
  await store.remove(deletePlanTarget.value.id);
  deletePlanTarget.value = null;
}

async function handleDeleteGoal() {
  await store.removeGoal(deleteGoalTarget.value.id);
  deleteGoalTarget.value = null;
}

onMounted(async () => {
  store.fetchAll();
  store.fetchGoals();
});
</script>
