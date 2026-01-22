import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

const proxyTarget = process.env.VITE_PROXY_TARGET || 'http://localhost:8000'
const devHost = process.env.VITE_DEV_HOST || '127.0.0.1'
const devPort = Number(process.env.VITE_DEV_PORT || 5173)

// Proxy /api to Symfony backend
export default defineConfig({
  plugins: [react()],
  server: {
    host: devHost,
    port: devPort,
    strictPort: true,
    hmr: {
      protocol: 'ws',
      host: devHost,
      clientPort: devPort,
    },
    proxy: {
      '/api': {
        target: proxyTarget,
        changeOrigin: true,
        secure: false,
      }
    }
  },
  build: {
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true, // Remove all console.* in production
        drop_debugger: true
      }
    }
  }
})
