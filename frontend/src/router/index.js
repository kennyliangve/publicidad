import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useSiteStore } from '@/stores/site'
import { isStaff, canAccessAdminModule, roleLevel, ROLES } from '@/utils/roles'

const routes = [
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: () => import('@/views/admin/AdminLogin.vue'),
    meta: { title: '后台登录' },
  },
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresStaff: true },
    children: [
      { path: '', redirect: { name: 'AdminDashboard' } },
      {
        path: 'dashboard',
        name: 'AdminDashboard',
        component: () => import('@/views/admin/Dashboard.vue'),
        meta: { title: '仪表盘', minRole: ROLES.MODERATOR, module: 'dashboard' },
      },
      {
        path: 'categories',
        name: 'AdminCategories',
        component: () => import('@/views/admin/Categories.vue'),
        meta: { title: '分类管理', minRole: ROLES.ADMIN, module: 'categories' },
      },
      {
        path: 'posts',
        name: 'AdminPosts',
        component: () => import('@/views/admin/Posts.vue'),
        meta: { title: '信息管理', minRole: ROLES.MODERATOR, module: 'posts' },
      },
      {
        path: 'users',
        name: 'AdminUsers',
        component: () => import('@/views/admin/Users.vue'),
        meta: { title: '用户管理', minRole: ROLES.ADMIN, module: 'users' },
      },
      {
        path: 'regions',
        name: 'AdminRegions',
        component: () => import('@/views/admin/Regions.vue'),
        meta: { title: '省/城市管理', minRole: ROLES.ADMIN, module: 'regions' },
      },
      {
        path: 'settings',
        name: 'AdminSettings',
        component: () => import('@/views/admin/Settings.vue'),
        meta: { title: '系统设置', minRole: ROLES.ADMIN, module: 'settings' },
      },
      {
        path: 'vip-settings',
        name: 'AdminVipSettings',
        component: () => import('@/views/admin/VipSettings.vue'),
        meta: { title: 'VIP 与银行对接', minRole: ROLES.ADMIN, module: 'vip' },
      },
    ],
  },
  {
    path: '/',
    component: () => import('@/layouts/DefaultLayout.vue'),
    children: [
      {
        path: '',
        name: 'Home',
        component: () => import('@/views/Home.vue'),
        meta: { title: '首页' },
      },
      {
        path: 'category/:id',
        name: 'Category',
        component: () => import('@/views/Category.vue'),
        meta: { title: '分类' },
      },
      {
        path: 'post/:id',
        name: 'PostDetail',
        component: () => import('@/views/PostDetail.vue'),
        meta: { title: '详情' },
      },
      {
        path: 'publish/:id?',
        name: 'Publish',
        component: () => import('@/views/Publish.vue'),
        meta: { title: '发布信息', auth: true },
      },
      {
        path: 'search',
        name: 'Search',
        component: () => import('@/views/Search.vue'),
        meta: { title: '搜索' },
      },
      {
        path: 'login',
        name: 'Login',
        component: () => import('@/views/Login.vue'),
        meta: { title: '登录' },
      },
      {
        path: 'register',
        name: 'Register',
        component: () => import('@/views/Register.vue'),
        meta: { title: '注册' },
      },
      {
        path: 'user',
        name: 'User',
        component: () => import('@/views/User.vue'),
        meta: { title: '我的', auth: true },
      },
      {
        path: 'upgrade',
        name: 'Upgrade',
        component: () => import('@/views/Upgrade.vue'),
        meta: { title: '升级 VIP', auth: true },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

async function ensureUserProfile(userStore) {
  if (userStore.user) return
  if (!userStore.token) return
  try {
    await Promise.race([
      userStore.fetchProfile(),
      new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 8000)),
    ])
  } catch {
    // 网络异常时使用本地缓存
  }
}

async function getUserRole(userStore) {
  await ensureUserProfile(userStore)
  return roleLevel(userStore.user?.role)
}

function routeMinRole(to) {
  const record = [...to.matched].reverse().find(r => r.meta.minRole !== undefined)
  return record?.meta.minRole ?? ROLES.MODERATOR
}

function routeModule(to) {
  const record = [...to.matched].reverse().find(r => r.meta.module)
  return record?.meta.module
}

router.beforeEach(async (to, from, next) => {
  const siteStore = useSiteStore()
  if (!siteStore.loaded) {
    await siteStore.load()
  }
  const siteName = siteStore.siteName

  if (to.name === 'AdminLogin' || to.matched.some(r => r.meta.requiresStaff)) {
    document.title = `${to.meta.title || '管理后台'} - ${siteName}`
  } else {
    const pageTitle = to.meta.title || ''
    document.title = pageTitle ? `${pageTitle} - ${siteName}` : siteName
  }

  const token = localStorage.getItem('token')
  const userStore = useUserStore()

  if (to.name === 'Register' && !siteStore.allowRegister) {
    next({ name: 'Login', query: { error: 'register_disabled' } })
    return
  }

  if (to.name === 'AdminLogin') {
    if (token && isStaff(await getUserRole(userStore))) {
      next({ name: 'AdminDashboard' })
      return
    }
    next()
    return
  }

  if (to.matched.some(r => r.meta.requiresStaff)) {
    if (!token) {
      next({ name: 'AdminLogin', query: { redirect: to.fullPath } })
      return
    }

    const role = await getUserRole(userStore)
    if (!isStaff(role)) {
      next({ name: 'AdminLogin', query: { error: 'forbidden', redirect: to.fullPath } })
      return
    }

    const module = routeModule(to)
    const minRole = routeMinRole(to)
    if (roleLevel(role) < minRole || (module && !canAccessAdminModule(role, module))) {
      next({ name: 'AdminDashboard' })
      return
    }

    next()
    return
  }

  if (to.meta.auth && !token) {
    next({ name: 'Login', query: { redirect: to.fullPath } })
  } else {
    next()
  }
})

export default router
