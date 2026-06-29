<template>
  <div class="admin-page">
    <div class="admin-toolbar">
      <button class="btn btn-primary btn-sm" @click="openForm()">+ 新增分类</button>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>名称</th>
            <th>标识</th>
            <th>父级</th>
            <th>图标</th>
            <th>排序</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="cat in categories" :key="cat.id">
            <td>{{ cat.id }}</td>
            <td>{{ cat.parent_id > 0 ? '└ ' : '' }}{{ cat.name }}</td>
            <td>{{ cat.slug }}</td>
            <td>{{ cat.parent_id || '-' }}</td>
            <td>{{ cat.icon || '-' }}</td>
            <td>{{ cat.sort_order }}</td>
            <td>
              <div class="admin-actions">
                <button class="btn-xs primary" @click="openForm(cat)">编辑</button>
                <button class="btn-xs danger" @click="remove(cat)">删除</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="modal-mask" @click.self="showModal = false">
      <div class="modal-box">
        <h3>{{ editing?.id ? '编辑分类' : '新增分类' }}</h3>
        <div class="form-group">
          <label class="form-label">名称</label>
          <input v-model="form.name" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">标识 slug</label>
          <input v-model="form.slug" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">父级ID (0=一级)</label>
          <input v-model.number="form.parent_id" type="number" class="form-input" />
        </div>
        <div class="form-group">
          <label class="form-label">图标 (Lucide名)</label>
          <input v-model="form.icon" class="form-input" placeholder="briefcase" />
        </div>
        <div class="form-group">
          <label class="form-label">排序</label>
          <input v-model.number="form.sort_order" type="number" class="form-input" />
        </div>
        <div class="modal-actions">
          <button class="btn btn-outline btn-sm" @click="showModal = false">取消</button>
          <button class="btn btn-primary btn-sm" @click="save">保存</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue'
import { adminApi } from '@/api/admin'

const showToast = inject('showToast')
const categories = ref([])
const showModal = ref(false)
const editing = ref(null)
const form = ref({ name: '', slug: '', parent_id: 0, icon: '', sort_order: 0 })

async function load() {
  categories.value = await adminApi.getCategories()
}

function openForm(cat = null) {
  editing.value = cat
  form.value = cat
    ? { name: cat.name, slug: cat.slug, parent_id: +cat.parent_id, icon: cat.icon || '', sort_order: +cat.sort_order }
    : { name: '', slug: '', parent_id: 0, icon: '', sort_order: 0 }
  showModal.value = true
}

async function save() {
  try {
    if (editing.value?.id) {
      await adminApi.updateCategory(editing.value.id, form.value)
      showToast('更新成功')
    } else {
      await adminApi.createCategory(form.value)
      showToast('创建成功')
    }
    showModal.value = false
    load()
  } catch (e) {
    showToast(e.message)
  }
}

async function remove(cat) {
  if (!confirm(`确定删除分类「${cat.name}」？`)) return
  try {
    await adminApi.deleteCategory(cat.id)
    showToast('已删除')
    load()
  } catch (e) {
    showToast(e.message)
  }
}

onMounted(load)
</script>
