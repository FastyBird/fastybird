import { resolve } from "path";
import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import dts from 'vite-plugin-dts'

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [
        dts({
            outputDir: 'dist',
            staticImport: true,
            insertTypesEntry: true,
            logDiagnostics: true
        }),
        vue(),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'src'),
        },
    },
    build: {
        lib: {
            entry: resolve(__dirname, 'src/entry.ts'),
            name: 'WebUiTheme',
            fileName: (format) => `web-ui-theme.${format}.js`,
        },
        rollupOptions: {
            external: [
                'vue',
            ],
            output: {
                sourcemap: true,
                // Provide global variables to use in the UMD build
                // for externalized deps
                globals: {
                    vue: 'Vue',
                },
                assetFileNames: (chunkInfo) => {
                  if (chunkInfo.name == 'style.css')
                    return 'web-ui-theme.css';

                  return chunkInfo.name as string;
                },
            },
        },
        sourcemap: true,
    },
})
