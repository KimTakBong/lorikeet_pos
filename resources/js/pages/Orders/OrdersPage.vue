<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Orders</h1>
      <p class="text-gray-500 mt-1">View and manage all orders</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
          <input v-model="filters.search" @input="debouncedSearch" type="text" placeholder="Invoice or customer..." class="w-full border border-gray-300 rounded-lg px-4 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <select v-model="filters.status" @change="tableRef?.refresh()" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            <option value="">All Status</option>
            <option value="completed">Completed</option>
            <option value="refunded">Refunded</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
          <input v-model="filters.date_from" @change="tableRef?.refresh()" type="date" class="w-full border border-gray-300 rounded-lg px-4 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
          <input v-model="filters.date_to" @change="tableRef?.refresh()" type="date" class="w-full border border-gray-300 rounded-lg px-4 py-2" />
        </div>
      </div>
    </div>

    <!-- Orders Table -->
    <DataTable ref="tableRef" :columns="columns" :fetch-data="fetchOrders" search-placeholder="" empty-message="No orders found">
      <template #cell-invoice_number="{ item }">
        <div class="font-medium text-indigo-600">{{ item.invoice_number }}</div>
        <div class="text-xs text-gray-500">{{ formatDate(item.created_at) }}</div>
      </template>

      <template #cell-customer="{ item }">
        <div>{{ item.customer?.name || 'Walk-in' }}</div>
        <div class="text-xs text-gray-500">{{ item.customer?.phone || '-' }}</div>
      </template>

      <template #cell-total="{ item }">
        <div class="font-medium text-gray-900">Rp {{ formatPrice(item.grand_total) }}</div>
      </template>

      <template #cell-status="{ item }">
        <span :class="statusClass(item.order_status)" class="px-2 py-1 text-xs font-medium rounded-full">
          {{ item.order_status }}
        </span>
      </template>

      <template #cell-actions="{ item }">
        <div class="flex justify-end gap-2">
          <ButtonIcon @click="viewOrder(item)" title="View Details">
            <Eye class="w-5 h-5" />
          </ButtonIcon>
          <ButtonIcon v-if="item.order_status !== 'refunded'" variant="danger" @click="openRefundModal(item)" title="Refund">
            <Undo2 class="w-5 h-5" />
          </ButtonIcon>
        </div>
      </template>
    </DataTable>

    <!-- Order Detail Modal -->
    <div v-if="selectedOrder && showDetailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="showDetailModal = false">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="p-6 border-b flex justify-between items-center">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Order Details</h2>
            <p class="text-sm text-gray-500">{{ selectedOrder.invoice_number }}</p>
          </div>
          <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
          <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
              <div class="text-sm text-gray-500">Customer</div>
              <div class="font-medium">{{ selectedOrder.customer?.name || 'Walk-in' }}</div>
              <div class="text-sm text-gray-500">{{ selectedOrder.customer?.phone || '-' }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-500">Cashier</div>
              <div class="font-medium">{{ selectedOrder.staff?.name || '-' }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-500">Date</div>
              <div class="font-medium">{{ formatDateTime(selectedOrder.created_at) }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-500">Status</div>
              <span :class="statusClass(selectedOrder.order_status)" class="px-2 py-1 text-xs font-medium rounded-full">
                {{ selectedOrder.order_status }}
              </span>
            </div>
          </div>

          <!-- Order Items -->
          <h3 class="font-semibold text-gray-900 mb-3">Items</h3>
          <div class="border rounded-lg overflow-hidden mb-6">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in selectedOrder.items" :key="item.id">
                  <td class="px-4 py-3">{{ item.product_name }}</td>
                  <td class="px-4 py-3 text-right">{{ item.quantity }}</td>
                  <td class="px-4 py-3 text-right">Rp {{ formatPrice(item.price) }}</td>
                  <td class="px-4 py-3 text-right font-medium">Rp {{ formatPrice(item.total) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Payment -->
          <h3 class="font-semibold text-gray-900 mb-3">Payment</h3>
          <div class="border rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="payment in selectedOrder.payments" :key="payment.id">
                  <td class="px-4 py-3">{{ payment.payment_method?.name || '-' }}</td>
                  <td class="px-4 py-3 text-right font-medium">Rp {{ formatPrice(payment.amount) }}</td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50">
                <tr>
                  <td class="px-4 py-3 font-bold">Grand Total</td>
                  <td class="px-4 py-3 text-right font-bold text-lg">Rp {{ formatPrice(selectedOrder.grand_total) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div class="p-6 border-t flex justify-end gap-3">
          <ButtonSecondary @click="showDetailModal = false">Close</ButtonSecondary>
          <ButtonPrimary v-if="selectedOrder.order_status !== 'refunded'" @click="openRefundModal(selectedOrder)">Process Refund</ButtonPrimary>
        </div>
      </div>
    </div>

    <!-- Refund Modal -->
    <div v-if="showRefundModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="showRefundModal = false">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
        <div class="p-6 border-b">
          <h2 class="text-xl font-bold text-gray-900">Process Refund</h2>
          <p class="text-sm text-gray-500">{{ selectedOrder?.invoice_number }}</p>
        </div>
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
            <textarea v-model="refundForm.reason" required rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="Reason for refund..."></textarea>
          </div>
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
            <div class="flex items-start gap-2">
              <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <div class="text-sm text-yellow-800">
                <p class="font-medium">Warning</p>
                <p>This will refund the entire order. This action cannot be undone.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="p-6 border-t flex justify-end gap-3">
          <ButtonSecondary @click="showRefundModal = false">Cancel</ButtonSecondary>
          <ButtonDanger @click="processRefund" :loading="processingRefund">Confirm Refund</ButtonDanger>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import DataTable from '../../components/tables/DataTable.vue';
import ButtonSecondary from '../../components/ui/ButtonSecondary.vue';
import ButtonPrimary from '../../components/ui/ButtonPrimary.vue';
import ButtonDanger from '../../components/ui/ButtonDanger.vue';
import AlertService from '../../components/alerts/AlertService.js';
import { Eye, Undo2 } from 'lucide-vue-next';

const tableRef = ref(null);
const selectedOrder = ref(null);
const showDetailModal = ref(false);
const showRefundModal = ref(false);
const processingRefund = ref(false);

const filters = reactive({
  search: '',
  status: '',
  date_from: new Date(new Date().setDate(1)).toISOString().split('T')[0],
  date_to: new Date().toISOString().split('T')[0]
});

const columns = {
  invoice_number: { label: 'Invoice', sortable: true },
  customer: { label: 'Customer', sortable: false },
  total: { label: 'Total', sortable: true },
  status: { label: 'Status', sortable: false },
  actions: { label: 'Actions', sortable: false }
};

const refundForm = reactive({
  reason: '',
  staff_id: 1 // TODO: Get from auth
});

const token = localStorage.getItem('token');

let searchTimeout;
function debouncedSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => tableRef.value?.refresh(), 300);
}

async function fetchOrders(params) {
  const searchParams = { ...params, ...filters };
  const response = await axios.get('/api/v1/orders', { 
    headers: { Authorization: `Bearer ${token}` }, 
    params: searchParams 
  });
  
  const result = response.data;
  const paginator = result.data;
  const orders = paginator.data || [];
  
  return { 
    data: orders, 
    current_page: paginator.current_page || 1, 
    last_page: paginator.last_page || 1, 
    from: paginator.from || 1, 
    to: paginator.to || orders.length, 
    total: paginator.total || orders.length 
  };
}

function viewOrder(order) {
  selectedOrder.value = order;
  showDetailModal.value = true;
}

function openRefundModal(order) {
  selectedOrder.value = order;
  refundForm.reason = '';
  showRefundModal.value = true;
  showDetailModal.value = false;
}

async function processRefund() {
  if (!refundForm.reason) {
    AlertService.error('Please enter refund reason');
    return;
  }

  processingRefund.value = true;
  try {
    // Get all items for full refund
    const items = selectedOrder.value.items.map(item => ({
      order_item_id: item.id,
      quantity: item.quantity,
      amount: item.total
    }));

    await axios.post(`/api/v1/orders/${selectedOrder.value.id}/refund`, {
      staff_id: refundForm.staff_id,
      reason: refundForm.reason,
      items
    }, {
      headers: { Authorization: `Bearer ${token}` }
    });

    AlertService.success('Refund processed successfully');
    showRefundModal.value = false;
    tableRef.value?.refresh();
  } catch (err) {
    AlertService.error(err.response?.data?.message || 'Failed to process refund');
  } finally {
    processingRefund.value = false;
  }
}

function statusClass(status) {
  const classes = {
    completed: 'bg-green-100 text-green-800',
    refunded: 'bg-red-100 text-red-800',
    pending: 'bg-yellow-100 text-yellow-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price);
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

onMounted(() => {
  // Load orders
});
</script>
