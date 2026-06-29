<template>
  <div class="user-page container">
    <div v-if="!userStore.isLoggedIn" class="login-prompt card">
      <AppIcon name="user" :size="48" class="prompt-icon" />
      <p>登录后查看和管理您的信息</p>
      <router-link to="/login" class="btn btn-primary">立即登录</router-link>
    </div>

    <template v-else>
      <div class="user-header card">
        <div class="avatar">{{ profile?.username?.[0] || 'U' }}</div>
        <div class="user-info">
          <div class="username-row">
            <span class="username">{{ profile?.username }}</span>
            <span v-if="profile?.role === 1" class="badge admin">管理员</span>
          </div>
          <div class="contact">{{ profile?.email || profile?.phone }}</div>
          <div v-if="locationText" class="meta">
            <AppIcon name="map-pin" :size="14" />
            {{ locationText }}
          </div>
        </div>
      </div>

      <div class="user-stats card">
        <div class="stat-item">
          <div class="stat-num">{{ profile?.login_count || 0 }}</div>
          <div class="stat-label">登录次数</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">{{ posts.length }}</div>
          <div class="stat-label">发布数量</div>
        </div>
        <div class="stat-item">
          <div class="stat-label">注册时间</div>
          <div class="stat-date">{{ formatDate(profile?.created_at) }}</div>
        </div>
      </div>

      <div class="profile-card card">
        <div class="card-head">
          <h3>个人资料</h3>
          <button type="button" class="edit-btn" @click="editing = !editing">
            {{ editing ? '取消' : '编辑' }}
          </button>
        </div>

        <div v-if="!editing" class="profile-view">
          <div class="profile-row">
            <span class="label">真实姓名</span>
            <span>{{ profile?.real_name || '未填写' }}</span>
          </div>
          <div class="profile-row">
            <span class="label">性别</span>
            <span>{{ profile?.gender_label || '保密' }}</span>
          </div>
          <div class="profile-row">
            <span class="label">个人简介</span>
            <span>{{ profile?.bio || '未填写' }}</span>
          </div>
          <div class="profile-row">
            <span class="label">账号状态</span>
            <span>{{ profile?.status_label }}</span>
          </div>
          <div class="profile-row">
            <span class="label">最后登录</span>
            <span>{{ formatDateTime(profile?.last_login_at) }}</span>
          </div>
        </div>

        <form v-else class="profile-form" @submit.prevent="saveProfile">
          <div class="form-group">
            <label class="form-label">昵称</label>
            <input v-model="form.username" class="form-input" maxlength="50" />
          </div>
          <div class="form-group">
            <label class="form-label">真实姓名</label>
            <input v-model="form.real_name" class="form-input" maxlength="50" />
          </div>
          <div class="form-group">
            <label class="form-label">性别</label>
            <select v-model="form.gender" class="form-select">
              <option :value="0">保密</option>
              <option :value="1">男</option>
              <option :value="2">女</option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">城市</label>
              <input v-model="form.city" class="form-input" />
            </div>
            <div class="form-group">
              <label class="form-label">区域</label>
              <input v-model="form.district" class="form-input" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">个人简介</label>
            <textarea v-model="form.bio" class="form-textarea" rows="3" maxlength="255"></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-block" :disabled="saving">
            {{ saving ? '保存中...' : '保存资料' }}
          </button>
        </form>
      </div>

      <div class="user-menu card">
        <router-link v-if="profile?.role === 1" to="/admin" class="menu-item admin-entry">
          <span class="menu-label">
            <AppIcon name="wrench" :size="18" />
            管理后台
          </span>
          <AppIcon name="chevron-right" :size="18" class="arrow" />
        </router-link>
        <router-link to="/publish" class="menu-item">
          <span class="menu-label">
            <AppIcon name="pen-line" :size="18" />
            发布信息
          </span>
          <AppIcon name="chevron-right" :size="18" class="arrow" />
        </router-link>
      </div>

      <div class="my-posts">
        <h3>我的发布</h3>
        <div v-if="loading" class="loading">加载中...</div>
        <div v-else-if="!posts.length" class="empty-state card">
          <AppIcon name="inbox" :size="48" class="state-icon" />
          <p>您还没有发布信息</p>
          <router-link to="/publish" class="btn btn-primary btn-sm" style="margin-top:12px">去发布</router-link>
        </div>
        <div v-else class="card post-list">
          <div v-for="post in posts" :key="post.id" class="my-post-item">
            <router-link :to="`/post/${post.id}`" class="my-post-link">
              <div class="my-post-title">{{ post.title }}</div>
              <div class="my-post-meta">
                {{ post.category_name }} · {{ formatDate(post.created_at) }} · {{ post.views }}浏览
              </div>
            </router-link>
            <button class="delete-btn" @click="deletePost(post.id)">删除</button>
          </div>
        </div>
      </div>

      <button class="btn btn-outline btn-block logout-btn" @click="logout">退出登录</button>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { api } from '@/api'
