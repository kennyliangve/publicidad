<template>
  <div class="home">
    <section class="category-nav container">
      <div class="category-grid">
        <router-link
          v-for="cat in categories"
          :key="cat.id"
          :to="`/category/${cat.id}`"
          class="category-item"
        >
          <AppIcon :name="getCategoryIcon(cat.slug, cat.name)" :size="28" class="cat-icon" />
          <span class="cat-name">{{ cat.name }}</span>
        </router-link>
      </div>
    </section>

    <section class="mobile-search-section hide-desktop container">
      <form class="mobile-search-box" @submit.prevent="submitSearch">
        <AppIcon name="search" :size="18" class="search-box-icon" />
        <input
          v-model="searchKeyword"
          type="search"
          class="search-box-input"
          placeholder="搜索招聘、租房..."
          enterkeyhint="search"
          autocomplete="off"
          maxlength="100"
        />
        <button type="submit" class="search-box-btn">搜索</button>
      </form>
    </section>

    <section class="latest-posts container">
      <div class="section-header">
        <h2>最新信息</h2>
        <router-link to="/search" class="more-link">
          查看更多 <AppIcon name="chevron-right" :size="16" />
        </router-link>
      </div>

      <div v-if="loading" class="loading">加载中...</div>
      <div v-else-if="error" class="empty-state card">
        <AppIcon name="alert-triangle" :size="48" class="state-icon" />
        <p>{{ error }}</p>
        <button class="btn btn-primary btn-sm" style="margin-top:12px" @click="loadData">重新加载</button>
      </div>
      <div v-else-if="!posts.length" class="empty-state">
        <AppIcon name="inbox" :size="48" class="state-icon" />
        <p>暂无信息，快来发布第一条吧</p>
      </div>
      <template v-else>
        <div class="post-grid">
          <PostCard v-for="post in posts" :key="post.id" :post="post" />
        </div>
      </template>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/api'
import { useSiteStore } from '@/stores/site'
import PostCard from '@/components/PostCard.vue'
import AppIcon from '@/components/AppIcon.vue'
import { getCategoryIcon } from '@/utils/categoryIcons'

const siteStore = useSiteStore()
const router = useRouter()
const categories = ref([])
const posts = ref([])
const loading = ref(true)
const error = ref('')
const searchKeyword = ref('')

function submitSearch() {
  const q = searchKeyword.value.trim()
  router.push(q ? { path: '/search', query: { q } } : { path: '/search' })
}

async function loadData() {
  loading.value = true
  error.value = ''
  try {
    const [cats, postData] = await Promise.all([
      api.getCategories(),
      api.getPosts({ page: 1, limit: siteStore.postsPerPage }),
    ])
    categories.value = cats
    posts.value = postData.list
  } catch (e) {
    error.value = e.message || '数据加载失败，请检查网络或 API 配置'
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.home {
  width: 100%;
  max-width: 100%;
  overflow-x: clip;
}

.category-nav { padding-top: 16px; }
.category-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px 4px;
  background: var(--white);
  border-radius: var(--radius);
  padding: 16px 12px;
  box-shadow: var(--shadow);
}
.category-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 8px 2px;
  border-radius: 8px;
  transition: background .15s;
  min-width: 0;
}
@media (min-width: 769px) {
  .category-grid {
    display: flex;
    flex-wrap: nowrap;
    gap: 12px;
    padding: 20px 16px;
  }
  .category-item {
    flex: 1 1 0;
    padding: 8px 4px;
  }
}
.category-item:hover { background: var(--primary); color: var(--black); }
.cat-icon { color: var(--black); flex-shrink: 0; }
.cat-name {
  font-size: 12px;
  color: var(--text);
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
  line-height: 1.3;
}
@media (min-width: 769px) {
  .cat-name { font-size: 13px; }
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

.mobile-search-section {
  width: 100%;
  max-width: 100%;
  min-width: 0;
  margin-top: 12px;
  /* 勿设 padding:0，否则会覆盖 .container 的左右 16px 边距 */
}

.mobile-search-box {
  display: flex;
  align-items: center;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  gap: 6px;
  background: var(--white);
  padding: 4px 4px 4px 12px;
  border-radius: 0;
  box-shadow: var(--shadow);
  border: 1px solid rgba(0, 0, 0, 0.04);
  box-sizing: border-box;
  overflow: hidden;
}

.mobile-search-box:focus-within {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.search-box-icon {
  color: var(--text-muted);
  flex-shrink: 0;
  width: 18px;
  height: 18px;
}

.search-box-input {
  flex: 1 1 0;
  min-width: 0;
  width: 0;
  border: none;
  background: transparent;
  font-size: 14px;
  color: var(--text);
  padding: 8px 0;
}

.search-box-input::placeholder {
  color: var(--text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.search-box-input:focus {
  outline: none;
}

.search-box-input::-webkit-search-cancel-button {
  -webkit-appearance: none;
}

.search-box-btn {
  flex: 0 0 auto;
  height: 34px;
  padding: 0 12px;
  border-radius: 0;
  background: var(--black);
  color: var(--primary);
  font-size: 13px;
  font-weight: 700;
  white-space: nowrap;
}

@media (max-width: 360px) {
  .mobile-search-box {
    gap: 4px;
    padding-left: 10px;
  }

  .search-box-input {
    font-size: 13px;
  }

  .search-box-btn {
    padding: 0 10px;
    font-size: 12px;
  }
}

.latest-posts { margin-top: 12px; }
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.section-header h2 { font-size: 18px; }
.more-link {
  display: flex;
  align-items: center;
  gap: 2px;
  font-size: 14px;
  color: var(--text-muted);
}
.more-link:hover { color: var(--black); }

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
  .post-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (min-width: 769px) and (max-width: 960px) {
  .post-grid { grid-template-columns: repeat(2, 1fr); }
}
.state-icon { color: var(--text-muted); margin-bottom: 12px; }
</style>
