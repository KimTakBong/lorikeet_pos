<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Expenses</h1>
        <p class="text-gray-500 mt-1">Track business expenses</p>
      </div>
      <ButtonPrimary @click="openCreateModal">
        <Plus class="w-4 h-4" />
        Add Expense
      </ButtonPrimary>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="dark:bg-gray-800 bg-white rounded-xl shadow-sm p-6">
        <div class="text-sm text-gray-500 mb-1">Total This Month</div>
        <div class="text-2xl font-bold text-gray-900">Rp {{ formatPrice(summary.total) }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ summary.count }} transactions</div>
      </div>
      <div class="dark:bg-gray-800 bg-white rounded-xl shadow-sm p-6">
        <div class="text-sm text-gray-500 mb-1">Average per Transaction</div>
        <div class="text-2xl font-bold text-gray-900">Rp {{ formatPrice(summary.count > 0 ? summary.total / summary.count : 0) }}</div>
      </div>
      <div class="dark:bg-gray-800 bg-white rounded-xl shadow-sm p-6">
        <div class="text-sm text-gray-500 mb-1">Top Category</div>
        <div class="text-lg font-bold text-gray-900">{{ topCategory }}</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="dark:bg-gray-800 bg-white rounded-xl shadow-sm p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
          <input v-model="filters.search" @input="debouncedSearch" type="text" placeholder="Description..." class="w-full border border-gray-300 rounded-lg px-4 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
          <select v-model="filters.category_id" @change="tableRef?.refresh()" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            <option value="">All Categories</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
          <input v-model="filters.date_from" @change="tableRef?.refresh()" type="date" class="w-full border border-gray-300 rounded-lg px-4 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
          <input v-model="filters.date_to" @change="tableRef?.refresh()" type="date" class="w-full border border-gray-300 rounded-lg px-4 py-2" />
        </div>
      </div>
    </div>

    <!-- Expenses Table -->
    <DataTable ref="tableRef" :columns="columns" :fetch-data="fetchExpenses" search-placeholder="" empty-message="No expenses found">
      <template #cell-description="{ item }">
        <div class="font-medium text-gray-900">{{ item.description }}</div>
        <div class="text-xs text-gray-500">{{ item.category?.name || 'Uncategorized' }}</div>
      </template>

      <template #cell-amount="{ item }">
        <div class="font-medium text-red-600">- Rp {{ formatPrice(item.amount) }}</div>
      </template>

      <template #cell-expense_date="{ item }">
        <div class="text-sm">{{ formatDate(item.expense_date) }}</div>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <ButtonIcon variant="primary" @click="openEditModal(item)" title="Edit Expense">
            <Edit class="w-5 h-5" />
          </ButtonIcon>
          <ButtonIcon variant="danger" @click="confirmDelete(item)" title="Delete Expense">
            <Trash class="w-5 h-5" />
          </ButtonIcon>
        </div>
      </template>
    </DataTable>

    <!-- Expense Modal -->
    <FormModal v-model="showModal" :title="isEdit ? 'Edit Expense' : 'Add Expense'" @submit="handleSubmit" submit-text="Save">
      <template #default="{ loading }">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
            <select v-model="form.category_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
              <option value="">Select Category</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Amount (Rp) *</label>
            <CurrencyInput v-model="form.amount" required placeholder="0" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
            <textarea v-model="form.description" required rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="Expense description..."></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
            <input v-model="form.expense_date" type="date" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" />
          </div>
        </div>
      </template>
    </FormModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import DataTable from '../../components/tables/DataTable.vue';
import FormModal from '../../components/forms/FormModal.vue';
import CurrencyInput from '../../components/forms/CurrencyInput.vue';
import ButtonPrimary from '../../components/ui/ButtonPrimary.vue';
import ButtonIcon from '../../components/ui/ButtonIcon.vue';
import AlertService from '../../components/alerts/AlertService.js';
import { Plus, Edit, Trash } from 'lucide-vue-next';

