// API 地址：开发可直连线上 PHP（VITE_API_BASE）；生产走 index.php
export const API_BASE = import.meta.env.VITE_API_BASE
  || (import.meta.env.DEV ? '/api' : '/publicidad/api/index.php')

async function request(url, options = {}) {
  const token = localStorage.getItem('token')
  const headers = {
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  }

  const res = await fetch(`${API_BASE}${url}`, { ...options, headers })
  let data
  try {
    data = await res.json()
  } catch {
    throw new Error(`请求失败 (${res.status})`)
  }

  if (data.code !== 0) {
    if (data.code === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
    throw new Error(data.message || `请求失败 (${res.status})`)
  }
  return data.data
}

export { request }

export const api = {
  getSiteSettings: () => request('/settings/public'),
  getCategories: () => request('/categories'),
  getPriceUnits: () => request('/settings/price-units'),
  getRegions: () => request('/settings/regions'),
  getPosts: (params) => {
    const qs = new URLSearchParams(params).toString()
    return request(`/posts?${qs}`)
  },
  getPost: (id) => request(`/posts/${id}`),
  getPostForEdit: (id) => request(`/posts/${id}/edit`),
  getMyPosts: (page = 1) => request(`/posts/my?page=${page}`),
  createPost: (data) => request('/posts', { method: 'POST', body: JSON.stringify(data) }),
  updatePost: (id, data) => request(`/posts/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
  deletePost: (id) => request(`/posts/${id}`, { method: 'DELETE' }),
  pinPost: (id, isTop) => request(`/posts/${id}/pin`, { method: 'PUT', body: JSON.stringify({ is_top: isTop ? 1 : 0 }) }),
  login: (data) => request('/auth/login', { method: 'POST', body: JSON.stringify(data) }),
  register: (data) => request('/auth/register', { method: 'POST', body: JSON.stringify(data) }),
  getProfile: () => request('/auth/profile'),
  updateProfile: (data) => request('/auth/profile', { method: 'PUT', body: JSON.stringify(data) }),
  getVipPlan: () => request('/vip/plan'),
  verifyVipPayment: (data) => request('/vip/verify', { method: 'POST', body: JSON.stringify(data) }),
  upload: async (file) => {
    const token = localStorage.getItem('token')
    const form = new FormData()
    form.append('file', file)
    const res = await fetch(`${API_BASE}/upload`, {
      method: 'POST',
      headers: token ? { Authorization: `Bearer ${token}` } : {},
      body: form,
    })
    let data
    try {
      data = await res.json()
    } catch {
      throw new Error(`上传失败 (${res.status})`)
    }
    if (data.code !== 0) {
      if (data.code === 401) {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
      }
      throw new Error(data.message || `上传失败 (${res.status})`)
    }
    return data.data
  },
  uploadLogo: async (file) => {
    const token = localStorage.getItem('token')
    const form = new FormData()
    form.append('file', file)
    const res = await fetch(`${API_BASE}/upload/logo`, {
      method: 'POST',
      headers: token ? { Authorization: `Bearer ${token}` } : {},
      body: form,
    })
    let data
    try {
      data = await res.json()
    } catch {
      throw new Error(`上传失败 (${res.status})`)
    }
    if (data.code !== 0) {
      if (data.code === 401) {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
      }
      throw new Error(data.message || `上传失败 (${res.status})`)
    }
    return data.data
  },
}
