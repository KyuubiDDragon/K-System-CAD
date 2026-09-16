import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5174,
    proxy: {
      "/api/social": {
        target: process.env.SOCIAL_BACKEND || "http://127.0.0.1:8088",
        changeOrigin: true,
      },
    },
  },
});
