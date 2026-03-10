<template>
  <div id="app">
    <component :is="currentLayout">
      <router-view />
    </component>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import AdminLayout from './components/layouts/AdminLayout.vue';
import POSLayout from './components/layouts/POSLayout.vue';

const route = useRoute();

const currentLayout = computed(() => {
  switch (route.meta.layout) {
    case 'admin':
      return AdminLayout;
    case 'pos':
      return POSLayout;
    default:
      return 'div';
  }
});
</script>

<style>
html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  overflow: hidden;
}

#app {
  height: 100%;
}
</style>
