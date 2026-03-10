<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">Products</h1>
      <ButtonPrimary @click="openCreateModal">
        <Plus class="w-4 h-4" />
        Add Product
      </ButtonPrimary>
    </div>

    <DataTable ref="tableRef" :columns="columns" :fetch-data="fetchProducts" search-placeholder="Search products..." empty-message="No products found">
      <template #toolbar>
        <ButtonSecondary @click="refreshCategories">Refresh</ButtonSecondary>
      </template>

      <template #cell-name="{ item }">
        <div class="font-medium text-gray-900">{{ item.name }}</div>
      </template>

      <template #cell-sku="{ item }">
        <code class="text-sm text-gray-500">{{ item.sku }}</code>
      </template>

      <template #cell-category_id="{ item }">
        {{ item.category?.name || '-' }}
      </template>

      <template #cell-price="{ item }">
        <span class="font-medium">Rp {{ formatPrice(item.prices?.[0]?.price || 0) }}</span>
      </template>

      <template #cell-cost="{ item }">
        <span class="text-gray-500">Rp {{ formatPrice(item.costs?.[0]?.cost || 0) }}</span>
      </template>

      <template #cell-is_active="{ item }">
        <span :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 text-xs font-medium rounded-full">{{ item.is_active ? 'Active' : 'Inactive' }}</span>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <ButtonIcon variant="primary" @click="openEditModal(item)">
            <Edit class="w-5 h-5" />
          </ButtonIcon>
          <ButtonIcon variant="danger" @click="confirmDelete(item)">
            <Trash class="w-5 h-5" />
          </ButtonIcon>
        </div>
      </template>
    </DataTable>

    <!-- Product Modal -->
    <FormModal v-model="showModal" :title="isEdit ? 'Edit Product' : 'Create Product'" @submit="handleSubmit" submit-text="Save">
      <template #default="{ loading }">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
            <input v-model="form.name" type="text" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="e.g., Latte" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">SKU *</label>
            <input v-model="form.sku" type="text" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="e.g., LAT001" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <SearchSelect
              v-model="form.category_id"
              :options="categories"
              placeholder="Select category"
              search-placeholder="Search categories..."
              option-label="name"
              option-value="id"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Price (Rp) *</label>
            <CurrencyInput v-model="form.price" required placeholder="25000" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Cost (Rp)</label>
            <CurrencyInput v-model="form.cost" placeholder="12000" />
          </div>
          <div class="col-span-2">
            <label class="flex items-center"><input v-model="form.is_active" type="checkbox" class="w-4 h-4 text-indigo-600 rounded" /><span class="ml-2 text-sm text-gray-700">Active</span></label>
          </div>
        </div>
      </template>
    </FormModal>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import DataTable from '../../components/tables/DataTable.vue';
import FormModal from '../../components/forms/FormModal.vue';
import CurrencyInput from '../../components/forms/CurrencyInput.vue';
import SearchSelect from '../../components/forms/SearchSelect.vue';
import ButtonPrimary from '../../components/ui/ButtonPrimary.vue';
import ButtonSecondary from '../../components/ui/ButtonSecondary.vue';
import ButtonIcon from '../../components/ui/ButtonIcon.vue';
import AlertService from '../../components/alerts/AlertService.js';
import { Plus, Edit, Trash } from 'lucide-vue-next';

const tableRef = ref(null);
const showModal = ref(false);
const isEdit = ref(false);
const categories = ref([]);
const selectedProduct = ref(null);

const columns = {
  name: { label: 'Product', sortable: true },
  sku: { label: 'SKU', sortable: true },
  category_id: { label: 'Category', sortable: true },
  price: { label: 'Price', sortable: true, format: 'currency' },
  cost: { label: 'Cost', format: 'currency' },
  is_active: { label: 'Status', sortable: true }
};

const form = reactive({ name: '', sku: '', category_id: '', price: 0, cost: 0, is_active: true });

async function fetchProducts(params) {
  const token = localStorage.getItem('token');
  const response = await axios.get('/api/v1/products', { headers: { Authorization: `Bearer ${token}` }, params });
  return response.data.data;
}

async function refreshCategories() {
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
  refreshCategories();
  showModal.value = true;
}

function openEditModal(product) {
  isEdit.value = true;
  selectedProduct.value = product;
  Object.assign(form, {
    name: product.name,
    sku: product.sku,
    category_id: product.category_id || '',
    price: product.prices?.[0]?.price || 0,
    cost: product.costs?.[0]?.cost || 0,
    is_active: product.is_active
  });
  refreshCategories();
  showModal.value = true;
}

async function handleSubmit({ setLoading, setError }) {
  try {
    const token = localStorage.getItem('token');
    const config = { headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' } };
    
    // Ensure price and cost are raw numbers (not formatted)
    const payload = {
      ...form,
      price: parseInt(form.price) || 0,
      cost: parseInt(form.cost) || 0
    };
    
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
</script>
