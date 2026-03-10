<template>
  <button :type="type" :disabled="loading || disabled" @click="$emit('click', $event)" :class="[
    'inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg font-semibold transition-all duration-200',
    'bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800',
    'disabled:opacity-50 disabled:cursor-not-allowed disabled:active:bg-indigo-600',
    'min-h-[44px]',
    size === 'sm' ? 'text-sm px-4 py-2 min-h-[36px]' : size === 'lg' ? 'text-lg px-8 py-4 min-h-[52px]' : 'text-base',
    variant === 'outline' ? 'bg-transparent border-2 border-indigo-600 text-indigo-600 hover:bg-indigo-50' : '',
    className
  ]">
    <LoadingSpinner v-if="loading" size="sm" color="white" />
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
  variant: { type: String, default: 'solid' }, 
  icon: Object, 
  className: String,
  loadingText: { type: String, default: 'Loading...' }
});
defineEmits(['click']);
</script>
