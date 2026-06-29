<template>
  <div class="user-page container">
    <div v-if="!userStore.isLoggedIn" class="login-prompt card">
      <AppIcon name="user" :size="48" class="prompt-icon" />
      <h2>个人中心</h2>
      <p>登录后查看和管理您的信息与发布</p>
      <router-link to="/login" class="btn btn-primary">立即登录</router-link>
    </div>

    <template v-else>
      <div class="user-layout">
        <aside class="user-sidebar">
          <div class="user-header card">
            <div class="avatar">{{ profile?.username?.[0]?.toUpperCase() || 'U' }}</div>
            <div class="user-info">
              <div class="username-row">
                <span class="username">{{ profile?.username }}</span>
                <span v-if="isStaffUser" class="badge staff">{{ profile?.role_label }}</span>
                <span v-else-if="isActiveVipUser" class="badge vip">{{ profile?.role_label }}</span>
              </div>
              <div class="contact">{{ profile?.email || profile?.phone }}</div>
              <div v-if="locationText" class="meta">
                <AppIcon name="map-pin" :size="14" />
                {{ locationText }}
              </div>
            </div>
          </div>

          <div v-if="isActiveVipUser && profile?.vip_expires_at" class="vip-banner card">
            <AppIcon name="star" :size="18" />
            <div>
              <div class="vip-banner-title">VIP 会员</div>
              <div class="vip-banner-date">到期 {{ formatDateTime(profile.vip_expires_at) }}</div>
            </div>
          </div>

          <div class="user-stats card">
            <div class="stat-item">
              <div class="stat-num">{{ profile?.login_count || 0 }}</div>
              <div class="stat-label">登录次数</div>
            </div>
            <div class="stat-item">
              <div class="stat-num">{{ postsTotal }}</div>
              <div class="stat-label">发布数量</div>
            </div>
            <div class="stat-item">
              <div class="stat-date">{{ formatDate(profile?.created_at) }}</div>
              <div class="stat-label">注册时间</div>
            </div>
          </div>

          <nav class="quick-actions card">
            <router-link v-if="isStaffUser" to="/admin/dashboard" class="action-tile admin">
              <AppIcon name="wrench" :size="22" />
              <span>管理后台</span>
            </router-link>
            <router-link v-if="showUpgradeEntry" to="/upgrade" class="action-tile vip">
              <AppIcon name="star" :size="22" />
              <span>升级 VIP</span>
            </router-link>
            <router-link v-else-if="isActiveVipUser" to="/upgrade" class="action-tile vip">
              <AppIcon name="star" :size="22" />
              <span>VIP 续费</span>
            </router-link>
            <router-link to="/publish" class="action-tile">
              <AppIcon name="pen-line" :size="22" />
              <span>发布信息</span>
            </router-link>
          </nav>

          <button type="button" class="btn btn-outline btn-block logout-btn" @click="logout">
            退出登录
          </button>
        </aside>

        <main class="user-main">
          <div class="profile-card card">
            <div class="card-head">
              <h3>个人资料</h3>
              <button type="button" class="edit-btn" @click="editing = !editing">
                {{ editing ? '取消' : '编辑' }}
              </button>
            </div>

            <div v-if="!editing" class="profile-view">
              <div class="profile-grid">
                <div class="profile-row">
                  <span class="label">真实姓名</span>
                  <span>{{ profile?.real_name || '未填写' }}</span>
                </div>
                <div class="profile-row">
                  <span class="label">性别</span>
                  <span>{{ profile?.gender_label || '保密' }}</span>
                </div>
                <div class="profile-row">
                  <span class="label">账号状态</span>
                  <span>{{ profile?.status_label }}</span>
                </div>
                <div class="profile-row">
                  <span class="label">最后登录</span>
                  <span>{{ formatDateTime(profile?.last_login_at) }}</span>
                </div>
                <div class="profile-row profile-row-full">
                  <span class="label">个人简介</span>
                  <span class="bio-text">{{ profile?.bio || '未填写' }}</span>
                </div>
              </div>
            </div>

            <form v-else class="profile-form" @submit.prevent="saveProfile">
              <div class="profile-form-grid">
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
              </div>
              <CitySelect
                v-model:province="form.province"
                v-model:city="form.city"
                v-model:district="form.district"
                :regions="regions"
                :show-district="true"
              />
              <div class="form-group">
                <label class="form-label">个人简介</label>
                <textarea v-model="form.bio" class="form-textarea" rows="3" maxlength="255"></textarea>
              </div>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                {{ saving ? '保存中...' : '保存资料' }}
              </button>
            </form>
          </div>

          <section class="my-posts">
            <div class="section-head">
              <div>
                <h3>我的发布</h3>
                <p v-if="canPinPostsFlag" class="pin-quota">置顶 {{ pinMeta.pin_count }}/{{ pinMeta.pin_limit }}</p>
              </div>
              <router-link to="/publish" class="section-link">+ 新发一条</router-link>
            </div>

            <div v-if="loading" class="loading card">加载中...</div>
            <div v-else-if="!posts.length" class="empty-state card">
              <AppIcon name="inbox" :size="48" class="state-icon" />
              <p>您还没有发布信息</p>
              <router-link to="/publish" class="btn btn-primary btn-sm">去发布</router-link>
            </div>
            <div v-else class="card post-list">
              <div v-for="post in posts" :key="post.id" class="my-post-item">
                <router-link :to="`/post/${post.id}`" class="my-post-link">
                  <div class="my-post-title-row">
                    <span class="my-post-title">{{ post.title }}</span>
                    <span v-if="isPostPinned(post)" class="post-status top">置顶</span>
                    <span v-else class="post-status" :class="postStatusClass(post.status)">
                      {{ postStatusLabel(post.status) }}
                    </span>
                  </div>
                  <div class="my-post-meta">
                    {{ post.category_name }} · {{ formatDate(post.created_at) }} · {{ post.views }} 浏览
                  </div>
                </router-link>
                <div class="post-actions">
                  <router-link
                    v-if="canEditPost(post)"
                    :to="`/publish/${post.id}`"
                    class="edit-btn"
                  >
                    编辑
                  </router-link>
                  <button
                    v-if="canPinPostsFlag && isPostActive(post.status)"
                    type="button"
                    class="pin-btn"
                    :class="{ active: isPostPinned(post) }"
                    :disabled="pinningId === post.id || (!isPostPinned(post) && pinMeta.pin_count >= pinMeta.pin_limit)"
                    @click="togglePin(post)"
                  >
                    {{ isPostPinned(post) ? '取消置顶' : '置顶' }}
                  </button>
                  <button type="button" class="delete-btn" @click="deletePost(post.id)">删除</button>
                </div>
              </div>
            </div>
          </section>
        </main>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { api } from '@/api'
