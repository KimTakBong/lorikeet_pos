<template>
  <button :type="type" :disabled="loading || disabled" @click="$emit('click', $event)" :class="[
    'inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg font-semibold transition-all duration-200',
    'bg-gray-200 text-gray-800 hover:bg-gray-300 active:bg-gray-400',
    'disabled:opacity-50 disabled:cursor-not-allowed disabled:active:bg-gray-200',
    'min-h-[44px]',
    size === 'sm' ? 'text-sm px-4 py-2 min-h-[36px]' : size === 'lg' ? 'text-lg px-8 py-4 min-h-[52px]' : 'text-base',
    className
  ]">
    <LoadingSpinner v-if="loading" size="sm" />
    <component v-else-if="icon" :is="icon" class="w-5 h-5" />
    <span v-if="loading">{{ loadingText }}</span>
    <slot></slot>
  </button>
</template>
<script setup>
import LoadingSpinner from './LoadingSpinner.vue';
defineProps({ 
  type: { type: String, default: 'button' }, 
  loading: Boolean, 
  disabled: Boolean, 
  size: { type: String, default: 'md' }, 
  className: String,
  loadingText: { type: String, default: 'Loading...' }
});
defineEmits(['click']);
</script>
