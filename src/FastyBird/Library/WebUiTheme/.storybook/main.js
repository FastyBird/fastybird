const path = require('path')

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
    '@storybook/addon-essentials',
    '@storybook/addon-docs',
    '@storybook/addon-controls',
    '@storybook/addon-actions',
    '@storybook/addon-links',
    '@storybook/addon-viewport',
  ],
  framework: '@storybook/vue3',
  webpackFinal: async (config) => {
    // Remove 'svg' from the existing regex covering all images
    const svgRule = config.module.rules.find(rule => typeof rule.test.test === 'function' && rule.test.test('.svg'));
    svgRule.test = new RegExp(svgRule.test.source.replace('svg|', ''))

    config.resolve.alias = {
      ...config.resolve.alias,
      '@': path.resolve(__dirname, '../src/'),
    };

    config.resolve.modules = [
      ...(config.resolve.modules || []),
      path.resolve(__dirname, "../src"),
    ];

    config.module.rules.push({
      test: /\.scss$/,
      use: ['style-loader', 'css-loader', 'sass-loader'],
      include: path.resolve(__dirname, '../'),
    });

    config.module.rules.push({
      test: /\.mjs$/,
      include: /node_modules/,
      type: 'javascript/auto'
    });

    config.module.rules.push({
      test: /\.svg$/,
      use: [{loader: 'html-loader', options: {minimize: true, esModule: false}}],
    });

    // keep this if you're doing typescript
    // config.resolve.extensions.push(".ts", ".tsx");
    return config;
  },
}
