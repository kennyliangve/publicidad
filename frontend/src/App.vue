<template>
  <div class="app" :class="{ 'is-admin': isAdmin }">
    <AppHeader v-if="!isAdmin" />
    <main class="page-content" :class="{ 'admin-page-content': isAdmin }">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
    <AppTabBar v-if="!isAdmin" />
    <div v-if="toast" class="toast">{{ toast }}</div>
  </div>
</template>

<script setup>
import { ref, provide, computed } from 'vue'
import { useRoute } from 'vue-router'
import AppHeader from '@/components/AppHeader.vue'
import AppTabBar from '@/components/AppTabBar.vue'

const route = useRoute()
const isAdmin = computed(() => route.path.startsWith('/admin'))

const toast = ref('')
let toastTimer = null

function showToast(msg, duration = 2000) {
  toast.value = msg
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = '' }, duration)
}

provide('showToast', showToast)
</script>

<style>
.fade-enter-active, .fade-leave-active { transition: opacity .15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.is-admin .admin-page-content {
  padding: 0;
  max-width: none;
}
</style>
