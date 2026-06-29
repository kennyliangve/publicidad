<template>
  <div class="admin-page">
    <div v-if="loading" class="loading">加载中...</div>

    <template v-else>
      <section class="price-units-card card">
        <div class="section-head">
          <h3>价格单位管理</h3>
          <p>发布信息时可选择的单位，支持新增、修改、删除</p>
        </div>

        <div class="units-list">
          <div v-for="(unit, index) in priceUnits" :key="index" class="unit-row">
            <input
              v-model="priceUnits[index]"
              class="form-input"
              maxlength="20"
              placeholder="如：元/月"
            />
            <button
              type="button"
              class="btn-xs ghost"
              :disabled="priceUnits.length <= 1"
              @click="removeUnit(index)"
            >
              删除
            </button>
          </div>
        </div>

        <div class="unit-add">
          <input
            v-model="newUnit"
            class="form-input"
            maxlength="20"
            placeholder="输入新单位，如：美元/月"
            @keyup.enter="addUnit"
          />
          <button type="button" class="btn btn-primary btn-sm" @click="addUnit">添加单位</button>
        </div>
      </section>

      <form class="settings-form card" @submit.prevent="save">
        <h3 class="section-title">基础设置</h3>

        <div class="form-group logo-field">
          <label class="form-label">系统 Logo</label>
          <p class="field-hint">建议 PNG/JPG，不超过 2MB，显示在网站顶栏</p>
          <div class="logo-upload">
            <div class="logo-preview">
              <img v-if="logoPreview" :src="logoPreview" alt="Logo 预览" />
              <span v-else class="logo-placeholder">暂无 Logo</span>
            </div>
            <div class="logo-actions">
              <label class="btn btn-primary btn-sm logo-pick">
                {{ uploadingLogo ? '上传中...' : '上传 Logo' }}
                <input type="file" accept="image/*" hidden :disabled="uploadingLogo" @change="uploadLogo" />
              </label>
              <button v-if="logoPreview" type="button" class="btn btn-outline btn-sm" @click="removeLogo">移除</button>
            </div>
          </div>
        </div>

        <div v-for="(item, key) in visibleSettings" :key="key" class="form-group">
          <label class="form-label">{{ item.label || key }}</label>
          <input
            v-if="!isTextarea(key)"
            v-model="form[key]"
            class="form-input"
          />
          <textarea
            v-else
            v-model="form[key]"
            class="form-textarea"
            rows="3"
          />
          <p class="field-hint">键名: {{ key }}</p>
          <p v-if="key === 'contact_phone'" class="field-hint">留空则不显示</p>
        </div>

        <button type="submit" class="btn btn-primary" :disabled="saving">
          {{ saving ? '保存中...' : '保存设置' }}
        </button>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import { api } from '@/api'
import { useSiteStore } from '@/stores/site'
import { resolveLogoUrl, normalizeLogoUrl } from '@/utils/asset'
import { validatePhoneMessage, normalizeVenezuelaPhone } from '@/utils/phone'

const showToast = inject('showToast')
const siteStore = useSiteStore()

const settings = ref({})
const form = ref({})
const priceUnits = ref([])
const newUnit = ref('')
const logoPreview = ref('')
const uploadingLogo = ref(false)
const loading = ref(true)
const saving = ref(false)

const DEFAULT_UNITS = ['元', '元/月', '元/次', '元/天', '万元']

const HIDDEN_SETTING_KEYS = [
  'price_units',
  'regions',
  'site_logo',
  'vip_upgrade_enabled',
  'vip_plan_amount',
  'vip_plan_currency',
  'vip_merchant_phone',
  'vip_merchant_rif',
  'vip_merchant_bank_code',
  'bank_api_mode',
  'bank_api_endpoint',
  'bank_api_endpoint_sandbox',
  'bank_api_token',
  'bank_auth_type',
]

const visibleSettings = computed(() => {
  const result = { ...settings.value }
  for (const key of HIDDEN_SETTING_KEYS) {
    delete result[key]
  }
  return result
})

function isTextarea(key) {
  return key === 'site_description'
}

