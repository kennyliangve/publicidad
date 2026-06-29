<template>
  <div class="publish-page container">
    <div class="card publish-form">
      <h2>发布信息</h2>

      <div class="form-group">
        <label class="form-label">分类 <span class="required">*</span></label>
        <select v-model="form.category_id" class="form-select">
          <option value="">请选择分类</option>
          <optgroup v-for="cat in categories" :key="cat.id" :label="cat.name">
            <option :value="cat.id">{{ cat.name }}</option>
            <option v-for="sub in cat.children" :key="sub.id" :value="sub.id">
              └ {{ sub.name }}
            </option>
          </optgroup>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">标题 <span class="required">*</span></label>
        <input v-model="form.title" class="form-input" placeholder="请输入标题，如：急招前端开发" maxlength="200" />
      </div>

      <div class="form-group">
        <label class="form-label">详细描述 <span class="required">*</span></label>
        <textarea v-model="form.content" class="form-textarea" placeholder="请详细描述您的信息..." rows="6"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">价格</label>
          <input v-model="form.price" type="number" class="form-input" placeholder="选填" />
        </div>
        <div class="form-group">
          <label class="form-label">单位</label>
          <select v-model="form.price_unit" class="form-select">
            <option v-for="unit in priceUnits" :key="unit" :value="unit">{{ unit }}</option>
          </select>
        </div>
      </div>

      <div class="location-section">
        <CitySelect
          v-model:province="form.province"
          v-model:city="form.city"
          v-model:district="form.district"
          :regions="regions"
          district-placeholder="如：社区、Barrio（选填）"
        />
      </div>

      <div class="form-group">
        <label class="form-label">详细地址</label>
        <input v-model="form.address" class="form-input" placeholder="选填" />
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">联系人</label>
          <input v-model="form.contact_name" class="form-input" placeholder="您的称呼" />
        </div>
        <div class="form-group">
          <label class="form-label">联系电话 <span class="required">*</span></label>
          <PhoneInput v-model="form.contact_phone" placeholder="0412-0000000" />
          <p class="field-hint">{{ phoneHint }}</p>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">
          图片（最多6张）
          <span v-if="canUploadImages" class="label-tag vip">VIP 及以上</span>
        </label>

        <div v-if="canUploadImages" class="image-upload">
          <div v-for="(img, i) in form.images" :key="i" class="img-preview">
            <img :src="resolveAssetUrl(img)" alt="" />
            <button type="button" class="img-remove" @click="removeImage(i)">×</button>
          </div>
          <label v-if="form.images.length < 6" class="img-add">
            <input type="file" accept="image/*" hidden @change="uploadImage" />
            <span>+</span>
          </label>
        </div>

        <div v-else class="image-upload-locked">
          <AppIcon name="lock" :size="22" class="lock-icon" />
          <p class="lock-title">图片上传为 VIP 及以上专属功能</p>
          <p class="lock-desc">成为 VIP 用户或更高级别账号后，即可在发布信息时上传图片，让信息更直观、更容易被关注。</p>
          <p v-if="userStore.user?.role_label" class="lock-role">
            当前身份：{{ userStore.user.role_label }}
          </p>
          <router-link to="/upgrade" class="btn btn-primary btn-sm upgrade-link">
            立即升级 VIP
          </router-link>
        </div>
      </div>

      <button class="btn btn-primary btn-block" :disabled="submitting" @click="submit">
        {{ submitting ? '发布中...' : '立即发布' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useSiteStore } from '@/stores/site'
import { api } from '@/api'
import { resolveAssetUrl, normalizeUploadUrl } from '@/utils/asset'
import CitySelect from '@/components/CitySelect.vue'
import PhoneInput from '@/components/PhoneInput.vue'
import AppIcon from '@/components/AppIcon.vue'
import { canUploadPostImages } from '@/utils/roles'
import { validatePhoneMessage, normalizeVenezuelaPhone, PHONE_HINT } from '@/utils/phone'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const siteStore = useSiteStore()
const showToast = inject('showToast')

const categories = ref([])
const priceUnits = ref(['元'])
const regions = ref([])
const phoneHint = PHONE_HINT
const submitting = ref(false)
const canUploadImages = computed(() => canUploadPostImages(userStore.user?.role, userStore.user))
const form = ref({
  category_id: '',
  title: '',
  content: '',
  price: '',
  price_unit: '元',
  province: '',
  city: '',
  district: '',
  address: '',
  contact_name: '',
  contact_phone: '',
  images: [],
})

onMounted(async () => {
  if (userStore.isLoggedIn) {
    await userStore.fetchProfile()
  }

  const [cats, unitsData, regionsData] = await Promise.all([
    api.getCategories(),
    api.getPriceUnits().catch(() => ({ units: ['元'] })),
    api.getRegions().catch(() => ({ regions: [] })),
  ])
  categories.value = cats
  if (unitsData?.units?.length) {
    priceUnits.value = unitsData.units
    if (!priceUnits.value.includes(form.value.price_unit)) {
      form.value.price_unit = priceUnits.value[0]
    }
  }
  if (regionsData?.regions?.length) {
    regions.value = regionsData.regions
  }

  const presetCategory = Number(route.query.category)
  if (presetCategory) {
    const valid = categories.value.some(
      c => Number(c.id) === presetCategory || c.children?.some(s => Number(s.id) === presetCategory)
    )
    if (valid) form.value.category_id = presetCategory
  }
})

async function uploadImage(e) {
  if (!canUploadImages.value) {
    showToast('仅 VIP 及以上用户可上传图片')
    e.target.value = ''
    return
  }
  const file = e.target.files[0]
  if (!file) return
  try {
    const data = await api.upload(file)
    form.value.images.push(normalizeUploadUrl(data))
  } catch (err) {
    if (err.message?.includes('登录')) {
      userStore.logout()
      showToast('登录已过期，请重新登录')
      router.push('/login?redirect=/publish')
      return
    }
    showToast(err.message)
  }
  e.target.value = ''
}

function removeImage(i) {
  form.value.images.splice(i, 1)
}

async function submit() {
  const f = form.value
  if (!f.category_id || !f.title || !f.content || !f.contact_phone) {
    showToast('请填写必填项')
    return
  }
  const phoneMsg = validatePhoneMessage(f.contact_phone, '联系电话')
  if (phoneMsg) {
    showToast(phoneMsg)
    return
  }

  submitting.value = true
  try {
    const payload = {
      ...f,
      category_id: Number(f.category_id),
      price: f.price ? Number(f.price) : null,
      contact_phone: normalizeVenezuelaPhone(f.contact_phone),
      images: canUploadImages.value ? f.images : [],
    }
    const data = await api.createPost(payload)
    showToast(siteStore.requirePostReview ? '提交成功，等待审核' : '发布成功')
    router.push(`/post/${data.id}`)
  } catch (err) {
    showToast(err.message)
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.publish-form { padding: 24px; max-width: 640px; margin: 0 auto; }
.location-section :deep(.city-select) {
  margin-top: 4px;
}

.field-hint {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
@media (max-width: 480px) {
  .form-row { grid-template-columns: 1fr; }
}

.image-upload {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.img-preview, .img-add {
  width: 80px;
  height: 80px;
  border-radius: 6px;
  overflow: hidden;
  position: relative;
}
.img-preview img { width: 100%; height: 100%; object-fit: cover; }
.img-remove {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 20px;
  height: 20px;
  background: rgba(0,0,0,.5);
  color: #fff;
  border-radius: 50%;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.img-add {
  border: 1px dashed var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: var(--text-muted);
  cursor: pointer;
}
.img-add:hover { border-color: var(--black); color: var(--black); background: var(--primary-light); }

.label-tag {
  margin-left: 8px;
  padding: 1px 8px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 600;
  vertical-align: middle;
}

.label-tag.vip {
  background: linear-gradient(135deg, #f8d000 0%, #ffb800 100%);
  color: #5c4a00;
}

.image-upload-locked {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 24px 16px;
  border: 1px dashed var(--border);
  border-radius: var(--radius);
  background: linear-gradient(160deg, #fafafa 0%, #f5f5f5 100%);
  text-align: center;
}

.lock-icon {
  color: var(--text-muted);
  opacity: 0.7;
}

.lock-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--text);
}

.lock-desc {
  font-size: 13px;
  line-height: 1.6;
  color: var(--text-muted);
  max-width: 360px;
}

.lock-role {
  font-size: 12px;
  color: #8a6d00;
  margin-top: 4px;
}

.upgrade-link {
  margin-top: 10px;
}
</style>
