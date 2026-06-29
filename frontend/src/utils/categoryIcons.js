/** 分类 slug → Lucide 图标名映射 */
export const categoryIconMap = {
  jobs: 'briefcase',
  services: 'store',
  rent: 'building-2',
  house: 'home',
  car: 'car',
  goods: 'package',
  pet: 'paw-print',
  social: 'users',
  fulltime: 'briefcase',
  parttime: 'clock',
  resume: 'file-text',
  housekeeping: 'sparkles',
  repair: 'wrench',
  moving: 'truck',
  education: 'graduation-cap',
  wedding: 'heart',
  'whole-rent': 'home',
  'share-rent': 'users',
  'short-rent': 'calendar',
  sedan: 'car',
  suv: 'car-front',
  truck: 'truck',
}

/** 根据 slug 或分类名获取图标名 */
export function getCategoryIcon(slug, name = '') {
  if (slug && categoryIconMap[slug]) return categoryIconMap[slug]

  const rules = [
    ['招聘', 'briefcase'],
    ['租房', 'building-2'],
    ['二手车', 'car'],
    ['二手', 'package'],
    ['生活', 'store'],
    ['宠物', 'paw-print'],
    ['交友', 'users'],
    ['房', 'home'],
  ]
  for (const [keyword, icon] of rules) {
    if (name.includes(keyword)) return icon
  }
  return 'file-text'
}