import { isStaff, isActiveVip, canUploadPostImages, canPinPosts, hasEnabledVipPlans } from '@/utils/roles'
import { useSiteStore } from '@/stores/site'
import CitySelect from '@/components/CitySelect.vue'
import AppIcon from '@/components/AppIcon.vue'

const router = useRouter()
const userStore = useUserStore()
const siteStore = useSiteStore()
const showToast = inject('showToast')

const profile = ref(null)
const posts = ref([])
const postsTotal = ref(0)
const regions = ref([])
const loading = ref(false)
const editing = ref(false)
const saving = ref(false)
const pinningId = ref(null)
const pinMeta = ref({
  can_pin: false,
  pin_count: 0,
  pin_limit: 5,
})
const form = ref({
  username: '',
  real_name: '',
  gender: 0,
  province: '',
  city: '',
  district: '',
  bio: '',
})

const locationText = computed(() => {
  const p = profile.value
  if (!p) return ''
  return [p.province, p.city, p.district].filter(Boolean).join(' ')
})

const isStaffUser = computed(() => isStaff(profile.value?.role))
const isActiveVipUser = computed(() => isActiveVip(profile.value))
const canPinPostsFlag = computed(() => canPinPosts(profile.value))
const showUpgradeEntry = computed(() =>
  !canUploadPostImages(profile.value?.role, profile.value)
    && hasEnabledVipPlans(siteStore.vipUpgrade)
)

function isPostActive(status) {
  return Number(status) === 1
}

function canEditPost(post) {
  return Number(post.status) !== 0
}

function isPostPinned(post) {
  return Number(post?.is_top) === 1
}

function refreshPinMeta(apiData = {}) {
  pinMeta.value = {
    can_pin: canPinPosts(profile.value),
    pin_count: apiData.pin_count != null
      ? Number(apiData.pin_count)
      : posts.value.filter(p => isPostPinned(p)).length,
    pin_limit: Number(apiData.pin_limit || 5),
  }
}
function postStatusLabel(status) {
  return ({ 0: '已下架', 1: '展示中', 2: '待审核' })[Number(status)] ?? '未知'
}

