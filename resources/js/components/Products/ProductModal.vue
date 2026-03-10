<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white z-10">
        <h2 class="text-xl font-bold text-gray-900">{{ isEdit ? 'Edit Product' : 'Create Product' }}</h2>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
            <input v-model="form.name" type="text" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="e.g., Latte" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">SKU *</label>
            <input v-model="form.sku" type="text" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="e.g., LAT001" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <select v-model="form.category_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
              <option value="">Select Category</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Price (Rp) *</label>
            <input v-model.number="form.price" type="number" required min="0" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="25000" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Cost (Rp)</label>
            <input v-model.number="form.cost" type="number" min="0" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="12000" />
          </div>
          <div class="col-span-2">
            <label class="flex items-center"><input v-model="form.is_active" type="checkbox" class="w-4 h-4 text-indigo-600 rounded" /><span class="ml-2 text-sm text-gray-700">Active</span></label>
          </div>
        </div>
        <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg"><p class="text-red-600 text-sm">{{ error }}</p></div>
        <div class="flex justify-end gap-3 pt-4 border-t">
          <button type="button" @click="$emit('close')" class="bg-gray-200 text-gray-800 rounded-lg font-medium px-6 py-2 hover:bg-gray-300">Cancel</button>
          <button type="submit" :disabled="loading" class="bg-indigo-600 text-white rounded-lg font-medium px-6 py-2 hover:bg-indigo-700 disabled:opacity-50">{{ loading ? 'Saving...' : (isEdit ? 'Update' : 'Create') }}</button>
        </div>
      </form>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
const props = defineProps({ product: { type: Object, default: null } });
const emit = defineEmits(['close', 'saved']);
const isEdit = ref(!!props.product);
const loading = ref(false);
const error = ref('');
const categories = ref([]);
const form = reactive({ name: '', sku: '', category_id: '', price: 0, cost: 0, is_active: true });
onMounted(async () => {
  try { const response = await axios.get('/api/v1/categories', { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } }); categories.value = response.data.data; } catch (e) { console.error('Failed to load categories:', e); }
  if (props.product) { form.name = props.product.name; form.sku = props.product.sku; form.category_id = props.product.category_id || ''; form.is_active = props.product.is_active; form.price = props.product.prices?.[0]?.price || 0; form.cost = props.product.costs?.[0]?.cost || 0; }
});
async function handleSubmit() {
  loading.value = true; error.value = '';
  try {
    const token = localStorage.getItem('token');
    const config = { headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' } };
    if (isEdit.value) { await axios.put(`/api/v1/products/${props.product.id}`, form, config); } else { await axios.post('/api/v1/products', form, config); }
    emit('saved');
  } catch (err) { error.value = err.response?.data?.message || 'Failed to save product'; }
  finally { loading.value = false; }
}
</script>
