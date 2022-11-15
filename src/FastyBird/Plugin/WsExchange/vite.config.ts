import { resolve } from 'path';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import eslintPlugin from 'vite-plugin-eslint';
import dts from 'vite-plugin-dts';
import del from 'rollup-plugin-delete';

// https://vitejs.dev/config/
export default defineConfig({
	plugins: [
		vue(),
		eslintPlugin(),
		dts({
			outputDir: 'dist',
			staticImport: true,
			insertTypesEntry: true,
			noEmitOnError: true,
			skipDiagnostics: true,
		}),
	],
	resolve: {
		alias: {
			'@': resolve(__dirname, './public'),
		},
	},
	build: {
		lib: {
			entry: resolve(__dirname, './public/entry.ts'),
			name: 'ws-exchange-plugin',
			fileName: (format) => `ws-exchange-plugin.${format}.js`,
		},
		rollupOptions: {
			plugins: [
				// @ts-ignore
				del({
					targets: ['dist/types', 'dist/Client.ts', 'dist/entry.ts', 'dist/Logger.ts', 'dist/RpcCallError.ts', 'dist/useWsExchangeClient.ts'],
					hook: 'generateBundle',
				}),
			],
			external: ['vue'],
			output: {
				sourcemap: true,
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
