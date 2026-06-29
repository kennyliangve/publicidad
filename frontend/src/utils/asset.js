const UPLOAD_BASE = '/publicidad/uploads'
const LOGO_BASE = '/publicidad/logo'

function toPathname(url) {
  if (!url) return ''
  if (!/^https?:\/\//i.test(url)) return url
  try {
    return new URL(url).pathname
  } catch {
    return url
  }
}

/** 统一为 /publicidad/uploads/ 路径（兼容旧 /media/ 数据） */
export function normalizeAssetPath(url) {
  return toPathname(url).replace('/media/', '/uploads/')
}

/** 从路径提取文件名 */
export function extractFilename(url) {
  const path = normalizeAssetPath(toPathname(url))
  const name = path.split('/').filter(Boolean).pop()
  return name && /^[a-zA-Z0-9._-]+$/.test(name) ? name : ''
}

/**
 * 图片展示 URL
 * - 开发：/img/{file} 经 Vite 代理到线上 uploads（URL 不含 uploads/publicidad，避免广告拦截）
 * - 生产：/publicidad/uploads/{file}（与线上一致）
 */
export function resolveAssetUrl(url) {
  if (!url) return ''

  const filename = extractFilename(url)
  if (!filename) return normalizeAssetPath(toPathname(url))

  if (import.meta.env.DEV) {
    return `/img/${filename}`
  }

  return `${UPLOAD_BASE}/${filename}`
}

/** 上传后存库路径 */
export function normalizeUploadUrl(data) {
  const raw = typeof data === 'string' ? data : (data?.url || data?.full_url || '')
  const path = normalizeAssetPath(raw)
  const filename = extractFilename(path)
  if (filename) {
    return `${UPLOAD_BASE}/${filename}`
  }
  return path
}

/** Logo 路径规范化 */
export function normalizeLogoUrl(data) {
  const raw = typeof data === 'string' ? data : (data?.url || data?.full_url || '')
  const path = toPathname(raw)
  const filename = path.split('/').filter(Boolean).pop()
  if (filename && /^[a-zA-Z0-9._-]+$/.test(filename)) {
    return `${LOGO_BASE}/${filename}`
  }
  return path
}

/**
 * Logo 展示 URL（物理目录 logo/）
 * - 开发：/logo/{file} 经 Vite 代理
 * - 生产：/publicidad/logo/{file}
 */
export function resolveLogoUrl(url) {
  if (!url) return ''

  let path = toPathname(url)
  if (path.includes('/uploads/')) {
    return resolveAssetUrl(url)
  }

  const filename = path.split('/').filter(Boolean).pop()
  if (!filename || !/^[a-zA-Z0-9._-]+$/.test(filename)) {
    return path
  }

  if (import.meta.env.DEV) {
    return `/logo/${filename}`
  }

  return `${LOGO_BASE}/${filename}`
}
