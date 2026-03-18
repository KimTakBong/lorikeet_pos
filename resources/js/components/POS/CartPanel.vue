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
          @focus="resetCustomerState"
          type="tel"
          placeholder="WhatsApp: 08... (auto send receipt)"
          class="input-field flex-1"
        />
        <ButtonSecondary @click="findCustomer" :loading="findingCustomer">Find</ButtonSecondary>
      </div>
      
      <!-- WhatsApp Receipt Status - Only show after Find pressed -->
      <div v-if="customerFound && customer" class="flex items-center justify-between bg-green-500/10 border border-green-500/20 rounded-lg px-3 py-2">
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <span class="text-sm text-green-300 font-medium">{{ customer.name }}</span>
        </div>
        <span class="text-xs text-green-400 bg-green-500/10 px-2 py-1 rounded">Auto receipt ✓</span>
      </div>

      <!-- Customer not found - show create input -->
      <div v-if="customerNotFound" class="bg-amber-500/10 border border-amber-500/20 rounded-lg p-3 space-y-2">
        <p class="text-sm text-amber-300">Customer not found. Create new?</p>
        <div class="flex gap-2">
          <input
            v-model="newCustomerName"
            @keyup.enter="createNewCustomer"
            type="text"
            placeholder="Customer name (optional)"
            class="input-field flex-1 text-sm"
          />
          <ButtonSecondary @click="createNewCustomer" size="sm">Save</ButtonSecondary>
        </div>
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
const customerFound = ref(false);
const customerNotFound = ref(false);
const newCustomerName = ref('');
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

function resetCustomerState() {
  customerFound.value = false;
  customerNotFound.value = false;
  customer.value = null;
  newCustomerName.value = "";
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
  let phone = customerPhone.value.replace(/\D/g, '');
  if (phone.startsWith('0')) {
    phone = '62' + phone.slice(1);
  }
  customerPhone.value = phone;
  
  findingCustomer.value = true;
  customerFound.value = false;
  customerNotFound.value = false;
  customer.value = null;
  newCustomerName.value = '';
  
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get('/api/v1/customers', {
      headers: { Authorization: `Bearer ${token}` },
      params: { phone: phone }
    });
    
    const customers = response.data.data?.data || response.data.data || [];
    if (customers.length > 0) {
      customer.value = customers[0];
      customerFound.value = true;
      emit('set-customer', customer.value);
      emit('set-customer-phone', phone);
    } else {
      customerNotFound.value = true;
      emit('set-customer-phone', phone);
    }
  } catch (e) {
    console.error('Failed to find customer:', e);
  } finally {
    findingCustomer.value = false;
  }
}

async function createNewCustomer() {
  if (!customerPhone.value) return;
  
  let phone = customerPhone.value.replace(/\D/g, '');
  if (phone.startsWith('0')) {
    phone = '62' + phone.slice(1);
  }
  
  try {
    const token = localStorage.getItem('token');
    const createResponse = await axios.post('/api/v1/customers', {
      name: newCustomerName.value.trim() || 'Walk-in Customer',
      phone: phone
    }, {
      headers: { Authorization: `Bearer ${token}` }
    });
    customer.value = createResponse.data.data;
    customerFound.value = true;
    customerNotFound.value = false;
    emit('set-customer', customer.value);
  } catch (e) {
    console.error('Failed to create customer:', e);
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

/* Dark mode for POS cart */
:root.dark .cart-panel {
  background-color: #0f172a;
  border-left-color: #1e293b;
}
:root.dark .cart-item {
  background-color: #1e293b;
}
:root.dark .input-field {
  background-color: #1e293b;
  border-color: #334155;
  color: #f8fafc;
}
:root.dark .action-bar {
  background-color: #0f172a;
  border-top-color: #1e293b;
}
</style>
