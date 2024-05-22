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
			rollupTypes: true,
		}),
	],
	resolve: {
		dedupe: ['vue', '@vueuse/core', 'element-plus'],
	},
	optimizeDeps: {
		include: ['vue', '@vueuse/core', 'element-plus'],
	},
	build: {
		lib: {
			entry: resolve(__dirname, './src/entry.ts'),
			name: 'web-ui-components',
			fileName: (format) => `web-ui-components.${format}.js`,
		},
		rollupOptions: {
			plugins: [
				// @ts-ignore
				del({
					targets: ['dist/types', 'dist/entry.ts'],
					hook: 'generateBundle',
				}),
			],
			external: ['vue', 'element-plus'],
			output: {
				// Provide global variables to use in the UMD build
				// for externalized deps
				globals: {
					vue: 'Vue',
					'element-plus': 'ElementPlus',
				},
			},
		},
		sourcemap: true,
		target: 'esnext',
	},
});
