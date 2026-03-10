<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">Inventory</h1>
      <div class="flex gap-2">
        <ButtonSecondary @click="showMovementsModal = true">
          <History class="w-4 h-4" />
          Stock History
        </ButtonSecondary>
        <ButtonPrimary @click="openRestockModal">
          <Package class="w-4 h-4" />
          Restock
        </ButtonPrimary>
      </div>
    </div>

    <DataTable ref="tableRef" :columns="columns" :fetch-data="fetchStockLevels" search-placeholder="Search products..." empty-message="No products found">
      <template #toolbar>
        <select v-model="lowStockFilter" @change="tableRef?.refresh()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
          <option value="">All Stock</option>
          <option value="10">Low Stock (< 10)</option>
          <option value="20">Low Stock (< 20)</option>
          <option value="50">Low Stock (< 50)</option>
        </select>
      </template>

      <template #cell-name="{ item }">
        <div class="font-medium text-gray-900">{{ item.name }}</div>
        <div class="text-xs text-gray-500">{{ item.sku }}</div>
      </template>

      <template #cell-category_id="{ item }">
        {{ item.category?.name || '-' }}
      </template>

      <template #cell-current_stock="{ item }">
        <span :class="getStockClass(item.current_stock)" class="px-2 py-1 text-xs font-medium rounded-full">
          {{ item.current_stock }}
        </span>
      </template>

      <template #cell-price="{ item }">
        <span class="font-medium">Rp {{ formatPrice(item.prices?.[0]?.price || 0) }}</span>
      </template>

      <template #actions="{ item }">
        <div class="flex justify-end gap-2">
          <ButtonIcon variant="primary" @click="openAdjustModal(item)" title="Adjust Stock">
            <Edit class="w-5 h-5" />
          </ButtonIcon>
          <ButtonIcon @click="viewMovements(item)" title="View History">
            <History class="w-5 h-5" />
          </ButtonIcon>
        </div>
      </template>
    </DataTable>

    <!-- Stock Adjustment Modal -->
    <FormModal v-model="showAdjustModal" title="Adjust Stock" @submit="handleAdjustSubmit" submit-text="Adjust">
      <template #default="{ loading }">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
            <input :value="selectedProduct?.name" disabled class="w-full bg-gray-100 border border-gray-300 rounded-lg px-4 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Current Stock</label>
            <input :value="selectedProduct?.current_stock || 0" disabled class="w-full bg-gray-100 border border-gray-300 rounded-lg px-4 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Quantity *</label>
            <input v-model.number="adjustForm.quantity" type="number" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="+100 or -50" />
            <p class="text-xs text-gray-500 mt-1">Use positive for add, negative for reduce</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
            <textarea v-model="adjustForm.reason" required rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="e.g., Stock count correction, damaged items"></textarea>
          </div>
        </div>
      </template>
    </FormModal>

    <!-- Restock Modal -->
    <FormModal v-model="showRestockModal" title="Restock Products" @submit="handleRestockSubmit" submit-text="Restock">
      <template #default="{ loading }">
        <div class="space-y-4">
          <div>
            <SearchSelect
              v-model="restockForm.supplier_id"
              :options="suppliers"
              label="Supplier *"
              placeholder="Select supplier"
              search-placeholder="Search suppliers..."
              option-label="name"
              option-value="id"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Number</label>
            <input v-model="restockForm.invoice_number" type="text" class="w-full border border-gray-300 rounded-lg px-4 py-2" placeholder="Auto-generated if empty" />
          </div>
          <div class="border-t pt-4">
            <div class="flex justify-between items-center mb-2">
              <label class="block text-sm font-medium text-gray-700">Products to Restock</label>
              <ButtonSecondary size="sm" @click="addRestockItem">+ Add Product</ButtonSecondary>
            </div>
            <div v-for="(item, index) in restockForm.items" :key="index" class="flex gap-2 mb-2">
              <div class="flex-1">
                <label class="block text-xs font-medium text-gray-600 mb-1">Product</label>
                <SearchSelect
                  v-model="item.product_id"
                  :options="products"
                  placeholder="Select product"
                  search-placeholder="Search products..."
                  option-label="name"
                  option-value="id"
                  :clearable="false"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Qty</label>
                <input v-model.number="item.quantity" type="number" min="1" placeholder="0" class="w-20 border border-gray-300 rounded-lg px-3 py-2" />
              </div>
              <div class="flex-1">
                <label class="block text-xs font-medium text-gray-600 mb-1">Cost (Rp)</label>
                <CurrencyInput v-model="item.cost_price" placeholder="0" :prefix="''" />
              </div>
              <ButtonIcon variant="danger" size="sm" @click="removeRestockItem(index)" class="self-end">
                <Trash class="w-4 h-4" />
              </ButtonIcon>
            </div>
          </div>
        </div>
      </template>
    </FormModal>

    <!-- Stock Movements Modal -->
    <div v-if="showMovementsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="showMovementsModal = false">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">Stock Movement History</h2>
          <button @click="showMovementsModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
          <DataTable ref="movementsTableRef" :columns="movementColumns" :fetch-data="fetchMovements" search-placeholder="Search movements..." empty-message="No movements found" />
        </div>
      </div>
    </div>
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
import { Package, History, Edit, Trash } from 'lucide-vue-next';