import AppIcon from '@/components/AppIcon.vue'

const router = useRouter()
const userStore = useUserStore()
const showToast = inject('showToast')

const profile = ref(null)
const posts = ref([])
const loading = ref(false)
const editing = ref(false)
const saving = ref(false)
const form = ref({
  username: '',
  real_name: '',
  gender: 0,
  city: '',
  district: '',
  bio: '',
})

const locationText = computed(() => {
  const p = profile.value
  if (!p) return ''
  return [p.province, p.city, p.district].filter(Boolean).join(' ')
})

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('zh-CN')
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('zh-CN')
}

function fillForm() {
  const p = profile.value
  if (!p) return
  form.value = {
    username: p.username || '',
    real_name: p.real_name || '',
    gender: p.gender ?? 0,
    city: p.city || '',
    district: p.district || '',
    bio: p.bio || '',
  }
}

async function loadProfile() {
  const data = await api.getProfile()
  profile.value = data
  userStore.user = data
  localStorage.setItem('user', JSON.stringify(data))
  fillForm()
}

async function loadPosts() {
  loading.value = true
  try {
    const data = await api.getMyPosts()
    posts.value = data.list
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  saving.value = true
  try {
    const data = await api.updateProfile({
      ...form.value,
      gender: Number(form.value.gender),
    })
    profile.value = data
    userStore.user = data
    localStorage.setItem('user', JSON.stringify(data))
    editing.value = false
    showToast('资料已保存')
  } catch (err) {
    showToast(err.message)
  } finally {
    saving.value = false
  }
}

async function deletePost(id) {
  if (!confirm('确定删除这条信息吗？')) return
  try {
    await api.deletePost(id)
    posts.value = posts.value.filter(p => p.id !== id)
    showToast('删除成功')
  } catch (err) {
    showToast(err.message)
  }
}

function logout() {
  userStore.logout()
  showToast('已退出登录')
  router.push('/')
}

onMounted(async () => {
  if (!userStore.isLoggedIn) return
  try {
    await Promise.all([loadProfile(), loadPosts()])
  } catch (err) {
    showToast(err.message)
  }
})
</script>

<style scoped>
.login-prompt {
  text-align: center;
  padding: 48px 20px;
  margin-top: 20px;
}
.prompt-icon, .state-icon { color: var(--text-muted); margin-bottom: 12px; }
.login-prompt p { color: var(--text-muted); margin-bottom: 16px; }

.user-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 24px 20px;
  margin-top: 16px;
}
.avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--black);
  color: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  font-weight: 600;
  flex-shrink: 0;
}
.username-row {
  display: flex;
  align-items: center;
  gap: 8px;
}
.username { font-size: 18px; font-weight: 700; }
.badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 10px;
  font-weight: 600;
}
.badge.admin {
  background: var(--black);
  color: var(--primary);
}
.contact { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
.meta {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: var(--text-muted);
  margin-top: 4px;
}

.user-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  margin-top: 12px;
  padding: 16px;
  text-align: center;
}
.stat-num { font-size: 20px; font-weight: 800; color: var(--black); }
.stat-label { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.stat-date { font-size: 13px; font-weight: 600; margin-top: 4px; }

.profile-card { margin-top: 12px; padding: 16px 20px; }
.card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.card-head h3 { font-size: 16px; }
.edit-btn {
  font-size: 14px;
  color: var(--black);
  font-weight: 600;
  padding: 4px 12px;
  border: 1px solid var(--border);
  border-radius: 14px;
}
.profile-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid var(--border);
  font-size: 14px;
}
.profile-row:last-child { border-bottom: none; }
.profile-row .label { color: var(--text-muted); flex-shrink: 0; }

.profile-form { margin-top: 8px; }
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
@media (max-width: 480px) {
  .form-row { grid-template-columns: 1fr; }
  .user-stats { grid-template-columns: 1fr; gap: 12px; }
}

.user-menu { margin-top: 12px; overflow: hidden; }
.menu-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 20px;
  font-size: 15px;
}
.menu-item:hover { background: #fafafa; }
.menu-label { display: flex; align-items: center; gap: 8px; }
.arrow { color: var(--text-muted); }

.my-posts { margin-top: 20px; }
.my-posts h3 { font-size: 16px; margin-bottom: 12px; }

.my-post-item {
  display: flex;
  align-items: center;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border);
}
.my-post-item:last-child { border-bottom: none; }
.my-post-link { flex: 1; min-width: 0; }
.my-post-title {
  font-size: 15px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.my-post-meta { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.delete-btn {
  flex-shrink: 0;
  padding: 4px 12px;
  font-size: 13px;
  color: #e74c3c;
  border: 1px solid #e74c3c;
  border-radius: 4px;
  margin-left: 12px;
}
.delete-btn:hover { background: #e74c3c; color: #fff; }

.logout-btn { margin-top: 24px; margin-bottom: 32px; }
</style>
