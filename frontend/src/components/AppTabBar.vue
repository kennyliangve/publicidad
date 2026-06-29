<template>
  <nav class="tab-bar hide-desktop">
    <div class="tab-bar-inner">
      <router-link to="/" class="tab-item" :class="{ active: route.name === 'Home' }">
        <AppIcon name="home" :size="22" class="tab-icon" />
        <span>首页</span>
      </router-link>
      <router-link to="/search" class="tab-item" :class="{ active: route.name === 'Search' }">
        <AppIcon name="search" :size="22" class="tab-icon" />
        <span>搜索</span>
      </router-link>
      <router-link to="/publish" class="tab-item publish-tab" :class="{ active: route.name === 'Publish' }">
        <span class="tab-icon publish-icon">
          <AppIcon name="plus" :size="20" />
        </span>
        <span>发布</span>
      </router-link>
      <router-link to="/user" class="tab-item" :class="{ active: route.name === 'User' }">
        <AppIcon name="user" :size="22" class="tab-icon" />
        <span>我的</span>
      </router-link>
    </div>
  </nav>
</template>

<script setup>
import { useRoute } from 'vue-router'
import AppIcon from '@/components/AppIcon.vue'

const route = useRoute()
</script>

<style scoped>
.publish-tab .publish-icon {
  background: var(--black);
  color: var(--primary);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: -8px;
}
</style>
