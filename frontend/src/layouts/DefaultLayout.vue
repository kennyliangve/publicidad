<template>
  <AppHeader />
  <main class="page-content">
    <router-view />
  </main>
  <AppTabBar />
  <AppFooter />
</template>

<script setup>
import AppHeader from '@/components/AppHeader.vue'
import AppTabBar from '@/components/AppTabBar.vue'
import AppFooter from '@/components/AppFooter.vue'
</script>