const tableRef = ref(null);
const showModal = ref(false);
const isEdit = ref(false);
const selectedExpense = ref(null);
const categories = ref([]);
const summary = ref({ total: 0, count: 0 });

const filters = reactive({
  search: '',
  category_id: '',
  date_from: new Date(new Date().setDate(1)).toISOString().split('T')[0],
  date_to: new Date().toISOString().split('T')[0]
});

const columns = {
  description: { label: 'Description', sortable: true },
  amount: { label: 'Amount', sortable: true },
  expense_date: { label: 'Date', sortable: true }
};

const form = reactive({
  category_id: '',
  amount: 0,
  description: '',
  expense_date: new Date().toISOString().split('T')[0]
});

const topCategory = computed(() => {
  if (summary.byCategory && summary.byCategory.length > 0) {
    return summary.byCategory[0]?.category || '-';
  }
  return '-';
});

let searchTimeout;
function debouncedSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => tableRef.value?.refresh(), 300);
}

async function fetchExpenses(params) {
  const token = localStorage.getItem('token');
  const searchParams = { ...params, ...filters };
  
  const response = await axios.get('/api/v1/expenses', { 
    headers: { Authorization: `Bearer ${token}` }, 
    params: searchParams 
  });
  
  const result = response.data;
  const paginator = result.data;
  const expenses = paginator.data || [];
  
  return { 
    data: expenses, 
    current_page: paginator.current_page || 1, 
    last_page: paginator.last_page || 1, 
    from: paginator.from || 1, 
    to: paginator.to || expenses.length, 
    total: paginator.total || expenses.length 
  };
}

async function loadCategories() {
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get('/api/v1/expense-categories', {
      headers: { Authorization: `Bearer ${token}` }
    });
    categories.value = response.data.data || [];
  } catch (e) {
    console.error('Failed to load categories:', e);
  }
}

async function loadSummary() {
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get('/api/v1/expenses-summary', {
      headers: { Authorization: `Bearer ${token}` },
      params: { date_from: filters.date_from, date_to: filters.date_to }
    });
    summary.value = response.data.data || { total: 0, count: 0, byCategory: [] };
  } catch (e) {
    console.error('Failed to load summary:', e);
  }
}

function openCreateModal() {
  isEdit.value = false;
  selectedExpense.value = null;
  Object.assign(form, {
    category_id: categories.value[0]?.id || '',
    amount: 0,
    description: '',
    expense_date: new Date().toISOString().split('T')[0]
  });
  showModal.value = true;
}

function openEditModal(expense) {
  isEdit.value = true;
  selectedExpense.value = expense;
  Object.assign(form, {
    category_id: expense.category_id,
    amount: expense.amount,
    description: expense.description,
    expense_date: expense.expense_date?.split('T')[0] || ''
  });
  showModal.value = true;
}

async function handleSubmit({ setLoading, setError }) {
  try {
    const token = localStorage.getItem('token');
    const payload = {
      ...form,
      amount: parseInt(form.amount) || 0
    };
    
    if (isEdit.value) {
      await axios.put(`/api/v1/expenses/${selectedExpense.value.id}`, payload, {
        headers: { Authorization: `Bearer ${token}` }
      });
      AlertService.success('Expense updated successfully');
    } else {
      await axios.post('/api/v1/expenses', payload, {
        headers: { Authorization: `Bearer ${token}` }
      });
      AlertService.success('Expense created successfully');
    }
    showModal.value = false;
    tableRef.value?.refresh();
    loadSummary();
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to save expense');
  } finally {
    setLoading(false);
  }
}

async function confirmDelete(expense) {
  if (!await AlertService.deleteConfirm(`"${expense.description}"`)) return;
  try {
    const token = localStorage.getItem('token');
    await axios.delete(`/api/v1/expenses/${expense.id}`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    AlertService.success('Expense deleted successfully');
    tableRef.value?.refresh();
    loadSummary();
  } catch (e) {
    AlertService.error('Failed to delete expense');
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price);
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

onMounted(() => {
  loadCategories();
  loadSummary();
});
</script>
