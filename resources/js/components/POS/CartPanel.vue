<template>
  <div class="cart-panel flex flex-col h-full">
    <!-- Header -->
    <div class="p-4 bg-white border-b">
      <h2 class="text-lg font-bold">Current Order</h2>
      <div v-if="customer" class="text-sm text-gray-500 mt-1">
        Customer: {{ customer.name }}
      </div>
    </div>

    <!-- Cart Items -->
    <div class="cart-items flex-1 overflow-y-auto p-4">
      <div v-if="items.length === 0" class="text-center text-gray-500 mt-8">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <p>Cart is empty</p>
        <p class="text-sm mt-2">Tap products to add</p>
      </div>
      <div v-else class="space-y-2">
        <div v-for="item in items" :key="item.product_id" class="cart-item">
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <div class="font-medium">{{ item.product_name }}</div>
              <div class="text-sm text-gray-500">Rp {{ formatPrice(item.price) }}</div>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="$emit('update-quantity', item.product_id, item.quantity - 1)"
                class="w-7 h-7 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center text-lg font-medium"
              >
                -
              </button>
              <span class="w-8 text-center font-medium">{{ item.quantity }}</span>
              <button
                @click="$emit('update-quantity', item.product_id, item.quantity + 1)"
                class="w-7 h-7 rounded-full bg-blue-600 text-white hover:bg-blue-700 flex items-center justify-center text-lg font-medium"
              >
                +
              </button>
            </div>
          </div>
          <div class="text-right font-semibold mt-2 text-blue-600">
            Rp {{ formatPrice(item.price * item.quantity) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Totals Section -->
    <div class="bg-white border-t p-4 space-y-2">
      <div class="flex justify-between">
        <span class="text-gray-600">Subtotal</span>
        <span class="font-medium">Rp {{ formatPrice(subtotal) }}</span>
      </div>
      <div v-if="discountTotal > 0" class="flex justify-between text-red-600">
        <span>Discount</span>
        <span>-Rp {{ formatPrice(discountTotal) }}</span>
      </div>
      <div class="flex justify-between text-lg font-bold border-t pt-2 mt-2">
        <span>Total</span>
        <span class="text-blue-600">Rp {{ formatPrice(grandTotal) }}</span>
      </div>
    </div>

    <!-- Action Bar -->
    <div class="action-bar bg-white border-t p-4 space-y-3">
      <!-- Customer Phone / WhatsApp -->
      <div class="flex gap-2">
        <input
          v-model="customerPhone"
          @input="handlePhoneInput"
          @keyup.enter="findCustomer"
          type="tel"
          placeholder="WhatsApp: 08... (auto send receipt)"
          class="input-field flex-1"
        />
        <ButtonSecondary @click="findCustomer" :loading="findingCustomer">Find</ButtonSecondary>
      </div>
      
      <!-- WhatsApp Receipt Status -->
      <div v-if="customerPhone && isValidPhone" class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2">
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
          </svg>
          <span class="text-sm text-green-800 font-medium">{{ formattedPhone }}</span>
        </div>
        <span class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded">Auto send receipt ✓</span>
      </div>
      
      <!-- Discount Inputs (Percent & Amount) -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Discount %</label>
          <input
            v-model="discountPercentInput"
            @input="handleDiscountPercentInput"
            type="text"
            inputmode="numeric"
            placeholder="%"
            class="input-field w-full"
            min="0"
            max="100"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Discount (Rp)</label>
          <input
            v-model="discountAmountInput"
            @input="handleDiscountAmountInput"
            @blur="formatDiscountAmount"
            type="text"
            inputmode="numeric"
            placeholder="Rp"
            class="input-field w-full"
            min="0"
          />
        </div>
      </div>

      <!-- Pay Button -->
      <ButtonPrimary @click="$emit('checkout')" :disabled="items.length === 0" class="w-full py-4 text-lg">
        Pay Rp {{ formatPrice(grandTotal) }}
      </ButtonPrimary>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import ButtonSecondary from '../ui/ButtonSecondary.vue';
import ButtonPrimary from '../ui/ButtonPrimary.vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
  subtotal: { type: Number, default: 0 },
  discountPercent: { type: Number, default: 0 },
  discountAmount: { type: Number, default: 0 },
  grandTotal: { type: Number, default: 0 }
});

const emit = defineEmits(['update-quantity', 'checkout', 'set-customer', 'set-discount', 'set-customer-phone']);

const customerPhone = ref('');
const findingCustomer = ref(false);
const discountPercentInput = ref('');
const discountAmountInput = ref('');
const customer = ref(null);
const phoneFormatted = ref('');

const isValidPhone = computed(() => {
  const phone = customerPhone.value.replace(/\D/g, '');
  return phone.length >= 10 && phone.length <= 15;
});

