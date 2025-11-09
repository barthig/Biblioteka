import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// Proxy /api to Symfony backend running at http://localhost:8000
export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      }
    }
  }
})
