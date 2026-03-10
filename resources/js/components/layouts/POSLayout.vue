<template>
  <div class="pos-layout h-screen w-screen overflow-hidden bg-gray-100">
    <router-view />
  </div>
</template>

<script setup>
// POS Layout - Fullscreen mode without sidebar
// The actual POS interface is rendered in POSPage.vue
</script>

<style scoped>
.pos-layout {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}
</style>
