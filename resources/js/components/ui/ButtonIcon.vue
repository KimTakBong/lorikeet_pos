<template>
  <button :type="type" :disabled="disabled" @click="$emit('click', $event)" :class="[
    'inline-flex items-center justify-center rounded-lg transition-all duration-200',
    'hover:bg-gray-100 active:bg-gray-200',
    'disabled:opacity-50 disabled:cursor-not-allowed',
    'min-h-[44px] min-w-[44px]',
    variant === 'danger' ? 'text-red-600 hover:bg-red-50 hover:text-red-700' : variant === 'primary' ? 'text-indigo-600 hover:bg-indigo-50 hover:text-indigo-700' : 'text-gray-600 hover:text-gray-800',
    size === 'sm' ? 'p-2' : size === 'lg' ? 'p-4' : 'p-3',
    className
  ]">
    <slot></slot>
  </button>
</template>
<script setup>
defineProps({ 
  type: { type: String, default: 'button' }, 
  disabled: Boolean, 
  size: { type: String, default: 'md' }, 
  variant: { type: String, default: 'default' }, 
  className: String
});
defineEmits(['click']);
</script>
