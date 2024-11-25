import { resolve } from 'path';
import del from 'rollup-plugin-delete';
import { defineConfig } from 'vite';
import dts from 'vite-plugin-dts';

import eslint from '@nabla/vite-plugin-eslint';
import vue from '@vitejs/plugin-vue';

// https://vitejs.dev/config/
export default defineConfig({
	plugins: [
		vue(),
		eslint(),
		dts({
			outDir: 'dist',
			staticImport: true,
			insertTypesEntry: true,
			rollupTypes: true,
		}),
	],
	build: {
		lib: {
			entry: resolve(__dirname, './assets/entry.ts'),
			name: 'tools',
			fileName: (format) => `tools.${format}.js`,
		},
		rollupOptions: {
			plugins: [
				// @ts-ignore
				del({
					targets: ['dist/types', 'dist/entry.ts'],
					hook: 'generateBundle',
				}),
			],
			external: ['@fastybird/metadata-library', '@vueuse/core', 'axios', 'element-plus', 'pinia', 'vue', 'vue-i18n', 'vue-router'],
			output: {
				// Provide global variables to use in the UMD build
				// for externalized deps
				globals: {
					vue: 'Vue',
				},
			},
		},
		sourcemap: true,
		target: 'esnext',
	},
});
