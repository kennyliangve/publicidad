<template>
  <router-link :to="`/post/${post.id}`" class="post-item">
    <div class="post-thumb">
      <img v-if="post.images?.length" :src="post.images[0]" :alt="post.title" />
      <AppIcon v-else :name="iconName" :size="32" class="thumb-icon" />
    </div>
    <div class="post-info">
      <div class="post-title">{{ post.title }}</div>
      <div class="post-meta">
        <span v-if="post.city">{{ post.city }}{{ post.district ? ' · ' + post.district : '' }}</span>
        <span>{{ post.category_name }}</span>
        <span>{{ formatTime(post.created_at) }}</span>
      </div>
      <div v-if="post.price" class="post-price">
        {{ formatPrice(post.price) }}<span class="unit">{{ post.price_unit }}</span>
      </div>
    </div>
  </router-link>
</template>

<script setup>
import { computed } from 'vue'
import AppIcon from '@/components/AppIcon.vue'
import { getCategoryIcon } from '@/utils/categoryIcons'

const props = defineProps({
  post: { type: Object, required: true },
})

const iconName = computed(() =>
  getCategoryIcon(props.post.category_slug, props.post.category_name || '')
)

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
.thumb-icon {
  color: var(--text-muted);
}
</style>
