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
      <div class="mobile-search-box" @click="$router.push('/search')">
        <AppIcon name="search" :size="16" />
        搜索招聘、租房、二手车...
      </div>
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
        <div class="post-list hide-desktop card">
          <PostItem v-for="post in posts" :key="post.id" :post="post" />
        </div>
        <div class="post-grid hide-mobile">
          <PostCard v-for="post in posts" :key="post.id" :post="post" />
        </div>
      </template>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '@/api'
import { useSiteStore } from '@/stores/site'
import PostItem from '@/components/PostItem.vue'
import PostCard from '@/components/PostCard.vue'
import AppIcon from '@/components/AppIcon.vue'
import { getCategoryIcon } from '@/utils/categoryIcons'

const siteStore = useSiteStore()
const categories = ref([])
const posts = ref([])
const loading = ref(true)
const error = ref('')

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
.category-nav { padding-top: 16px; }
.category-grid {
  display: flex;
  flex-wrap: nowrap;
  gap: 8px;
  background: var(--white);
  border-radius: var(--radius);
  padding: 16px 12px;
  box-shadow: var(--shadow);
  overflow-x: auto;
  overflow-y: hidden;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.category-grid::-webkit-scrollbar {
  display: none;
}
.category-item {
  flex: 0 0 auto;
  min-width: 68px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 8px 4px;
  border-radius: 8px;
  transition: background .15s;
}
@media (min-width: 769px) {
  .category-grid {
    gap: 12px;
    padding: 20px 16px;
    overflow-x: visible;
  }
  .category-item {
    flex: 1 1 0;
    min-width: 0;
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

.mobile-search-section { padding: 12px 0; }
.mobile-search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--white);
  padding: 10px 16px;
  border-radius: 20px;
  color: var(--text-muted);
  font-size: 14px;
  box-shadow: var(--shadow);
  cursor: pointer;
}

.latest-posts { margin-top: 16px; }
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

.post-list { overflow: hidden; }

.post-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

@media (min-width: 769px) and (max-width: 1200px) {
  .post-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (min-width: 769px) and (max-width: 960px) {
  .post-grid { grid-template-columns: repeat(2, 1fr); }
}
.state-icon { color: var(--text-muted); margin-bottom: 12px; }
</style>
