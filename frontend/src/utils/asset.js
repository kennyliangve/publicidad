import {
  UPLOAD_STORE_PATH,
  LOGO_STORE_PATH,
  resolveUploadBase,
  resolveLogoBase,
} from '@/config/runtime'

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
 * - 开发：/img/{file} 经 Vite 代理
 * - Cloudflare：线上绝对 URL
 * - phpstudy：/publicidad/uploads/{file}
 */
export function resolveAssetUrl(url) {
  if (!url) return ''

  const filename = extractFilename(url)
  if (!filename) return normalizeAssetPath(toPathname(url))

  return `${resolveUploadBase()}/${filename}`
}

/** 上传后存库路径 */
export function normalizeUploadUrl(data) {
  const raw = typeof data === 'string' ? data : (data?.url || data?.full_url || '')
  const path = normalizeAssetPath(raw)
  const filename = extractFilename(path)
  if (filename) {
    return `${UPLOAD_STORE_PATH}/${filename}`
  }
  return path
}

/** Logo 路径规范化 */
export function normalizeLogoUrl(data) {
  const raw = typeof data === 'string' ? data : (data?.url || data?.full_url || '')
  const path = toPathname(raw)
  const filename = path.split('/').filter(Boolean).pop()
  if (filename && /^[a-zA-Z0-9._-]+$/.test(filename)) {
    return `${LOGO_STORE_PATH}/${filename}`
  }
  return path
}

/**
 * Logo 展示 URL
 * - 开发：/logo/{file} 经 Vite 代理
 * - Cloudflare：线上绝对 URL
 * - phpstudy：/publicidad/logo/{file}
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

  const base = resolveLogoBase()
  return `${base}/${filename}`
}