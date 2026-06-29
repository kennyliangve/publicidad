import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig(({ command, mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const proxyTarget = env.VITE_API_PROXY_TARGET || 'https://www.vecino.com.ve'

  return {
    plugins: [vue()],
    base: command === 'serve' ? '/' : '/publicidad/',
    resolve: {
      alias: {
        '@': resolve(__dirname, 'src'),
      },
    },
    server: {
      port: 5173,
      open: true,
      proxy: {
        '/api': {
          target: proxyTarget,
          changeOrigin: true,
          secure: true,
          rewrite: (path) =>
            '/publicidad/api/index.php' + path.replace(/^\/api/, ''),
        },
      },
    },
    build: {
      outDir: '../dist',
      emptyOutDir: true,
    },
  }
})
