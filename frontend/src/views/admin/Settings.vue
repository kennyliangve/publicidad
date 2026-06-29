<template>
  <div class="admin-page">
    <div v-if="loading" class="loading">加载中...</div>
    <form v-else class="settings-form card" @submit.prevent="save">
      <div v-for="(item, key) in settings" :key="key" class="form-group">
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
      </div>
      <button type="submit" class="btn btn-primary" :disabled="saving">
        {{ saving ? '保存中...' : '保存设置' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue'
import { adminApi } from '@/api/admin'

const showToast = inject('showToast')
const settings = ref({})
const form = ref({})
const loading = ref(true)
const saving = ref(false)

function isTextarea(key) {
  return key === 'site_description'
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
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    settings.value = await adminApi.updateSettings(form.value)
    showToast('设置已保存')
  } catch (e) {
    showToast(e.message)
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<style scoped>
.settings-form {
  max-width: 560px;
  padding: 24px;
}
.field-hint {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
}
</style>
