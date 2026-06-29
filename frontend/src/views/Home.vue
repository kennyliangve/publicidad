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
      <div v-else class="card post-list">
        <PostItem v-for="post in posts" :key="post.id" :post="post" />
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from '@/api'
import PostItem from '@/components/PostItem.vue'
import AppIcon from '@/components/AppIcon.vue'
import { getCategoryIcon } from '@/utils/categoryIcons'

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
      api.getPosts({ page: 1, limit: 20 }),
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
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  background: var(--white);
  border-radius: var(--radius);
  padding: 20px 16px;
  box-shadow: var(--shadow);
}
@media (min-width: 769px) {
  .category-grid { grid-template-columns: repeat(8, 1fr); }
}
.category-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 8px 4px;
  border-radius: 8px;
  transition: background .15s;
}
.category-item:hover { background: var(--primary); color: var(--black); }
.cat-icon { color: var(--black); }
.cat-name { font-size: 13px; color: var(--text); }

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
.state-icon { color: var(--text-muted); margin-bottom: 12px; }
</style>
