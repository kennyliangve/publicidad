<template>
  <div class="category-page">
    <div v-if="notFound && !error" class="container empty-state card">
      <AppIcon name="search-x" :size="48" class="state-icon" />
      <p>分类不存在</p>
      <router-link to="/" class="btn btn-primary btn-sm">回首页</router-link>
    </div>

    <template v-else-if="error && !categories.length">
      <div class="container empty-state card">
        <AppIcon name="alert-triangle" :size="48" class="state-icon" />
        <p>{{ error }}</p>
        <button type="button" class="btn btn-primary btn-sm" @click="initPage">重新加载</button>
      </div>
    </template>

    <template v-else>
      <!-- 分类快捷导航 -->
      <section class="category-nav container">
        <div class="category-grid">
          <router-link
            v-for="cat in categories"
            :key="cat.id"
            :to="categoryLink(cat.id)"
            class="category-item"
            :class="{ active: Number(currentCategory?.id) === Number(cat.id) }"
          >
            <AppIcon :name="getCategoryIcon(cat.slug, cat.name)" :size="26" class="cat-icon" />
            <span class="cat-name">{{ cat.name }}</span>
          </router-link>
        </div>
      </section>

      <div class="container category-body">
        <nav class="breadcrumb">
          <router-link to="/">首页</router-link>
          <AppIcon name="chevron-right" :size="14" />
          <span>{{ currentCategory?.name || '分类' }}</span>
        </nav>

        <div class="page-header card">
          <div class="header-main">
            <AppIcon
              :name="getCategoryIcon(currentCategory?.slug, currentCategory?.name)"
              :size="32"
              class="header-icon"
            />
            <div>
              <h1>{{ currentCategory?.name || '分类' }}</h1>
              <p v-if="!loading" class="header-count">共 {{ total }} 条信息</p>
            </div>
          </div>
          <router-link :to="publishLink" class="btn-publish">
            <AppIcon name="plus" :size="16" />
            发布信息
          </router-link>
        </div>

        <!-- 子分类 -->
        <div v-if="subCategories.length" class="sub-tags">
          <button
            type="button"
            class="sub-tag"
            :class="{ active: !selectedSub }"
            @click="selectSub(null)"
          >全部</button>
          <button
            v-for="sub in subCategories"
            :key="sub.id"
            type="button"
            class="sub-tag"
            :class="{ active: selectedSub === Number(sub.id) }"
            @click="selectSub(sub.id)"
          >{{ sub.name }}</button>
        </div>

        <!-- 筛选 -->
        <div class="filter-bar card">
          <div class="filter-search">
            <AppIcon name="search" :size="16" class="filter-icon" />
            <input
              v-model="keyword"
              type="search"
              class="filter-input"
              placeholder="在本分类中搜索..."
              enterkeyhint="search"
              autocomplete="off"
              maxlength="100"
              @keyup.enter="applyFilters"
            />
          </div>
          <select v-model="filterCity" class="filter-select" @change="applyFilters">
            <option value="">全部城市</option>
            <option v-for="city in cityOptions" :key="city" :value="city">{{ city }}</option>
          </select>
          <button
            v-if="hasActiveFilters"
            type="button"
            class="filter-clear"
            @click="clearFilters"
          >清除</button>
          <button type="button" class="btn btn-primary btn-sm filter-btn" @click="applyFilters">搜索</button>
        </div>

        <div v-if="loading" class="loading">加载中...</div>
        <div v-else-if="error" class="empty-state card">
          <AppIcon name="alert-triangle" :size="48" class="state-icon" />
          <p>{{ error }}</p>
          <button type="button" class="btn btn-primary btn-sm" @click="loadPosts">重新加载</button>
        </div>
        <div v-else-if="!posts.length" class="empty-state card">
          <AppIcon name="inbox" :size="48" class="state-icon" />
          <p>{{ hasActiveFilters ? '没有符合条件的信息' : '该分类暂无信息' }}</p>
          <router-link v-if="!hasActiveFilters" :to="publishLink" class="btn btn-primary btn-sm">
            发布第一条
          </router-link>
          <button v-else type="button" class="btn btn-outline btn-sm" @click="clearFilters">清除筛选</button>
        </div>
        <div v-else>
          <div v-if="hasActiveFilters" class="result-hint">
            找到 {{ total }} 条结果
            <span v-if="filterCity"> · {{ filterCity }}</span>
            <span v-if="keyword.trim()"> · 「{{ keyword.trim() }}」</span>
          </div>

          <div class="post-grid">
            <PostCard v-for="post in posts" :key="post.id" :post="post" />
          </div>

          <div v-if="totalPages > 1" class="pagination">
            <button type="button" :disabled="page <= 1" @click="changePage(page - 1)">上一页</button>
            <button type="button" class="active">{{ page }} / {{ totalPages }}</button>
            <button type="button" :disabled="page >= totalPages" @click="changePage(page + 1)">下一页</button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/api'
