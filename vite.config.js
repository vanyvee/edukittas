import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import Components from 'unplugin-vue-components/vite'
import AutoImport from 'unplugin-auto-import/vite'
import { VitePWA } from 'vite-plugin-pwa'
import { fileURLToPath, URL } from 'node:url'
import VueRouter from 'vue-router/vite'

export default defineConfig({
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
  },
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
      fonts: [
        // keep if you want Laravel’s bunny font integration
        // bunny('Instrument Sans', { weights: [400, 500, 600] }),
      ],
    }),
    VueRouter({
    routesFolder: 'resources/js/pages',
}),
    vue(),
    Components({
      dirs: ['resources/js/components'],
      dts: true,
    }),
    AutoImport({
      imports: ['vue', 'vue-router', 'pinia', '@vueuse/core'],
      dts: true,
    }),
    VitePWA({
      registerType: 'autoUpdate',
      manifest: {
        name: 'byti',
        short_name: 'byti',
        theme_color: '#ffffff',
        icons: [
          { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png' },
          { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png' },
        ],
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
      },
    }),
    tailwindcss(),
  ],
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'vue-router', 'axios', 'pinia'],
          gsap: ['gsap'],
          vueuse: ['@vueuse/core'],
        },
      },
    },
  },
  server: {
    host: '127.0.0.1',
    port: 5173,
    hmr: { host: '127.0.0.1' },
    watch: {
      ignored: ['**/storage/framework/views/**'],
    },
  },
})
