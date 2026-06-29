<template>
  <div class="admin-page">
    <div class="stat-cards">
      <div class="stat-card">
        <div class="num">{{ stats.users }}</div>
        <div class="label">用户总数</div>
      </div>
      <div class="stat-card">
        <div class="num">{{ stats.posts }}</div>
        <div class="label">信息总数</div>
      </div>
      <div class="stat-card">
        <div class="num">{{ stats.posts_active }}</div>
        <div class="label">已发布</div>
      </div>
      <div class="stat-card">
        <div class="num">{{ stats.posts_pending }}</div>
        <div class="label">待审核</div>
      </div>
      <div class="stat-card">
        <div class="num">{{ stats.categories }}</div>
        <div class="label">分类数</div>
      </div>
    </div>

    <div class="dashboard-grid">
      <section class="panel card">
        <h3>最新信息</h3>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>标题</th>
                <th>分类</th>
                <th>发布者</th>
                <th>状态</th>
                <th>时间</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in recentPosts" :key="p.id">
                <td>{{ p.title }}</td>
                <td>{{ p.category_name }}</td>
                <td>{{ p.username }}</td>
                <td><span :class="postStatusClass(p.status)">{{ postStatusLabel(p.status) }}</span></td>
                <td>{{ formatTime(p.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="panel card">
        <h3>最新用户</h3>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>昵称</th>
                <th>手机</th>
                <th>角色</th>
                <th>状态</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in recentUsers" :key="u.id">
                <td>{{ u.username }}</td>
                <td>{{ u.phone }}</td>
                <td>{{ u.role_label }}</td>
                <td>{{ u.status_label }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { adminApi } from '@/api/admin'

const stats = ref({ users: 0, posts: 0, posts_active: 0, posts_pending: 0, categories: 0 })
const recentPosts = ref([])
const recentUsers = ref([])

function postStatusLabel(s) {
  return { 0: '已下架', 1: '正常', 2: '待审核' }[s] || '-'
}
function postStatusClass(s) {
  return { 0: 'status-tag off', 1: 'status-tag ok', 2: 'status-tag pending' }[s] || 'status-tag'
}
function formatTime(t) {
  return t ? new Date(t).toLocaleString('zh-CN') : '-'
}

onMounted(async () => {
  const data = await adminApi.dashboard()
  stats.value = data.stats
  recentPosts.value = data.recent_posts
  recentUsers.value = data.recent_users
})
</script>

<style scoped>
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}
@media (max-width: 900px) {
  .dashboard-grid { grid-template-columns: 1fr; }
}
.panel { padding: 16px; }
.panel h3 { font-size: 16px; margin-bottom: 12px; }
</style>
