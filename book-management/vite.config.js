import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    server: {
        proxy: {
            '/api': {
                target: 'http://127.0.0.1:8003',
                changeOrigin: true,
                secure: false,
                configure: (proxy, options) => {
                    proxy.on('proxyReq', (proxyReq, req, res) => {
                        // Cookie ヘッダーを転送
                        if (req.headers.cookie) {
                            proxyReq.setHeader('Cookie', req.headers.cookie);
                        }
                    });
                    
                    proxy.on('proxyRes', (proxyRes, req, res) => {
                        // Set-Cookie ヘッダーを転送
                        if (proxyRes.headers['set-cookie']) {
                            res.setHeader('Set-Cookie', proxyRes.headers['set-cookie']);
                        }
                    });
                }
            }
        }
    }
});
