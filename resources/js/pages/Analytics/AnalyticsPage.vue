<template>
  <div class="space-y-6">
    <!-- Header with Date Range -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Analytics</h1>
        <p class="text-gray-500 mt-1">Business insights and performance metrics</p>
      </div>
      <div class="flex gap-2">
        <input v-model="dateFrom" @change="loadData" type="date" class="border border-gray-300 rounded-lg px-4 py-2" />
        <span class="self-center">to</span>
        <input v-model="dateTo" @change="loadData" type="date" class="border border-gray-300 rounded-lg px-4 py-2" />
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-sm text-gray-500 mb-1">Total Sales</div>
        <div class="text-2xl font-bold text-gray-900">Rp {{ formatPrice(stats.sales.total) }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ stats.sales.orders }} orders</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-sm text-gray-500 mb-1">Avg Order</div>
        <div class="text-2xl font-bold text-gray-900">Rp {{ formatPrice(stats.sales.average) }}</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-sm text-gray-500 mb-1">Total Expenses</div>
        <div class="text-2xl font-bold text-red-600">Rp {{ formatPrice(stats.expenses.total) }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ stats.expenses.count }} transactions</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-sm text-gray-500 mb-1">Profit</div>
        <div :class="stats.profit.total >= 0 ? 'text-green-600' : 'text-red-600'" class="text-2xl font-bold">
          Rp {{ formatPrice(stats.profit.total) }}
        </div>
        <div class="text-xs text-gray-500 mt-1">{{ formatNumber(stats.profit.margin) }}% margin</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-sm text-gray-500 mb-1">New Customers</div>
        <div class="text-2xl font-bold text-gray-900">{{ stats.customers.new }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ stats.customers.total }} total</div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Sales Trend Chart -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Sales Trend</h3>
        <Line :data="salesChartData" :options="chartOptions" />
      </div>

      <!-- Payment Breakdown -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Methods</h3>
        <Doughnut :data="paymentChartData" :options="chartOptions" />
      </div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Top Products -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Products</h3>
        <div class="space-y-3">
          <div v-for="(product, index) in topProducts" :key="product.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold text-sm">{{ index + 1 }}</div>
              <div>
                <div class="font-medium">{{ product.name }}</div>
                <div class="text-xs text-gray-500">{{ product.quantity_sold }} sold</div>
              </div>
            </div>
            <div class="text-right">
              <div class="font-medium text-gray-900">Rp {{ formatPrice(product.revenue) }}</div>
            </div>
          </div>
          <div v-if="topProducts.length === 0" class="text-center py-8 text-gray-500">No products sold yet</div>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Orders</h3>
        <div class="space-y-3">
          <div v-for="order in recentOrders" :key="order.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
              <div class="font-medium">{{ order.invoice_number }}</div>
              <div class="text-xs text-gray-500">{{ order.customer?.name || 'Walk-in' }} • {{ formatDate(order.created_at) }}</div>
            </div>
            <div class="text-right">
              <div class="font-medium text-gray-900">Rp {{ formatPrice(order.grand_total) }}</div>
              <div class="text-xs" :class="order.order_status === 'completed' ? 'text-green-600' : 'text-yellow-600'">{{ order.order_status }}</div>
            </div>
          </div>
          <div v-if="recentOrders.length === 0" class="text-center py-8 text-gray-500">No orders yet</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { Line, Doughnut } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Title, Tooltip, Legend, Filler);

const token = localStorage.getItem('token');

// Default to last 30 days
const dateFrom = ref(new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]);
const dateTo = ref(new Date().toISOString().split('T')[0]);

const stats = ref({
  sales: { total: 0, orders: 0, average: 0 },
  expenses: { total: 0, count: 0 },
  profit: { total: 0, margin: 0 },
  customers: { new: 0, total: 0 },
});

const salesTrend = ref([]);
const topProducts = ref([]);
const paymentBreakdown = ref([]);
const recentOrders = ref([]);

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
    },
  },
};

const salesChartData = computed(() => ({
  labels: salesTrend.value.map(item => item.date),
  datasets: [{
    label: 'Sales',
    data: salesTrend.value.map(item => item.total),
    borderColor: '#4f46e5',
    backgroundColor: 'rgba(79, 70, 229, 0.1)',
    fill: true,
    tension: 0.4,
  }],
}));

const paymentChartData = computed(() => ({
  labels: paymentBreakdown.value.map(item => item.name),
  datasets: [{
    data: paymentBreakdown.value.map(item => item.total),
    backgroundColor: [
      '#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'
    ],
  }],
}));

async function loadData() {
  try {
    const response = await axios.get('/api/v1/analytics/dashboard', {
      headers: { Authorization: `Bearer ${token}` },
      params: { date_from: dateFrom.value, date_to: dateTo.value }
    });
    
    const data = response.data.data;
    stats.value = data.stats;
    salesTrend.value = data.sales_trend;
    topProducts.value = data.top_products;
    paymentBreakdown.value = data.payment_breakdown;
    recentOrders.value = data.recent_orders;
  } catch (e) {
    console.error('Failed to load analytics:', e);
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('id-ID').format(price);
}

function formatNumber(num) {
  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(num);
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

onMounted(() => {
  loadData();
});
</script>

<style scoped>
canvas {
  max-height: 300px;
}
</style>
