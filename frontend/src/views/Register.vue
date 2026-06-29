<template>
  <div class="auth-page">
    <div class="auth-wrap container">
      <div class="auth-card card">
        <div class="auth-header">
          <span class="auth-logo">
            <AppIcon name="user" :size="28" />
          </span>
          <h2>创建账号</h2>
          <p class="auth-sub">填写信息即可免费发布本地生活信息</p>
        </div>

        <form class="auth-form" @submit.prevent="submit">
          <div class="form-group">
            <label class="form-label">昵称 <span class="required">*</span></label>
            <div class="input-wrap">
              <AppIcon name="user" :size="18" class="input-icon" />
              <input
                v-model="form.username"
                class="form-input"
                placeholder="您的称呼"
                maxlength="50"
                autocomplete="nickname"
              />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">邮箱 <span class="required">*</span></label>
            <div class="input-wrap" :class="{ error: emailError }">
              <AppIcon name="mail" :size="18" class="input-icon" />
              <input
                v-model="form.email"
                class="form-input"
                type="email"
                placeholder="example@email.com"
                autocomplete="email"
                @blur="validateEmail"
              />
            </div>
            <p v-if="emailError" class="field-error">{{ emailError }}</p>
          </div>

          <div class="form-group">
            <label class="form-label">手机号 <span class="required">*</span></label>
            <div class="input-wrap" :class="{ error: phoneError }">
              <AppIcon name="phone" :size="18" class="input-icon" />
              <PhoneInput
                v-model="form.phone"
                placeholder="0412-0000000"
              />
            </div>
            <p v-if="phoneError" class="field-error">{{ phoneError }}</p>
            <p v-else class="field-hint">{{ phoneHint }}</p>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">密码 <span class="required">*</span></label>
              <div class="input-wrap">
                <AppIcon name="lock" :size="18" class="input-icon" />
                <input
                  v-model="form.password"
                  class="form-input"
                  :type="showPwd ? 'text' : 'password'"
                  placeholder="至少6位"
                  autocomplete="new-password"
                />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">确认密码 <span class="required">*</span></label>
              <div class="input-wrap">
                <AppIcon name="lock" :size="18" class="input-icon" />
                <input
                  v-model="form.confirm"
                  class="form-input"
                  :type="showPwd ? 'text' : 'password'"
                  placeholder="再次输入"
                  autocomplete="new-password"
                />
              </div>
            </div>
          </div>

          <label class="show-pwd">
            <input v-model="showPwd" type="checkbox" />
            显示密码
          </label>

          <button type="submit" class="btn btn-primary btn-block submit-btn" :disabled="loading">
            {{ loading ? '注册中...' : '立即注册' }}
          </button>
        </form>

        <p class="auth-footer">
          已有账号？<router-link to="/login">立即登录</router-link>
        </p>
      </div>

      <div class="auth-side hide-mobile">
        <div class="side-badge">
          <AppIcon name="home" :size="32" />
        </div>
        <h3>加入{{ siteStore.siteName }}</h3>
        <ul class="side-list">
          <li><AppIcon name="plus" :size="16" /> 免费发布招聘、租房等信息</li>
          <li><AppIcon name="search" :size="16" /> 快速搜索本地服务</li>
          <li><AppIcon name="phone" :size="16" /> 一键联系发布者</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useSiteStore } from '@/stores/site'
import { api } from '@/api'
import AppIcon from '@/components/AppIcon.vue'
import PhoneInput from '@/components/PhoneInput.vue'
import { validatePhoneMessage, normalizeVenezuelaPhone, PHONE_HINT } from '@/utils/phone'

const router = useRouter()
const userStore = useUserStore()
const siteStore = useSiteStore()
const showToast = inject('showToast')

const loading = ref(false)
const showPwd = ref(false)
const emailError = ref('')
const phoneError = ref('')
const phoneHint = PHONE_HINT
const form = ref({
  username: '',
  email: '',
  phone: '',
  password: '',
  confirm: '',
})

function validateEmail() {
  const email = form.value.email.trim()
  if (!email) {
    emailError.value = ''
    return false
  }
  const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
  emailError.value = ok ? '' : '请输入有效的邮箱地址'
  return ok
}

function validatePhoneField() {
  const msg = validatePhoneMessage(form.value.phone, '手机号')
  phoneError.value = msg || ''
  return !msg
}

async function submit() {
  const f = form.value
  if (!f.username || !f.email || !f.phone || !f.password || !f.confirm) {
    showToast('请填写完整信息')
    return
  }
  if (!validateEmail()) {
    showToast('邮箱格式不正确')
    return
  }
  if (!validatePhoneField()) {
    showToast(phoneError.value || '手机号格式不正确')
    return
  }
  if (f.password.length < 6) {
    showToast('密码至少6位')
    return
  }
  if (f.password !== f.confirm) {
    showToast('两次密码不一致')
    return
  }

  loading.value = true
  try {
    const data = await api.register({
      username: f.username.trim(),
      email: f.email.trim().toLowerCase(),
      phone: normalizeVenezuelaPhone(f.phone),
      password: f.password,
    })
    userStore.setAuth(data)
    showToast('注册成功')
    router.push('/')
  } catch (err) {
    showToast(err.message)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-page {
  padding: 32px 16px 48px;
  min-height: calc(100vh - var(--header-h) - 80px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.auth-wrap {
  display: flex;
  align-items: stretch;
  gap: 32px;
  width: 100%;
  max-width: 880px;
}

.auth-card {
  flex: 1;
  padding: 36px 32px;
  max-width: 480px;
}

.auth-header {
  text-align: center;
  margin-bottom: 28px;
}

.auth-logo {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: var(--black);
  color: var(--primary);
  margin-bottom: 14px;
}

.auth-card h2 {
  font-size: 24px;
  font-weight: 800;
  color: var(--black);
}

.auth-sub {
  color: var(--text-muted);
  font-size: 14px;
  margin-top: 8px;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrap.error .form-input {
  border-color: #e74c3c;
}

.input-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  pointer-events: none;
}

.input-wrap .form-input,
.input-wrap :deep(input.form-input) {
  padding-left: 40px;
}

.field-error {
  font-size: 12px;
  color: #e74c3c;
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

.show-pwd {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-muted);
  margin: 8px 0 16px;
  cursor: pointer;
}

.show-pwd input {
  width: 16px;
  height: 16px;
  accent-color: var(--black);
}

.submit-btn {
  height: 46px;
  font-size: 16px;
  border-radius: 23px;
  background: var(--black);
  color: var(--primary);
}

.submit-btn:hover:not(:disabled) {
  background: #222;
}

.submit-btn:disabled {
  opacity: 0.6;
}

.auth-footer {
  text-align: center;
  margin-top: 24px;
  font-size: 14px;
  color: var(--text-muted);
}

.auth-footer a {
  color: var(--black);
  font-weight: 700;
}

/* 右侧说明（电脑端） */
.auth-side {
  flex: 1;
  max-width: 360px;
  padding: 40px 32px;
  background: var(--primary);
  border-radius: var(--radius);
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.side-badge {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  background: var(--black);
  color: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.auth-side h3 {
  font-size: 22px;
  font-weight: 800;
  color: var(--black);
  margin-bottom: 20px;
}

.side-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.side-list li {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: var(--text-secondary);
  font-weight: 500;
}
</style>
