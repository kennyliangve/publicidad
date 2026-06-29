<template>
  <div class="auth-page">
    <div class="auth-wrap container">
      <div class="auth-card card">
        <div class="auth-header">
          <span class="auth-logo">
            <AppIcon name="lock" :size="28" />
          </span>
          <h2>欢迎回来</h2>
          <p class="auth-sub">登录后即可发布和管理您的信息</p>
        </div>

        <form class="auth-form" @submit.prevent="submit">
          <div v-if="errorMsg" class="error-banner">{{ errorMsg }}</div>
          <div class="form-group">
            <label class="form-label">手机号 / 邮箱 <span class="required">*</span></label>
            <div class="input-wrap">
              <AppIcon :name="accountIcon" :size="18" class="input-icon" />
              <PhoneInput
                v-if="!isEmail"
                v-model="form.account"
                placeholder="0412-0000000"
              />
              <input
                v-else
                v-model="form.account"
                class="form-input"
                type="email"
                placeholder="example@email.com"
                autocomplete="username"
              />
            </div>
            <p v-if="!isEmail" class="field-hint">{{ phoneHint }}</p>
          </div>

          <div class="form-group">
            <label class="form-label">密码 <span class="required">*</span></label>
            <div class="input-wrap">
              <AppIcon name="lock" :size="18" class="input-icon" />
              <input
                v-model="form.password"
                class="form-input"
                :type="showPwd ? 'text' : 'password'"
                placeholder="请输入密码"
                autocomplete="current-password"
              />
            </div>
          </div>

          <div class="form-options">
            <label class="show-pwd">
              <input v-model="showPwd" type="checkbox" />
              显示密码
            </label>
          </div>

          <button type="submit" class="btn btn-primary btn-block submit-btn" :disabled="loading">
            {{ loading ? '登录中...' : '立即登录' }}
          </button>
        </form>

        <p v-if="siteStore.allowRegister" class="auth-footer">
          还没有账号？<router-link to="/register">立即注册</router-link>
        </p>
        <p v-else class="auth-footer auth-footer-muted">当前已关闭新用户注册</p>
      </div>

      <div class="auth-side hide-mobile">
        <div class="side-badge">
          <AppIcon name="home" :size="32" />
        </div>
        <h3>{{ siteStore.siteName }}</h3>
        <ul class="side-list">
          <li><AppIcon name="briefcase" :size="16" /> 浏览招聘、租房、二手车</li>
          <li><AppIcon name="plus" :size="16" /> 免费发布本地信息</li>
          <li><AppIcon name="user" :size="16" /> 管理我的发布记录</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useSiteStore } from '@/stores/site'
import { api } from '@/api'
import AppIcon from '@/components/AppIcon.vue'
import PhoneInput from '@/components/PhoneInput.vue'
import { normalizeLoginAccount, PHONE_HINT } from '@/utils/phone'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const siteStore = useSiteStore()
const showToast = inject('showToast')

const loading = ref(false)
const showPwd = ref(false)
const errorMsg = ref('')
const form = ref({ account: '', password: '' })

const isEmail = computed(() => form.value.account.includes('@'))
const accountIcon = computed(() => (isEmail.value ? 'mail' : 'phone'))
const phoneHint = PHONE_HINT

onMounted(() => {
  if (route.query.error === 'register_disabled') {
    errorMsg.value = '当前已关闭用户注册'
  }
})

async function submit() {
  const account = form.value.account.trim()
  const password = form.value.password
  errorMsg.value = ''

  if (!account || !password) {
    errorMsg.value = '请填写账号和密码'
    return
  }

  loading.value = true
  try {
    const data = await api.login({
      account: normalizeLoginAccount(account),
      password,
    })
    userStore.setAuth(data)
    showToast('登录成功')
    router.push(route.query.redirect || '/')
  } catch (err) {
    errorMsg.value = err.message || '登录失败'
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

.error-banner {
  background: #fff5f5;
  border: 1px solid #ffcdd2;
  color: #c62828;
  font-size: 13px;
  padding: 10px 14px;
  border-radius: 8px;
  margin-bottom: 12px;
  line-height: 1.5;
}

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
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

.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 8px 0 20px;
}

.show-pwd {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-muted);
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

.field-hint {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
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

.auth-footer-muted {
  color: var(--text-muted);
}

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
