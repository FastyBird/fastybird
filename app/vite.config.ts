import { defineConfig } from 'vite';
import { resolve } from 'path';
import { viteVConsole } from 'vite-plugin-vconsole';
import svgLoader from 'vite-svg-loader';
import vue from '@vitejs/plugin-vue';
import vueI18n from '@intlify/unplugin-vue-i18n/vite';

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
				maxLogNumber: 1000,
				theme: 'dark',
			},
		}),
		svgLoader(),
	],
	resolve: {
		alias: {
			'@fastybird/accounts-module': resolve(__dirname, './../src/FastyBird/Module/Accounts/assets/entry.ts'),
			'@fastybird/devices-module': resolve(__dirname, './../src/FastyBird/Module/Devices/assets/entry.ts'),
			'@fastybird/triggers-module': resolve(__dirname, './../src/FastyBird/Module/Triggers/assets/entry.ts'),
			'@fastybird/metadata-library': resolve(__dirname, './../node_modules/@fastybird/metadata-library'),
			'@fastybird/web-ui-library': resolve(__dirname, './../node_modules/@fastybird/web-ui-library'),
			'@': resolve(__dirname, './assets'),
		},
		dedupe: ['vue', 'pinia', 'vue-router', 'vee-validate', 'vue-i18n', 'vue-meta', 'nprogress', '@vueuse/core'],
	},
	css: {
		modules: {
			localsConvention: 'camelCaseOnly',
		},
	},
	optimizeDeps: {
		include: ['vue', 'pinia', 'vue-router', 'vee-validate', 'vue-i18n', 'vue-meta', 'nprogress', '@vueuse/core'],
	},
	build: {
		outDir: resolve(__dirname, './../public/dist'),
	},
	server: {
		proxy: {
			'/api': {
				target: 'http://miniserver.local:8000',
				secure: true,
				changeOrigin: true,
			},
			'/ws-exchange': {
				target: 'ws://miniserver.local:8888',
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
