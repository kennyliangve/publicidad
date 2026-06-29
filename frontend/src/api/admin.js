import { request } from './index'

export const adminApi = {
  dashboard: () => request('/admin/dashboard'),
  getCategories: () => request('/admin/categories'),
  createCategory: (data) => request('/admin/categories', { method: 'POST', body: JSON.stringify(data) }),
  updateCategory: (id, data) => request(`/admin/categories/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  deleteCategory: (id) => request(`/admin/categories/${id}`, { method: 'DELETE' }),
  getPosts: (params) => {
    const qs = new URLSearchParams(params).toString()
    return request(`/admin/posts?${qs}`)
  },
  updatePost: (id, data) => request(`/admin/posts/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  deletePost: (id) => request(`/admin/posts/${id}`, { method: 'DELETE' }),
  getUsers: (params) => {
    const qs = new URLSearchParams(params).toString()
    return request(`/admin/users?${qs}`)
  },
  updateUser: (id, data) => request(`/admin/users/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  getSettings: () => request('/admin/settings'),
  updateSettings: (data) => request('/admin/settings', { method: 'PUT', body: JSON.stringify(data) }),
  getVipPlans: () => request('/admin/vip-plans'),
  createVipPlan: (data) => request('/admin/vip-plans', { method: 'POST', body: JSON.stringify(data) }),
  updateVipPlan: (id, data) => request(`/admin/vip-plans/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  deleteVipPlan: (id) => request(`/admin/vip-plans/${id}`, { method: 'DELETE' }),
}
