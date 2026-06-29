/** 提取帖子正文摘要（去除 HTML、合并空白） */
export function postContentExcerpt(content, fallback = '暂无详细描述') {
  const text = (content || '').replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim()
  return text || fallback
}

/** 价格展示 */
export function formatPostPrice(price) {
  if (price == null || price === '') return ''
  const n = Number(price)
  if (Number.isNaN(n)) return String(price)
  if (n >= 10000) return (n / 10000).toFixed(n % 10000 === 0 ? 0 : 1) + '万'
  return String(n)
}

/** 相对时间 */
export function formatRelativeTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const now = new Date()
  const diff = (now - d) / 1000
  if (diff < 60) return '刚刚'
  if (diff < 3600) return Math.floor(diff / 60) + '分钟前'
  if (diff < 86400) return Math.floor(diff / 3600) + '小时前'
  if (diff < 604800) return Math.floor(diff / 86400) + '天前'
  return d.toLocaleDateString('zh-CN')
}

/** 完整时间 */
export function formatFullTime(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

/** 地区拼接 */
export function formatRegion(post) {
  return [post?.province, post?.city, post?.district].filter(Boolean).join(' · ')
}
