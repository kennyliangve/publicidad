<template>
  <div class="search-page container">
    <div class="search-box">
      <input
        ref="inputRef"
        v-model="keyword"
        type="search"
        class="form-input search-input"
        placeholder="搜索招聘、租房、二手车..."
        enterkeyhint="search"
        autocomplete="off"
        maxlength="100"
        @keyup.enter="doSearch"
      />
      <button type="button" class="btn btn-primary" @click="doSearch">搜索</button>
    </div>

    <div v-if="searched">
      <div v-if="loading" class="loading">搜索中...</div>
      <div v-else-if="!posts.length" class="empty-state card">
        <AppIcon name="search-x" :size="48" class="state-icon" />
        <p>未找到相关信息</p>
      </div>
      <div v-else>
        <div class="result-count">找到 {{ total }} 条相关信息</div>
        <div class="card post-list">
          <PostItem v-for="post in posts" :key="post.id" :post="post" />
        </div>
        <div v-if="totalPages > 1" class="pagination">
          <button :disabled="page <= 1" @click="changePage(page - 1)">上一页</button>
          <button class="active">{{ page }} / {{ totalPages }}</button>
          <button :disabled="page >= totalPages" @click="changePage(page + 1)">下一页</button>
        </div>
      </div>
    </div>

    <div v-else class="hot-keywords">
      <h3>热门搜索</h3>
      <div class="keyword-tags">
        <button v-for="kw in hotKeywords" :key="kw" class="keyword-tag" @click="searchKeyword(kw)">
          {{ kw }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/api'
import { useSiteStore } from '@/stores/site'
import PostItem from '@/components/PostItem.vue'
import AppIcon from '@/components/AppIcon.vue'

const route = useRoute()
const router = useRouter()
const siteStore = useSiteStore()
const inputRef = ref(null)
const keyword = ref(typeof route.query.q === 'string' ? route.query.q : '')
const posts = ref([])
const loading = ref(false)
const searched = ref(false)
const page = ref(1)
const total = ref(0)
const limit = computed(() => siteStore.postsPerPage)

const hotKeywords = ['前端开发', '整租', '二手车', '家政保洁', 'iPhone', '兼职']
const totalPages = computed(() => Math.ceil(total.value / limit.value))

function focusInput() {
  nextTick(() => inputRef.value?.focus())
}

async function doSearch() {
  const q = keyword.value.trim()
  if (!q) {
    searched.value = false
    router.replace({ path: '/search' })
    focusInput()
    return
  }

  if (route.query.q !== q) {
    router.replace({ path: '/search', query: { q } })
  }

  searched.value = true
  page.value = 1
  await fetchPosts()
}

function searchKeyword(kw) {
  keyword.value = kw
  doSearch()
}

async function fetchPosts() {
  loading.value = true
  try {
    const data = await api.getPosts({ keyword: keyword.value.trim(), page: page.value, limit: limit.value })
    posts.value = data.list
    total.value = data.total
  } finally {
    loading.value = false
  }
}

function changePage(p) {
  page.value = p
  fetchPosts()
  window.scrollTo({ top: 0 })
}

watch(
  () => route.query.q,
  (q) => {
    const next = typeof q === 'string' ? q : ''
    if (keyword.value !== next) {
      keyword.value = next
    }
    if (next) {
      searched.value = true
      page.value = 1
      fetchPosts()
    } else if (route.name === 'Search') {
      searched.value = false
      posts.value = []
      total.value = 0
    }
  }
)

onMounted(() => {
  if (keyword.value) {
    searched.value = true
    fetchPosts()
  }
  focusInput()
})
</script>

<style scoped>
.search-box {
  display: flex;
  gap: 8px;
  padding: 16px 0;
}
.search-input { flex: 1; }

.result-count {
  font-size: 14px;
  color: var(--text-muted);
  margin-bottom: 12px;
}

.hot-keywords { padding: 20px 0; }
.hot-keywords h3 { font-size: 16px; margin-bottom: 12px; }
.keyword-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.keyword-tag {
  padding: 6px 16px;
  background: var(--white);
  border-radius: 16px;
  font-size: 14px;
  color: var(--text-secondary);
  border: 1px solid var(--border);
  transition: all .2s;
}
.keyword-tag:hover { border-color: var(--black); color: var(--black); background: var(--primary); }

.post-list { overflow: hidden; }
.state-icon { color: var(--text-muted); margin-bottom: 12px; }
</style>
