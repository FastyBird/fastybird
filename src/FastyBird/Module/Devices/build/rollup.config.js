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
  'ajv',
  'jsona',
  'jsona/lib/simplePropertyMappers',
  'lodash/capitalize',
  'lodash/clone',
  'lodash/get',
  'lodash/uniq',
  'uuid',
  'vue',
  'vuex',
  '@fastybird/metadata',
  '@fastybird/metadata/resources/schemas/devices-module/entity.device.property.json',
  '@fastybird/metadata/resources/schemas/devices-module/entity.device.json',
  '@fastybird/metadata/resources/schemas/devices-module/entity.device.configuration.json',
  '@fastybird/metadata/resources/schemas/devices-module/entity.channel.json',
  '@fastybird/metadata/resources/schemas/devices-module/entity.device.connector.json',
  '@fastybird/metadata/resources/schemas/devices-module/entity.channel.property.json',
  '@fastybird/metadata/resources/schemas/devices-module/entity.channel.configuration.json',
  '@fastybird/metadata/resources/schemas/devices-module/entity.connector.json',
  '@vuex-orm/core',
  'date-fns',
];

// UMD/IIFE shared settings: output.globals
// Refer to https://rollupjs.org/guide/en#output-globals for details
const globals = {
  // Provide global variable names to replace your external imports
  // eg. jquery: '$'
  ajv: 'Ajv',
  jsona: 'Jsona',
  'jsona/lib/simplePropertyMappers': 'defineRelationGetter',
  'lodash/capitalize': 'capitalize',
  'lodash/clone': 'clone',
  'lodash/get': 'get',
  'lodash/uniq': 'uniq',
  uuid: 'v4',
  vue: 'Vue',
  vuex: 'Vuex',
  '@fastybird/metadata': 'ModulesMetadata',
  '@fastybird/metadata/resources/schemas/devices-module/entity.device.property.json': 'DevicePropertyExchangeEntitySchema',
  '@fastybird/metadata/resources/schemas/devices-module/entity.device.json': 'DeviceExchangeEntitySchema',
  '@fastybird/metadata/resources/schemas/devices-module/entity.device.configuration.json': 'DeviceConfigurationExchangeEntitySchema',
  '@fastybird/metadata/resources/schemas/devices-module/entity.channel.json': 'ChannelExchangeEntitySchema',
  '@fastybird/metadata/resources/schemas/devices-module/entity.device.connector.json': 'DeviceConnectorExchangeEntitySchema',
  '@fastybird/metadata/resources/schemas/devices-module/entity.channel.property.json': 'ChannelPropertyExchangeEntitySchema',
  '@fastybird/metadata/resources/schemas/devices-module/entity.channel.configuration.json': 'ChannelConfigurationExchangeEntitySchema',
  '@fastybird/metadata/resources/schemas/devices-module/entity.connector.json': 'ConnectorExchangeEntitySchema',
  '@vuex-orm/core': 'OrmCore',
  'date-fns': 'dateFns',
};

// Customize configs for individual targets
const buildFormats = [];

if (!argv.format || argv.format === 'es') {
  const esConfig = {
    ...baseConfig,
    external,
    output: {
      file: 'dist/devices-module.esm.js',
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
      file: 'dist/devices-module.ssr.js',
      format: 'cjs',
      name: 'Devices',
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
      file: 'dist/devices-module.min.js',
      format: 'iife',
      name: 'Devices',
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
    file: 'dist/devices-module.d.ts',
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
