import pluginSortImports from '@trivago/prettier-plugin-sort-imports';

export default {
	plugins: [pluginSortImports],
	printWidth: 150,
	tabWidth: 2,
	useTabs: true,
	semi: true,
	singleQuote: true,
	quoteProps: 'as-needed',
	jsxSingleQuote: false,
	trailingComma: 'es5',
	bracketSpacing: true,
	arrowParens: 'always',
	requirePragma: false,
	insertPragma: false,
	proseWrap: 'preserve',
	htmlWhitespaceSensitivity: 'css',
	vueIndentScriptAndStyle: false,
	endOfLine: 'auto',
	singleAttributePerLine: true,
};
