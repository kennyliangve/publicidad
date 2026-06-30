import { readFileSync, existsSync } from 'fs'
import { resolve, dirname } from 'path'
import { fileURLToPath } from 'url'

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..')
const distDir = resolve(root, 'dist')
const html = readFileSync(resolve(distDir, 'index.html'), 'utf8')

if (existsSync(resolve(distDir, '_redirects'))) {
  console.error('❌ Cloudflare Workers 构建错误：dist/_redirects 会导致部署失败')
  console.error('   Workers 已配置 not_found_handling=single-page-application，请勿包含 _redirects')
  process.exit(1)
}

if (html.includes('/publicidad/assets/')) {
  console.error('❌ Cloudflare 构建错误：index.html 仍引用 /publicidad/assets/')
  console.error('   请使用 npm run build:cloudflare 或根目录 npm run build')
  process.exit(1)
}

if (!html.includes('/assets/')) {
  console.error('❌ Cloudflare 构建错误：index.html 未找到 /assets/ 引用')
  process.exit(1)
}

console.log('✓ Cloudflare 构建验证通过')
