import { unlinkSync, existsSync } from 'fs'
import { resolve, dirname } from 'path'
import { fileURLToPath } from 'url'

const distDir = resolve(dirname(fileURLToPath(import.meta.url)), '../../dist')

// Workers 已用 not_found_handling=single-page-application，这些文件会导致部署失败
for (const name of ['_redirects', '_headers']) {
  const file = resolve(distDir, name)
  if (existsSync(file)) {
    unlinkSync(file)
    console.log(`removed dist/${name}`)
  }
}
