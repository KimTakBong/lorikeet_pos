<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">{{ label }}</label>
    <div class="relative">
      <span v-if="prefix" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ prefix }}</span>
      <input
        :type="type"
        :value="formattedValue"
        @input="handleInput"
        @blur="handleBlur"
        :placeholder="placeholder"
        :required="required"
        :min="min"
        :class="[
          'w-full rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent',
          'border border-gray-300',
          prefix ? 'pl-12' : '',
          suffix ? 'pr-12' : ''
        ]"
      />
      <span v-if="suffix" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">{{ suffix }}</span>
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue';
import { formatCurrency, parseCurrency } from '../../utils/formatCurrency';

const props = defineProps({
  modelValue: [Number, String],
  label: String,
  type: { type: String, default: 'text' },
  placeholder: String,
  required: Boolean,
  min: { type: Number, default: 0 },
  prefix: { type: String, default: 'Rp ' },
  suffix: String
});

const emit = defineEmits(['update:modelValue']);

const formattedValue = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') return '';
  return formatCurrency(props.modelValue);
});

function handleInput(event) {
  const rawValue = event.target.value.replace(/[^0-9]/g, '');
  const numValue = parseInt(rawValue, 10) || 0;
  emit('update:modelValue', numValue);
}

function handleBlur(event) {
  const rawValue = event.target.value.replace(/[^0-9]/g, '');
  const numValue = parseInt(rawValue, 10) || 0;
  if (numValue < props.min) {
    emit('update:modelValue', props.min);
  } else {
    emit('update:modelValue', numValue);
  }
}
</script>
