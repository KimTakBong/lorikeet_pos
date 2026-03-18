<template>
  <div class="dark:bg-gray-800 bg-white rounded-xl shadow-sm overflow-hidden">
    <!-- Toolbar -->
    <div v-if="showToolbar" class="p-4 border-b dark:border-gray-700 border-gray-200 flex flex-col sm:flex-row gap-4 justify-between items-center">
      <div class="relative w-full sm:w-64">
        <input v-model="localSearch" @input="debouncedSearch" type="text" :placeholder="searchPlaceholder" class="w-full pl-10 pr-4 py-2 border dark:border-gray-600 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 bg-white dark:text-white text-gray-900 dark:placeholder-gray-400 placeholder-gray-500" />
        <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 dark:text-gray-400 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
      </div>
      <div class="flex items-center gap-2">
        <select v-model="localPerPage" @change="changePerPage" class="border dark:border-gray-600 border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 bg-white dark:text-white text-gray-900">
          <option v-for="opt in perPageOptions" :key="opt" :value="opt" class="dark:bg-gray-700 bg-white">{{ opt }} / page</option>
        </select>
        <slot name="toolbar"></slot>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y dark:divide-gray-700 divide-gray-200">
        <thead class="dark:bg-gray-900 bg-gray-50">
          <tr>
            <th v-for="(column, key) in columns" :key="key" @click="column.sortable !== false && sortBy(key)" :class="['px-6 py-3 text-left text-xs font-medium uppercase tracking-wider', column.sortable !== false ? 'cursor-pointer' : '', 'dark:text-gray-300 dark:hover:bg-gray-700 text-gray-700 hover:bg-gray-100']">
              <div class="flex items-center gap-1">
                <span>{{ column.label }}</span>
                <span v-if="sortField === key" class="text-indigo-400">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
              </div>
            </th>
            <th v-if="$slots.actions" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider dark:text-gray-300 text-gray-700">Actions</th>
          </tr>
        </thead>
        <tbody class="dark:bg-gray-800 bg-white divide-y dark:divide-gray-700 divide-gray-200">
          <tr v-if="loading">
            <td :colspan="colspan">
              <div class="flex flex-col items-center justify-center py-16 gap-3">
                <LoadingSpinner size="lg" />
                <p class="text-sm dark:text-gray-400 text-gray-500">Loading...</p>
              </div>
            </td>
          </tr>
          <tr v-else-if="data.length === 0">
            <td :colspan="colspan" class="text-center py-16">
              <svg class="w-12 h-12 mx-auto mb-3 dark:text-gray-600 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
              <p class="text-sm dark:text-gray-400 text-gray-500">{{ emptyMessage }}</p>
            </td>
          </tr>
          <tr v-else v-for="(item, index) in data" :key="item.id || index" class="transition-colors dark:hover:bg-gray-700 hover:bg-gray-50">
            <td v-for="(column, key) in columns" :key="key" class="px-6 py-4 whitespace-nowrap dark:text-gray-200 text-gray-900">
              <slot :name="'cell-' + key" :item="item" :value="item[key]">{{ formatValue(item[key], column.format) }}</slot>
            </td>
            <td v-if="$slots.actions" class="px-6 py-4 text-right">
              <slot name="actions" :item="item"></slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t dark:border-gray-700 border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
      <span class="text-sm dark:text-gray-400 text-gray-500">Showing {{ pagination.from }} - {{ pagination.to }} of {{ pagination.total }}</span>
      <div class="flex gap-2">
        <ButtonSecondary size="sm" @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1">Previous</ButtonSecondary>
        <div class="hidden sm:flex gap-1">
          <template v-for="page in visiblePages" :key="page">
            <span v-if="page === '...'" class="px-2 py-1 text-sm dark:text-gray-500 text-gray-400">...</span>
            <button v-else @click="changePage(page)" :class="['px-3 py-1 rounded-lg text-sm font-medium transition-colors', page === pagination.current_page ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 bg-gray-200 text-gray-800 hover:bg-gray-300']">{{ page }}</button>
          </template>
        </div>
        <ButtonSecondary size="sm" @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page">Next</ButtonSecondary>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, computed, watch } from 'vue';
import ButtonSecondary from '../ui/ButtonSecondary.vue';
import LoadingSpinner from '../ui/LoadingSpinner.vue';

const props = defineProps({
  columns: Object,
  fetchData: Function,
  searchPlaceholder: { type: String, default: 'Search...' },
  emptyMessage: { Type: String, default: 'No data available' },
  perPageOptions: { type: Array, default: () => [10, 20, 50, 100] },
  showToolbar: { type: Boolean, default: true }
});

const colspan = computed(() => Object.keys(props.columns || {}).length + 1);

const data = ref([]);
const loading = ref(false);
const localSearch = ref('');
const localPerPage = ref(20);
const sortField = ref('name');
const sortDirection = ref('asc');
const pagination = reactive({ current_page: 1, last_page: 1, from: 0, to: 0, total: 0 });

let searchTimeout;
function debouncedSearch() { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => { pagination.current_page = 1; loadData(); }, 300); }
function sortBy(field) { if (sortField.value === field) { sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'; } else { sortField.value = field; sortDirection.value = 'asc'; } loadData(); }
function changePage(page) { if (page >= 1 && page <= pagination.last_page) { pagination.current_page = page; loadData(); } }
function changePerPage() { pagination.current_page = 1; loadData(); }
function formatValue(value, format) { if (value === null || value === undefined) return '-'; if (format === 'currency') return 'Rp ' + new Intl.NumberFormat('id-ID').format(value); if (format === 'date') return new Date(value).toLocaleDateString(); return value; }

const visiblePages = computed(() => {
  const pages = [];
  const current = pagination.current_page;
  const last = pagination.last_page;
  if (last <= 5) { for (let i = 1; i <= last; i++) pages.push(i); }
  else {
    if (current > 3) pages.push(1, '...');
    for (let i = Math.max(1, current - 1); i <= Math.min(last, current + 1); i++) pages.push(i);
    if (current < last - 2) pages.push('...', last);
  }
  return pages;
});

async function loadData() {
  loading.value = true;
  try {
    const result = await props.fetchData({ page: pagination.current_page, per_page: localPerPage.value, search: localSearch.value, sort_by: sortField.value, sort_direction: sortDirection.value });
    data.value = result.data || result;
    if (result.current_page) Object.assign(pagination, { current_page: result.current_page, last_page: result.last_page, from: result.from, to: result.to, total: result.total });
    else Object.assign(pagination, { current_page: 1, last_page: 1, from: 1, to: data.value.length, total: data.value.length });
  } catch (e) { console.error('Failed to load data:', e); }
  finally { loading.value = false; }
}

watch(() => props.fetchData, loadData, { immediate: true });
defineExpose({ refresh: loadData });
</script>
