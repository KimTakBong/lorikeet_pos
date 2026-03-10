<template>
  <div v-if="modelValue" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
      <div class="text-center">
        <div class="mx-auto w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ title }}</h3>
        <p class="text-gray-500 mb-6">{{ message }}</p>
        <div class="flex gap-3 justify-center">
          <ButtonSecondary @click="$emit('update:modelValue', false)">Cancel</ButtonSecondary>
          <ButtonDanger @click="$emit('confirm')" :loading="loading">Delete</ButtonDanger>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue';
import ButtonSecondary from '../ui/ButtonSecondary.vue';
import ButtonDanger from '../ui/ButtonDanger.vue';
const props = defineProps({ modelValue: Boolean, title: { type: String, default: 'Delete Confirmation' }, message: String });
const emit = defineEmits(['update:modelValue', 'confirm']);
const loading = ref(false);
function handleConfirm() { loading.value = true; emit('confirm'); }
</script>
