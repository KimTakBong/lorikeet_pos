import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../api';

export const useProductStore = defineStore('product', () => {
  const products = ref([]);
  const categories = ref([]);
  const loading = ref(false);
  const searchQuery = ref('');
  const selectedCategory = ref(null);

  async function fetchProducts(search = '', categoryId = null) {
    loading.value = true;
    try {
      const params = {};
      if (search) params.search = search;
      if (categoryId) params.category_id = categoryId;
      
      const response = await api.get('/products', { params });
      products.value = response.data.data;
    } catch (error) {
      console.error('Failed to fetch products:', error);
    } finally {
      loading.value = false;
    }
  }

  async function fetchCategories() {
    try {
      // For now, extract from products
      const uniqueCategories = [...new Set(products.value.map(p => p.category?.name).filter(Boolean))];
      categories.value = uniqueCategories.map((name, index) => ({ id: index + 1, name }));
    } catch (error) {
      console.error('Failed to fetch categories:', error);
    }
  }

  function setSearchQuery(query) {
    searchQuery.value = query;
    fetchProducts(query, selectedCategory.value);
  }

  function setCategory(categoryId) {
    selectedCategory.value = categoryId;
    fetchProducts(searchQuery.value, categoryId);
  }

  function getProductById(id) {
    return products.value.find(p => p.id === id);
  }

  return {
    products,
    categories,
    loading,
    searchQuery,
    selectedCategory,
    fetchProducts,
    fetchCategories,
    setSearchQuery,
    setCategory,
    getProductById,
  };
});
