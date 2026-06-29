<template>
  <div class="detail-page">
    <div v-if="loading" class="container loading">加载中...</div>

    <div v-else-if="!post" class="container empty-state card">
      <AppIcon name="search-x" :size="48" class="state-icon" />
      <p>信息不存在或已下架</p>
      <button class="btn btn-primary btn-sm" @click="router.push('/')">回首页</button>
    </div>

    <template v-else>
      <div class="container detail-layout">
        <article class="detail-main">
          <!-- 图片画廊 -->
          <div v-if="post.images?.length" class="gallery card">
            <div class="gallery-main" @click="openLightbox(activeImage)">
              <img :src="resolveAssetUrl(post.images[activeImage])" :alt="post.title" />
              <span v-if="post.images.length > 1" class="gallery-counter">
                {{ activeImage + 1 }} / {{ post.images.length }}
              </span>
            </div>
            <div v-if="post.images.length > 1" class="gallery-thumbs">
              <button
                v-for="(img, i) in post.images"
                :key="i"
                type="button"
                class="thumb-btn"
                :class="{ active: activeImage === i }"
                @click="activeImage = i"
              >
                <img :src="resolveAssetUrl(img)" :alt="`${post.title} ${i + 1}`" />
              </button>
            </div>
          </div>

          <div class="detail-card card">
            <router-link
              v-if="post.category_id"
              :to="`/category/${post.category_id}`"
              class="category-badge"
            >
              <AppIcon :name="categoryIcon" :size="14" />
              {{ post.category_name }}
            </router-link>

            <h1 class="detail-title">{{ post.title }}</h1>

            <div class="detail-meta">
              <span class="meta-item">
                <AppIcon name="clock" :size="14" />
                {{ relativeTime }}
                <span class="meta-sub">{{ fullTime }}</span>
              </span>
              <span class="meta-item">
                <AppIcon name="eye" :size="14" />
                {{ post.views }} 浏览
              </span>
              <span v-if="regionText" class="meta-item">
                <AppIcon name="map-pin" :size="14" />
                {{ regionText }}
              </span>
            </div>

            <div v-if="post.price" class="detail-price">
              <span class="price-label">价格</span>
              <span class="price-value">
                {{ formatPrice(post.price) }}
                <span class="unit">{{ post.price_unit }}</span>
              </span>
            </div>

            <section class="content-section">
              <h2 class="section-title">详细描述</h2>
              <div class="detail-content">{{ post.content }}</div>
            </section>

            <section v-if="post.address || regionText" class="location-section">
              <h2 class="section-title">位置信息</h2>
              <div class="location-card">
                <AppIcon name="map-pin" :size="20" class="loc-icon" />
                <div>
                  <div v-if="regionText" class="loc-region">{{ regionText }}</div>
                  <div v-if="post.address" class="loc-address">{{ post.address }}</div>
                </div>
              </div>
            </section>

            <div class="safety-tip">
              <AppIcon name="alert-triangle" :size="16" />
              <span>请勿提前转账或支付定金，线下交易请注意人身与财产安全。</span>
            </div>
          </div>
        </article>

        <aside class="detail-sidebar">
          <div class="contact-card card">
            <div class="contact-panel">
              <div class="contact-avatar">
                <AppIcon name="user" :size="22" />
              </div>
              <div class="contact-body">
                <div class="contact-label">联系人</div>
                <div class="contact-name">{{ post.contact_name || post.username || '联系人' }}</div>
                <div class="contact-phone">{{ showPhone ? post.contact_phone : maskPhone(post.contact_phone) }}</div>
              </div>
              <div class="contact-actions">
                <button type="button" class="btn btn-outline btn-sm" @click="copyPhone">复制号码</button>
                <button type="button" class="btn btn-primary btn-sm contact-btn" @click="revealOrCall">
                  <AppIcon name="phone" :size="16" />
                  {{ showPhone ? '立即拨打' : '查看电话' }}
                </button>
              </div>
              <a
                v-if="whatsappUrl"
                :href="whatsappUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="whatsapp-link"
                @click="onWhatsAppClick"
              >
                <AppIcon name="message-circle" :size="18" />
                WhatsApp 联系
              </a>
            </div>
          </div>

          <div class="nav-actions-card card">
            <div class="nav-actions">
              <button type="button" class="nav-btn" title="复制链接" @click="copyLink">
                <AppIcon name="copy" :size="18" />
                <span>复制链接</span>
              </button>
              <button type="button" class="nav-btn" title="分享" @click="sharePost">
                <AppIcon name="share-2" :size="18" />
                <span>分享</span>
              </button>
            </div>
          </div>

          <div v-if="relatedPosts.length" class="related-card card">
            <h3 class="related-title">相关推荐</h3>
            <router-link
              v-for="item in relatedPosts"
              :key="item.id"
              :to="`/post/${item.id}`"
              class="related-item"
            >
              <div v-if="item.images?.length" class="related-thumb">
                <img :src="resolveAssetUrl(item.images[0])" :alt="item.title" />
              </div>
              <div class="related-info">
                <div class="related-name">{{ item.title }}</div>
                <div class="related-meta">
                  <span v-if="formatRegion(item)">{{ formatRegion(item) }}</span>
                  <span>{{ formatRelativeTime(item.created_at) }}</span>
                </div>
                <div v-if="item.price" class="related-price">
                  {{ formatPrice(item.price) }}<span class="unit">{{ item.price_unit }}</span>
                </div>
              </div>
            </router-link>
          </div>
        </aside>
      </div>

      <!-- 移动端底部联系栏 -->
      <div class="contact-bar card">
        <div class="bar-info">
          <div class="bar-name">{{ post.contact_name || post.username || '联系人' }}</div>
          <div class="bar-phone">{{ showPhone ? post.contact_phone : maskPhone(post.contact_phone) }}</div>
        </div>
        <div class="bar-actions">
          <a
            v-if="whatsappUrl"
            :href="whatsappUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-whatsapp btn-sm"
            @click="onWhatsAppClick"
          >
            <AppIcon name="message-circle" :size="16" />
          </a>
          <button type="button" class="btn btn-outline btn-sm" @click="copyPhone">复制</button>
          <button type="button" class="btn btn-primary btn-sm contact-btn" @click="revealOrCall">
            <AppIcon name="phone" :size="16" />
            {{ showPhone ? '拨打' : '查看电话' }}
          </button>
        </div>
      </div>
    </template>

    <!-- 图片灯箱 -->
    <Teleport to="body">
      <div v-if="lightboxOpen" class="lightbox" @click.self="closeLightbox">
        <button type="button" class="lightbox-close" @click="closeLightbox">
          <AppIcon name="x" :size="24" />
        </button>
        <button
          v-if="post?.images?.length > 1"
          type="button"
          class="lightbox-nav lightbox-prev"
          @click="lightboxPrev"
        >
          <AppIcon name="chevron-left" :size="28" />
        </button>
        <img
          v-if="post?.images?.length"
          class="lightbox-img"
          :src="resolveAssetUrl(post.images[lightboxIndex])"
          :alt="post.title"
        />
        <button
          v-if="post?.images?.length > 1"
          type="button"
          class="lightbox-nav lightbox-next"
          @click="lightboxNext"
        >
          <AppIcon name="chevron-right" :size="28" />
        </button>
        <div v-if="post?.images?.length > 1" class="lightbox-counter">
          {{ lightboxIndex + 1 }} / {{ post.images.length }}
        </div>
      </div>
    </Teleport>

    <div v-if="toast" class="toast">{{ toast }}</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/api'
