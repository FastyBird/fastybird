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
				'@fortawesome/vue-fontawesome',
				'@sentry/vue',
				'ajv',
				'axios',
				'date-fns',
				'jsona',
				'lodash.get',
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
			name: 'accounts-module',
			fileName: (format) => `accounts-module.${format}.js`,
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
				'@fortawesome/vue-fontawesome',
				'@sentry/vue',
				'ajv',
				'axios',
				'date-fns',
				'jsona',
				'lodash.get',
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
					'@fortawesome/vue-fontawesome': 'VueFontawesome',
					'@sentry/vue': 'SentryVue',
					ajv: 'Ajv',
					axios: 'Axios',
					'date-fns': 'dateFns',
					jsona: 'Jsona',
					'lodash.get': 'LodashGet',
					uuid: 'uuid',
					yup: 'Yup',
					pinia: 'pinia',
					'vee-validate': 'veeValidate',
					vue: 'vue',
					'vue-i18n': 'vueI18n',
					'vue-meta': 'VueMeta',
					'vue-router': 'VueRouter',
					'vue-toastification': 'vueToastification',
				},
				assetFileNames: (chunkInfo) => {
					if (chunkInfo.name == 'style.css') return 'accounts-module.css';

					return chunkInfo.name as string;
				},
			},
		},
		sourcemap: true,
		target: 'esnext',
	},
});
