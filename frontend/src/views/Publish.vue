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
            <option value="元">元</option>
            <option value="元/月">元/月</option>
            <option value="元/次">元/次</option>
            <option value="元/天">元/天</option>
            <option value="万元">万元</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">城市</label>
          <input v-model="form.city" class="form-input" placeholder="如：北京" />
        </div>
        <div class="form-group">
          <label class="form-label">区域</label>
          <input v-model="form.district" class="form-input" placeholder="如：朝阳区" />
        </div>
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
          <input v-model="form.contact_phone" class="form-input" placeholder="手机号" />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">图片（最多6张）</label>
        <div class="image-upload">
          <div v-for="(img, i) in form.images" :key="i" class="img-preview">
            <img :src="resolveAssetUrl(img)" />
            <button class="img-remove" @click="removeImage(i)">×</button>
          </div>
          <label v-if="form.images.length < 6" class="img-add">
            <input type="file" accept="image/*" hidden @change="uploadImage" />
            <span>+</span>
          </label>
        </div>
      </div>

      <button class="btn btn-primary btn-block" :disabled="submitting" @click="submit">
        {{ submitting ? '发布中...' : '立即发布' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/api'
import { resolveAssetUrl } from '@/utils/asset'

const router = useRouter()
const showToast = inject('showToast')

const categories = ref([])
const submitting = ref(false)
const form = ref({
  category_id: '',
  title: '',
  content: '',
  price: '',
  price_unit: '元',
  city: '',
  district: '',
  address: '',
  contact_name: '',
  contact_phone: '',
  images: [],
})

onMounted(async () => {
  categories.value = await api.getCategories()
})

async function uploadImage(e) {
  const file = e.target.files[0]
  if (!file) return
  try {
    const data = await api.upload(file)
    form.value.images.push(data.url)
  } catch (err) {
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

  submitting.value = true
  try {
    const data = await api.createPost({
      ...f,
      category_id: Number(f.category_id),
      price: f.price ? Number(f.price) : null,
    })
    showToast('发布成功')
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
.publish-form h2 { font-size: 20px; margin-bottom: 20px; }

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
</style>
