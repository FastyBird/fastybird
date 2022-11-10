import { resolve } from 'path';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import eslintPlugin from 'vite-plugin-eslint';
import dts from 'vite-plugin-dts';

// https://vitejs.dev/config/
export default defineConfig({
	plugins: [
		vue(),
		eslintPlugin(),
		dts({
			outputDir: 'dist',
			staticImport: true,
			insertTypesEntry: true,
		}),
	],
	resolve: {
		alias: {
			'@': resolve(__dirname, 'public'),
		},
	},
	build: {
		lib: {
			entry: resolve(__dirname, 'public/entry.ts'),
			name: 'devices-module',
			fileName: (format) => `devices-module.${format}.js`,
		},
		rollupOptions: {
			external: ['@fastybird/web-ui-library', '@fastybird/ws-exchange-plugin', 'vue', 'vue-toastification'],
			output: {
				sourcemap: true,
				// Provide global variables to use in the UMD build
				// for externalized deps
				globals: {
					vue: 'Vue',
				},
				assetFileNames: (chunkInfo) => {
					if (chunkInfo.name == 'style.css') return 'devices-module.css';

					return chunkInfo.name as string;
				},
			},
		},
		sourcemap: true,
		target: 'esnext',
	},
});
