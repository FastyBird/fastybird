import { defineConfig } from 'vite';
import { resolve } from 'path';
import vue from '@vitejs/plugin-vue';
import eslintPlugin from 'vite-plugin-eslint';
import svgLoader from 'vite-svg-loader';
import vueI18n from '@intlify/vite-plugin-vue-i18n';
import { viteVConsole } from 'vite-plugin-vconsole';

import vueTypeImports from 'vite-plugin-vue-type-imports';

// https://vitejs.dev/config/
export default defineConfig({
	plugins: [
		vue(),
		vueTypeImports(),
		vueI18n({
			include: resolve(__dirname, './src/locales/**.json'),
		}),
		viteVConsole({
			entry: resolve('src/main.ts'), // entry file
			localEnabled: true, // dev environment
			enabled: false, // build production
			config: {
				maxLogNumber: 1000,
				theme: 'dark',
			},
		}),
		// eslintPlugin(),
		svgLoader(),
	],
	resolve: {
		alias: {
			'@fastybird': resolve(__dirname, './node_modules/@fastybird'),
			'@': resolve(__dirname, './src'),
		},
	},
	css: {
		modules: {
			localsConvention: 'camelCaseOnly',
		},
	},
	optimizeDeps: {
		include: ['vue', 'pinia', 'vue-router', 'nprogress', '@vueuse/core', 'vue-i18n'],
	},
	server: {
		proxy: {
			'/api': {
				target: 'http://10.10.10.116',
				rewrite: (path): string => {
					const apiPrefix: string = '/api';

					return path.replace(new RegExp(`^${apiPrefix}`, 'g'), ''); // Remove base path
				},
				secure: true,
				changeOrigin: true,
				headers: {
					// "X-Api-Key": null,
				},
			},
			'/ws-exchange': {
				target: 'ws://10.10.10.116:9000',
				rewrite: (path: string): string => {
					const wsPrefix: string = '/ws-exchange';

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
});
