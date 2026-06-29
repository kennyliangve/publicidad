const SITE_ORIGIN = import.meta.env.VITE_SITE_ORIGIN || ''

/** 将相对路径（如 /publicidad/uploads/xxx.jpg）转为完整 URL */
export function resolveAssetUrl(url) {
  if (!url) return ''
  if (/^https?:\/\//i.test(url)) return url
  if (SITE_ORIGIN && url.startsWith('/')) return `${SITE_ORIGIN.replace(/\/$/, '')}${url}`
  return url
}
