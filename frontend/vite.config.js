import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig(({ command, mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const proxyTarget = env.VITE_API_PROXY_TARGET || 'https://www.vecino.com.ve'
  const basePath = env.VITE_BASE_PATH ?? (mode === 'cloudflare' ? '/' : '/publicidad/')

  return {
    plugins: [vue()],
    base: basePath,
    resolve: {
      alias: {
        '@': resolve(__dirname, 'src'),
      },
    },
    server: {
      port: 5173,
      open: false,
      proxy: {
        '/api': {
          target: proxyTarget,
          changeOrigin: true,
          secure: true,
          rewrite: (path) =>
            '/publicidad/api/index.php' + path.replace(/^\/api/, ''),
        },
        // 开发预览：/img/xxx.jpg → 线上 /publicidad/uploads/xxx.jpg（避免广告拦截 uploads/publicidad）
        '/img': {
          target: proxyTarget,
          changeOrigin: true,
          secure: true,
          rewrite: (path) => '/publicidad/uploads' + path.replace(/^\/img/, ''),
        },
        '/logo': {
          target: proxyTarget,
          changeOrigin: true,
          secure: true,
          rewrite: (path) => '/publicidad/logo' + path.replace(/^\/logo/, ''),
        },
      },
    },
    build: {
      outDir: '../dist',
      emptyOutDir: true,
    },
  }
})
