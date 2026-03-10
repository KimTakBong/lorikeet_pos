import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useCartStore = defineStore('cart', () => {
  const items = ref([]);
  const discountPercent = ref(0);
  const discountAmount = ref(0);
  const taxAmount = ref(0);
  const customer = ref(null);

  const subtotal = computed(() => {
    return items.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  });

  const discountTotal = computed(() => {
    if (discountAmount.value > 0) {
      return discountAmount.value;
    }
    return Math.round(subtotal.value * (discountPercent.value / 100));
  });

  const grandTotal = computed(() => {
    return subtotal.value - discountTotal.value + taxAmount.value;
  });

  function addItem(product) {
    const existingItem = items.value.find(item => item.product_id === product.id);
    
    if (existingItem) {
      existingItem.quantity++;
    } else {
      items.value.push({
        product_id: product.id,
        product_name: product.name,
        price: product.current_price || 0,
        quantity: 1,
      });
    }
  }

  function removeItem(productId) {
    const index = items.value.findIndex(item => item.product_id === productId);
    if (index !== -1) {
      items.value.splice(index, 1);
    }
  }

  function updateQuantity(productId, quantity) {
    const item = items.value.find(item => item.product_id === productId);
    if (item) {
      if (quantity <= 0) {
        removeItem(productId);
      } else {
        item.quantity = quantity;
      }
    }
  }

  function setDiscountPercent(percent) {
    discountPercent.value = percent;
    discountAmount.value = 0;
  }

  function setDiscountAmount(amount) {
    discountAmount.value = amount;
    discountPercent.value = 0;
  }

  function clearCart() {
    items.value = [];
    discountPercent.value = 0;
    discountAmount.value = 0;
    taxAmount.value = 0;
    customer.value = null;
  }

  function setCustomer(customerData) {
    customer.value = customerData;
  }

  return {
    items,
    discountPercent,
    discountAmount,
    taxAmount,
    customer,
    subtotal,
    discountTotal,
    grandTotal,
    addItem,
    removeItem,
    updateQuantity,
    setDiscountPercent,
    setDiscountAmount,
    clearCart,
    setCustomer,
  };
});