const tableRef = ref(null);
const movementsTableRef = ref(null);
const showAdjustModal = ref(false);
const showRestockModal = ref(false);
const showMovementsModal = ref(false);
const selectedProduct = ref(null);
const lowStockFilter = ref('');
const suppliers = ref([]);
const products = ref([]);

const columns = {
  name: { label: 'Product', sortable: true },
  category_id: { label: 'Category', sortable: true },
  current_stock: { label: 'Stock', sortable: true },
  price: { label: 'Price', sortable: true, format: 'currency' }
};

const movementColumns = {
  product_name: { label: 'Product' },
  movement_type: { label: 'Type' },
  quantity: { label: 'Quantity' },
  created_at: { label: 'Date' }
};

const adjustForm = reactive({ quantity: 0, reason: '' });
const restockForm = reactive({ supplier_id: '', invoice_number: '', items: [{ product_id: '', quantity: 1, cost_price: 0 }] });

async function fetchStockLevels(params) {
  const token = localStorage.getItem('token');
  const searchParams = { ...params };
  if (lowStockFilter.value) searchParams.low_stock = lowStockFilter.value;
  const response = await axios.get('/api/v1/inventory', { headers: { Authorization: `Bearer ${token}` }, params: searchParams });
  const data = response.data.data;
  data.data = data.data.map(item => ({ ...item, current_stock: parseInt(item.current_stock) || 0 }));
  return data;
}

async function fetchMovements(params) {
  const token = localStorage.getItem('token');
  const response = await axios.get('/api/v1/inventory/movements', { headers: { Authorization: `Bearer ${token}` }, params });
  const data = response.data.data;
  data.data = data.data.map(m => ({ ...m, product_name: m.product?.name || '-' }));
  return data;
}

function getStockClass(stock) {
  if (stock <= 0) return 'bg-red-100 text-red-800';
  if (stock < 10) return 'bg-orange-100 text-orange-800';
  if (stock < 20) return 'bg-yellow-100 text-yellow-800';
  return 'bg-green-100 text-green-800';
}

function openAdjustModal(product) {
  selectedProduct.value = product;
  adjustForm.quantity = 0;
  adjustForm.reason = '';
  showAdjustModal.value = true;
}

function openRestockModal() {
  restockForm.supplier_id = '';
  restockForm.invoice_number = '';
  restockForm.items = [{ product_id: '', quantity: 1, cost_price: 0 }];
  loadSuppliers();
  loadProducts();
  showRestockModal.value = true;
}

function viewMovements(product) {
  selectedProduct.value = product;
  showMovementsModal.value = true;
}

async function loadSuppliers() {
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get('/api/v1/suppliers', { headers: { Authorization: `Bearer ${token}` } });
    suppliers.value = response.data.data;
  } catch (e) { console.error('Failed to load suppliers:', e); }
}

async function loadProducts() {
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get('/api/v1/products', { headers: { Authorization: `Bearer ${token}` } });
    products.value = response.data.data.data || response.data.data;
  } catch (e) { console.error('Failed to load products:', e); }
}

function addRestockItem() {
  restockForm.items.push({ product_id: '', quantity: 1, cost_price: 0 });
}

function removeRestockItem(index) {
  if (restockForm.items.length > 1) {
    restockForm.items.splice(index, 1);
  }
}

async function handleAdjustSubmit({ setLoading, setError }) {
  try {
    const token = localStorage.getItem('token');
    await axios.post('/api/v1/inventory/adjust', {
      product_id: selectedProduct.value.id,
      quantity: adjustForm.quantity,
      reason: adjustForm.reason
    }, { headers: { Authorization: `Bearer ${token}` } });
    AlertService.success('Stock adjusted successfully');
    showAdjustModal.value = false;
    tableRef.value?.refresh();
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to adjust stock');
  } finally {
    setLoading(false);
  }
}

async function handleRestockSubmit({ setLoading, setError }) {
  try {
    const token = localStorage.getItem('token');
    const validItems = restockForm.items.filter(i => i.product_id && i.quantity > 0);
    if (validItems.length === 0) {
      setError('Please add at least one product to restock');
      return;
    }
    await axios.post('/api/v1/inventory/restock', {
      supplier_id: restockForm.supplier_id,
      invoice_number: restockForm.invoice_number,
      items: validItems.map(i => ({ ...i, cost_price: parseInt(i.cost_price) || 0 }))
    }, { headers: { Authorization: `Bearer ${token}` } });
    AlertService.success('Stock restocked successfully');
    showRestockModal.value = false;
    tableRef.value?.refresh();
  } catch (err) {
    setError(err.response?.data?.message || 'Failed to restock');
  } finally {
    setLoading(false);
  }
}

function formatPrice(price) { return new Intl.NumberFormat('id-ID').format(price); }
</script>
