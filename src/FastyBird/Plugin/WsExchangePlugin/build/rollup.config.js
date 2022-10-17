// rollup.config.js
import fs from 'fs';
import path from 'path';
import alias from '@rollup/plugin-alias';
import commonjs from '@rollup/plugin-commonjs';
import replace from '@rollup/plugin-replace';
import babel from '@rollup/plugin-babel';
import eslint from '@rollup/plugin-eslint';
import dts from 'rollup-plugin-dts';
import {terser} from 'rollup-plugin-terser';
import minimist from 'minimist';

// Get browserslist config and remove ie from es build targets
const esbrowserslist = fs.readFileSync('./.browserslistrc')
  .toString()
  .split('\n')
  .filter((entry) => entry && entry.substring(0, 2) !== 'ie');

const argv = minimist(process.argv.slice(2));

const projectRoot = path.resolve(__dirname, '..');

const baseConfig = {
  input: 'public/entry.ts',
};

const basePlugins = {
  forAll: [
    alias({
      resolve: ['.js', '.ts'],
      entries: {
        '@': path.resolve(projectRoot, 'public'),
      },
    }),
    eslint(),
  ],
  replace: {
    preventAssignment: true,
    'process.env.NODE_ENV': JSON.stringify('production'),
    'process.env.ES_BUILD': JSON.stringify('false'),
  },
};

const babelConfig = {
  babelHelpers: 'bundled',
  exclude: 'node_modules/**',
  extensions: ['.js', '.ts',],
};

// ESM/UMD/IIFE shared settings: externals
// Refer to https://rollupjs.org/guide/en/#warning-treating-module-as-external-dependency
const external = [
  // list external dependencies, exactly the way it is written in the import statement.
  // eg. 'jquery'
  'vue',
];

// UMD/IIFE shared settings: output.globals
// Refer to https://rollupjs.org/guide/en#output-globals for details
const globals = {
  // Provide global variable names to replace your external imports
  // eg. jquery: '$'
  'vue' : 'Vue',
};

// Customize configs for individual targets
const buildFormats = [];

if (!argv.format || argv.format === 'es') {
  const esConfig = {
    ...baseConfig,
    external,
    output: {
      file: 'dist/ws-exchange-plugin.esm.js',
      format: 'esm',
      exports: 'named',
    },
    plugins: [
      replace({
        ...basePlugins.replace,
        'process.env.ES_BUILD': JSON.stringify('true'),
      }),
      ...basePlugins.forAll,
      babel({
        ...babelConfig,
        presets: [
          [
            '@babel/preset-env',
            {
              targets: esbrowserslist,
            },
          ],
        ],
      }),
      commonjs(),
    ],
  };
  buildFormats.push(esConfig);
}

if (!argv.format || argv.format === 'cjs') {
  const umdConfig = {
    ...baseConfig,
    external,
    output: {
      compact: true,
      file: 'dist/ws-exchange-plugin.ssr.js',
      format: 'cjs',
      name: 'VueWampV1',
      exports: 'named',
      globals,
    },
    plugins: [
      replace(basePlugins.replace),
      ...basePlugins.forAll,
      babel(babelConfig),
      commonjs(),
    ],
  };
  buildFormats.push(umdConfig);
}

if (!argv.format || argv.format === 'iife') {
  const unpkgConfig = {
    ...baseConfig,
    external,
    output: {
      compact: true,
      file: 'dist/ws-exchange-plugin.min.js',
      format: 'iife',
      name: 'VueWampV1',
      exports: 'named',
      globals,
    },
    plugins: [
      replace(basePlugins.replace),
      ...basePlugins.forAll,
      babel(babelConfig),
      commonjs(),
      terser({
        output: {
          ecma: 5,
        },
      }),
    ],
  };
  buildFormats.push(unpkgConfig);
}

buildFormats.push({
  ...baseConfig,
  external,
  output: {
    file: 'dist/ws-exchange-plugin.d.ts',
    format: 'es',
  },
  plugins: [
    replace({
      ...basePlugins.replace,
      'process.env.ES_BUILD': JSON.stringify('true'),
    }),
    ...basePlugins.forAll,
    babel({
      ...babelConfig,
      presets: [
        [
          '@babel/preset-env',
          {
            targets: esbrowserslist,
          },
        ],
      ],
    }),
    commonjs(),
    dts(),
  ],
});

// Export config
export default buildFormats;
