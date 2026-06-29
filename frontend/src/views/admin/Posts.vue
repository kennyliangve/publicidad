<template>
  <div class="admin-page">
    <div class="admin-toolbar">
      <select v-model="filter.status" class="form-select" @change="load">
        <option value="">全部状态</option>
        <option value="1">正常</option>
        <option value="2">待审核</option>
        <option value="0">已下架</option>
      </select>
      <input v-model="filter.keyword" class="form-input" placeholder="搜索标题..." @keyup.enter="load" />
      <button class="btn btn-primary btn-sm" @click="load">搜索</button>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>标题</th>
            <th>分类</th>
            <th>发布者</th>
            <th>浏览</th>
            <th>状态</th>
            <th>发布时间</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in list" :key="p.id">
            <td>{{ p.id }}</td>
            <td class="title-cell">{{ p.title }}</td>
            <td>{{ p.category_name }}</td>
            <td>{{ p.username }}</td>
            <td>{{ p.views }}</td>
            <td><span :class="statusClass(p.status)">{{ statusLabel(p.status) }}</span></td>
            <td>{{ formatTime(p.created_at) }}</td>
            <td>
              <div class="admin-actions">
                <button v-if="p.status !== 1" class="btn-xs primary" @click="setStatus(p, 1)">通过</button>
                <button v-if="p.status === 1" class="btn-xs ghost" @click="setStatus(p, 0)">下架</button>
                <button v-if="p.status !== 2" class="btn-xs ghost" @click="setStatus(p, 2)">待审</button>
                <button class="btn-xs danger" @click="remove(p)">删除</button>
              </div>
            </td>
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
import { adminApi } from '@/api/admin'

const showToast = inject('showToast')
const list = ref([])
const page = ref(1)
const total = ref(0)
const limit = 20
const filter = ref({ status: '', keyword: '' })

const totalPages = computed(() => Math.ceil(total.value / limit))

function statusLabel(s) {
  return { 0: '已下架', 1: '正常', 2: '待审核' }[s] || '-'
}
function statusClass(s) {
  return { 0: 'status-tag off', 1: 'status-tag ok', 2: 'status-tag pending' }[s] || 'status-tag'
}
function formatTime(t) {
  return t ? new Date(t).toLocaleString('zh-CN') : '-'
}

async function load() {
  const data = await adminApi.getPosts({
    page: page.value,
    limit,
    ...(filter.value.status !== '' ? { status: filter.value.status } : {}),
    ...(filter.value.keyword ? { keyword: filter.value.keyword } : {}),
  })
  list.value = data.list
  total.value = data.total
}

async function setStatus(p, status) {
  try {
    await adminApi.updatePost(p.id, { status })
    showToast('状态已更新')
    load()
  } catch (e) {
    showToast(e.message)
  }
}

async function remove(p) {
  if (!confirm(`确定下架「${p.title}」？`)) return
  try {
    await adminApi.deletePost(p.id)
    showToast('已下架')
    load()
  } catch (e) {
    showToast(e.message)
  }
}

onMounted(load)
</script>

<style scoped>
.title-cell { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