import AppIcon from '@/components/AppIcon.vue'
import { resolveAssetUrl } from '@/utils/asset'
import { maskVenezuelaPhone, buildWhatsAppUrl } from '@/utils/phone'
import { getCategoryIcon } from '@/utils/categoryIcons'
import {
  formatPostPrice,
  formatRelativeTime,
  formatFullTime,
  formatRegion,
} from '@/utils/post'


const route = useRoute()
const router = useRouter()
const post = ref(null)
const relatedPosts = ref([])
const loading = ref(true)
const showPhone = ref(false)
const activeImage = ref(0)
const lightboxOpen = ref(false)
const lightboxIndex = ref(0)
const toast = ref('')
let toastTimer = null

const categoryIcon = computed(() =>
  getCategoryIcon(post.value?.category_slug, post.value?.category_name)
)
const regionText = computed(() => formatRegion(post.value))
const relativeTime = computed(() => formatRelativeTime(post.value?.created_at))
const fullTime = computed(() => formatFullTime(post.value?.created_at))
const whatsappUrl = computed(() => {
  if (!post.value?.contact_phone) return null
  const text = post.value.title
    ? `你好，我对「${post.value.title}」感兴趣，想进一步了解。`
    : '你好，我想进一步了解这条信息。'
  return buildWhatsAppUrl(post.value.contact_phone, text)
})

