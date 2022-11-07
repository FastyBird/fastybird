const {loadConfigFromFile, mergeConfig} = require('vite');

const eslintPlugin = require('vite-plugin-eslint').default;

const path = require('path');

module.exports = {
    stories: [
        '../src/**/*.stories.mdx',
        '../src/**/*.stories.@(js|ts)',
        '../src/**/story.mdx',
        '../src/**/stories.mdx',
        '../src/**/story.@(js|ts)',
        '../src/**/stories.@(js|ts)',
    ],
    addons: [
        '@storybook/addon-links',
        '@storybook/addon-essentials',
        '@storybook/addon-a11y',
    ],
    framework: '@storybook/vue3',
    core: {
        builder: '@storybook/builder-vite',
    },
    typescript: {
        check: false,
        checkOptions: {},
    },
    async viteFinal(previousConfig) {
        const {config} = await loadConfigFromFile(
            path.resolve(__dirname, '../vite.config.ts')
        );

        return mergeConfig(previousConfig, {
            ...config,
            plugins: [
                eslintPlugin(),
            ],
        });
    },
};
