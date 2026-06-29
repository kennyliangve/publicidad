import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/user'

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
    meta: { requiresAdmin: true },
    children: [
      { path: '', redirect: { name: 'AdminDashboard' } },
      {
        path: 'dashboard',
        name: 'AdminDashboard',
        component: () => import('@/views/admin/Dashboard.vue'),
        meta: { title: '仪表盘' },
      },
      {
        path: 'categories',
        name: 'AdminCategories',
        component: () => import('@/views/admin/Categories.vue'),
        meta: { title: '分类管理' },
      },
      {
        path: 'posts',
        name: 'AdminPosts',
        component: () => import('@/views/admin/Posts.vue'),
        meta: { title: '信息管理' },
      },
      {
        path: 'users',
        name: 'AdminUsers',
        component: () => import('@/views/admin/Users.vue'),
        meta: { title: '用户管理' },
      },
      {
        path: 'settings',
        name: 'AdminSettings',
        component: () => import('@/views/admin/Settings.vue'),
        meta: { title: '系统设置' },
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
        path: 'publish',
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
    ],
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

async function checkIsAdmin(userStore) {
  if (userStore.user && Number(userStore.user.role) === 1) {
    return true
  }
  if (!userStore.token) {
    return false
  }
  try {
    await Promise.race([
      userStore.fetchProfile(),
      new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 8000)),
    ])
  } catch {
    // 网络异常时使用本地缓存，避免导航卡死导致白屏
  }
  return !!(userStore.user && Number(userStore.user.role) === 1)
}

router.beforeEach(async (to, from, next) => {
  if (to.name === 'AdminLogin' || to.matched.some(r => r.meta.requiresAdmin)) {
    document.title = `${to.meta.title || '管理后台'} - 同城信息`
  } else {
    document.title = `${to.meta.title || ''} - 同城信息`
  }

  const token = localStorage.getItem('token')
  const userStore = useUserStore()

  if (to.name === 'AdminLogin') {
    if (token && await checkIsAdmin(userStore)) {
      next({ name: 'AdminDashboard' })
      return
    }
    next()
    return
  }

  if (to.matched.some(r => r.meta.requiresAdmin)) {
    if (!token) {
      next({ name: 'AdminLogin', query: { redirect: to.fullPath } })
      return
    }
    if (!(await checkIsAdmin(userStore))) {
      next({ name: 'AdminLogin', query: { error: 'forbidden', redirect: to.fullPath } })
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
