import js from '@eslint/js';
import pluginPrettier from 'eslint-plugin-prettier';
import ts from 'typescript-eslint';

export default [
	// Base configuration
	js.configs.recommended,
	...ts.configs.recommended,
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
