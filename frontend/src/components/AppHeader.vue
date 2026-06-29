<template>
  <header class="header">
    <div class="header-top hide-mobile">
      <div class="container header-top-inner">
        <router-link to="/" class="logo">
          <span class="logo-mark">
            <img v-if="logoSrc" :src="logoSrc" :alt="siteStore.siteName" class="logo-img" />
            <AppIcon v-else name="home" :size="22" />
          </span>
          <span class="logo-text">
            <span class="logo-title">{{ siteStore.siteName }}</span>
            <span class="logo-sub">{{ siteStore.siteDescription }}</span>
          </span>
        </router-link>

        <form class="search-wrap" @submit.prevent="submitSearch">
          <div class="search-bar">
            <AppIcon name="search" :size="18" class="search-icon" />
            <input
              v-model="searchKeyword"
              type="search"
              class="search-input"
              placeholder="搜索招聘、租房、二手车、家政..."
              enterkeyhint="search"
              autocomplete="off"
              maxlength="100"
            />
            <button type="submit" class="search-btn">搜索</button>
          </div>
        </form>

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
            <router-link v-if="siteStore.allowRegister" to="/register" class="auth-btn" :class="{ active: route.name === 'Register' }">注册</router-link>
          </template>
        </div>
      </div>
    </div>

    <!-- 移动端 -->
    <div class="header-mobile hide-desktop">
      <div class="container header-mobile-inner">
        <router-link to="/" class="logo-mobile">
          <img v-if="logoSrc" :src="logoSrc" :alt="siteStore.siteName" class="logo-img-mobile" />
          <AppIcon v-else name="home" :size="22" />
          <span>{{ siteStore.siteName }}</span>
        </router-link>
        <form class="mobile-search-form" @submit.prevent="submitSearch">
          <AppIcon name="search" :size="16" class="mobile-search-icon" />
          <input
            v-model="searchKeyword"
            type="search"
            class="mobile-search-input"
            placeholder="搜索..."
            enterkeyhint="search"
            autocomplete="off"
            maxlength="100"
          />
        </form>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useSiteStore } from '@/stores/site'
import { resolveLogoUrl } from '@/utils/asset'
import AppIcon from '@/components/AppIcon.vue'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const siteStore = useSiteStore()

const searchKeyword = ref('')

const logoSrc = computed(() => resolveLogoUrl(siteStore.siteLogo))

const userInitial = computed(() =>
  (userStore.user?.username?.[0] || 'U').toUpperCase()
)

watch(
  () => route.query.q,
  (q) => {
    if (route.name === 'Search') {
      searchKeyword.value = typeof q === 'string' ? q : ''
    }
  },
  { immediate: true }
)

function submitSearch() {
  const q = searchKeyword.value.trim()
  router.push(q ? { path: '/search', query: { q } } : { path: '/search' })
}
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
  overflow: hidden;
}

.logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: var(--white);
}

.logo-img-mobile {
  width: 28px;
  height: 28px;
  object-fit: contain;
  border-radius: 6px;
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
  margin: 0;
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
  transition: box-shadow 0.2s;
}

.search-bar:focus-within {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.search-icon {
  color: var(--text-muted);
  flex-shrink: 0;
}

.search-input {
  flex: 1;
  min-width: 0;
  border: none;
  background: transparent;
  font-size: 14px;
  color: var(--text);
  padding: 0;
}

.search-input::placeholder {
  color: var(--text-muted);
}

.search-input:focus {
  outline: none;
}

/* 隐藏 type=search 默认清除按钮（部分浏览器） */
.search-input::-webkit-search-cancel-button {
  -webkit-appearance: none;
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
  gap: 10px;
  height: var(--header-h);
}

.logo-mobile {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 17px;
  font-weight: 800;
  color: var(--black);
  flex-shrink: 0;
  max-width: 42%;
}

.logo-mobile span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mobile-search-form {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  height: 36px;
  padding: 0 12px;
  background: var(--white);
  border: 2px solid var(--black);
  border-radius: 18px;
}

.mobile-search-icon {
  color: var(--text-muted);
  flex-shrink: 0;
}

.mobile-search-input {
  flex: 1;
  min-width: 0;
  border: none;
  background: transparent;
  font-size: 14px;
  color: var(--text);
  padding: 0;
}

.mobile-search-input::placeholder {
  color: var(--text-muted);
}

.mobile-search-input:focus {
  outline: none;
}

.mobile-search-input::-webkit-search-cancel-button {
  -webkit-appearance: none;
}
</style>
