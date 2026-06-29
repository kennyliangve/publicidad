/** 默认委内瑞拉地区（前端 fallback） */
export const DEFAULT_REGIONS = [
  { province: 'Distrito Capital', cities: ['Caracas', 'Baruta', 'Chacao'] },
  { province: 'Zulia', cities: ['Maracaibo', 'Cabimas', 'Ciudad Ojeda'] },
  { province: 'Carabobo', cities: ['Valencia', 'Puerto Cabello'] },
]

export function parseRegions(raw) {
  if (!raw) return null
  try {
    const parsed = JSON.parse(raw)
    if (!Array.isArray(parsed)) return null
    return parsed
      .map(r => ({
        province: String(r.province || '').trim(),
        cities: (r.cities || []).map(c => String(c).trim()).filter(Boolean),
      }))
      .filter(r => r.province && r.cities.length)
  } catch {
    return null
  }
}

export function formatLocation(post) {
  if (!post) return ''
  const parts = [post.province, post.city, post.district].filter(Boolean)
  return parts.join(' · ')
}
