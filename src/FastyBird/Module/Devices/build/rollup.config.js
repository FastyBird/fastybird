// rollup.config.js
import fs from 'fs';
import alias from '@rollup/plugin-alias';
import commonjs from '@rollup/plugin-commonjs';
import replace from '@rollup/plugin-replace';
import babel from '@rollup/plugin-babel';
import eslint from '@rollup/plugin-eslint';
import terser from '@rollup/plugin-terser';
import nodeResolve from '@rollup/plugin-node-resolve';
import minimist from 'minimist';
import typescript from 'rollup-plugin-typescript2';
import flatDts from 'rollup-plugin-flat-dts';

// Get browserslist config and remove ie from es build targets
const esbrowserslist = fs.readFileSync('./.browserslistrc')
    .toString()
    .split('\n')
    .filter((entry) => entry && entry.substring(0, 2) !== 'ie');

const argv = minimist(process.argv.slice(2));

const baseConfig = {
    input: 'public/entry.ts',
};

const basePlugins = {
    forAll: [
        typescript({
            typescript: require('ttypescript'),
            tsconfigDefaults: {
                compilerOptions: {
                    plugins: [
                        {"transform": "typescript-transform-paths"},
                        {"transform": "typescript-transform-paths", "afterDeclarations": true}
                    ]
                }
            }
        }),
        nodeResolve(),
        alias({
            resolve: ['.js', '.ts'],
        }),
        eslint(),
        flatDts({
            file: 'devices-module.d.ts'
        }),
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
  // e.g. 'jquery'
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
  '@fastybird/metadata-library',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.device.property.json',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.device.json',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.device.configuration.json',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.channel.json',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.device.connector.json',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.channel.property.json',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.channel.configuration.json',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.connector.json',
  '@vuex-orm/core',
  'date-fns',
];

// UMD/IIFE shared settings: output.globals
// Refer to https://rollupjs.org/guide/en#output-globals for details
const globals = {
  // Provide global variable names to replace your external imports
  // e.g. jquery: '$'
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
  '@fastybird/metadata-library': 'ModulesMetadata',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.device.property.json': 'DevicePropertyExchangeEntitySchema',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.device.json': 'DeviceExchangeEntitySchema',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.device.configuration.json': 'DeviceConfigurationExchangeEntitySchema',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.channel.json': 'ChannelExchangeEntitySchema',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.device.connector.json': 'DeviceConnectorExchangeEntitySchema',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.channel.property.json': 'ChannelPropertyExchangeEntitySchema',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.channel.configuration.json': 'ChannelConfigurationExchangeEntitySchema',
  '@fastybird/metadata-library/resources/schemas/devices-module/entity.connector.json': 'ConnectorExchangeEntitySchema',
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
            sourcemap: true,
        },
        plugins: [
            ...basePlugins.forAll,
            replace({
                ...basePlugins.replace,
                'process.env.ES_BUILD': JSON.stringify('true'),
            }),
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
            name: 'Metadata',
            exports: 'named',
            sourcemap: true,
            globals,
        },
        plugins: [
            ...basePlugins.forAll,
            replace(basePlugins.replace),
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
            name: 'Metadata',
            exports: 'named',
            sourcemap: true,
            globals,
        },
        plugins: [
            ...basePlugins.forAll,
            replace(basePlugins.replace),
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

// Export config
export default buildFormats;
