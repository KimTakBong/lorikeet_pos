import { createRouter, createWebHistory } from 'vue-router';

// Layouts
import AdminLayout from '../components/layouts/AdminLayout.vue';
import POSLayout from '../components/layouts/POSLayout.vue';

// Auth Pages
import Login from '../pages/Auth/Login.vue';

// Admin Pages
import Dashboard from '../pages/Dashboard/DashboardPage.vue';
import ProductsPage from '../pages/Products/ProductsPage.vue';
import InventoryPage from '../pages/Inventory/InventoryPage.vue';
import OrdersPage from '../pages/Orders/OrdersPage.vue';
import CustomersPage from '../pages/Customers/CustomersPage.vue';
import CampaignsPage from '../pages/Campaigns/CampaignsPage.vue';
import ExpensesPage from '../pages/Expenses/ExpensesPage.vue';
import AnalyticsPage from '../pages/Analytics/AnalyticsPage.vue';
import StaffPage from '../pages/Staff/StaffPage.vue';
import SettingsPage from '../pages/Settings/SettingsPage.vue';
import WhatsAppSettingsPage from '../pages/Settings/WhatsAppSettingsPage.vue';
import BusinessSettingsPage from '../pages/Settings/BusinessSettingsPage.vue';

// POS Page
import POSPage from '../pages/POSPage.vue';

const routes = [
  // Front page - redirect to login
  {
    path: '/',
    redirect: '/login',
  },
  
  // Auth routes (no layout)
  {
    path: '/login',
    name: 'login',
    component: Login,
  },
  
  // Admin routes (with AdminLayout)
  {
    path: '/dashboard',
    name: 'dashboard',
    component: Dashboard,
    meta: { layout: 'admin' },
  },
  {
    path: '/products',
    name: 'products',
    component: ProductsPage,
    meta: { layout: 'admin' },
  },
  {
    path: '/inventory',
    name: 'inventory',
    component: InventoryPage,
    meta: { layout: 'admin' },
  },
  {
    path: '/orders',
    name: 'orders',
    component: OrdersPage,
    meta: { layout: 'admin' },
  },
  {
    path: '/customers',
    name: 'customers',
    component: CustomersPage,
    meta: { layout: 'admin' },
  },
  {
    path: '/campaigns',
    name: 'campaigns',
    component: CampaignsPage,
    meta: { layout: 'admin' },
  },
  {
    path: '/expenses',
    name: 'expenses',
    component: ExpensesPage,
    meta: { layout: 'admin' },
  },
  {
    path: '/analytics',
    name: 'analytics',
    component: AnalyticsPage,
    meta: { layout: 'admin' },
  },
  {
    path: '/staff',
    name: 'staff',
    component: StaffPage,
    meta: { layout: 'admin' },
  },
  {
    path: '/settings',
    name: 'settings',
    component: SettingsPage,
    meta: { layout: 'admin' },
  },
  {
    path: '/settings/whatsapp',
    name: 'settings.whatsapp',
    component: WhatsAppSettingsPage,
    meta: { layout: 'admin' },
  },
  {
    path: '/settings/business',
    name: 'settings.business',
    component: BusinessSettingsPage,
    meta: { layout: 'admin' },
  },

  // POS route (with POSLayout - fullscreen, no sidebar)
  {
    path: '/pos',
    name: 'pos',
    component: POSPage,
    meta: { layout: 'pos' },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation guard for authentication
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  const publicRoutes = ['/login'];
  
  if (!token && !publicRoutes.includes(to.path)) {
    next('/login');
  } else if (token && to.path === '/login') {
    next('/dashboard');
  } else {
    next();
  }
});

export default router;
