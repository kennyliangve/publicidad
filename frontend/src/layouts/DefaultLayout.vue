<template>
  <AppHeader />
  <main class="page-content">
    <router-view />
  </main>
  <AppTabBar />
</template>

<script setup>
import AppHeader from '@/components/AppHeader.vue'
import AppTabBar from '@/components/AppTabBar.vue'
</script>
