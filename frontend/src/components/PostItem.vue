<template>
  <router-link
    :to="`/post/${post.id}`"
    class="post-item"
    :class="{ 'post-item--no-thumb': !post.images?.length }"
  >
    <div v-if="post.images?.length" class="post-thumb">
      <img :src="resolveAssetUrl(post.images[0])" :alt="post.title" />
    </div>
    <div class="post-info">
      <div class="post-title">{{ post.title }}</div>
      <div class="post-meta">
        <span v-if="post.city || post.province">{{ [post.province, post.city, post.district].filter(Boolean).join(' · ') }}</span>
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
import { resolveAssetUrl } from '@/utils/asset'
import { formatPostPrice, formatRelativeTime } from '@/utils/post'

const props = defineProps({
  post: { type: Object, required: true },
})

function formatPrice(price) {
  return formatPostPrice(price)
}

function formatTime(dateStr) {
  return formatRelativeTime(dateStr)
}
</script>

<style scoped>
.post-item--no-thumb {
  gap: 0;
}
</style>
