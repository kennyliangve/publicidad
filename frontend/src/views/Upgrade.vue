<template>
  <div class="upgrade-page container">
    <div v-if="loading" class="loading card">加载中...</div>

    <div v-else-if="!plan.enabled" class="disabled-card card">
      <AppIcon name="lock" :size="40" class="state-icon" />
      <h2>VIP 升级暂未开放</h2>
      <p>在线升级功能尚未启用，请联系管理员。</p>
      <router-link to="/user" class="btn btn-outline">返回个人中心</router-link>
    </div>

    <div v-else-if="isStaffUser" class="success-card card">
      <AppIcon name="star" :size="40" class="state-icon vip" />
      <h2>员工账号无需升级</h2>
      <p>您已拥有发布图片等权限。</p>
      <router-link to="/publish" class="btn btn-primary">去发布信息</router-link>
    </div>

    <template v-else>
      <div class="upgrade-header">
        <h1>{{ isActiveVipUser ? 'VIP 续费' : '升级 VIP' }}</h1>
        <p class="subtitle">
          {{ isActiveVipUser
            ? `当前 VIP 有效期至 ${formatDateTime(userStore.user?.vip_expires_at)}，续费将从当前到期日顺延`
            : '选择套餐并完成付款验证，即可上传图片发布信息' }}
        </p>
      </div>

      <div class="upgrade-grid">
        <section class="plan-card card">
          <h2 class="section-title">选择套餐</h2>
          <div class="plan-options">
            <button
              v-for="item in plan.plans"
              :key="item.id"
              type="button"
              class="plan-option"
              :class="{ active: selectedPlanId === item.id }"
              @click="selectedPlanId = item.id"
            >
              <div class="plan-option-head">
                <span class="plan-name">{{ item.name }}</span>
                <span class="plan-duration">{{ item.duration_label }}</span>
              </div>
              <div class="plan-option-price">
                <span class="currency">{{ plan.currency || 'VES' }}</span>
                <span class="amount">{{ formatAmount(item.amount) }}</span>
              </div>
            </button>
          </div>

          <ul class="benefits">
            <li v-for="(item, i) in plan.benefits" :key="i">
              <AppIcon name="check" :size="16" />
              {{ item }}
            </li>
          </ul>

          <div class="merchant-info">
            <h3>收款信息（Pago Móvil）</h3>
            <div class="info-row">
              <span class="label">银行代码</span>
              <span>{{ plan.merchant_bank || '—' }}</span>
            </div>
            <div class="info-row">
              <span class="label">手机号</span>
              <span>{{ plan.merchant_phone || '待配置' }}</span>
            </div>
            <div class="info-row">
              <span class="label">RIF</span>
              <span>{{ plan.merchant_rif || '待配置' }}</span>
            </div>
          </div>

          <ol class="steps">
            <li>向以上账号完成 Pago Móvil 转账，金额为 <strong>Bs {{ formatAmount(selectedPlan?.amount) }}</strong></li>
            <li>保存银行参考号最后 6 位、付款日期等信息</li>
            <li>在右侧填写并提交验证</li>
          </ol>
        </section>

        <section class="verify-card card">
          <h2>提交付款验证</h2>
          <p class="form-desc">请填写与您银行短信/凭证一致的付款信息</p>

          <form class="verify-form" @submit.prevent="submit">
            <div v-if="errorMsg" class="error-banner">{{ errorMsg }}</div>

            <div class="form-group">
              <label class="form-label">参考号（最后 6 位） <span class="required">*</span></label>
              <input
                v-model="form.reference"
                class="form-input"
                inputmode="numeric"
                maxlength="20"
                placeholder="Ultimo 6 digito referencia"
                required
              />
            </div>

            <div class="form-group">
              <label class="form-label">付款手机号 <span class="required">*</span></label>
              <PhoneInput v-model="form.payer_phone" placeholder="0412-0000000" />
            </div>

            <div class="form-group">
              <label class="form-label">付款银行 <span class="required">*</span></label>
              <select v-model="form.payer_bank_code" class="form-input" required>
                <option value="" disabled>请选择银行</option>
                <option v-for="bank in banks" :key="bank.code" :value="bank.code">
                  {{ bank.code }} — {{ bank.name }}
                </option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label">付款日期 <span class="required">*</span></label>
                <input v-model="form.payment_date" class="form-input" type="date" required />
              </div>
              <div class="form-group flex-1">
                <label class="form-label">付款金额 (Bs)</label>
                <div class="amount-locked">
                  <span class="amount-value">{{ formatAmount(selectedPlan?.amount) }}</span>
                  <AppIcon name="lock" :size="16" class="lock-icon" />
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" :disabled="submitting || !selectedPlan">
              {{ submitting ? '验证中...' : (isActiveVipUser ? '提交验证并续费' : '提交验证并升级') }}
            </button>
          </form>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { api } from '@/api'
import { isStaff, isActiveVip } from '@/utils/roles'
import AppIcon from '@/components/AppIcon.vue'
import PhoneInput from '@/components/PhoneInput.vue'
import { getVenezuelaTodayIsoDate } from '@/utils/phone'

const router = useRouter()
const userStore = useUserStore()
const showToast = inject('showToast')

const loading = ref(true)
const submitting = ref(false)
const errorMsg = ref('')
const selectedPlanId = ref(null)
const plan = ref({
  enabled: false,
  currency: 'VES',
  merchant_phone: '',
  merchant_rif: '',
  merchant_bank: '',
  plans: [],
  benefits: [],
})

const form = ref({
  reference: '',
  payer_phone: '',
  payer_bank_code: '',
  payment_date: getVenezuelaTodayIsoDate(),
})

