<template>
  <div class="app">
    <router-view v-slot="{ Component }">
      <component :is="Component" v-if="Component" />
    </router-view>
    <div v-if="toast" class="toast">{{ toast }}</div>
  </div>
</template>

<script setup>
import { ref, provide } from 'vue'

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
/* 页面切换样式保留供局部使用 */
.fade-enter-active, .fade-leave-active { transition: opacity .15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
