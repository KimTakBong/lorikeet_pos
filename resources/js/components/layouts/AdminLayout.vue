<template>
  <div class="admin-layout flex h-screen bg-slate-50 dark:bg-slate-950">
    <!-- Mobile sidebar overlay -->
    <div v-if="sidebarOpen" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-20 lg:hidden" @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <aside :class="['fixed lg:static inset-y-0 left-0 z-30 w-64 transform transition-transform duration-300 ease-out', sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', 'bg-gradient-to-b from-indigo-900 via-indigo-950 to-slate-900 text-white flex flex-col']">
      <div class="h-16 flex items-center px-6 border-b border-white/10">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
          </div>
          <span class="text-lg font-bold tracking-tight">Lorikeet</span>
        </div>
      </div>
      <nav class="flex-1 mt-4 px-3 space-y-1 overflow-y-auto">
        <router-link v-for="item in navigation" :key="item.path" :to="item.path" class="sidebar-item" :class="{ 'active': isActive(item.path) }">
          <component :is="item.icon" class="w-5 h-5 mr-3 flex-shrink-0" />
          <span>{{ item.name }}</span>
        </router-link>
      </nav>
      <div class="p-4 border-t border-white/10">
        <button @click="logout" class="sidebar-item w-full text-red-300 hover:text-red-200 hover:bg-red-500/10">
          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
          Logout
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
      <header class="h-16 flex items-center px-4 lg:px-6 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 mr-4">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <h1 v-if="!route.meta.hideNavbarTitle" class="text-lg font-semibold text-slate-800 dark:text-white">{{ pageTitle }}</h1>
      </header>
      <main class="flex-1 overflow-y-auto p-4 lg:p-6 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, h } from 'vue';
import { useRoute, useRouter } from 'vue-router';
const route = useRoute();
const router = useRouter();
const sidebarOpen = ref(false);

const pageTitle = computed(() => {
  const names = { dashboard: 'Dashboard', products: 'Products', inventory: 'Inventory', orders: 'Orders', customers: 'Customers', campaigns: 'Campaigns', expenses: 'Expenses', analytics: 'Analytics', staff: 'Staff', settings: 'Settings' };
  return names[route.name] || 'Page';
});

const Icon = (path) => ({ render() { return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [h('path', { d: path })]); } });

const navigation = [
  { name: 'Dashboard', path: '/dashboard', icon: Icon('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6') },
  { name: 'POS', path: '/pos', icon: Icon('M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z') },
  { name: 'Orders', path: '/orders', icon: Icon('M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z') },
  { name: 'Products', path: '/products', icon: Icon('M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4') },
  { name: 'Inventory', path: '/inventory', icon: Icon('M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4') },
  { name: 'Customers', path: '/customers', icon: Icon('M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z') },
  { name: 'Campaigns', path: '/campaigns', icon: Icon('M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z') },
  { name: 'Expenses', path: '/expenses', icon: Icon('M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z') },
  { name: 'Analytics', path: '/analytics', icon: Icon('M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z') },
  { name: 'Staff', path: '/staff', icon: Icon('M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z') },
  { name: 'Settings', path: '/settings', icon: Icon('M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z') },
];

function isActive(path) { return route.path === path; }
function logout() { localStorage.removeItem('token'); localStorage.removeItem('user'); router.push('/login'); }
</script>
