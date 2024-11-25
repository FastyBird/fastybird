import { resolve } from 'path';
import UnoCSS from 'unocss/vite';
import { defineConfig } from 'vite';
import dts from 'vite-plugin-dts';
import vueTypeImports from 'vite-plugin-vue-type-imports';
import svgLoader from 'vite-svg-loader';

import vueI18n from '@intlify/unplugin-vue-i18n/vite';
import eslint from '@nabla/vite-plugin-eslint';
import vue from '@vitejs/plugin-vue';

// https://vitejs.dev/config/
export default defineConfig({
	plugins: [
		vue(),
		vueTypeImports(),
		vueI18n({
			include: [resolve(__dirname, './locales/**.json')],
		}),
		eslint(),
		dts({
			outDir: 'dist',
			staticImport: true,
			insertTypesEntry: true,
			rollupTypes: true,
		}),
		svgLoader(),
		UnoCSS(),
	],
	build: {
		lib: {
			entry: resolve(__dirname, './assets/entry.ts'),
			name: 'accounts-module',
			fileName: (format) => `accounts-module.${format}.js`,
		},
		rollupOptions: {
			external: [
				'@fastybird/tools',
				'@fastybird/metadata-library',
				'@fastybird/web-ui-icons',
				'@fastybird/web-ui-library',
				'axios',
				'element-plus',
				'pinia',
				'unocss',
				'vue',
				'vue-i18n',
				'vue-meta',
				'vue-router',
			],
			output: {
				assetFileNames: (chunkInfo) => {
					if (chunkInfo.name == 'style.css') return 'accounts-module.css';

					return chunkInfo.name as string;
				},
				exports: 'named',
			},
		},
		sourcemap: true,
		target: 'esnext',
	},
});
