<template>
  <div class="category-page container">
    <div class="page-title">
      <AppIcon
        :name="getCategoryIcon(currentCategory?.slug, currentCategory?.name)"
        :size="28"
        class="title-icon"
      />
      <h1>{{ currentCategory?.name || '分类' }}</h1>
    </div>

    <!-- 子分类标签 -->
    <div v-if="subCategories.length" class="sub-tags">
      <button
        class="sub-tag"
        :class="{ active: !selectedSub }"
        @click="selectSub(null)"
      >全部</button>
      <button
        v-for="sub in subCategories"
        :key="sub.id"
        class="sub-tag"
        :class="{ active: selectedSub === sub.id }"
        @click="selectSub(sub.id)"
      >{{ sub.name }}</button>
    </div>

    <div v-if="loading" class="loading">加载中...</div>
    <div v-else-if="!posts.length" class="empty-state card">
      <AppIcon name="inbox" :size="48" class="state-icon" />
      <p>该分类暂无信息</p>
    </div>
    <div v-else>
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
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/api'
import PostItem from '@/components/PostItem.vue'
import AppIcon from '@/components/AppIcon.vue'
import { getCategoryIcon } from '@/utils/categoryIcons'

const route = useRoute()
const categories = ref([])
const currentCategory = ref(null)
const subCategories = ref([])
const selectedSub = ref(null)
const posts = ref([])
const loading = ref(true)
const page = ref(1)
const total = ref(0)
const limit = 20

const totalPages = computed(() => Math.ceil(total.value / limit))

async function loadCategories() {
  categories.value = await api.getCategories()
  const catId = Number(route.params.id)
  currentCategory.value = categories.value.find(c => c.id === catId)
  subCategories.value = currentCategory.value?.children || []
}

async function loadPosts() {
  loading.value = true
  try {
    const catId = selectedSub.value || route.params.id
    const data = await api.getPosts({ category_id: catId, page: page.value, limit })
    posts.value = data.list
    total.value = data.total
  } finally {
    loading.value = false
  }
}

function selectSub(id) {
  selectedSub.value = id
  page.value = 1
  loadPosts()
}

function changePage(p) {
  page.value = p
  loadPosts()
  window.scrollTo({ top: 0 })
}

watch(() => route.params.id, async () => {
  selectedSub.value = null
  page.value = 1
  await loadCategories()
  loadPosts()
})

onMounted(async () => {
  await loadCategories()
  loadPosts()
})
</script>

<style scoped>
.page-title {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 0 12px;
}
.title-icon { flex-shrink: 0; color: var(--black); }
.page-title h1 { font-size: 20px; }
.state-icon { color: var(--text-muted); margin-bottom: 12px; }

.sub-tags {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}
.sub-tag {
  padding: 6px 16px;
  border-radius: 16px;
  font-size: 13px;
  background: var(--white);
  color: var(--text-secondary);
  border: 1px solid var(--border);
  transition: all .2s;
}
.sub-tag.active {
  background: var(--primary);
  color: var(--black);
  border-color: var(--black);
  font-weight: 600;
}

.post-list { overflow: hidden; }
</style>
