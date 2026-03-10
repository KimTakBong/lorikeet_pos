<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="$emit('close')">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
      <div class="p-6 border-b flex justify-between items-center">
        <h3 class="text-xl font-bold text-gray-900">Payment</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="p-6 space-y-6">
        <!-- Total Amount -->
        <div class="text-center">
          <div class="text-gray-600 text-sm">Total Amount</div>
          <div class="text-4xl font-bold text-blue-600 mt-2">Rp {{ formatPrice(total) }}</div>
        </div>

        <!-- Payment Method Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
          <select v-model="selectedMethod" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500">
            <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
              {{ method.name }}
            </option>
          </select>
        </div>

        <!-- Cash Payment -->
        <div v-if="selectedMethod === 1" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Cash Received</label>
            <input
              v-model.number="cashReceived"
              type="number"
              class="w-full border border-gray-300 rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-indigo-500"
              placeholder="Enter amount"
            />
          </div>
          
          <!-- Change Display -->
          <div v-if="cashReceived > 0" :class="change >= 0 ? 'text-green-600' : 'text-red-600'" class="text-center font-medium">
            <span v-if="change >= 0">Change: Rp {{ formatPrice(change) }}</span>
            <span v-else>Short: Rp {{ formatPrice(Math.abs(change)) }}</span>
          </div>

          <!-- Quick Cash Buttons -->
          <div class="grid grid-cols-3 gap-2">
            <ButtonSecondary @click="cashReceived = total" class="py-3">Exact</ButtonSecondary>
            <ButtonSecondary @click="cashReceived = 50000" class="py-3">50k</ButtonSecondary>
            <ButtonSecondary @click="cashReceived = 100000" class="py-3">100k</ButtonSecondary>
          </div>
        </div>

        <!-- Payment Info for Non-Cash -->
        <div v-else class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-800">
              <p class="font-medium mb-1">Payment Instructions</p>
              <p>Please complete the {{ paymentMethodName }} payment for Rp {{ formatPrice(total) }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="p-6 border-t flex gap-3">
        <ButtonSecondary @click="$emit('close')" class="flex-1 py-3">Cancel</ButtonSecondary>
        <ButtonPrimary 
          @click="confirmPayment" 
          :disabled="!canProcessPayment"
          class="flex-1 py-3"
        >
          Confirm Payment
        </ButtonPrimary>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import ButtonSecondary from '../ui/ButtonSecondary.vue';
import ButtonPrimary from '../ui/ButtonPrimary.vue';

const props = defineProps({
  total: { type: Number, required: true }
});

const emit = defineEmits(['close', 'confirm']);

const paymentMethods = ref([]);
const selectedMethod = ref(1);
const cashReceived = ref(0);

const change = computed(() => {
  if (selectedMethod.value === 1) {
    return cashReceived.value - props.total;
  }
  return 0;
});

const canProcessPayment = computed(() => {
  if (selectedMethod.value === 1) {
    return cashReceived.value >= props.total;
  }
  return true;
});

const paymentMethodName = computed(() => {
  const method = paymentMethods.value.find(m => m.id === selectedMethod.value);
  return method?.name || 'payment';
});

onMounted(async () => {
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get('/api/v1/payment-methods', {
      headers: { Authorization: `Bearer ${token}` }
    });
    paymentMethods.value = response.data.data || [];
    if (paymentMethods.value.length > 0) {
      selectedMethod.value = paymentMethods.value[0].id;
    }
  } catch (e) {
    console.error('Failed to load payment methods:', e);
  }
});

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price);
}

function confirmPayment() {
  emit('confirm', {
    paymentMethodId: selectedMethod.value,
    amount: selectedMethod.value === 1 ? cashReceived.value : props.total
  });
}
</script>
