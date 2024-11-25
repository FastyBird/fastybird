import { resolve } from 'path';
import { defineConfig } from 'vite';
import dts from 'vite-plugin-dts';
import del from 'rollup-plugin-delete';

import eslint from '@nabla/vite-plugin-eslint';

// https://vitejs.dev/config/
export default defineConfig({
	plugins: [
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
		},
		sourcemap: true,
		target: 'esnext',
	},
});