function postStatusClass(status) {
  return ({ 0: 'off', 1: 'on', 2: 'pending' })[Number(status)] ?? ''
}

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
    province: p.province || '',
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
  if (!userStore.isLoggedIn) {
    posts.value = []
    postsTotal.value = 0
    return
  }
  loading.value = true
  try {
    const data = await api.getMyPosts()
    posts.value = data.list || []
    postsTotal.value = Number(data.total || posts.value.length)
    refreshPinMeta(data)
  } finally {
    loading.value = false
  }
}

function resetUserData() {
  profile.value = null
  posts.value = []
  postsTotal.value = 0
  pinMeta.value = { can_pin: false, pin_count: 0, pin_limit: 5 }
}

async function togglePin(post) {
  if (!canPinPostsFlag.value || !isPostActive(post.status)) return
  const next = !isPostPinned(post)
  if (next && pinMeta.value.pin_count >= pinMeta.value.pin_limit) {
    showToast(`最多置顶 ${pinMeta.value.pin_limit} 条信息`)
    return
  }
  pinningId.value = post.id
  try {
    const data = await api.pinPost(post.id, next)
    const updated = data.post
    posts.value = posts.value.map(p => (p.id === post.id ? { ...p, ...updated } : p))
    posts.value.sort((a, b) => Number(b.is_top || 0) - Number(a.is_top || 0))
    refreshPinMeta(data)
    showToast(next ? '已置顶' : '已取消置顶')
  } catch (err) {
    showToast(err.message)
  } finally {
    pinningId.value = null
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
    postsTotal.value = Math.max(0, postsTotal.value - 1)
    await loadPosts()
    showToast('删除成功')
  } catch (err) {
    showToast(err.message)
  }
}

function logout() {
  resetUserData()
  userStore.logout()
  showToast('已退出登录')
  router.push('/')
}

watch(
  () => userStore.token,
  async (token, prev) => {
    if (!token) {
      resetUserData()
      return
    }
    if (token !== prev) {
      resetUserData()
      await loadProfile()
      await loadPosts()
    }
  },
)

onMounted(async () => {
  if (!userStore.isLoggedIn) return
  try {
    await siteStore.load().catch(() => {})
    const regionsData = await api.getRegions().catch(() => ({ regions: [] }))
    if (regionsData?.regions?.length) {
      regions.value = regionsData.regions
    }
    await loadProfile()
    await loadPosts()
  } catch (err) {
    if (err.message?.includes('登录')) {
      userStore.logout()
    }
    showToast(err.message)
  }
})
</script>

<style scoped>
.user-page.container {
  max-width: 860px;
  padding: 16px 16px 40px;
}

.login-prompt {
  text-align: center;
  padding: 56px 24px;
  margin-top: 24px;
  max-width: 420px;
  margin-left: auto;
  margin-right: auto;
}

.login-prompt h2 {
  font-size: 20px;
  font-weight: 800;
  margin-bottom: 8px;
}

.prompt-icon,
.state-icon {
  color: var(--text-muted);
  margin-bottom: 12px;
}

.login-prompt p {
  color: var(--text-muted);
  margin-bottom: 20px;
}

.user-layout {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 16px;
}

.user-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 22px 20px;
}

.avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: var(--black);
  color: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: 700;
  flex-shrink: 0;
}

.username-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.username {
  font-size: 20px;
  font-weight: 800;
}

.badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 10px;
  font-weight: 600;
}

.badge.staff {
  background: var(--black);
  color: var(--primary);
}

.badge.vip {
  background: linear-gradient(135deg, #f8d000 0%, #ffb800 100%);
  color: #5c4a00;
  border: 1px solid rgba(0, 0, 0, 0.12);
}

.contact {
  font-size: 14px;
  color: var(--text-muted);
  margin-top: 4px;
}

.meta {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: var(--text-muted);
  margin-top: 4px;
}

.vip-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border-color: #fde68a;
  color: #92400e;
}

.vip-banner-title {
  font-size: 14px;
  font-weight: 700;
}

.vip-banner-date {
  font-size: 12px;
  margin-top: 2px;
  opacity: 0.9;
}

.user-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  padding: 16px 12px;
  text-align: center;
}

.stat-num {
  font-size: 22px;
  font-weight: 800;
  color: var(--black);
  line-height: 1.2;
}

.stat-label {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
}

.stat-date {
  font-size: 13px;
  font-weight: 700;
  line-height: 1.3;
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1px;
  background: var(--border);
  padding: 0;
  overflow: hidden;
}

.action-tile {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 18px 12px;
  background: var(--white);
  font-size: 13px;
  font-weight: 600;
  text-align: center;
  transition: background 0.15s;
}

