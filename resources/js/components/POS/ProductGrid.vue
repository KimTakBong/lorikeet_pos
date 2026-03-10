<template>
  <div class="flex flex-col h-full">
    <!-- Search & Filters -->
    <div class="p-4 bg-white border-b">
      <div class="flex gap-2">
        <div class="relative flex-1">
          <input
            v-model="localSearch"
            @input="debouncedSearch"
            type="text"
            placeholder="Search products..."
            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          />
          <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <select v-model="selectedCategory" @change="filterByCategory" class="border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500">
          <option value="">All Categories</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
      </div>
    </div>

    <!-- Products Grid -->
    <div class="flex-1 overflow-y-auto p-4">
      <div v-if="loading" class="flex items-center justify-center h-full">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
          <p class="text-gray-500">Loading products...</p>
        </div>
      </div>
      <div v-else-if="products.length === 0" class="text-center py-12 text-gray-500">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <p>No products found</p>
      </div>
      <div v-else class="product-grid">
        <div
          v-for="product in products"
          :key="product.id"
          @click="$emit('add-to-cart', product)"
          class="product-card cursor-pointer transform transition-transform hover:scale-105 active:scale-95"
        >
          <div class="font-medium text-gray-900 truncate">{{ product.name }}</div>
          <div class="text-blue-600 font-semibold mt-1">Rp {{ formatPrice(product.prices?.[0]?.price || 0) }}</div>
          <div v-if="product.category" class="text-xs text-gray-500 mt-1">{{ product.category.name }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const emit = defineEmits(['add-to-cart']);

const products = ref([]);
const categories = ref([]);
const loading = ref(false);
const localSearch = ref('');
const selectedCategory = ref('');

const token = localStorage.getItem('token');

let searchTimeout;
function debouncedSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadProducts();
  }, 300);
}

function filterByCategory() {
  loadProducts();
}

async function loadProducts() {
  loading.value = true;
  try {
    const params = {};
    if (localSearch.value) params.search = localSearch.value;
    if (selectedCategory.value) params.category_id = selectedCategory.value;

    const response = await axios.get('/api/v1/products', {
      headers: { Authorization: `Bearer ${token}` },
      params
    });

    const result = response.data;
    products.value = result.data?.data || result.data || [];
    
    // Extract categories from products
    const uniqueCategories = [...new Set(products.value.map(p => p.category).filter(Boolean))];
    categories.value = uniqueCategories;
  } catch (e) {
    console.error('Failed to load products:', e);
  } finally {
    loading.value = false;
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price);
}

onMounted(() => {
  loadProducts();
});
</script>

<style scoped>
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 0.75rem;
}

.product-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
  padding: 0.75rem;
  transition: all 0.15s ease;
}

.product-card:hover {
  border-color: #60a5fa;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.product-card:active {
  transform: scale(0.95);
}
</style>
