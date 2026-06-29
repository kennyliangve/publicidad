<template>
  <div class="admin-page">
    <div v-if="loading" class="loading">加载中...</div>

    <template v-else>
      <form class="vip-settings-card card" @submit.prevent="save">
        <div class="section-head">
          <h3>VIP 升级与银行对接</h3>
          <p>配置 Pago Móvil 收款信息与 BDV consultaMultiple API</p>
        </div>

        <div class="form-group checkbox-row">
          <label class="checkbox-label">
            <input v-model="vipEnabled" type="checkbox" />
            开启 VIP 在线升级
          </label>
        </div>

        <div class="form-group">
          <label class="form-label">货币</label>
          <input v-model="vipForm.vip_plan_currency" class="form-input" maxlength="8" />
        </div>

        <div class="form-group">
          <label class="form-label">收款手机号 (Pago Móvil)</label>
          <input v-model="vipForm.vip_merchant_phone" class="form-input" placeholder="0412-0000000" />
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">收款 RIF</label>
            <input v-model="vipForm.vip_merchant_rif" class="form-input" placeholder="J-00000000-0" />
          </div>
          <div class="form-group">
            <label class="form-label">收款银行代码</label>
            <input v-model="vipForm.vip_merchant_bank_code" class="form-input" placeholder="0102" />
          </div>
        </div>

        <div class="section-divider">
          <h4>银行 API</h4>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">API 模式</label>
            <select v-model="vipForm.bank_api_mode" class="form-input">
              <option value="production">生产 (production)</option>
              <option value="sandbox">沙盒 (sandbox)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">认证方式</label>
            <select v-model="vipForm.bank_auth_type" class="form-input">
              <option value="x_api_key">X-API-Key</option>
              <option value="bearer">Bearer Token</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">银行 API 生产地址</label>
          <input v-model="vipForm.bank_api_endpoint" class="form-input" placeholder="https://bdvconciliacion.banvenez.com:443/api/consulta/consultaMultiple" />
        </div>

        <div class="form-group">
          <label class="form-label">银行 API 沙盒地址</label>
          <input v-model="vipForm.bank_api_endpoint_sandbox" class="form-input" placeholder="https://bdvconciliacion.banvenez.com:443/api/consulta/consultaMultiple" />
        </div>

        <div class="form-group">
          <label class="form-label">银行 API Token</label>
          <input
            v-model="bankApiToken"
            class="form-input"
            type="password"
            autocomplete="new-password"
            placeholder="留空则不修改已保存的 Token"
          />
          <p class="field-hint">
            {{ bankTokenConfigured ? '已配置 Token，输入新值可替换' : '尚未配置 Token' }}
          </p>
        </div>

        <button type="submit" class="btn btn-primary" :disabled="saving">
          {{ saving ? '保存中...' : '保存银行与收款设置' }}
        </button>
      </form>

      <section class="plans-card card">
        <div class="section-head plans-head">
          <div>
            <h3>VIP 套餐</h3>
            <p>可配置多个套餐，分别设定金额与有效天数；到期后用户自动恢复为普通用户</p>
          </div>
          <button type="button" class="btn btn-outline btn-sm" @click="openPlanForm()">添加套餐</button>
        </div>

        <div v-if="!plans.length" class="empty-plans">暂无套餐，请添加至少一个启用的套餐</div>

        <table v-else class="plans-table">
          <thead>
            <tr>
              <th>名称</th>
              <th>金额 (Bs)</th>
              <th>有效天数</th>
              <th>排序</th>
              <th>状态</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="plan in plans" :key="plan.id">
              <td>{{ plan.name }}</td>
              <td>{{ formatAmount(plan.amount) }}</td>
              <td>{{ plan.duration_days }} 天（{{ plan.duration_label }}）</td>
              <td>{{ plan.sort_order ?? 0 }}</td>
              <td>
                <span class="status-tag" :class="plan.enabled ? 'on' : 'off'">
                  {{ plan.enabled ? '启用' : '停用' }}
                </span>
              </td>
              <td class="actions">
                <button type="button" class="link-btn" @click="openPlanForm(plan)">编辑</button>
                <button type="button" class="link-btn danger" @click="removePlan(plan)">删除</button>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <div v-if="planModalOpen" class="modal-overlay" @click.self="closePlanForm">
        <form class="modal-card card" @submit.prevent="savePlan">
          <h4>{{ editingPlan ? '编辑套餐' : '添加套餐' }}</h4>
          <div class="form-group">
            <label class="form-label">套餐名称</label>
            <input v-model="planForm.name" class="form-input" maxlength="100" required />
          </div>
          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label">金额 (Bs)</label>
              <input v-model="planForm.amount" class="form-input" type="number" step="0.01" min="0.01" required />
            </div>
            <div class="form-group">
              <label class="form-label">有效天数</label>
              <input v-model="planForm.duration_days" class="form-input" type="number" min="1" max="3650" required />
            </div>
          </div>
          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label">排序（越小越靠前）</label>
              <input v-model="planForm.sort_order" class="form-input" type="number" />
            </div>
            <div class="form-group checkbox-row">
              <label class="checkbox-label">
                <input v-model="planForm.enabled" type="checkbox" />
                启用
              </label>
            </div>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn btn-outline" @click="closePlanForm">取消</button>
            <button type="submit" class="btn btn-primary" :disabled="planSaving">
              {{ planSaving ? '保存中...' : '保存套餐' }}
            </button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue'
import { adminApi } from '@/api/admin'
import { useSiteStore } from '@/stores/site'

const showToast = inject('showToast')
const siteStore = useSiteStore()

const loading = ref(true)
const saving = ref(false)
const vipEnabled = ref(true)
const bankApiToken = ref('')
const bankTokenConfigured = ref(false)
const plans = ref([])
const planModalOpen = ref(false)
const planSaving = ref(false)
const editingPlan = ref(null)

