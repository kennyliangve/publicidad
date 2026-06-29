<template>
  <div class="detail-page container">
    <div v-if="loading" class="loading">加载中...</div>
    <template v-else-if="post">
      <div class="detail-card card">
        <h1 class="detail-title">{{ post.title }}</h1>
        <div class="detail-meta">
          <span>{{ post.category_name }}</span>
          <span>{{ post.city }}{{ post.district ? ' · ' + post.district : '' }}</span>
          <span>{{ post.views }} 浏览</span>
          <span>{{ formatTime(post.created_at) }}</span>
        </div>
        <div v-if="post.price" class="detail-price">
          {{ formatPrice(post.price) }}<span class="unit">{{ post.price_unit }}</span>
        </div>

        <div v-if="post.images?.length" class="detail-images">
          <img v-for="(img, i) in post.images" :key="i" :src="img" @click="previewImage(img)" />
        </div>

        <div class="detail-content">{{ post.content }}</div>

        <div v-if="post.address" class="detail-address">
          <AppIcon name="map-pin" :size="16" />
          {{ post.province }}{{ post.city }}{{ post.district }}{{ post.address }}
        </div>
      </div>

      <div class="contact-card card">
        <div class="contact-info">
          <div class="contact-name">{{ post.contact_name || post.username || '联系人' }}</div>
          <div class="contact-phone">{{ showPhone ? post.contact_phone : maskPhone(post.contact_phone) }}</div>
        </div>
        <button class="btn btn-primary contact-btn" @click="showPhone = true">
          <AppIcon name="phone" :size="18" />
          {{ showPhone ? post.contact_phone : '查看电话' }}
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/api'
import AppIcon from '@/components/AppIcon.vue'

const route = useRoute()
const post = ref(null)
const loading = ref(true)
const showPhone = ref(false)

function formatPrice(price) {
  if (price >= 10000) return (price / 10000).toFixed(price % 10000 === 0 ? 0 : 1) + '万'
  return price
}

function formatTime(dateStr) {
  return new Date(dateStr).toLocaleString('zh-CN')
}

function maskPhone(phone) {
  if (!phone || phone.length < 7) return phone
  return phone.slice(0, 3) + '****' + phone.slice(-4)
}

function previewImage(src) {
  window.open(src, '_blank')
}

onMounted(async () => {
  try {
    post.value = await api.getPost(route.params.id)
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.detail-card { padding: 20px; margin-bottom: 12px; }
.detail-title { font-size: 20px; line-height: 1.4; margin-bottom: 10px; }
.detail-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 12px;
}
.detail-price {
  font-size: 28px;
  font-weight: 800;
  color: var(--black);
  margin-bottom: 16px;
}
.detail-price .unit { font-size: 14px; font-weight: 400; }

.detail-images {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 8px;
  margin-bottom: 16px;
}
.detail-images img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  border-radius: 6px;
  cursor: pointer;
}

.detail-content {
  font-size: 15px;
  line-height: 1.8;
  color: var(--text);
  white-space: pre-wrap;
  margin-bottom: 16px;
}
.detail-address {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  font-size: 14px;
  color: var(--text-secondary);
  padding-top: 12px;
  border-top: 1px solid var(--border);
}
.contact-btn {
  display: flex;
  align-items: center;
  gap: 6px;
}

.contact-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  position: sticky;
  bottom: 0;
}
@media (max-width: 768px) {
  .contact-card {
    position: fixed;
    bottom: var(--tabbar-h);
    left: 0;
    right: 0;
    border-radius: 0;
    z-index: 50;
  }
}
.contact-name { font-size: 15px; font-weight: 500; }
.contact-phone { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
</style>
