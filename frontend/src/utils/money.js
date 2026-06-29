/** Bs 金额展示：千位分隔 + 两位小数（委内瑞拉格式） */
export function formatBsAmount(value) {
  const n = Number(value || 0)
  if (Number.isNaN(n)) return '0,00'
  return n.toLocaleString('es-VE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

/** USD 金额展示：两位小数 */
export function formatUsdAmount(value) {
  return Number(value || 0).toFixed(2)
}

/** 复制/接口用：纯数字两位小数，无分隔符 */
export function formatPlainAmount(value) {
  return Number(value || 0).toFixed(2)
}
