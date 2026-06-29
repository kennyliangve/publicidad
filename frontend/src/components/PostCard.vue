<template>
  <router-link :to="`/post/${post.id}`" class="post-card">
    <div class="post-card-preview">
      <span v-if="post.category_name" class="preview-category">{{ post.category_name }}</span>
      <p class="preview-text">{{ contentExcerpt }}</p>
    </div>
    <div class="post-card-body">
      <h3 class="post-card-title">{{ post.title }}</h3>
      <div class="post-card-meta">
        <div class="meta-left">
          <span v-if="post.city || post.province" class="meta-loc">
            <AppIcon name="map-pin" :size="12" />
            {{ [post.province, post.city, post.district].filter(Boolean).join(' · ') }}
          </span>
          <span>{{ formatTime(post.created_at) }}</span>
        </div>
        <span v-if="post.price" class="post-card-price">
          {{ formatPrice(post.price) }}<span class="unit">{{ post.price_unit }}</span>
        </span>
      </div>
    </div>
  </router-link>
</template>

<script setup>
import { computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import { postContentExcerpt } from '@/utils/post'

const props = defineProps({
  post: { type: Object, required: true },
})

const contentExcerpt = computed(() => postContentExcerpt(props.post.content))

function formatPrice(price) {
  if (price >= 10000) return (price / 10000).toFixed(price % 10000 === 0 ? 0 : 1) + '万'
  return price
}

function formatTime(dateStr) {
  const d = new Date(dateStr)
  const now = new Date()
  const diff = (now - d) / 1000
  if (diff < 3600) return Math.floor(diff / 60) + '分钟前'
  if (diff < 86400) return Math.floor(diff / 3600) + '小时前'
  if (diff < 604800) return Math.floor(diff / 86400) + '天前'
  return d.toLocaleDateString('zh-CN')
}
</script>

<style scoped>
.post-card {
  display: flex;
  flex-direction: column;
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
  transition: transform 0.18s, box-shadow 0.18s;
  cursor: pointer;
  height: 100%;
}

.post-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.post-card-preview {
  position: relative;
  aspect-ratio: 4 / 3;
  padding: 14px;
  background: linear-gradient(160deg, #fffef5 0%, #f7f7f7 100%);
  border-bottom: 1px solid var(--border);
  overflow: hidden;
}

.preview-category {
  display: inline-block;
  margin-bottom: 8px;
  padding: 2px 8px;
  border-radius: 4px;
  background: rgba(248, 208, 0, 0.35);
  color: #665500;
  font-size: 11px;
  font-weight: 600;
}

.preview-text {
  font-size: 14px;
  font-weight: 700;
  line-height: 1.6;
  color: #222;
  display: -webkit-box;
  -webkit-line-clamp: 5;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin: 0;
}

.post-card-price {
  flex-shrink: 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--black);
  line-height: 1.3;
  white-space: nowrap;
}

.post-card-price .unit {
  font-size: 12px;
  font-weight: 500;
  margin-left: 2px;
  color: var(--text-muted);
}

.post-card-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px 14px 14px;
  flex: 1;
}

.post-card-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--text);
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.post-card-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-top: auto;
}

.meta-left {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 10px;
  flex: 1;
  min-width: 0;
  font-size: 12px;
  color: var(--text-muted);
}

.meta-loc {
  display: inline-flex;
  align-items: center;
  gap: 3px;
}
</style>