const banks = [
  { code: '0102', name: 'Banco de Venezuela' },
  { code: '0104', name: 'Venezolano de Crédito' },
  { code: '0105', name: 'Mercantil' },
  { code: '0108', name: 'Provincial' },
  { code: '0114', name: 'Bancaribe' },
  { code: '0115', name: 'Exterior' },
  { code: '0116', name: 'BOD' },
  { code: '0134', name: 'Banesco' },
  { code: '0137', name: 'Sofitasa' },
  { code: '0138', name: 'Banco Plaza' },
  { code: '0151', name: 'BFC' },
  { code: '0156', name: '100% Banco' },
  { code: '0157', name: 'DelSur' },
  { code: '0163', name: 'Banco del Tesoro' },
  { code: '0166', name: 'Agrícola de Venezuela' },
  { code: '0171', name: 'Activo' },
  { code: '0172', name: 'Bancamiga' },
  { code: '0174', name: 'Banplus' },
  { code: '0175', name: 'Bicentenario' },
  { code: '0177', name: 'Banfanb' },
  { code: '0191', name: 'BNC' },
]

const isStaffUser = computed(() => isStaff(userStore.user?.role))
const isActiveVipUser = computed(() => isActiveVip(userStore.user))
const selectedPlan = computed(() => plan.value.plans.find(p => p.id === selectedPlanId.value) || null)

function formatAmount(value) {
  return Number(value || 0).toFixed(2)
}

function formatDateTime(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString('zh-CN')
}

async function loadPlan() {
  loading.value = true
  try {
    await userStore.fetchProfile().catch(() => {})
    const data = await api.getVipPlan()
    plan.value = data
    if (data.plans?.length && !selectedPlanId.value) {
      selectedPlanId.value = data.plans[0].id
    }
    if (!form.value.payment_date) {
      form.value.payment_date = getVenezuelaTodayIsoDate()
    }
  } catch (e) {
    showToast(e.message)
  } finally {
    loading.value = false
  }
}

async function submit() {
  if (!selectedPlanId.value) {
    showToast('请选择套餐')
    return
  }
  errorMsg.value = ''
  submitting.value = true
  try {
    const result = await api.verifyVipPayment({
      ...form.value,
      plan_id: selectedPlanId.value,
    })
    await userStore.fetchProfile()
    showToast(result.message || '升级成功')
    router.push('/user')
  } catch (e) {
    errorMsg.value = e.message || '验证失败，请稍后重试'
    showToast(errorMsg.value)
  } finally {
    submitting.value = false
  }
}

onMounted(loadPlan)
</script>

<style scoped>
.upgrade-page {
  max-width: 960px;
  padding: 24px 16px 48px;
}

.upgrade-header {
  margin-bottom: 20px;
}

.upgrade-header h1 {
  font-size: 26px;
  font-weight: 800;
  margin-bottom: 6px;
}

.subtitle {
  color: var(--text-muted);
  font-size: 14px;
}

.upgrade-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  align-items: start;
}

.plan-card,
.verify-card {
  padding: 24px;
}

.section-title {
  font-size: 16px;
  margin-bottom: 12px;
}

.plan-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 16px;
}

.plan-option {
  text-align: left;
  border: 2px solid var(--border);
  border-radius: 12px;
  padding: 12px 14px;
  background: #fff;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
}

.plan-option.active {
  border-color: #d97706;
  background: #fffbeb;
}

.plan-option-head {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 6px;
}

.plan-name {
  font-weight: 700;
  font-size: 14px;
}

.plan-duration {
  font-size: 12px;
  color: var(--text-muted);
}

.plan-option-price {
  display: flex;
  align-items: baseline;
  gap: 6px;
}

.plan-option-price .currency {
  font-size: 12px;
  color: var(--text-muted);
}

.plan-option-price .amount {
  font-size: 22px;
  font-weight: 800;
  color: var(--primary);
}

.benefits {
  list-style: none;
  padding: 0;
  margin: 0 0 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.benefits li {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.merchant-info {
  background: #f8fafc;
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 14px;
  margin-bottom: 16px;
}

.merchant-info h3 {
  font-size: 14px;
  margin-bottom: 10px;
}

.info-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 13px;
  padding: 4px 0;
}

.info-row .label {
  color: var(--text-muted);
}

.steps {
  margin: 0;
  padding-left: 18px;
  font-size: 13px;
  color: var(--text-muted);
  line-height: 1.7;
}

.verify-card h2 {
  font-size: 18px;
  margin-bottom: 4px;
}

.form-desc {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 16px;
}

.amount-locked {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius, 8px);
  background: #f5f5f5;
  color: var(--text-muted);
}

.amount-value {
  font-size: 16px;
  font-weight: 700;
  color: var(--text);
}

.amount-locked .lock-icon {
  flex-shrink: 0;
  opacity: 0.6;
}

.form-row {
  display: flex;
  gap: 12px;
}

.flex-1 {
  flex: 1;
}

.error-banner {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 13px;
  margin-bottom: 14px;
}

.disabled-card,
.success-card {
  max-width: 480px;
  margin: 40px auto;
  padding: 32px 24px;
  text-align: center;
}

.state-icon {
  color: var(--text-muted);
  margin-bottom: 12px;
}

.state-icon.vip {
  color: #d97706;
}

.disabled-card h2,
.success-card h2 {
  font-size: 20px;
  margin-bottom: 8px;
}

.disabled-card p,
.success-card p {
  color: var(--text-muted);
  margin-bottom: 20px;
}

@media (max-width: 768px) {
  .upgrade-grid {
    grid-template-columns: 1fr;
  }
}
</style>