import { useSiteStore } from '@/stores/site'
import PostCard from '@/components/PostCard.vue'
import AppIcon from '@/components/AppIcon.vue'
import { getCategoryIcon } from '@/utils/categoryIcons'

const route = useRoute()
const router = useRouter()
const siteStore = useSiteStore()

const categories = ref([])
const regions = ref([])
const currentCategory = ref(null)
const subCategories = ref([])
const selectedSub = ref(null)
const keyword = ref('')
const filterCity = ref('')
const posts = ref([])
const loading = ref(true)
const error = ref('')
const notFound = ref(false)
const page = ref(1)
const total = ref(0)
const limit = computed(() => siteStore.postsPerPage)
const totalPages = computed(() => Math.max(1, Math.ceil(total.value / limit.value)))

const activeCategoryId = computed(() => selectedSub.value || Number(route.params.id))

const hasActiveFilters = computed(() =>
  Boolean(keyword.value.trim() || filterCity.value)
)

const cityOptions = computed(() => {
  const list = []
  for (const r of regions.value) {
    for (const c of r.cities || []) {
      if (c && !list.includes(c)) list.push(c)
    }
  }
  return list.sort((a, b) => a.localeCompare(b, 'zh-CN'))
})

const publishLink = computed(() => ({
  path: '/publish',
  query: { category: activeCategoryId.value },
}))

function categoryLink(id) {
  return { path: `/category/${id}` }
}

/** 在一级/二级分类中解析 ID（兼容 API 字符串 id） */
function findCategoryById(catId, cats) {
  const id = Number(catId)
  if (!id || !Array.isArray(cats)) return null

  for (const parent of cats) {
    if (Number(parent.id) === id) {
      return { parent, subId: null }
    }
    for (const sub of parent.children || []) {
      if (Number(sub.id) === id) {
        return { parent, subId: id }
      }
    }
  }
  return null
}

function readQuery() {
  const sub = route.query.sub
  selectedSub.value = sub ? Number(sub) : null
  keyword.value = typeof route.query.q === 'string' ? route.query.q : ''
  filterCity.value = typeof route.query.city === 'string' ? route.query.city : ''
  page.value = Math.max(1, Number(route.query.page) || 1)
}

function buildQuery() {
  const query = {}
  if (selectedSub.value) query.sub = String(selectedSub.value)
  if (keyword.value.trim()) query.q = keyword.value.trim()
  if (filterCity.value) query.city = filterCity.value
  if (page.value > 1) query.page = String(page.value)
  return query
}

function syncRouteQuery() {
  router.replace({ path: route.path, query: buildQuery() })
}

async function loadCategories() {
  notFound.value = false
  error.value = ''
  const catId = Number(route.params.id)
  if (!catId) {
    notFound.value = true
    loading.value = false
    return
  }

  try {
    const [cats, regionsData] = await Promise.all([
      api.getCategories(),
      api.getRegions().catch(() => ({ regions: [] })),
    ])
    categories.value = Array.isArray(cats) ? cats : []
    regions.value = regionsData?.regions || []
  } catch (e) {
    error.value = e.message || '分类加载失败'
    loading.value = false
    return
  }

  const resolved = findCategoryById(catId, categories.value)
  if (!resolved) {
    notFound.value = true
    loading.value = false
    return
  }

  currentCategory.value = resolved.parent
  subCategories.value = resolved.parent.children || []

  // URL 为子分类 ID（如 /category/9）时自动选中
  if (resolved.subId && !route.query.sub) {
    selectedSub.value = resolved.subId
  }

  if (selectedSub.value && !subCategories.value.some(s => Number(s.id) === selectedSub.value)) {
    selectedSub.value = null
  }

  document.title = `${currentCategory.value.name} - ${siteStore.siteName}`
}

