<template>
  <div class="admin-login-page">
    <div class="login-bg">
      <div class="bg-grid"></div>
    </div>

    <div class="login-panel">
      <div class="login-brand">
        <div class="brand-icon">
          <AppIcon name="wrench" :size="28" />
        </div>
        <h1>管理后台</h1>
        <p>同城信息分类网 · 管理员专用入口</p>
      </div>

      <form class="login-form" @submit.prevent="submit">
        <div v-if="errorMsg" class="error-banner">{{ errorMsg }}</div>
        <div v-if="needMigrate" class="migrate-hint">
          <p>数据库可能缺少 <code>role</code> 字段，任选一种方式修复：</p>
          <ol>
            <li>浏览器打开：<a :href="installUrl" target="_blank" rel="noopener">{{ installUrl }}</a></li>
            <li>或在 phpMyAdmin 执行 <code>database/upgrade_manual.sql</code>（含 <code>UPDATE users SET role=1</code>）</li>
          </ol>
        </div>

        <div class="form-group">
          <label class="form-label">管理员账号</label>
          <div class="input-wrap">
            <AppIcon :name="accountIcon" :size="18" class="input-icon" />
            <input
              v-model="form.account"
              class="form-input"
              :type="isEmail ? 'email' : 'text'"
              placeholder="手机号或邮箱"
              autocomplete="username"
            />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">密码</label>
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

        <label class="show-pwd">
          <input v-model="showPwd" type="checkbox" />
          显示密码
        </label>

        <button type="submit" class="submit-btn" :disabled="loading">
          {{ loading ? '验证中...' : '进入后台' }}
        </button>
      </form>

      <router-link to="/" class="back-link">
        <AppIcon name="chevron-right" :size="16" style="transform:rotate(180deg)" />
        返回前台网站
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
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
const errorMsg = ref('')
const needMigrate = ref(false)
const form = ref({ account: '', password: '' })

const installUrl = import.meta.env.PROD
  ? '/publicidad/api/install.php?step=install'
  : 'https://www.vecino.com.ve/publicidad/api/install.php?step=install'

const isEmail = computed(() => form.value.account.includes('@'))
const accountIcon = computed(() => (isEmail.value ? 'mail' : 'phone'))

onMounted(() => {
  if (route.query.error === 'forbidden') {
    errorMsg.value = '当前账号无管理员权限，请使用管理员账号登录'
  }
})

async function submit() {
  const account = form.value.account.trim()
  const password = form.value.password
  errorMsg.value = ''
  needMigrate.value = false

  if (!account || !password) {
    errorMsg.value = '请填写账号和密码'
    return
  }

  loading.value = true
  try {
    const data = await api.login({ account, password })
    userStore.setAuth(data)

    if (Number(data.user?.role) !== 1) {
      userStore.logout()
      errorMsg.value = '该账号不是管理员。若数据库尚未升级，请先执行迁移（见下方说明）'
      needMigrate.value = true
      return
    }

    showToast('登录成功')
    const redirect = route.query.redirect
    router.push(typeof redirect === 'string' && redirect.startsWith('/admin') ? redirect : '/admin/dashboard')
  } catch (err) {
    errorMsg.value = err.message || '登录失败'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.admin-login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  position: relative;
  background: #0a0a0a;
}

.login-bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
}

.bg-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(248, 208, 0, 0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(248, 208, 0, 0.04) 1px, transparent 1px);
  background-size: 48px 48px;
}

.login-panel {
  position: relative;
  width: 100%;
  max-width: 420px;
  background: #141414;
  border: 1px solid rgba(248, 208, 0, 0.25);
  border-radius: 16px;
  padding: 40px 36px;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.5);
}

.login-brand {
  text-align: center;
  margin-bottom: 32px;
}

.brand-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 60px;
  height: 60px;
  border-radius: 14px;
  background: var(--primary);
  color: var(--black);
  margin-bottom: 16px;
}

.login-brand h1 {
  font-size: 24px;
  font-weight: 800;
  color: var(--primary);
  margin-bottom: 8px;
}

.login-brand p {
  font-size: 13px;
  color: rgba(248, 208, 0, 0.55);
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-label {
  display: block;
  font-size: 13px;
  color: rgba(248, 208, 0, 0.7);
  margin-bottom: 8px;
  font-weight: 600;
}

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
  margin-bottom: 16px;
}

.input-icon {
  position: absolute;
  left: 14px;
  color: rgba(248, 208, 0, 0.45);
  pointer-events: none;
}

.input-wrap .form-input {
  width: 100%;
  padding: 12px 14px 12px 42px;
  background: #1e1e1e;
  border: 1px solid rgba(248, 208, 0, 0.2);
  border-radius: 8px;
  color: #f5f5f5;
  font-size: 15px;
  transition: border-color 0.15s;
}

.input-wrap .form-input::placeholder {
  color: #666;
}

.input-wrap .form-input:focus {
  border-color: var(--primary);
  outline: none;
}

.show-pwd {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: rgba(248, 208, 0, 0.55);
  margin-bottom: 24px;
  cursor: pointer;
}

.show-pwd input {
  accent-color: var(--primary);
}

.submit-btn {
  width: 100%;
  height: 48px;
  border: none;
  border-radius: 8px;
  background: var(--primary);
  color: var(--black);
  font-size: 16px;
  font-weight: 800;
  cursor: pointer;
  transition: background 0.15s, transform 0.1s;
}

.submit-btn:hover:not(:disabled) {
  background: var(--primary-dark);
}

.submit-btn:active:not(:disabled) {
  transform: scale(0.98);
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-banner {
  background: rgba(198, 40, 40, 0.15);
  border: 1px solid rgba(198, 40, 40, 0.4);
  color: #ff8a80;
  font-size: 13px;
  padding: 10px 14px;
  border-radius: 8px;
  margin-bottom: 16px;
  line-height: 1.5;
}

.migrate-hint {
  background: rgba(248, 208, 0, 0.08);
  border: 1px solid rgba(248, 208, 0, 0.25);
  border-radius: 8px;
  padding: 12px 14px;
  margin-bottom: 16px;
  font-size: 12px;
  color: rgba(248, 208, 0, 0.85);
  line-height: 1.6;
}

.migrate-hint ol {
  margin: 8px 0 0 18px;
  padding: 0;
}

.migrate-hint a {
  color: var(--primary);
  word-break: break-all;
}

.migrate-hint code {
  background: rgba(0, 0, 0, 0.3);
  padding: 1px 4px;
  border-radius: 3px;
}

.back-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-top: 28px;
  font-size: 13px;
  color: rgba(248, 208, 0, 0.55);
  transition: color 0.15s;
}

.back-link:hover {
  color: var(--primary);
}
</style>
