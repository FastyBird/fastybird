import remarkGfm from 'remark-gfm';

import type { StorybookConfig } from '@storybook/vue3-vite';

const config: StorybookConfig = {
	stories: [
		'../src/components/**/*.mdx',
		'../src/stories/**/*.mdx',
		'../src/components/**/*.stories.@(js|jsx|mjs|ts|tsx)',
		'../src/stories/**/*.stories.@(js|jsx|mjs|ts|tsx)',
	],
	addons: [
		'@chromatic-com/storybook',
		'@storybook/addon-a11y',
		'@storybook/addon-actions',
		'@storybook/addon-essentials',
		'@storybook/addon-interactions',
		'@storybook/addon-links',
		'@storybook/manager-api',
		'storybook-dark-mode',
		{
			name: '@storybook/addon-docs',
			options: {
				mdxPluginOptions: {
					mdxCompileOptions: {
						remarkPlugins: [remarkGfm],
					},
				},
			},
		},
	],
	framework: {
		name: '@storybook/vue3-vite',
		options: {
			/*docgen: 'vue-component-meta'*/
		},
	},
	docs: {
		autodocs: 'tag',
	},
};

export default config;
