<template>
  <div class="space-y-6">
    <!-- Filter Card with Header & Add Button -->
    <div class="bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-700">
      <div class="flex justify-between items-start mb-4">
        <div>
          <h1 class="text-xl font-bold text-white">Products</h1>
          <p class="text-gray-400 mt-1">Manage your product catalog</p>
        </div>
        <ButtonPrimary @click="openCreateModal">
          <Plus class="w-4 h-4" />
          Add Product
        </ButtonPrimary>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-400 mb-2">Search</label>
          <input v-model="filters.search" @input="debouncedSearch" type="text" placeholder="Product name or SKU..." class="w-full border border-gray-600 rounded-lg px-4 py-2 bg-gray-700 text-white" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-400 mb-2">Category</label>
          <select v-model="filters.category_id" @change="tableRef?.refresh()" class="w-full border border-gray-600 rounded-lg px-4 py-2 bg-gray-700 text-white">
            <option value="">All Categories</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-400 mb-2">Status</label>
          <select v-model="filters.is_active" @change="tableRef?.refresh()" class="w-full border border-gray-600 rounded-lg px-4 py-2 bg-gray-700 text-white">
            <option value="">All</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Products Table -->
    <DataTable ref="tableRef" :columns="columns" :fetch-data="fetchProducts" search-placeholder="" empty-message="No products found" :show-toolbar="false">
      <template #cell-name="{ item }">
        <div class="font-semibold text-white">{{ item.name }}</div>
      </template>

      <template #cell-sku="{ item }">
        <code class="text-sm text-gray-400 bg-gray-700 px-2 py-0.5 rounded">{{ item.sku }}</code>
      </template>

      <template #cell-category_id="{ item }">
        <span class="text-gray-300">{{ item.category?.name || '-' }}</span>
      </template>

      <template #cell-price="{ item }">
        <span class="font-semibold text-white">Rp {{ formatPrice(item.current_price || 0) }}</span>
      </template>

      <template #cell-cost="{ item }">
        <span class="text-gray-400">Rp {{ formatPrice(item.current_cost || 0) }}</span>
      </template>

      <template #cell-is_active="{ item }">
        <span v-if="item.is_active" class="badge badge-success">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active
        </span>
        <span v-else class="badge badge-danger">
          <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Inactive
        </span>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <button @click="openEditModal(item)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600/20 text-indigo-400 hover:bg-indigo-600/30 rounded-lg text-sm font-medium transition-colors">
            <Edit class="w-4 h-4" />Edit
          </button>
          <button @click="confirmDelete(item)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600/20 text-red-400 hover:bg-red-600/30 rounded-lg text-sm font-medium transition-colors">
            <Trash class="w-4 h-4" />Delete
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Product Modal -->
    <FormModal v-model="showModal" :title="isEdit ? 'Edit Product' : 'Create Product'" @submit="handleSubmit" submit-text="Save">
      <template #default="{ loading }">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-300 mb-2">Product Name *</label>
            <input v-model="form.name" type="text" required class="input-modern" placeholder="e.g., Latte" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">SKU *</label>
            <input v-model="form.sku" type="text" required class="input-modern" placeholder="e.g., LAT001" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Category</label>
            <SearchSelect v-model="form.category_id" :options="categories" placeholder="Select category" search-placeholder="Search categories..." option-label="name" option-value="id" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Price (Rp) *</label>
            <CurrencyInput v-model="form.price" required placeholder="25000" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Cost (Rp)</label>
            <CurrencyInput v-model="form.cost" placeholder="12000" />
          </div>
          <div class="col-span-2">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="w-4 h-4 text-indigo-600 rounded border-gray-600" />
              <span class="text-sm text-gray-300">Active</span>
            </label>
          </div>
        </div>
      </template>
    </FormModal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import DataTable from '../../components/tables/DataTable.vue';
import FormModal from '../../components/forms/FormModal.vue';
import CurrencyInput from '../../components/forms/CurrencyInput.vue';
import SearchSelect from '../../components/forms/SearchSelect.vue';
import ButtonPrimary from '../../components/ui/ButtonPrimary.vue';
import AlertService from '../../components/alerts/AlertService.js';
import { Plus, Edit, Trash } from 'lucide-vue-next';

const tableRef = ref(null);
const showModal = ref(false);
const isEdit = ref(false);
const categories = ref([]);
const selectedProduct = ref(null);

const filters = reactive({ search: '', category_id: '', is_active: '' });

const columns = {
  name: { label: 'Product', sortable: true },
  sku: { label: 'SKU', sortable: true },
  category_id: { label: 'Category', sortable: true },
  price: { label: 'Price', sortable: true },
  cost: { label: 'Cost' },
  is_active: { label: 'Status', sortable: true }
};

const form = reactive({ name: '', sku: '', category_id: '', price: 0, cost: 0, is_active: true });

let searchTimeout;
function debouncedSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => tableRef.value?.refresh(), 300);
}

async function fetchProducts(params) {
  const token = localStorage.getItem('token');
  const response = await axios.get('/api/v1/products', {
    headers: { Authorization: `Bearer ${token}` },
    params: { ...params, search: filters.search, category_id: filters.category_id, is_active: filters.is_active }
  });
  return response.data.data;
}

async function loadCategories() {
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get('/api/v1/categories', { headers: { Authorization: `Bearer ${token}` } });
    categories.value = response.data.data;
  } catch (e) { console.error('Failed to load categories:', e); }
}

function openCreateModal() {
  isEdit.value = false;
  selectedProduct.value = null;
  Object.assign(form, { name: '', sku: '', category_id: '', price: 0, cost: 0, is_active: true });
  loadCategories();
  showModal.value = true;
}

function openEditModal(product) {
  isEdit.value = true;
  selectedProduct.value = product;
  Object.assign(form, {
    name: product.name,
    sku: product.sku,
    category_id: product.category_id || '',
    price: product.current_price || 0,
    cost: product.current_cost || 0,
    is_active: product.is_active
  });
  loadCategories();
  showModal.value = true;
}

async function handleSubmit({ setLoading, setError }) {
  try {
    const token = localStorage.getItem('token');
    const config = { headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' } };
    const payload = { ...form, price: parseInt(form.price) || 0, cost: parseInt(form.cost) || 0 };

    if (isEdit.value) {
      await axios.put(`/api/v1/products/${selectedProduct.value.id}`, payload, config);
      AlertService.success('Product updated successfully');
    } else {
      await axios.post('/api/v1/products', payload, config);
      AlertService.success('Product created successfully');
    }
    showModal.value = false;
    tableRef.value?.refresh();
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to save product');
  } finally {
    setLoading(false);
  }
}

async function confirmDelete(product) {
  if (!await AlertService.deleteConfirm(`"${product.name}"`)) return;
  try {
    const token = localStorage.getItem('token');
    await axios.delete(`/api/v1/products/${product.id}`, { headers: { Authorization: `Bearer ${token}` } });
    AlertService.success('Product deleted successfully');
    tableRef.value?.refresh();
  } catch (e) {
    AlertService.error('Failed to delete product');
  }
}

function formatPrice(price) { return new Intl.NumberFormat('id-ID').format(price); }

onMounted(() => { loadCategories(); });
</script>
