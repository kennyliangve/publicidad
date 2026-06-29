<template>
  <div class="admin-layout">
    <aside class="admin-sidebar" :class="{ open: sidebarOpen }">
      <div class="sidebar-brand">
        <AppIcon name="home" :size="22" />
        <span>管理后台</span>
      </div>
      <nav class="sidebar-nav">
        <router-link
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="nav-link"
          @click="sidebarOpen = false"
        >
          <AppIcon :name="item.icon" :size="18" />
          {{ item.label }}
        </router-link>
      </nav>
      <router-link to="/" class="back-site" @click="sidebarOpen = false">
        <AppIcon name="chevron-right" :size="16" style="transform:rotate(180deg)" />
        返回前台
      </router-link>
    </aside>

    <div class="admin-main">
      <header class="admin-topbar">
        <button type="button" class="menu-toggle hide-desktop-admin" @click="sidebarOpen = !sidebarOpen">
          <AppIcon name="menu" :size="22" />
        </button>
        <h1 class="page-title">{{ currentTitle }}</h1>
        <div class="topbar-user">{{ userStore.user?.username }}</div>
        <button type="button" class="logout-btn" @click="logout">退出</button>
      </header>
      <div class="admin-content">
        <router-view />
      </div>
    </div>

    <div v-if="sidebarOpen" class="sidebar-overlay hide-desktop-admin" @click="sidebarOpen = false"></div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import AppIcon from '@/components/AppIcon.vue'

const route = useRoute()
const router = useRouter()
const userStore = useUserStore()
const sidebarOpen = ref(false)

function logout() {
  userStore.logout()
  router.push({ name: 'AdminLogin' })
}

const navItems = [
  { path: '/admin/dashboard', label: '仪表盘', icon: 'home' },
  { path: '/admin/categories', label: '分类管理', icon: 'store' },
  { path: '/admin/posts', label: '信息管理', icon: 'file-text' },
  { path: '/admin/users', label: '用户管理', icon: 'users' },
  { path: '/admin/settings', label: '系统设置', icon: 'wrench' },
]

const titleMap = Object.fromEntries(navItems.map(i => [i.path, i.label]))

const currentTitle = computed(() => titleMap[route.path] || '管理后台')
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
  background: var(--bg);
}

.admin-sidebar {
  width: 220px;
  background: var(--black);
  color: var(--primary);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  position: sticky;
  top: 0;
  height: 100vh;
  z-index: 300;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 20px 18px;
  font-size: 17px;
  font-weight: 800;
  border-bottom: 1px solid rgba(248, 208, 0, 0.2);
}

.sidebar-nav {
  flex: 1;
  padding: 12px 10px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  color: rgba(248, 208, 0, 0.75);
  transition: background 0.15s, color 0.15s;
}

.nav-link:hover,
.nav-link.router-link-active {
  background: var(--primary);
  color: var(--black);
  font-weight: 700;
}

.back-site {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 18px;
  font-size: 13px;
  border-top: 1px solid rgba(248, 208, 0, 0.2);
  color: rgba(248, 208, 0, 0.8);
}

.admin-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.admin-topbar {
  display: flex;
  align-items: center;
  gap: 12px;
  height: 56px;
  padding: 0 24px;
  background: var(--white);
  border-bottom: 1px solid var(--border);
  position: sticky;
  top: 0;
  z-index: 100;
}

.page-title {
  font-size: 18px;
  font-weight: 700;
  flex: 1;
}

.topbar-user {
  font-size: 14px;
  color: var(--text-muted);
}

.logout-btn {
  font-size: 13px;
  color: var(--text-muted);
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid var(--border);
  transition: background 0.15s, color 0.15s;
}

.logout-btn:hover {
  background: #f5f5f5;
  color: var(--black);
}

.admin-content {
  flex: 1;
  padding: 24px;
}

.menu-toggle {
  display: none;
  color: var(--black);
}

.sidebar-overlay {
  display: none;
}

@media (max-width: 900px) {
  .admin-sidebar {
    position: fixed;
    left: 0;
    transform: translateX(-100%);
    transition: transform 0.25s;
  }
  .admin-sidebar.open {
    transform: translateX(0);
  }
  .menu-toggle { display: flex; }
  .hide-desktop-admin { display: block; }
  .sidebar-overlay {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.4);
    z-index: 250;
  }
  .admin-content { padding: 16px; }
}

@media (min-width: 901px) {
  .hide-desktop-admin { display: none !important; }
}
</style>
