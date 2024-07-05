import { defineConfig } from 'vite';
import { resolve } from 'path';
import { viteVConsole } from 'vite-plugin-vconsole';
import svgLoader from 'vite-svg-loader';
import vue from '@vitejs/plugin-vue';
import vueI18n from '@intlify/unplugin-vue-i18n/vite';
import UnoCSS from 'unocss/vite';

import packageAliases from './tools/generateAliases';

// https://vitejs.dev/config/
export default defineConfig({
	envPrefix: 'FB_APP_PARAMETER__',
	publicDir: false,
	plugins: [
		vue(),
		vueI18n({
			include: [resolve(__dirname, './assets/locales/**.json')],
		}),
		viteVConsole({
			entry: resolve('assets/main.ts'), // entry file
			localEnabled: true, // dev environment
			enabled: false, // build production
			config: {
				theme: 'dark',
			},
		}),
		svgLoader(),
		UnoCSS(),
	],
	resolve: {
		alias: {
			...packageAliases,
		},
		dedupe: ['vue', 'pinia', 'vue-router', 'vue-i18n', 'vue-meta', 'nprogress', '@vueuse/core', 'element-plus'],
	},
	css: {
		modules: {
			localsConvention: 'camelCaseOnly',
		},
	},
	optimizeDeps: {
		include: ['vue', 'pinia', 'vue-router', 'vue-i18n', 'vue-meta', 'nprogress', '@vueuse/core', 'element-plus'],
	},
	build: {
		manifest: true,
		outDir: resolve(__dirname, './../public'),
	},
	server: {
		watch: {
			usePolling: true,
		},
		hmr: {
			host: 'localhost',
		},
		proxy: {
			'/api': {
				target: process.env.FB_APP_PARAMETER__APPLICATION_TARGET || 'http://localhost',
				secure: false,
				changeOrigin: true,
				timeout: 60000,
			},
			'/ws-exchange': {
				target: process.env.FB_APP_PARAMETER__WEBSOCKETS_TARGET || 'ws://ws-server:8888',
				rewrite: (path: string): string => {
					const wsPrefix = '/ws-exchange';

					return path.replace(new RegExp(`^${wsPrefix}`, 'g'), ''); // Remove base path
				},
				secure: true,
				changeOrigin: true,
				ws: true,
				configure: (proxy) => {
					console.log('CONFIGURE');
					proxy.on('proxyReq', function (): void {
						console.log('EVENT');
					});
				},
			},
		},
		port: 3000,
	},
	preview: {
		port: 3000,
	},
});