const VIP_SETTING_KEYS = [
  'vip_plan_currency',
  'vip_merchant_phone',
  'vip_merchant_rif',
  'vip_merchant_bank_code',
  'bank_api_mode',
  'bank_api_endpoint',
  'bank_api_endpoint_sandbox',
  'bank_auth_type',
]

const vipForm = ref({
  vip_plan_currency: 'VES',
  vip_merchant_phone: '',
  vip_merchant_rif: '',
  vip_merchant_bank_code: '0102',
  bank_api_mode: 'production',
  bank_api_endpoint: 'https://bdvconciliacion.banvenez.com:443/api/consulta/consultaMultiple',
  bank_api_endpoint_sandbox: 'https://bdvconciliacion.banvenez.com:443/api/consulta/consultaMultiple',
  bank_auth_type: 'x_api_key',
})

const planForm = ref({
  name: '',
  amount: '',
  duration_days: 30,
  sort_order: 0,
  enabled: true,
})

function formatAmount(value) {
  return Number(value || 0).toFixed(2)
}

async function loadPlans() {
  const data = await adminApi.getVipPlans()
  plans.value = data.list || []
}

async function load() {
  loading.value = true
  try {
    const settings = await adminApi.getSettings()
    await loadPlans()
    const values = {}
    for (const [k, v] of Object.entries(settings)) {
      values[k] = v.value
    }
    vipEnabled.value = values.vip_upgrade_enabled !== '0'
    for (const key of VIP_SETTING_KEYS) {
      if (values[key] !== undefined) {
        vipForm.value[key] = values[key]
      }
    }
    bankTokenConfigured.value = !!settings.bank_api_token?.configured
    bankApiToken.value = ''
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    const settings = await adminApi.updateSettings({
      ...vipForm.value,
      vip_upgrade_enabled: vipEnabled.value ? '1' : '0',
      ...(bankApiToken.value.trim() ? { bank_api_token: bankApiToken.value.trim() } : {}),
    })
    bankApiToken.value = ''
    bankTokenConfigured.value = !!settings.bank_api_token?.configured
    showToast('设置已保存')
    await siteStore.reload()
  } catch (e) {
    showToast(e.message)
  } finally {
    saving.value = false
  }
}

function openPlanForm(plan = null) {
  editingPlan.value = plan
  if (plan) {
    planForm.value = {
      name: plan.name,
      amount: String(plan.amount),
      duration_days: plan.duration_days,
      sort_order: plan.sort_order ?? 0,
      enabled: plan.enabled === 1 || plan.enabled === true,
    }
  } else {
    planForm.value = {
      name: '',
      amount: '',
      duration_days: 30,
      sort_order: plans.value.length,
      enabled: true,
    }
  }
  planModalOpen.value = true
}

function closePlanForm() {
  planModalOpen.value = false
  editingPlan.value = null
}

async function savePlan() {
  planSaving.value = true
  try {
    const payload = {
      name: planForm.value.name.trim(),
      amount: Number(planForm.value.amount),
      duration_days: Number(planForm.value.duration_days),
      sort_order: Number(planForm.value.sort_order) || 0,
      enabled: planForm.value.enabled ? 1 : 0,
    }
    if (editingPlan.value) {
      await adminApi.updateVipPlan(editingPlan.value.id, payload)
      showToast('套餐已更新')
    } else {
      await adminApi.createVipPlan(payload)
      showToast('套餐已创建')
    }
    closePlanForm()
    await loadPlans()
    await siteStore.reload()
  } catch (e) {
    showToast(e.message)
  } finally {
    planSaving.value = false
  }
}

async function removePlan(plan) {
  if (!confirm(`确定删除套餐「${plan.name}」吗？`)) return
  try {
    await adminApi.deleteVipPlan(plan.id)
    showToast('套餐已删除')
    await loadPlans()
    await siteStore.reload()
  } catch (e) {
    showToast(e.message)
  }
}

onMounted(load)
</script>

<style scoped>
.vip-settings-card,
.plans-card {
  max-width: 760px;
  padding: 24px;
  margin-bottom: 20px;
}

.section-head h3 {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 6px;
}

.section-head p {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 20px;
}

.plans-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.plans-head p {
  margin-bottom: 0;
}

.section-divider {
  margin: 24px 0 16px;
  padding-top: 20px;
  border-top: 1px dashed var(--border);
}

.section-divider h4 {
  font-size: 14px;
  font-weight: 700;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.checkbox-row {
  margin-bottom: 14px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  cursor: pointer;
}

.field-hint {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
}

.empty-plans {
  font-size: 14px;
  color: var(--text-muted);
  padding: 16px 0;
}

.plans-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.plans-table th,
.plans-table td {
  padding: 10px 8px;
  border-bottom: 1px solid var(--border);
  text-align: left;
}

.plans-table th {
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 600;
}

.actions {
  white-space: nowrap;
}

.link-btn {
  background: none;
  border: none;
  color: var(--black);
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  padding: 0 6px 0 0;
}

.link-btn.danger {
  color: #b91c1c;
}

.status-tag {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 999px;
}

.status-tag.on {
  background: #ecfdf5;
  color: #047857;
}

.status-tag.off {
  background: #f3f4f6;
  color: #6b7280;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 500;
  padding: 16px;
}

.modal-card {
  width: 100%;
  max-width: 440px;
  padding: 24px;
}

.modal-card h4 {
  font-size: 16px;
  margin-bottom: 16px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 8px;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
  flex-shrink: 0;
}

@media (max-width: 600px) {
  .form-row-2 {
    grid-template-columns: 1fr;
  }

  .plans-head {
    flex-direction: column;
  }
}
</style>
