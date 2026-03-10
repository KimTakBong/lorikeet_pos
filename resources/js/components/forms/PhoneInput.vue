<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">{{ label }}</label>
    <div class="flex gap-2">
      <!-- Country Code Selector -->
      <select 
        v-model="selectedCountry" 
        @change="updatePhone"
        class="w-32 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500"
      >
        <option v-for="country in countries" :key="country.code" :value="country.code">
          {{ country.flag }} {{ country.code }}
        </option>
      </select>
      
      <!-- Phone Input -->
      <input
        :value="localValue"
        @input="handleInput"
        @blur="validatePhone"
        type="tel"
        :placeholder="placeholder"
        :required="required"
        :class="[
          'flex-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500',
          error ? 'border-red-500' : 'border-gray-300'
        ]"
      />
    </div>
    
    <!-- Error Message -->
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    
    <!-- Helper Text -->
    <p class="mt-1 text-xs text-gray-500">
      Example: {{ selectedCountry === '62' ? '628123456789' : selectedCountry === '60' ? '60123456789' : selectedCountry + 'XXXXXXXXXX' }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  modelValue: String,
  label: String,
  placeholder: { type: String, default: 'Phone number' },
  required: Boolean,
  defaultCountry: { type: String, default: '62' } // Indonesia
});

const emit = defineEmits(['update:modelValue', 'update:valid']);

const localValue = ref(props.modelValue || '');
const selectedCountry = ref(props.defaultCountry);
const error = ref('');

const countries = [
  { code: '62', flag: '🇮🇩', name: 'Indonesia', minLength: 10, maxLength: 13 },
  { code: '60', flag: '🇲🇾', name: 'Malaysia', minLength: 9, maxLength: 12 },
  { code: '65', flag: '🇸🇬', name: 'Singapore', minLength: 8, maxLength: 9 },
  { code: '63', flag: '🇵🇭', name: 'Philippines', minLength: 10, maxLength: 11 },
  { code: '66', flag: '🇹🇭', name: 'Thailand', minLength: 9, maxLength: 10 },
  { code: '84', flag: '🇻🇳', name: 'Vietnam', minLength: 9, maxLength: 11 },
  { code: '61', flag: '🇦🇺', name: 'Australia', minLength: 9, maxLength: 10 },
  { code: '1', flag: '🇺🇸', name: 'USA/Canada', minLength: 10, maxLength: 10 },
  { code: '44', flag: '🇬🇧', name: 'UK', minLength: 10, maxLength: 11 },
  { code: '91', flag: '🇮🇳', name: 'India', minLength: 10, maxLength: 10 },
];

function updatePhone() {
  // Remove old country code if exists
  let phone = localValue.value.replace(/^\d+/, '');
  
  // Add new country code
  localValue.value = selectedCountry.value + phone;
  emit('update:modelValue', localValue.value);
  validatePhone();
}

function handleInput(event) {
  let value = event.target.value.replace(/\D/g, ''); // Only numbers
  
  // BLOCK if starts with 0 (show error immediately)
  if (value.startsWith('0')) {
    error.value = 'Phone number cannot start with 0. Use country code (e.g., 62 for Indonesia).';
    localValue.value = value;
    emit('update:modelValue', value);
    emit('update:valid', false);
    return;
  }
  
  // If doesn't start with country code, add it
  if (!value.startsWith(selectedCountry.value)) {
    value = selectedCountry.value + value;
  }
  
  localValue.value = value;
  emit('update:modelValue', value);
  error.value = '';
  emit('update:valid', true);
}

function validatePhone() {
  const country = countries.find(c => c.code === selectedCountry.value);
  
  if (!localValue.value) {
    if (props.required) {
      error.value = 'Phone number is required';
      emit('update:valid', false);
      return false;
    }
    error.value = '';
    emit('update:valid', true);
    return true;
  }
  
  // Check if starts with country code
  if (!localValue.value.startsWith(selectedCountry.value)) {
    error.value = `Phone must start with country code ${selectedCountry.value}`;
    emit('update:valid', false);
    return false;
  }
  
  // Check length
  const phoneWithoutCountry = localValue.value.slice(selectedCountry.value.length);
  
  if (phoneWithoutCountry.length < (country?.minLength || 8)) {
    error.value = `Phone number too short for ${country?.name}`;
    emit('update:valid', false);
    return false;
  }
  
  if (phoneWithoutCountry.length > (country?.maxLength || 15)) {
    error.value = `Phone number too long for ${country?.name}`;
    emit('update:valid', false);
    return false;
  }
  
  error.value = '';
  emit('update:valid', true);
  return true;
}

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  if (newValue !== localValue.value) {
    localValue.value = newValue;
    
    // Auto-detect country code
    for (const country of countries) {
      if (newValue.startsWith(country.code)) {
        selectedCountry.value = country.code;
        break;
      }
    }
    
    validatePhone();
  }
});

// Initial validation
validatePhone();
</script>