function formatPrice(price) {
  return formatPostPrice(price)
}

function maskPhone(phone) {
  return maskVenezuelaPhone(phone)
}

function showToast(msg) {
  toast.value = msg
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = '' }, 2200)
}

async function copyText(text, okMsg) {
  if (!text) return
  try {
    await navigator.clipboard.writeText(text)
    showToast(okMsg)
  } catch {
    showToast('复制失败，请手动复制')
  }
}

function copyLink() {
  copyText(window.location.href, '链接已复制')
}

function copyPhone() {
  if (!showPhone.value && post.value?.contact_phone) {
    showPhone.value = true
  }
  copyText(post.value?.contact_phone, '号码已复制')
}

function revealOrCall() {
  if (!showPhone.value) {
    showPhone.value = true
    return
  }
  if (post.value?.contact_phone) {
    window.location.href = `tel:${post.value.contact_phone}`
  }
}

function onWhatsAppClick() {
  showPhone.value = true
}

async function sharePost() {
  const url = window.location.href
  const title = post.value?.title || '信息详情'
  if (navigator.share) {
    try {
      await navigator.share({ title, url })
      return
    } catch (e) {
      if (e.name === 'AbortError') return
    }
  }
  copyLink()
}

function openLightbox(index) {
  lightboxIndex.value = index
  lightboxOpen.value = true
}

function closeLightbox() {
  lightboxOpen.value = false
}

function lightboxPrev() {
  const len = post.value?.images?.length || 0
  if (!len) return
  lightboxIndex.value = (lightboxIndex.value - 1 + len) % len
}

function lightboxNext() {
  const len = post.value?.images?.length || 0
  if (!len) return
  lightboxIndex.value = (lightboxIndex.value + 1) % len
}

function onKeydown(e) {
  if (!lightboxOpen.value) return
  if (e.key === 'Escape') closeLightbox()
  if (e.key === 'ArrowLeft') lightboxPrev()
  if (e.key === 'ArrowRight') lightboxNext()
}

async function loadRelated(categoryId, currentId) {
  if (!categoryId) return
  try {
    const data = await api.getPosts({ category_id: categoryId, limit: 6 })
    relatedPosts.value = (data.list || []).filter((p) => p.id !== currentId).slice(0, 5)
  } catch {
    relatedPosts.value = []
  }
}

watch(
  () => route.params.id,
  async (id) => {
    if (!id) return
    loading.value = true
    showPhone.value = false
    activeImage.value = 0
    try {
      post.value = await api.getPost(id)
      document.title = `${post.value.title} - 详情`
      await loadRelated(post.value.category_id, post.value.id)
    } catch {
      post.value = null
      relatedPosts.value = []
    } finally {
      loading.value = false
    }
  },
  { immediate: true }
)

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
  clearTimeout(toastTimer)
})
</script>

<style scoped>
.detail-page {
  padding-bottom: 24px;
}