.action-tile:hover {
  background: #fafafa;
}

.action-tile.vip {
  color: #b45309;
}

.action-tile.admin {
  color: var(--black);
}

.logout-btn {
  margin-top: 4px;
}

.profile-card {
  padding: 20px;
}

.card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.card-head h3,
.section-head h3 {
  font-size: 17px;
  font-weight: 800;
}

.edit-btn {
  font-size: 13px;
  color: var(--black);
  font-weight: 600;
  padding: 6px 14px;
  border: 1px solid var(--border);
  border-radius: 999px;
  background: var(--white);
}

.edit-btn:hover {
  background: #fafafa;
}

.profile-grid {
  display: grid;
  gap: 0;
}

.profile-row {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
  font-size: 14px;
}

.profile-row:last-child {
  border-bottom: none;
}

.profile-row-full {
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
}

.profile-row .label {
  color: var(--text-muted);
  flex-shrink: 0;
}

.bio-text {
  line-height: 1.6;
  word-break: break-word;
}

.profile-form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 4px;
}

.profile-form-grid .form-group:last-child:nth-child(odd) {
  grid-column: 1 / -1;
}

.pin-quota {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
}

.section-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.section-link {
  font-size: 13px;
  font-weight: 600;
  color: var(--black);
}

.section-link:hover {
  text-decoration: underline;
}

.my-posts {
  margin-top: 16px;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
}

.empty-state p {
  color: var(--text-muted);
  margin: 8px 0 16px;
}

.my-post-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border);
}

.my-post-item:last-child {
  border-bottom: none;
}

.my-post-link {
  flex: 1;
  min-width: 0;
}

.my-post-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.my-post-title {
  font-size: 15px;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  min-width: 0;
}

.post-status {
  flex-shrink: 0;
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 999px;
  font-weight: 600;
}

.post-status.on {
  background: #ecfdf5;
  color: #047857;
}

.post-status.pending {
  background: #fffbeb;
  color: #b45309;
}

.post-status.off {
  background: #f3f4f6;
  color: #6b7280;
}

.post-status.top {
  background: var(--black);
  color: var(--primary);
}

.my-post-meta {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
}

.post-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex-shrink: 0;
}

.edit-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
  color: var(--black);
  border: 1px solid var(--border);
  border-radius: 6px;
  background: #fff;
  text-decoration: none;
}

.edit-btn:hover {
  border-color: var(--black);
  background: var(--primary-light);
}

.pin-btn {
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 600;
  color: #b45309;
  border: 1px solid #fde68a;
  border-radius: 6px;
  background: #fffbeb;
}

.pin-btn.active {
  color: var(--black);
  border-color: var(--black);
  background: var(--primary-light);
}

.pin-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.delete-btn {
  flex-shrink: 0;
  padding: 6px 12px;
  font-size: 12px;
  color: #dc2626;
  border: 1px solid #fecaca;
  border-radius: 6px;
  background: #fff;
}

.delete-btn:hover {
  background: #fef2f2;
}

@media (min-width: 1024px) {
  .post-actions {
    flex-direction: row;
    align-items: center;
  }

  .user-page.container {
    max-width: var(--max-width);
    padding: 24px 20px 48px;
  }

  .user-layout {
    display: grid;
    grid-template-columns: 300px minmax(0, 1fr);
    gap: 24px;
    align-items: start;
    margin-top: 20px;
  }

  .user-sidebar {
    position: sticky;
    top: calc(var(--header-h) + 16px);
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .user-stats {
    grid-template-columns: 1fr;
    text-align: left;
    padding: 8px 16px;
  }

  .stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
  }

  .stat-item:last-child {
    border-bottom: none;
  }

  .stat-label {
    margin-top: 0;
    order: -1;
  }

  .profile-grid {
    grid-template-columns: 1fr 1fr;
    gap: 0 32px;
  }

  .profile-row-full {
    grid-column: 1 / -1;
  }

  .profile-form-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 480px) {
  .user-stats {
    grid-template-columns: 1fr;
    text-align: left;
    padding: 8px 16px;
  }

  .stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
  }

  .stat-item:last-child {
    border-bottom: none;
  }

  .stat-label {
    margin-top: 0;
  }

  .profile-form-grid {
    grid-template-columns: 1fr;
  }

  .quick-actions {
    grid-template-columns: repeat(3, 1fr);
  }

  .quick-actions .action-tile:last-child:nth-child(odd) {
    grid-column: span 1;
  }
}
</style>