const formattedPhone = computed(() => {
  if (!isValidPhone.value) return customerPhone.value;
  return phoneFormatted.value || customerPhone.value;
});

const discountTotal = computed(() => {
  if (props.discountAmount > 0) return props.discountAmount;
  return Math.round(props.subtotal * (props.discountPercent / 100));
});

// Watch for prop changes and update inputs
watch(() => props.subtotal, () => {
  // Clear inputs when subtotal changes (cart cleared)
  if (props.subtotal === 0) {
    discountPercentInput.value = '';
    discountAmountInput.value = '';
  }
});

function handleDiscountPercentInput() {
  const percent = parseFloat((discountPercentInput.value || '').toString().replace(/[^0-9]/g, '')) || 0;
  if (percent >= 0 && percent <= 100) {
    const amount = Math.round(props.subtotal * (percent / 100));
    // Format percent display (no thousands separator needed for %)
    discountPercentInput.value = percent > 0 ? percent.toString() : '';
    emit('set-discount', { percent, amount });
  }
}

function handleDiscountAmountInput() {
  // Get cursor position before formatting
  const input = event.target;
  const cursorPos = input.selectionStart;
  const oldValue = input.value;
  
  // Remove all non-digit characters for calculation
  const rawAmount = (oldValue || '').toString().replace(/[^0-9]/g, '');
  const amount = parseInt(rawAmount, 10) || 0;
  
  if (amount >= 0 && amount <= props.subtotal) {
    const percent = props.subtotal > 0 ? Math.round((amount / props.subtotal) * 100) : 0;
    
    // Format with thousands separator for display
    const formattedValue = amount > 0 ? formatNumber(amount) : '';
    
    // Update input value with formatted value
    discountAmountInput.value = formattedValue;
    
    // Restore cursor position (adjust for added/removed dots)
    const newValue = discountAmountInput.value;
    const diff = newValue.length - oldValue.length;
    const newCursorPos = Math.min(cursorPos + diff, newValue.length);
    
    // Set cursor position after update
    requestAnimationFrame(() => {
      input.setSelectionRange(newCursorPos, newCursorPos);
    });
    
    discountPercentInput.value = percent > 0 ? percent.toString() : '';
    emit('set-discount', { percent, amount });
  }
}

function formatDiscountAmount() {
  // Format on blur for clean display
  const rawAmount = (discountAmountInput.value || '').toString().replace(/[^0-9]/g, '');
  const amount = parseInt(rawAmount, 10) || 0;
  discountAmountInput.value = amount > 0 ? formatNumber(amount) : '';
}

function formatNumber(num) {
  if (num === null || num === undefined || num === '') return '';
  return new Intl.NumberFormat('id-ID').format(num);
}

function handlePhoneInput() {
  // Auto-format phone number as user types
  let phone = customerPhone.value.replace(/\D/g, ''); // Remove non-digits
  
  if (phone.startsWith('0')) {
    phone = '62' + phone.slice(1);
    phoneFormatted.value = phone;
  } else if (phone.startsWith('62')) {
    phoneFormatted.value = phone;
  } else if (phone.length > 0) {
    phoneFormatted.value = phone;
  } else {
    phoneFormatted.value = '';
  }
  
  // Emit phone to parent for WhatsApp receipt
  if (isValidPhone.value) {
    emit('set-customer-phone', phoneFormatted.value);
  }
}

async function findCustomer() {
  if (!customerPhone.value) return;
  
  // Convert phone format: 08... → 628...
  let phone = customerPhone.value.replace(/\D/g, ''); // Remove non-digits
  if (phone.startsWith('0')) {
    phone = '62' + phone.slice(1);
  }
  customerPhone.value = phone;
  
  findingCustomer.value = true;
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get('/api/v1/customers', {
      headers: { Authorization: `Bearer ${token}` },
      params: { phone: phone }
    });
    
    const customers = response.data.data?.data || response.data.data || [];
    if (customers.length > 0) {
      customer.value = customers[0];
      emit('set-customer', customer.value);
    } else {
      // Create new customer
      const createResponse = await axios.post('/api/v1/customers', {
        name: 'Walk-in Customer',
        phone: phone
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });
      customer.value = createResponse.data.data;
      emit('set-customer', customer.value);
    }
  } catch (e) {
    console.error('Failed to find customer:', e);
  } finally {
    findingCustomer.value = false;
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price);
}
</script>

<style scoped>
.cart-panel {
  background-color: #f9fafb;
  border-left: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.cart-item {
  background-color: white;
  border-radius: 0.5rem;
  padding: 0.75rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.input-field {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
}

.input-field:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}
</style>
