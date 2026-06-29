<template>
  <header class="header">
    <div class="header-top hide-mobile">
      <div class="container header-top-inner">
        <router-link to="/" class="logo">
          <span class="logo-mark">
            <AppIcon name="home" :size="22" />
          </span>
          <span class="logo-text">
            <span class="logo-title">同城信息</span>
            <span class="logo-sub">本地生活服务平台</span>
          </span>
        </router-link>

        <div class="search-wrap">
          <div class="search-bar" @click="$router.push('/search')">
            <AppIcon name="search" :size="18" class="search-icon" />
            <span class="search-placeholder">搜索招聘、租房、二手车、家政...</span>
            <button type="button" class="search-btn" @click.stop="$router.push('/search')">搜索</button>
          </div>
        </div>

        <div class="header-actions">
          <router-link to="/publish" class="btn-publish">
            <AppIcon name="plus" :size="16" />
            免费发布
          </router-link>

          <template v-if="userStore.isLoggedIn">
            <router-link to="/user" class="user-entry" :class="{ active: route.name === 'User' }">
              <span class="user-avatar">{{ userInitial }}</span>
              <span class="user-name">{{ userStore.user?.username }}</span>
            </router-link>
          </template>
          <template v-else>
            <router-link to="/login" class="auth-link" :class="{ active: route.name === 'Login' }">登录</router-link>
            <router-link to="/register" class="auth-btn" :class="{ active: route.name === 'Register' }">注册</router-link>
          </template>
        </div>
      </div>
    </div>

    <!-- 移动端 -->
    <div class="header-mobile hide-desktop">
      <div class="container header-mobile-inner">
        <router-link to="/" class="logo-mobile">
          <AppIcon name="home" :size="22" />
          <span>同城信息</span>
        </router-link>
        <button type="button" class="mobile-search-btn" @click="$router.push('/search')">
          <AppIcon name="search" :size="22" />
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useUserStore } from '@/stores/user'
import AppIcon from '@/components/AppIcon.vue'

const route = useRoute()
const userStore = useUserStore()

const userInitial = computed(() =>
  (userStore.user?.username?.[0] || 'U').toUpperCase()
)
</script>

<style scoped>
.header {
  position: sticky;
  top: 0;
  z-index: 200;
  background: var(--primary);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

/* ===== 电脑端主栏 ===== */
.header-top-inner {
  display: flex;
  align-items: center;
  gap: 24px;
  width: 100%;
  min-height: 64px;
  padding-top: 10px;
  padding-bottom: 10px;
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
  min-width: 160px;
}

.logo-mark {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--black);
  color: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.logo-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.logo-title {
  font-size: 18px;
  font-weight: 800;
  color: var(--black);
}

.logo-sub {
  font-size: 11px;
  color: rgba(0, 0, 0, 0.55);
  margin-top: 2px;
}

.search-wrap {
  flex: 1;
  display: flex;
  justify-content: center;
  min-width: 0;
}

.search-bar {
  width: 100%;
  max-width: 560px;
  display: flex;
  align-items: center;
  gap: 10px;
  height: 42px;
  padding: 0 6px 0 14px;
  background: var(--white);
  border: 2px solid var(--black);
  border-radius: 24px;
  cursor: pointer;
  transition: box-shadow 0.2s;
}

.search-bar:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.search-icon {
  color: var(--text-muted);
  flex-shrink: 0;
}

.search-placeholder {
  flex: 1;
  font-size: 14px;
  color: var(--text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.search-btn {
  height: 32px;
  padding: 0 18px;
  border-radius: 18px;
  background: var(--black);
  color: var(--primary);
  font-size: 14px;
  font-weight: 700;
  flex-shrink: 0;
  transition: opacity 0.2s;
}

.search-btn:hover {
  opacity: 0.88;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
  margin-left: auto;
}

.btn-publish {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 40px;
  padding: 0 18px;
  border-radius: 20px;
  background: var(--black);
  color: var(--primary);
  font-size: 14px;
  font-weight: 700;
  transition: transform 0.15s, opacity 0.15s;
}

.btn-publish:hover {
  transform: translateY(-1px);
  opacity: 0.92;
}

.auth-link {
  font-size: 14px;
  font-weight: 600;
  color: var(--black);
  padding: 8px 4px;
  transition: opacity 0.2s;
}

.auth-link:hover,
.auth-link.active {
  opacity: 0.7;
}

.auth-btn {
  display: inline-flex;
  align-items: center;
  height: 36px;
  padding: 0 16px;
  border: 2px solid var(--black);
  border-radius: 18px;
  background: transparent;
  color: var(--black);
  font-size: 14px;
  font-weight: 700;
  transition: background 0.2s;
}

.auth-btn:hover,
.auth-btn.active {
  background: rgba(0, 0, 0, 0.08);
}

.user-entry {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 10px 4px 4px;
  border-radius: 20px;
  border: 2px solid transparent;
  transition: border-color 0.2s, background 0.2s;
}

.user-entry:hover,
.user-entry.active {
  border-color: var(--black);
  background: rgba(0, 0, 0, 0.06);
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--black);
  color: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
}

.user-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--black);
  max-width: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ===== 移动端 ===== */
.header-mobile {
  background: var(--primary);
}

.header-mobile-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: var(--header-h);
}

.logo-mobile {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 17px;
  font-weight: 800;
  color: var(--black);
}

.mobile-search-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  color: var(--black);
}
</style>
