<template>
  <div class="relative" ref="containerRef">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">{{ label }}</label>
    
    <!-- Trigger Button -->
    <button
      type="button"
      @click="toggleDropdown"
      :disabled="disabled"
      class="w-full flex items-center justify-between px-4 py-2 border border-gray-300 rounded-lg bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed pr-10"
    >
      <span :class="modelValue ? 'text-gray-900' : 'text-gray-500'" class="truncate">
        {{ modelValue ? getSelectedLabel() : placeholder }}
      </span>
      <svg class="w-5 h-5 text-gray-400 flex-shrink-0" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Clear Button -->
    <button
      v-if="modelValue && clearable"
      type="button"
      @click.stop="clear"
      class="absolute right-8 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1 z-10"
      title="Clear selection"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>

    <!-- Dropdown -->
    <div v-if="isOpen" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg overflow-hidden">
      <!-- Search Input -->
      <div class="p-2 border-b bg-gray-50">
        <div class="relative">
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            @keydown="handleKeydown"
            type="text"
            :placeholder="searchPlaceholder"
            class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          <svg class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      <!-- Options -->
      <div class="max-h-60 overflow-y-auto py-1">
        <div v-if="filteredOptions.length === 0" class="px-4 py-3 text-sm text-gray-500 text-center">
          {{ noResultsText }}
        </div>
        <button
          v-for="(option, index) in filteredOptions"
          :key="getOptionValue(option)"
          type="button"
          @click="selectOption(option)"
          @mouseenter="highlightedIndex = index"
          :class="[
            'w-full text-left px-4 py-2 text-sm transition-colors flex items-center justify-between',
            highlightedIndex === index ? 'bg-indigo-50 text-indigo-900' : 'text-gray-900 hover:bg-gray-50',
            isSelected(option) ? 'bg-indigo-100 font-medium' : ''
          ]"
        >
          <span class="truncate">{{ getOptionLabel(option) }}</span>
          <svg v-if="isSelected(option)" class="w-4 h-4 text-indigo-600 flex-shrink-0 ml-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>

      <!-- Footer (if loading) -->
      <div v-if="loading" class="px-4 py-2 border-t text-sm text-gray-500 text-center bg-gray-50">
        Loading...
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: [String, Number, Array],
  options: { type: Array, default: () => [] },
  label: String,
  placeholder: { type: String, default: 'Select...' },
  searchPlaceholder: { type: String, default: 'Search...' },
  noResultsText: { type: String, default: 'No results found' },
  optionLabel: { type: String, default: 'name' },
  optionValue: { type: String, default: 'id' },
  clearable: { type: Boolean, default: true },
  disabled: { type: Boolean, default: false },
  fetchOptions: Function,
  minCharsForSearch: { type: Number, default: 0 },
  multiple: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(0);
const loading = ref(false);
const containerRef = ref(null);
const searchInputRef = ref(null);

const filteredOptions = computed(() => {
  if (props.fetchOptions) {
    return props.options;
  }
  
  if (!searchQuery.value || searchQuery.value.length < props.minCharsForSearch) {
    return props.options;
  }
  
  const query = searchQuery.value.toLowerCase();
  return props.options.filter(option => {
    const label = getOptionLabel(option).toLowerCase();
    return label.includes(query);
  });
});

function getOptionLabel(option) {
  if (typeof option === 'string') return option;
  if (typeof option === 'number') return option;
  return option[props.optionLabel] ?? option.label ?? String(option);
}

function getOptionValue(option) {
  if (typeof option === 'string' || typeof option === 'number') return option;
  return option[props.optionValue] ?? option.value ?? option.id;
}

function isSelected(option) {
  const value = getOptionValue(option);
  if (props.multiple) {
    return Array.isArray(props.modelValue) && props.modelValue.includes(value);
  }
  return props.modelValue === value;
}

function getSelectedLabel() {
  if (props.multiple && Array.isArray(props.modelValue)) {
    const labels = props.modelValue.map(val => {
      const selected = props.options.find(o => getOptionValue(o) === val);
      return selected ? getOptionLabel(selected) : '';
    }).filter(Boolean);
    return labels.join(', ');
  }
  const selected = props.options.find(o => getOptionValue(o) === props.modelValue);
  return selected ? getOptionLabel(selected) : '';
}

function toggleDropdown() {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    setTimeout(() => searchInputRef.value?.focus(), 100);
    highlightedIndex.value = 0;
  }
}

function closeDropdown() {
  isOpen.value = false;
  searchQuery.value = '';
}

function selectOption(option) {
  const value = getOptionValue(option);
  
  if (props.multiple) {
    const currentValues = Array.isArray(props.modelValue) ? props.modelValue : [];
    const index = currentValues.indexOf(value);
    if (index > -1) {
      // Remove if already selected
      emit('update:modelValue', currentValues.filter(v => v !== value));
    } else {
      // Add if not selected
      emit('update:modelValue', [...currentValues, value]);
    }
    emit('change', props.modelValue);
  } else {
    emit('update:modelValue', value);
    emit('change', option);
    closeDropdown();
  }
}

function clear() {
  emit('update:modelValue', null);
  emit('change', null);
}

function handleKeydown(event) {
  if (!isOpen.value) {
    if (event.key === 'ArrowDown' || event.key === 'Enter') {
      toggleDropdown();
    }
    return;
  }

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault();
      highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1);
      break;
    case 'ArrowUp':
      event.preventDefault();
      highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
      break;
    case 'Enter':
      event.preventDefault();
      if (filteredOptions.value[highlightedIndex.value]) {
        selectOption(filteredOptions.value[highlightedIndex.value]);
      }
      break;
    case 'Escape':
      closeDropdown();
      break;
  }
}

function handleClickOutside(event) {
  if (containerRef.value && !containerRef.value.contains(event.target)) {
    closeDropdown();
  }
}

// Fetch options from API when search query changes
async function fetchOptionsDebounced() {
  if (!props.fetchOptions) return;
  if (searchQuery.value.length < props.minCharsForSearch) {
    emit('update:modelValue', props.modelValue);
    return;
  }
  
  loading.value = true;
  try {
    const options = await props.fetchOptions(searchQuery.value);
    emit('options-loaded', options);
  } catch (e) {
    console.error('Failed to fetch options:', e);
  } finally {
    loading.value = false;
  }
}

let fetchTimeout;
watch(searchQuery, () => {
  clearTimeout(fetchTimeout);
  fetchTimeout = setTimeout(fetchOptionsDebounced, 300);
});

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  clearTimeout(fetchTimeout);
});

defineExpose({ close: closeDropdown });
</script>
