import { resolve } from 'path';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import eslintPlugin from 'vite-plugin-eslint';
import dts from 'vite-plugin-dts';
import vueI18n from '@intlify/vite-plugin-vue-i18n';
import vueTypeImports from 'vite-plugin-vue-type-imports';
import del from 'rollup-plugin-delete';

// https://vitejs.dev/config/
export default defineConfig({
	plugins: [
		vue(),
		vueTypeImports(),
		vueI18n({
			include: resolve(__dirname, './locales/**.json'),
		}),
		eslintPlugin(),
		dts({
			outputDir: 'dist',
			staticImport: true,
			insertTypesEntry: true,
			noEmitOnError: true,
			skipDiagnostics: true,
			aliasesExclude: [
				'@fastybird/metadata-library',
				'@fastybird/web-ui-library',
				'@fastybird/ws-exchange-plugin',
				'ajv',
				'date-fns',
				'jsona',
				'lodash.capitalize',
				'lodash.get',
				'natural-orderby',
				'uuid',
				'yup',
				'pinia',
				'vee-validate',
				'vue',
				'vue-i18n',
				'vue-meta',
				'vue-router',
				'vue-toastification',
			],
		}),
	],
	resolve: {
		alias: {
			'@fastybird': resolve(__dirname, './node_modules/@fastybird'),
			'@': resolve(__dirname, './public'),
		},
	},
	build: {
		lib: {
			entry: resolve(__dirname, './public/entry.ts'),
			name: 'devices-module',
			fileName: (format) => `devices-module.${format}.js`,
		},
		rollupOptions: {
			plugins: [
				// @ts-ignore
				del({
					targets: [
						'dist/components',
						'dist/composables',
						'dist/errors',
						'dist/jsonapi',
						'dist/layouts',
						'dist/models',
						'dist/router',
						'dist/types',
						'dist/views',
						'dist/entry.ts',
					],
					hook: 'generateBundle',
				}),
			],
			external: [
				'@fastybird/metadata-library',
				'@fastybird/web-ui-library',
				'@fastybird/ws-exchange-plugin',
				'ajv',
				'date-fns',
				'jsona',
				'lodash.capitalize',
				'lodash.get',
				'natural-orderby',
				'uuid',
				'yup',
				'pinia',
				'vee-validate',
				'vue',
				'vue-i18n',
				'vue-meta',
				'vue-router',
				'vue-toastification',
			],
			output: {
				sourcemap: true,
				// Provide global variables to use in the UMD build
				// for externalized deps
				globals: {
					'@fastybird/metadata-library': 'FastyBirdMetadataLibrary',
					'@fastybird/web-ui-library': 'FastyBirdWebUiLibrary',
					'@fastybird/ws-exchange-plugin': 'FastyBirdWsExchangePlugin',
					ajv: 'Ajv',
					'date-fns': 'DateFns',
					jsona: 'Jsona',
					'lodash.capitalize': 'LodashCapitalize',
					'lodash.get': 'LodashGet',
					'natural-orderby': 'NaturalOrderby',
					uuid: 'Uuid',
					yup: 'Yup',
					pinia: 'Pinia',
					'vee-validate': 'VeeValidate',
					vue: 'Vue',
					'vue-i18n': 'VueI18n',
					'vue-meta': 'VueMeta',
					'vue-router': 'VueRouter',
					'vue-toastification': 'VueToastification',
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
