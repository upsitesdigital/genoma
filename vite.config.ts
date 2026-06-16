import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'
import { writeFileSync, rmSync, mkdirSync } from 'fs'

const DEV_SERVER_URL = 'http://localhost:5173'
const HOT_FILE = 'public/build/hot'

export default defineConfig({
  plugins: [
    react(),
    {
      name: 'upwork-hot-file',
      configureServer(server) {
        server.httpServer?.once('listening', () => {
          mkdirSync('public/build', { recursive: true })
          writeFileSync(HOT_FILE, DEV_SERVER_URL)
        })
        process.on('exit', () => { try { rmSync(HOT_FILE) } catch {} })
      },
      buildStart() {
        try { rmSync(HOT_FILE) } catch {}
      },
    },
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources'),
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    cors: true,
  },
  publicDir: false,
  build: {
    outDir: 'public/build',
    manifest: true,
    rollupOptions: {
      input: {
        app: 'resources/app.tsx',
      },
    },
  },
})
