<template>
  <div class="pos-container flex h-screen bg-gray-100">
    <!-- Loading State -->
    <div v-if="loadingShift" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
        <p class="text-gray-600">Loading POS...</p>
      </div>
    </div>

    <!-- No Shift Warning -->
    <!-- No Shift - Inline Start Form -->
    <div v-else-if="!shiftId" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md border border-gray-700">
        <div class="p-6 text-center border-b border-gray-700">
          <div class="w-16 h-16 bg-amber-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-white mb-2">No Active Shift</h3>
          <p class="text-gray-400 text-sm">Enter opening cash to start your shift</p>
        </div>
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Opening Cash (Rp)</label>
            <input
              v-model="inlineOpeningCash"
              @input="formatInlineCash"
              type="text"
              inputmode="numeric"
              placeholder="e.g., 500.000"
              class="w-full border border-gray-600 rounded-xl px-4 py-3 bg-gray-700 text-white text-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              @keyup.enter="startShiftInline"
            />
          </div>
          <div class="flex gap-3">
            <button @click="goToDashboard" class="flex-1 px-4 py-3 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-xl font-medium transition-colors">
              ← Dashboard
            </button>
            <button
              @click="startShiftInline"
              :disabled="startingShift || !inlineOpeningCash"
              class="flex-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white rounded-xl font-medium transition-colors"
            >
              {{ startingShift ? 'Starting...' : 'Start Shift' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- POS Interface -->
    <template v-else>
      <!-- Product Grid (Left Side) -->
      <div class="flex-1 flex flex-col">
        <!-- Back Button -->
        <div class="p-3 border-b bg-gray-800 border-gray-700">
          <button @click="goToDashboard" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Back to Admin</span>
          </button>
        </div>
        <div class="flex-1">
          <ProductGrid @add-to-cart="addToCart" />
        </div>
      </div>

      <!-- Cart Panel (Right Side) -->
      <div class="w-96">
        <CartPanel
          :items="cartItems"
          :subtotal="subtotal"
          :discount-percent="discountPercent"
          :discount-amount="discountAmount"
          :grand-total="grandTotal"
          @update-quantity="updateQuantity"
          @checkout="showPaymentModal = true"
          @set-customer="setCustomer"
          @set-discount="setDiscount"
          @set-customer-phone="setCustomerPhone"
        />
      </div>
    </template>
  </div>

  <!-- Payment Modal -->
  <PaymentModal
    v-if="showPaymentModal && shiftId"
    :total="grandTotal"
    @close="showPaymentModal = false"
    @confirm="confirmPayment"
    ref="paymentModalRef"
  />
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import ProductGrid from '../components/POS/ProductGrid.vue';
import CartPanel from '../components/POS/CartPanel.vue';
import PaymentModal from '../components/POS/PaymentModal.vue';
import AlertService from '../components/alerts/AlertService.js';

const cartItems = ref([]);
const discountPercent = ref(0);
const discountAmount = ref(0);
const customer = ref(null);
const customerPhoneForReceipt = ref(null);
const showPaymentModal = ref(false);
const staffId = ref(null);
const shiftId = ref(null);
const loadingShift = ref(true);
const router = useRouter();
const paymentModalRef = ref(null);
const inlineOpeningCash = ref('');
const startingShift = ref(false);

const subtotal = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const discountTotal = computed(() => {
  if (discountAmount.value > 0) return discountAmount.value;
  return Math.round(subtotal.value * (discountPercent.value / 100));
});

const grandTotal = computed(() => {
  return subtotal.value - discountTotal.value;
});

onMounted(async () => {
  await loadStaffAndShift();
});

async function loadStaffAndShift() {
  try {
    const token = localStorage.getItem('token');
    
    // Get current user (staff)
    const userResponse = await axios.get('/api/user', {
      headers: { Authorization: `Bearer ${token}` }
    });
    staffId.value = userResponse.data.id;
    
    // Get active shift for this staff
    const shiftsResponse = await axios.get('/api/v1/shifts/active', {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    const activeShifts = shiftsResponse.data.data || [];
    const myShift = activeShifts.find(s => s.staff_id === staffId.value);
    
    if (myShift) {
      shiftId.value = myShift.id;
    } else if (activeShifts.length > 0) {
      // Use first active shift if no personal shift
      shiftId.value = activeShifts[0].id;
    } else {
      // No active shift - inline form will show automatically
    }
    
    loadingShift.value = false;
  } catch (e) {
    console.error('Failed to load staff/shift:', e);
    loadingShift.value = false;
  }
}

function addToCart(product) {
  const existingItem = cartItems.value.find(item => item.product_id === product.id);
  
  if (existingItem) {
    existingItem.quantity++;
  } else {
    cartItems.value.push({
      product_id: product.id,
      product_name: product.name,
      price: product.current_price || 0,
      quantity: 1
    });
  }
}

function updateQuantity(productId, quantity) {
  const index = cartItems.value.findIndex(item => item.product_id === productId);
  if (index !== -1) {
    if (quantity <= 0) {
      cartItems.value.splice(index, 1);
    } else {
      cartItems.value[index].quantity = quantity;
    }
  }
}

function setCustomer(customerData) {
  customer.value = customerData;
}

function setDiscount({ percent, amount }) {
  discountPercent.value = percent;
  discountAmount.value = amount;
}

function setCustomerPhone(phone) {
  customerPhoneForReceipt.value = phone;
}

async function confirmPayment(paymentData) {
  // Capture current state for the request
  const token = localStorage.getItem('token');
  const orderData = {
    staff_id: staffId.value,
    shift_id: shiftId.value,
    customer_id: customer.value?.id || null,
    customer_phone: customerPhoneForReceipt.value || customer.value?.phone || null,
    items: cartItems.value.map(item => ({
      product_id: item.product_id,
      quantity: item.quantity,
      price: item.price
    })),
    discount_percent: discountPercent.value,
    discount_amount: discountAmount.value,
    payments: [
      {
        payment_method_id: paymentData.paymentMethodId,
        amount: paymentData.amount || grandTotal.value
      }
    ]
  };

  // Optimistic: close modal + reset cart immediately
  showPaymentModal.value = false;
  cartItems.value = [];
  discountPercent.value = 0;
  discountAmount.value = 0;
  customer.value = null;
  customerPhoneForReceipt.value = null;

  // Send request in background
  try {
    const response = await axios.post('/api/v1/orders', orderData, {
      headers: { Authorization: `Bearer ${token}` }
    });

    if (response.data.success) {
      AlertService.success('Order completed! Invoice: ' + response.data.data.invoice_number);
    }
  } catch (error) {
    console.error('Payment failed:', error);
    AlertService.error('Payment failed (order not saved): ' + (error.response?.data?.message || 'Network error'));
  }
}

function goToDashboard() {
  router.push('/dashboard');
}

function formatInlineCash(e) {
  let val = (e.target.value || '').toString().replace(/[^0-9]/g, '');
  inlineOpeningCash.value = val ? parseInt(val).toLocaleString('id-ID') : '';
}

async function startShiftInline() {
  if (!inlineOpeningCash.value) return;
  startingShift.value = true;
  try {
    const token = localStorage.getItem('token');
    const cash = parseInt(inlineOpeningCash.value.toString().replace(/[^0-9]/g, '')) || 0;

    const response = await axios.post('/api/v1/shifts/start', {
      staff_id: staffId.value,
      opening_cash: cash
    }, {
      headers: { Authorization: `Bearer ${token}` }
    });

    shiftId.value = response.data.data.id;
    inlineOpeningCash.value = '';
  } catch (e) {
    AlertService.error('Failed to start shift: ' + (e.response?.data?.message || 'Unknown error'));
  } finally {
    startingShift.value = false;
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price);
}
</script>

<style scoped>
.pos-container {
  height: 100vh;
  overflow: hidden;
}
</style>