.detail-sidebar {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.nav-actions-card {
  padding: 12px 14px;
}

.nav-actions {
  display: flex;
  gap: 10px;
}

.nav-btn {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 12px;
  border-radius: var(--radius);
  background: var(--bg);
  color: var(--text-secondary);
  font-size: 13px;
  font-weight: 500;
  transition: background 0.15s, color 0.15s;
}

.nav-btn:hover {
  background: var(--primary-light);
  color: var(--black);
}

.detail-layout {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 16px;
  align-items: start;
}

.detail-main {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
}

/* 画廊 */
.gallery { overflow: hidden; }

.gallery-main {
  position: relative;
  aspect-ratio: 16 / 10;
  background: #f0f0f0;
  cursor: zoom-in;
}

.gallery-main img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: #111;
}

.gallery-counter {
  position: absolute;
  right: 12px;
  bottom: 12px;
  padding: 4px 10px;
  border-radius: 20px;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
  font-size: 12px;
}

.gallery-thumbs {
  display: flex;
  gap: 8px;
  padding: 10px 12px;
  overflow-x: auto;
  border-top: 1px solid var(--border);
}

.thumb-btn {
  flex-shrink: 0;
  width: 64px;
  height: 48px;
  border-radius: 6px;
  overflow: hidden;
  border: 2px solid transparent;
  opacity: 0.65;
  transition: opacity 0.15s, border-color 0.15s;
}

.thumb-btn.active,
.thumb-btn:hover {
  opacity: 1;
  border-color: var(--primary);
}

.thumb-btn img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* 正文 */
.detail-card { padding: 20px; }

.category-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  margin-bottom: 12px;
  border-radius: 20px;
  background: var(--primary-light);
  color: #665500;
  font-size: 12px;
  font-weight: 600;
}

.category-badge:hover { background: rgba(248, 208, 0, 0.45); }

.detail-title {
  font-size: 22px;
  line-height: 1.4;
  margin-bottom: 12px;
}

.detail-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 16px;
  margin-bottom: 16px;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 13px;
  color: var(--text-muted);
}

.meta-sub {
  color: #aaa;
  font-size: 12px;
}

.detail-price {
  display: flex;
  align-items: baseline;
  gap: 10px;
  padding: 14px 16px;
  margin-bottom: 20px;
  border-radius: var(--radius);
  background: linear-gradient(90deg, #fffce8 0%, #fff 100%);
  border: 1px solid rgba(248, 208, 0, 0.35);
}

.price-label {
  font-size: 13px;
  color: var(--text-muted);
}

.price-value {
  font-size: 28px;
  font-weight: 800;
  color: var(--black);
}

.price-value .unit {
  font-size: 14px;
  font-weight: 500;
  margin-left: 4px;
  color: var(--text-muted);
}

.section-title {
  font-size: 15px;
  font-weight: 600;
  margin-bottom: 10px;
  padding-left: 10px;
  border-left: 3px solid var(--primary);
}

.content-section { margin-bottom: 20px; }

.detail-content {
  font-size: 15px;
  line-height: 1.85;
  color: var(--text);
  white-space: pre-wrap;
  word-break: break-word;
}

.location-section { margin-bottom: 16px; }

.location-card {
  display: flex;
  gap: 12px;
  padding: 14px;
  border-radius: var(--radius);
  background: var(--bg);
}

.loc-icon { color: var(--text-muted); flex-shrink: 0; margin-top: 2px; }
.loc-region { font-size: 14px; font-weight: 500; margin-bottom: 4px; }
.loc-address { font-size: 13px; color: var(--text-muted); line-height: 1.5; }

.safety-tip {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 12px 14px;
  border-radius: var(--radius);
  background: #fff8e6;
  font-size: 12px;
  color: #8a6d00;
  line-height: 1.5;
}

/* 联系面板 */
.contact-card { padding: 18px; }

.contact-panel {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.contact-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--primary-light);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #665500;
}

.contact-label {
  font-size: 12px;
  color: var(--text-muted);
  margin-bottom: 2px;
}

