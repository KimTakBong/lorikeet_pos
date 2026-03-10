<template>
  <div v-if="modelValue" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="close">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="p-6 border-b sticky top-0 bg-white z-10 flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-900">{{ title }}</h2>
        <button @click="close" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-lg transition-colors" aria-label="Close" type="button">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      <form @submit.prevent="handleSubmit" class="p-6">
        <slot :loading="loading" :error="error"></slot>
        <div v-if="error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg" role="alert">
          <p class="text-red-600 text-sm">{{ error }}</p>
        </div>
        <div class="flex justify-end gap-3 pt-4 border-t mt-6">
          <ButtonSecondary type="button" @click="close" :disabled="loading">Cancel</ButtonSecondary>
          <ButtonPrimary type="submit" :loading="loading">{{ submitText }}</ButtonPrimary>
        </div>
      </form>
    </div>
  </div>
</template>
<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import ButtonSecondary from '../ui/ButtonSecondary.vue';
import ButtonPrimary from '../ui/ButtonPrimary.vue';

const props = defineProps({ 
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Form' }, 
  submitText: { type: String, default: 'Save' }
});
const emit = defineEmits(['update:modelValue', 'close', 'submit']);

const loading = ref(false);
const error = ref('');

function close() {
  if (!loading.value) {
    emit('close');
    emit('update:modelValue', false);
  }
}

function handleSubmit() {
  emit('submit', { 
    setLoading: (v) => loading.value = v, 
    setError: (v) => error.value = v 
  });
}

// ESC key listener - only when modal is open
function handleEsc(event) {
  if (event.key === 'Escape' && props.modelValue && !loading.value) {
    event.preventDefault();
    close();
  }
}

watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    document.addEventListener('keydown', handleEsc);
  } else {
    document.removeEventListener('keydown', handleEsc);
  }
}, { immediate: true });

onUnmounted(() => {
  document.removeEventListener('keydown', handleEsc);
});
</script>
