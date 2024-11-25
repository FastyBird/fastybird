import pluginPrettier from 'eslint-plugin-prettier';
import pluginVue from 'eslint-plugin-vue';
import ts from 'typescript-eslint';

import js from '@eslint/js';

export default [
	// Base configuration
	js.configs.recommended,
	...ts.configs.recommended,
	...pluginVue.configs['flat/essential'],
	...pluginVue.configs['flat/strongly-recommended'],
	...pluginVue.configs['flat/recommended'],
	{
		plugins: {
			prettier: pluginPrettier,
		},
		languageOptions: {
			parserOptions: {
				parser: '@typescript-eslint/parser', // Use the TypeScript parser
			},
			globals: {
				GlobalEventHandlers: 'readonly',
				ScrollToOptions: 'readonly',
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
			'no-useless-computed-key': 'off',
			'@typescript-eslint/explicit-function-return-type': ['error'],
			'@typescript-eslint/ban-ts-comment': 'off',
			'@typescript-eslint/no-explicit-any': 'off',
			'prettier/prettier': ['error'],
		},
	},
];