.contact-name {
  font-size: 16px;
  font-weight: 600;
}

.contact-phone {
  font-size: 15px;
  color: var(--text-secondary);
  margin-top: 4px;
  letter-spacing: 0.5px;
}

.contact-actions {
  display: flex;
  gap: 8px;
}

.contact-btn {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.whatsapp-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 10px 14px;
  border-radius: var(--radius);
  background: #25d366;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  transition: background 0.15s;
}

.whatsapp-link:hover {
  background: #1ebe57;
  color: #fff;
}

.btn-whatsapp {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #25d366;
  color: #fff;
  padding: 8px 10px;
}

.btn-whatsapp:hover {
  background: #1ebe57;
  color: #fff;
}

/* 相关推荐 */
.related-card { padding: 16px; }

.related-title {
  font-size: 15px;
  font-weight: 600;
  margin-bottom: 12px;
}

.related-item {
  display: flex;
  gap: 10px;
  padding: 10px 0;
  border-bottom: 1px solid var(--border);
  transition: background 0.15s;
}

.related-item:last-child { border-bottom: none; padding-bottom: 0; }
.related-item:hover { opacity: 0.85; }

.related-thumb {
  width: 72px;
  height: 54px;
  flex-shrink: 0;
  border-radius: 6px;
  overflow: hidden;
  background: #f5f5f5;
}

.related-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.related-info { flex: 1; min-width: 0; }

.related-name {
  font-size: 13px;
  font-weight: 600;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.related-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 4px;
  font-size: 11px;
  color: var(--text-muted);
}

.related-price {
  margin-top: 4px;
  font-size: 14px;
  font-weight: 700;
}

.related-price .unit {
  font-size: 11px;
  font-weight: 500;
  color: var(--text-muted);
}

/* 移动端底部栏 */
.contact-bar {
  display: none;
  position: fixed;
  bottom: var(--tabbar-h);
  left: 0;
  right: 0;
  z-index: 50;
  padding: 12px 16px;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-radius: 0;
}

.bar-name { font-size: 14px; font-weight: 600; }
.bar-phone { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.bar-actions { display: flex; gap: 8px; flex-shrink: 0; }

/* 灯箱 */
.lightbox {
  position: fixed;
  inset: 0;
  z-index: 200;
  background: rgba(0, 0, 0, 0.92);
  display: flex;
  align-items: center;
  justify-content: center;
}

.lightbox-img {
  max-width: min(96vw, 960px);
  max-height: 88vh;
  object-fit: contain;
}

.lightbox-close {
  position: absolute;
  top: 16px;
  right: 16px;
  color: #fff;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lightbox-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  color: #fff;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.12);
  border-radius: 50%;
}

.lightbox-prev { left: 12px; }
.lightbox-next { right: 12px; }

.lightbox-counter {
  position: absolute;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  color: #fff;
  font-size: 14px;
}

.toast {
  position: fixed;
  bottom: calc(var(--tabbar-h) + 80px);
  left: 50%;
  transform: translateX(-50%);
  z-index: 300;
  padding: 10px 20px;
  border-radius: 24px;
  background: rgba(0, 0, 0, 0.78);
  color: #fff;
  font-size: 13px;
  pointer-events: none;
}

.empty-state {
  text-align: center;
  padding: 48px 24px;
  margin-top: 12px;
}

.empty-state .state-icon { color: var(--text-muted); margin-bottom: 12px; }
.empty-state p { color: var(--text-muted); margin-bottom: 16px; }

@media (max-width: 900px) {
  .detail-layout {
    grid-template-columns: 1fr;
    padding-top: 12px;
  }

  .detail-sidebar .contact-card {
    display: none;
  }

  .contact-bar {
    display: flex;
  }

  .detail-page {
    padding-bottom: calc(var(--tabbar-h) + 72px);
  }
}

@media (min-width: 901px) {
  .detail-sidebar {
    position: sticky;
    top: calc(var(--header-h) + 12px);
  }
}
</style>