function loadPriceUnits(raw) {
  if (!raw) {
    priceUnits.value = [...DEFAULT_UNITS]
    return
  }
  try {
    const parsed = JSON.parse(raw)
    if (Array.isArray(parsed) && parsed.length) {
      priceUnits.value = parsed.map(u => String(u).trim()).filter(Boolean)
      return
    }
  } catch {
    // ignore
  }
  priceUnits.value = [...DEFAULT_UNITS]
}

function addUnit() {
  const value = newUnit.value.trim()
  if (!value) {
    showToast('请输入单位内容')
    return
  }
  if (priceUnits.value.includes(value)) {
    showToast('该单位已存在')
    return
  }
  priceUnits.value.push(value)
  newUnit.value = ''
}

function removeUnit(index) {
  if (priceUnits.value.length <= 1) return
  priceUnits.value.splice(index, 1)
}

async function load() {
  loading.value = true
  try {
    settings.value = await adminApi.getSettings()
    const f = {}
    for (const [k, v] of Object.entries(settings.value)) {
      f[k] = v.value
    }
    form.value = f
    logoPreview.value = f.site_logo ? resolveLogoUrl(f.site_logo) : ''
    loadPriceUnits(f.price_units)
  } finally {
    loading.value = false
  }
}

async function uploadLogo(e) {
  const file = e.target.files[0]
  if (!file) return
  if (file.size > 2 * 1024 * 1024) {
    showToast('Logo 不能超过 2MB')
    e.target.value = ''
    return
  }
  uploadingLogo.value = true
  try {
    const data = await api.uploadLogo(file)
    const path = normalizeLogoUrl(data)
    form.value.site_logo = path
    logoPreview.value = resolveLogoUrl(path)
    showToast('Logo 已上传，请点击保存设置')
  } catch (err) {
    showToast(err.message)
  } finally {
    uploadingLogo.value = false
    e.target.value = ''
  }
}

function removeLogo() {
  form.value.site_logo = ''
  logoPreview.value = ''
}

async function save() {
  const units = priceUnits.value.map(u => u.trim()).filter(Boolean)
  if (!units.length) {
    showToast('至少保留一个价格单位')
    return
  }

  if (form.value.contact_phone?.trim()) {
    const msg = validatePhoneMessage(form.value.contact_phone, '联系电话')
    if (msg && msg !== '请填写联系电话') {
      showToast(msg)
      return
    }
  }

  saving.value = true
  try {
    settings.value = await adminApi.updateSettings({
      ...form.value,
      price_units: JSON.stringify(units),
      ...(form.value.contact_phone?.trim()
        ? { contact_phone: normalizeVenezuelaPhone(form.value.contact_phone) }
        : { contact_phone: '' }),
    })
    showToast('设置已保存')
    loadPriceUnits(settings.value.price_units?.value)
    logoPreview.value = form.value.site_logo ? resolveLogoUrl(form.value.site_logo) : ''
    await siteStore.reload()
  } catch (e) {
    showToast(e.message)
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.price-units-card {
  max-width: 640px;
  padding: 24px;
  margin-bottom: 20px;
}

.section-head h3,
.section-title {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 6px;
}

.section-head p {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 16px;
}

.units-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 14px;
}

.unit-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

.unit-row .form-input {
  flex: 1;
}

.unit-add {
  display: flex;
  gap: 10px;
  align-items: center;
  padding-top: 4px;
  border-top: 1px dashed var(--border);
}

.unit-add .form-input {
  flex: 1;
}

.settings-form {
  max-width: 640px;
  padding: 24px;
}

.field-hint {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
}

.logo-field {
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px dashed var(--border);
}

.logo-upload {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-top: 8px;
}

.logo-preview {
  width: 72px;
  height: 72px;
  border-radius: 12px;
  border: 1px solid var(--border);
  background: #fafafa;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
}

.logo-preview img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.logo-placeholder {
  font-size: 11px;
  color: var(--text-muted);
  text-align: center;
  padding: 4px;
}

.logo-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.logo-pick {
  cursor: pointer;
  margin: 0;
}
</style>
