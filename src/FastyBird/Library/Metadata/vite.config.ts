import { resolve } from 'path';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import eslint from '@nabla/vite-plugin-eslint';
import dts from 'vite-plugin-dts';
import del from 'rollup-plugin-delete';

// https://vitejs.dev/config/
export default defineConfig({
	plugins: [
		vue(),
		eslint(),
		dts({
			outDir: 'dist',
			staticImport: true,
			insertTypesEntry: true,
		}),
	],
	resolve: {
		alias: {
			'@': resolve(__dirname, './assets'),
		},
	},
	build: {
		lib: {
			entry: resolve(__dirname, './assets/entry.ts'),
			name: 'metadata-library',
			fileName: (format) => `metadata-library.${format}.js`,
		},
		rollupOptions: {
			plugins: [
				// @ts-ignore
				del({
					targets: ['dist/types', 'dist/entry.ts'],
					hook: 'generateBundle',
				}),
			],
			external: ['vue'],
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
