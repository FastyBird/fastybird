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
                        { "transform": "typescript-transform-paths" },
                        { "transform": "typescript-transform-paths", "afterDeclarations": true }
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
            file: 'metadata-library.d.ts'
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
    'date-fns',
];

// UMD/IIFE shared settings: output.globals
// Refer to https://rollupjs.org/guide/en#output-globals for details
const globals = {
    // Provide global variable names to replace your external imports
    // e.g. jquery: '$'
    'date-fns': 'dateFns',
};

// Customize configs for individual targets
const buildFormats = [];

if (!argv.format || argv.format === 'es') {
    const esConfig = {
        ...baseConfig,
        external,
        output: {
            file: 'dist/metadata-library.esm.js',
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
            file: 'dist/metadata-library.ssr.js',
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
            file: 'dist/metadata-library.min.js',
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
