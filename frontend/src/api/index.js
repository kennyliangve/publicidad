// API 地址：开发走 Vite 代理；Cloudflare Pages 走 /api 函数代理；原服务器走 index.php
const API_BASE = import.meta.env.VITE_API_BASE
  || (import.meta.env.DEV ? '/api' : '/publicidad/api/index.php')

async function request(url, options = {}) {
  const token = localStorage.getItem('token')
  const headers = {
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  }

  const res = await fetch(`${API_BASE}${url}`, { ...options, headers })
  const data = await res.json()

  if (data.code !== 0) {
    throw new Error(data.message || '请求失败')
  }
  return data.data
}

export { request }

export const api = {
  getCategories: () => request('/categories'),
  getPosts: (params) => {
    const qs = new URLSearchParams(params).toString()
    return request(`/posts?${qs}`)
  },
  getPost: (id) => request(`/posts/${id}`),
  getMyPosts: (page = 1) => request(`/posts/my?page=${page}`),
  createPost: (data) => request('/posts', { method: 'POST', body: JSON.stringify(data) }),
  deletePost: (id) => request(`/posts/${id}`, { method: 'DELETE' }),
  login: (data) => request('/auth/login', { method: 'POST', body: JSON.stringify(data) }),
  register: (data) => request('/auth/register', { method: 'POST', body: JSON.stringify(data) }),
  getProfile: () => request('/auth/profile'),
  updateProfile: (data) => request('/auth/profile', { method: 'PUT', body: JSON.stringify(data) }),
  upload: async (file) => {
    const token = localStorage.getItem('token')
    const form = new FormData()
    form.append('file', file)
    const res = await fetch(`${API_BASE}/upload`, {
      method: 'POST',
      headers: token ? { Authorization: `Bearer ${token}` } : {},
      body: form,
    })
    const data = await res.json()
    if (data.code !== 0) throw new Error(data.message)
    return data.data
  },
}
