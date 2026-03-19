<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-800">Purchases</h1>
      <button @click="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">+ Add Purchase</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="store.loading" class="text-center py-10 text-gray-400">Loading...</div>
      <div class="overflow-x-auto">
        <table v-if="!store.loading" class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Item</th>
              <th class="text-right px-4 py-3 text-gray-500 font-medium">Total Cost</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Type</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Installments</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Purchase Date</th>
              <th class="text-left px-4 py-3 text-gray-500 font-medium">Status</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="store.items.length === 0">
              <td colspan="7" class="text-center py-10 text-gray-400">No purchases found</td>
            </tr>
            <tr v-for="item in store.items" :key="item.id" class="border-b last:border-0 hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-700">
                <div>{{ item.item_name }}</div>
                <div v-if="item.description" class="text-xs text-gray-400">{{ item.description }}</div>
              </td>
              <td class="px-4 py-3 text-right font-semibold text-gray-700">{{ formatCurrency(item.total_cost) }}</td>
              <td class="px-4 py-3">
                <span v-if="item.is_installment" class="bg-purple-100 text-purple-700 text-xs px-2 py-1 rounded-full">Installment</span>
                <span v-else class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">Full Pay</span>
              </td>
              <td class="px-4 py-3 text-gray-500">
                <span v-if="item.is_installment">
                  {{ item.paid_installments ?? 0 }} / {{ item.total_installments }} paid
                  <span class="text-xs text-gray-400">({{ formatCurrency(item.installment_amount) }}/mo)</span>
                </span>
                <span v-else class="text-gray-400 text-xs">N/A</span>
              </td>
              <td class="px-4 py-3 text-gray-500">{{ item.purchase_date ?? '—' }}</td>
              <td class="px-4 py-3">
                <span class="text-xs px-2 py-1 rounded-full" :class="statusClass(item)">
                  {{ itemStatus(item) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-2 justify-end flex-wrap">
                  <button
                    v-if="item.is_installment && (item.paid_installments ?? 0) < item.total_installments"
                    @click="handlePayInstallment(item)"
                    class="text-green-600 hover:text-green-800 text-xs px-2 py-1 border border-green-300 rounded bg-green-50 hover:bg-green-100"
                  >Pay Installment</button>
                  <button @click="openModal(item)" class="text-blue-500 hover:text-blue-700 text-xs px-2 py-1 border rounded">Edit</button>
                  <button @click="confirmDelete(item)" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 border rounded">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="store.pagination" class="flex justify-between items-center text-sm text-gray-500">
      <span>{{ store.pagination.total }} records</span>
      <div class="flex gap-2">
        <button
          :disabled="store.pagination.current_page <= 1"
          @click="changePage(store.pagination.current_page - 1)"
          class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100"
        >Prev</button>
        <span class="px-3 py-1">{{ store.pagination.current_page }} / {{ store.pagination.last_page }}</span>
        <button
          :disabled="store.pagination.current_page >= store.pagination.last_page"
          @click="changePage(store.pagination.current_page + 1)"
          class="px-3 py-1 border rounded disabled:opacity-40 hover:bg-gray-100"
        >Next</button>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b">
          <h2 class="font-semibold text-gray-800">{{ editing ? 'Edit Purchase' : 'Add Purchase' }}</h2>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form @submit.prevent="handleSubmit" class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Item Name *</label>
            <input v-model="form.item_name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Laptop, Refrigerator" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Total Cost *</label>
            <input v-model="form.total_cost" type="number" min="0" step="0.01" required class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
            <input v-model="form.purchase_date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.is_installment" type="checkbox" id="installment" class="rounded" />
            <label for="installment" class="text-sm text-gray-700">Installment purchase</label>
          </div>
          <template v-if="form.is_installment">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Total Installments *</label>
              <input v-model="form.total_installments" type="number" min="1" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. 12" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Installment Amount</label>
              <input v-model="form.installment_amount" type="number" min="0" step="0.01" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </template>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" rows="2"></textarea>
          </div>
          <div v-if="formError" class="text-red-600 text-sm bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
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
        <h3 class="font-semibold text-gray-800 mb-2">Delete Purchase</h3>
        <p class="text-sm text-gray-500 mb-4">Delete "{{ deleteTarget.item_name }}"? This cannot be undone.</p>
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
import { usePurchaseStore } from '@/stores/purchase';

const store = usePurchaseStore();
const showModal = ref(false);
const editing = ref(null);
const deleteTarget = ref(null);
const saving = ref(false);
const formError = ref('');

const defaultForm = () => ({
  item_name: '',
  total_cost: '',
  purchase_date: new Date().toISOString().split('T')[0],
  is_installment: false,
  total_installments: '',
  installment_amount: '',
  description: '',
});

const form = ref(defaultForm());

function formatCurrency(val) {
  return '₱' + Number(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function itemStatus(item) {
  if (!item.is_installment) return 'Paid';
  const paid = item.paid_installments ?? 0;
  if (paid >= item.total_installments) return 'Completed';
  return `${paid}/${item.total_installments} paid`;
}

function statusClass(item) {
  if (!item.is_installment) return 'bg-green-100 text-green-700';
  const paid = item.paid_installments ?? 0;
  if (paid >= item.total_installments) return 'bg-green-100 text-green-700';
  return 'bg-yellow-100 text-yellow-700';
}

function openModal(item = null) {
  editing.value = item;
  form.value = item
    ? { ...item, purchase_date: item.purchase_date?.split('T')[0] ?? item.purchase_date ?? '' }
    : defaultForm();
  formError.value = '';
  showModal.value = true;
}

function confirmDelete(item) {
  deleteTarget.value = item;
}

async function handlePayInstallment(item) {
  try {
    await store.payInstallment(item.id);
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to record payment.');
  }
}

async function handleSubmit() {
  saving.value = true;
  formError.value = '';
  try {
    if (editing.value) {
      await store.update(editing.value.id, form.value);
    } else {
      await store.create(form.value);
    }
    showModal.value = false;
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save. Please try again.';
  } finally {
    saving.value = false;
  }
}

async function handleDelete() {
  await store.remove(deleteTarget.value.id);
  deleteTarget.value = null;
}

function changePage(page) {
  store.fetchAll({ page });
}

onMounted(() => store.fetchAll());
</script>
