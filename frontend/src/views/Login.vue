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
          <div class="form-group">
            <label class="form-label">手机号 / 邮箱 <span class="required">*</span></label>
            <div class="input-wrap">
              <AppIcon :name="accountIcon" :size="18" class="input-icon" />
              <input
                v-model="form.account"
                class="form-input"
                :type="isEmail ? 'email' : 'text'"
                placeholder="请输入手机号或邮箱"
                autocomplete="username"
              />
            </div>
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

        <p class="auth-footer">
          还没有账号？<router-link to="/register">立即注册</router-link>
        </p>
      </div>

      <div class="auth-side hide-mobile">
        <div class="side-badge">
          <AppIcon name="home" :size="32" />
        </div>
        <h3>同城信息平台</h3>
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
import { ref, computed, inject } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { api } from '@/api'
import AppIcon from '@/components/AppIcon.vue'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const showToast = inject('showToast')

const loading = ref(false)
const showPwd = ref(false)
const form = ref({ account: '', password: '' })

const isEmail = computed(() => form.value.account.includes('@'))
const accountIcon = computed(() => (isEmail.value ? 'mail' : 'phone'))

async function submit() {
  const account = form.value.account.trim()
  const password = form.value.password

  if (!account || !password) {
    showToast('请填写账号和密码')
    return
  }

  loading.value = true
  try {
    const data = await api.login({ account, password })
    userStore.setAuth(data)
    showToast('登录成功')
    router.push(route.query.redirect || '/')
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

.input-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  pointer-events: none;
}

.input-wrap .form-input {
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
