<template>
  <div class="admin-page">
    <div v-if="loading" class="loading">加载中...</div>
    <template v-else>
      <RegionsEditor ref="regionsEditorRef" v-model="regions" />

      <div class="page-actions">
        <button type="button" class="btn btn-primary" :disabled="saving" @click="save">
          {{ saving ? '保存中...' : '保存地区设置' }}
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import { api } from '@/api'
import { parseRegions } from '@/utils/regions'
import RegionsEditor from '@/components/admin/RegionsEditor.vue'

const showToast = inject('showToast')
const regions = ref([])
const loading = ref(true)
const saving = ref(false)
const regionsEditorRef = ref(null)

function loadRegions(raw) {
  const parsed = parseRegions(raw)
  regions.value = parsed?.length
    ? parsed.map(r => ({ province: r.province, cities: [...r.cities] }))
    : []
}

async function load() {
  loading.value = true
  try {
    const settings = await adminApi.getSettings()
    loadRegions(settings.regions?.value)
    if (!regions.value.length) {
      const data = await api.getRegions().catch(() => null)
      if (data?.regions?.length) {
        regions.value = data.regions.map(r => ({
          province: r.province,
          cities: [...r.cities],
        }))
      }
    }
  } finally {
    loading.value = false
  }
}

async function save() {
  const normalizedRegions = regionsEditorRef.value?.getNormalized?.() ?? regions.value
  if (!normalizedRegions.length) {
    showToast('至少保留一个省份及城市')
    return
  }

  saving.value = true
  try {
    await adminApi.updateSettings({
      regions: JSON.stringify(normalizedRegions),
    })
    showToast('地区设置已保存')
    const settings = await adminApi.getSettings()
    loadRegions(settings.regions?.value)
  } catch (e) {
    showToast(e.message)
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.page-actions {
  max-width: 640px;
  margin-top: 8px;
}
</style>
