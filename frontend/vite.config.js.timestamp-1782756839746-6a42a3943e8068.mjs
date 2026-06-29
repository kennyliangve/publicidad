// vite.config.js
import { defineConfig, loadEnv } from "file:///G:/phpstudy_pro/WWW/publicidad/frontend/node_modules/vite/dist/node/index.js";
import vue from "file:///G:/phpstudy_pro/WWW/publicidad/frontend/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import { resolve } from "path";
var __vite_injected_original_dirname = "G:\\phpstudy_pro\\WWW\\publicidad\\frontend";
var vite_config_default = defineConfig(({ command, mode }) => {
  const env = loadEnv(mode, process.cwd(), "");
  const proxyTarget = env.VITE_API_PROXY_TARGET || "https://www.vecino.com.ve";
  const basePath = env.VITE_BASE_PATH ?? (mode === "cloudflare" ? "/" : "/publicidad/");
  return {
    plugins: [vue()],
    base: basePath,
    resolve: {
      alias: {
        "@": resolve(__vite_injected_original_dirname, "src")
      }
    },
    server: {
      port: 5173,
      open: true,
      proxy: {
        "/api": {
          target: proxyTarget,
          changeOrigin: true,
          secure: true,
          rewrite: (path) => "/publicidad/api/index.php" + path.replace(/^\/api/, "")
        },
        "/publicidad/media": {
          target: proxyTarget,
          changeOrigin: true,
          secure: true,
          // 浏览器请求 /media/（避免广告拦截）；代理层转发到线上 /uploads/ 物理文件
          rewrite: (path) => path.replace(/^\/publicidad\/media\//, "/publicidad/uploads/")
        }
      }
    },
    build: {
      outDir: "../dist",
      emptyOutDir: true
    }
  };
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJHOlxcXFxwaHBzdHVkeV9wcm9cXFxcV1dXXFxcXHB1YmxpY2lkYWRcXFxcZnJvbnRlbmRcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIkc6XFxcXHBocHN0dWR5X3Byb1xcXFxXV1dcXFxccHVibGljaWRhZFxcXFxmcm9udGVuZFxcXFx2aXRlLmNvbmZpZy5qc1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vRzovcGhwc3R1ZHlfcHJvL1dXVy9wdWJsaWNpZGFkL2Zyb250ZW5kL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnLCBsb2FkRW52IH0gZnJvbSAndml0ZSdcbmltcG9ydCB2dWUgZnJvbSAnQHZpdGVqcy9wbHVnaW4tdnVlJ1xuaW1wb3J0IHsgcmVzb2x2ZSB9IGZyb20gJ3BhdGgnXG5cbmV4cG9ydCBkZWZhdWx0IGRlZmluZUNvbmZpZygoeyBjb21tYW5kLCBtb2RlIH0pID0+IHtcbiAgY29uc3QgZW52ID0gbG9hZEVudihtb2RlLCBwcm9jZXNzLmN3ZCgpLCAnJylcbiAgY29uc3QgcHJveHlUYXJnZXQgPSBlbnYuVklURV9BUElfUFJPWFlfVEFSR0VUIHx8ICdodHRwczovL3d3dy52ZWNpbm8uY29tLnZlJ1xuICBjb25zdCBiYXNlUGF0aCA9IGVudi5WSVRFX0JBU0VfUEFUSCA/PyAobW9kZSA9PT0gJ2Nsb3VkZmxhcmUnID8gJy8nIDogJy9wdWJsaWNpZGFkLycpXG5cbiAgcmV0dXJuIHtcbiAgICBwbHVnaW5zOiBbdnVlKCldLFxuICAgIGJhc2U6IGJhc2VQYXRoLFxuICAgIHJlc29sdmU6IHtcbiAgICAgIGFsaWFzOiB7XG4gICAgICAgICdAJzogcmVzb2x2ZShfX2Rpcm5hbWUsICdzcmMnKSxcbiAgICAgIH0sXG4gICAgfSxcbiAgICBzZXJ2ZXI6IHtcbiAgICAgIHBvcnQ6IDUxNzMsXG4gICAgICBvcGVuOiB0cnVlLFxuICAgICAgcHJveHk6IHtcbiAgICAgICAgJy9hcGknOiB7XG4gICAgICAgICAgdGFyZ2V0OiBwcm94eVRhcmdldCxcbiAgICAgICAgICBjaGFuZ2VPcmlnaW46IHRydWUsXG4gICAgICAgICAgc2VjdXJlOiB0cnVlLFxuICAgICAgICAgIHJld3JpdGU6IChwYXRoKSA9PlxuICAgICAgICAgICAgJy9wdWJsaWNpZGFkL2FwaS9pbmRleC5waHAnICsgcGF0aC5yZXBsYWNlKC9eXFwvYXBpLywgJycpLFxuICAgICAgICB9LFxuICAgICAgICAnL3B1YmxpY2lkYWQvbWVkaWEnOiB7XG4gICAgICAgICAgdGFyZ2V0OiBwcm94eVRhcmdldCxcbiAgICAgICAgICBjaGFuZ2VPcmlnaW46IHRydWUsXG4gICAgICAgICAgc2VjdXJlOiB0cnVlLFxuICAgICAgICAgIC8vIFx1NkQ0Rlx1ODlDOFx1NTY2OFx1OEJGN1x1NkM0MiAvbWVkaWEvXHVGRjA4XHU5MDdGXHU1MTREXHU1RTdGXHU1NDRBXHU2MkU2XHU2MjJBXHVGRjA5XHVGRjFCXHU0RUUzXHU3NDA2XHU1QzQyXHU4RjZDXHU1M0QxXHU1MjMwXHU3RUJGXHU0RTBBIC91cGxvYWRzLyBcdTcyNjlcdTc0MDZcdTY1ODdcdTRFRjZcbiAgICAgICAgICByZXdyaXRlOiAocGF0aCkgPT4gcGF0aC5yZXBsYWNlKC9eXFwvcHVibGljaWRhZFxcL21lZGlhXFwvLywgJy9wdWJsaWNpZGFkL3VwbG9hZHMvJyksXG4gICAgICAgIH0sXG4gICAgICB9LFxuICAgIH0sXG4gICAgYnVpbGQ6IHtcbiAgICAgIG91dERpcjogJy4uL2Rpc3QnLFxuICAgICAgZW1wdHlPdXREaXI6IHRydWUsXG4gICAgfSxcbiAgfVxufSlcbiJdLAogICJtYXBwaW5ncyI6ICI7QUFBaVQsU0FBUyxjQUFjLGVBQWU7QUFDdlYsT0FBTyxTQUFTO0FBQ2hCLFNBQVMsZUFBZTtBQUZ4QixJQUFNLG1DQUFtQztBQUl6QyxJQUFPLHNCQUFRLGFBQWEsQ0FBQyxFQUFFLFNBQVMsS0FBSyxNQUFNO0FBQ2pELFFBQU0sTUFBTSxRQUFRLE1BQU0sUUFBUSxJQUFJLEdBQUcsRUFBRTtBQUMzQyxRQUFNLGNBQWMsSUFBSSx5QkFBeUI7QUFDakQsUUFBTSxXQUFXLElBQUksbUJBQW1CLFNBQVMsZUFBZSxNQUFNO0FBRXRFLFNBQU87QUFBQSxJQUNMLFNBQVMsQ0FBQyxJQUFJLENBQUM7QUFBQSxJQUNmLE1BQU07QUFBQSxJQUNOLFNBQVM7QUFBQSxNQUNQLE9BQU87QUFBQSxRQUNMLEtBQUssUUFBUSxrQ0FBVyxLQUFLO0FBQUEsTUFDL0I7QUFBQSxJQUNGO0FBQUEsSUFDQSxRQUFRO0FBQUEsTUFDTixNQUFNO0FBQUEsTUFDTixNQUFNO0FBQUEsTUFDTixPQUFPO0FBQUEsUUFDTCxRQUFRO0FBQUEsVUFDTixRQUFRO0FBQUEsVUFDUixjQUFjO0FBQUEsVUFDZCxRQUFRO0FBQUEsVUFDUixTQUFTLENBQUMsU0FDUiw4QkFBOEIsS0FBSyxRQUFRLFVBQVUsRUFBRTtBQUFBLFFBQzNEO0FBQUEsUUFDQSxxQkFBcUI7QUFBQSxVQUNuQixRQUFRO0FBQUEsVUFDUixjQUFjO0FBQUEsVUFDZCxRQUFRO0FBQUE7QUFBQSxVQUVSLFNBQVMsQ0FBQyxTQUFTLEtBQUssUUFBUSwwQkFBMEIsc0JBQXNCO0FBQUEsUUFDbEY7QUFBQSxNQUNGO0FBQUEsSUFDRjtBQUFBLElBQ0EsT0FBTztBQUFBLE1BQ0wsUUFBUTtBQUFBLE1BQ1IsYUFBYTtBQUFBLElBQ2Y7QUFBQSxFQUNGO0FBQ0YsQ0FBQzsiLAogICJuYW1lcyI6IFtdCn0K
