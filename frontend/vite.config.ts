import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import vuetify from 'vite-plugin-vuetify'
import vueJsx from '@vitejs/plugin-vue-jsx'
import legacy from '@vitejs/plugin-legacy'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue({
      template: {
        compilerOptions: {
          // Enable all features
          isCustomElement: () => false
        }
      },
      // Enable script setup support
      script: {
        defineModel: true,
        propsDestructure: true
      },
      // Additional Vue options
    }),
    vueJsx(),
    vueDevTools(),
    vuetify({
      autoImport: true,
      // styles: true,
    }),
    // Legacy plugin für CEF 103 und ältere Browser
    legacy({
      targets: ['chrome >= 103'],
      // Generiert moderne und legacy bundles
      modernPolyfills: ['es.string.replace-all'],
      renderLegacyChunks: true,
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },

  // ========= 1) Dev‑Server Tuning =========
  server: {
    // **Watcher optimieren** (Chokidar)
    watch: {
      usePolling: false,       // Native FS‑Events statt Polling
      ignored: [
        '**/node_modules/**',
        '**/vendor/**',        // PHP‑Composer‑Packages
        '**/.git/**'
      ]
    },
    // **HMR‑Feinjustierung**
    hmr: {
      overlay: false,          // Kein Error‑Overlay bei jeder Warnung
      timeout: 30000,
      protocol: 'ws'
      // port: 6463,           // evtl. custom Port, falls Konflikte
    },
    // **Proxy** zu deinem PHP‑Backend
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false
      }
    }
  },

  // ======== 2) Dependencies vorkompilieren ========
  optimizeDeps: {
    include: [
      'vue',
      'vue-router',
      'pinia',
      // ...weitere libs, die du häufig nutzt
    ]
    // exclude: ['some‑heavy‑lib‑if‑needed']
  },

  // ======== 3) Sourcemaps im Build abschalten ========
  build: {
    sourcemap: false,
    // Deaktiviere TypeScript-Typprüfungen für den Build
    rollupOptions: {
      external: ['vue-tsc']
    }
  },

  // TypeScript-Einstellungen überschreiben
  esbuild: {
    logOverride: { 'this-is-undefined-in-esm': 'silent' },
    // Typprüfungen deaktivieren
    tsconfigRaw: JSON.stringify({
      compilerOptions: {
        skipLibCheck: true,
        skipDefaultLibCheck: true,
        noImplicitAny: false,
        strict: false
      }
    })
  },
  
  // Vue-spezifische Konfiguration
  define: {
    __VUE_OPTIONS_API__: true,
    __VUE_PROD_DEVTOOLS__: false,
    __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
    // Ersetze import.meta.env für Legacy-Browser
    'import.meta.env.VITE_API_URL': JSON.stringify(process.env.VITE_API_URL || 'http://localhost:8080/backend'),
    'import.meta.env.VITE_SOCKET_URL': JSON.stringify(process.env.VITE_SOCKET_URL || 'http://localhost:3001'),
    'import.meta.env.MODE': JSON.stringify(process.env.NODE_ENV || 'development'),
    'import.meta.env.DEV': process.env.NODE_ENV !== 'production',
    'import.meta.env.PROD': process.env.NODE_ENV === 'production'
  }
})
