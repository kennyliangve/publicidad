<template>
  <div class="admin-page">
    <div class="admin-toolbar">
      <input v-model="keyword" class="form-input" placeholder="搜索用户..." @keyup.enter="load" />
      <button class="btn btn-primary btn-sm" @click="load">搜索</button>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>昵称</th>
            <th>手机</th>
            <th>邮箱</th>
            <th>角色</th>
            <th>状态</th>
            <th>登录次数</th>
            <th>注册时间</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in list" :key="u.id">
            <td>{{ u.id }}</td>
            <td>{{ u.username }}</td>
            <td>{{ u.phone }}</td>
            <td>{{ u.email || '-' }}</td>
            <td>
              <select
                v-if="canEditUser(u)"
                :value="u.role"
                class="form-select inline-select"
                @change="updateRole(u, $event)"
              >
                <option
                  v-for="opt in roleOptionsFor(u)"
                  :key="opt.value"
                  :value="opt.value"
                >
                  {{ opt.label }}
                </option>
              </select>
              <span v-else class="role-readonly" :class="{ 'role-vip': Number(u.role) === ROLES.VIP }">{{ u.role_label }}</span>
            </td>
            <td>
              <select
                v-if="canEditUser(u)"
                :value="u.status"
                class="form-select inline-select"
                @change="updateStatus(u, $event)"
              >
                <option :value="1">正常</option>
                <option :value="2">待审核</option>
                <option :value="0">禁用</option>
              </select>
              <span v-else>{{ u.status_label }}</span>
            </td>
            <td>{{ u.login_count }}</td>
            <td>{{ formatTime(u.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="totalPages > 1" class="pagination">
      <button :disabled="page <= 1" @click="page--; load()">上一页</button>
      <button class="active">{{ page }} / {{ totalPages }}</button>
      <button :disabled="page >= totalPages" @click="page++; load()">下一页</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue'
import { useUserStore } from '@/stores/user'
import { adminApi } from '@/api/admin'
import { assignableRoles, canModifyUser, ROLES } from '@/utils/roles'

const showToast = inject('showToast')
const userStore = useUserStore()
const list = ref([])
const page = ref(1)
const total = ref(0)
const keyword = ref('')
const limit = 20

const myRole = computed(() => userStore.user?.role ?? 0)
const totalPages = computed(() => Math.ceil(total.value / limit))

function formatTime(t) {
  return t ? new Date(t).toLocaleDateString('zh-CN') : '-'
}

function canEditUser(u) {
  return canModifyUser(myRole.value, u.role)
}

function roleOptionsFor(u) {
  const opts = assignableRoles(myRole.value)
  if (opts.some(o => o.value === u.role)) return opts
  return [...opts, { value: u.role, label: u.role_label }]
}

async function load() {
  const data = await adminApi.getUsers({
    page: page.value,
    limit,
    ...(keyword.value ? { keyword: keyword.value } : {}),
  })
  list.value = data.list
  total.value = data.total
}

async function updateRole(u, e) {
  try {
    await adminApi.updateUser(u.id, { role: +e.target.value })
    showToast('角色已更新')
    load()
  } catch (err) {
    showToast(err.message)
    load()
  }
}

async function updateStatus(u, e) {
  try {
    await adminApi.updateUser(u.id, { status: +e.target.value })
    showToast('状态已更新')
    load()
  } catch (err) {
    showToast(err.message)
    load()
  }
}

onMounted(load)
</script>

<style scoped>
.inline-select {
  padding: 4px 8px;
  font-size: 13px;
  min-width: 110px;
}

.role-readonly {
  font-size: 13px;
  color: var(--text-muted);
}

.role-vip {
  color: #8a6d00;
  font-weight: 600;
}
</style>
