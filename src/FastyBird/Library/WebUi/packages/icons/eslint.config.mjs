import js from '@eslint/js';
import ts from 'typescript-eslint';
import pluginPrettier from 'eslint-plugin-prettier/recommended';
import pluginVue from 'eslint-plugin-vue';

export default [
	js.configs.recommended,
	...ts.configs.recommended,
	...pluginVue.configs['flat/essential'],
	...pluginVue.configs['flat/strongly-recommended'],
	...pluginVue.configs['flat/recommended'],
	pluginPrettier,
	{
		languageOptions: {
			parserOptions: {
				parser: '@typescript-eslint/parser'
			},
		},
		rules: {
			'lines-between-class-members': [
				'error',
				'always',
				{
					exceptAfterSingleLine: true,
				},
			],
			'vue/multi-word-component-names': 'off',
			'@typescript-eslint/explicit-function-return-type': ['error'],
			'@typescript-eslint/ban-ts-comment': 'off',
			'@typescript-eslint/no-explicit-any': 'off',
			'prettier/prettier': ['error'],
		},
	},
];
