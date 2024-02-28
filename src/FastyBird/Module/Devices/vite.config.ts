import { resolve } from 'path';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import eslint from '@nabla/vite-plugin-eslint';
import dts from 'vite-plugin-dts';
import vueI18n from '@intlify/unplugin-vue-i18n/vite';
import vueTypeImports from 'vite-plugin-vue-type-imports';
import svgLoader from 'vite-svg-loader';
import del from 'rollup-plugin-delete';

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
			aliasesExclude: [
				'@fastybird/metadata-library',
				'@fastybird/web-ui-library',
				'@fastybird/vue-wamp-v1',
				'@fortawesome/vue-fontawesome',
				'ajv',
				'axios',
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
		svgLoader(),
	],
	resolve: {
		alias: {
			'@fastybird': resolve(__dirname, './../../../../node_modules/@fastybird'),
		},
	},
	build: {
		lib: {
			entry: resolve(__dirname, './assets/entry.ts'),
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
						'dist/configuration.ts',
					],
					hook: 'generateBundle',
				}),
			],
			external: [
				'@fastybird/metadata-library',
				'@fastybird/web-ui-library',
				'@fastybird/vue-wamp-v1',
				'@fortawesome/vue-fontawesome',
				'ajv',
				'axios',
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
				// Provide global variables to use in the UMD build
				// for externalized deps
				globals: {
					'@fastybird/metadata-library': 'fastyBirdMetadataLibrary',
					'@fastybird/web-ui-library': 'fastyBirdWebUiLibrary',
					'@fastybird/vue-wamp-v1': 'fastyBirdVueWampV1',
					'@fortawesome/vue-fontawesome': 'VueFontawesome',
					ajv: 'Ajv',
					axios: 'Axios',
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
