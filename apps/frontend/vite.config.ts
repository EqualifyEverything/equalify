import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { VitePWA } from 'vite-plugin-pwa'
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    define: { 'import.meta.env.VITE_BUILD_DATE': JSON.stringify(new Date().toISOString()) },
    server: { allowedHosts: ['c.ngrok.pro'] },
    plugins: [
        react(),
        tailwindcss(),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: 'auto',
            includeAssets: ['favicon.png', 'icon.png'],
            manifest: {
                name: 'Equalify',
                short_name: 'Equalify',
                description: 'Equalify',
                theme_color: '#000000',
                icons: [{ src: 'icon.png', sizes: '512x512', type: 'image/png' }],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
                cleanupOutdatedCaches: true,
                clientsClaim: true,
                skipWaiting: true,
                navigateFallback: 'index.html',
                maximumFileSizeToCacheInBytes: 5000000,
            },
        }),
    ],
})