async function loadPosts() {
  if (notFound.value) return
  loading.value = true
  error.value = ''
  try {
    const params = {
      category_id: activeCategoryId.value,
      page: page.value,
      limit: limit.value,
    }
    const q = keyword.value.trim()
    if (q) params.keyword = q
    if (filterCity.value) params.city = filterCity.value

    const data = await api.getPosts(params)
    posts.value = data.list
    total.value = data.total
  } catch (e) {
    error.value = e.message || '加载失败，请稍后重试'
    posts.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

function selectSub(id) {
  selectedSub.value = id
  page.value = 1
  syncRouteQuery()
}

function applyFilters() {
  page.value = 1
  syncRouteQuery()
}

function clearFilters() {
  keyword.value = ''
  filterCity.value = ''
  page.value = 1
  syncRouteQuery()
}

function changePage(p) {
  page.value = p
  syncRouteQuery()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function initPage() {
  readQuery()
  loading.value = true
  await loadCategories()
  if (!notFound.value) {
    await loadPosts()
  }
}

watch(() => route.params.id, async (id, prev) => {
  if (prev && id !== prev) {
    selectedSub.value = null
    keyword.value = ''
    filterCity.value = ''
    page.value = 1
  }
  await initPage()
})

watch(
  () => route.query,
  () => {
    if (route.name !== 'Category' || notFound.value) return
    readQuery()
    loadPosts()
  }
)

onMounted(initPage)
</script>

<style scoped>
.category-page {
  padding-bottom: 24px;
}

.category-nav {
  padding-top: 12px;
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px 4px;
  background: var(--white);
  border-radius: var(--radius);
  padding: 14px 12px;
  box-shadow: var(--shadow);
}

.category-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 8px 2px;
  border-radius: 8px;
  transition: background 0.15s;
  min-width: 0;
}

.category-item.active {
  background: var(--primary);
  color: var(--black);
}

.category-item:hover {
  background: var(--primary-light);
}

.category-item.active:hover {
  background: var(--primary);
}

.cat-icon {
  color: var(--black);
  flex-shrink: 0;
}

.cat-name {
  font-size: 12px;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
  line-height: 1.3;
}

@media (min-width: 769px) {
  .category-grid {
    display: flex;
    flex-wrap: nowrap;
    gap: 12px;
    padding: 16px;
  }

  .category-item {
    flex: 1 1 0;
    padding: 8px 4px;
  }

  .cat-name {
    font-size: 13px;
  }
}

@media (max-width: 360px) {
  .category-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 10px 4px;
  }

  .cat-name {
    font-size: 11px;
  }
}

.category-body {
  padding-top: 12px;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 12px;
}

.breadcrumb a:hover {
  color: var(--black);
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 18px;
  margin-bottom: 12px;
}

.header-main {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.header-icon {
  flex-shrink: 0;
  color: var(--black);
}

.page-header h1 {
  font-size: 20px;
  line-height: 1.3;
}

.header-count {
  font-size: 13px;
  color: var(--text-muted);
  margin-top: 4px;
}

.btn-publish {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
  height: 38px;
  padding: 0 16px;
  border-radius: 20px;
  background: var(--black);
  color: var(--primary);
  font-size: 14px;
  font-weight: 700;
  transition: opacity 0.15s;
}

.btn-publish:hover {
  opacity: 0.9;
}

.sub-tags {
  display: flex;
  gap: 8px;
  flex-wrap: nowrap;
  overflow-x: auto;
  margin-bottom: 12px;
  padding-bottom: 4px;
  scrollbar-width: none;
}

.sub-tags::-webkit-scrollbar {
  display: none;
}

.sub-tag {
  flex-shrink: 0;
  padding: 6px 16px;
  border-radius: 16px;
  font-size: 13px;
  background: var(--white);
  color: var(--text-secondary);
  border: 1px solid var(--border);
  transition: all 0.2s;
}

.sub-tag.active {
  background: var(--primary);
  color: var(--black);
  border-color: var(--black);
  font-weight: 600;
}

.filter-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  padding: 12px;
  margin-bottom: 16px;
}

.filter-search {
  flex: 1;
  min-width: 160px;
  display: flex;
  align-items: center;
  gap: 8px;
  height: 38px;
  padding: 0 12px;
  background: var(--bg);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.filter-search:focus-within {
  border-color: var(--black);
}

.filter-icon {
  color: var(--text-muted);
  flex-shrink: 0;
}

.filter-input {
  flex: 1;
  min-width: 0;
  border: none;
  background: transparent;
  font-size: 14px;
  padding: 0;
}

.filter-input:focus {
  outline: none;
}

.filter-select {
  height: 38px;
  padding: 0 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--white);
  font-size: 14px;
  min-width: 120px;
}

.filter-clear {
  font-size: 13px;
  color: var(--text-muted);
  padding: 8px 10px;
}

.filter-clear:hover {
  color: var(--black);
}

.filter-btn {
  flex-shrink: 0;
}

.result-hint {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 12px;
}

.post-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

@media (min-width: 769px) {
  .post-grid {
    gap: 16px;
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (min-width: 769px) and (max-width: 1200px) {
  .post-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 769px) and (max-width: 960px) {
  .post-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.empty-state {
  text-align: center;
  padding: 40px 24px;
}

.state-icon {
  color: var(--text-muted);
  margin-bottom: 12px;
}

.empty-state p {
  color: var(--text-muted);
  margin-bottom: 14px;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-publish {
    justify-content: center;
  }

  .filter-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-select,
  .filter-btn {
    width: 100%;
  }
}
</style>
